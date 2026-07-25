<?php

namespace Tests\Feature\Family;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMemberTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Family}
     */
    private function adminWithFamily(): array
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);

        return [$user, $family];
    }

    private function addMember(Family $family, FamilyRole $role = FamilyRole::Member): FamilyMember
    {
        return $family->members()->create([
            'user_id' => User::factory()->create()->id,
            'role' => $role,
            'relationship' => Relationship::Son,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Ravi Kumar',
            'email' => 'ravi@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => FamilyRole::Member->value,
            'relationship' => Relationship::Son->value,
        ];
    }

    public function test_guests_cannot_view_the_member_list(): void
    {
        $this->get(route('family.index'))->assertRedirect(route('login'));
    }

    public function test_a_member_can_view_the_family_list(): void
    {
        [, $family] = $this->adminWithFamily();
        $member = $this->addMember($family);

        $this->actingAs($member->user)
            ->get(route('family.index'))
            ->assertOk();
    }

    public function test_an_admin_can_add_a_member_and_a_login_account_is_created(): void
    {
        [$admin, $family] = $this->adminWithFamily();

        $this->actingAs($admin)
            ->post(route('family.store'), $this->validPayload())
            ->assertRedirect(route('family.index'));

        $this->assertDatabaseHas('users', ['email' => 'ravi@example.com']);

        $newUser = User::firstWhere('email', 'ravi@example.com');

        $this->assertDatabaseHas('family_members', [
            'family_id' => $family->id,
            'user_id' => $newUser->id,
            'role' => FamilyRole::Member->value,
            'relationship' => Relationship::Son->value,
        ]);
    }

    public function test_the_new_member_can_log_in_with_the_assigned_password(): void
    {
        [$admin] = $this->adminWithFamily();

        $this->actingAs($admin)->post(route('family.store'), $this->validPayload());

        auth()->logout();

        $this->post('/login', [
            'email' => 'ravi@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_a_plain_member_cannot_add_members(): void
    {
        [, $family] = $this->adminWithFamily();
        $member = $this->addMember($family);

        $this->actingAs($member->user)
            ->post(route('family.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'ravi@example.com']);
    }

    public function test_a_plain_member_cannot_edit_another_membership(): void
    {
        [, $family] = $this->adminWithFamily();
        $member = $this->addMember($family);
        $target = $this->addMember($family);

        $this->actingAs($member->user)
            ->put(route('family.update', $target), [
                'role' => FamilyRole::Admin->value,
                'relationship' => Relationship::Son->value,
            ])
            ->assertForbidden();
    }

    public function test_an_admin_can_promote_a_member_to_admin(): void
    {
        [$admin, $family] = $this->adminWithFamily();
        $member = $this->addMember($family);

        $this->actingAs($admin)
            ->put(route('family.update', $member), [
                'role' => FamilyRole::Admin->value,
                'relationship' => Relationship::Daughter->value,
            ])
            ->assertRedirect(route('family.index'));

        $member->refresh();

        $this->assertSame(FamilyRole::Admin, $member->role);
        $this->assertSame(Relationship::Daughter, $member->relationship);
    }

    public function test_the_family_creator_cannot_be_demoted(): void
    {
        [$admin, $family] = $this->adminWithFamily();
        $creatorMembership = $family->members()->firstWhere('user_id', $admin->id);

        $this->actingAs($admin)
            ->put(route('family.update', $creatorMembership), [
                'role' => FamilyRole::Member->value,
                'relationship' => Relationship::Head->value,
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame(FamilyRole::Admin, $creatorMembership->refresh()->role);
    }

    public function test_an_admin_can_remove_a_member(): void
    {
        [$admin, $family] = $this->adminWithFamily();
        $member = $this->addMember($family);

        $this->actingAs($admin)
            ->delete(route('family.destroy', $member))
            ->assertRedirect(route('family.index'));

        $this->assertDatabaseMissing('family_members', ['id' => $member->id]);
    }

    public function test_the_family_creator_cannot_be_removed(): void
    {
        [$admin, $family] = $this->adminWithFamily();
        $otherAdmin = $this->addMember($family, FamilyRole::Admin);
        $creatorMembership = $family->members()->firstWhere('user_id', $admin->id);

        $this->actingAs($otherAdmin->user)
            ->delete(route('family.destroy', $creatorMembership))
            ->assertSessionHasErrors('member');

        $this->assertDatabaseHas('family_members', ['id' => $creatorMembership->id]);
    }

    public function test_an_admin_cannot_remove_themselves(): void
    {
        [, $family] = $this->adminWithFamily();
        $selfAdmin = $this->addMember($family, FamilyRole::Admin);

        $this->actingAs($selfAdmin->user)
            ->delete(route('family.destroy', $selfAdmin))
            ->assertSessionHasErrors('member');

        $this->assertDatabaseHas('family_members', ['id' => $selfAdmin->id]);
    }

    public function test_an_admin_cannot_manage_another_familys_member(): void
    {
        [$admin] = $this->adminWithFamily();
        [, $otherFamily] = $this->adminWithFamily();
        $foreignMember = $this->addMember($otherFamily);

        $this->actingAs($admin)
            ->get(route('family.edit', $foreignMember))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('family.destroy', $foreignMember))
            ->assertForbidden();
    }

    public function test_adding_a_member_requires_a_unique_email(): void
    {
        [$admin] = $this->adminWithFamily();

        $payload = $this->validPayload();
        $payload['email'] = $admin->email;

        $this->actingAs($admin)
            ->post(route('family.store'), $payload)
            ->assertSessionHasErrors('email');
    }
}

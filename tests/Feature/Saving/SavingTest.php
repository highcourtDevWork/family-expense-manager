<?php

namespace Tests\Feature\Saving;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Enums\SavingType;
use App\Models\Family;
use App\Models\Saving;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Family}
     */
    private function userWithFamily(): array
    {
        $user = User::factory()->create();
        $family = app(FamilyService::class)->createForUser($user);

        return [$user, $family];
    }

    private function addMember(Family $family): User
    {
        $user = User::factory()->create();
        $family->members()->create([
            'user_id' => $user->id,
            'role' => FamilyRole::Member,
            'relationship' => Relationship::Son,
        ]);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'saving_type' => SavingType::Gold->value,
            'amount' => 25000,
            'date' => now()->format('Y-m-d'),
            'notes' => 'Diwali gold',
        ];
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('savings.index'))->assertRedirect(route('login'));
    }

    public function test_a_member_can_record_a_saving(): void
    {
        [$user, $family] = $this->userWithFamily();

        $this->actingAs($user)
            ->post(route('savings.store'), $this->validPayload())
            ->assertRedirect(route('savings.index'));

        $this->assertDatabaseHas('savings', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'saving_type' => SavingType::Gold->value,
            'amount' => 25000,
        ]);
    }

    public function test_creating_a_saving_requires_valid_data(): void
    {
        [$user] = $this->userWithFamily();

        $this->actingAs($user)
            ->post(route('savings.store'), ['amount' => 'nope'])
            ->assertSessionHasErrors(['saving_type', 'amount', 'date']);
    }

    public function test_a_member_can_update_their_own_saving(): void
    {
        [$user, $family] = $this->userWithFamily();
        $saving = Saving::factory()->for($family)->create(['user_id' => $user->id]);

        $payload = $this->validPayload();
        $payload['amount'] = 99999;

        $this->actingAs($user)->put(route('savings.update', $saving), $payload)->assertRedirect();
        $this->assertSame('99999.00', $saving->refresh()->amount);
    }

    public function test_a_member_cannot_update_another_members_saving(): void
    {
        [$owner, $family] = $this->userWithFamily();
        $other = $this->addMember($family);
        $saving = Saving::factory()->for($family)->create(['user_id' => $owner->id]);

        $this->actingAs($other)->put(route('savings.update', $saving), $this->validPayload())->assertForbidden();
    }

    public function test_an_admin_can_update_any_family_saving(): void
    {
        [$admin, $family] = $this->userWithFamily();
        $member = $this->addMember($family);
        $saving = Saving::factory()->for($family)->create(['user_id' => $member->id]);

        $payload = $this->validPayload();
        $payload['amount'] = 55555;

        $this->actingAs($admin)->put(route('savings.update', $saving), $payload)->assertRedirect();
        $this->assertSame('55555.00', $saving->refresh()->amount);
    }

    public function test_a_user_cannot_touch_another_familys_saving(): void
    {
        [$user] = $this->userWithFamily();
        [, $otherFamily] = $this->userWithFamily();
        $foreign = Saving::factory()->for($otherFamily)->create(['user_id' => $otherFamily->created_by]);

        $this->actingAs($user)->get(route('savings.edit', $foreign))->assertForbidden();
    }

    public function test_deleting_a_saving_soft_deletes_it(): void
    {
        [$user, $family] = $this->userWithFamily();
        $saving = Saving::factory()->for($family)->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('savings.destroy', $saving))->assertRedirect(route('savings.index'));
        $this->assertSoftDeleted($saving);
    }
}

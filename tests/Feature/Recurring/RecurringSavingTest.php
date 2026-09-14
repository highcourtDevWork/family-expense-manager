<?php

namespace Tests\Feature\Recurring;

use App\Enums\FamilyRole;
use App\Enums\Relationship;
use App\Enums\SavingType;
use App\Models\Family;
use App\Models\RecurringSaving;
use App\Models\User;
use App\Services\FamilyService;
use App\Services\RecurringSavingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringSavingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

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
            'relationship' => Relationship::Spouse,
        ]);

        return $user;
    }

    public function test_a_member_can_create_a_savings_template(): void
    {
        [$user, $family] = $this->userWithFamily();

        $this->actingAs($user)->post(route('recurring-savings.store'), [
            'name' => 'Monthly SIP',
            'amount' => 15000,
            'saving_type' => SavingType::Mutual->value,
            'user_id' => $user->id,
            'auto_post' => '1',
        ])->assertRedirect(route('recurring-savings.index'));

        $this->assertDatabaseHas('recurring_savings', [
            'family_id' => $family->id,
            'user_id' => $user->id,
            'name' => 'Monthly SIP',
            'amount' => 15000,
            'saving_type' => SavingType::Mutual->value,
            'auto_post' => true,
        ]);
    }

    public function test_both_members_savings_post_and_attribute_to_the_right_saver(): void
    {
        [$me, $family] = $this->userWithFamily();
        $wife = $this->addMember($family);

        $mine = RecurringSaving::factory()->for($family)->create([
            'user_id' => $me->id, 'name' => 'My SIP', 'amount' => 15000, 'saving_type' => SavingType::Mutual,
        ]);
        $hers = RecurringSaving::factory()->for($family)->create([
            'user_id' => $wife->id, 'name' => 'Gold Plan', 'amount' => 8000, 'saving_type' => SavingType::Gold,
        ]);

        $this->actingAs($me)->post(route('recurring-savings.post', $mine))->assertRedirect();
        $this->actingAs($me)->post(route('recurring-savings.post', $hers))->assertRedirect();

        // Each saving is attributed to its member, not to whoever posted it.
        $this->assertDatabaseHas('savings', [
            'family_id' => $family->id, 'user_id' => $me->id, 'amount' => 15000, 'notes' => 'My SIP',
            'saving_type' => SavingType::Mutual->value,
            'reference' => 'recurring-saving:'.$mine->id.':2026-07', 'date' => '2026-07-01',
        ]);
        $this->assertDatabaseHas('savings', [
            'family_id' => $family->id, 'user_id' => $wife->id, 'amount' => 8000, 'notes' => 'Gold Plan',
            'saving_type' => SavingType::Gold->value,
            'reference' => 'recurring-saving:'.$hers->id.':2026-07',
        ]);
    }

    public function test_posting_twice_in_a_month_does_not_duplicate(): void
    {
        [$user, $family] = $this->userWithFamily();
        $template = RecurringSaving::factory()->for($family)->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('recurring-savings.post', $template));
        $this->actingAs($user)->post(route('recurring-savings.post', $template))
            ->assertSessionHas('status', 'recurring-saving-already-posted');

        $this->assertDatabaseCount('savings', 1);
    }

    public function test_the_same_template_posts_again_in_a_new_month(): void
    {
        [$user, $family] = $this->userWithFamily();
        $template = RecurringSaving::factory()->for($family)->create(['user_id' => $user->id]);
        $service = app(RecurringSavingService::class);

        $this->assertNotNull($service->postForMonth($template, Carbon::parse('2026-07-01')));
        $this->assertNotNull($service->postForMonth($template, Carbon::parse('2026-08-01')));

        $this->assertDatabaseCount('savings', 2);
    }

    public function test_the_scheduler_only_posts_active_auto_post_templates(): void
    {
        [$user, $family] = $this->userWithFamily();
        RecurringSaving::factory()->for($family)->create(['user_id' => $user->id, 'auto_post' => true, 'active' => true]);
        RecurringSaving::factory()->for($family)->create(['user_id' => $user->id, 'auto_post' => false, 'active' => true]);
        RecurringSaving::factory()->for($family)->create(['user_id' => $user->id, 'auto_post' => true, 'active' => false]);

        $created = app(RecurringSavingService::class)->postAllDue(Carbon::parse('2026-07-01'));

        $this->assertSame(1, $created);
        $this->assertDatabaseCount('savings', 1);
    }

    public function test_the_saver_must_belong_to_the_family(): void
    {
        [$user] = $this->userWithFamily();
        [$outsider] = $this->userWithFamily(); // a different family's user

        $this->actingAs($user)->post(route('recurring-savings.store'), [
            'name' => 'Bogus',
            'amount' => 1000,
            'saving_type' => SavingType::Bank->value,
            'user_id' => $outsider->id,
        ])->assertSessionHasErrors('user_id');
    }

    public function test_a_user_cannot_post_another_familys_template(): void
    {
        [$user] = $this->userWithFamily();
        [$otherUser, $otherFamily] = $this->userWithFamily();
        $foreign = RecurringSaving::factory()->for($otherFamily)->create(['user_id' => $otherUser->id]);

        $this->actingAs($user)->post(route('recurring-savings.post', $foreign))->assertForbidden();
    }
}

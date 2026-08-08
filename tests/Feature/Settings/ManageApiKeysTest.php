<?php

namespace Tests\Feature\Settings;

use App\Models\ApiCredential;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageApiKeysTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = User::factory()->create();
        app(FamilyService::class)->createForUser($user);

        return $user;
    }

    public function test_generating_a_key_reveals_the_secret_once(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('api-keys.store'), ['name' => 'Mobile app'])
            ->assertRedirect(route('api-keys.index'))
            ->assertSessionHas('new_api_secret')
            ->assertSessionHas('new_api_key');

        $this->assertDatabaseHas('api_credentials', ['user_id' => $user->id, 'name' => 'Mobile app']);
    }

    public function test_a_user_can_delete_their_own_key(): void
    {
        $user = $this->user();
        [$credential] = ApiCredential::generate($user, 'Temp');

        $this->actingAs($user)
            ->delete(route('api-keys.destroy', $credential))
            ->assertRedirect(route('api-keys.index'));

        $this->assertDatabaseMissing('api_credentials', ['id' => $credential->id]);
    }

    public function test_a_user_cannot_delete_someone_elses_key(): void
    {
        $user = $this->user();
        $other = $this->user();
        [$foreign] = ApiCredential::generate($other, 'Theirs');

        $this->actingAs($user)
            ->delete(route('api-keys.destroy', $foreign))
            ->assertForbidden();

        $this->assertDatabaseHas('api_credentials', ['id' => $foreign->id]);
    }
}

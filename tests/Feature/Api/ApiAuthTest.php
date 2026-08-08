<?php

namespace Tests\Feature\Api;

use App\Models\ApiCredential;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: array<string, string>, 2: string}
     */
    private function userWithKey(): array
    {
        $user = User::factory()->create();
        app(FamilyService::class)->createForUser($user);
        [$credential, $secret] = ApiCredential::generate($user, 'Test key');

        return [$user, [
            'X-Api-Key' => $credential->key,
            'X-Api-Secret' => $secret,
            'Accept' => 'application/json',
        ], $secret];
    }

    public function test_valid_key_and_secret_authenticate(): void
    {
        [$user, $headers] = $this->userWithKey();

        $this->getJson('/api/v1/user', $headers)
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.family.role', 'admin');
    }

    public function test_using_the_key_records_last_used_at(): void
    {
        [$user, $headers] = $this->userWithKey();

        $this->getJson('/api/v1/user', $headers)->assertOk();

        $this->assertNotNull($user->apiCredentials()->first()->last_used_at);
    }

    public function test_missing_headers_are_rejected(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_a_wrong_secret_is_rejected(): void
    {
        [, $headers] = $this->userWithKey();
        $headers['X-Api-Secret'] = 'fem_sec_wrong';

        $this->getJson('/api/v1/user', $headers)->assertUnauthorized();
    }

    public function test_an_unknown_key_is_rejected(): void
    {
        $headers = [
            'X-Api-Key' => 'fem_key_does_not_exist',
            'X-Api-Secret' => 'fem_sec_whatever',
            'Accept' => 'application/json',
        ];

        $this->getJson('/api/v1/user', $headers)->assertUnauthorized();
    }

    public function test_a_revoked_key_is_rejected(): void
    {
        [$user, $headers] = $this->userWithKey();
        $user->apiCredentials()->first()->update(['revoked_at' => now()]);

        $this->getJson('/api/v1/user', $headers)->assertUnauthorized();
    }

    public function test_the_secret_is_stored_hashed_not_in_plaintext(): void
    {
        [$user, , $secret] = $this->userWithKey();

        $stored = $user->apiCredentials()->first()->secret;

        $this->assertNotSame($secret, $stored);
        $this->assertTrue(Hash::check($secret, $stored));
    }
}

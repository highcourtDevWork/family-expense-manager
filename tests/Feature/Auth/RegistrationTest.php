<?php

namespace Tests\Feature\Auth;

use App\Enums\FamilyRole;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_creates_a_family_with_the_user_as_admin(): void
    {
        $this->post('/register', [
            'name' => 'Kumar',
            'email' => 'kumar@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::firstWhere('email', 'kumar@example.com');
        $family = $user->currentFamily();

        $this->assertNotNull($family);
        $this->assertSame("Kumar's Family", $family->name);
        $this->assertTrue($user->isAdminOf($family));
        $this->assertEquals(FamilyRole::Admin, $family->members()->first()->role);
    }

    public function test_registration_seeds_the_default_expense_categories(): void
    {
        $this->post('/register', [
            'name' => 'Kumar',
            'email' => 'kumar@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $family = User::firstWhere('email', 'kumar@example.com')->currentFamily();

        $this->assertCount(count(FamilyService::DEFAULT_CATEGORIES), $family->categories);
        $this->assertNotNull($family->categories()->firstWhere('name', 'Food'));
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}

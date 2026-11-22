<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertDontSee('Lupa Password?');
    }

    public function test_admins_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_students_cannot_authenticate_using_the_admin_login_screen(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        $this->post('/login', [
            'email' => $student->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_technicians_cannot_authenticate_using_the_admin_login_screen(): void
    {
        $technician = User::factory()->create(['role' => 'teknisi']);

        $this->post('/login', [
            'email' => $technician->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}

<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register_through_the_mobile_api(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'nama' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nama', 'Test User')
            ->assertJsonPath('data.role', 'siswa');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'siswa',
        ]);
    }
}

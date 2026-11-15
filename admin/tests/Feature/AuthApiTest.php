<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_password_change_requires_and_checks_the_current_password(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $payload = [
            'user_id' => $user->id,
            'nama' => $user->nama,
            'password' => 'new-password',
        ];

        $this->putJson('/api/auth/profile', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->putJson('/api/auth/profile', [
            ...$payload,
            'current_password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.current_password.0', 'Kata sandi saat ini tidak sesuai.');

        $this->putJson('/api/auth/profile', [
            ...$payload,
            'current_password' => 'password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }
}

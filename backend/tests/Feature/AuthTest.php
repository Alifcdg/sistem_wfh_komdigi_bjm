<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create([
            'nama_role' => 'Pegawai',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::create([
            'role_id' => 1,
            'username' => 'pegawai_test',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'pegawai_test',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login berhasil.')
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id_user',
                    'username',
                    'role',
                    'pegawai',
                ],
            ]);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::create([
            'role_id' => 1,
            'username' => 'pegawai_test',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'pegawai_test',
            'password' => 'salah_password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Username atau password salah.',
            ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'role_id' => 1,
            'username' => 'pegawai_nonaktif',
            'password' => 'password123',
            'status_akun' => 'Nonaktif',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'pegawai_nonaktif',
            'password' => 'password123',
        ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Akun tidak aktif.',
            ]);
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::create([
            'role_id' => 1,
            'username' => 'pegawai_test',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/profile');

        $response
            ->assertOk()
            ->assertJsonPath('user.username', 'pegawai_test');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::create([
            'role_id' => 1,
            'username' => 'pegawai_test',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/logout');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Logout berhasil.',
            ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['nama_role' => 'Admin']);
        Role::create(['nama_role' => 'Pegawai']);
        Role::create(['nama_role' => 'Kepala Balai']);
    }

    private function loginAsAdmin(): User
    {
        $adminRole = Role::where('nama_role', 'Admin')->firstOrFail();

        $admin = User::create([
            'pegawai_id' => null,
            'role_id' => $adminRole->id_role,
            'username' => 'admin.test',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        Sanctum::actingAs($admin);

        return $admin;
    }

    private function createPegawai(): Pegawai
    {
        return Pegawai::create([
            'kendaraan_id' => null,
            'nip' => '199901012026011001',
            'nama' => 'Pegawai Test',
            'jabatan' => 'Staf',
            'unit_kerja' => 'Teknologi Informasi',
            'alamat_rumah' => 'Banjarmasin',
            'jarak_kantor_km' => 5.5,
            'status_pegawai' => 'Aktif',
        ]);
    }

    public function test_admin_can_view_user_list(): void
    {
        $this->loginAsAdmin();

        $response = $this->getJson('/api/admin/users');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
            ]);
    }

    public function test_admin_can_create_kepala_balai_account(): void
    {
        $this->loginAsAdmin();

        $response = $this->postJson('/api/admin/users', [
            'role_id' => Role::where('nama_role', 'Kepala Balai')->value('id_role'),
            'username' => 'kepala.test',
            'password' => 'password123',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'username' => 'kepala.test',
            'pegawai_id' => null,
            'status_akun' => 'Aktif',
        ]);
    }

    public function test_admin_can_create_pegawai_account(): void
    {
        $this->loginAsAdmin();

        $pegawai = $this->createPegawai();

        $response = $this->postJson('/api/admin/users', [
            'role_id' => Role::where('nama_role', 'Pegawai')->value('id_role'),
            'pegawai_id' => $pegawai->id_pegawai,
            'username' => 'pegawai.test',
            'password' => 'password123',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'pegawai_id' => $pegawai->id_pegawai,
            'username' => 'pegawai.test',
        ]);
    }

    public function test_admin_can_update_user_account(): void
    {
        $this->loginAsAdmin();

        $role = Role::where('nama_role', 'Kepala Balai')->firstOrFail();

        $user = User::create([
            'pegawai_id' => null,
            'role_id' => $role->id_role,
            'username' => 'kepala.lama',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $response = $this->putJson("/api/admin/users/{$user->id_user}", [
            'role_id' => $role->id_role,
            'username' => 'kepala.baru',
            'password' => '',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id_user' => $user->id_user,
            'username' => 'kepala.baru',
        ]);
    }

    public function test_admin_cannot_update_admin_account(): void
    {
        $admin = $this->loginAsAdmin();

        $response = $this->putJson("/api/admin/users/{$admin->id_user}", [
            'role_id' => Role::where('nama_role', 'Pegawai')->value('id_role'),
            'pegawai_id' => $this->createPegawai()->id_pegawai,
            'username' => 'admin.baru',
            'password' => '',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id_user' => $admin->id_user,
            'username' => 'admin.test',
        ]);
    }

    public function test_admin_can_deactivate_and_activate_user_account(): void
    {
        $this->loginAsAdmin();

        $role = Role::where('nama_role', 'Kepala Balai')->firstOrFail();

        $user = User::create([
            'pegawai_id' => null,
            'role_id' => $role->id_role,
            'username' => 'kepala.test',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $this->patchJson("/api/admin/users/{$user->id_user}/status", [
            'status_akun' => 'Tidak Aktif',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id_user' => $user->id_user,
            'status_akun' => 'Tidak Aktif',
        ]);

        $this->patchJson("/api/admin/users/{$user->id_user}/status", [
            'status_akun' => 'Aktif',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id_user' => $user->id_user,
            'status_akun' => 'Aktif',
        ]);
    }

    public function test_admin_account_cannot_be_deleted(): void
    {
        $admin = $this->loginAsAdmin();

        $response = $this->deleteJson("/api/admin/users/{$admin->id_user}");

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id_user' => $admin->id_user,
        ]);
    }

    public function test_user_account_can_be_deleted_without_deleting_pegawai(): void
    {
        $this->loginAsAdmin();

        $pegawai = $this->createPegawai();

        $role = Role::where('nama_role', 'Pegawai')->firstOrFail();

        $user = User::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'role_id' => $role->id_role,
            'username' => 'pegawai.hapus',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $response = $this->deleteJson("/api/admin/users/{$user->id_user}");

        $response->assertOk();

        $this->assertDatabaseMissing('users', [
            'id_user' => $user->id_user,
        ]);

        $this->assertDatabaseHas('pegawais', [
            'id_pegawai' => $pegawai->id_pegawai,
            'nama' => 'Pegawai Test',
        ]);
    }
}

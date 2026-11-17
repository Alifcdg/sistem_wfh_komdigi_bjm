<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PegawaiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['nama_role' => 'Admin']);
        Role::create(['nama_role' => 'Pegawai']);
        Role::create(['nama_role' => 'Kepala Balai']);
    }

    private function loginAsAdmin(): void
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
    }

    private function createPegawai(string $status = 'Aktif'): Pegawai
    {
        return Pegawai::create([
            'kendaraan_id' => null,
            'nip' => '199901012026011001',
            'nama' => 'Pegawai Test',
            'jabatan' => 'Staf',
            'unit_kerja' => 'Teknologi Informasi',
            'alamat_rumah' => 'Banjarmasin',
            'jarak_kantor_km' => 5.5,
            'status_pegawai' => $status,
        ]);
    }

    private function createUserForPegawai(Pegawai $pegawai, string $status = 'Aktif'): User
    {
        $pegawaiRole = Role::where('nama_role', 'Pegawai')->firstOrFail();

        return User::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'role_id' => $pegawaiRole->id_role,
            'username' => 'pegawai.test',
            'password' => 'password123',
            'status_akun' => $status,
        ]);
    }

    public function test_updating_employee_to_inactive_also_deactivates_user_account(): void
    {
        $this->loginAsAdmin();

        $pegawai = $this->createPegawai();
        $user = $this->createUserForPegawai($pegawai);

        $response = $this->putJson("/api/pegawai/{$pegawai->id_pegawai}", [
            'nip' => $pegawai->nip,
            'nama' => $pegawai->nama,
            'jabatan' => $pegawai->jabatan,
            'unit_kerja' => $pegawai->unit_kerja,
            'alamat_rumah' => $pegawai->alamat_rumah,
            'jarak_kantor_km' => $pegawai->jarak_kantor_km,
            'kendaraan_id' => null,
            'status_pegawai' => 'Tidak Aktif',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('pegawais', [
            'id_pegawai' => $pegawai->id_pegawai,
            'status_pegawai' => 'Tidak Aktif',
        ]);

        $this->assertDatabaseHas('users', [
            'id_user' => $user->id_user,
            'status_akun' => 'Tidak Aktif',
        ]);
    }

    public function test_updating_employee_to_active_also_activates_user_account(): void
    {
        $this->loginAsAdmin();

        $pegawai = $this->createPegawai('Tidak Aktif');
        $user = $this->createUserForPegawai($pegawai, 'Tidak Aktif');

        $response = $this->putJson("/api/pegawai/{$pegawai->id_pegawai}", [
            'nip' => $pegawai->nip,
            'nama' => $pegawai->nama,
            'jabatan' => $pegawai->jabatan,
            'unit_kerja' => $pegawai->unit_kerja,
            'alamat_rumah' => $pegawai->alamat_rumah,
            'jarak_kantor_km' => $pegawai->jarak_kantor_km,
            'kendaraan_id' => null,
            'status_pegawai' => 'Aktif',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('pegawais', [
            'id_pegawai' => $pegawai->id_pegawai,
            'status_pegawai' => 'Aktif',
        ]);

        $this->assertDatabaseHas('users', [
            'id_user' => $user->id_user,
            'status_akun' => 'Aktif',
        ]);
    }

    public function test_employee_without_user_account_can_update_status(): void
    {
        $this->loginAsAdmin();

        $pegawai = $this->createPegawai();

        $response = $this->putJson("/api/pegawai/{$pegawai->id_pegawai}", [
            'nip' => $pegawai->nip,
            'nama' => $pegawai->nama,
            'jabatan' => $pegawai->jabatan,
            'unit_kerja' => $pegawai->unit_kerja,
            'alamat_rumah' => $pegawai->alamat_rumah,
            'jarak_kantor_km' => $pegawai->jarak_kantor_km,
            'kendaraan_id' => null,
            'status_pegawai' => 'Tidak Aktif',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('pegawais', [
            'id_pegawai' => $pegawai->id_pegawai,
            'status_pegawai' => 'Tidak Aktif',
        ]);
    }
}

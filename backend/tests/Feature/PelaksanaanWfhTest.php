<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\PelaksanaanWfh;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelaksanaanWfhTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_only_view_their_own_wfh_schedules(): void
    {
        $role = Role::create([
            'nama_role' => 'Pegawai',
        ]);

        // Membuat dua pegawai
        $pegawai1 = Pegawai::create([
            'nama' => 'Pegawai Pertama',
            'status_pegawai' => 'Aktif',
        ]);

        $pegawai2 = Pegawai::create([
            'nama' => 'Pegawai Kedua',
            'status_pegawai' => 'Aktif',
        ]);

        // Membuat akun masing-masing pegawai
        $user1 = User::create([
            'pegawai_id' => $pegawai1->id_pegawai,
            'role_id' => $role->id_role,
            'username' => 'pegawai_pertama',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        User::create([
            'pegawai_id' => $pegawai2->id_pegawai,
            'role_id' => $role->id_role,
            'username' => 'pegawai_kedua',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        // Membuat jadwal WFH untuk kedua pegawai
        $jadwal1 = PelaksanaanWfh::create([
            'pegawai_id' => $pegawai1->id_pegawai,
            'tanggal_wfh' => '2026-11-17',
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        PelaksanaanWfh::create([
            'pegawai_id' => $pegawai2->id_pegawai,
            'tanggal_wfh' => '2026-11-18',
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        // Pegawai pertama mengambil daftar jadwal WFH
        $response = $this->actingAs($user1)
            ->getJson('/api/pelaksanaan-wfh');

        $response->assertOk();

        // Memastikan jadwal milik pegawai pertama ditampilkan
        $response->assertJsonFragment([
            'id_pelaksanaan' => $jadwal1->id_pelaksanaan,
        ]);

        // Memastikan jadwal pegawai kedua tidak ditampilkan
        $response->assertJsonMissing([
            'pegawai_id' => $pegawai2->id_pegawai,
        ]);
    }

    public function test_employee_can_confirm_wfh_as_completed(): void
    {
        $role = Role::create([
            'nama_role' => 'Pegawai',
        ]);

        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Testing',
            'status_pegawai' => 'Aktif',
        ]);

        $user = User::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'role_id' => $role->id_role,
            'username' => 'pegawai_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $tanggalWfh = now()->subDay()->toDateString();

        $pelaksanaan = PelaksanaanWfh::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_wfh' => $tanggalWfh,
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/api/pelaksanaan-wfh/' . $pelaksanaan->id_pelaksanaan . '/konfirmasi', [
                'status_pelaksanaan' => 'Terlaksana',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('pelaksanaan_wfhs', [
            'id_pelaksanaan' => $pelaksanaan->id_pelaksanaan,
            'status_pelaksanaan' => 'Terlaksana',
            'dikonfirmasi_oleh' => $user->id_user,
        ]);

        $this->assertDatabaseMissing('pelaksanaan_wfhs', [
            'id_pelaksanaan' => $pelaksanaan->id_pelaksanaan,
            'tanggal_konfirmasi' => null,
        ]);
    }

    public function test_employee_can_confirm_wfh_as_not_completed(): void
    {
        $role = Role::create([
            'nama_role' => 'Pegawai',
        ]);

        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Testing',
            'status_pegawai' => 'Aktif',
        ]);

        $user = User::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'role_id' => $role->id_role,
            'username' => 'pegawai_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $tanggalWfh = now()->subDay()->toDateString();

        $pelaksanaan = PelaksanaanWfh::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_wfh' => $tanggalWfh,
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/api/pelaksanaan-wfh/' . $pelaksanaan->id_pelaksanaan . '/konfirmasi', [
                'status_pelaksanaan' => 'Tidak Terlaksana',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('pelaksanaan_wfhs', [
            'id_pelaksanaan' => $pelaksanaan->id_pelaksanaan,
            'status_pelaksanaan' => 'Tidak Terlaksana',
            'dikonfirmasi_oleh' => $user->id_user,
        ]);

        $this->assertDatabaseMissing('pelaksanaan_wfhs', [
            'id_pelaksanaan' => $pelaksanaan->id_pelaksanaan,
            'tanggal_konfirmasi' => null,
        ]);
    }

    public function test_employee_cannot_confirm_wfh_twice(): void
    {
        $role = Role::create([
            'nama_role' => 'Pegawai',
        ]);

        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Testing',
            'status_pegawai' => 'Aktif',
        ]);

        $user = User::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'role_id' => $role->id_role,
            'username' => 'pegawai_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $tanggalWfh = now()->subDay()->toDateString();

        $pelaksanaan = PelaksanaanWfh::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_wfh' => $tanggalWfh,
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        // Konfirmasi pertama berhasil
        $this->actingAs($user)
            ->patchJson('/api/pelaksanaan-wfh/' . $pelaksanaan->id_pelaksanaan . '/konfirmasi', [
                'status_pelaksanaan' => 'Terlaksana',
            ])
            ->assertOk();

        // Konfirmasi kedua harus ditolak
        $response = $this->patchJson(
            '/api/pelaksanaan-wfh/' . $pelaksanaan->id_pelaksanaan . '/konfirmasi',
            [
                'status_pelaksanaan' => 'Tidak Terlaksana',
            ]
        );

        $response->assertUnprocessable();

        // Status harus tetap Terlaksana
        $this->assertDatabaseHas('pelaksanaan_wfhs', [
            'id_pelaksanaan' => $pelaksanaan->id_pelaksanaan,
            'status_pelaksanaan' => 'Terlaksana',
            'dikonfirmasi_oleh' => $user->id_user,
        ]);
    }

    public function test_employee_cannot_confirm_another_employees_wfh_schedule(): void
    {
        $role = Role::create([
            'nama_role' => 'Pegawai',
        ]);

        $pegawai1 = Pegawai::create([
            'nama' => 'Pegawai Pertama',
            'status_pegawai' => 'Aktif',
        ]);

        $pegawai2 = Pegawai::create([
            'nama' => 'Pegawai Kedua',
            'status_pegawai' => 'Aktif',
        ]);

        $user1 = User::create([
            'pegawai_id' => $pegawai1->id_pegawai,
            'role_id' => $role->id_role,
            'username' => 'pegawai_pertama',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $pelaksanaan = PelaksanaanWfh::create([
            'pegawai_id' => $pegawai2->id_pegawai,
            'tanggal_wfh' => now()->subDay()->toDateString(),
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        $response = $this->actingAs($user1)
            ->patchJson('/api/pelaksanaan-wfh/' . $pelaksanaan->id_pelaksanaan . '/konfirmasi', [
                'status_pelaksanaan' => 'Terlaksana',
            ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('pelaksanaan_wfhs', [
            'id_pelaksanaan' => $pelaksanaan->id_pelaksanaan,
            'status_pelaksanaan' => 'Terjadwal',
        ]);
    }

    public function test_employee_cannot_confirm_wfh_before_its_date_ends(): void
    {
        $role = Role::create([
            'nama_role' => 'Pegawai',
        ]);

        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Testing',
            'status_pegawai' => 'Aktif',
        ]);

        $user = User::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'role_id' => $role->id_role,
            'username' => 'pegawai_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $pelaksanaan = PelaksanaanWfh::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_wfh' => now()->toDateString(),
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        $response = $this->actingAs($user)
            ->patchJson('/api/pelaksanaan-wfh/' . $pelaksanaan->id_pelaksanaan . '/konfirmasi', [
                'status_pelaksanaan' => 'Terlaksana',
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('pelaksanaan_wfhs', [
            'id_pelaksanaan' => $pelaksanaan->id_pelaksanaan,
            'status_pelaksanaan' => 'Terjadwal',
        ]);
    }

    public function test_employee_cannot_confirm_wfh_after_confirmation_deadline(): void
    {
        $role = Role::create([
            'nama_role' => 'Pegawai',
        ]);

        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Testing',
            'status_pegawai' => 'Aktif',
        ]);

        $user = User::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'role_id' => $role->id_role,
            'username' => 'pegawai_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $tanggalWfh = now()->subDays(2)->toDateString();

        $pelaksanaan = PelaksanaanWfh::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_wfh' => $tanggalWfh,
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        $this->travelTo(
            \Carbon\Carbon::parse($tanggalWfh)->addDays(2)->startOfDay()
        );

        $response = $this->actingAs($user)
            ->patchJson('/api/pelaksanaan-wfh/' . $pelaksanaan->id_pelaksanaan . '/konfirmasi', [
                'status_pelaksanaan' => 'Terlaksana',
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('pelaksanaan_wfhs', [
            'id_pelaksanaan' => $pelaksanaan->id_pelaksanaan,
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        $this->travelBack();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanWfhTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_employee_can_submit_wfh_application(): void
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

        $tanggalMulai = now()->addDays(7);
        $tanggalSelesai = $tanggalMulai->copy()->addDay();

        // Menghindari tanggal Jumat
        while ($tanggalMulai->isFriday()) {
            $tanggalMulai->addDay();
            $tanggalSelesai = $tanggalMulai->copy()->addDay();
        }

        while ($tanggalSelesai->isFriday()) {
            $tanggalSelesai->addDay();
        }

        $response = $this->actingAs($user)
            ->postJson('/api/pengajuan-wfh', [
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_selesai' => $tanggalSelesai->toDateString(),
                'keterangan' => 'Pengujian otomatis pengajuan WFH.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Pengajuan WFH berhasil dikirim.'
            )
            ->assertJsonPath('data.status', 'Menunggu');

        $this->assertDatabaseHas('pengajuan_wfhs', [
            'pegawai_id' => $pegawai->id_pegawai,
            'status' => 'Menunggu',
        ]);
    }


    public function test_employee_cannot_submit_wfh_with_past_date(): void
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

        $response = $this->actingAs($user)
            ->postJson('/api/pengajuan-wfh', [
                'tanggal_mulai' => now()->subDay()->toDateString(),
                'tanggal_selesai' => now()->addDay()->toDateString(),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal_mulai');
    }

    public function test_employee_cannot_submit_wfh_with_invalid_date_range(): void
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

        $tanggalMulai = now()->addDays(10);
        $tanggalSelesai = $tanggalMulai->copy()->subDay();

        $response = $this->actingAs($user)
            ->postJson('/api/pengajuan-wfh', [
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_selesai' => $tanggalSelesai->toDateString(),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal_selesai');
    }

    public function test_employee_cannot_submit_wfh_for_friday_only(): void
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

        // Menentukan hari Jumat mendatang
        $tanggalJumat = now()->next(\Carbon\Carbon::FRIDAY);

        $response = $this->actingAs($user)
            ->postJson('/api/pengajuan-wfh', [
                'tanggal_mulai' => $tanggalJumat->toDateString(),
                'tanggal_selesai' => $tanggalJumat->toDateString(),
            ]);

        $response
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Pengajuan WFH tidak diperlukan karena seluruh tanggal yang dipilih merupakan jadwal WFH otomatis hari Jumat.',
            ]);

        $this->assertDatabaseCount('pengajuan_wfhs', 0);
    }

    public function test_employee_can_submit_wfh_with_friday_in_date_range(): void
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

        // Menentukan rentang Kamis sampai Sabtu
        $tanggalKamis = now()->next(\Carbon\Carbon::THURSDAY);
        $tanggalSabtu = $tanggalKamis->copy()->addDays(2);

        $response = $this->actingAs($user)
            ->postJson('/api/pengajuan-wfh', [
                'tanggal_mulai' => $tanggalKamis->toDateString(),
                'tanggal_selesai' => $tanggalSabtu->toDateString(),
            ]);

        $response
            ->assertCreated()
            ->assertJson([
                'message' => 'Pengajuan WFH berhasil dikirim.',
            ]);

        $this->assertDatabaseHas('pengajuan_wfhs', [
            'pegawai_id' => $pegawai->id_pegawai,
            'status' => 'Menunggu',
        ]);
    }

    public function test_admin_can_approve_wfh_application_and_generate_schedules(): void
    {
        // Membuat role
        $rolePegawai = Role::create([
            'nama_role' => 'Pegawai',
        ]);

        $roleAdmin = Role::create([
            'nama_role' => 'Admin',
        ]);

        // Membuat data pegawai
        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Testing',
            'status_pegawai' => 'Aktif',
        ]);

        // Membuat akun pegawai
        User::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'role_id' => $rolePegawai->id_role,
            'username' => 'pegawai_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        // Membuat akun Admin
        $admin = User::create([
            'role_id' => $roleAdmin->id_role,
            'username' => 'admin_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        // Menentukan rentang Kamis sampai Sabtu
        $tanggalKamis = now()->next(\Carbon\Carbon::THURSDAY);
        $tanggalJumat = $tanggalKamis->copy()->addDay();
        $tanggalSabtu = $tanggalKamis->copy()->addDays(2);

        // Membuat pengajuan WFH
        $pengajuan = \App\Models\PengajuanWfh::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_mulai' => $tanggalKamis->toDateString(),
            'tanggal_selesai' => $tanggalSabtu->toDateString(),
            'status' => 'Menunggu',
        ]);

        // Admin menyetujui pengajuan
        $response = $this->actingAs($admin)
            ->patchJson('/api/admin/pengajuan-wfh/' . $pengajuan->id_pengajuan . '/verifikasi', [
                'status_verifikasi' => 'Disetujui',
                'catatan' => 'Pengajuan WFH disetujui.',
            ]);

        $response->assertOk();

        // Memastikan status pengajuan berubah
        $this->assertDatabaseHas('pengajuan_wfhs', [
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'status' => 'Disetujui',
        ]);

        // Memastikan jadwal Kamis dibuat
        $this->assertDatabaseHas('pelaksanaan_wfhs', [
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_wfh' => $tanggalKamis->format('Y-m-d 00:00:00'),
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        // Memastikan jadwal Sabtu dibuat
        $this->assertDatabaseHas('pelaksanaan_wfhs', [
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_wfh' => $tanggalSabtu->format('Y-m-d 00:00:00'),
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terjadwal',
        ]);

        // Memastikan jadwal Jumat tidak dibuat dari pengajuan
        $this->assertDatabaseMissing('pelaksanaan_wfhs', [
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_wfh' => $tanggalJumat->format('Y-m-d 00:00:00'),
            'sumber_jadwal' => 'Pengajuan',
        ]);

        // Memastikan hanya dua jadwal yang dibuat
        $this->assertDatabaseCount('pelaksanaan_wfhs', 2);
    }

    public function test_admin_can_reject_wfh_application_without_generating_schedules(): void
    {
        $rolePegawai = Role::create([
            'nama_role' => 'Pegawai',
        ]);

        $roleAdmin = Role::create([
            'nama_role' => 'Admin',
        ]);

        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Testing',
            'status_pegawai' => 'Aktif',
        ]);

        User::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'role_id' => $rolePegawai->id_role,
            'username' => 'pegawai_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $admin = User::create([
            'role_id' => $roleAdmin->id_role,
            'username' => 'admin_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $tanggalKamis = now()->next(\Carbon\Carbon::THURSDAY);
        $tanggalSabtu = $tanggalKamis->copy()->addDays(2);

        $pengajuan = \App\Models\PengajuanWfh::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_mulai' => $tanggalKamis->toDateString(),
            'tanggal_selesai' => $tanggalSabtu->toDateString(),
            'status' => 'Menunggu',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson('/api/admin/pengajuan-wfh/' . $pengajuan->id_pengajuan . '/verifikasi', [
                'status_verifikasi' => 'Ditolak',
                'catatan' => 'Pengajuan WFH ditolak.',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('pengajuan_wfhs', [
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'status' => 'Ditolak',
        ]);

        $this->assertDatabaseCount('pelaksanaan_wfhs', 0);

        $this->assertDatabaseHas('verifikasi_wfhs', [
            'pengajuan_wfh_id' => $pengajuan->id_pengajuan,
            'user_id' => $admin->id_user,
            'status_verifikasi' => 'Ditolak',
        ]);
    }

    public function test_admin_cannot_verify_wfh_application_twice(): void
    {
        $rolePegawai = Role::create([
            'nama_role' => 'Pegawai',
        ]);

        $roleAdmin = Role::create([
            'nama_role' => 'Admin',
        ]);

        $pegawai = Pegawai::create([
            'nama' => 'Pegawai Testing',
            'status_pegawai' => 'Aktif',
        ]);

        User::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'role_id' => $rolePegawai->id_role,
            'username' => 'pegawai_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $admin = User::create([
            'role_id' => $roleAdmin->id_role,
            'username' => 'admin_testing',
            'password' => 'password123',
            'status_akun' => 'Aktif',
        ]);

        $tanggalKamis = now()->next(\Carbon\Carbon::THURSDAY);

        $pengajuan = \App\Models\PengajuanWfh::create([
            'pegawai_id' => $pegawai->id_pegawai,
            'tanggal_mulai' => $tanggalKamis->toDateString(),
            'tanggal_selesai' => $tanggalKamis->toDateString(),
            'status' => 'Menunggu',
        ]);

        // Verifikasi pertama: disetujui
        $this->actingAs($admin)
            ->patchJson('/api/admin/pengajuan-wfh/' . $pengajuan->id_pengajuan . '/verifikasi', [
                'status_verifikasi' => 'Disetujui',
            ])
            ->assertOk();

        // Verifikasi kedua: mencoba mengubah menjadi ditolak
        $response = $this->patchJson(
            '/api/admin/pengajuan-wfh/' . $pengajuan->id_pengajuan . '/verifikasi',
            [
                'status_verifikasi' => 'Ditolak',
            ]
        );

        $response->assertUnprocessable();

        // Status harus tetap disetujui
        $this->assertDatabaseHas('pengajuan_wfhs', [
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'status' => 'Disetujui',
        ]);

        // Riwayat verifikasi hanya boleh satu
        $this->assertDatabaseCount('verifikasi_wfhs', 1);

        // Jadwal WFH hanya dibuat satu kali
        $this->assertDatabaseCount('pelaksanaan_wfhs', 1);
    }
}

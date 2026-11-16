<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitoringWfhTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_public_wfh_monitoring_summary(): void
    {
        $tanggalMulai = now()->startOfMonth()->toDateString();
        $tanggalSelesai = now()->endOfMonth()->toDateString();

        $response = $this->getJson('/api/monitoring?' . http_build_query([
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'periode' => [
                    'tanggal_mulai',
                    'tanggal_selesai',
                ],
                'data' => [
                    'total_hari_wfh',
                    'total_penghematan_bbm_liter',
                    'total_pengurangan_emisi_kg',
                    'jumlah_data_tidak_lengkap',
                ],
            ]);
    }

    public function test_employee_can_only_view_their_own_wfh_monitoring_summary(): void
    {
        $now = now();

        // Membuat role Pegawai.
        $roleId = DB::table('roles')->insertGetId([
            'nama_role' => 'Pegawai',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat kendaraan.
        $kendaraanId = DB::table('kendaraans')->insertGetId([
            'nama_kendaraan' => 'Honda Vario 125',
            'jenis_kendaraan' => 'Motor',
            'jenis_bbm' => 'Pertalite',
            'efisiensi_km_per_liter' => 48,
            'sumber_efisiensi' => 'Data pengujian',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat dua data pegawai.
        $pegawaiPertamaId = DB::table('pegawais')->insertGetId([
            'kendaraan_id' => $kendaraanId,
            'nama' => 'Pegawai Pertama',
            'jarak_kantor_km' => 6.5,
            'status_pegawai' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pegawaiKeduaId = DB::table('pegawais')->insertGetId([
            'kendaraan_id' => $kendaraanId,
            'nama' => 'Pegawai Kedua',
            'jarak_kantor_km' => 10,
            'status_pegawai' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat akun untuk pegawai pertama.
        $userId = DB::table('users')->insertGetId([
            'pegawai_id' => $pegawaiPertamaId,
            'role_id' => $roleId,
            'username' => 'pegawai1',
            'password' => bcrypt('password123'),
            'status_akun' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat data pelaksanaan WFH untuk kedua pegawai.
        $tanggalWfh = $now->startOfMonth()->addDays(1)->toDateString();

        DB::table('pelaksanaan_wfhs')->insert([
            [
                'pegawai_id' => $pegawaiPertamaId,
                'tanggal_wfh' => $tanggalWfh,
                'sumber_jadwal' => 'Pengajuan',
                'status_pelaksanaan' => 'Terlaksana',
                'jarak_pp_km' => 13,
                'efisiensi_km_per_liter' => 48,
                'estimasi_bbm_liter' => 0.271,
                'faktor_emisi_kg_per_liter' => 2.31,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'pegawai_id' => $pegawaiKeduaId,
                'tanggal_wfh' => $tanggalWfh,
                'sumber_jadwal' => 'Pengajuan',
                'status_pelaksanaan' => 'Terlaksana',
                'jarak_pp_km' => 20,
                'efisiensi_km_per_liter' => 48,
                'estimasi_bbm_liter' => 0.417,
                'faktor_emisi_kg_per_liter' => 2.31,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Login sebagai pegawai pertama.
        $user = \App\Models\User::findOrFail($userId);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/pegawai/monitoring?' . http_build_query([
            'tanggal_mulai' => $now->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->endOfMonth()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('data.total_hari_wfh', 1)
            ->assertJsonPath('data.jumlah_data_tidak_lengkap', 0)
            ->assertJsonPath(
                'data.total_penghematan_bbm_liter',
                0.271
            )
            ->assertJsonPath(
                'data.total_pengurangan_emisi_kg',
                0.626
            );
    }

    public function test_admin_can_view_all_employees_wfh_monitoring_summary(): void
    {
        $now = now();

        // Membuat role Admin.
        $roleId = DB::table('roles')->insertGetId([
            'nama_role' => 'Admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat kendaraan.
        $kendaraanId = DB::table('kendaraans')->insertGetId([
            'nama_kendaraan' => 'Honda Vario 125',
            'jenis_kendaraan' => 'Motor',
            'jenis_bbm' => 'Pertalite',
            'efisiensi_km_per_liter' => 48,
            'sumber_efisiensi' => 'Data pengujian',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat dua pegawai.
        $pegawaiPertamaId = DB::table('pegawais')->insertGetId([
            'kendaraan_id' => $kendaraanId,
            'nama' => 'Pegawai Pertama',
            'jarak_kantor_km' => 6.5,
            'status_pegawai' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pegawaiKeduaId = DB::table('pegawais')->insertGetId([
            'kendaraan_id' => $kendaraanId,
            'nama' => 'Pegawai Kedua',
            'jarak_kantor_km' => 10,
            'status_pegawai' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat akun Admin.
        $userId = DB::table('users')->insertGetId([
            'pegawai_id' => null,
            'role_id' => $roleId,
            'username' => 'admin1',
            'password' => bcrypt('password123'),
            'status_akun' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat pelaksanaan WFH kedua pegawai.
        $tanggalWfh = $now->startOfMonth()->addDays(1)->toDateString();

        DB::table('pelaksanaan_wfhs')->insert([
            [
                'pegawai_id' => $pegawaiPertamaId,
                'tanggal_wfh' => $tanggalWfh,
                'sumber_jadwal' => 'Pengajuan',
                'status_pelaksanaan' => 'Terlaksana',
                'jarak_pp_km' => 13,
                'efisiensi_km_per_liter' => 48,
                'estimasi_bbm_liter' => 0.271,
                'faktor_emisi_kg_per_liter' => 2.31,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'pegawai_id' => $pegawaiKeduaId,
                'tanggal_wfh' => $tanggalWfh,
                'sumber_jadwal' => 'Pengajuan',
                'status_pelaksanaan' => 'Terlaksana',
                'jarak_pp_km' => 20,
                'efisiensi_km_per_liter' => 48,
                'estimasi_bbm_liter' => 0.417,
                'faktor_emisi_kg_per_liter' => 2.31,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Login sebagai Admin.
        $user = \App\Models\User::findOrFail($userId);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/admin/monitoring?' . http_build_query([
            'tanggal_mulai' => $now->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->endOfMonth()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('data.total_hari_wfh', 2)
            ->assertJsonPath('data.jumlah_data_tidak_lengkap', 0)
            ->assertJsonPath(
                'data.total_penghematan_bbm_liter',
                0.688
            )
            ->assertJsonPath(
                'data.total_pengurangan_emisi_kg',
                1.589
            );
    }

    public function test_kepala_balai_can_view_all_employees_wfh_monitoring_summary(): void
    {
        $now = now();

        // Membuat role Kepala Balai.
        $roleId = DB::table('roles')->insertGetId([
            'nama_role' => 'Kepala Balai',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat kendaraan.
        $kendaraanId = DB::table('kendaraans')->insertGetId([
            'nama_kendaraan' => 'Honda Vario 125',
            'jenis_kendaraan' => 'Motor',
            'jenis_bbm' => 'Pertalite',
            'efisiensi_km_per_liter' => 48,
            'sumber_efisiensi' => 'Data pengujian',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat dua pegawai.
        $pegawaiPertamaId = DB::table('pegawais')->insertGetId([
            'kendaraan_id' => $kendaraanId,
            'nama' => 'Pegawai Pertama',
            'jarak_kantor_km' => 6.5,
            'status_pegawai' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pegawaiKeduaId = DB::table('pegawais')->insertGetId([
            'kendaraan_id' => $kendaraanId,
            'nama' => 'Pegawai Kedua',
            'jarak_kantor_km' => 10,
            'status_pegawai' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat akun Kepala Balai.
        $userId = DB::table('users')->insertGetId([
            'pegawai_id' => null,
            'role_id' => $roleId,
            'username' => 'kepalabalai1',
            'password' => bcrypt('password123'),
            'status_akun' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat pelaksanaan WFH kedua pegawai.
        $tanggalWfh = $now->startOfMonth()->addDays(1)->toDateString();

        DB::table('pelaksanaan_wfhs')->insert([
            [
                'pegawai_id' => $pegawaiPertamaId,
                'tanggal_wfh' => $tanggalWfh,
                'sumber_jadwal' => 'Pengajuan',
                'status_pelaksanaan' => 'Terlaksana',
                'jarak_pp_km' => 13,
                'efisiensi_km_per_liter' => 48,
                'estimasi_bbm_liter' => 0.271,
                'faktor_emisi_kg_per_liter' => 2.31,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'pegawai_id' => $pegawaiKeduaId,
                'tanggal_wfh' => $tanggalWfh,
                'sumber_jadwal' => 'Pengajuan',
                'status_pelaksanaan' => 'Terlaksana',
                'jarak_pp_km' => 20,
                'efisiensi_km_per_liter' => 48,
                'estimasi_bbm_liter' => 0.417,
                'faktor_emisi_kg_per_liter' => 2.31,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Login sebagai Kepala Balai.
        $user = \App\Models\User::findOrFail($userId);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/kepala-balai/monitoring?' . http_build_query([
            'tanggal_mulai' => $now->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->endOfMonth()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('data.total_hari_wfh', 2)
            ->assertJsonPath('data.jumlah_data_tidak_lengkap', 0)
            ->assertJsonPath(
                'data.total_penghematan_bbm_liter',
                0.688
            )
            ->assertJsonPath(
                'data.total_pengurangan_emisi_kg',
                1.589
            );
    }

    public function test_employee_cannot_access_admin_monitoring_endpoint(): void
    {
        $now = now();

        // Membuat role Pegawai.
        $roleId = DB::table('roles')->insertGetId([
            'nama_role' => 'Pegawai',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat data pegawai.
        $pegawaiId = DB::table('pegawais')->insertGetId([
            'nama' => 'Pegawai Pengujian',
            'status_pegawai' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat akun Pegawai.
        $userId = DB::table('users')->insertGetId([
            'pegawai_id' => $pegawaiId,
            'role_id' => $roleId,
            'username' => 'pegawai_akses_admin',
            'password' => bcrypt('password123'),
            'status_akun' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Login sebagai Pegawai.
        $user = \App\Models\User::findOrFail($userId);

        $this->actingAs($user, 'sanctum');

        // Mencoba mengakses monitoring Admin.
        $response = $this->getJson('/api/admin/monitoring?' . http_build_query([
            'tanggal_mulai' => $now->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->endOfMonth()->toDateString(),
        ]));

        $response->assertForbidden();
    }

    public function test_user_with_unknown_role_cannot_access_monitoring(): void
    {
        $now = now();

        // Membuat role yang tidak memiliki hak akses monitoring.
        $roleId = DB::table('roles')->insertGetId([
            'nama_role' => 'Supervisor',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat akun dengan role Supervisor.
        $userId = DB::table('users')->insertGetId([
            'pegawai_id' => null,
            'role_id' => $roleId,
            'username' => 'supervisor1',
            'password' => bcrypt('password123'),
            'status_akun' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Login sebagai Supervisor.
        $user = \App\Models\User::findOrFail($userId);

        $this->actingAs($user, 'sanctum');

        // Mencoba mengakses monitoring.
        $response = $this->getJson('/api/monitoring?' . http_build_query([
            'tanggal_mulai' => $now->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->endOfMonth()->toDateString(),
        ]));

        $response->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki hak akses.',
            ]);
    }

    public function test_unsupported_fuel_type_is_counted_as_incomplete_data(): void
    {
        $now = now();

        // Membuat kendaraan dengan jenis BBM yang tidak didukung.
        $kendaraanId = DB::table('kendaraans')->insertGetId([
            'nama_kendaraan' => 'Kendaraan Pengujian',
            'jenis_kendaraan' => 'Motor',
            'jenis_bbm' => 'BBM Tidak Dikenal',
            'efisiensi_km_per_liter' => 40,
            'sumber_efisiensi' => 'Data pengujian',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat pegawai dengan kendaraan tersebut.
        $pegawaiId = DB::table('pegawais')->insertGetId([
            'kendaraan_id' => $kendaraanId,
            'nama' => 'Pegawai Pengujian',
            'jarak_kantor_km' => 10,
            'status_pegawai' => 'Aktif',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Membuat pelaksanaan WFH yang sudah terlaksana.
        DB::table('pelaksanaan_wfhs')->insert([
            'pegawai_id' => $pegawaiId,
            'tanggal_wfh' => $now->startOfMonth()->addDays(1)->toDateString(),
            'sumber_jadwal' => 'Pengajuan',
            'status_pelaksanaan' => 'Terlaksana',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Mengakses monitoring publik.
        $response = $this->getJson('/api/monitoring?' . http_build_query([
            'tanggal_mulai' => $now->startOfMonth()->toDateString(),
            'tanggal_selesai' => $now->endOfMonth()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('data.total_hari_wfh', 1)
            ->assertJsonPath('data.jumlah_data_tidak_lengkap', 1)
            ->assertJsonPath('data.total_penghematan_bbm_liter', 0)
            ->assertJsonPath('data.total_pengurangan_emisi_kg', 0);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pengaturan urutan kolom hanya untuk MySQL/MariaDB.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE pelaksanaan_wfhs
            MODIFY dikonfirmasi_oleh BIGINT UNSIGNED NULL
            AFTER pegawai_id
        ");

        DB::statement("
            ALTER TABLE pelaksanaan_wfhs
            MODIFY tanggal_konfirmasi TIMESTAMP NULL
            AFTER status_pelaksanaan
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pengaturan urutan kolom hanya untuk MySQL/MariaDB.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE pelaksanaan_wfhs
            MODIFY tanggal_konfirmasi TIMESTAMP NULL
            AFTER updated_at
        ");

        DB::statement("
            ALTER TABLE pelaksanaan_wfhs
            MODIFY dikonfirmasi_oleh BIGINT UNSIGNED NULL
            AFTER tanggal_konfirmasi
        ");
    }
};

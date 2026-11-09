<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelaksanaan_wfhs', function (Blueprint $table) {
            $table->id('id_pelaksanaan');

            $table->unsignedBigInteger('pegawai_id');

            $table->date('tanggal_wfh');
            $table->string('sumber_jadwal', 20);
            $table->string('status_pelaksanaan', 25);

            $table->decimal('jarak_pp_km', 8, 2)->nullable();
            $table->decimal('efisiensi_km_per_liter', 6, 2)->nullable();
            $table->decimal('estimasi_bbm_liter', 10, 3)->nullable();

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->foreign('pegawai_id')
                ->references('id_pegawai')
                ->on('pegawais')
                ->restrictOnDelete();

            $table->unique(
                ['pegawai_id', 'tanggal_wfh'],
                'unique_pegawai_tanggal_wfh'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelaksanaan_wfhs');
    }
};

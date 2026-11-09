<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_wfhs', function (Blueprint $table) {
            $table->id('id_pengajuan');

            $table->unsignedBigInteger('pegawai_id');

            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');

            $table->text('keterangan')->nullable();

            $table->string('status', 20)->default('Menunggu');

            $table->timestamps();

            $table->foreign('pegawai_id')
                ->references('id_pegawai')
                ->on('pegawais')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_wfhs');
    }
};

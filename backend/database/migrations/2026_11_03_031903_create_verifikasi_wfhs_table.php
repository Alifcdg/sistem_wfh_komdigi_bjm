<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_wfhs', function (Blueprint $table) {
            $table->id('id_verifikasi');

            $table->unsignedBigInteger('pengajuan_wfh_id');
            $table->unsignedBigInteger('user_id');

            $table->string('status_verifikasi', 20);
            $table->text('catatan')->nullable();
            $table->dateTime('tanggal_verifikasi');

            $table->timestamps();

            $table->foreign('pengajuan_wfh_id')
                ->references('id_pengajuan')
                ->on('pengajuan_wfhs')
                ->restrictOnDelete();

            $table->foreign('user_id')
                ->references('id_user')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_wfhs');
    }
};

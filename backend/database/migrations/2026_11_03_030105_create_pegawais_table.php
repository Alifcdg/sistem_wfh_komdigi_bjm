<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id('id_pegawai');

            $table->unsignedBigInteger('kendaraan_id')->nullable();

            $table->string('nip', 30)->nullable()->unique();
            $table->string('nama', 150);
            $table->string('jabatan', 100)->nullable();
            $table->string('unit_kerja', 100)->nullable();
            $table->text('alamat_rumah')->nullable();
            $table->decimal('jarak_kantor_km', 8, 2)->nullable();
            $table->string('status_pegawai', 20);

            $table->timestamps();

            $table->foreign('kendaraan_id')
                ->references('id_kendaraan')
                ->on('kendaraans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};

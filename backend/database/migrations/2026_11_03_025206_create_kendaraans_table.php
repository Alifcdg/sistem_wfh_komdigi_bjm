<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kendaraans', function (Blueprint $table) {
            $table->id('id_kendaraan');
            $table->string('nama_kendaraan', 100);
            $table->string('jenis_kendaraan', 30);
            $table->string('jenis_bbm', 30)->nullable();
            $table->decimal('efisiensi_km_per_liter', 6, 2)->nullable();
            $table->string('sumber_efisiensi', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kendaraans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_user');

            $table->unsignedBigInteger('pegawai_id')->nullable()->unique();
            $table->unsignedBigInteger('role_id');

            $table->string('username', 100)->unique();
            $table->string('password', 255);
            $table->string('status_akun', 20);

            $table->timestamps();

            $table->foreign('pegawai_id')
                ->references('id_pegawai')
                ->on('pegawais')
                ->nullOnDelete();

            $table->foreign('role_id')
                ->references('id_role')
                ->on('roles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

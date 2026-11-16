<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pelaksanaan_wfhs', function (Blueprint $table) {
            $table->timestamp('tanggal_konfirmasi')->nullable();

            $table->foreignId('dikonfirmasi_oleh')
                ->nullable()
                ->constrained('users', 'id_user')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelaksanaan_wfhs', function (Blueprint $table) {
            $table->dropForeign(['dikonfirmasi_oleh']);
            $table->dropColumn([
                'tanggal_konfirmasi',
                'dikonfirmasi_oleh',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelaksanaan_wfhs', function (Blueprint $table) {
            $table->decimal('faktor_emisi_kg_per_liter', 6, 3)
                ->nullable()
                ->after('estimasi_bbm_liter');
        });
    }

    public function down(): void
    {
        Schema::table('pelaksanaan_wfhs', function (Blueprint $table) {
            $table->dropColumn('faktor_emisi_kg_per_liter');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regras_elegibilidade') && !Schema::hasColumn('regras_elegibilidade', 'exige_pcd')) {
            Schema::table('regras_elegibilidade', function (Blueprint $table) {
                $table->boolean('exige_pcd')->nullable()->default(false);
            });
        }
    }

    public function down(): void
    {
        // Critérios já cadastrados precisam permanecer registrados em rollback.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('perfis_estudantes') && !Schema::hasColumn('perfis_estudantes', 'raca_cor')) {
            Schema::table('perfis_estudantes', function (Blueprint $table) {
                // TEXT comporta o valor cifrado pelo cast encrypted do Laravel.
                $table->text('raca_cor')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Não apagar autodeclarações criptografadas em rollback.
    }
};

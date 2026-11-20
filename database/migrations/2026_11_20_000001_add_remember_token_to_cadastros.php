<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cadastros') && !Schema::hasColumn('cadastros', 'remember_token')) {
            Schema::table('cadastros', function (Blueprint $table) {
                $table->string('remember_token', 100)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Coluna mantida no rollback para não invalidar tokens nem alterar a tabela legada.
    }
};

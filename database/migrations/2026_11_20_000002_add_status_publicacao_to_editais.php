<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('editais') && !Schema::hasColumn('editais', 'status_publicacao')) {
            Schema::table('editais', function (Blueprint $table) {
                // Registros já existentes permanecem visíveis; novos editais começam como rascunho.
                $table->enum('status_publicacao', ['RASCUNHO', 'PUBLICADO', 'ARQUIVADO'])
                    ->default('PUBLICADO');
            });
        }
    }

    public function down(): void
    {
        // Não remover o estado de publicação no rollback: ele pode conter decisões administrativas.
    }
};

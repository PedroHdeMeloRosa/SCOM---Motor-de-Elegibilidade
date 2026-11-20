<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Migration histórica substituída pela tabela perfis_estudantes.
     * Dados da ficha não devem ser adicionados a cadastros.
     */
    public function up(): void
    {
        // A ficha estudantil possui tabela própria no esquema oficial.
    }

    public function down(): void
    {
        // Não remover dados de perfil por rollback desta migration antiga.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Os campos tipo_perfil e status_conta pertencem à tabela cadastros e são
     * criados pela migration baseline. Esta migration antiga fica sem efeito
     * para preservar a sequência histórica do repositório.
     */
    public function up(): void
    {
        // RBAC já faz parte da estrutura oficial de cadastros.
    }

    public function down(): void
    {
        // Não remover colunas de autorização de uma tabela de negócio.
    }
};

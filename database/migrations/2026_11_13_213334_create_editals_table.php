<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Migration histórica substituída pela baseline com a estrutura oficial
     * da tabela editais (nome_processo, custo_medio_republica etc.).
     */
    public function up(): void
    {
        // A tabela editais é criada pela migration baseline quando necessário.
    }

    public function down(): void
    {
        // Nunca apagar a tabela editais por rollback desta migration antiga.
    }
};

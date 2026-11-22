<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('editais') && !Schema::hasColumn('editais', 'arquivo_pdf')) {
            Schema::table('editais', function (Blueprint $table) {
                $table->string('arquivo_pdf', 255)->nullable()->after('link_documento');
            });
        }
    }

    public function down(): void
    {
        // Preserva referências de arquivos em rollback.
    }
};
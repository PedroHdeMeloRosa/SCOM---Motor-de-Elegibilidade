<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('logs_auditoria')) {
            return;
        }

        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('ator_id')->nullable();
            $table->string('acao', 60);
            $table->string('entidade_tipo', 40);
            $table->integer('entidade_id')->nullable();
            $table->text('detalhes')->nullable();
            $table->timestamp('criado_em')->useCurrent();

            $table->index('ator_id');
            $table->index('criado_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_auditoria');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cadastros')) {
            Schema::create('cadastros', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('nome', 100);
                $table->string('email', 150)->unique('email');
                $table->string('senha_hash', 255);
                $table->enum('tipo_perfil', ['ESTUDANTE', 'COORDENADOR', 'DIRETOR'])->default('ESTUDANTE');
                $table->enum('status_conta', ['ATIVA', 'BANIDA'])->nullable()->default('ATIVA');
                $table->timestamp('criado_em')->useCurrent();
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('cadastros');
    }
};
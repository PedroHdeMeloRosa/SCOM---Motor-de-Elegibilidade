<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cadastros')) {
            Schema::create('cadastros', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->string('nome', 100);
                $table->string('email', 150)->unique('email');
                $table->string('senha_hash', 255);
                $table->string('remember_token', 100)->nullable();
                $table->enum('tipo_perfil', ['ESTUDANTE', 'COORDENADOR', 'DIRETOR'])->default('ESTUDANTE');
                $table->enum('status_conta', ['ATIVA', 'BANIDA'])->nullable()->default('ATIVA');
                $table->timestamp('criado_em')->useCurrent();
            });
        }

        if (!Schema::hasTable('editais')) {
            Schema::create('editais', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->string('instituicao', 100);
                $table->string('nome_processo', 150);
                $table->string('cidade', 100);
                $table->char('estado', 2);
                $table->enum('regiao', ['SUDESTE', 'SUL', 'CENTRO_OESTE', 'NORDESTE', 'NORTE', 'INTERNACIONAL'])
                    ->nullable()->default('SUDESTE');
                $table->decimal('latitude', 10, 6);
                $table->decimal('longitude', 10, 6);
                $table->decimal('custo_medio_republica', 10, 2)->nullable()->default(600.00);
                $table->boolean('tem_moradia_estudantil')->nullable()->default(true);
                $table->year('ano_referencia');
                $table->string('link_documento', 255)->nullable();
                $table->enum('status_publicacao', ['RASCUNHO', 'PUBLICADO', 'ARQUIVADO'])->default('PUBLICADO');
                $table->timestamp('criado_em')->useCurrent();
            });
        }

        if (!Schema::hasTable('perfis_estudantes')) {
            Schema::create('perfis_estudantes', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->integer('id_usuario')->unique('id_usuario');
                $table->string('cep', 15)->nullable()->default('18013-004');
                $table->string('cidade_origem', 100)->nullable()->default('Sorocaba');
                $table->char('estado_origem', 2)->nullable()->default('SP');
                $table->decimal('lat_origem', 10, 6)->nullable()->default(-23.501500);
                $table->decimal('lng_origem', 10, 6)->nullable()->default(-47.452600);
                $table->decimal('renda_per_capita', 10, 2)->default(1500.00);
                $table->enum('tipo_escola', ['PUBLICA', 'PRIVADA_BOLSA', 'PRIVADA'])->default('PUBLICA');
                $table->enum('etnia', ['NAO_DECLARADO', 'PPI', 'BRANCO', 'AMARELO'])->nullable()->default('NAO_DECLARADO');
                $table->tinyInteger('pcd')->nullable()->default(0);
                $table->integer('nota_media_enem')->nullable()->default(650);
                $table->enum('nivel_mobilidade', ['LOCAL', 'ESTADUAL', 'INTERESTADUAL', 'INTERNACIONAL'])
                    ->nullable()->default('ESTADUAL');
                $table->decimal('orcamento_moradia', 10, 2)->nullable()->default(800.00);
                $table->tinyInteger('interesse_republica')->nullable()->default(1);
                $table->tinyInteger('precisa_auxilio_moradia')->nullable()->default(0);
                $table->timestamp('atualizado_em')->useCurrent();
                $table->foreign('id_usuario')->references('id')->on('cadastros')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('regras_elegibilidade')) {
            Schema::create('regras_elegibilidade', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->integer('id_edital');
                $table->enum('tipo_beneficio', [
                    'ISENCAO_TAXA',
                    'COTA_RENDA',
                    'COTA_ESCOLA_PUBLICA',
                    'COTA_RACIAL',
                    'AMPLA_CONCORRENCIA',
                ]);
                $table->decimal('renda_maxima_per_capita', 10, 2)->nullable();
                $table->tinyInteger('exige_escola_publica')->nullable()->default(0);
                $table->tinyInteger('exige_etnia')->nullable()->default(0);
                $table->tinyInteger('exige_pcd')->nullable()->default(0);
                $table->integer('nota_corte_estimada')->nullable()->default(500);
                $table->date('prazo_inicio');
                $table->date('prazo_fim');
                $table->text('documentos_exigidos')->nullable();
                $table->enum('status_regra', ['ATIVA', 'INATIVA'])->nullable()->default('ATIVA');
                $table->integer('versao')->nullable()->default(1);
                $table->foreign('id_edital')->references('id')->on('editais')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('comentarios')) {
            Schema::create('comentarios', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->integer('id_usuario');
                $table->integer('id_edital');
                $table->text('texto_comentario');
                $table->timestamp('data_comentario')->useCurrent();
                $table->enum('status_comentario', ['VISIVEL', 'OCULTO'])->nullable()->default('VISIVEL');
                $table->foreign('id_usuario')->references('id')->on('cadastros')->cascadeOnDelete();
                $table->foreign('id_edital')->references('id')->on('editais')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('historico_retificacoes')) {
            Schema::create('historico_retificacoes', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->integer('id_regra_original');
                $table->integer('id_regra_nova');
                $table->string('motivo_retificacao', 255);
                $table->timestamp('data_retificacao')->useCurrent();
                $table->foreign('id_regra_original')->references('id')->on('regras_elegibilidade');
                $table->foreign('id_regra_nova')->references('id')->on('regras_elegibilidade');
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
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        // Baseline: rollback não remove tabelas de negócio.
    }
};
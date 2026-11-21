<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesScomTestSchema
{
    protected function criarEsquemaScomParaTeste(): void
    {
        // Limpa qualquer resíduo do banco persistente (MariaDB entre testes)
        foreach ([
            'logs_auditoria',
            'comentarios',
            'historico_retificacoes',
            'regras_elegibilidade',
            'editais',
            'perfis_estudantes',
            'cadastros',
        ] as $tabela) {
            Schema::dropIfExists($tabela);
        }

        Schema::create('cadastros', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 100);
            $table->string('email', 150)->unique();
            $table->string('senha_hash', 255);
            $table->string('remember_token', 100)->nullable();
            $table->string('tipo_perfil', 20)->default('ESTUDANTE');
            $table->string('status_conta', 10)->default('ATIVA');
            $table->timestamp('criado_em')->useCurrent();
        });

        Schema::create('perfis_estudantes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_usuario')->unique();
            $table->string('cep', 15)->nullable();
            $table->string('cidade_origem', 100)->nullable();
            $table->char('estado_origem', 2)->nullable();
            $table->decimal('lat_origem', 10, 6)->nullable();
            $table->decimal('lng_origem', 10, 6)->nullable();
            $table->decimal('renda_per_capita', 10, 2)->nullable();
            $table->string('tipo_escola', 30)->nullable();
            $table->string('etnia', 30)->nullable();
            $table->text('raca_cor')->nullable();
            $table->boolean('pcd')->default(false);
            $table->integer('nota_media_enem')->nullable();
            $table->string('nivel_mobilidade', 30)->nullable();
            $table->decimal('orcamento_moradia', 10, 2)->nullable();
            $table->boolean('interesse_republica')->default(false);
            $table->boolean('precisa_auxilio_moradia')->default(false);
            $table->timestamp('atualizado_em')->nullable();
        });

        Schema::create('editais', function (Blueprint $table) {
            $table->increments('id');
            $table->string('instituicao', 100);
            $table->string('nome_processo', 150);
            $table->string('cidade', 100);
            $table->char('estado', 2);
            $table->string('regiao', 30)->nullable();
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->decimal('custo_medio_republica', 10, 2)->nullable();
            $table->boolean('tem_moradia_estudantil')->nullable();
            $table->unsignedSmallInteger('ano_referencia');
            $table->string('link_documento', 255)->nullable();
            $table->string('status_publicacao', 20)->default('PUBLICADO');
            $table->timestamp('criado_em')->nullable();
        });

        Schema::create('regras_elegibilidade', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_edital');
            $table->string('tipo_beneficio', 40);
            $table->decimal('renda_maxima_per_capita', 10, 2)->nullable();
            $table->boolean('exige_escola_publica')->default(false);
            $table->boolean('exige_etnia')->default(false);
            $table->boolean('exige_pcd')->default(false);
            $table->integer('nota_corte_estimada')->nullable();
            $table->date('prazo_inicio');
            $table->date('prazo_fim');
            $table->text('documentos_exigidos')->nullable();
            $table->string('status_regra', 10)->default('ATIVA');
            $table->integer('versao')->default(1);
        });

        Schema::create('historico_retificacoes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_regra_original');
            $table->unsignedInteger('id_regra_nova');
            $table->string('motivo_retificacao', 255);
            $table->timestamp('data_retificacao')->useCurrent();
        });

        Schema::create('comentarios', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_edital');
            $table->text('texto_comentario');
            $table->timestamp('data_comentario')->useCurrent();
            $table->string('status_comentario', 20)->default('VISIVEL');
        });

        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ator_id')->nullable();
            $table->string('acao', 60);
            $table->string('entidade_tipo', 40);
            $table->unsignedInteger('entidade_id')->nullable();
            $table->text('detalhes')->nullable();
            $table->timestamp('criado_em')->useCurrent();
        });
    }
}
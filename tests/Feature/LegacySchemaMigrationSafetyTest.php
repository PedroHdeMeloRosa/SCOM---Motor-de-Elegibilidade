<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacySchemaMigrationSafetyTest extends TestCase
{
    public function test_baseline_creates_official_schema_and_rollback_does_not_drop_business_tables(): void
    {
        $migration = require database_path('migrations/0001_01_01_000000_create_users_table.php');
        $migration->up();

        foreach ([
            'cadastros',
            'perfis_estudantes',
            'editais',
            'regras_elegibilidade',
            'comentarios',
            'historico_retificacoes',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "A tabela {$table} deveria existir na baseline.");
        }

        $migration->down();

        $this->assertTrue(Schema::hasTable('cadastros'));
        $this->assertTrue(Schema::hasTable('editais'));
        $this->assertTrue(Schema::hasTable('perfis_estudantes'));
    }


    public function test_additive_migrations_preserve_existing_accounts_and_edicts(): void
    {
        Schema::create('cadastros', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 100);
            $table->string('email', 150)->unique();
            $table->string('senha_hash', 255);
            $table->string('tipo_perfil', 20);
            $table->string('status_conta', 10);
            $table->timestamp('criado_em')->useCurrent();
        });
        DB::table('cadastros')->insert([
            'id' => 27,
            'nome' => 'Conta preservada',
            'email' => 'preservada@example.test',
            'senha_hash' => '$2y$10$hash-de-teste',
            'tipo_perfil' => 'ESTUDANTE',
            'status_conta' => 'ATIVA',
        ]);

        Schema::create('editais', function (Blueprint $table) {
            $table->increments('id');
            $table->string('instituicao', 100);
            $table->string('nome_processo', 150);
            $table->string('cidade', 100);
            $table->char('estado', 2);
            $table->string('regiao', 30);
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->decimal('custo_medio_republica', 10, 2)->nullable();
            $table->boolean('tem_moradia_estudantil')->nullable();
            $table->unsignedSmallInteger('ano_referencia');
            $table->string('link_documento', 255)->nullable();
            $table->timestamp('criado_em')->nullable();
        });
        DB::table('editais')->insert([
            'id' => 19,
            'instituicao' => 'Edital preservado',
            'nome_processo' => 'Processo original',
            'cidade' => 'Sorocaba',
            'estado' => 'SP',
            'regiao' => 'SUDESTE',
            'latitude' => -23.5,
            'longitude' => -47.4,
            'ano_referencia' => 2027,
        ]);

        $baseline = require database_path('migrations/0001_01_01_000000_create_users_table.php');
        $baseline->up();

        $rememberToken = require database_path('migrations/2026_11_20_000001_add_remember_token_to_cadastros.php');
        $rememberToken->up();
        $statusPublicacao = require database_path('migrations/2026_11_20_000002_add_status_publicacao_to_editais.php');
        $statusPublicacao->up();

        $this->assertSame('preservada@example.test', DB::table('cadastros')->where('id', 27)->value('email'));
        $this->assertSame('$2y$10$hash-de-teste', DB::table('cadastros')->where('id', 27)->value('senha_hash'));
        $this->assertSame('Processo original', DB::table('editais')->where('id', 19)->value('nome_processo'));
        $this->assertSame('PUBLICADO', DB::table('editais')->where('id', 19)->value('status_publicacao'));
        $this->assertTrue(Schema::hasColumn('cadastros', 'remember_token'));

        $statusPublicacao->down();
        $rememberToken->down();
        $baseline->down();

        $this->assertTrue(Schema::hasTable('cadastros'));
        $this->assertTrue(Schema::hasTable('editais'));
        $this->assertSame('preservada@example.test', DB::table('cadastros')->where('id', 27)->value('email'));
        $this->assertSame('Processo original', DB::table('editais')->where('id', 19)->value('nome_processo'));
    }

    public function test_exige_pcd_migration_adds_optional_rule_flag_without_changing_existing_rules(): void
    {
        Schema::create('regras_elegibilidade', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_edital');
            $table->string('tipo_beneficio', 40);
            $table->decimal('renda_maxima_per_capita', 10, 2)->nullable();
            $table->boolean('exige_escola_publica')->default(false);
            $table->boolean('exige_etnia')->default(false);
            $table->integer('nota_corte_estimada')->nullable();
            $table->date('prazo_inicio');
            $table->date('prazo_fim');
            $table->text('documentos_exigidos')->nullable();
            $table->string('status_regra', 10)->default('ATIVA');
            $table->integer('versao')->default(1);
        });

        DB::table('regras_elegibilidade')->insert([
            'id' => 41,
            'id_edital' => 19,
            'tipo_beneficio' => 'COTA_RENDA',
            'renda_maxima_per_capita' => 2118,
            'exige_escola_publica' => true,
            'exige_etnia' => false,
            'prazo_inicio' => '2026-08-01',
            'prazo_fim' => '2027-01-31',
        ]);

        $migration = require database_path('migrations/2026_11_20_000003_add_exige_pcd_to_regras_elegibilidade.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('regras_elegibilidade', 'exige_pcd'));
        $this->assertEquals(0, DB::table('regras_elegibilidade')->where('id', 41)->value('exige_pcd'));

        $migration->down();

        $this->assertTrue(Schema::hasColumn('regras_elegibilidade', 'exige_pcd'));
        $this->assertSame('COTA_RENDA', DB::table('regras_elegibilidade')->where('id', 41)->value('tipo_beneficio'));
    }

    public function test_race_color_migration_preserves_legacy_self_declaration_column_and_records(): void
    {
        Schema::create('cadastros', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 100);
            $table->string('email', 150)->unique();
            $table->string('senha_hash', 255);
            $table->string('tipo_perfil', 20);
            $table->string('status_conta', 10);
            $table->timestamp('criado_em')->useCurrent();
        });
        DB::table('cadastros')->insert([
            'id' => 9,
            'nome' => 'Estudante legado',
            'email' => 'legado@example.test',
            'senha_hash' => 'hash-preservado',
            'tipo_perfil' => 'ESTUDANTE',
            'status_conta' => 'ATIVA',
        ]);

        Schema::create('perfis_estudantes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_usuario')->unique();
            $table->string('etnia', 30)->nullable();
        });
        DB::table('perfis_estudantes')->insert([
            'id' => 2,
            'id_usuario' => 9,
            'etnia' => 'NAO_DECLARADO',
        ]);

        $migration = require database_path('migrations/2026_11_20_000000_add_raca_cor_to_perfis_estudantes.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('perfis_estudantes', 'etnia'));
        $this->assertTrue(Schema::hasColumn('perfis_estudantes', 'raca_cor'));
        $this->assertSame('NAO_DECLARADO', DB::table('perfis_estudantes')->where('id', 2)->value('etnia'));
        $this->assertNull(DB::table('perfis_estudantes')->where('id', 2)->value('raca_cor'));

        $migration->down();

        $this->assertTrue(Schema::hasColumn('perfis_estudantes', 'raca_cor'));
        $this->assertSame('hash-preservado', DB::table('cadastros')->where('id', 9)->value('senha_hash'));
    }
}

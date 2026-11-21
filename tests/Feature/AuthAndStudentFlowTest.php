<?php

namespace Tests\Feature;

use App\Models\Edital;
use App\Models\PerfilEstudante;
use App\Models\RegraElegibilidade;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesScomTestSchema;
use Tests\TestCase;

class AuthAndStudentFlowTest extends TestCase
{
    use CreatesScomTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow('2026-11-20 12:00:00');
        $this->criarEsquemaScomParaTeste();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_active_student_can_login_using_legacy_columns(): void
    {
        $user = $this->criarUsuario('ESTUDANTE', 'ATIVA');

        $response = $this->post(route('login.autenticar'), [
            'email' => $user->email,
            'password' => 'senha-forte-123',
        ]);

        $response->assertRedirect(route('estudante.painel'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_banned_account_cannot_login_even_with_correct_password(): void
    {
        $user = $this->criarUsuario('ESTUDANTE', 'BANIDA');

        $this->post(route('login.autenticar'), [
            'email' => $user->email,
            'password' => 'senha-forte-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }


    public function test_first_director_can_only_be_created_through_console_command(): void
    {
        $this->artisan('scom:criar-diretor')
            ->expectsQuestion('Nome completo do diretor', 'Diretor Inicial')
            ->expectsQuestion('E-mail do diretor', 'diretor-inicial@example.test')
            ->expectsQuestion('Senha (mínimo 12 caracteres)', 'senha-super-segura-2026')
            ->expectsQuestion('Confirme a senha', 'senha-super-segura-2026')
            ->assertExitCode(0);

        $this->assertDatabaseHas('cadastros', [
            'email' => 'diretor-inicial@example.test',
            'tipo_perfil' => 'DIRETOR',
            'status_conta' => 'ATIVA',
        ]);
    }

    public function test_student_api_returns_json_401_when_guest(): void
    {
        $this->get('/api/estudante/painel')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_student_cannot_access_director_dashboard(): void
    {
        $this->actingAs($this->criarUsuario('ESTUDANTE', 'ATIVA'))
            ->get(route('diretoria.dashboard'))
            ->assertForbidden();
    }

    public function test_director_can_access_own_dashboard(): void
    {
        $this->actingAs($this->criarUsuario('DIRETOR', 'ATIVA'))
            ->get(route('diretoria.dashboard'))
            ->assertOk();
    }

    public function test_public_registration_cannot_promote_account_to_director(): void
    {
        $this->post(route('cadastro.registrar'), [
            'nome' => 'Conta de Teste',
            'email' => 'novo@example.test',
            'password' => 'senha-de-teste-123',
            'password_confirmation' => 'senha-de-teste-123',
            'tipo_perfil' => 'DIRETOR',
            'status_conta' => 'ATIVA',
        ])->assertRedirect(route('estudante.painel'));

        $this->assertDatabaseHas('cadastros', [
            'email' => 'novo@example.test',
            'tipo_perfil' => 'ESTUDANTE',
            'status_conta' => 'ATIVA',
        ]);
    }

    public function test_registration_rejects_short_password(): void
    {
        $this->from(route('cadastro'))->post(route('cadastro.registrar'), [
            'nome' => 'Conta de Teste',
            'email' => 'curta@example.test',
            'password' => '123456',
            'password_confirmation' => '123456',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('cadastros', ['email' => 'curta@example.test']);
    }

    public function test_registration_rejects_password_longer_than_bcrypt_limit(): void
    {
        $senhaLonga = str_repeat('a', 73);

        $this->from(route('cadastro'))->post(route('cadastro.registrar'), [
            'nome' => 'Conta de Teste',
            'email' => 'longa@example.test',
            'password' => $senhaLonga,
            'password_confirmation' => $senhaLonga,
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('cadastros', ['email' => 'longa@example.test']);
    }

    public function test_registration_rejects_multibyte_password_over_bcrypt_byte_limit(): void
    {
        $senhaLonga = str_repeat('é', 37); // 37 caracteres, mas 74 bytes em UTF-8.

        $this->from(route('cadastro'))->post(route('cadastro.registrar'), [
            'nome' => 'Conta de Teste',
            'email' => 'multibyte@example.test',
            'password' => $senhaLonga,
            'password_confirmation' => $senhaLonga,
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('cadastros', ['email' => 'multibyte@example.test']);
    }

    public function test_student_can_save_race_color_as_encrypted_profile_data(): void
    {
        $user = $this->criarUsuario('ESTUDANTE', 'ATIVA');

        $this->actingAs($user)->postJson(route('api.estudante.ficha'), $this->dadosFicha())
            ->assertOk()
            ->assertJsonPath('sucesso', true);

        $perfil = PerfilEstudante::where('id_usuario', $user->id)->firstOrFail();
        $this->assertSame('PRETA', $perfil->raca_cor);

        $valorPersistido = \Illuminate\Support\Facades\DB::table('perfis_estudantes')
            ->where('id_usuario', $user->id)
            ->value('raca_cor');
        $this->assertNotSame('PRETA', $valorPersistido);
        $this->assertNotSame('', $valorPersistido);
    }

    public function test_student_panel_returns_only_explicit_profile_fields(): void
    {
        $user = $this->criarUsuario('ESTUDANTE', 'ATIVA');
        $user->perfil()->create([
            'renda_per_capita' => 1500,
            'tipo_escola' => 'PUBLICA',
            'raca_cor' => 'PARDA',
            'nota_media_enem' => 650,
            'cidade_origem' => 'Sorocaba',
            'estado_origem' => 'SP',
            'nivel_mobilidade' => 'ESTADUAL',
            'orcamento_moradia' => 800,
        ]);

        $this->actingAs($user)
            ->getJson(route('api.estudante.painel'))
            ->assertOk()
            ->assertJsonPath('ficha_completa', true)
            ->assertJsonMissingPath('perfil.etnia')
            ->assertJsonPath('perfil.raca_cor', 'PARDA');
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.autenticar'), [
                'email' => 'nao-existe@example.test',
                'password' => 'senha-incorreta',
            ]);
        }

        $this->post(route('login.autenticar'), [
            'email' => 'nao-existe@example.test',
            'password' => 'senha-incorreta',
        ])->assertSessionHasErrors('email');
    }


    public function test_authenticated_user_can_logout_without_missing_remember_token_column(): void
    {
        $user = $this->criarUsuario('ESTUDANTE', 'ATIVA');

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_account_banned_after_login_is_logged_out_on_next_protected_request(): void
    {
        $user = $this->criarUsuario('ESTUDANTE', 'ATIVA');
        $this->actingAs($user);

        DB::table('cadastros')->where('id', $user->id)->update(['status_conta' => 'BANIDA']);
        $this->app['auth']->forgetGuards();

        $this->get(route('estudante.painel'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_coordinator_can_create_an_editais_as_draft_and_publish_it(): void
    {
        $coordinator = $this->criarUsuario('COORDENADOR', 'ATIVA');

        $dados = [
            'instituicao' => 'Universidade de Teste',
            'nome_processo' => 'Processo Seletivo 2027',
            'cidade' => 'Sorocaba',
            'estado' => 'SP',
            'regiao' => 'SUDESTE',
            'latitude' => -23.50,
            'longitude' => -47.45,
            'custo_medio_republica' => 600,
            'tem_moradia_estudantil' => '0',
            'ano_referencia' => 2027,
            'link_documento' => 'https://example.test/edital.pdf',
            'regra' => [
                'tipo_beneficio' => 'COTA_RENDA',
                'renda_maxima_per_capita' => 2118,
                'exige_escola_publica' => '1',
                'exige_etnia' => '0',
                'exige_pcd' => '0',
                'nota_corte_estimada' => 600,
                'prazo_inicio' => '2026-11-01',
                'prazo_fim' => '2027-01-31',
                'documentos_exigidos' => 'Comprovante de renda e histórico escolar.',
            ],
        ];

        $this->actingAs($coordinator)
            ->post(route('coordenacao.editais.store'), $dados)
            ->assertRedirect();

        $edital = Edital::where('nome_processo', 'Processo Seletivo 2027')->firstOrFail();
        $this->assertSame('RASCUNHO', $edital->status_publicacao);
        $this->assertDatabaseHas('regras_elegibilidade', [
            'id_edital' => $edital->id,
            'tipo_beneficio' => 'COTA_RENDA',
            'status_regra' => 'ATIVA',
            'versao' => 1,
        ]);

        $this->post(route('coordenacao.editais.publicar', $edital))->assertRedirect();
        $this->assertDatabaseHas('editais', [
            'id' => $edital->id,
            'status_publicacao' => 'PUBLICADO',
        ]);
    }

    public function test_student_cannot_create_editais(): void
    {
        $student = $this->criarUsuario('ESTUDANTE', 'ATIVA');

        $this->actingAs($student)
            ->post(route('coordenacao.editais.store'), [])
            ->assertForbidden();
    }

    public function test_director_can_promote_student_to_coordinator(): void
    {
        $director = $this->criarUsuario('DIRETOR', 'ATIVA');
        $student = $this->criarUsuario('ESTUDANTE', 'ATIVA');

        $this->actingAs($director)
            ->patch(route('diretoria.usuarios.atualizar', $student), [
                'tipo_perfil' => 'COORDENADOR',
                'status_conta' => 'ATIVA',
            ])->assertRedirect();

        $this->assertDatabaseHas('cadastros', [
            'id' => $student->id,
            'tipo_perfil' => 'COORDENADOR',
            'status_conta' => 'ATIVA',
        ]);
    }

    public function test_director_cannot_change_own_role_or_status(): void
    {
        $director = $this->criarUsuario('DIRETOR', 'ATIVA');

        $this->actingAs($director)
            ->patch(route('diretoria.usuarios.atualizar', $director), [
                'tipo_perfil' => 'ESTUDANTE',
                'status_conta' => 'BANIDA',
            ])->assertSessionHasErrors('usuario');

        $this->assertDatabaseHas('cadastros', [
            'id' => $director->id,
            'tipo_perfil' => 'DIRETOR',
            'status_conta' => 'ATIVA',
        ]);
    }


    public function test_coordinator_retification_creates_new_rule_version_and_history(): void
    {
        $coordinator = $this->criarUsuario('COORDENADOR', 'ATIVA');
        $edital = $this->criarEditalComRegra('PUBLICADO');
        $regraOriginal = $edital->regras()->firstOrFail();

        $this->actingAs($coordinator)
            ->post(route('coordenacao.regras.retificar', $regraOriginal), [
                'motivo_retificacao' => 'Atualização do limite de renda após retificação oficial.',
                'tipo_beneficio' => 'COTA_RENDA',
                'renda_maxima_per_capita' => 2300,
                'exige_escola_publica' => '1',
                'exige_etnia' => '0',
                'exige_pcd' => '0',
                'nota_corte_estimada' => 650,
                'prazo_inicio' => '2026-08-01',
                'prazo_fim' => '2027-01-20',
                'documentos_exigidos' => 'Comprovante atualizado de renda.',
            ])->assertRedirect();

        $this->assertDatabaseHas('regras_elegibilidade', [
            'id' => $regraOriginal->id,
            'status_regra' => 'INATIVA',
            'versao' => 1,
        ]);
        $this->assertDatabaseHas('regras_elegibilidade', [
            'id_edital' => $edital->id,
            'status_regra' => 'ATIVA',
            'versao' => 2,
            'renda_maxima_per_capita' => 2300,
        ]);
        $this->assertDatabaseHas('historico_retificacoes', [
            'id_regra_original' => $regraOriginal->id,
            'motivo_retificacao' => 'Atualização do limite de renda após retificação oficial.',
        ]);
    }

    public function test_income_quota_cannot_be_published_without_a_limit(): void
    {
        $coordinator = $this->criarUsuario('COORDENADOR', 'ATIVA');
        $dados = [
            'instituicao' => 'Universidade de Teste',
            'nome_processo' => 'Cota sem limite',
            'cidade' => 'Sorocaba',
            'estado' => 'SP',
            'regiao' => 'SUDESTE',
            'latitude' => -23.5,
            'longitude' => -47.45,
            'tem_moradia_estudantil' => '0',
            'ano_referencia' => 2027,
            'regra' => [
                'tipo_beneficio' => 'COTA_RENDA',
                'renda_maxima_per_capita' => null,
                'exige_escola_publica' => '0',
                'exige_etnia' => '0',
                'exige_pcd' => '0',
                'prazo_inicio' => '2026-11-01',
                'prazo_fim' => '2027-01-31',
            ],
        ];

        $this->actingAs($coordinator)
            ->from(route('coordenacao.dashboard'))
            ->post(route('coordenacao.editais.store'), $dados)
            ->assertSessionHasErrors('regra.renda_maxima_per_capita');

        $this->assertDatabaseMissing('editais', ['nome_processo' => 'Cota sem limite']);
    }

    public function test_student_can_view_published_edital_details_but_not_drafts(): void
    {
        $student = $this->criarUsuario('ESTUDANTE', 'ATIVA');
        $publicado = $this->criarEditalComRegra('PUBLICADO');
        $rascunho = $this->criarEditalComRegra('RASCUNHO');

        $this->actingAs($student)
            ->get(route('estudante.editais.show', $publicado))
            ->assertOk()
            ->assertSee($publicado->nome_processo);

        $this->get(route('estudante.editais.show', $rascunho))->assertNotFound();
    }

    public function test_student_cannot_access_director_user_management(): void
    {
        $student = $this->criarUsuario('ESTUDANTE', 'ATIVA');

        $this->actingAs($student)
            ->get(route('diretoria.dashboard'))
            ->assertForbidden();
    }

    public function test_logistics_does_not_warn_about_replica_cost_when_student_housing_is_available(): void
    {
        $user = $this->criarUsuario('ESTUDANTE', 'ATIVA');
        $user->perfil()->create([
            'cidade_origem' => 'Sorocaba',
            'estado_origem' => 'SP',
            'lat_origem' => null,
            'lng_origem' => null,
            'renda_per_capita' => 1200,
            'tipo_escola' => 'PUBLICA',
            'raca_cor' => 'PARDA',
            'nota_media_enem' => 680,
            'nivel_mobilidade' => 'ESTADUAL',
            'orcamento_moradia' => 500,
            'interesse_republica' => true,
            'pcd' => false,
        ]);

        $edital = $this->criarEditalComRegra('PUBLICADO');
        $edital->update([
            'custo_medio_republica' => 900,
            'tem_moradia_estudantil' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('api.estudante.painel'))
            ->assertOk()
            ->assertJsonPath('oportunidades.0.alertas_logisticos', []);
    }

    public function test_logistics_warns_about_replica_cost_when_no_student_housing_is_available(): void
    {
        $user = $this->criarUsuario('ESTUDANTE', 'ATIVA');
        $user->perfil()->create([
            'cidade_origem' => 'Sorocaba',
            'estado_origem' => 'SP',
            'lat_origem' => null,
            'lng_origem' => null,
            'renda_per_capita' => 1200,
            'tipo_escola' => 'PUBLICA',
            'raca_cor' => 'PARDA',
            'nota_media_enem' => 680,
            'nivel_mobilidade' => 'ESTADUAL',
            'orcamento_moradia' => 500,
            'interesse_republica' => true,
            'pcd' => false,
        ]);

        $edital = $this->criarEditalComRegra('PUBLICADO');
        $edital->update([
            'custo_medio_republica' => 900,
            'tem_moradia_estudantil' => false,
        ]);

        $this->actingAs($user)
            ->getJson(route('api.estudante.painel'))
            ->assertOk()
            ->assertJsonFragment([
                'O custo médio de república informado para a cidade supera o orçamento de moradia da ficha.',
            ]);
    }

    public function test_student_panel_evaluates_published_rules_and_ignores_drafts(): void
    {
        $user = $this->criarUsuario('ESTUDANTE', 'ATIVA');
        $user->perfil()->create([
            'cidade_origem' => 'Sorocaba',
            'estado_origem' => 'SP',
            'lat_origem' => null,
            'lng_origem' => null,
            'renda_per_capita' => 1200,
            'tipo_escola' => 'PUBLICA',
            'raca_cor' => 'PARDA',
            'nota_media_enem' => 680,
            'nivel_mobilidade' => 'ESTADUAL',
            'orcamento_moradia' => 800,
        ]);

        $publicado = $this->criarEditalComRegra('PUBLICADO');
        $this->criarEditalComRegra('RASCUNHO');

        $this->actingAs($user)
            ->getJson(route('api.estudante.painel'))
            ->assertOk()
            ->assertJsonCount(1, 'oportunidades')
            ->assertJsonPath('oportunidades.0.id_edital', $publicado->id)
            ->assertJsonPath('oportunidades.0.status_elegibilidade', 'COMPATIVEL')
            ->assertJsonPath('oportunidades.0.distancia_km', null);
    }

    public function test_diretoria_filtra_usuarios_por_busca_perfil_e_status(): void
    {
        $diretor = $this->criarUsuario('DIRETOR', 'ATIVA', 'diretor.filtro@example.test');
        $coordAtivo = $this->criarUsuario('COORDENADOR', 'ATIVA', 'coord.ativo@example.test');
        $estudanteBanido = $this->criarUsuario('ESTUDANTE', 'BANIDA', 'aluno.banido@example.test');

        $this->actingAs($diretor)
            ->get(route('diretoria.dashboard', [
                'busca' => 'aluno.banido',
                'perfil' => 'ESTUDANTE',
                'status' => 'BANIDA',
            ]))
            ->assertOk()
            ->assertSee($estudanteBanido->email)
            ->assertDontSee($coordAtivo->email);
    }

    public function test_atualizacao_de_conta_pela_diretoria_registra_log_de_auditoria(): void
    {
        $diretor = $this->criarUsuario('DIRETOR');
        $estudante = $this->criarUsuario('ESTUDANTE', 'ATIVA', 'audit.alvo@example.test');

        $this->actingAs($diretor)
            ->patch(route('diretoria.usuarios.atualizar', $estudante), [
                'tipo_perfil' => 'COORDENADOR',
                'status_conta' => 'ATIVA',
            ])->assertRedirect();

        $this->assertDatabaseHas('logs_auditoria', [
            'ator_id' => $diretor->id,
            'acao' => 'CONTA_ATUALIZADA',
            'entidade_tipo' => 'USUARIO',
            'entidade_id' => $estudante->id,
        ]);

        $this->actingAs($diretor)
            ->get(route('diretoria.dashboard'))
            ->assertOk()
            ->assertSee('CONTA_ATUALIZADA')
            ->assertSee('audit.alvo@example.test');
    }

    private function criarUsuario(string $perfil, string $status = 'ATIVA', ?string $email = null): User
    {
        return User::create([
            'nome' => 'Usuário de Teste',
            'email' => $email ?? strtolower($perfil) . '.' . strtolower($status) . '@example.test',
            'senha_hash' => Hash::make('senha-forte-123'),
            'tipo_perfil' => $perfil,
            'status_conta' => $status,
        ]);
    }


    private function criarEditalComRegra(string $statusPublicacao): Edital
    {
        $edital = Edital::create([
            'instituicao' => 'Instituição de Teste',
            'nome_processo' => 'Edital ' . $statusPublicacao,
            'cidade' => 'Campinas',
            'estado' => 'SP',
            'regiao' => 'SUDESTE',
            'latitude' => -22.90,
            'longitude' => -47.06,
            'custo_medio_republica' => 700,
            'tem_moradia_estudantil' => true,
            'ano_referencia' => 2027,
            'link_documento' => null,
            'status_publicacao' => $statusPublicacao,
        ]);

        $edital->regras()->create([
            'tipo_beneficio' => 'COTA_RENDA',
            'renda_maxima_per_capita' => 2118,
            'exige_escola_publica' => true,
            'exige_etnia' => false,
            'exige_pcd' => false,
            'nota_corte_estimada' => 640,
            'prazo_inicio' => '2026-08-01',
            'prazo_fim' => '2026-12-20',
            'documentos_exigidos' => 'Comprovante de renda.',
            'status_regra' => 'ATIVA',
            'versao' => 1,
        ]);

        return $edital;
    }

    private function dadosFicha(): array
    {
        return [
            'renda_per_capita' => 1200,
            'tipo_escola' => 'PUBLICA',
            'raca_cor' => 'PRETA',
            'nota_media_enem' => 680,
            'cep' => '18013-004',
            'cidade_origem' => 'Sorocaba',
            'estado_origem' => 'SP',
            'lat_origem' => -23.5015,
            'lng_origem' => -47.4526,
            'nivel_mobilidade' => 'ESTADUAL',
            'orcamento_moradia' => 800,
            'interesse_republica' => true,
            'precisa_auxilio_moradia' => false,
            'pcd' => false,
        ];
    }
}

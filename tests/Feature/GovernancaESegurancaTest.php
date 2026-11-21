<?php

namespace Tests\Feature;

use App\Models\Edital;
use App\Models\HistoricoRetificacao;
use App\Models\PerfilEstudante;
use App\Models\RegraElegibilidade;
use App\Models\User;
use Database\Seeders\EditaisSeeder;
use Database\Seeders\UsuariosSeeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\Concerns\CreatesScomTestSchema;
use Tests\TestCase;

class GovernancaESegurancaTest extends TestCase
{
    use CreatesScomTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->criarEsquemaScomParaTeste();
    }

    public function test_isolamento_estrito_entre_paineis_dos_tres_perfis(): void
    {
        $estudante = $this->criarUsuario('ESTUDANTE');
        $coordenador = $this->criarUsuario('COORDENADOR');
        $diretor = $this->criarUsuario('DIRETOR');

        // Estudante não acessa áreas administrativas
        $this->actingAs($estudante)->get(route('coordenacao.dashboard'))->assertForbidden();
        $this->actingAs($estudante)->get(route('diretoria.dashboard'))->assertForbidden();

        // Coordenador não acessa diretoria nem painel/API do estudante
        $this->actingAs($coordenador)->get(route('diretoria.dashboard'))->assertForbidden();
        $this->actingAs($coordenador)->get(route('estudante.painel'))->assertForbidden();
        $this->actingAs($coordenador)->getJson(route('api.estudante.painel'))->assertForbidden();

        // Diretor não acessa coordenação nem painel/API do estudante
        $this->actingAs($diretor)->get(route('coordenacao.dashboard'))->assertForbidden();
        $this->actingAs($diretor)->get(route('estudante.painel'))->assertForbidden();
        $this->actingAs($diretor)->getJson(route('api.estudante.painel'))->assertForbidden();
    }

    public function test_coordenador_nao_pode_gerenciar_usuarios_nem_promover_ou_banir(): void
    {
        $coordenador = $this->criarUsuario('COORDENADOR');
        $outroCoordenador = $this->criarUsuario('COORDENADOR');
        $estudante = $this->criarUsuario('ESTUDANTE');

        // Tenta rebaixar outro coordenador
        $this->actingAs($coordenador)
            ->patch(route('diretoria.usuarios.atualizar', $outroCoordenador), [
                'tipo_perfil' => 'ESTUDANTE',
                'status_conta' => 'ATIVA',
            ])->assertForbidden();

        // Tenta banir um estudante
        $this->actingAs($coordenador)
            ->patch(route('diretoria.usuarios.atualizar', $estudante), [
                'tipo_perfil' => 'ESTUDANTE',
                'status_conta' => 'BANIDA',
            ])->assertForbidden();

        // Tenta promover um estudante a diretor
        $this->actingAs($coordenador)
            ->patch(route('diretoria.usuarios.atualizar', $estudante), [
                'tipo_perfil' => 'DIRETOR',
                'status_conta' => 'ATIVA',
            ])->assertForbidden();

        $this->assertDatabaseHas('cadastros', [
            'id' => $outroCoordenador->id,
            'tipo_perfil' => 'COORDENADOR',
            'status_conta' => 'ATIVA',
        ]);
        $this->assertDatabaseHas('cadastros', [
            'id' => $estudante->id,
            'tipo_perfil' => 'ESTUDANTE',
            'status_conta' => 'ATIVA',
        ]);
    }

    public function test_estudante_nao_pode_editar_publicar_arquivar_ou_retificar_editais(): void
    {
        $estudante = $this->criarUsuario('ESTUDANTE');
        [$edital, $regra] = $this->criarEditalComRegra('RASCUNHO');

        $this->actingAs($estudante)
            ->patch(route('coordenacao.editais.atualizar', $edital), $this->dadosEdital())
            ->assertForbidden();

        $this->actingAs($estudante)
            ->post(route('coordenacao.editais.publicar', $edital))
            ->assertForbidden();

        $this->actingAs($estudante)
            ->post(route('coordenacao.editais.arquivar', $edital))
            ->assertForbidden();

        $this->actingAs($estudante)
            ->post(route('coordenacao.regras.retificar', $regra), $this->dadosRetificacao())
            ->assertForbidden();

        $this->assertSame('RASCUNHO', $edital->fresh()->status_publicacao);
        $this->assertSame('ATIVA', $regra->fresh()->status_regra);
    }

    public function test_diretor_nao_pode_alterar_ou_banir_outro_diretor_nem_promover_para_diretor(): void
    {
        $diretorA = $this->criarUsuario('DIRETOR');
        $diretorB = $this->criarUsuario('DIRETOR');
        $estudanteA = $this->criarUsuario('ESTUDANTE');
        $estudanteB = $this->criarUsuario('ESTUDANTE');

        // Diretor A tenta rebaixar e banir Diretor B
        $this->actingAs($diretorA)
            ->patch(route('diretoria.usuarios.atualizar', $diretorB), [
                'tipo_perfil' => 'ESTUDANTE',
                'status_conta' => 'BANIDA',
            ])->assertSessionHasErrors('usuario');

        $this->assertDatabaseHas('cadastros', [
            'id' => $diretorB->id,
            'tipo_perfil' => 'DIRETOR',
            'status_conta' => 'ATIVA',
        ]);

        // Diretor A tenta promover estudante a DIRETOR pela interface web
        $this->actingAs($diretorA)
            ->patch(route('diretoria.usuarios.atualizar', $estudanteA), [
                'tipo_perfil' => 'DIRETOR',
                'status_conta' => 'ATIVA',
            ])->assertSessionHasErrors('tipo_perfil');

        $this->assertDatabaseHas('cadastros', [
            'id' => $estudanteA->id,
            'tipo_perfil' => 'ESTUDANTE',
        ]);

        // Dois diretores operam contas diferentes em paralelo sem conflito
        $this->actingAs($diretorA)
            ->patch(route('diretoria.usuarios.atualizar', $estudanteA), [
                'tipo_perfil' => 'COORDENADOR',
                'status_conta' => 'ATIVA',
            ])->assertRedirect();

        $this->actingAs($diretorB)
            ->patch(route('diretoria.usuarios.atualizar', $estudanteB), [
                'tipo_perfil' => 'ESTUDANTE',
                'status_conta' => 'BANIDA',
            ])->assertRedirect();

        $this->assertDatabaseHas('cadastros', ['id' => $estudanteA->id, 'tipo_perfil' => 'COORDENADOR', 'status_conta' => 'ATIVA']);
        $this->assertDatabaseHas('cadastros', ['id' => $estudanteB->id, 'tipo_perfil' => 'ESTUDANTE', 'status_conta' => 'BANIDA']);
    }

    public function test_rebaixamento_de_coordenador_revoga_acesso_na_proxima_requisicao(): void
    {
        $diretor = $this->criarUsuario('DIRETOR');
        $coordenador = $this->criarUsuario('COORDENADOR');

        $this->actingAs($coordenador)->get(route('coordenacao.dashboard'))->assertOk();

        $this->actingAs($diretor)
            ->patch(route('diretoria.usuarios.atualizar', $coordenador), [
                'tipo_perfil' => 'ESTUDANTE',
                'status_conta' => 'ATIVA',
            ])->assertRedirect();

        $this->actingAs($coordenador->fresh())
            ->get(route('coordenacao.dashboard'))
            ->assertForbidden();
    }

    public function test_coordenador_edita_e_arquiva_edital_ocultando_do_estudante_sem_apagar_dados(): void
    {
        $coordenador = $this->criarUsuario('COORDENADOR');
        $estudante = $this->criarUsuario('ESTUDANTE');
        $this->criarPerfilCompleto($estudante);
        [$edital, $regra] = $this->criarEditalComRegra('PUBLICADO');

        // Antes de arquivar, estudante visualiza normalmente
        $this->actingAs($estudante)->get(route('estudante.editais.show', $edital))->assertOk();
        $this->actingAs($estudante)
            ->getJson(route('api.estudante.painel'))
            ->assertOk()
            ->assertJsonCount(1, 'oportunidades');

        // Coordenador edita dados do edital
        $this->actingAs($coordenador)
            ->patch(route('coordenacao.editais.atualizar', $edital), $this->dadosEdital([
                'nome_processo' => 'Vestibular Retificado 2027',
                'cidade' => 'Bauru',
            ]))->assertRedirect();

        $this->assertDatabaseHas('editais', [
            'id' => $edital->id,
            'nome_processo' => 'Vestibular Retificado 2027',
            'cidade' => 'Bauru',
        ]);

        // Coordenador arquiva o edital (sem deletar registros do banco)
        $this->actingAs($coordenador)
            ->post(route('coordenacao.editais.arquivar', $edital))
            ->assertRedirect();

        $this->assertDatabaseHas('editais', [
            'id' => $edital->id,
            'status_publicacao' => 'ARQUIVADO',
        ]);
        $this->assertDatabaseHas('regras_elegibilidade', ['id' => $regra->id]);

        // Após arquivamento, some da visão do estudante
        $this->actingAs($estudante)->get(route('estudante.editais.show', $edital))->assertNotFound();
        $this->actingAs($estudante)
            ->getJson(route('api.estudante.painel'))
            ->assertOk()
            ->assertJsonCount(0, 'oportunidades');
    }

    public function test_coordenador_nao_pode_publicar_edital_sem_regra_ativa(): void
    {
        $coordenador = $this->criarUsuario('COORDENADOR');
        [$edital, $regra] = $this->criarEditalComRegra('RASCUNHO');
        $regra->update(['status_regra' => 'INATIVA']);

        $this->actingAs($coordenador)
            ->post(route('coordenacao.editais.publicar', $edital))
            ->assertSessionHasErrors('edital');

        $this->assertSame('RASCUNHO', $edital->fresh()->status_publicacao);
    }

    public function test_retificacao_duplicada_na_mesma_versao_e_bloqueada(): void
    {
        $coordenador = $this->criarUsuario('COORDENADOR');
        [$edital, $regraV1] = $this->criarEditalComRegra('PUBLICADO');

        // Primeira retificação: cria v2 e inativa v1
        $this->actingAs($coordenador)
            ->post(route('coordenacao.regras.retificar', $regraV1), $this->dadosRetificacao())
            ->assertRedirect();

        $this->assertSame('INATIVA', $regraV1->fresh()->status_regra);
        $this->assertSame(1, HistoricoRetificacao::count());

        // Segunda tentativa na mesma v1 (ex: tela desatualizada ou coordenador concorrente)
        $this->actingAs($coordenador)
            ->post(route('coordenacao.regras.retificar', $regraV1), $this->dadosRetificacao([
                'motivo_retificacao' => 'Tentativa duplicada sobre versao ja encerrada.',
            ]))
            ->assertSessionHasErrors('regra');

        $this->assertSame(1, HistoricoRetificacao::count());
        $this->assertSame(2, $edital->regras()->count());
    }

    public function test_coordenador_rejeita_link_javascript_e_regras_incoerentes(): void
    {
        $coordenador = $this->criarUsuario('COORDENADOR');
        [$edital] = $this->criarEditalComRegra('RASCUNHO');

        // XSS via link_documento no cadastro
        $this->actingAs($coordenador)
            ->post(route('coordenacao.editais.store'), $this->dadosEditalComRegra([
                'link_documento' => 'javascript:alert(1)',
            ]))
            ->assertSessionHasErrors('link_documento');

        // XSS via link_documento na edição
        $this->actingAs($coordenador)
            ->patch(route('coordenacao.editais.atualizar', $edital), $this->dadosEdital([
                'link_documento' => 'javascript:alert(1)',
            ]))
            ->assertSessionHasErrors('link_documento');

        // Prazo final anterior ao inicial
        $this->actingAs($coordenador)
            ->post(route('coordenacao.editais.store'), $this->dadosEditalComRegra([], [
                'prazo_inicio' => '2027-02-10',
                'prazo_fim' => '2027-01-10',
            ]))
            ->assertSessionHasErrors('regra.prazo_fim');

        // Cota racial sem exigir critério étnico
        $this->actingAs($coordenador)
            ->post(route('coordenacao.editais.store'), $this->dadosEditalComRegra([], [
                'tipo_beneficio' => 'COTA_RACIAL',
                'exige_etnia' => false,
            ]))
            ->assertSessionHasErrors('regra.exige_etnia');

        // Cota escola pública sem exigir escola pública
        $this->actingAs($coordenador)
            ->post(route('coordenacao.editais.store'), $this->dadosEditalComRegra([], [
                'tipo_beneficio' => 'COTA_ESCOLA_PUBLICA',
                'exige_escola_publica' => false,
            ]))
            ->assertSessionHasErrors('regra.exige_escola_publica');
    }

    public function test_estudante_nao_sobrescreve_ficha_de_outro_nem_escala_perfil_na_api(): void
    {
        $estudanteA = $this->criarUsuario('ESTUDANTE');
        $estudanteB = $this->criarUsuario('ESTUDANTE');
        $this->criarPerfilCompleto($estudanteB, ['renda_per_capita' => 4500.00]);

        $payloadMalicioso = $this->dadosFichaApi() + [
            'id_usuario' => $estudanteB->id,
            'tipo_perfil' => 'DIRETOR',
            'status_conta' => 'BANIDA',
        ];

        $this->actingAs($estudanteA)
            ->postJson(route('api.estudante.ficha'), $payloadMalicioso)
            ->assertOk()
            ->assertJsonPath('sucesso', true);

        // Perfil do Estudante B permanece intacto
        $this->assertEquals(4500.00, (float) $estudanteB->fresh()->perfil->renda_per_capita);
        // Perfil foi salvo apenas para o Estudante A, sem escalação de papel
        $this->assertEquals(1200.00, (float) $estudanteA->fresh()->perfil->renda_per_capita);
        $this->assertSame('ESTUDANTE', $estudanteA->fresh()->tipo_perfil);
        $this->assertSame('ATIVA', $estudanteA->fresh()->status_conta);
    }

    public function test_api_da_ficha_rejeita_valores_fora_de_limite_com_422(): void
    {
        $estudante = $this->criarUsuario('ESTUDANTE');

        $this->actingAs($estudante)
            ->postJson(route('api.estudante.ficha'), $this->dadosFichaApi([
                'renda_per_capita' => -100,
                'nota_media_enem' => 1500,
                'estado_origem' => 'SPX',
                'lat_origem' => 999,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'renda_per_capita',
                'nota_media_enem',
                'estado_origem',
                'lat_origem',
            ]);
    }

    public function test_login_normaliza_email_e_mantem_mensagem_unica_contra_enumeracao(): void
    {
        $ativo = $this->criarUsuario('ESTUDANTE', 'ATIVA', 'estudante.norm@example.test');
        $banido = $this->criarUsuario('ESTUDANTE', 'BANIDA', 'banido.norm@example.test');

        $erroSenha = $this->post(route('login.autenticar'), [
            'email' => $ativo->email,
            'password' => 'senha-errada-123',
        ])->assertSessionHasErrors('email');
        $msgSenha = session('errors')->first('email');

        $erroInexistente = $this->post(route('login.autenticar'), [
            'email' => 'ninguem@example.test',
            'password' => 'senha-errada-123',
        ])->assertSessionHasErrors('email');
        $msgInexistente = session('errors')->first('email');

        $erroBanido = $this->post(route('login.autenticar'), [
            'email' => $banido->email,
            'password' => 'senha-forte-123',
        ])->assertSessionHasErrors('email');
        $msgBanido = session('errors')->first('email');

        // Todos os cenários de falha devolvem a mesma mensagem genérica
        $this->assertSame($msgSenha, $msgInexistente);
        $this->assertSame($msgSenha, $msgBanido);

        // E-mail com espaços e maiúsculas autentica normalmente
        $this->post(route('login.autenticar'), [
            'email' => '  ESTUDANTE.NORM@Example.Test  ',
            'password' => 'senha-forte-123',
        ])->assertRedirect(route('estudante.painel'));

        $this->assertAuthenticatedAs($ativo);
    }

    public function test_rate_limit_no_cadastro_bloqueia_apos_cinco_tentativas(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('cadastro.registrar'), [
                'nome' => "Teste {$i}",
                'email' => "tentativa{$i}@example.test",
                'password' => 'curta',
                'password_confirmation' => 'curta',
            ])->assertSessionHasErrors('password');
        }

        // 6ª tentativa é bloqueada pelo RateLimiter mesmo com dados válidos
        $this->post(route('cadastro.registrar'), [
            'nome' => 'Bloqueado',
            'email' => 'sexta@example.test',
            'password' => 'senha-super-segura-123',
            'password_confirmation' => 'senha-super-segura-123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('cadastros', ['email' => 'sexta@example.test']);
    }

    public function test_seeders_de_demonstracao_sao_idempotentes_e_respeitam_travas_de_seguranca(): void
    {
        // Idempotência: rodar duas vezes não duplica contas nem editais
        $this->seed(UsuariosSeeder::class);
        $this->seed(UsuariosSeeder::class);
        $this->assertSame(3, User::count());

        $this->seed(EditaisSeeder::class);
        $this->seed(EditaisSeeder::class);
        $this->assertSame(2, Edital::count());
        $this->assertSame(2, RegraElegibilidade::count());
        $this->assertDatabaseHas('editais', ['status_publicacao' => 'PUBLICADO']);

        // Bloqueio por senha de demonstração fraca (< 12 caracteres)
        $senhaOriginal = config('scom.demo_password');
        try {
            config(['scom.demo_password' => 'curta123']);
            $this->expectException(RuntimeException::class);
            (new UsuariosSeeder())->run();
        } finally {
            config(['scom.demo_password' => $senhaOriginal]);
        }
    }

    public function test_guard_de_seeders_bloqueia_ambiente_local_sem_flag_explicita(): void
    {
        $envOriginal = $this->app['env'];
        $flagOriginal = config('scom.allow_demo_seeders');

        try {
            $this->app['env'] = 'local';
            config(['scom.allow_demo_seeders' => false]);

            $this->expectException(RuntimeException::class);
            (new EditaisSeeder())->run();
        } finally {
            $this->app['env'] = $envOriginal;
            config(['scom.allow_demo_seeders' => $flagOriginal]);
        }
    }

    private function criarUsuario(string $perfil, string $status = 'ATIVA', ?string $email = null): User
    {
        return User::create([
            'nome' => "Usuario {$perfil}",
            'email' => $email ?? strtolower($perfil).'-'.uniqid().'@example.test',
            'senha_hash' => Hash::make('senha-forte-123'),
            'tipo_perfil' => $perfil,
            'status_conta' => $status,
        ]);
    }

    private function criarPerfilCompleto(User $user, array $overrides = []): PerfilEstudante
    {
        return PerfilEstudante::create(array_merge([
            'id_usuario' => $user->id,
            'cep' => '18013-004',
            'cidade_origem' => 'Sorocaba',
            'estado_origem' => 'SP',
            'lat_origem' => -23.5015,
            'lng_origem' => -47.4526,
            'renda_per_capita' => 1200.00,
            'tipo_escola' => 'PUBLICA',
            'raca_cor' => 'PARDA',
            'pcd' => false,
            'nota_media_enem' => 700,
            'nivel_mobilidade' => 'ESTADUAL',
            'orcamento_moradia' => 800.00,
            'interesse_republica' => true,
            'precisa_auxilio_moradia' => false,
            'atualizado_em' => now(),
        ], $overrides));
    }

    private function criarEditalComRegra(string $statusPublicacao): array
    {
        $edital = Edital::create(array_merge($this->dadosEdital(), [
            'status_publicacao' => $statusPublicacao,
        ]));

        $regra = RegraElegibilidade::create([
            'id_edital' => $edital->id,
            'tipo_beneficio' => 'COTA_RENDA',
            'renda_maxima_per_capita' => 2118.00,
            'exige_escola_publica' => true,
            'exige_etnia' => false,
            'exige_pcd' => false,
            'nota_corte_estimada' => 620,
            'prazo_inicio' => now()->subDays(5)->toDateString(),
            'prazo_fim' => now()->addDays(30)->toDateString(),
            'documentos_exigidos' => 'Histórico escolar e comprovante de renda.',
            'status_regra' => 'ATIVA',
            'versao' => 1,
        ]);

        return [$edital, $regra];
    }

    private function dadosEdital(array $overrides = []): array
    {
        return array_merge([
            'instituicao' => 'UNESP',
            'nome_processo' => 'Vestibular 2027',
            'cidade' => 'Sorocaba',
            'estado' => 'SP',
            'regiao' => 'SUDESTE',
            'latitude' => -23.4836,
            'longitude' => -47.4261,
            'custo_medio_republica' => 650.00,
            'tem_moradia_estudantil' => true,
            'ano_referencia' => 2027,
            'link_documento' => 'https://www.unesp.br/vestibular',
        ], $overrides);
    }

    private function dadosEditalComRegra(array $editalOverrides = [], array $regraOverrides = []): array
    {
        return $this->dadosEdital($editalOverrides) + [
            'regra' => array_merge([
                'tipo_beneficio' => 'COTA_RENDA',
                'renda_maxima_per_capita' => 2118.00,
                'exige_escola_publica' => true,
                'exige_etnia' => false,
                'exige_pcd' => false,
                'nota_corte_estimada' => 600,
                'prazo_inicio' => now()->subDays(5)->toDateString(),
                'prazo_fim' => now()->addDays(30)->toDateString(),
                'documentos_exigidos' => 'Histórico e comprovante de renda.',
            ], $regraOverrides),
        ];
    }

    private function dadosRetificacao(array $overrides = []): array
    {
        return array_merge([
            'motivo_retificacao' => 'Atualização do limite de renda conforme retificação oficial.',
            'tipo_beneficio' => 'COTA_RENDA',
            'renda_maxima_per_capita' => 2500.00,
            'exige_escola_publica' => true,
            'exige_etnia' => false,
            'exige_pcd' => false,
            'nota_corte_estimada' => 630,
            'prazo_inicio' => now()->subDays(5)->toDateString(),
            'prazo_fim' => now()->addDays(45)->toDateString(),
            'documentos_exigidos' => 'Documentos atualizados.',
        ], $overrides);
    }

    private function dadosFichaApi(array $overrides = []): array
    {
        return array_merge([
            'renda_per_capita' => 1200.00,
            'tipo_escola' => 'PUBLICA',
            'raca_cor' => 'PRETA',
            'nota_media_enem' => 710,
            'cep' => '18013-004',
            'estado_origem' => 'SP',
            'cidade_origem' => 'Sorocaba',
            'lat_origem' => -23.5015,
            'lng_origem' => -47.4526,
            'nivel_mobilidade' => 'ESTADUAL',
            'orcamento_moradia' => 800.00,
            'interesse_republica' => true,
            'precisa_auxilio_moradia' => false,
            'pcd' => false,
        ], $overrides);
    }
}
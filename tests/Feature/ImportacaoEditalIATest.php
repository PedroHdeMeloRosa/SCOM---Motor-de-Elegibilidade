<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesScomTestSchema;
use Tests\TestCase;

class ImportacaoEditalIATest extends TestCase
{
    use CreatesScomTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->criarEsquemaScomParaTeste();
    }

    private function criarUsuario(string $perfil, string $email): User
    {
        return User::create([
            'nome' => "Usuário {$perfil}",
            'email' => $email,
            'senha_hash' => Hash::make('SenhaSegura2026!'),
            'tipo_perfil' => $perfil,
            'status_conta' => 'ATIVA',
        ]);
    }

    public function test_estudante_e_diretor_nao_podem_acessar_escaneamento_ia(): void
    {
        $estudante = $this->criarUsuario('ESTUDANTE', 'aluno.ia@example.test');
        $diretor = $this->criarUsuario('DIRETOR', 'diretor.ia@example.test');

        $this->postJson(route('coordenacao.editais.escanear-ia'), [
            'texto_edital' => 'Edital UNESP 2027',
        ])->assertStatus(401);

        $this->actingAs($estudante)
            ->postJson(route('coordenacao.editais.escanear-ia'), [
                'texto_edital' => 'Edital UNESP 2027',
            ])->assertStatus(403);

        $this->actingAs($diretor)
            ->postJson(route('coordenacao.editais.escanear-ia'), [
                'texto_edital' => 'Edital UNESP 2027',
            ])->assertStatus(403);
    }

    public function test_coordenador_recebe_422_quando_nao_envia_pdf_nem_texto(): void
    {
        $coordenador = $this->criarUsuario('COORDENADOR', 'coord.vazio@example.test');

        $this->actingAs($coordenador)
            ->postJson(route('coordenacao.editais.escanear-ia'), [])
            ->assertStatus(422)
            ->assertJson([
                'sucesso' => false,
            ]);
    }

    public function test_coordenador_rejeita_arquivo_que_nao_e_pdf(): void
    {
        $coordenador = $this->criarUsuario('COORDENADOR', 'coord.arquivo@example.test');
        $arquivoInvalido = UploadedFile::fake()->create('script.php', 10, 'application/x-php');

        $this->actingAs($coordenador)
            ->postJson(route('coordenacao.editais.escanear-ia'), [
                'arquivo_pdf' => $arquivoInvalido,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['arquivo_pdf']);
    }

    public function test_coordenador_escaneia_texto_e_recebe_json_sanitizado(): void
    {
        $coordenador = $this->criarUsuario('COORDENADOR', 'coord.ok@example.test');

        $respostaFakeIA = json_encode([
            'instituicao' => '  UNESP  ',
            'nome_processo' => 'Vestibular 2027 - Isenção',
            'cidade' => 'Sorocaba',
            'estado' => 'sp',
            'regiao' => 'SUDESTE',
            'ano_referencia' => 2027,
            'custo_medio_republica' => 750.5,
            'tem_moradia_estudantil' => true,
            'link_documento' => 'javascript:alert(1)', // Deve ser descartado pela sanitização
            'regra' => [
                'tipo_beneficio' => 'COTA_RENDA',
                'renda_maxima_per_capita' => 2118,
                'exige_escola_publica' => true,
                'exige_etnia' => false,
                'exige_pcd' => false,
                'nota_corte_estimada' => 640,
                'prazo_inicio' => '2026-08-01',
                'prazo_fim' => 'data-invalida', // Deve virar null
                'documentos_exigidos' => 'RG, CPF e comprovante de renda.',
            ],
        ]);

        Http::fake([
            '*/api/chat' => Http::response([
                'message' => ['content' => $respostaFakeIA],
            ], 200),
        ]);

        $response = $this->actingAs($coordenador)
            ->postJson(route('coordenacao.editais.escanear-ia'), [
                'texto_edital' => 'Edital UNESP Sorocaba 2027 com renda máxima de R$ 2.118,00.',
            ]);

        $response->assertOk()
            ->assertJson([
                'sucesso' => true,
                'dados' => [
                    'instituicao' => 'UNESP',
                    'nome_processo' => 'Vestibular 2027 - Isenção',
                    'cidade' => 'Sorocaba',
                    'estado' => 'SP',
                    'regiao' => 'SUDESTE',
                    'ano_referencia' => 2027,
                    'custo_medio_republica' => 750.5,
                    'tem_moradia_estudantil' => true,
                    'link_documento' => null,
                    'regra' => [
                        'tipo_beneficio' => 'COTA_RENDA',
                        'renda_maxima_per_capita' => 2118.0,
                        'exige_escola_publica' => true,
                        'exige_etnia' => false,
                        'exige_pcd' => false,
                        'nota_corte_estimada' => 640,
                        'prazo_inicio' => '2026-08-01',
                        'prazo_fim' => null,
                        'documentos_exigidos' => 'RG, CPF e comprovante de renda.',
                    ],
                ],
            ]);
    }

    public function test_retorna_503_amigavel_quando_ollama_esta_offline(): void
    {
        $coordenador = $this->criarUsuario('COORDENADOR', 'coord.offline@example.test');

        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $this->actingAs($coordenador)
            ->postJson(route('coordenacao.editais.escanear-ia'), [
                'texto_edital' => 'Texto de edital para teste de fallback offline.',
            ])
            ->assertStatus(503)
            ->assertJson([
                'sucesso' => false,
            ]);
    }

    public function test_retorna_503_quando_ia_retorna_json_malformado(): void
    {
        $coordenador = $this->criarUsuario('COORDENADOR', 'coord.malformado@example.test');

        Http::fake([
            '*/api/chat' => Http::response([
                'message' => ['content' => 'isso não é um json válido {'],
            ], 200),
        ]);

        $this->actingAs($coordenador)
            ->postJson(route('coordenacao.editais.escanear-ia'), [
                'texto_edital' => 'Texto de edital para teste de JSON malformado.',
            ])
            ->assertStatus(503)
            ->assertJson([
                'sucesso' => false,
            ]);
    }
}
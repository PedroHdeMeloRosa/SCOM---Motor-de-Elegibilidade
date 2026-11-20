<?php

namespace Tests\Feature;

use App\Models\Edital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegurancaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\UsuariosSeeder::class);
    }

    public function test_xss_em_dados_de_edital_e_escapado_no_painel(): void
    {
        $estudante = User::where('email', 'estudante@example.test')->first();
        $this->assertNotNull($estudante);

        $payload = '<script>alert("xss")</script>';
        Edital::create([
            'instituicao' => $payload,
            'nome_processo' => 'Processo XSS',
            'cidade' => 'Sorocaba',
            'estado' => 'SP',
            'regiao' => 'SUDESTE',
            'latitude' => -23.5,
            'longitude' => -47.4,
            'ano_referencia' => 2026,
            'status_publicacao' => 'PUBLICADO',
        ]);

        $response = $this->actingAs($estudante)->get('/area-do-estudante');
        $response->assertStatus(200);

        // O payload NÃO deve aparecer cru (Blade deve ter escapado)
        $response->assertDontSee($payload, false);
        // Mas o texto "alert" deve aparecer (escapado), provando que o conteúdo foi renderizado
        $response->assertSee('alert', false);
    }

    public function test_estudante_nao_acessa_ficha_de_outro_estudante(): void
    {
        $estudante = User::where('email', 'estudante@example.test')->first();
        $this->assertNotNull($estudante);

        $outro = User::create([
            'nome' => 'Outro Estudante',
            'email' => 'outro@example.test',
            'senha_hash' => bcrypt('senha12345678'),
            'tipo_perfil' => 'ESTUDANTE',
            'status_conta' => 'ATIVA',
        ]);

        $response = $this->actingAs($estudante)->get('/api/estudante/painel/' . $outro->id);
        $this->assertContains(
            $response->status(),
            [403, 404, 405],
            'Estudante não deve acessar ficha de outro estudante'
        );
    }
}
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UsuariosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UsuariosSeeder::class);
    }

    public function test_visitante_e_redirecionado_para_login_ao_abrir_painel_do_estudante(): void
    {
        $response = $this->get('/area-do-estudante');
        $response->assertRedirect();
    }

    public function test_estudante_autenticado_consegue_abrir_area_do_estudante(): void
    {
        $estudante = User::where('email', 'estudante@example.test')->first();
        $this->assertNotNull($estudante, 'UsuariosSeeder deve criar estudante@example.test');

        $response = $this->actingAs($estudante)->get('/area-do-estudante');
        $response->assertStatus(200);
    }

    public function test_coordenador_autenticado_nao_recebe_erro_500_na_home(): void
    {
        $coordenador = User::where('email', 'coordenador@example.test')->first();
        $this->assertNotNull($coordenador, 'UsuariosSeeder deve criar coordenador@example.test');

        $response = $this->actingAs($coordenador)->get('/');
        $this->assertContains($response->status(), [200, 302], 'Home não deve retornar 500');
    }

    public function test_diretor_autenticado_acessa_dashboard_da_diretoria(): void
    {
        $diretor = User::where('email', 'diretor@example.test')->first();
        $this->assertNotNull($diretor, 'UsuariosSeeder deve criar diretor@example.test');

        $response = $this->actingAs($diretor)->get('/diretoria/dashboard');
        $response->assertStatus(200);
    }
}
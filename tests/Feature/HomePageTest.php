<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_nao_retorna_500(): void
    {
        $response = $this->get('/');
        $this->assertContains($response->status(), [200, 302], 'Home não deve retornar 500');
    }

    public function test_area_do_estudante_redireciona_visitante_para_login(): void
    {
        $response = $this->get('/area-do-estudante');
        $response->assertRedirect();
    }

    public function test_dashboard_da_diretoria_redireciona_visitante(): void
    {
        $response = $this->get('/diretoria/dashboard');
        $response->assertRedirect();
    }
}
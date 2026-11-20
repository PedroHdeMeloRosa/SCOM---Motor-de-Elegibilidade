<?php

namespace Tests\Unit;

use App\Services\Elegibilidade\AvaliadorElegibilidade;
use PHPUnit\Framework\TestCase;

class AvaliadorElegibilidadeTest extends TestCase
{
    private AvaliadorElegibilidade $avaliador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->avaliador = new AvaliadorElegibilidade();
    }

    public function test_profile_meeting_recorded_criteria_is_compatible(): void
    {
        $resultado = $this->avaliador->avaliar($this->perfil(), $this->regra(), '2026-11-20');

        $this->assertSame('COMPATIVEL', $resultado['status']);
        $this->assertSame([], $resultado['motivos']);
        $this->assertSame([], $resultado['pendencias']);
    }

    public function test_income_above_the_recorded_limit_is_incompatible(): void
    {
        $perfil = $this->perfil();
        $perfil['renda_per_capita'] = 2200;

        $resultado = $this->avaliador->avaliar($perfil, $this->regra(), '2026-11-20');

        $this->assertSame('INCOMPATIVEL', $resultado['status']);
        $this->assertNotEmpty($resultado['motivos']);
    }

    public function test_missing_race_color_is_pending_not_assumed_as_non_ppi(): void
    {
        $perfil = $this->perfil();
        $perfil['raca_cor'] = 'NAO_INFORMAR';

        $resultado = $this->avaliador->avaliar($perfil, $this->regra(), '2026-11-20');

        $this->assertSame('PENDENTE', $resultado['status']);
        $this->assertNotEmpty($resultado['pendencias']);
    }

    public function test_non_ppi_race_color_does_not_meet_a_racial_rule(): void
    {
        $perfil = $this->perfil();
        $perfil['raca_cor'] = 'BRANCA';

        $resultado = $this->avaliador->avaliar($perfil, $this->regra(), '2026-11-20');

        $this->assertSame('INCOMPATIVEL', $resultado['status']);
    }

    public function test_rule_is_not_evaluated_before_start_date(): void
    {
        $resultado = $this->avaliador->avaliar($this->perfil(), $this->regra(), '2026-07-31');

        $this->assertSame('AGUARDANDO_ABERTURA', $resultado['status']);
    }

    public function test_rule_is_marked_expired_after_end_date(): void
    {
        $resultado = $this->avaliador->avaliar($this->perfil(), $this->regra(), '2027-01-16');

        $this->assertSame('ENCERRADO', $resultado['status']);
    }

    public function test_estimated_cutoff_is_informational_not_a_disqualifying_rule(): void
    {
        $perfil = $this->perfil();
        $perfil['nota_media_enem'] = 500;

        $resultado = $this->avaliador->avaliar($perfil, $this->regra(), '2026-11-20');

        $this->assertSame('COMPATIVEL', $resultado['status']);
        $this->assertNotEmpty($resultado['alertas']);
    }

    public function test_school_requirement_is_enforced_only_when_declared_by_rule(): void
    {
        $perfil = $this->perfil();
        $perfil['tipo_escola'] = 'PRIVADA';
        $regra = $this->regra();
        $regra['exige_escola_publica'] = false;

        $resultado = $this->avaliador->avaliar($perfil, $regra, '2026-11-20');

        $this->assertSame('COMPATIVEL', $resultado['status']);
    }


    public function test_rule_requiring_pcd_rejects_profile_without_declared_condition(): void
    {
        $regra = $this->regra();
        $regra['exige_etnia'] = false;
        $regra['exige_pcd'] = true;
        $perfil = $this->perfil();
        $perfil['pcd'] = false;

        $resultado = $this->avaliador->avaliar($perfil, $regra, '2026-11-20');

        $this->assertSame('INCOMPATIVEL', $resultado['status']);
    }

    public function test_required_pcd_information_missing_is_pending_not_assumed_false(): void
    {
        $regra = $this->regra();
        $regra['exige_etnia'] = false;
        $regra['exige_pcd'] = true;
        $perfil = $this->perfil();
        unset($perfil['pcd']);

        $resultado = $this->avaliador->avaliar($perfil, $regra, '2026-11-20');

        $this->assertSame('PENDENTE', $resultado['status']);
        $this->assertNotEmpty($resultado['pendencias']);
    }

    public function test_end_date_before_start_date_returns_invalid_rule_without_breaking_panel(): void
    {
        $regra = $this->regra();
        $regra['prazo_inicio'] = '2026-12-01';
        $regra['prazo_fim'] = '2026-11-01';

        $resultado = $this->avaliador->avaliar($this->perfil(), $regra, '2026-11-20');

        $this->assertSame('REGRA_INVALIDA', $resultado['status']);
    }

    public function test_unknown_benefit_type_is_marked_as_invalid_rule(): void
    {
        $regra = $this->regra();
        $regra['tipo_beneficio'] = 'BENEFICIO_DESCONHECIDO';

        $resultado = $this->avaliador->avaliar($this->perfil(), $regra, '2026-11-20');

        $this->assertSame('REGRA_INVALIDA', $resultado['status']);
    }

    public function test_income_quota_without_limit_is_marked_as_invalid_rule(): void
    {
        $regra = $this->regra();
        $regra['renda_maxima_per_capita'] = null;

        $resultado = $this->avaliador->avaliar($this->perfil(), $regra, '2026-11-20');

        $this->assertSame('REGRA_INVALIDA', $resultado['status']);
    }

    public function test_racial_quota_without_racial_requirement_is_marked_as_invalid_rule(): void
    {
        $regra = $this->regra();
        $regra['tipo_beneficio'] = 'COTA_RACIAL';
        $regra['exige_etnia'] = false;

        $resultado = $this->avaliador->avaliar($this->perfil(), $regra, '2026-11-20');

        $this->assertSame('REGRA_INVALIDA', $resultado['status']);
    }

    private function perfil(): array
    {
        return [
            'renda_per_capita' => 1200,
            'tipo_escola' => 'PUBLICA',
            'raca_cor' => 'PRETA',
            'nota_media_enem' => 680,
        ];
    }

    private function regra(): array
    {
        return [
            'status_regra' => 'ATIVA',
            'tipo_beneficio' => 'COTA_RENDA',
            'prazo_inicio' => '2026-08-01',
            'prazo_fim' => '2026-12-20',
            'renda_maxima_per_capita' => 2118,
            'exige_escola_publica' => true,
            'exige_etnia' => true,
            'exige_pcd' => false,
            'nota_corte_estimada' => 640,
        ];
    }
}

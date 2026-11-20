<?php

namespace Database\Seeders;

use App\Models\Edital;
use App\Models\RegraElegibilidade;
use Database\Seeders\Concerns\GuardsDemoSeeders;
use Illuminate\Database\Seeder;

class EditaisSeeder extends Seeder
{
    use GuardsDemoSeeders;

    public function run(): void
    {
        $this->ensureDemoDatabaseIsAllowed();

        // Registros fictícios para demonstração; não representam editais oficiais.
        $dados = [
            [
                'instituicao' => 'UNESP (demonstração)',
                'nome_processo' => 'Auxílio Permanência e Moradia - exemplo',
                'cidade' => 'Sorocaba', 'estado' => 'SP', 'regiao' => 'SUDESTE',
                'latitude' => -23.469400, 'longitude' => -47.429900,
                'custo_medio_republica' => 600.00, 'tem_moradia_estudantil' => false,
                'ano_referencia' => 2026, 'link_documento' => null,
                'regra' => ['tipo_beneficio' => 'COTA_RENDA', 'renda_maxima_per_capita' => 2118.00, 'exige_escola_publica' => true, 'exige_etnia' => false, 'nota_corte_estimada' => 580, 'prazo_inicio' => '2026-08-01', 'prazo_fim' => '2026-12-20', 'documentos_exigidos' => 'Documentos de renda e histórico escolar.'],
            ],
            [
                'instituicao' => 'UFSCar (demonstração)',
                'nome_processo' => 'Bolsa Alimentação e Apoio Estudantil - exemplo',
                'cidade' => 'Sorocaba', 'estado' => 'SP', 'regiao' => 'SUDESTE',
                'latitude' => -23.585200, 'longitude' => -47.523300,
                'custo_medio_republica' => 650.00, 'tem_moradia_estudantil' => true,
                'ano_referencia' => 2026, 'link_documento' => null,
                'regra' => ['tipo_beneficio' => 'COTA_ESCOLA_PUBLICA', 'renda_maxima_per_capita' => null, 'exige_escola_publica' => true, 'exige_etnia' => false, 'nota_corte_estimada' => 550, 'prazo_inicio' => '2026-08-01', 'prazo_fim' => '2026-12-30', 'documentos_exigidos' => 'Declaração de escolaridade.'],
            ],
        ];

        foreach ($dados as $item) {
            $regra = $item['regra'];
            unset($item['regra']);

            $edital = Edital::firstOrCreate(
                [
                    'instituicao' => $item['instituicao'],
                    'nome_processo' => $item['nome_processo'],
                    'ano_referencia' => $item['ano_referencia'],
                ],
                $item
            );

            RegraElegibilidade::firstOrCreate(
                [
                    'id_edital' => $edital->id,
                    'tipo_beneficio' => $regra['tipo_beneficio'],
                    'versao' => 1,
                ],
                $regra + ['status_regra' => 'ATIVA', 'versao' => 1]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Edital;

class EditaisSeeder extends Seeder
{
    public function run(): void
    {
        $editais = [
            [
                'instituicao' => 'UNESP Sorocaba',
                'titulo' => 'Auxílio Permanência e Moradia 2026',
                'cidade' => 'Sorocaba', 'estado' => 'SP',
                'latitude' => -23.4694, 'longitude' => -47.4299, // Coordenadas UNESP Sorocaba
                'prazo_limite' => '2026-12-20',
                'custo_moradia_estimado' => 600.00,
                'tipo_vaga' => 'Moradia', 'escopo_geografico' => 'LOCAL'
            ],
            [
                'instituicao' => 'UFSCar Sorocaba',
                'titulo' => 'Bolsa Alimentação e Apoio Estudantil',
                'cidade' => 'Sorocaba', 'estado' => 'SP',
                'latitude' => -23.5852, 'longitude' => -47.5233, // Coordenadas UFSCar
                'prazo_limite' => '2026-11-30',
                'custo_moradia_estimado' => 650.00,
                'tipo_vaga' => 'Alimentação', 'escopo_geografico' => 'LOCAL'
            ],
            [
                'instituicao' => 'UNICAMP Campinas',
                'titulo' => 'Programa de Isenção de Taxas (SAE)',
                'cidade' => 'Campinas', 'estado' => 'SP',
                'latitude' => -22.8184, 'longitude' => -47.0647, // Coordenadas UNICAMP
                'prazo_limite' => '2026-12-15',
                'custo_moradia_estimado' => 850.00,
                'tipo_vaga' => 'Isenção', 'escopo_geografico' => 'ESTADUAL'
            ],
            [
                'instituicao' => 'USP São Paulo',
                'titulo' => 'Alojamento CRUSP / PUSP-C',
                'cidade' => 'São Paulo', 'estado' => 'SP',
                'latitude' => -23.5614, 'longitude' => -46.7293, // Coordenadas USP Butantã
                'prazo_limite' => '2027-01-10',
                'custo_moradia_estimado' => 1200.00,
                'tipo_vaga' => 'Alojamento', 'escopo_geografico' => 'ESTADUAL'
            ],
        ];

        foreach ($editais as $edital) {
            Edital::create($edital);
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Edital;
use Carbon\Carbon;

class EditaisMassivosSeeder extends Seeder
{
    public function run(): void
    {
        $hoje = Carbon::now();
        $universidades = [
            ['inst' => 'UFAM', 'cid' => 'Manaus', 'est' => 'AM', 'lat' => -3.0883, 'lng' => -59.9824, 'custo' => 850],
            ['inst' => 'UFPE', 'cid' => 'Recife', 'est' => 'PE', 'lat' => -8.0539, 'lng' => -34.9506, 'custo' => 900],
            ['inst' => 'UFRGS', 'cid' => 'Porto Alegre', 'est' => 'RS', 'lat' => -30.0331, 'lng' => -51.2300, 'custo' => 1100],
            ['inst' => 'UnB', 'cid' => 'Brasília', 'est' => 'DF', 'lat' => -15.7623, 'lng' => -47.8702, 'custo' => 1300],
            ['inst' => 'UFMG', 'cid' => 'Belo Horizonte', 'est' => 'MG', 'lat' => -19.8691, 'lng' => -43.9664, 'custo' => 950],
            ['inst' => 'UFRJ', 'cid' => 'Rio de Janeiro', 'est' => 'RJ', 'lat' => -22.8622, 'lng' => -43.2269, 'custo' => 1400],
            ['inst' => 'UFBA', 'cid' => 'Salvador', 'est' => 'BA', 'lat' => -12.9974, 'lng' => -38.5204, 'custo' => 800],
            ['inst' => 'UFPR', 'cid' => 'Curitiba', 'est' => 'PR', 'lat' => -25.4277, 'lng' => -49.2731, 'custo' => 1000],
        ];

        $tipos = ['Moradia', 'Alimentação', 'Isenção'];

        foreach ($universidades as $uni) {
            foreach ($tipos as $tipo) {
                // 30% de chance de ser uma vaga já EXPIRADA (Ex: há 2 meses atrás)
                $isPassado = rand(1, 100) <= 30; 
                $data = $isPassado ? $hoje->copy()->subDays(rand(10, 180)) : $hoje->copy()->addDays(rand(10, 365));

                Edital::create([
                    'instituicao' => $uni['inst'] . ' ' . $uni['cid'],
                    'titulo' => 'Programa de ' . $tipo . ' ' . $data->year,
                    'cidade' => $uni['cid'],
                    'estado' => $uni['est'],
                    'latitude' => $uni['lat'],
                    'longitude' => $uni['lng'],
                    'prazo_limite' => $data->format('Y-m-d'),
                    'custo_moradia_estimado' => $uni['custo'],
                    'tipo_vaga' => $tipo,
                    'escopo_geografico' => 'INTERESTADUAL'
                ]);
            }
        }
    }
}
<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardsDemoSeeders;
use Illuminate\Database\Seeder;

/**
 * Seeder legado desativado: os dados gerados aleatoriamente não eram
 * reprodutíveis e não possuíam regras de elegibilidade associadas.
 * Use EditaisSeeder apenas em banco isolado de demonstração.
 */
class EditaisMassivosSeeder extends Seeder
{
    use GuardsDemoSeeders;

    public function run(): void
    {
        $this->ensureDemoDatabaseIsAllowed();
        $this->call(EditaisSeeder::class);
    }
}

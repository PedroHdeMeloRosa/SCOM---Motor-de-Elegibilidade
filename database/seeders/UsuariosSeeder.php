<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\GuardsDemoSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UsuariosSeeder extends Seeder
{
    use GuardsDemoSeeders;

    public function run(): void
    {
        $this->ensureDemoDatabaseIsAllowed();

        $senha = (string) config('scom.demo_password', '');
        if (mb_strlen($senha) < 12) {
            throw new RuntimeException('Defina SCOM_DEMO_PASSWORD com pelo menos 12 caracteres para criar contas de demonstração.');
        }

        $contas = [
            ['nome' => 'Diretoria SCOM', 'email' => 'diretor@example.test', 'perfil' => 'DIRETOR'],
            ['nome' => 'Coordenação Acadêmica', 'email' => 'coordenador@example.test', 'perfil' => 'COORDENADOR'],
            ['nome' => 'Estudante de Demonstração', 'email' => 'estudante@example.test', 'perfil' => 'ESTUDANTE'],
        ];

        foreach ($contas as $conta) {
            User::firstOrCreate(
                ['email' => $conta['email']],
                [
                    'nome' => $conta['nome'],
                    'senha_hash' => Hash::make($senha),
                    'tipo_perfil' => $conta['perfil'],
                    'status_conta' => 'ATIVA',
                ]
            );
        }
    }
}

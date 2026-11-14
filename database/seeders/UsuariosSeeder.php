<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $senha_plana = 'scom2026';
        
        $contas = [
            ['name' => 'Diretoria SCOM', 'email' => 'diretor@unilegi.com', 'perfil' => 'DIRETOR'],
            ['name' => 'Coordenação Acadêmica', 'email' => 'coordenador@unilegi.com', 'perfil' => 'COORDENADOR'],
            ['name' => 'Pedro (Aluno Teste)', 'email' => 'estudante@unilegi.com', 'perfil' => 'ESTUDANTE']
        ];

        foreach ($contas as $conta) {
            // O firstOrCreate faz exatamente o que o seu script fazia:
            // Só cria a conta se o e-mail não existir no banco.
            User::firstOrCreate(
                ['email' => $conta['email']],
                [
                    'name' => $conta['name'],
                    'password' => Hash::make($senha_plana),
                    'tipo_perfil' => $conta['perfil'],
                    'status_conta' => 'ATIVA'
                ]
            );
        }
    }
}
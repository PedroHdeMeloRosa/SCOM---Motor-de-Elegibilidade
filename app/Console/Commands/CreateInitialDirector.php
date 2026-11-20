<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CreateInitialDirector extends Command
{
    protected $signature = 'scom:criar-diretor';

    protected $description = 'Cria a primeira conta de diretoria pelo terminal, sem rota pública.';

    public function handle(): int
    {
        if (User::query()->where('tipo_perfil', 'DIRETOR')->exists()) {
            $this->error('Já existe uma conta DIRETOR. Nenhuma alteração foi feita.');
            return self::FAILURE;
        }

        $dados = [
            'nome' => trim((string) $this->ask('Nome completo do diretor')),
            'email' => mb_strtolower(trim((string) $this->ask('E-mail do diretor'))),
            'password' => (string) $this->secret('Senha (mínimo 12 caracteres)'),
            'password_confirmation' => (string) $this->secret('Confirme a senha'),
        ];

        $validator = Validator::make($dados, [
            'nome' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:cadastros,email'],
            'password' => [
                'required', 'string', 'min:12', 'max:72', 'same:password_confirmation',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && strlen($value) > 72) {
                        $fail('A senha não pode ultrapassar 72 bytes.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $erro) {
                $this->error($erro);
            }

            return self::FAILURE;
        }

        $diretor = User::create([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'senha_hash' => Hash::make($dados['password']),
            'tipo_perfil' => 'DIRETOR',
            'status_conta' => 'ATIVA',
        ]);

        Log::notice('scom.auth.diretor_inicial_criado', [
            'cadastro_id' => $diretor->id,
        ]);

        $this->info('Conta inicial de diretoria criada. Guarde a senha em um gerenciador seguro.');

        return self::SUCCESS;
    }
}

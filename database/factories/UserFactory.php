<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /**
     * Define os atributos padrão de um cadastro.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'senha_hash' => static::$password ??= Hash::make('password'),
            'tipo_perfil' => 'ESTUDANTE',
            'status_conta' => 'ATIVA',
        ];
    }

    /**
     * Cria um cadastro com acesso bloqueado.
     */
    public function banned(): static
    {
        return $this->state(fn(array $attributes) => [
            'status_conta' => 'BANIDA',
        ]);
    }
}
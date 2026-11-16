<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Tabela utilizada para autenticação.
     */
    protected $table = 'cadastros';

    /**
     * A tabela utiliza criado_em, não created_at e updated_at.
     */
    public $timestamps = false;

    /**
     * Atributos permitidos para preenchimento em massa.
     */
    protected $fillable = [
        'nome',
        'email',
        'senha_hash',
        'tipo_perfil',
        'status_conta',
    ];

    /**
     * Atributos ocultos em serializações.
     */
    protected $hidden = [
        'senha_hash',
    ];

    /**
     * Conversões de atributos.
     */
    protected function casts(): array
    {
        return [
            'criado_em' => 'datetime',
        ];
    }

    /**
     * Informa ao Laravel o campo que armazena a senha.
     */
    public function getAuthPasswordName(): string
    {
        return 'senha_hash';
    }

    public function getAuthPassword(): string
    {
        return $this->senha_hash;
    }
}
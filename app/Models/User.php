<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Tabela oficial de autenticação no banco motor_elegibilidade.
     */
    protected $table = 'cadastros';

    /**
     * A tabela legada utiliza apenas criado_em (sem created_at/updated_at).
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
     * Atributos ocultos em serializações e respostas JSON.
     */
    protected $hidden = [
        'senha_hash',
        'remember_token',
    ];

    /**
     * Conversões de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'senha_hash' => 'hashed',
            'criado_em' => 'datetime',
        ];
    }

    /**
     * Informa ao sistema de autenticação do Laravel o nome da coluna de senha.
     */
    public function getAuthPasswordName(): string
    {
        return 'senha_hash';
    }

    /**
     * Retorna o hash da senha para verificação do Auth.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->senha_hash;
    }

    /**
     * Compatibilidade reversa: caso algum trecho chame $user->name, retorna $user->nome.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->nome,
            set: fn (string $value) => ['nome' => $value],
        );
    }

    /**
     * Helpers de verificação de perfil (RBAC) e status.
     */
    public function isAtiva(): bool
    {
        return $this->status_conta === 'ATIVA';
    }

    public function isEstudante(): bool
    {
        return $this->tipo_perfil === 'ESTUDANTE';
    }

    public function isCoordenador(): bool
    {
        return $this->tipo_perfil === 'COORDENADOR';
    }

    public function isDiretor(): bool
    {
        return $this->tipo_perfil === 'DIRETOR';
    }

    /**
     * Relação direta: Um usuário tem um (hasOne) Perfil de Estudante.
     */
    public function perfil()
    {
        return $this->hasOne(PerfilEstudante::class, 'id_usuario');
    }

    
}
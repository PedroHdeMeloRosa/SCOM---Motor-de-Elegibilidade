<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Os atributos que podem ser preenchidos em massa (Mass Assignment).
     */
    protected $fillable = [
        'name', 'email', 'password', 'tipo_perfil', 'status_conta',
        'renda_per_capita', 'tipo_escola', 'etnia', 'nota_media_enem', 
        'cep', 'cidade_origem', 'estado_origem', 'nivel_mobilidade', 
        'orcamento_moradia', 'interesse_republica', 'precisa_auxilio_moradia', 'pcd'
    ];

    /**
     * Os atributos que devem ficar ocultos (ex: ao retornar num JSON de API).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerfilEstudante extends Model
{
    use HasFactory;

    // Aponta para a tabela correta do banco legado
    protected $table = 'perfis_estudantes';

    // Desativa os timestamps padrão (created_at/updated_at), pois o legado usa atualizado_em
    public $timestamps = false;

    // Campos permitidos para gravação em massa
    protected $fillable = [
        'id_usuario',
        'cep',
        'cidade_origem',
        'estado_origem',
        'lat_origem',
        'lng_origem',
        'renda_per_capita',
        'tipo_escola',
        'etnia',
        'pcd',
        'nota_media_enem',
        'nivel_mobilidade',
        'orcamento_moradia',
        'interesse_republica',
        'precisa_auxilio_moradia'
    ];

    /**
     * Relação reversa: Este perfil pertence a um Usuário.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
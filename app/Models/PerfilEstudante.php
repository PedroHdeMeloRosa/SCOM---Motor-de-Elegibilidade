<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerfilEstudante extends Model
{
    use HasFactory;

    protected $table = 'perfis_estudantes';

    // updated_at não existe nesta tabela; a aplicação atualiza atualizado_em ao salvar a ficha.
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'cep',
        'cidade_origem',
        'estado_origem',
        'lat_origem',
        'lng_origem',
        'renda_per_capita',
        'tipo_escola',
        'etnia', // legado: mantido para preservar registros antigos; não recebe novas autodeclarações.
        'raca_cor',
        'pcd',
        'nota_media_enem',
        'nivel_mobilidade',
        'orcamento_moradia',
        'interesse_republica',
        'precisa_auxilio_moradia',
    ];

    protected function casts(): array
    {
        return [
            // Texto cifrado pelo Laravel; não é pesquisável por igualdade no SQL.
            'raca_cor' => 'encrypted',
            'renda_per_capita' => 'decimal:2',
            'lat_origem' => 'float',
            'lng_origem' => 'float',
            'nota_media_enem' => 'integer',
            'orcamento_moradia' => 'decimal:2',
            'pcd' => 'boolean',
            'interesse_republica' => 'boolean',
            'precisa_auxilio_moradia' => 'boolean',
            'atualizado_em' => 'datetime',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}

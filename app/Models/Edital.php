<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Edital extends Model
{
    use HasFactory;

    protected $table = 'editais';

    public $timestamps = false;

    protected $fillable = [
        'instituicao',
        'nome_processo',
        'cidade',
        'estado',
        'regiao',
        'latitude',
        'longitude',
        'custo_medio_republica',
        'tem_moradia_estudantil',
        'ano_referencia',
        'link_documento',
        'status_publicacao',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'custo_medio_republica' => 'float',
            'tem_moradia_estudantil' => 'boolean',
            'criado_em' => 'datetime',
        ];
    }

    /**
     * Relação: Um edital possui várias regras/modalidades de elegibilidade.
     */
    public function regras()
    {
        return $this->hasMany(RegraElegibilidade::class, 'id_edital');
    }
}
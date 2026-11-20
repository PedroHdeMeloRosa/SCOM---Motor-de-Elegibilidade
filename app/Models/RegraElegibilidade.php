<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegraElegibilidade extends Model
{
    use HasFactory;

    protected $table = 'regras_elegibilidade';

    public $timestamps = false;

    protected $fillable = [
        'id_edital',
        'tipo_beneficio',
        'renda_maxima_per_capita',
        'exige_escola_publica',
        'exige_etnia',
        'exige_pcd',
        'nota_corte_estimada',
        'prazo_inicio',
        'prazo_fim',
        'documentos_exigidos',
        'status_regra',
        'versao',
    ];

    protected function casts(): array
    {
        return [
            'renda_maxima_per_capita' => 'float',
            'exige_escola_publica' => 'boolean',
            'exige_etnia' => 'boolean',
            'exige_pcd' => 'boolean',
            'nota_corte_estimada' => 'integer',
            'versao' => 'integer',
        ];
    }

    /**
     * Relação: Uma regra de elegibilidade pertence a um Edital.
     */
    public function edital()
    {
        return $this->belongsTo(Edital::class, 'id_edital');
    }

    public function retificacoesOrigem()
    {
        return $this->hasMany(HistoricoRetificacao::class, 'id_regra_original');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoRetificacao extends Model
{
    protected $table = 'historico_retificacoes';

    public $timestamps = false;

    protected $fillable = [
        'id_regra_original',
        'id_regra_nova',
        'motivo_retificacao',
        'data_retificacao',
    ];

    protected function casts(): array
    {
        return [
            'data_retificacao' => 'datetime',
        ];
    }

    public function regraOriginal()
    {
        return $this->belongsTo(RegraElegibilidade::class, 'id_regra_original');
    }

    public function regraNova()
    {
        return $this->belongsTo(RegraElegibilidade::class, 'id_regra_nova');
    }
}

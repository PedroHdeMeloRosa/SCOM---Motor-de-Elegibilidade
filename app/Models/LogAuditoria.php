<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAuditoria extends Model
{
    protected $table = 'logs_auditoria';

    public $timestamps = false;

    protected $fillable = [
        'ator_id',
        'acao',
        'entidade_tipo',
        'entidade_id',
        'detalhes',
        'criado_em',
    ];

    protected function casts(): array
    {
        return [
            'criado_em' => 'datetime',
        ];
    }

    public function ator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ator_id');
    }
}
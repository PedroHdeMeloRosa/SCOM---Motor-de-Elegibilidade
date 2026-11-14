<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Edital extends Model
{
    use HasFactory;
    
    protected $table = 'editais'; // Garante o nome correto no plural

    protected $fillable = [
        'instituicao', 'titulo', 'cidade', 'estado', 'latitude', 'longitude', 
        'prazo_limite', 'custo_moradia_estimado', 'tipo_vaga', 'escopo_geografico'
    ];
}
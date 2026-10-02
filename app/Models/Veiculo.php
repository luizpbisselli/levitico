<?php

namespace App\Models;

use Illuminate\Database\Model;

class Veiculo extends Model
{
    protected $table = 'veiculos';

    protected $fillable = ['placa', 'tipo', 'marca', 'modelo', 'renavam', 'extras', 'ativo'];

    protected $casts = ['extras' => 'array', 'ativo' => 'boolean'];

    public function motoristas()
    {
        return $this->belongsToMany(Motorista::class, 'motorista_veiculo');
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motorista extends Model
{
    protected $table = 'motoristas';

    protected $fillable = ['user_id', 'nome', 'cnh', 'telefone', 'agregado', 'extras'];

    protected $casts = ['extras' => 'array', 'agregado' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function veiculos()
    {
        return $this->belongsToMany(Veiculo::class, 'motorista_veiculo');
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class);
    }
}

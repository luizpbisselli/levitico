<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = ['nome', 'documento', 'telefone', 'whatsapp', 'endereco', 'cidade', 'uf', 'cep'];

    public function nfes()
    {
        return $this->hasMany(Nfe::class);
    }
}

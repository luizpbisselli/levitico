<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailIngestao extends Model
{
    protected $table = 'email_ingestoes';

    protected $fillable = ['message_id', 'assunto', 'remetente', 'lido_em', 'status', 'detalhes'];

    protected $casts = ['lido_em' => 'datetime'];
}

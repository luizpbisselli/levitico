<?php

namespace App\Models;

use Illuminate\Database\Model;

class AuditoriaLog extends Model
{
    protected $table = 'auditoria_logs';

    protected $fillable = ['user_id', 'acao', 'modelo', 'registro_id', 'payload', 'ip'];

    protected $casts = ['payload' => 'array'];
}

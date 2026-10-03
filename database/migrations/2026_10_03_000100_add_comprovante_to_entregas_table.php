<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona suporte ao Canhoto Digital / Foto do Comprovante de Entrega.
     * Regra: migração estritamente aditiva com campos anuláveis.
     */
    public function up(): void
    {
        Schema::table('entregas', function (Blueprint $table) {
            if (! Schema::hasColumn('entregas', 'comprovante_path')) {
                $table->string('comprovante_path')->nullable()->after('observacao_ocorrencia');
            }
            if (! Schema::hasColumn('entregas', 'comprovante_enviado_em')) {
                $table->timestamp('comprovante_enviado_em')->nullable()->after('comprovante_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('entregas', function (Blueprint $table) {
            $table->dropColumn(['comprovante_path', 'comprovante_enviado_em']);
        });
    }
};

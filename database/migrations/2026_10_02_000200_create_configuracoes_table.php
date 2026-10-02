<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Configurações em runtime (hospedagem compartilhada, sem edição de .env via SSH).
 * Permite configurar MySQL e a caixa de e-mail IMAP direto pela área administrativa.
 * Regra do projeto: somente migrations aditivas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 100)->unique();
            $table->text('valor')->nullable();
            $table->boolean('sigilosa')->default(false); // valores mascarados na UI
            $table->timestamps();
        });

        // Migra valores já presentes no .env (se existirem) para o banco,
        // para que a tela "Configurações" já nasça preenchida após o deploy.
        $mapa = [
            'db.host'              => 'DB_HOST',
            'db.port'              => 'DB_PORT',
            'db.database'          => 'DB_DATABASE',
            'db.username'          => 'DB_USERNAME',
            'db.password'          => 'DB_PASSWORD',
            'email.host'           => 'INGESTAO_EMAIL_HOST',
            'email.port'           => 'INGESTAO_EMAIL_PORT',
            'email.user'           => 'INGESTAO_EMAIL_USER',
            'email.password'       => 'INGESTAO_EMAIL_PASSWORD',
            'email.box'            => 'INGESTAO_EMAIL_BOX',
            'email.pasta_processados' => 'INGESTAO_EMAIL_PASTA',
        ];

        $agora = now();
        foreach ($mapa as $chave => $varEnv) {
            $valor = trim((string) env($varEnv, ''));
            if ($valor === '') {
                continue;
            }
            DB::table('configuracoes')->insert([
                'chave'     => $chave,
                'valor'     => $valor,
                'sigilosa'  => str_contains($chave, 'password') ? 1 : 0,
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};

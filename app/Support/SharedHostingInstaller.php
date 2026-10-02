<?php

namespace App\Support;

/**
 * Instalador transparente para hospedagem compartilhada (cPanel, Hostinger,
 * Locaweb etc.), onde não há acesso SSH para rodar `php artisan migrate`.
 *
 * Executado automaticamente no boot do app, a cada requisição web. Toda a
 * lógica está em AutoInstall (compartilhada com o fallback public/install.php):
 *
 *  1. Garante que o arquivo SQLite exista (cria vazio se necessário);
 *  2. Gera a APP_KEY no .env automaticamente (sem key:generate);
 *  3. Roda as migrations pendentes na primeira execução e em novos deploys;
 *  4. Executa o seeder inicial (admin + dados de exemplo) uma única vez.
 *
 * O custo após a instalação é apenas algumas leituras leves de banco.
 */
class SharedHostingInstaller
{
    public static function boot(): void
    {
        try {
            AutoInstall::run();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

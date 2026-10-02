<?php

namespace App\Support;

use Illuminate\Database\Schema\SchemaManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Instalador transparente para hospedagem compartilhada (cPanel, Hostinger,
 * Locaweb etc.), onde não há acesso SSH para rodar `php artisan migrate`.
 *
 * Executado automaticamente no boot do app, a cada requisição:
 *
 *  1. Garante que o arquivo SQLite exista (cria vazio se necessário);
 *  2. Roda as migrations pendentes na primeira execução e em novos deploys;
 *  3. Executa o seeder inicial (admin + dados de exemplo) uma única vez;
 *  4. Registra um "gatilho" de scheduler para o cron da hospedagem (ver
 *     public/cron.php), sem exigir configuração manual além de 1 linha.
 *
 * O custo após a instalação é apenas algumas leituras leves de banco.
 */
class SharedHostingInstaller
{
    /** Nome da tabela usada como "flag" de instalação concluída. */
    protected const INSTALL_TABLE = 'email_ingestoes';

    public static function boot(): void
    {
        $sqlite = database_path('database.sqlite');

        // 1) Cria o banco vazio caso o arquivo não exista (ex.: .gitignore).
        if (! is_file($sqlite)) {
            @touch($sqlite);
        }

        try {
            if (! self::migrationsApplied()) {
                Artisan::call('migrate', ['--force' => true]);
            }

            if (! self::seeded()) {
                Artisan::call('db:seed', ['--force' => true]);
                self::markSeeded();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** As migrations já foram aplicadas? (checa tabela + colunas essenciais) */
    protected static function migrationsApplied(): bool
    {
        try {
            $schema = app(SchemaManager::class);
            if (! $schema->hasTable('users')) {
                return false;
            }

            // Migração nova ainda não aplicada? Compara com os arquivos.
            $ran = DB::table('migrations')->pluck('migration')->all();
            $files = collect(glob(database_path('migrations/*.php')))
                ->map(fn ($f) => basename($f, '.php'))
                ->all();

            return count(array_diff($files, $ran)) === 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /** O seed inicial já foi executado? */
    protected static function seeded(): bool
    {
        try {
            return DB::table(self::INSTALL_TABLE)->where('message_id', 'install_completed')->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function markSeeded(): void
    {
        try {
            if (! app(SchemaManager::class)->hasTable(self::INSTALL_TABLE)) {
                return;
            }

            DB::table(self::INSTALL_TABLE)->insert([
                'message_id' => 'install_completed',
                'assunto'    => 'Instalação automática (hospedagem compartilhada)',
                'remetente'  => 'system@local',
                'status'     => 'processado',
                'detalhes'   => now()->toDateTimeString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // melhor effort-only
        }
    }
}

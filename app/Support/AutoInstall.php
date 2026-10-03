<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Núcleo da instalação transparente (hospedagem compartilhada, sem SSH).
 *
 * Chamado tanto pelo SharedHostingInstaller (a cada requisição web) quanto
 * pelo public/install.php (fallback via navegador quando o PHP CLI do
 * cPanel não permite subir o app completo).
 *
 * Garante, nesta ordem:
 *  1. Arquivo SQLite existente (criado vazio se necessário);
 *  2. APP_KEY gerada automaticamente no .env (sem `artisan key:generate`);
 *  3. Migrations pendentes aplicadas;
 *  4. Seeder inicial executado uma única vez (admin + dados de exemplo).
 */
class AutoInstall
{
    /** Tabela usada como "flag" de seed concluído. */
    protected const SEED_FLAG_TABLE = 'email_ingestoes';
    protected const SEED_FLAG_ID   = 'install_completed';

    /**
     * Executa todos os passos de instalação e migração no MySQL.
     *
     * @return array{steps: array<string,string>, ok: bool}
     */
    public static function run(): array
    {
        $steps = [];

        // 1) Conexão MySQL
        $conexaoOk = self::ensureDatabaseConnection($erroDb);
        $steps['database'] = $conexaoOk
            ? 'ok (MySQL conectado)'
            : 'falha: ' . $erroDb;

        if (! $conexaoOk) {
            return [
                'steps' => $steps,
                'ok'    => false,
            ];
        }

        // 2) APP_KEY automática
        $steps['app_key'] = self::ensureAppKey();

        try {
            // 3) Migrations
            $steps['migrations'] = self::migrationsApplied()
                ? 'já aplicadas'
                : (Artisan::call('migrate', ['--force' => true]) === 0 ? 'aplicadas agora' : 'falha ao aplicar');

            // 4) Seed único
            if (! self::seeded()) {
                Artisan::call('db:seed', ['--force' => true]);
                self::markSeeded();
                $steps['seed'] = 'executado agora';
            } else {
                $steps['seed'] = 'já executado';
            }
        } catch (\Throwable $e) {
            $steps['erro'] = $e->getMessage();
        }

        return [
            'steps' => $steps,
            'ok'    => ! isset($steps['erro']) && $conexaoOk,
        ];
    }

    /** Valida se a conexão com o MySQL está acessível. */
    public static function ensureDatabaseConnection(?string &$erro = null): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $e) {
            $erro = $e->getMessage();
            return false;
        }
    }

    /**
     * Gera a APP_KEY diretamente (random_bytes + base64) e grava no .env,
     * eliminando a necessidade de `php artisan key:generate`.
     */
    public static function ensureAppKey(): string
    {
        $key = (string) config('app.key');

        if ($key !== '') {
            return 'já definida';
        }

        $newKey = 'base64:' . base64_encode(random_bytes(32));
        $envPath = base_path('.env');

        try {
            if (is_file($envPath)) {
                $contents = (string) file_get_contents($envPath);

                if (preg_match('/^APP_KEY=.*$/m', $contents)) {
                    $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $newKey, $contents);
                } else {
                    $contents .= "\nAPP_KEY=" . $newKey . "\n";
                }

                file_put_contents($envPath, $contents);
            }

            // Atualiza o valor em runtime para esta mesma requisição.
            putenv('APP_KEY=' . $newKey);
            $_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = $newKey;
            config(['app.key' => $newKey]);

            return 'gerada automaticamente';
        } catch (\Throwable $e) {
            return 'erro: ' . $e->getMessage();
        }
    }

    /** As migrations já foram aplicadas no MySQL? */
    public static function migrationsApplied(): bool
    {
        try {
            if (! Schema::hasTable('users')) {
                return false;
            }

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
    public static function seeded(): bool
    {
        try {
            return DB::table(self::SEED_FLAG_TABLE)
                ->where('message_id', self::SEED_FLAG_ID)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function markSeeded(): void
    {
        try {
            if (! Schema::hasTable(self::SEED_FLAG_TABLE)) {
                return;
            }

            $agora = now();

            DB::table(self::SEED_FLAG_TABLE)->insert([
                'message_id' => self::SEED_FLAG_ID,
                'assunto'    => 'Instalação automática (MySQL)',
                'remetente'  => 'system@local',
                'status'     => 'processado',
                'detalhes'   => $agora->toDateTimeString(),
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        } catch (\Throwable) {
            // best effort
        }
    }
}

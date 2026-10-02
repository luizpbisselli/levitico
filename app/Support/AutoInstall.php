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
     * Executa todos os passos de instalação.
     *
     * @return array{steps: array<string,string>, ok: bool}
     */
    public static function run(): array
    {
        $steps = [];

        // 1) Banco SQLite
        $steps['database'] = self::ensureDatabaseFile()
            ? 'ok'
            : 'erro: sem permissão para criar database/database.sqlite (ajuste as permissões da pasta database/ no cPanel)';

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
            'ok'    => ! isset($steps['erro']) && ! str_starts_with($steps['database'], 'erro'),
        ];
    }

    /** Cria o arquivo SQLite vazio caso não exista. */
    public static function ensureDatabaseFile(): bool
    {
        $sqlite = database_path('database.sqlite');

        if (is_file($sqlite)) {
            return true;
        }

        // Garante que a pasta existe e é gravável.
        if (! is_dir(dirname($sqlite))) {
            @mkdir(dirname($sqlite), 0775, true);
        }

        return @touch($sqlite);
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

    /** As migrations já foram aplicadas? (tabela users + arquivos pendentes) */
    public static function migrationsApplied(): bool
    {
        try {
            if (! static::ensureDatabaseFile()) {
                return false;
            }

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

            // INSERT direto com now() do PHP: evita depender de triggers
            // created_at/updated_at (inexistentes no SQLite).
            $agora = now();

            DB::table(self::SEED_FLAG_TABLE)->insert([
                'message_id' => self::SEED_FLAG_ID,
                'assunto'    => 'Instalação automática (hospedagem compartilhada)',
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

<?php

namespace App\Support;

use App\Models\Configuracao;

/**
 * Resolve a conexão de banco efetiva (banco de configurações > .env),
 * aplicando o override em runtime. Isso permite apontar o sistema para
 * um MySQL criado no cPanel sem editar o .env e sem SSH.
 *
 * Chamado logo após a criação da aplicação (bootstrap/app.php), antes de
 * qualquer uso do DB — inclusive pelo AutoInstall.
 */
class DatabaseConfigurator
{
    public static function apply(): void
    {
        try {
            $connection = self::fromDb();

            if ($connection === null) {
                return; // nada configurado: mantém o padrão do config/database.php
            }

            config(['database.default' => $connection]);
            config(['database.connections.' . $connection => $connection]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Retorna a definição de conexão vinda das configurações salvas, ou null. */
    public static function fromDb(): ?array
    {
        $driver = Configuracao::get('db.driver', 'mysql');

        if ($driver === 'mysql') {
            $database = Configuracao::comFallback('db.database', 'DB_DATABASE');
            $host     = Configuracao::comFallback('db.host', 'DB_HOST', '127.0.0.1');
            $username = Configuracao::comFallback('db.username', 'DB_USERNAME');

            // Só ativa MySQL quando há dados suficientes configurados.
            if (! $database || ! $host || ! $username) {
                return null;
            }

            return [
                'driver'    => 'mysql',
                'url'       => null,
                'host'      => $host,
                'port'      => (int) Configuracao::comFallback('db.port', 'DB_PORT', '3306'),
                'database'  => $database,
                'username'  => $username,
                'password'  => (string) Configuracao::comFallback('db.password', 'DB_PASSWORD', ''),
                'unix_socket' => '',
                'charset'   => Configuracao::get('db.charset', 'utf8mb4'),
                'collation' => Configuracao::get('db.collation', 'utf8mb4_unicode_ci'),
                'prefix'    => Configuracao::get('db.prefixo_tabela', ''),
                'prefix_indexes' => true,
                'strict'    => true,
                'engine'    => null,
                'options'   => extension_loaded('pdo_mysql') ? array_filter([
                    \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ];
        }

        return null;
    }

    /** Testa uma conexão MySQL com parâmetros brutos (usado pela tela de Configurações). */
    public static function testarMysql(array $params): array
    {
        if (! extension_loaded('pdo_mysql')) {
            return ['ok' => false, 'erro' => 'A extensão pdo_mysql não está disponível neste servidor.'];
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $params['host'] ?? 'localhost',
                (int) ($params['port'] ?? 3306),
                $params['database'] ?? ''
            );

            $pdo = new \PDO($dsn, $params['username'] ?? '', $params['password'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);

            $versao = $pdo->query('SELECT VERSION()')->fetchColumn();

            return ['ok' => true, 'info' => 'Conexão OK. MySQL ' . $versao];
        } catch (\Throwable $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }
}

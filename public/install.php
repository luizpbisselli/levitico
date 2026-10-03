<?php

/**
 * Fallback de instalação via navegador — hospedagem compartilhada sem SSH.
 *
 * Alguns servidores (cPanel/Hostinger/Locaweb) possuem PHP CLI desabilitado
 * ou restrito, o que impede o auto-instalador interno do Laravel de rodar
 * migrations via Artisan. Nesse caso basta acessar:
 *
 *     https://seu-dominio.com/install.php
 *
 * O script sobe o framework e delega tudo ao App\Support\AutoInstall:
 *  - valida conexão ativa com o banco MySQL;
 *  - gera a APP_KEY no .env automaticamente;
 *  - aplica as migrations pendentes no MySQL;
 *  - roda o seeder inicial (admin + dados de exemplo) uma única vez.
 *
 * Segurança: depois de instalado, renomeie/apague este arquivo OU defina
 * INSTALL_TOKEN no .env e acesse com ?token=... . Sem token configurado,
 * qualquer pessoa pode chamá-lo — mas ele é idempotente (não re-executa
 * migrations nem seed já concluídos).
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

$app = require_once $root . '/bootstrap/app.php';

// Sobe o kernel (carrega .env, config e provedores) sem tratar uma rota.
$kernel = $app->make(Illuminate\Contracts\Foundation\Application::class);
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

$result = App\Support\AutoInstall::run();

// Proteção opcional por token
$expectedToken = trim((string) env('INSTALL_TOKEN', ''));
if ($expectedToken !== '' && (string) ($_GET['token'] ?? '') !== $expectedToken) {
    http_response_code(403);
    echo '<h1>Acesso negado</h1><p>Use install.php?token=SEU_TOKEN (valor de INSTALL_TOKEN no .env).</p>';
    exit;
}

?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalação — Conciliação de Fretes</title>
    <style>
        body { font-family: system-ui, sans-serif; background:#0f172a; color:#e2e8f0; display:flex; justify-content:center; padding:40px 16px; }
        .card { background:#1e293b; border-radius:16px; padding:32px; max-width:560px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.4); }
        h1 { margin:0 0 8px; font-size:1.4rem; }
        p.sub { color:#94a3b8; margin-top:0; }
        ul { list-style:none; padding:0; margin:24px 0; }
        li { padding:10px 14px; border-radius:10px; margin-bottom:8px; background:#0f172a; display:flex; justify-content:space-between; gap:12px; }
        li b { color:#38bdf8; font-weight:600; }
        li span { text-align:right; }
        .ok li span { color:#4ade80; }
        .fail li span { color:#f87171; }
        a.btn { display:inline-block; margin-top:16px; background:#38bdf8; color:#0f172a; padding:12px 20px; border-radius:10px; text-decoration:none; font-weight:700; }
        .note { font-size:.85rem; color:#94a3b8; margin-top:20px; }
    </style>
</head>
<body>
<div class="card <?= $result['ok'] ? 'ok' : 'fail' ?>">
    <h1>⚙️ Instalação automática</h1>
    <p class="sub">Conciliação de Fretes — hospedagem compartilhada, sem terminal.</p>

    <ul>
        <?php foreach ($result['steps'] as $step => $status): ?>
            <li><b><?= htmlspecialchars((string) $step) ?></b><span><?= htmlspecialchars((string) $status) ?></span></li>
        <?php endforeach; ?>
    </ul>

    <?php if ($result['ok']): ?>
        <a class="btn" href="/levitico/login">Ir para o login →</a>
        <p class="note">
            Primeiro acesso: <b>admin@fretes.local</b> / <b>admin123</b> (troque a senha!).<br>
            Recomendado: apague ou renomeie este <code>install.php</code> após a instalação.
        </p>
    <?php else: ?>
        <p class="note">Corrija os erros acima (geralmente permissão de escrita nas pastas
            <code>database/</code>, <code>storage/</code> e no arquivo <code>.env</code>) e recarregue esta página.</p>
    <?php endif; ?>
</div>
</body>
</html>

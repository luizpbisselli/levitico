<?php

/**
 * Cron para hospedagem compartilhada (cPanel → "Trabalhos Cron").
 *
 * Configure UMA linha no cPanel, executando a cada minuto:
 *
 *   php -q /home/SEU_USUARIO/seu-dominio/public/cron.php
 *
 * Este arquivo dispara o agendador do Laravel (routes/console.php), que hoje
 * contém a ingestão de e-mails (app:ingestao-email, a cada 2 minutos) e
 * poderá receber outras tarefas futuras. O Laravel controla sozinho os
 * intervalos e o "sem sobreposição", então rodar a cada minuto é seguro.
 *
 * Se o PHP CLI estiver bloqueado no seu plano, alternativa: agendar via
 * serviço externo (cron-job.org / UptimeRobot) chamando
 * https://seu-dominio.com/run-cron?token=CRON_TOKEN (ver routes/web.php).
 */

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

/** @var Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$status = $app->make(Illuminate\Contracts\Console\Kernel::class)
    ->call('schedule:run');

exit($status);

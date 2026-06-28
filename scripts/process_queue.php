<?php

require_once __DIR__ . '/../src/Lib/Env.php';
Env::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../src/Lib/SendQueueProcessor.php';

$token = $argv[1] ?? '';
$statusFile = __DIR__ . '/../storage/queue_' . $token . '.json';
$logFile = __DIR__ . '/../storage/queue_' . $token . '.log';

@mkdir(dirname($statusFile), 0755, true);

$writeStatus = function (array $payload) use ($statusFile): void {
    file_put_contents($statusFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
};

$writeStatus([
    'status' => 'running',
    'message' => 'Initialisation…',
    'processed' => 0,
    'totalJobs' => 0,
    'pending' => 0,
]);

$processor = new SendQueueProcessor();
$pdo = DB::getConnection();

$stmt = $pdo->query('SELECT COUNT(*) FROM send_jobs WHERE status = "pending"');
$totalJobs = (int) $stmt->fetchColumn();

$writeStatus([
    'status' => 'running',
    'message' => 'Traitement en cours…',
    'processed' => 0,
    'totalJobs' => $totalJobs,
    'pending' => $totalJobs,
]);

$result = $processor->run(function (string $message) use ($statusFile, $logFile, $totalJobs, $pdo): void {
    $processed = (int) $pdo->query('SELECT COUNT(*) FROM send_jobs WHERE status = "sent"')->fetchColumn();
    $pending = (int) $pdo->query('SELECT COUNT(*) FROM send_jobs WHERE status = "pending"')->fetchColumn();

    file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
    file_put_contents($statusFile, json_encode([
        'status' => 'running',
        'message' => $message,
        'processed' => $processed,
        'totalJobs' => $totalJobs,
        'pending' => $pending,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
});

$writeStatus([
    'status' => 'done',
    'message' => 'Terminé',
    'processed' => $result['processed'],
    'totalJobs' => $totalJobs,
    'pending' => $result['pending'],
]);

<?php
// One-time web deploy helper for hosts without SSH.
// Usage: https://yourdomain.com/deploy.php?token=YOUR_SECRET_TOKEN
// DELETE THIS FILE after deployment.

$token = $_GET['token'] ?? '';
$secret = 'change-this-to-a-long-random-string';

if (!hash_equals($secret, $token)) {
    http_response_code(403);
    exit('Forbidden');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$commands = [
    'migrate' => ['--force' => true],
    'config:cache' => [],
    'route:cache' => [],
    'view:cache' => [],
];

$results = [];
foreach ($commands as $command => $params) {
    try {
        $exitCode = $kernel->call($command, $params);
        $results[$command] = "exit code: {$exitCode}";
    } catch (\Throwable $e) {
        $results[$command] = "ERROR: " . $e->getMessage();
    }
}

header('Content-Type: text/plain');
foreach ($results as $cmd => $res) {
    echo "{$cmd}: {$res}\n";
}
echo "\nDone. Delete deploy.php now.\n";
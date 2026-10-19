<?php
// One-time web sync helper for hosts without SSH.
// Visit: https://yourdomain.com/sync.php
// DELETE THIS FILE after use.

set_time_limit(300);
header('Content-Type: text/plain');

$base = dirname(__DIR__);
$commands = [
    'git pull origin main',
    'php artisan migrate --force',
    'php artisan db:seed --class=AdminUserSeeder',
    'php artisan config:cache',
    'php artisan route:clear',
    'php artisan route:cache',
    'php artisan view:cache',
];

foreach ($commands as $cmd) {
    echo "=== {$cmd} ===\n";
    if (function_exists('shell_exec')) {
        $out = shell_exec('cd ' . escapeshellarg($base) . ' && ' . $cmd . ' 2>&1');
        echo $out ?: "(no output)\n";
    } else {
        echo "shell_exec is disabled on this host. Run manually via SSH/terminal.\n";
    }
    echo "\n";
}
echo "Done. Delete sync.php now.\n";
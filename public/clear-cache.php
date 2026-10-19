<?php
// Visit: https://yourdomain.com/clear-cache.php
// DELETE after use.
if (function_exists('opcache_reset')) { opcache_reset(); echo "OPcache reset.\n"; }
// Clear Laravel view cache
$dir = __DIR__ . '/../storage/framework/views';
foreach (glob($dir . '/*.php') as $f) { @unlink($f); }
echo "View cache cleared.\n";
echo "Done. Delete clear-cache.php now.\n";
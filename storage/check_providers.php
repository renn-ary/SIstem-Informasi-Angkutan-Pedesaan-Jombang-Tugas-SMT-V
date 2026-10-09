<?php
ini_set('error_log', __DIR__ . '/php_errors.log');
require __DIR__ . '/../vendor/autoload.php';

echo "Check providers:\n";
$providers = [
    'Inertia\\ServiceProvider',
    'Tighten\\Ziggy\\ZiggyServiceProvider',
    'Laravel\\Sanctum\\SanctumServiceProvider',
];
foreach ($providers as $p) {
    $exists = class_exists($p);
    echo "  $p : " . ($exists ? "YES" : "NO") . "\n";
    if ($exists) {
        try {
            $r = new ReflectionClass($p);
            echo "     path: " . $r->getFileName() . "\n";
        } catch (Throwable $e) {
            echo "     error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nCheck Inertia ResponseFactory facade binding, Ziggy Blade directive:\n";
if (class_exists('Inertia\\ServiceProvider')) {
    echo "  Inertia methods available via SP\n";
}
if (class_exists('Tighten\\Ziggy\\ZiggyServiceProvider')) {
    $methods = get_class_methods('Tighten\\Ziggy\\ZiggyServiceProvider');
    echo "  Ziggy SP methods: " . implode(', ', $methods) . "\n";
}

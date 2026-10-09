<?php
ini_set('error_log', __DIR__ . '/php_errors.log');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$projectRoot = __DIR__ . '/..';
$installedJsonPath = $projectRoot . '/vendor/composer/installed.json';
$installedPhpPath = $projectRoot . '/vendor/composer/installed.php';
$composerJsonPath = $projectRoot . '/composer.json';

echo "=== Fix installed.json / installed.php (tambah Inertia, Ziggy, Sanctum) ===\n\n";

// Load existing
$installed = json_decode(file_get_contents($installedJsonPath), true);
if (!is_array($installed) || !isset($installed['packages'])) {
    echo "ERROR: installed.json tidak berbentuk dev-master composer 2?\n";
    var_dump(array_keys($installed));
    exit(1);
}

$expectedVersions = [
    'inertiajs/inertia-laravel' => ['v' => 'v2.0.12', 'type' => 'library', 'providers' => ['Inertia\\ServiceProvider'], 'src' => 'inertiajs/inertia-laravel/src', 'psr4' => ['Inertia\\' => 'src'], 'files' => ['./helpers.php']],
    'laravel/sanctum'           => ['v' => 'v4.2.1',  'type' => 'library', 'providers' => ['Laravel\\Sanctum\\SanctumServiceProvider'], 'src' => 'laravel/sanctum/src', 'psr4' => ['Laravel\\Sanctum\\' => 'src']],
    'tightenco/ziggy'           => ['v' => 'v2.5.1',  'type' => 'library', 'providers' => ['Tighten\\Ziggy\\ZiggyServiceProvider'], 'src' => 'tightenco/ziggy/src', 'psr4' => ['Tighten\\Ziggy\\' => 'src/']],
];

function loadPackageComposerJson($projectRoot, $name) {
    $candidate = $projectRoot . '/vendor/' . $name . '/composer.json';
    if (file_exists($candidate)) {
        $data = json_decode(file_get_contents($candidate), true);
        if (json_last_error() === JSON_ERROR_NONE) return $data;
    }
    return null;
}

$byName = [];
foreach ($installed['packages'] as $idx => $p) {
    $byName[$p['name']] = $idx;
}

foreach ($expectedVersions as $name => $meta) {
    $composerData = loadPackageComposerJson($projectRoot, $name);
    if (!$composerData) {
        echo "SKIP $name (tidak ada composer.json di vendor)\n";
        continue;
    }
    // Basic structure minimum untuk package:discover detect extra.laravel.providers
    $pkg = [
        'name' => $name,
        'version' => $meta['v'],
        'version_normalized' => preg_replace('/^v/', '', $meta['v']) . '.0',
        'type' => $composerData['type'] ?? 'library',
        'installation_source' => 'dist',
        'installed_path' => $name,
    ];
    if (isset($composerData['autoload'])) $pkg['autoload'] = $composerData['autoload'];
    if (isset($composerData['require']))  $pkg['require']  = $composerData['require'];
    if (isset($composerData['extra']))    $pkg['extra']    = $composerData['extra'];
    if (isset($composerData['license']))  $pkg['license']  = $composerData['license'];
    if (isset($composerData['description'])) $pkg['description'] = $composerData['description'];
    if (isset($composerData['keywords'])) $pkg['keywords'] = $composerData['keywords'];
    if (isset($composerData['authors'])) $pkg['authors'] = $composerData['authors'];
    if (isset($composerData['homepage'])) $pkg['homepage'] = $composerData['homepage'];
    if (isset($composerData['support'])) $pkg['support'] = $composerData['support'];
    if (isset($composerData['time'])) $pkg['time'] = $composerData['time'];
    if (isset($composerData['dist'])) $pkg['dist'] = $composerData['dist'];
    if (isset($composerData['source'])) $pkg['source'] = $composerData['source'];
    if (isset($composerData['suggest'])) $pkg['suggest'] = $composerData['suggest'];
    if (isset($composerData['autoload-dev'])) $pkg['autoload-dev'] = $composerData['autoload-dev'];
    if (isset($composerData['scripts'])) $pkg['scripts'] = $composerData['scripts'];
    if (isset($composerData['conflict'])) $pkg['conflict'] = $composerData['conflict'];
    if (isset($composerData['replace'])) $pkg['replace'] = $composerData['replace'];
    if (isset($composerData['provide'])) $pkg['provide'] = $composerData['provide'];
    if (isset($composerData['require-dev'])) $pkg['require-dev'] = $composerData['require-dev'];

    if (isset($byName[$name])) {
        $installed['packages'][$byName[$name]] = $pkg;
        echo "UPDATE: $name ke installed.json (version {$meta['v']})\n";
    } else {
        $installed['packages'][] = $pkg;
        $byName[$name] = count($installed['packages']) - 1;
        echo "INSERT: $name ke installed.json (version {$meta['v']})\n";
    }
}

// Commit installed.json
$json = json_encode($installed, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($installedJsonPath, $json);
echo "\nTulis installed.json: " . strlen($json) . " bytes\n";

// Re-installed.php generate (mirip format composer 2 installed.php)
// installed.php isinya array [ 'dev' => true, 'packages' => [ 'name' => [pretty_version, version, .. ] ] ]
$packagesFlat = [];
foreach ($installed['packages'] as $p) {
    $packagesFlat[$p['name']] = [
        'pretty_version' => $p['version'],
        'version' => $p['version_normalized'],
        'type' => $p['type'] ?? 'library',
        'install_path' => $p['installed_path'] ?? null,
        'aliases' => [],
        'reference' => $p['dist']['shasum'] ?? null,
        'dev' => (isset($installed['dev-package-names']) && in_array($p['name'], $installed['dev-package-names'])),
        'dev_requirement' => false,
    ];
}
$installedPhp = "<?php\n\nreturn [\n";
$installedPhp .= "    'root' => [\n";
$rootComposer = json_decode(file_get_contents($composerJsonPath), true);
$rootName = $rootComposer['name'] ?? 'laravel/laravel';
$rootVersion = $rootComposer['version'] ?? '1.0.0+no-version-set';
$installedPhp .= "        'name' => '$rootName',\n";
$installedPhp .= "        'pretty_version' => '$rootVersion',\n";
$installedPhp .= "        'version' => '1.0.0.0',\n";
$installedPhp .= "        'reference' => null,\n";
$installedPhp .= "        'type' => 'project',\n";
$installedPhp .= "        'install_path' => dirname(__DIR__, 2),\n";
$installedPhp .= "        'aliases' => [],\n";
$installedPhp .= "        'dev' => true,\n";
$installedPhp .= "    ],\n";
$installedPhp .= "    'versions' => [\n";
// Always add root itself
$installedPhp .= "        '$rootName' => [\n";
$installedPhp .= "            'pretty_version' => '$rootVersion',\n";
$installedPhp .= "            'version' => '1.0.0.0',\n";
$installedPhp .= "            'reference' => null,\n";
$installedPhp .= "            'type' => 'project',\n";
$installedPhp .= "            'install_path' => dirname(__DIR__, 2),\n";
$installedPhp .= "            'aliases' => [],\n";
$installedPhp .= "            'dev' => true,\n";
$installedPhp .= "        ],\n";
foreach ($packagesFlat as $name => $p) {
    $pv = var_export($p['pretty_version'], true);
    $vv = var_export($p['version'], true);
    $tv = var_export($p['type'], true);
    $iv = $p['install_path'] !== null ? var_export('__DIR__ . \'/..\' . ' . var_replace_for_installed_php($p['install_path']), true) : 'null';
    $dv = $p['dev'] ? 'true' : 'false';
    $installedPhp .= "        '$name' => [\n";
    $installedPhp .= "            'pretty_version' => $pv,\n";
    $installedPhp .= "            'version' => $vv,\n";
    $installedPhp .= "            'reference' => " . var_export($p['reference'], true) . ",\n";
    $installedPhp .= "            'type' => $tv,\n";
    $installedPhp .= "            'install_path' => $iv,\n";
    $installedPhp .= "            'aliases' => [],\n";
    $installedPhp .= "            'dev' => $dv,\n";
    $installedPhp .= "        ],\n";
}
$installedPhp .= "    ],\n];\n";

function var_replace_for_installed_php($rel) {
    return str_replace('\\', '/', $rel);
}

// Actually simpler approach - use __DIR__ . '/../' . $name relative
$installedPhp = str_replace("'__DIR__ . '/..' . '/", "'__DIR__ . '/../", $installedPhp);
file_put_contents($installedPhpPath, $installedPhp);
echo "Tulis installed.php: " . strlen($installedPhp) . " bytes\n";
echo "\nDONE. Sekarang jalankan: php artisan package:discover (dan restart serve)\n";

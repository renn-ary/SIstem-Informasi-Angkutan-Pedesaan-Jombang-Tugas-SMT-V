<?php

ini_set('error_log', __DIR__ . '/php_errors.log');
ini_set('display_errors', '1');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$resolveMap = [
    'codeload.github.com:443:20.205.243.165',
    'api.github.com:443:20.205.243.168',
    'github.com:443:20.205.243.166',
    'objects.githubusercontent.com:443:20.205.243.164',
    'raw.githubusercontent.com:443:20.205.243.133',
    'mirrors.aliyun.com:443:43.109.42.180',
    'repo.packagist.org:443:138.199.24.209',
];

$packages = [
    [
        'name' => 'inertiajs/inertia-laravel',
        'version' => 'v2.0.12',
        'commit' => '9170201299d741bb7f5f3baf37d8f1299a321753',
        'vendor_dir' => __DIR__ . '/../vendor/inertiajs/inertia-laravel',
        'url' => 'https://codeload.github.com/inertiajs/inertia-laravel/legacy.zip/9170201299d741bb7f5f3baf37d8f1299a321753',
        'autoload_psr4' => ['Inertia\\' => 'src/'],
        'license' => ['MIT'],
        'type' => 'library',
    ],
    [
        'name' => 'laravel/sanctum',
        'version' => 'v4.2.1',
        'commit' => 'f5fb373be39a246c74a060f2cf2ae2c2145b3664',
        'vendor_dir' => __DIR__ . '/../vendor/laravel/sanctum',
        'url' => 'https://codeload.github.com/laravel/sanctum/legacy.zip/f5fb373be39a246c74a060f2cf2ae2c2145b3664',
        'autoload_psr4' => ['Laravel\\Sanctum\\' => 'src/'],
        'license' => ['MIT'],
        'type' => 'library',
    ],
    [
        'name' => 'tightenco/ziggy',
        'version' => 'v2.5.1',
        'commit' => 'b9f0a56db58e6e9cc25d1a02d1c91ed659d52ec8',
        'vendor_dir' => __DIR__ . '/../vendor/tightenco/ziggy',
        'url' => 'https://codeload.github.com/tighten/ziggy/legacy.zip/v2.5.1',
        'autoload_psr4' => ['Tighten\\Ziggy\\' => 'src/'],
        'license' => ['MIT'],
        'type' => 'library',
    ],
];

$base = __DIR__ . '/manual_cache';
@mkdir($base, 0777, true);

function downloadFile($url, $save, $resolveMap) {
    echo "Downloading: " . basename($save) . " from $url...\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RESOLVE, $resolveMap);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($data === false || $code !== 200) {
        echo "  FAIL HTTP=$code, err=$err, size=" . strlen($data) . "\n";
        return false;
    }
    file_put_contents($save, $data);
    echo "  OK size=" . strlen($data) . " bytes\n";
    return true;
}

function unzipClean($zip, $dest) {
    @mkdir($dest, 0777, true);
    $za = new ZipArchive;
    if ($za->open($zip) !== true) { echo "ZIP OPEN FAIL for $zip\n"; return false; }
    $tmp = $dest . '_tmp_' . uniqid();
    @mkdir($tmp, 0777, true);
    $za->extractTo($tmp);
    $za->close();
    $dirs = glob($tmp . '/*', GLOB_ONLYDIR);
    if (count($dirs) === 1) {
        $inner = $dirs[0];
        foreach (scandir($inner) as $f) {
            if ($f === '.' || $f === '..') continue;
            $src = "$inner/$f";
            $dst = "$dest/$f";
            if (is_dir($src)) {
                $dstParent = dirname($dst);
                @mkdir($dstParent, 0777, true);
                rename($src, $dst);
            } else {
                $dstParent = dirname($dst);
                @mkdir($dstParent, 0777, true);
                copy($src, $dst);
            }
        }
    } else {
        foreach (scandir($tmp) as $f) {
            if ($f === '.' || $f === '..') continue;
            $src = "$tmp/$f";
            $dst = "$dest/$f";
            if (is_dir($src)) rename($src, $dst); else copy($src, $dst);
        }
    }
    // clean tmp
    $di = new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS);
    $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($ri as $file) { $file->isDir() ? rmdir($file) : unlink($file); }
    @rmdir($tmp);
    echo "  extracted to $dest\n";
    return true;
}

// --- Step 1: Download packages ---
echo "=== STEP 1: Download ===\n";
$zipFiles = [];
$allOk = true;
foreach ($packages as $p) {
    $safe = str_replace(['/', '\\'], '_', $p['name']) . '.zip';
    $zip = "$base/$safe";
    if (!file_exists($zip) || filesize($zip) < 1000) {
        if (!downloadFile($p['url'], $zip, $resolveMap)) {
            // Retry with api.github.com format
            $alt = 'https://api.github.com/repos/' . ( $p['name'] === 'tightenco/ziggy' ? 'tighten/ziggy' : $p['name'] ) . '/zipball/' . $p['commit'];
            echo "  retry alt: $alt\n";
            $ch2 = curl_init($alt);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch2, CURLOPT_RESOLVE, $resolveMap);
            curl_setopt($ch2, CURLOPT_USERAGENT, 'PHP/Composer');
            $d2 = curl_exec($ch2);
            $c2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            $e2 = curl_error($ch2);
            curl_close($ch2);
            if ($d2 === false || $c2 !== 200) { echo "  ALT FAIL HTTP=$c2 err=$e2\n"; $allOk = false; $zipFiles[] = null; continue; }
            file_put_contents($zip, $d2);
        }
    }
    $zipFiles[] = $zip;
}
if (!$allOk) { echo "ABORT some downloads failed\n"; exit(1); }

// --- Step 2: Extract to vendor ---
echo "\n=== STEP 2: Extract ===\n";
foreach ($packages as $i => $p) {
    $zip = $zipFiles[$i];
    if (!$zip) continue;
    // Clean previous
    if (is_dir($p['vendor_dir'])) {
        $di = new RecursiveDirectoryIterator($p['vendor_dir'], FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) { $file->isDir() ? rmdir($file) : unlink($file); }
        rmdir($p['vendor_dir']);
    }
    @mkdir(dirname($p['vendor_dir']), 0777, true);
    unzipClean($zip, $p['vendor_dir']);
}

// --- Step 3: Update composer.json require ---
echo "\n=== STEP 3: Update composer.json ===\n";
$cjPath = __DIR__ . '/../composer.json';
$cj = json_decode(file_get_contents($cjPath), true);
if (!isset($cj['require'])) $cj['require'] = [];
foreach ($packages as $p) {
    $ver = preg_replace('#^v#', '', $p['version']);
    $cj['require'][$p['name']] = '^' . $ver;
    echo "  added require {$p['name']}:^{$ver}\n";
}
// Sort packages
uksort($cj['require'], 'strnatcasecmp');
file_put_contents($cjPath, json_encode($cj, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n");
echo "  composer.json saved\n";

// --- Step 4: Add to installed.json and installed-packages.php ---
echo "\n=== STEP 4: Update installed metadata ===\n";
// installed.json
$installedJsonPath = __DIR__ . '/../vendor/composer/installed.json';
$installed = json_decode(file_get_contents($installedJsonPath), true);
$names = array_flip(array_column($installed['packages'] ?? [], 'name'));
foreach ($packages as $p) {
    if (isset($names[$p['name']])) continue;
    $installed['packages'][] = [
        'name' => $p['name'],
        'version' => $p['version'],
        'version_normalized' => ltrim($p['version'], 'v') . '.0',
        'source' => ['type' => 'git', 'url' => 'https://github.com/' . ( $p['name'] === 'tightenco/ziggy' ? 'tighten/ziggy' : $p['name'] ) . '.git', 'reference' => $p['commit']],
        'dist' => ['type' => 'zip', 'url' => $p['url'], 'reference' => $p['commit'], 'shasum' => ''],
        'require' => new stdClass(),
        'type' => $p['type'],
        'autoload' => ['psr-4' => (object)$p['autoload_psr4']],
        'notification-url' => 'https://packagist.org/downloads/',
        'license' => $p['license'],
    ];
    $names[$p['name']] = true;
    echo "  added to installed.json: {$p['name']} {$p['version']}\n";
}
file_put_contents($installedJsonPath, json_encode($installed, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n");

// installed-packages.php (PHP format)
$ippPath = __DIR__ . '/../vendor/composer/installed-packages.php';
$packagesPhp = null;
if (file_exists($ippPath)) {
    include $ippPath;
    if (is_array($packagesPhp)) {
        foreach ($packages as $p) {
            if (!isset($packagesPhp[$p['name']])) {
                $packagesPhp[$p['name']] = [
                    'pretty_version' => $p['version'],
                    'version' => ltrim($p['version'], 'v') . '.0',
                    'reference' => $p['commit'],
                    'type' => $p['type'],
                    'install_path' => $p['vendor_dir'],
                    'aliases' => [],
                    'dev' => false,
                ];
                echo "  added to installed-packages.php: {$p['name']}\n";
            }
        }
        $content = "<?php\n\n// InstalledVersions::getRawData() format\n\$versions[" . var_export(array_keys($packagesPhp)[0], true) . "] = []; // placeholder\n\nreturn \$packagesPhp = [\n";
        foreach ($packagesPhp as $name => $info) {
            $content .= "    " . var_export($name, true) . " => [\n";
            foreach ($info as $k => $v) {
                if (is_array($v)) {
                    $content .= "        '$k' => [],\n";
                } else {
                    $content .= "        '$k' => " . var_export($v, true) . ",\n";
                }
            }
            $content .= "    ],\n";
        }
        $content .= "];\n";
        file_put_contents($ippPath, $content);
    }
}

// autoload_psr4.php
$psr4Path = __DIR__ . '/../vendor/composer/autoload_psr4.php';
if (file_exists($psr4Path)) {
    $map = include $psr4Path;
    foreach ($packages as $p) {
        foreach ($p['autoload_psr4'] as $ns => $src) {
            $fullPath = $p['vendor_dir'] . '/' . rtrim($src, '/');
            if (!isset($map[$ns]) || !in_array($fullPath, (array)$map[$ns])) {
                if (isset($map[$ns])) {
                    if (is_array($map[$ns])) $map[$ns][] = $fullPath;
                    else $map[$ns] = [$map[$ns], $fullPath];
                } else {
                    $map[$ns] = [$fullPath];
                }
                echo "  added PSR-4 $ns => $fullPath\n";
            }
        }
    }
    // Write
    $content = "<?php\n\n// autoload_psr4.php @generated by Composer\n\n\$vendorDir = dirname(__DIR__);\n\$baseDir = dirname(\$vendorDir);\n\nreturn array(\n";
    foreach ($map as $ns => $dirs) {
        $nsRep = var_export($ns, true);
        if (!is_array($dirs)) $dirs = [$dirs];
        $phpDirs = array_map(fn($d) => '$vendorDir . ' . var_export(substr($d, strlen(__DIR__ . '/../vendor')), true), $dirs);
        // Handle fallback: write relative to $vendorDir
        $content .= "    $nsRep => array(";
        $items = [];
        foreach ($dirs as $d) {
            if (str_starts_with($d, __DIR__ . '/../vendor')) {
                $rel = substr($d, strlen(__DIR__ . '/../vendor'));
                $items[] = '$vendorDir . ' . var_export($rel, true);
            } else {
                $items[] = var_export($d, true);
            }
        }
        $content .= implode(', ', $items) . "),\n";
    }
    $content .= ");\n";
    file_put_contents($psr4Path, $content);
}

echo "\n=== DONE. Now run: composer dump-autoload (or via artisan patched) ===\n";

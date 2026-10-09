<?php
$srcPhar = 'C:\\laragon\\bin\\composer\\composer.phar';
$workDir = __DIR__ . '\\composer_patch_work';
$extractDir = $workDir . '\\extracted';
$patchedPhar = __DIR__ . '\\composer_dns_safe.phar';

$resolveItems = [
    'codeload.github.com:443:20.205.243.165',
    'api.github.com:443:20.205.243.168',
    'github.com:443:20.205.243.166',
    'objects.githubusercontent.com:443:20.205.243.164',
    'raw.githubusercontent.com:443:20.205.243.133',
    'user-images.githubusercontent.com:443:20.205.243.133',
];
$dnsServers = '8.8.8.8,8.8.4.4';
$resolvePhp = var_export($resolveItems, true);

function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $p = $f->getPathname();
        if ($f->isDir()) @rmdir($p); else @unlink($p);
    }
    @rmdir($dir);
}

echo "=== Step 1: Cleanup & Persiapan ===\n";
rrmdir($workDir);
@mkdir($workDir, 0777, true);
@mkdir($extractDir, 0777, true);
$localPhar = $workDir . '\\composer.phar';
copy($srcPhar, $localPhar);
echo "OK\n\n";

echo "=== Step 2: Extract PHAR ===\n";
$phar = new Phar($localPhar);
$phar->extractTo($extractDir, null, true);
echo "OK. Struktur root extract:\n";
foreach (scandir($extractDir) as $f) {
    if ($f === '.' || $f === '..') continue;
    echo "  - $f\n";
}
echo "\n";

echo "=== Step 3: Cari file handler CURL/HTTP ===\n";
$targets = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($it as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'php') continue;
    $c = file_get_contents($path);
    if (strpos($c, 'curl_setopt_array') !== false || strpos($c, 'curl_init') !== false) {
        $rel = substr($path, strlen($extractDir) + 1);
        if (strpos($rel, 'tests/') === 0) continue;
        echo "  + $rel\n";
        $targets[] = $path;
    }
}
echo "Ditemukan " . count($targets) . " file calon.\n\n";

echo "=== Step 4: PATCH dengan 3 pattern sederhana ===\n";
$count = 0;
foreach ($targets as $path) {
    $c = file_get_contents($path);
    $orig = $c;

    $c = preg_replace_callback(
        '/(^|;|\{|\s)(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*curl_init\s*(\([^;]*\));/',
        function($m) use ($resolvePhp, $dnsServers) {
            $prefix = $m[1];
            $var = $m[2];
            $args = $m[3];
            $inject = PHP_EOL . '            @curl_setopt(' . $var . ', CURLOPT_RESOLVE, ' . $resolvePhp . ');' . PHP_EOL
                    . '            @curl_setopt(' . $var . ', CURLOPT_DNS_SERVERS, \'' . $dnsServers . '\');';
            return $prefix . $var . ' = curl_init' . $args . ';' . $inject;
        },
        $c
    );

    $c = preg_replace_callback(
        '/curl_setopt_array\s*\(\s*(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*,\s*(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*\)/',
        function($m) use ($resolvePhp, $dnsServers) {
            $chVar = $m[1];
            $optVar = $m[2];
            $pre = ''
                . '(function($__ch, &$__o) { $__dnsmap = ' . $resolvePhp . '; '
                . '$__o[CURLOPT_RESOLVE] = array_merge(isset($__o[CURLOPT_RESOLVE]) ? $__o[CURLOPT_RESOLVE] : [], $__dnsmap); '
                . '$__o[CURLOPT_DNS_SERVERS] = ' . var_export($dnsServers, true) . '; '
                . 'return true; })(' . $chVar . ', ' . $optVar . '); ';
            return $pre . 'curl_setopt_array(' . $chVar . ', ' . $optVar . ')';
        },
        $c
    );

    $c = preg_replace_callback(
        '/(curl_setopt_array\s*\(\s*\$[a-zA-Z_][a-zA-Z0-9_]*\s*,\s*)(\[\s*)((?:[^\[\]]|(?2))*\])(\s*\))/',
        function($m) use ($resolvePhp, $dnsServers) {
            $before = $m[1];
            $open = $m[2];
            $body = $m[3];
            $close = $m[4];
            $inject = "\n                "
                . 'CURLOPT_RESOLVE => ' . $resolvePhp . ",\n"
                . '                CURLOPT_DNS_SERVERS => ' . var_export($dnsServers, true) . ",\n";
            return $before . $open . $inject . $body . $close;
        },
        $c
    );

    if ($c !== $orig) {
        file_put_contents($path, $c);
        $count++;
        echo "  [OK] " . substr($path, strlen($extractDir) + 1) . "\n";
    }
}
echo "Total file terpatch: $count\n\n";

echo "=== Step 5: Verifikasi salah satu file patch ===\n";
$sampleHit = 0;
$showSample = true;
foreach ($targets as $path) {
    $cc = file_get_contents($path);
    if (strpos($cc, 'CURLOPT_RESOLVE') !== false) {
        $sampleHit++;
        if ($showSample) {
            echo "SAMPLE - " . substr($path, strlen($extractDir) + 1) . ":\n";
            $lines = file($path);
            $print = false;
            $printed = 0;
            foreach ($lines as $idx => $l) {
                if (strpos($l, 'CURLOPT_RESOLVE') !== false || strpos($l, 'DNS FIX') !== false || strpos($l, '__dnsmap') !== false) {
                    $print = true;
                    $printed = 0;
                }
                if ($print) {
                    echo "  " . ($idx + 1) . ": " . rtrim($l, "\r\n") . "\n";
                    $printed++;
                    if ($printed > 8) { $print = false; $showSample = false; }
                }
            }
        }
    }
}
echo "File yang mengandung CURLOPT_RESOLVE setelah patch: $sampleHit\n\n";

echo "=== Step 6: Re-pack PHAR ===\n";
if (file_exists($patchedPhar)) unlink($patchedPhar);

$stub = "#!/usr/bin/env php\n<?php\n";
if (file_exists($extractDir . '\\bin\\composer')) {
    $stub .= "Phar::mapPhar('composer.phar');\nrequire 'phar://composer.phar/bin/composer';\n__HALT_COMPILER();\n";
} elseif (file_exists($extractDir . '\\src\\Composer\\Autoload\\ClassLoader.php')) {
    $stubContent = file_get_contents($extractDir . '\\.phar\\stub.php');
    if ($stubContent === false) {
        $stubContent = "#!/usr/bin/env php\n<?php\nPhar::mapPhar('composer.phar');\nrequire 'phar://composer.phar/vendor/autoload.php';\n__HALT_COMPILER();\n";
    }
    $stub = $stubContent;
} else {
    $stub .= "Phar::mapPhar('composer.phar');\nrequire 'phar://composer.phar/vendor/autoload.php';\n__HALT_COMPILER();\n";
}

$newPhar = new Phar($patchedPhar, FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME, 'composer.phar');
$newPhar->startBuffering();
$newPhar->buildFromDirectory($extractDir);
$newPhar->setStub($stub);
$newPhar->stopBuffering();

echo "Stub:\n  " . str_replace("\n", "\n  ", trim($stub)) . "\n";
echo "Phar file: $patchedPhar\n";
clearstatcache();
echo "Size: " . (file_exists($patchedPhar) ? number_format(filesize($patchedPhar)) : '0') . " bytes\n";

echo "\n=== SELESAI ===\n";
echo "Coba jalankan:\n  php $patchedPhar --version\n  php $patchedPhar install\n\n";

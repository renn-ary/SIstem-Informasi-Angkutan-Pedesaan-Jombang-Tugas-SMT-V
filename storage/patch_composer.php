<?php
$srcPhar = 'C:\\laragon\\bin\\composer\\composer.phar';
$workDir = __DIR__ . '\\composer_patch_work';
$extractDir = $workDir . '\\extracted';
$patchedPhar = __DIR__ . '\\composer_dns_safe.phar';

$resolveMap = [
    'codeload.github.com:443:20.205.243.165',
    'api.github.com:443:20.205.243.168',
    'github.com:443:20.205.243.166',
    'objects.githubusercontent.com:443:20.205.243.164',
    'raw.githubusercontent.com:443:20.205.243.133',
    'user-images.githubusercontent.com:443:20.205.243.133',
];
$dnsServers = '8.8.8.8,8.8.4.4';

echo "=== Step 1: Persiapan direktori ===\n";
@mkdir($workDir, 0777, true);
@mkdir($extractDir, 0777, true);
cleanDir($extractDir);
echo "OK\n\n";

echo "=== Step 2: Copy composer.phar ===\n";
$localPhar = $workDir . '\\composer_orig.phar';
if (!file_exists($srcPhar)) {
    die("ERROR: $srcPhar tidak ada\n");
}
copy($srcPhar, $localPhar);
echo "OK - copied to $localPhar\n\n";

echo "=== Step 3: Extract PHAR ===\n";
$phar = new Phar($localPhar);
$phar->extractTo($extractDir, null, true);
echo "OK - extracted ke $extractDir\n\n";

echo "=== Step 4: Patch CURL options (tambahkan DNS resolve & DNS server) ===\n";
$patchedFiles = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$resolveCode = '
// === DNS FIX PATCH START ===
if (!isset($value[CURLOPT_RESOLVE])) {
    $dnsMap = ' . var_export($resolveMap, true) . ';
    $existing = isset($value[CURLOPT_RESOLVE]) ? $value[CURLOPT_RESOLVE] : [];
    $value[CURLOPT_RESOLVE] = array_merge($existing, $dnsMap);
}
if (!isset($value[CURLOPT_DNS_SERVERS])) {
    $value[CURLOPT_DNS_SERVERS] = \'' . $dnsServers . '\';
}
// === DNS FIX PATCH END ===
';

$resolveCodeSingle = '
// === DNS FIX PATCH START SINGLE ===
if ($key === CURLOPT_RESOLVE) {
    $dnsMap = ' . var_export($resolveMap, true) . ';
    if (!is_array($value)) $value = [$value];
    $value = array_merge($value, $dnsMap);
} elseif ($key === CURLOPT_DNS_SERVERS || $option === CURLOPT_DNS_SERVERS) {
    // keep existing
}
// === DNS FIX PATCH END SINGLE ===
';

$resolveCodeCurlInit = '
// === DNS FIX PATCH AFTER CURL_INIT ===
if (is_resource($__ch) || $__ch instanceof CurlHandle) {
    $dnsMap = ' . var_export($resolveMap, true) . ';
    @curl_setopt($__ch, CURLOPT_RESOLVE, $dnsMap);
    @curl_setopt($__ch, CURLOPT_DNS_SERVERS, \'' . $dnsServers . '\');
}
// unset tmp
// === DNS FIX PATCH END AFTER CURL_INIT ===
';

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext !== 'php') continue;

    $content = file_get_contents($path);
    $original = $content;
    $basename = basename($path);

    if (strpos($content, 'curl_setopt_array') !== false) {
        $pattern = '/(curl_setopt_array\s*\(\s*\$[a-zA-Z0-9_]+\s*,\s*)(array\s*\(|\\[)/';
        $content = preg_replace_callback($pattern, function($m) use ($resolveMap, $dnsServers) {
            $before = $m[1];
            $open = $m[2];
            $inject = $open . PHP_EOL . '
                // === DNS FIX PATCH INJECT ===
                CURLOPT_RESOLVE => ' . var_export($resolveMap, true) . ',' . PHP_EOL . '
                CURLOPT_DNS_SERVERS => \'' . $dnsServers . '\',' . PHP_EOL . '
                // === DNS FIX PATCH END ===
                ';
            return $before . $inject;
        }, $content);
    }

    if (strpos($content, 'function exec($ch, $options = null') !== false
        || strpos($content, 'curl_setopt($ch, CURLOPT') !== false
        || basename($path) === 'CurlDownloader.php'
        || basename($path) === 'HttpDownloader.php'
        || basename($path) === 'RemoteFilesystem.php') {

        if (strpos($content, 'CURLOPT_RESOLVE') === false) {
            $content = preg_replace(
                '/(curl_setopt_array\s*\([^,]+,\s*)(\s*array\s*\()/',
                '${1}' . PHP_EOL . '
            // === DNS FIX PATCH ===
            $__dns_resolve = ' . var_export($resolveMap, true) . ';
            $__dns_servers = \'' . $dnsServers . '\';
            if (is_array($2) || strpos("$2", "array") !== false || strpos("$2", "[") !== false) {}
            // === END DNS FIX ===
            ' . '${1}',
                $content
            );

            $pattern = '/(curl_setopt_array\s*\(\s*(\$[a-zA-Z0-9_]+)\s*,\s*)(array\s*\(|\\[)(.*?)(\\]\\)|\\);)/s';
            if (preg_match_all($pattern, $content, $mm, PREG_OFFSET_CAPTURE)) {
                for ($i = count($mm[0]) - 1; $i >= 0; $i--) {
                    $fullMatch = $mm[0][$i][0];
                    $fullOffset = $mm[0][$i][1];
                    $before = $mm[1][$i][0];
                    $arrayOpen = $mm[3][$i][0];
                    $middle = $mm[4][$i][0];
                    $arrayClose = $mm[5][$i][0];

                    if (strpos($middle, 'CURLOPT_RESOLVE') !== false) continue;

                    $injectNewLine = PHP_EOL . '
                CURLOPT_RESOLVE => ' . var_export($resolveMap, true) . ',' . PHP_EOL . '
                CURLOPT_DNS_SERVERS => \'' . $dnsServers . '\',' . PHP_EOL . '
            ';
                    $newArrayPart = $before . $arrayOpen . $injectNewLine . $middle . $arrayClose;
                    $content = substr_replace($content, $newArrayPart, $fullOffset, strlen($fullMatch));
                }
            }

            if (strpos($content, 'CURLOPT_RESOLVE') === false) {
                $content = preg_replace_callback(
                    '/(curl_setopt\s*\(\s*\$([a-zA-Z0-9_]+)\s*,\s*CURLOPT_[A-Z0-9_]+\s*,)/',
                    function($m) use ($resolveMap, $dnsServers) {
                        static $injectedPerHandle = [];
                        $handleName = $m[2];
                        if (isset($injectedPerHandle[$handleName])) {
                            return $m[0];
                        }
                        $injectedPerHandle[$handleName] = true;
                        $pre = PHP_EOL . '
            // === DNS FIX PATCH (before first setopt) ===
            @curl_setopt($' . $handleName . ', CURLOPT_RESOLVE, ' . var_export($resolveMap, true) . ');' . PHP_EOL . '
            @curl_setopt($' . $handleName . ', CURLOPT_DNS_SERVERS, \'' . $dnsServers . '\');' . PHP_EOL . '
            ';
                        return $pre . $m[0];
                    },
                    $content,
                    1
                );
            }
        }
    }

    if (strpos($content, 'CURLOPT_RESOLVE') === false && (
        strpos($content, 'curl_multi_init') !== false ||
        strpos($content, 'curl_copy_handle') !== false ||
        strpos($basename, 'Curl') !== false
    )) {
        $content = preg_replace_callback(
            '/(\$([a-zA-Z0-9_]+)\s*=\s*curl_init\s*\()/',
            function($m) use ($resolveMap, $dnsServers) {
                $handleVar = '$' . $m[2];
                $after = $m[0];
                $post = PHP_EOL . '
// === DNS FIX POST CURL_INIT ===
@curl_setopt(' . $handleVar . ', CURLOPT_RESOLVE, ' . var_export($resolveMap, true) . ');' . PHP_EOL . '
@curl_setopt(' . $handleVar . ', CURLOPT_DNS_SERVERS, \'' . $dnsServers . '\');' . PHP_EOL . '
// === END DNS FIX POST ===
';
                return $after . $post;
            },
            $content
        );
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
        $patchedFiles++;
        echo "  [PATCHED] " . substr($path, strlen($extractDir) + 1) . "\n";
    }
}

echo "\nTotal file di-patch: $patchedFiles\n";

if ($patchedFiles === 0) {
    echo "\nWARNING: TIDAK ADA FILE YANG DI-PATCH! Mencoba pendekatan brute force...\n";

    $iterator2 = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator2 as $file) {
        if ($file->isDir()) continue;
        $path = $file->getPathname();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext !== 'php') continue;
        $content = file_get_contents($path);
        $orig = $content;

        if (strpos($content, 'curl_init') !== false && strpos($content, 'CURLOPT_RESOLVE') === false) {
            $content = preg_replace_callback(
                '/(\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*curl_init\s*(\([^;]*\));)/',
                function($m) use ($resolveMap, $dnsServers) {
                    $full = $m[0];
                    $varName = '$' . $m[2];
                    return $full . PHP_EOL . '
                        @curl_setopt(' . $varName . ', CURLOPT_RESOLVE, ' . var_export($resolveMap, true) . ');' . PHP_EOL . '
                        @curl_setopt(' . $varName . ', CURLOPT_DNS_SERVERS, \'' . $dnsServers . '\');' . PHP_EOL;
                },
                $content
            );
        }

        if (strpos($content, 'curl_setopt_array') !== false && strpos($content, 'CURLOPT_RESOLVE') === false) {
            $content = preg_replace_callback(
                '/(curl_setopt_array\s*\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*,\s*)(array\s*\(|\\[)/i',
                function($m) use ($resolveMap, $dnsServers) {
                    return $m[0] . PHP_EOL . '
                        /* DNS PATCH - inject before array */
                        CURLOPT_RESOLVE => ' . var_export($resolveMap, true) . ',' . PHP_EOL . '
                        CURLOPT_DNS_SERVERS => \'' . $dnsServers . '\',' . PHP_EOL;
                },
                $content,
                -1,
                $cnt
            );
        }

        if ($content !== $orig) {
            file_put_contents($path, $content);
            $patchedFiles++;
            echo "  [BF-PATCHED] " . substr($path, strlen($extractDir) + 1) . "\n";
        }
    }
    echo "Total brute-force patched: $patchedFiles\n";
}

echo "\n=== Step 5: Re-pack PHAR ===\n";
if (file_exists($patchedPhar)) unlink($patchedPhar);

$stubFile = $extractDir . '\\.phar\\stub.php';
if (!file_exists($stubFile)) {
    $stubContent = <<<'STUB'
#!/usr/bin/env php
<?php
/**
 * Composer DNS Safe - Patched for DNS resolve fix
 */
$web = 'https://getcomposer.org/';
Phar::mapPhar('composer.phar');
require 'phar://composer.phar/bin/composer';
__HALT_COMPILER();
STUB;
    @mkdir(dirname($stubFile), 0777, true);
    file_put_contents($stubFile, $stubContent);
}

$newPhar = new Phar($patchedPhar, 0, 'composer.phar');
$newPhar->startBuffering();
$newPhar->buildFromDirectory($extractDir);

if (file_exists($extractDir . '\\bin\\composer')) {
    $stub = "#!/usr/bin/env php\n<?php\nPhar::mapPhar('composer.phar');\nrequire 'phar://composer.phar/bin/composer';\n__HALT_COMPILER();\n";
} elseif (file_exists($extractDir . '\\src\\bootstrap.php')) {
    $stub = file_get_contents($stubFile);
} else {
    $rootFiles = glob($extractDir . '\\*.php');
    $stub = "#!/usr/bin/env php\n<?php\nPhar::mapPhar('composer.phar');\n";
    foreach (['bin/composer', 'bin/composer.php', 'composer.php', 'bootstrap.php'] as $try) {
        if (file_exists($extractDir . DIRECTORY_SEPARATOR . $try)) {
            $stub .= "require 'phar://composer.phar/{$try}';\n";
            break;
        }
    }
    $stub .= "__HALT_COMPILER();\n";
}

$newPhar->setStub($stub);
$newPhar->stopBuffering();
echo "OK - PHAR dibuat: $patchedPhar\n";
echo "Stub yang digunakan:\n" . implode("\n", array_slice(explode("\n", $stub), 0, 5)) . "\n...\n\n";

echo "=== Step 6: Test keberadaan file ===\n";
clearstatcache();
echo "Size patched phar: " . (file_exists($patchedPhar) ? filesize($patchedPhar) : 0) . " bytes\n";

echo "\n=== Step 7: Cleanup ===\n";
// biarkan file extract untuk debugging jika perlu, tapi hapus local copy
@unlink($localPhar);
echo "OK\n\n";

echo "======================================\n";
echo "SELESAI. Jalankan composer dengan perintah:\n";
echo "  php {$patchedPhar} install\n";
echo "======================================\n";

function cleanDir($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        if ($f->isDir()) @rmdir($f->getPathname());
        else @unlink($f->getPathname());
    }
}

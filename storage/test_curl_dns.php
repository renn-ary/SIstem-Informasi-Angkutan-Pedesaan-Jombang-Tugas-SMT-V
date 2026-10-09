<?php
$resolveMap = [
    'codeload.github.com:443:20.205.243.165',
    'api.github.com:443:20.205.243.168',
    'github.com:443:20.205.243.166',
    'objects.githubusercontent.com:443:20.205.243.164',
    'raw.githubusercontent.com:443:20.205.243.133',
];

$testUrl = 'https://codeload.github.com/php-fig/container/legacy.zip/c71ecc56dfe541dbd90c5360474fbc405f8d5963';

echo "=== Test 1: TANPA CURLOPT_RESOLVE (cek DNS default) ===" . PHP_EOL;
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$r = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
if ($r === false) {
    echo "GAGAL: {$err}" . PHP_EOL;
} else {
    echo "BERHASIL! HTTP Code: {$code}" . PHP_EOL;
}

echo PHP_EOL . "=== Test 2: DENGAN CURLOPT_RESOLVE (paksa IP GitHub) ===" . PHP_EOL;
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_RESOLVE, $resolveMap);
$r = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
if ($r === false) {
    echo "GAGAL: {$err}" . PHP_EOL;
} else {
    echo "BERHASIL! HTTP Code: {$code}, Size: " . strlen($r) . " bytes" . PHP_EOL;
}

echo PHP_EOL . "=== Test 3: DENGAN CURLOPT_DNS_SERVERS (paksa DNS 8.8.8.8) ===" . PHP_EOL;
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_DNS_SERVERS, '8.8.8.8,8.8.4.4');
$r = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
if ($r === false) {
    echo "GAGAL: {$err}" . PHP_EOL;
} else {
    echo "BERHASIL! HTTP Code: {$code}" . PHP_EOL;
}

echo PHP_EOL . "Selesai." . PHP_EOL;

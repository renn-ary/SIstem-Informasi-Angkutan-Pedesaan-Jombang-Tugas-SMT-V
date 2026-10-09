<?php
ini_set('error_log', __DIR__ . '/php_errors.log');
echo "=== GET /register response via PHP Curl (HTTP 1.1) ===\n";
$ch = curl_init('http://127.0.0.1:8000/register');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => false,
    CURLOPT_HEADER => 1,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_USERAGENT => 'TraeBrowserTest/1.0',
    CURLOPT_HTTPGET => true,
]);
$r = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);
if ($err) { echo "CURL ERR: $err\n"; exit(1); }
echo "HTTP status: {$info['http_code']}\n";
echo "Content-Type: {$info['content_type']}\n";
echo "Size: {$info['size_download']} bytes\n\n";
$headers = substr($r, 0, $info['header_size']);
$body = substr($r, $info['header_size']);
echo "--- HEADERS ---\n";
foreach (explode("\r\n", $headers) as $h) { echo "  $h\n"; }
echo "\n--- BODY (first 2500 chars) ---\n";
echo substr($body, 0, 2500);
echo "\n--- END ---\n";

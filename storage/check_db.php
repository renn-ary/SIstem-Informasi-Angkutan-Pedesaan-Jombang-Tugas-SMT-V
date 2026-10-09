<?php
ini_set('error_log', __DIR__ . '/php_errors.log');
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "=== Check MySQL + DB + Tables ===\n";
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "MySQL: CONNECTED (server up)\n";
    $stmt = $pdo->query("SHOW DATABASES LIKE 'angkutan_jombang'");
    if ($stmt->rowCount() > 0) {
        echo "DB angkutan_jombang: EXISTS\n";
        $pdo->exec("USE angkutan_jombang");
        foreach (['users','sessions','cache','angkutans','rutes','jadwals','tarifs','titik_pemberhentians','verifikasis','jobs','migrations','password_reset_tokens','personal_access_tokens'] as $t) {
            $s = $pdo->query("SHOW TABLES LIKE '$t'");
            echo "  Table '$t': " . ($s->rowCount() > 0 ? "YES" : "NO") . "\n";
        }
    } else {
        echo "DB angkutan_jombang: MISSING\n";
    }
} catch (Throwable $e) {
    echo "MYSQL / DB ERROR: " . $e->getMessage() . "\n";
    echo "=== Auto Fix: Ganti driver session & cache ke FILE (tidak butuh DB) ===\n";
    // Simpan backup env
    $envPath = __DIR__ . '/../.env';
    $env = file_get_contents($envPath);
    $env = preg_replace('/^SESSION_DRIVER\s*=\s*.*$/m', 'SESSION_DRIVER=file', $env);
    $env = preg_replace('/^CACHE_STORE\s*=\s*.*$/m', 'CACHE_STORE=file', $env);
    file_put_contents($envPath, $env);
    echo "Diupdate: SESSION_DRIVER=file, CACHE_STORE=file (restart artisan required)\n";
    echo "Kemudian Anda harus menjalankan: (1) Nyalakan MySQL via Laragon/XAMPP control panel  (2) Buat database `angkutan_jombang` jika belum ada  (3) php artisan migrate\n";
}

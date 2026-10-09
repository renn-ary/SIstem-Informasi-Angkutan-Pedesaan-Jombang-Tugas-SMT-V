<?php

use Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$environment = Dotenv::createImmutable(dirname(__DIR__, 2))->load();
$connection = new mysqli(
    $environment['DB_HOST'],
    $environment['DB_USERNAME'],
    $environment['DB_PASSWORD'],
    'mysql',
    (int) $environment['DB_PORT'],
);

$connection->query('CREATE DATABASE IF NOT EXISTS angkutan_jombang_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
fwrite(STDOUT, "Database test angkutan_jombang_test siap.\n");

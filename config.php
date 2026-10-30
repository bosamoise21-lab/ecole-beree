<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$localConfigPath = __DIR__ . '/config.local.php';
$localConfig = [];

if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (!is_array($loadedConfig)) {
        throw new RuntimeException('config.local.php doit retourner un tableau de configuration.');
    }
    $localConfig = $loadedConfig;
}

$dbHost = (string) ($localConfig['host'] ?? (getenv('DB_HOST') ?: '127.0.0.1'));
$dbPort = (int) ($localConfig['port'] ?? (getenv('DB_PORT') ?: 3306));
$dbName = (string) ($localConfig['name'] ?? (getenv('DB_NAME') ?: 'ecole'));
$dbUser = (string) ($localConfig['user'] ?? (getenv('DB_USER') ?: ''));
$dbPass = (string) ($localConfig['pass'] ?? (getenv('DB_PASS') ?: ''));

if ($dbUser === '') {
    throw new RuntimeException('Configurez DB_USER ou renseignez config.local.php.');
}

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);

function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    return $pdo;
}

function isAdminConnected(): bool
{
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin(): void
{
    if (!isAdminConnected()) {
        header('Location: login.php');
        exit;
    }
}

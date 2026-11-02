<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (getenv('APP_ENV') === 'production')
            || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
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

function currentAdminRole(): ?string
{
    $role = $_SESSION['admin_role'] ?? null;
    return is_string($role) ? $role : null;
}

function isAdminRole(): bool
{
    return currentAdminRole() === 'admin';
}

function canAccessAdminPage(string $role, string $page): bool
{
    if ($role === 'admin') {
        return true;
    }

    if ($role !== 'gestionnaire') {
        return false;
    }

    return in_array($page, [
        'dashboard.php',
        'admissions.php',
        'messages.php',
        'add_student.php',
        'classes.php',
        'notes.php',
        'attendance.php',
        'timetable.php',
        'student_profile.php',
    ], true);
}

function requireAdmin(): void
{
    if (!isAdminConnected() || empty($_SESSION['admin_id'])) {
        $_SESSION = [];
        header('Location: login.php');
        exit;
    }

    $stmt = getDb()->prepare('SELECT username, nom, role FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $_SESSION['admin_id']]);
    $account = $stmt->fetch();

    if (!$account || !in_array($account['role'], ['admin', 'gestionnaire'], true)) {
        $_SESSION = [];
        header('Location: login.php');
        exit;
    }

    $_SESSION['admin_username'] = $account['username'];
    $_SESSION['admin_nom'] = $account['nom'] ?? $account['username'];
    $_SESSION['admin_role'] = $account['role'];

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    $page = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (!canAccessAdminPage($account['role'], $page)) {
        http_response_code(403);
        echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Accès refusé</title>';
        echo '<body><h1>Accès réservé</h1><p>Votre rôle ne vous permet pas d’ouvrir cette page.</p>';
        echo '<a href="dashboard.php">Retour au tableau de bord</a></body></html>';
        exit;
    }
}

function csrfToken(string $scope): string
{
    $key = 'csrf_' . $scope;
    if (!isset($_SESSION[$key]) || !is_string($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION[$key];
}

function requireValidCsrfToken(string $scope): void
{
    if (!isValidCsrfToken($scope, $_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('La session du formulaire a expiré. Rechargez la page puis réessayez.');
    }
}

function isValidCsrfToken(string $scope, mixed $token): bool
{
    $sessionToken = $_SESSION['csrf_' . $scope] ?? null;

    return is_string($token)
        && is_string($sessionToken)
        && hash_equals($sessionToken, $token);
}

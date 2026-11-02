<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function findMysqldump(): string
{
    $configuredPath = getenv('MYSQLDUMP_PATH');
    $candidates = [];

    if (is_string($configuredPath) && $configuredPath !== '') {
        $candidates[] = $configuredPath;
    }

    $candidates[] = 'mysqldump';
    if (DIRECTORY_SEPARATOR === '\\') {
        $candidates[] = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    }

    foreach ($candidates as $candidate) {
        if ($candidate !== 'mysqldump' && !is_file($candidate)) {
            continue;
        }

        $process = @proc_open(
            [$candidate, '--version'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true]
        );
        if (!is_resource($process)) {
            continue;
        }

        fclose($pipes[0]);
        $version = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode === 0 && is_string($version) && stripos($version . $error, 'mysqldump') !== false) {
            return $candidate;
        }
    }

    throw new RuntimeException('mysqldump est introuvable. Installez MySQL/MariaDB ou définissez MYSQLDUMP_PATH.');
}

$configuredBackupDirectory = getenv('APP_BACKUP_DIR');
$defaultBackupBase = DIRECTORY_SEPARATOR === '\\'
    ? (getenv('LOCALAPPDATA') ?: getenv('HOME'))
    : (getenv('XDG_STATE_HOME') ?: (rtrim((string) getenv('HOME'), '/\\') . '/.local/state'));
$backupDirectory = $argv[1]
    ?? ((is_string($configuredBackupDirectory) && $configuredBackupDirectory !== '')
        ? $configuredBackupDirectory
        : rtrim((string) $defaultBackupBase, '/\\')
            . DIRECTORY_SEPARATOR
            . 'ecole-beree'
            . DIRECTORY_SEPARATOR
            . 'backups');
if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0700, true) && !is_dir($backupDirectory)) {
    throw new RuntimeException('Impossible de créer le dossier de sauvegarde.');
}

$backupDirectory = realpath($backupDirectory);
if ($backupDirectory === false || !is_writable($backupDirectory)) {
    throw new RuntimeException('Le dossier de sauvegarde est inaccessible en écriture.');
}

$backupPath = $backupDirectory
    . DIRECTORY_SEPARATOR
    . DB_NAME
    . '-'
    . date('Ymd-His')
    . '.sql';
$checksumPath = $backupPath . '.sha256';

if (file_exists($backupPath) || file_exists($checksumPath)) {
    throw new RuntimeException('Un fichier de sauvegarde portant ce nom existe déjà.');
}

$mysqldump = findMysqldump();
$command = [
    $mysqldump,
    '--host=' . DB_HOST,
    '--port=' . DB_PORT,
    '--user=' . DB_USER,
    '--single-transaction',
    '--routines',
    '--triggers',
    '--events',
    '--hex-blob',
    '--databases',
    DB_NAME,
    '--result-file=' . $backupPath,
];

$previousPassword = getenv('MYSQL_PWD');
putenv('MYSQL_PWD=' . DB_PASS);
$process = proc_open(
    $command,
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    null,
    null,
    ['bypass_shell' => true]
);

if (!is_resource($process)) {
    if ($previousPassword === false) {
        putenv('MYSQL_PWD');
    } else {
        putenv('MYSQL_PWD=' . $previousPassword);
    }
    throw new RuntimeException('Impossible de démarrer mysqldump.');
}

fclose($pipes[0]);
$output = stream_get_contents($pipes[1]);
$error = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($previousPassword === false) {
    putenv('MYSQL_PWD');
} else {
    putenv('MYSQL_PWD=' . $previousPassword);
}

if ($exitCode !== 0) {
    @unlink($backupPath);
    error_log('Échec mysqldump (' . $exitCode . '): ' . trim((string) $error));
    throw new RuntimeException('La sauvegarde MySQL a échoué. Vérifiez le journal PHP et la connexion à la base.');
}

$requiredTables = ['admins', 'classes', 'eleves', 'inscriptions', 'paiements'];
if (filesize($backupPath) < 1024) {
    @unlink($backupPath);
    throw new RuntimeException('La sauvegarde créée est vide ou incomplète.');
}

$backupHandle = fopen($backupPath, 'rb');
if ($backupHandle === false) {
    @unlink($backupPath);
    throw new RuntimeException('Impossible de vérifier le contenu de la sauvegarde.');
}

$remainingTables = array_fill_keys($requiredTables, true);
while (($line = fgets($backupHandle)) !== false && $remainingTables !== []) {
    foreach (array_keys($remainingTables) as $table) {
        if (str_contains($line, 'CREATE TABLE `' . $table . '`')) {
            unset($remainingTables[$table]);
        }
    }
}
fclose($backupHandle);

if ($remainingTables !== []) {
    @unlink($backupPath);
    throw new RuntimeException(
        'La sauvegarde ne contient pas toutes les tables attendues : '
        . implode(', ', array_keys($remainingTables))
        . '.'
    );
}

$hash = hash_file('sha256', $backupPath);
if (!is_string($hash) || file_put_contents($checksumPath, $hash . '  ' . basename($backupPath) . PHP_EOL, LOCK_EX) === false) {
    @unlink($backupPath);
    @unlink($checksumPath);
    throw new RuntimeException('Impossible de vérifier ou d’enregistrer la somme de contrôle de la sauvegarde.');
}

@chmod($backupPath, 0600);
@chmod($checksumPath, 0600);

echo 'Sauvegarde vérifiée : ' . $backupPath . PHP_EOL;
echo 'SHA-256 : ' . $hash . PHP_EOL;

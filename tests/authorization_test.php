<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$managerPages = [
    'dashboard.php',
    'admissions.php',
    'messages.php',
    'add_student.php',
    'classes.php',
    'notes.php',
    'attendance.php',
    'timetable.php',
    'student_profile.php',
];

$restrictedPages = [
    'manage_data.php',
    'teachers.php',
    'fees.php',
    'payroll.php',
    'users.php',
];

$tests = [];
foreach ($managerPages as $page) {
    $tests['manager can access ' . $page] = canAccessAdminPage('gestionnaire', $page);
}
foreach ($restrictedPages as $page) {
    $tests['manager cannot access ' . $page] = !canAccessAdminPage('gestionnaire', $page);
}
$tests['admin can access account management'] = canAccessAdminPage('admin', 'users.php');
$tests['unknown role is denied'] = !canAccessAdminPage('unknown', 'dashboard.php');
$tests['unknown page is denied to manager'] = !canAccessAdminPage('gestionnaire', 'unknown.php');

$token = csrfToken('authorization_test');
$tests['generated CSRF token is accepted'] = isValidCsrfToken('authorization_test', $token);
$tests['incorrect CSRF token is rejected'] = !isValidCsrfToken('authorization_test', 'invalid');
$tests['non-string CSRF token is rejected'] = !isValidCsrfToken('authorization_test', []);

$failed = [];
foreach ($tests as $name => $passed) {
    if (!$passed) {
        $failed[] = $name;
    }
}

if ($failed !== []) {
    fwrite(STDERR, 'ÉCHEC: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'OK: ' . count($tests) . ' tests de rôles et CSRF réussis.' . PHP_EOL;

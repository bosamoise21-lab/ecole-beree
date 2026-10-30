<?php

declare(strict_types=1);

require_once __DIR__ . '/../payroll_helpers.php';

$tests = [
    'positive integer salary' => isValidPayrollAmount('850000'),
    'positive decimal salary' => isValidPayrollAmount('850000.50'),
    'zero salary rejected' => !isValidPayrollAmount('0'),
    'negative salary rejected' => !isValidPayrollAmount('-50'),
    'nonnumeric salary rejected' => !isValidPayrollAmount('abc'),
    'excess precision rejected' => !isValidPayrollAmount('1200.999'),
    'salary above decimal capacity rejected' => !isValidPayrollAmount('10000000000'),
    'valid payroll period normalized' => normalizePayrollPeriod('2026-10') === '2026-10-01',
    'invalid payroll month rejected' => normalizePayrollPeriod('2026-13') === null,
    'malformed payroll period rejected' => normalizePayrollPeriod('2026-1') === null,
    'valid payment date accepted' => isValidPayrollPaymentDate('2026-10-31'),
    'impossible payment date rejected' => !isValidPayrollPaymentDate('2026-02-30'),
];

$failed = [];
foreach ($tests as $name => $passed) {
    if (!$passed) {
        $failed[] = $name;
    }
}

if ($failed !== []) {
    fwrite(STDERR, "ÉCHEC: " . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'OK: ' . count($tests) . " tests de validation de paie réussis." . PHP_EOL;

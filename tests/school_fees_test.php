<?php

declare(strict_types=1);

require_once __DIR__ . '/../school_fees_helpers.php';

$tests = [
    'registration component has no trimester' => isValidSchoolFeeComponent('inscription', 0),
    'other component has no trimester' => isValidSchoolFeeComponent('autre', 0),
    'first trimester accepted' => isValidSchoolFeeComponent('trimestre', 1),
    'third trimester accepted' => isValidSchoolFeeComponent('trimestre', 3),
    'missing trimester rejected' => !isValidSchoolFeeComponent('trimestre', 0),
    'fourth trimester rejected' => !isValidSchoolFeeComponent('trimestre', 4),
    'unknown fee type rejected' => !isValidSchoolFeeComponent('unknown', 0),
    'positive fee amount accepted' => isValidSchoolFeeAmount('12500.50'),
    'zero fee amount rejected' => !isValidSchoolFeeAmount('0'),
    'negative fee amount rejected' => !isValidSchoolFeeAmount('-5'),
    'excess precision rejected' => !isValidSchoolFeeAmount('1200.999'),
    'amount beyond database capacity rejected' => !isValidSchoolFeeAmount('100000000'),
    'decimal amount converts to cents exactly' => schoolFeeAmountToCents('12500.50') === 1250050,
    'integer amount converts to cents' => schoolFeeAmountToCents('900') === 90000,
    'remaining balance cannot be negative' => schoolFeeRemainingCents(10000, 12000) === 0,
    'positive remaining balance calculated' => schoolFeeRemainingCents(10000, 3500) === 6500,
    'trimester label is explicit' => schoolFeeComponentLabel('trimestre', 2) === 'Trimestre 2',
];

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

echo 'OK: ' . count($tests) . ' tests de suivi des frais scolaires réussis.' . PHP_EOL;

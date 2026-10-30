<?php

declare(strict_types=1);

function isValidPayrollAmount(string $amount): bool
{
    if (!preg_match('/\A(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?\z/', $amount)) {
        return false;
    }

    return (float) $amount > 0;
}

function normalizePayrollPeriod(string $period): ?string
{
    if (!preg_match('/\A([0-9]{4})-(0[1-9]|1[0-2])\z/', $period, $matches)) {
        return null;
    }

    $year = (int) $matches[1];
    $month = (int) $matches[2];

    return checkdate($month, 1, $year) ? sprintf('%04d-%02d-01', $year, $month) : null;
}

function isValidPayrollPaymentDate(string $date): bool
{
    if (!preg_match('/\A([0-9]{4})-(0[1-9]|1[0-2])-([0-9]{2})\z/', $date, $matches)) {
        return false;
    }

    return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
}

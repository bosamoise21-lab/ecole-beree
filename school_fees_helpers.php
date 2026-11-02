<?php

declare(strict_types=1);

function isValidSchoolFeeType(string $type): bool
{
    return in_array($type, ['inscription', 'trimestre', 'frais', 'autre'], true);
}

function isValidSchoolFeeComponent(string $type, int $trimester): bool
{
    if (!isValidSchoolFeeType($type)) {
        return false;
    }

    return $type === 'trimestre'
        ? $trimester >= 1 && $trimester <= 3
        : $trimester === 0;
}

function isValidSchoolFeeAmount(string $amount): bool
{
    if (!preg_match('/\A(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?\z/', $amount)) {
        return false;
    }

    return (float) $amount > 0;
}

function schoolFeeAmountToCents(string $amount): int
{
    if (!preg_match('/\A([0-9]+)(?:\.([0-9]{1,2}))?\z/', $amount, $matches)) {
        throw new InvalidArgumentException('Montant de frais scolaires invalide.');
    }

    return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
}

function schoolFeeRemainingCents(int $dueCents, int $paidCents): int
{
    return max(0, $dueCents - $paidCents);
}

function schoolFeeComponentLabel(string $type, int $trimester): string
{
    if ($type === 'trimestre' && $trimester === 0) {
        return 'Trimestre à préciser';
    }

    $labels = [
        'inscription' => 'Inscription',
        'trimestre' => 'Trimestre ' . $trimester,
        'frais' => 'Frais',
        'autre' => 'Autre',
    ];

    return $labels[$type] ?? 'Type inconnu';
}

function formatSchoolFeeCents(int $amountCents): string
{
    return number_format($amountCents / 100, 2, ',', ' ') . ' FCFA';
}

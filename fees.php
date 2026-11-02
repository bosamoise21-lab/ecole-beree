<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/payroll_helpers.php';
require_once __DIR__ . '/school_fees_helpers.php';
requireAdmin();

$pdo = getDb();
$csrf = csrfToken('fees');
$message = (string) ($_SESSION['fees_message'] ?? '');
$messageType = (string) ($_SESSION['fees_message_type'] ?? 'success');
unset($_SESSION['fees_message'], $_SESSION['fees_message_type']);
$error = '';
$feeTypes = [
    'inscription' => 'Inscription',
    'trimestre' => 'Trimestre',
    'frais' => 'Frais',
    'autre' => 'Autre',
];
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$feeLabel = static fn (string $type, int $trimester): string => schoolFeeComponentLabel($type, $trimester);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken('fees');
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'save_tariff') {
            $classId = filter_var($_POST['classe_id'] ?? null, FILTER_VALIDATE_INT);
            $type = (string) ($_POST['type_paiement'] ?? '');
            $trimesterValue = filter_var($_POST['trimestre'] ?? 0, FILTER_VALIDATE_INT);
            $trimester = $trimesterValue === false ? -1 : $trimesterValue;
            $amount = trim((string) ($_POST['montant'] ?? ''));

            if ($classId === false || $classId <= 0) {
                throw new InvalidArgumentException('Choisissez une classe valide.');
            }
            if (!isValidSchoolFeeComponent($type, $trimester)) {
                throw new InvalidArgumentException('Choisissez un type de frais et un trimestre valides.');
            }
            if (!isValidSchoolFeeAmount($amount)) {
                throw new InvalidArgumentException('Le montant doit être positif et ne pas dépasser deux décimales.');
            }

            $pdo->beginTransaction();
            $classQuery = $pdo->prepare('SELECT id FROM classes WHERE id = :id');
            $classQuery->execute([':id' => $classId]);
            if (!$classQuery->fetchColumn()) {
                throw new InvalidArgumentException('La classe sélectionnée n’existe plus.');
            }

            $tariffLock = $pdo->prepare(
                'SELECT id FROM tarifs_scolaires
                 WHERE classe_id = :classe_id AND type_paiement = :type AND trimestre = :trimestre
                 FOR UPDATE'
            );
            $tariffLock->execute([
                ':classe_id' => $classId,
                ':type' => $type,
                ':trimestre' => $trimester,
            ]);
            $tariffLock->fetchColumn();

            $paidQuery = $pdo->prepare(
                'SELECT COALESCE(SUM(p.montant), 0)
                 FROM paiements p
                 INNER JOIN inscriptions i ON i.id = p.inscription_id
                 WHERE i.classe_id = :classe_id
                   AND p.type_paiement = :type
                   AND p.trimestre = :trimestre'
            );
            $paidQuery->execute([
                ':classe_id' => $classId,
                ':type' => $type,
                ':trimestre' => $trimester,
            ]);
            if (schoolFeeAmountToCents($amount) < schoolFeeAmountToCents((string) $paidQuery->fetchColumn())) {
                throw new InvalidArgumentException('Le nouveau tarif ne peut pas être inférieur aux paiements déjà enregistrés.');
            }

            $saveTariff = $pdo->prepare(
                'INSERT INTO tarifs_scolaires (classe_id, type_paiement, trimestre, montant)
                 VALUES (:classe_id, :type, :trimestre, :montant)
                 ON DUPLICATE KEY UPDATE montant = VALUES(montant)'
            );
            $saveTariff->execute([
                ':classe_id' => $classId,
                ':type' => $type,
                ':trimestre' => $trimester,
                ':montant' => $amount,
            ]);
            $pdo->commit();
            $_SESSION['fees_message'] = 'Le tarif scolaire a été enregistré.';
        } elseif ($action === 'delete_tariff') {
            $tariffId = filter_var($_POST['tarif_id'] ?? null, FILTER_VALIDATE_INT);
            if ($tariffId === false || $tariffId <= 0) {
                throw new InvalidArgumentException('Choisissez un tarif valide.');
            }

            $pdo->beginTransaction();
            $tariffQuery = $pdo->prepare(
                'SELECT id, classe_id, type_paiement, trimestre
                 FROM tarifs_scolaires
                 WHERE id = :id
                 FOR UPDATE'
            );
            $tariffQuery->execute([':id' => $tariffId]);
            $tariff = $tariffQuery->fetch();
            if (!$tariff) {
                throw new InvalidArgumentException('Ce tarif n’existe plus.');
            }

            $paidQuery = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM paiements p
                 INNER JOIN inscriptions i ON i.id = p.inscription_id
                 WHERE i.classe_id = :classe_id
                   AND p.type_paiement = :type
                   AND p.trimestre = :trimestre'
            );
            $paidQuery->execute([
                ':classe_id' => $tariff['classe_id'],
                ':type' => $tariff['type_paiement'],
                ':trimestre' => $tariff['trimestre'],
            ]);
            if ((int) $paidQuery->fetchColumn() > 0) {
                throw new InvalidArgumentException('Ce tarif ne peut pas être supprimé car des paiements lui sont déjà rattachés. Modifiez plutôt son montant.');
            }

            $deleteTariff = $pdo->prepare('DELETE FROM tarifs_scolaires WHERE id = :id');
            $deleteTariff->execute([':id' => $tariffId]);
            $pdo->commit();
            $_SESSION['fees_message'] = 'Le tarif scolaire a été supprimé.';
        } elseif ($action === 'record_payment') {
            $enrollmentId = filter_var($_POST['inscription_id'] ?? null, FILTER_VALIDATE_INT);
            $type = (string) ($_POST['type_paiement'] ?? '');
            $trimesterValue = filter_var($_POST['trimestre'] ?? 0, FILTER_VALIDATE_INT);
            $trimester = $trimesterValue === false ? -1 : $trimesterValue;
            $amount = trim((string) ($_POST['montant'] ?? ''));
            $paymentDate = trim((string) ($_POST['date_paiement'] ?? ''));
            $paymentMode = (string) ($_POST['mode_paiement'] ?? '');
            $comment = trim((string) ($_POST['commentaire'] ?? ''));
            $paymentModes = ['cash', 'banque', 'mobile_money', 'cheque'];

            if ($enrollmentId === false || $enrollmentId <= 0) {
                throw new InvalidArgumentException('Choisissez une inscription valide.');
            }
            if (!isValidSchoolFeeComponent($type, $trimester)) {
                throw new InvalidArgumentException('Choisissez un type de frais et un trimestre valides.');
            }
            if (!isValidSchoolFeeAmount($amount)) {
                throw new InvalidArgumentException('Le montant doit être positif et ne pas dépasser deux décimales.');
            }
            if (!isValidPayrollPaymentDate($paymentDate)) {
                throw new InvalidArgumentException('La date du paiement est invalide.');
            }
            if (!in_array($paymentMode, $paymentModes, true)) {
                throw new InvalidArgumentException('Choisissez un mode de paiement valide.');
            }
            if (strlen($comment) > 255) {
                throw new InvalidArgumentException('Le commentaire ne peut pas dépasser 255 caractères.');
            }

            $pdo->beginTransaction();
            $tariffQuery = $pdo->prepare(
                'SELECT i.eleve_id, t.montant
                 FROM inscriptions i
                 INNER JOIN tarifs_scolaires t ON t.classe_id = i.classe_id
                 WHERE i.id = :inscription_id
                   AND t.type_paiement = :type
                   AND t.trimestre = :trimestre
                 FOR UPDATE'
            );
            $tariffQuery->execute([
                ':inscription_id' => $enrollmentId,
                ':type' => $type,
                ':trimestre' => $trimester,
            ]);
            $tariff = $tariffQuery->fetch();
            if (!$tariff) {
                throw new InvalidArgumentException('Définissez d’abord le tarif correspondant à cette classe et à cette année scolaire.');
            }

            $paidQuery = $pdo->prepare(
                'SELECT COALESCE(SUM(montant), 0)
                 FROM paiements
                 WHERE inscription_id = :inscription_id
                   AND type_paiement = :type
                   AND trimestre = :trimestre'
            );
            $paidQuery->execute([
                ':inscription_id' => $enrollmentId,
                ':type' => $type,
                ':trimestre' => $trimester,
            ]);
            $remaining = schoolFeeRemainingCents(
                schoolFeeAmountToCents((string) $tariff['montant']),
                schoolFeeAmountToCents((string) $paidQuery->fetchColumn())
            );
            if (schoolFeeAmountToCents($amount) > $remaining) {
                throw new InvalidArgumentException('Le paiement dépasse le solde restant pour ces frais.');
            }

            $insertPayment = $pdo->prepare(
                'INSERT INTO paiements
                    (eleve_id, inscription_id, montant, type_paiement, trimestre, date_paiement, mode_paiement, commentaire)
                 VALUES
                    (:eleve_id, :inscription_id, :montant, :type, :trimestre, :date_paiement, :mode_paiement, :commentaire)'
            );
            $insertPayment->execute([
                ':eleve_id' => $tariff['eleve_id'],
                ':inscription_id' => $enrollmentId,
                ':montant' => $amount,
                ':type' => $type,
                ':trimestre' => $trimester,
                ':date_paiement' => $paymentDate,
                ':mode_paiement' => $paymentMode,
                ':commentaire' => $comment !== '' ? $comment : null,
            ]);
            $pdo->commit();
            $_SESSION['fees_message'] = 'Le paiement a été enregistré.';
        } elseif ($action === 'assign_payment') {
            $paymentId = filter_var($_POST['paiement_id'] ?? null, FILTER_VALIDATE_INT);
            $enrollmentId = filter_var($_POST['inscription_id'] ?? null, FILTER_VALIDATE_INT);
            $trimesterValue = filter_var($_POST['trimestre'] ?? 0, FILTER_VALIDATE_INT);
            $trimester = $trimesterValue === false ? -1 : $trimesterValue;

            if ($paymentId === false || $paymentId <= 0 || $enrollmentId === false || $enrollmentId <= 0) {
                throw new InvalidArgumentException('Sélectionnez un paiement et une inscription valides.');
            }

            $pdo->beginTransaction();
            $legacyQuery = $pdo->prepare(
                'SELECT eleve_id, type_paiement, montant
                 FROM paiements
                 WHERE id = :id
                   AND (inscription_id IS NULL OR (type_paiement = "trimestre" AND trimestre = 0))
                 FOR UPDATE'
            );
            $legacyQuery->execute([':id' => $paymentId]);
            $legacyPayment = $legacyQuery->fetch();
            if (!$legacyPayment) {
                throw new InvalidArgumentException('Ce paiement a déjà été affecté ou n’existe plus.');
            }
            if (!isValidSchoolFeeComponent((string) $legacyPayment['type_paiement'], $trimester)) {
                throw new InvalidArgumentException('Indiquez le trimestre exact pour ce paiement.');
            }

            $enrollmentQuery = $pdo->prepare(
                'SELECT classe_id
                 FROM inscriptions
                 WHERE id = :id AND eleve_id = :eleve_id'
            );
            $enrollmentQuery->execute([
                ':id' => $enrollmentId,
                ':eleve_id' => $legacyPayment['eleve_id'],
            ]);
            $classId = $enrollmentQuery->fetchColumn();
            if (!$classId) {
                throw new InvalidArgumentException('L’inscription choisie ne correspond pas à cet élève.');
            }

            $tariffQuery = $pdo->prepare(
                'SELECT id FROM tarifs_scolaires
                 WHERE classe_id = :classe_id AND type_paiement = :type AND trimestre = :trimestre
                 FOR UPDATE'
            );
            $tariffQuery->execute([
                ':classe_id' => $classId,
                ':type' => $legacyPayment['type_paiement'],
                ':trimestre' => $trimester,
            ]);
            if (!$tariffQuery->fetchColumn()) {
                throw new InvalidArgumentException('Définissez le tarif correspondant avant d’affecter ce paiement.');
            }

            $assignPayment = $pdo->prepare(
                'UPDATE paiements
                 SET inscription_id = :inscription_id, trimestre = :trimestre
                 WHERE id = :id
                   AND (inscription_id IS NULL OR (type_paiement = "trimestre" AND trimestre = 0))'
            );
            $assignPayment->execute([
                ':inscription_id' => $enrollmentId,
                ':trimestre' => $trimester,
                ':id' => $paymentId,
            ]);
            if ($assignPayment->rowCount() !== 1) {
                throw new InvalidArgumentException('Le paiement n’a pas pu être affecté.');
            }
            $pdo->commit();
            $_SESSION['fees_message'] = 'Le paiement historique a été affecté à son année scolaire.';
        } else {
            throw new InvalidArgumentException('Action de gestion des frais inconnue.');
        }

        $_SESSION['fees_message_type'] = 'success';
        header('Location: fees.php');
        exit;
    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $exception->getMessage();
        $messageType = 'error';
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Échec du suivi des frais scolaires: ' . $exception->getMessage());
        $error = 'Opération impossible. Vérifiez les données et la configuration de la base.';
        $messageType = 'error';
    }
}

$classes = $pdo->query(
    'SELECT id, nom, niveau, annee_scolaire
     FROM classes
     ORDER BY annee_scolaire DESC, niveau, nom'
)->fetchAll();
$enrollments = $pdo->query(
    'SELECT i.id, i.eleve_id, i.classe_id, i.statut,
            e.matricule, e.nom, e.prenom, c.nom AS classe_nom, c.niveau, c.annee_scolaire
     FROM inscriptions i
     INNER JOIN eleves e ON e.id = i.eleve_id
     INNER JOIN classes c ON c.id = i.classe_id
     ORDER BY c.annee_scolaire DESC, e.nom, e.prenom'
)->fetchAll();
$enrollmentsByStudent = [];
foreach ($enrollments as $enrollment) {
    $enrollmentsByStudent[(int) $enrollment['eleve_id']][] = $enrollment;
}

$tariffs = $pdo->query(
    'SELECT t.id, t.classe_id, t.type_paiement, t.trimestre, t.montant,
            c.nom AS classe_nom, c.niveau, c.annee_scolaire
     FROM tarifs_scolaires t
     INNER JOIN classes c ON c.id = t.classe_id
     ORDER BY c.annee_scolaire DESC, c.niveau, c.nom, t.type_paiement, t.trimestre'
)->fetchAll();
$tariffsByClass = [];
foreach ($tariffs as $tariff) {
    $tariffsByClass[(int) $tariff['classe_id']][] = $tariff;
}

$paidByComponent = [];
$paidTotals = $pdo->query(
    'SELECT inscription_id, type_paiement, trimestre, SUM(montant) AS total
     FROM paiements
     WHERE inscription_id IS NOT NULL
     GROUP BY inscription_id, type_paiement, trimestre'
)->fetchAll();
foreach ($paidTotals as $paidTotal) {
    $key = (int) $paidTotal['inscription_id'] . ':' . $paidTotal['type_paiement'] . ':' . (int) $paidTotal['trimestre'];
    $paidByComponent[$key] = schoolFeeAmountToCents((string) $paidTotal['total']);
}

$summaryRows = [];
$detailRows = [];
$totalDue = 0;
$totalPaid = 0;
$totalRemaining = 0;
foreach ($enrollments as $enrollment) {
    $studentExpected = 0;
    $studentPaid = 0;
    $studentRemaining = 0;
    $studentTariffs = $tariffsByClass[(int) $enrollment['classe_id']] ?? [];

    foreach ($studentTariffs as $tariff) {
        $key = (int) $enrollment['id'] . ':' . $tariff['type_paiement'] . ':' . (int) $tariff['trimestre'];
        $due = schoolFeeAmountToCents((string) $tariff['montant']);
        $paid = $paidByComponent[$key] ?? 0;
        $remaining = schoolFeeRemainingCents($due, $paid);
        $studentExpected += $due;
        $studentPaid += $paid;
        $studentRemaining += $remaining;
        $detailRows[] = [
            'enrollment' => $enrollment,
            'label' => $feeLabel($tariff['type_paiement'], (int) $tariff['trimestre']),
            'due' => $due,
            'paid' => $paid,
            'remaining' => $remaining,
        ];
    }

    $totalDue += $studentExpected;
    $totalPaid += $studentPaid;
    $totalRemaining += $studentRemaining;
    $summaryRows[] = [
        'enrollment' => $enrollment,
        'due' => $studentExpected,
        'paid' => $studentPaid,
        'remaining' => $studentRemaining,
        'has_tariffs' => $studentTariffs !== [],
    ];
}

$payments = $pdo->query(
    'SELECT p.id, p.eleve_id, p.inscription_id, p.montant, p.type_paiement, p.trimestre,
            p.date_paiement, p.mode_paiement, p.commentaire,
            e.matricule, e.nom, e.prenom, c.nom AS classe_nom, c.annee_scolaire
     FROM paiements p
     INNER JOIN eleves e ON e.id = p.eleve_id
     LEFT JOIN inscriptions i ON i.id = p.inscription_id
     LEFT JOIN classes c ON c.id = i.classe_id
     ORDER BY p.date_paiement DESC, p.id DESC'
)->fetchAll();
$unassignedPayments = array_filter(
    $payments,
    static fn (array $payment): bool => $payment['inscription_id'] === null
        || ($payment['type_paiement'] === 'trimestre' && (int) $payment['trimestre'] === 0)
);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Frais scolaires - École Beree</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #172033; }
        .header { background: #172033; color: white; padding: 18px 32px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: #fff; text-decoration: none; }
        .container { max-width: 1200px; margin: 36px auto; padding: 0 20px; }
        .panel { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 6px 20px rgba(15,23,42,0.08); margin-bottom: 24px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; }
        .field { margin-bottom: 14px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; }
        button { background: #2563eb; color: white; border: none; padding: 11px 16px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 11px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        th { background: #2563eb; color: white; }
        .table-wrap { max-width: 100%; overflow-x: auto; }
        .notice { padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        .warning { background: #fff7ed; color: #9a3412; }
        .muted { color: #64748b; font-size: .9rem; }
        .totals { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .total-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; }
        .total-card span { display: block; color: #475569; font-size: .9rem; }
        .total-card strong { display: block; margin-top: 6px; font-size: 1.25rem; }
        .inline-form { min-width: 250px; display: grid; gap: 7px; }
        @media (max-width: 600px) {
            .header { padding: 14px 16px; align-items: flex-start; flex-direction: column; gap: 12px; }
            .container { width: 100%; margin: 22px auto; padding: 0 14px; }
            .panel { padding: 17px; }
            .grid { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <strong>Suivi des frais scolaires</strong>
        <nav style="display:flex; gap:14px; flex-wrap:wrap;">
            <a href="dashboard.php">Tableau de bord</a>
            <a href="student_profile.php">Profil élève</a>
            <a href="logout.php">Déconnexion</a>
        </nav>
    </header>

    <main class="container">
        <?php if ($message !== ''): ?>
            <div class="notice <?= $messageType === 'success' ? 'success' : 'error' ?>" role="status"><?= $escape($message) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="notice error" role="alert"><?= $escape($error) ?></div>
        <?php endif; ?>
        <?php if ($unassignedPayments !== []): ?>
            <div class="notice warning" role="status">
                <?= count($unassignedPayments) ?> paiement(s) historique(s) nécessitent une affectation à l’année scolaire et, pour les trimestres, au trimestre exact. Ils ne sont pas inclus dans les soldes avant cette vérification.
            </div>
        <?php endif; ?>

        <section class="panel">
            <h1>Situation des frais</h1>
            <p class="muted">Les soldes sont calculés à partir des tarifs enregistrés pour chaque classe et année scolaire. Seuls les frais configurés sont comptés.</p>
            <div class="totals">
                <div class="total-card"><span>Montant dû configuré</span><strong><?= $escape(formatSchoolFeeCents($totalDue)) ?></strong></div>
                <div class="total-card"><span>Montant encaissé affecté</span><strong><?= $escape(formatSchoolFeeCents($totalPaid)) ?></strong></div>
                <div class="total-card"><span>Solde restant</span><strong><?= $escape(formatSchoolFeeCents($totalRemaining)) ?></strong></div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Élève</th><th>Classe / année</th><th>Tarifs</th><th>Dû</th><th>Payé</th><th>Reste à payer</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summaryRows as $row): ?>
                            <?php $student = $row['enrollment']; ?>
                            <tr>
                                <td><?= $escape($student['prenom'] . ' ' . $student['nom']) ?><br><span class="muted"><?= $escape($student['matricule']) ?></span></td>
                                <td><?= $escape($student['classe_nom'] . ' · ' . $student['annee_scolaire']) ?></td>
                                <td><?= $row['has_tariffs'] ? 'Configurés' : 'À définir' ?></td>
                                <td><?= $escape(formatSchoolFeeCents($row['due'])) ?></td>
                                <td><?= $escape(formatSchoolFeeCents($row['paid'])) ?></td>
                                <td><?= $escape(formatSchoolFeeCents($row['remaining'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($summaryRows === []): ?>
                            <tr><td colspan="6">Aucune inscription scolaire à afficher.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>Tarifs par classe et année scolaire</h2>
            <p class="muted">Pour un trimestre, enregistrez un tarif séparé pour chacun des trois trimestres. Les autres frais sont définis une fois par année de classe.</p>
            <form method="post" action="fees.php">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrf) ?>">
                <input type="hidden" name="action" value="save_tariff">
                <div class="grid">
                    <div class="field">
                        <label for="tariff_class">Classe / année *</label>
                        <select id="tariff_class" name="classe_id" required>
                            <option value="">Choisir</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= (int) $class['id'] ?>"><?= $escape($class['nom'] . ' · ' . $class['annee_scolaire']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="tariff_type">Type de frais *</label>
                        <select id="tariff_type" name="type_paiement" data-trimester-control="tariff_trimester" required>
                            <?php foreach ($feeTypes as $type => $label): ?>
                                <option value="<?= $escape($type) ?>"><?= $escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field" id="tariff_trimester_field">
                        <label for="tariff_trimester">Trimestre *</label>
                        <select id="tariff_trimester" name="trimestre">
                            <option value="1">Trimestre 1</option>
                            <option value="2">Trimestre 2</option>
                            <option value="3">Trimestre 3</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="tariff_amount">Montant (FCFA) *</label>
                        <input id="tariff_amount" type="number" name="montant" min="0.01" max="99999999.99" step="0.01" required>
                    </div>
                </div>
                <button type="submit">Enregistrer le tarif</button>
            </form>

            <h3>Tarifs enregistrés</h3>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Classe / année</th><th>Frais</th><th>Montant</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($tariffs as $tariff): ?>
                            <tr>
                                <td><?= $escape($tariff['classe_nom'] . ' · ' . $tariff['annee_scolaire']) ?></td>
                                <td><?= $escape($feeLabel($tariff['type_paiement'], (int) $tariff['trimestre'])) ?></td>
                                <td><?= $escape(formatSchoolFeeCents(schoolFeeAmountToCents((string) $tariff['montant']))) ?></td>
                                <td>
                                    <form method="post" action="fees.php">
                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrf) ?>">
                                        <input type="hidden" name="action" value="delete_tariff">
                                        <input type="hidden" name="tarif_id" value="<?= (int) $tariff['id'] ?>">
                                        <button type="submit">Supprimer le tarif</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($tariffs === []): ?>
                            <tr><td colspan="4">Aucun tarif scolaire n’a encore été défini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>Enregistrer un paiement</h2>
            <form method="post" action="fees.php">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrf) ?>">
                <input type="hidden" name="action" value="record_payment">
                <div class="grid">
                    <div class="field">
                        <label for="payment_enrollment">Élève / classe / année *</label>
                        <select id="payment_enrollment" name="inscription_id" required>
                            <option value="">Choisir une inscription</option>
                            <?php foreach ($enrollments as $enrollment): ?>
                                <option value="<?= (int) $enrollment['id'] ?>"><?= $escape($enrollment['prenom'] . ' ' . $enrollment['nom'] . ' · ' . $enrollment['classe_nom'] . ' · ' . $enrollment['annee_scolaire']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="payment_type">Type de frais *</label>
                        <select id="payment_type" name="type_paiement" data-trimester-control="payment_trimester" required>
                            <?php foreach ($feeTypes as $type => $label): ?>
                                <option value="<?= $escape($type) ?>"><?= $escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field" id="payment_trimester_field">
                        <label for="payment_trimester">Trimestre *</label>
                        <select id="payment_trimester" name="trimestre">
                            <option value="1">Trimestre 1</option>
                            <option value="2">Trimestre 2</option>
                            <option value="3">Trimestre 3</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="payment_amount">Montant (FCFA) *</label>
                        <input id="payment_amount" type="number" name="montant" min="0.01" max="99999999.99" step="0.01" required>
                    </div>
                    <div class="field">
                        <label for="payment_date">Date *</label>
                        <input id="payment_date" type="date" name="date_paiement" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="field">
                        <label for="payment_mode">Mode de paiement *</label>
                        <select id="payment_mode" name="mode_paiement" required>
                            <option value="cash">Espèces</option>
                            <option value="banque">Banque</option>
                            <option value="mobile_money">Mobile money</option>
                            <option value="cheque">Chèque</option>
                        </select>
                    </div>
                    <div class="field" style="grid-column:1/-1">
                        <label for="payment_comment">Commentaire</label>
                        <textarea id="payment_comment" name="commentaire" maxlength="255"></textarea>
                    </div>
                </div>
                <button type="submit">Enregistrer le paiement</button>
            </form>
        </section>

        <?php if ($detailRows !== []): ?>
            <section class="panel">
                <h2>Détail des soldes par frais</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Élève</th><th>Classe / année</th><th>Frais</th><th>Dû</th><th>Payé</th><th>Solde</th></tr></thead>
                        <tbody>
                            <?php foreach ($detailRows as $row): ?>
                                <?php $student = $row['enrollment']; ?>
                                <tr>
                                    <td><?= $escape($student['prenom'] . ' ' . $student['nom']) ?></td>
                                    <td><?= $escape($student['classe_nom'] . ' · ' . $student['annee_scolaire']) ?></td>
                                    <td><?= $escape($row['label']) ?></td>
                                    <td><?= $escape(formatSchoolFeeCents($row['due'])) ?></td>
                                    <td><?= $escape(formatSchoolFeeCents($row['paid'])) ?></td>
                                    <td><?= $escape(formatSchoolFeeCents($row['remaining'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <section class="panel">
            <h2>Historique des paiements</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Élève</th><th>Classe / année</th><th>Frais</th><th>Montant</th><th>Mode</th><th>Date</th><th>Affectation</th></tr></thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <?php
                            $needsAssignment = $payment['inscription_id'] === null
                                || ($payment['type_paiement'] === 'trimestre' && (int) $payment['trimestre'] === 0);
                            ?>
                            <tr>
                                <td><?= $escape($payment['prenom'] . ' ' . $payment['nom']) ?><br><span class="muted"><?= $escape($payment['matricule']) ?></span></td>
                                <td><?= $escape($payment['classe_nom'] !== null ? $payment['classe_nom'] . ' · ' . $payment['annee_scolaire'] : 'Année non affectée') ?></td>
                                <td><?= $escape($feeLabel($payment['type_paiement'], (int) $payment['trimestre'])) ?></td>
                                <td><?= $escape(formatSchoolFeeCents(schoolFeeAmountToCents((string) $payment['montant']))) ?></td>
                                <td><?= $escape($payment['mode_paiement']) ?></td>
                                <td><?= $escape($payment['date_paiement']) ?></td>
                                <td>
                                    <?php if ($needsAssignment): ?>
                                        <?php $studentEnrollments = $enrollmentsByStudent[(int) $payment['eleve_id']] ?? []; ?>
                                        <?php if ($studentEnrollments !== []): ?>
                                            <form method="post" action="fees.php" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?= $escape($csrf) ?>">
                                                <input type="hidden" name="action" value="assign_payment">
                                                <input type="hidden" name="paiement_id" value="<?= (int) $payment['id'] ?>">
                                                <select name="inscription_id" aria-label="Année scolaire du paiement" required>
                                                    <option value="">Choisir l’année</option>
                                                    <?php foreach ($studentEnrollments as $enrollment): ?>
                                                        <option value="<?= (int) $enrollment['id'] ?>"><?= $escape($enrollment['classe_nom'] . ' · ' . $enrollment['annee_scolaire']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <?php if ($payment['type_paiement'] === 'trimestre'): ?>
                                                    <select name="trimestre" aria-label="Trimestre payé" required>
                                                        <option value="">Choisir le trimestre</option>
                                                        <option value="1">Trimestre 1</option>
                                                        <option value="2">Trimestre 2</option>
                                                        <option value="3">Trimestre 3</option>
                                                    </select>
                                                <?php else: ?>
                                                    <input type="hidden" name="trimestre" value="0">
                                                <?php endif; ?>
                                                <button type="submit">Affecter</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="muted">Aucune inscription disponible</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Affecté
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($payments === []): ?>
                            <tr><td colspan="7">Aucun paiement enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-trimester-control]').forEach((typeSelect) => {
            const trimesterSelect = document.getElementById(typeSelect.dataset.trimesterControl);
            const trimesterField = document.getElementById(`${typeSelect.dataset.trimesterControl}_field`);
            const updateTrimesterField = () => {
                const isTrimester = typeSelect.value === 'trimestre';
                trimesterField.hidden = !isTrimester;
                trimesterSelect.disabled = !isTrimester;
                if (!isTrimester) {
                    trimesterSelect.value = '1';
                }
            };
            typeSelect.addEventListener('change', updateTrimesterField);
            updateTrimesterField();
        });
    </script>
</body>
</html>

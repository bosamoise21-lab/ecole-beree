<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/payroll_helpers.php';
requireAdmin();

$pdo = getDb();
$message = (string) ($_SESSION['payroll_message'] ?? '');
$messageType = (string) ($_SESSION['payroll_message_type'] ?? 'success');
unset($_SESSION['payroll_message'], $_SESSION['payroll_message_type']);
$error = '';

if (empty($_SESSION['payroll_csrf'])) {
    $_SESSION['payroll_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['payroll_csrf'], $token)) {
        http_response_code(403);
        exit('La session du formulaire a expiré. Rechargez la page et réessayez.');
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'add_employee') {
            $matricule = trim((string) ($_POST['matricule'] ?? ''));
            $nom = trim((string) ($_POST['nom'] ?? ''));
            $prenom = trim((string) ($_POST['prenom'] ?? ''));
            $poste = trim((string) ($_POST['poste'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $telephone = trim((string) ($_POST['telephone'] ?? ''));
            $dateEmbauche = trim((string) ($_POST['date_embauche'] ?? ''));
            $salary = trim((string) ($_POST['salaire_mensuel'] ?? ''));

            if ($matricule === '' || $nom === '' || $prenom === '' || $poste === '') {
                throw new InvalidArgumentException('Le matricule, le nom, le prénom et le poste sont obligatoires.');
            }
            if (strlen($poste) > 100) {
                throw new InvalidArgumentException('Le poste ne peut pas dépasser 100 caractères.');
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Veuillez saisir une adresse e-mail valide.');
            }
            if ($salary !== '' && !isValidPayrollAmount($salary)) {
                throw new InvalidArgumentException('Le salaire mensuel doit être un montant positif avec deux décimales au maximum.');
            }
            if ($dateEmbauche !== '' && !isValidPayrollPaymentDate($dateEmbauche)) {
                throw new InvalidArgumentException('La date d’embauche est invalide.');
            }

            $statement = $pdo->prepare(
                'INSERT INTO employes (matricule, nom, prenom, poste, email, telephone, date_embauche, salaire_mensuel)
                 VALUES (:matricule, :nom, :prenom, :poste, :email, :telephone, :date_embauche, :salaire_mensuel)'
            );
            $statement->execute([
                ':matricule' => $matricule,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':poste' => $poste,
                ':email' => $email !== '' ? $email : null,
                ':telephone' => $telephone !== '' ? $telephone : null,
                ':date_embauche' => $dateEmbauche !== '' ? $dateEmbauche : null,
                ':salaire_mensuel' => $salary !== '' ? $salary : null,
            ]);

            $_SESSION['payroll_message'] = 'L’employé a été ajouté.';
        } elseif ($action === 'record_payment') {
            $personType = (string) ($_POST['person_type'] ?? '');
            $personId = filter_var($_POST['person_id'] ?? null, FILTER_VALIDATE_INT);
            $amount = trim((string) ($_POST['montant'] ?? ''));
            $period = normalizePayrollPeriod(trim((string) ($_POST['periode'] ?? '')));
            $paymentDate = trim((string) ($_POST['date_paiement'] ?? ''));
            $paymentMode = (string) ($_POST['mode_paiement'] ?? '');
            $allowedModes = ['cash', 'banque', 'mobile_money', 'cheque'];

            if (!in_array($personType, ['enseignant', 'employe'], true) || $personId === false || $personId <= 0) {
                throw new InvalidArgumentException('Choisissez un enseignant ou un employé valide.');
            }
            if (!isValidPayrollAmount($amount)) {
                throw new InvalidArgumentException('Le montant versé doit être positif et contenir au maximum deux décimales.');
            }
            if ($period === null) {
                throw new InvalidArgumentException('Choisissez un mois de paie valide.');
            }
            if (!isValidPayrollPaymentDate($paymentDate)) {
                throw new InvalidArgumentException('La date de paiement est invalide.');
            }
            if (!in_array($paymentMode, $allowedModes, true)) {
                throw new InvalidArgumentException('Choisissez un mode de paiement valide.');
            }

            $column = $personType === 'enseignant' ? 'enseignant_id' : 'employe_id';
            $table = $personType === 'enseignant' ? 'enseignants' : 'employes';
            $exists = $pdo->prepare("SELECT 1 FROM `$table` WHERE id = :id");
            $exists->execute([':id' => $personId]);
            if (!$exists->fetchColumn()) {
                throw new InvalidArgumentException('La personne choisie n’existe plus.');
            }

            $statement = $pdo->prepare(
                "INSERT INTO paiements_salaires ($column, periode, montant, date_paiement, mode_paiement, commentaire)
                 VALUES (:person_id, :periode, :montant, :date_paiement, :mode_paiement, :commentaire)"
            );
            $statement->execute([
                ':person_id' => $personId,
                ':periode' => $period,
                ':montant' => $amount,
                ':date_paiement' => $paymentDate,
                ':mode_paiement' => $paymentMode,
                ':commentaire' => trim((string) ($_POST['commentaire'] ?? '')) ?: null,
            ]);

            $_SESSION['payroll_message'] = 'Le versement du salaire a été enregistré.';
        } else {
            throw new InvalidArgumentException('Action de paie non reconnue.');
        }

        $_SESSION['payroll_message_type'] = 'success';
        header('Location: payroll.php');
        exit;
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (PDOException $exception) {
        error_log('Échec de gestion des salaires: ' . $exception->getMessage());
        $error = 'Enregistrement impossible. Vérifiez les doublons et les données saisies.';
    }
}

$teachers = $pdo->query(
    'SELECT id, matricule, nom, prenom, salaire_mensuel
     FROM enseignants
     ORDER BY nom, prenom'
)->fetchAll();
$employees = $pdo->query(
    'SELECT id, matricule, nom, prenom, poste, salaire_mensuel
     FROM employes
     WHERE actif = 1
     ORDER BY nom, prenom'
)->fetchAll();
$payroll = $pdo->query(
    "SELECT p.periode, p.montant, p.date_paiement, p.mode_paiement,
            CONCAT(e.prenom, ' ', e.nom) AS personne,
            e.matricule, 'Enseignant' AS categorie
     FROM paiements_salaires p
     INNER JOIN enseignants e ON e.id = p.enseignant_id
     WHERE p.enseignant_id IS NOT NULL
     UNION ALL
     SELECT p.periode, p.montant, p.date_paiement, p.mode_paiement,
            CONCAT(s.prenom, ' ', s.nom) AS personne,
            s.matricule, s.poste AS categorie
     FROM paiements_salaires p
     INNER JOIN employes s ON s.id = p.employe_id
     WHERE p.employe_id IS NOT NULL
     ORDER BY date_paiement DESC, personne"
)->fetchAll();

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatAmount = static fn (mixed $value): string => number_format((float) $value, 2, ',', ' ') . ' CDF';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salaires et paie - École Beree</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-topbar">
        <a class="site-brand" href="dashboard.php"><img class="school-logo" src="beree-logo.png" alt="Logo de l’École Beree"><span>École Beree</span></a>
        <nav class="site-nav">
            <a href="dashboard.php">Tableau de bord</a>
            <a href="teachers.php">Enseignants</a>
            <a href="manage_data.php?entity=employes">Employés</a>
            <a href="logout.php">Déconnexion</a>
        </nav>
    </header>

    <main class="container payroll-page">
        <section class="hero-section payroll-heading">
            <p class="eyebrow">Gestion du personnel</p>
            <h1>Salaires et versements</h1>
            <p>Gérez les salaires mensuels et gardez une trace des paiements effectués au personnel.</p>
        </section>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= $escape($message) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert error"><?= $escape($error) ?></div>
        <?php endif; ?>

        <div class="payroll-grid">
            <section class="paper-panel payroll-form-panel">
                <p class="eyebrow">Équipe administrative</p>
                <h2>Ajouter un employé</h2>
                <p class="payroll-help">Pour la directrice, le personnel administratif, l’entretien et les autres fonctions.</p>
                <form method="post" action="payroll.php">
                    <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['payroll_csrf']) ?>">
                    <input type="hidden" name="action" value="add_employee">
                    <div class="grid">
                        <div class="field"><label for="matricule">Matricule *</label><input id="matricule" name="matricule" maxlength="30" required></div>
                        <div class="field"><label for="poste">Poste *</label><input id="poste" name="poste" placeholder="Directrice, comptable..." maxlength="100" required></div>
                        <div class="field"><label for="prenom">Prénom *</label><input id="prenom" name="prenom" required></div>
                        <div class="field"><label for="nom">Nom *</label><input id="nom" name="nom" required></div>
                        <div class="field"><label for="email">E-mail</label><input type="email" id="email" name="email"></div>
                        <div class="field"><label for="telephone">Téléphone</label><input id="telephone" name="telephone"></div>
                        <div class="field"><label for="date_embauche">Date d’embauche</label><input type="date" id="date_embauche" name="date_embauche"></div>
                        <div class="field"><label for="salaire_mensuel">Salaire mensuel (CDF)</label><input type="number" id="salaire_mensuel" name="salaire_mensuel" min="0.01" max="9999999999.99" step="0.01" placeholder="Ex. 850000"></div>
                    </div>
                    <button type="submit" class="btn-primary">Ajouter à l’équipe</button>
                </form>
            </section>

            <section class="paper-panel payroll-form-panel">
                <p class="eyebrow">Historique de paie</p>
                <h2>Enregistrer un versement</h2>
                <p class="payroll-help">Les versements partiels sont permis; indiquez le mois auquel ils se rapportent.</p>
                <form method="post" action="payroll.php">
                    <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['payroll_csrf']) ?>">
                    <input type="hidden" name="action" value="record_payment">
                    <div class="grid">
                        <div class="field">
                            <label for="person_id">Personne *</label>
                            <select id="person_id" name="person_id" required>
                                <option value="">Choisir une personne</option>
                                <?php if ($teachers !== []): ?>
                                    <optgroup label="Enseignants">
                                        <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?= (int) $teacher['id'] ?>" data-person-type="enseignant"><?= $escape($teacher['prenom'] . ' ' . $teacher['nom'] . ' (' . $teacher['matricule'] . ')') ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                                <?php if ($employees !== []): ?>
                                    <optgroup label="Directrice et autres employés">
                                        <?php foreach ($employees as $employee): ?>
                                            <option value="<?= (int) $employee['id'] ?>" data-person-type="employe"><?= $escape($employee['prenom'] . ' ' . $employee['nom'] . ' — ' . $employee['poste']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                            <input type="hidden" id="person_type" name="person_type" value="">
                        </div>
                        <div class="field"><label for="periode">Mois concerné *</label><input type="month" id="periode" name="periode" value="<?= date('Y-m') ?>" required></div>
                        <div class="field"><label for="montant">Montant versé (CDF) *</label><input type="number" id="montant" name="montant" min="0.01" max="9999999999.99" step="0.01" required></div>
                        <div class="field"><label for="date_paiement">Date du versement *</label><input type="date" id="date_paiement" name="date_paiement" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="field">
                            <label for="mode_paiement">Mode de paiement *</label>
                            <select id="mode_paiement" name="mode_paiement" required>
                                <option value="cash">Espèces</option>
                                <option value="banque">Banque</option>
                                <option value="mobile_money">Mobile money</option>
                                <option value="cheque">Chèque</option>
                            </select>
                        </div>
                        <div class="field"><label for="commentaire">Commentaire</label><input id="commentaire" name="commentaire" maxlength="255" placeholder="Référence ou précision"></div>
                    </div>
                    <button type="submit" class="btn-primary">Enregistrer le versement</button>
                </form>
            </section>
        </div>

        <section class="paper-panel payroll-history">
            <div class="section-header">
                <div><p class="eyebrow">Suivi financier</p><h2>Derniers versements</h2></div>
                <span class="payroll-count"><?= count($payroll) ?> versement(s)</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Personne</th><th>Poste / catégorie</th><th>Mois</th><th>Montant versé</th><th>Date</th><th>Mode</th></tr></thead>
                    <tbody>
                        <?php if ($payroll === []): ?>
                            <tr><td colspan="6" class="payroll-empty">Aucun versement enregistré pour le moment.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payroll as $payment): ?>
                                <tr>
                                    <td><?= $escape($payment['personne']) ?><small class="payroll-matricule"><?= $escape($payment['matricule']) ?></small></td>
                                    <td><?= $escape($payment['categorie']) ?></td>
                                    <td><?= $escape(substr((string) $payment['periode'], 0, 7)) ?></td>
                                    <td><strong><?= $escape($formatAmount($payment['montant'])) ?></strong></td>
                                    <td><?= $escape($payment['date_paiement']) ?></td>
                                    <td><?= $escape(str_replace('_', ' ', (string) $payment['mode_paiement'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const personSelect = document.getElementById('person_id');
        const personType = document.getElementById('person_type');
        personSelect.addEventListener('change', () => {
            personType.value = personSelect.selectedOptions[0]?.dataset.personType ?? '';
        });
    </script>
</body>
</html>

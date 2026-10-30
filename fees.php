<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();
$message = '';
$messageType = 'success';

$eleves = $pdo->query('SELECT e.id, CONCAT(e.prenom, " ", e.nom) AS nom_complet, c.nom AS classe_nom FROM eleves e LEFT JOIN inscriptions i ON i.eleve_id = e.id LEFT JOIN classes c ON c.id = i.classe_id ORDER BY e.nom, e.prenom')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eleveId = (int) ($_POST['eleve_id'] ?? 0);
    $montant = (float) ($_POST['montant'] ?? 0);
    $typePaiement = trim((string) ($_POST['type_paiement'] ?? ''));
    $datePaiement = trim((string) ($_POST['date_paiement'] ?? date('Y-m-d')));
    $modePaiement = trim((string) ($_POST['mode_paiement'] ?? 'cash'));
    $commentaire = trim((string) ($_POST['commentaire'] ?? ''));

    if ($eleveId <= 0 || $montant <= 0 || $typePaiement === '') {
        $message = 'L’élève, le montant et le type de paiement sont obligatoires.';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare('INSERT INTO paiements (eleve_id, montant, type_paiement, date_paiement, mode_paiement, commentaire) VALUES (:eleve_id, :montant, :type_paiement, :date_paiement, :mode_paiement, :commentaire)');
        $stmt->execute([
            ':eleve_id' => $eleveId,
            ':montant' => $montant,
            ':type_paiement' => $typePaiement,
            ':date_paiement' => $datePaiement,
            ':mode_paiement' => $modePaiement,
            ':commentaire' => $commentaire !== '' ? $commentaire : null,
        ]);
        $message = 'Le paiement a bien été enregistré.';
        $messageType = 'success';
    }
}

$payments = $pdo->query(
    'SELECT p.*, CONCAT(e.prenom, " ", e.nom) AS eleve, c.nom AS classe_nom
     FROM paiements p
     INNER JOIN eleves e ON e.id = p.eleve_id
     LEFT JOIN inscriptions i ON i.eleve_id = e.id
     LEFT JOIN classes c ON c.id = i.classe_id
     ORDER BY p.date_paiement DESC, p.id DESC'
)->fetchAll();
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
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .panel { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 6px 20px rgba(15,23,42,0.08); margin-bottom: 26px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .field { margin-bottom: 18px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; }
        button { background: #2563eb; color: white; border: none; padding: 12px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 8px 22px rgba(15,23,42,0.08); }
        th, td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #2563eb; color: white; }
        .msg { padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        .table-wrap { max-width: 100%; overflow-x: auto; }
        @media (max-width: 600px) {
            .header { padding: 14px 16px; align-items: flex-start; flex-direction: column; gap: 12px; }
            .header > div:last-child { flex-wrap: wrap; gap: 8px !important; }
            .container { width: 100%; margin: 22px auto; padding: 0 14px; }
            .panel { padding: 18px; }
            .grid { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div>Frais scolaires</div>
        <div style="display:flex; gap:12px; align-items:center;">
            <a href="dashboard.php">Dashboard</a>
            <a href="student_profile.php">Profil élève</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <div class="container">
        <div class="panel">
            <?php if ($message !== ''): ?>
                <div class="msg <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <h2>Enregistrer un paiement</h2>
            <form method="post" action="fees.php">
                <div class="grid">
                    <div class="field">
                        <label for="eleve_id">Élève</label>
                        <select id="eleve_id" name="eleve_id" required>
                            <option value="">Choisir</option>
                            <?php foreach ($eleves as $eleve): ?>
                                <option value="<?= (int) $eleve['id'] ?>"><?= htmlspecialchars($eleve['nom_complet'] . ' (' . ($eleve['classe_nom'] ?? 'Classe') . ')', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="montant">Montant</label>
                        <input type="number" id="montant" name="montant" min="1" step="0.01" required>
                    </div>
                    <div class="field">
                        <label for="type_paiement">Type</label>
                        <select id="type_paiement" name="type_paiement" required>
                            <option value="inscription">Inscription</option>
                            <option value="trimestre">Trimestre</option>
                            <option value="frais">Frais</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="date_paiement">Date</label>
                        <input type="date" id="date_paiement" name="date_paiement" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="field">
                        <label for="mode_paiement">Mode</label>
                        <select id="mode_paiement" name="mode_paiement">
                            <option value="cash">Cash</option>
                            <option value="banque">Banque</option>
                            <option value="mobile_money">Mobile money</option>
                            <option value="cheque">Chèque</option>
                        </select>
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="commentaire">Commentaire</label>
                        <textarea id="commentaire" name="commentaire" placeholder="Commentaire..."></textarea>
                    </div>
                </div>
                <div style="margin-top: 16px;"><button type="submit">Enregistrer</button></div>
            </form>
        </div>

        <div class="panel">
            <h2>Historique des paiements</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Élève</th><th>Classe</th><th>Type</th><th>Montant</th><th>Mode</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= htmlspecialchars($payment['eleve'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($payment['classe_nom'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($payment['type_paiement'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(number_format((float) $payment['montant'], 2, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($payment['mode_paiement'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($payment['date_paiement'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

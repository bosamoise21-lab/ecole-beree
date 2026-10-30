<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();

$eleveId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($eleveId <= 0) {
    $eleves = $pdo->query('SELECT e.id, CONCAT(e.prenom, " ", e.nom) AS nom_complet FROM eleves e ORDER BY e.nom, e.prenom')->fetchAll();
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Profil élève - École Beree</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #172033; }
            .header { background: #172033; color: white; padding: 18px 32px; display: flex; justify-content: space-between; align-items: center; }
            .header a { color: #fff; text-decoration: none; }
            .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
            .panel { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 6px 20px rgba(15,23,42,0.08); }
            .list { display: grid; gap: 10px; }
            .list a { display: block; background: #eff6ff; padding: 12px; border-radius: 8px; text-decoration: none; color: #172033; font-weight: bold; }
        </style>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="header">
            <div>Profil élève</div>
            <div style="display:flex; gap:12px; align-items:center;">
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Déconnexion</a>
            </div>
        </div>
        <div class="container">
            <div class="panel">
                <h2>Choisir un élève</h2>
                <div class="list">
                    <?php foreach ($eleves as $eleve): ?>
                        <a href="student_profile.php?id=<?= (int) $eleve['id'] ?>"><?= htmlspecialchars($eleve['nom_complet'], ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$student = $pdo->prepare('SELECT e.*, CONCAT(e.prenom, " ", e.nom) AS nom_complet, c.nom AS classe_nom, c.niveau AS classe_niveau, r.nom AS responsable_nom, r.prenom AS responsable_prenom FROM eleves e LEFT JOIN inscriptions i ON i.eleve_id = e.id LEFT JOIN classes c ON c.id = i.classe_id LEFT JOIN responsables r ON r.id = e.responsable_id WHERE e.id = :id LIMIT 1');
$student->execute([':id' => $eleveId]);
$studentData = $student->fetch();

if (!$studentData) {
    header('Location: student_profile.php');
    exit;
}

$notes = $pdo->prepare(
    'SELECT n.note, n.note_sur, n.type_evaluation, n.date_evaluation, m.nom AS matiere
     FROM notes n
     INNER JOIN inscriptions i ON i.id = n.inscription_id
     INNER JOIN matieres m ON m.id = n.matiere_id
     WHERE i.eleve_id = :eleve_id
     ORDER BY n.date_evaluation DESC'
);
$notes->execute([':eleve_id' => $eleveId]);
$notesList = $notes->fetchAll();

$attendance = $pdo->prepare(
    'SELECT date_presence, statut, motif FROM presences WHERE eleve_id = :eleve_id ORDER BY date_presence DESC LIMIT 10'
);
$attendance->execute([':eleve_id' => $eleveId]);
$attendanceList = $attendance->fetchAll();

$fees = $pdo->prepare('SELECT montant, type_paiement, date_paiement FROM paiements WHERE eleve_id = :eleve_id ORDER BY date_paiement DESC');
$fees->execute([':eleve_id' => $eleveId]);
$feesList = $fees->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil de <?= htmlspecialchars($studentData['prenom'], ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #172033; }
        .header { background: #172033; color: white; padding: 18px 32px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: #fff; text-decoration: none; }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .panel { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 6px 20px rgba(15,23,42,0.08); margin-bottom: 24px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .info { background: #f8fafc; border-radius: 10px; padding: 14px; }
        .label { color: #475569; font-size: 0.8rem; }
        .value { font-weight: bold; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #2563eb; color: white; }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div>Profil élève</div>
        <div style="display:flex; gap:12px; align-items:center;">
            <a href="dashboard.php">Dashboard</a>
            <a href="student_profile.php">Choisir un élève</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <div class="container">
        <div class="panel">
            <h2><?= htmlspecialchars($studentData['nom_complet'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="grid">
                <div class="info"><div class="label">Matricule</div><div class="value"><?= htmlspecialchars($studentData['matricule'], ENT_QUOTES, 'UTF-8') ?></div></div>
                <div class="info"><div class="label">Classe</div><div class="value"><?= htmlspecialchars($studentData['classe_nom'] ?? 'Non inscrit', ENT_QUOTES, 'UTF-8') ?></div></div>
                <div class="info"><div class="label">Niveau</div><div class="value"><?= htmlspecialchars($studentData['classe_niveau'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div></div>
                <div class="info"><div class="label">Date de naissance</div><div class="value"><?= htmlspecialchars($studentData['date_naissance'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div></div>
                <div class="info"><div class="label">Téléphone</div><div class="value"><?= htmlspecialchars($studentData['telephone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div></div>
                <div class="info"><div class="label">Email</div><div class="value"><?= htmlspecialchars($studentData['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div></div>
                <div class="info"><div class="label">Responsable</div><div class="value"><?= htmlspecialchars(($studentData['responsable_prenom'] ?? '') . ' ' . ($studentData['responsable_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '-' ?></div></div>
                <div class="info"><div class="label">Adresse</div><div class="value"><?= htmlspecialchars($studentData['adresse'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div></div>
            </div>
        </div>

        <div class="panel">
            <h3>Notes</h3>
            <table>
                <thead>
                    <tr><th>Matière</th><th>Type</th><th>Note</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($notesList as $note): ?>
                        <tr>
                            <td><?= htmlspecialchars($note['matiere'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($note['type_evaluation'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $note['note'] . '/' . (string) $note['note_sur'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($note['date_evaluation'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h3>Présences récentes</h3>
            <table>
                <thead>
                    <tr><th>Date</th><th>Statut</th><th>Motif</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($attendanceList as $entry): ?>
                        <tr>
                            <td><?= htmlspecialchars($entry['date_presence'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($entry['statut'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($entry['motif'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h3>Paiements</h3>
            <table>
                <thead>
                    <tr><th>Type</th><th>Montant</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($feesList as $fee): ?>
                        <tr>
                            <td><?= htmlspecialchars($fee['type_paiement'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(number_format((float) $fee['montant'], 2, ',', ' ') . ' FCFA', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($fee['date_paiement'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

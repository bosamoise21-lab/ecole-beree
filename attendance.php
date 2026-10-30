<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();
$message = '';
$messageType = 'success';

$classes = $pdo->query('SELECT * FROM classes ORDER BY annee_scolaire DESC, nom ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classeId = (int) ($_POST['classe_id'] ?? 0);
    $eleveId = (int) ($_POST['eleve_id'] ?? 0);
    $datePresence = trim((string) ($_POST['date_presence'] ?? date('Y-m-d')));
    $statut = trim((string) ($_POST['statut'] ?? 'present'));
    $motif = trim((string) ($_POST['motif'] ?? ''));

    if ($classeId <= 0 || $eleveId <= 0) {
        $message = 'La classe et l’élève sont obligatoires.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO presences (eleve_id, classe_id, date_presence, statut, motif) VALUES (:eleve_id, :classe_id, :date_presence, :statut, :motif)');
            $stmt->execute([
                ':eleve_id' => $eleveId,
                ':classe_id' => $classeId,
                ':date_presence' => $datePresence,
                ':statut' => $statut,
                ':motif' => $motif !== '' ? $motif : null,
            ]);
            $message = 'La présence a bien été enregistrée.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Une présence existe déjà pour cet élève à cette date.';
            $messageType = 'error';
        }
    }
}

$eleves = $pdo->query('SELECT e.id, CONCAT(e.prenom, " ", e.nom) AS nom_complet, c.nom AS classe_nom FROM eleves e LEFT JOIN inscriptions i ON i.eleve_id = e.id LEFT JOIN classes c ON c.id = i.classe_id ORDER BY e.nom, e.prenom')->fetchAll();

$presences = $pdo->query(
    'SELECT p.date_presence, p.statut, p.motif, CONCAT(e.prenom, " ", e.nom) AS eleve, c.nom AS classe
     FROM presences p
     INNER JOIN eleves e ON e.id = p.eleve_id
     INNER JOIN classes c ON c.id = p.classe_id
     ORDER BY p.date_presence DESC, e.nom ASC LIMIT 30'
)->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Présences - École Beree</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #172033; }
        .header { background: #172033; color: white; padding: 18px 32px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: #fff; text-decoration: none; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .panel { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 6px 20px rgba(15,23,42,0.08); margin-bottom: 26px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; }
        .field { margin-bottom: 18px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; }
        input, select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; }
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
        <div>Présences</div>
        <div style="display:flex; gap:12px; align-items:center;">
            <a href="dashboard.php">Dashboard</a>
            <a href="notes.php">Notes</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <div class="container">
        <div class="panel">
            <?php if ($message !== ''): ?>
                <div class="msg <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <h2>Marquer une présence</h2>
            <form method="post" action="attendance.php">
                <div class="grid">
                    <div class="field">
                        <label for="classe_id">Classe</label>
                        <select id="classe_id" name="classe_id" required>
                            <option value="">Choisir</option>
                            <?php foreach ($classes as $classe): ?>
                                <option value="<?= (int) $classe['id'] ?>"><?= htmlspecialchars($classe['nom'] . ' - ' . $classe['niveau'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                        <label for="date_presence">Date</label>
                        <input type="date" id="date_presence" name="date_presence" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="field">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut">
                            <option value="present">Présent</option>
                            <option value="absent">Absent</option>
                            <option value="retard">Retard</option>
                            <option value="justifie">Justifié</option>
                        </select>
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="motif">Motif</label>
                        <input type="text" id="motif" name="motif" placeholder="Ex : maladie, sport, etc.">
                    </div>
                </div>
                <div style="margin-top: 16px;"><button type="submit">Enregistrer</button></div>
            </form>
        </div>

        <div class="panel">
            <h2>Historique récent</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Élève</th><th>Classe</th><th>Statut</th><th>Motif</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presences as $presence): ?>
                            <tr>
                                <td><?= htmlspecialchars($presence['date_presence'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($presence['eleve'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($presence['classe'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($presence['statut'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($presence['motif'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

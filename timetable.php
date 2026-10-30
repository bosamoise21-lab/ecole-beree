<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();
$classes = $pdo->query('SELECT * FROM classes ORDER BY annee_scolaire DESC, nom ASC')->fetchAll();
$matieres = $pdo->query('SELECT * FROM matieres ORDER BY nom ASC')->fetchAll();
$teachers = $pdo->query('SELECT * FROM enseignants ORDER BY nom, prenom')->fetchAll();

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classeId = (int) ($_POST['classe_id'] ?? 0);
    $matiereId = (int) ($_POST['matiere_id'] ?? 0);
    $enseignantId = (int) ($_POST['enseignant_id'] ?? 0);
    $jour = trim((string) ($_POST['jour'] ?? ''));
    $heureDebut = trim((string) ($_POST['heure_debut'] ?? ''));
    $heureFin = trim((string) ($_POST['heure_fin'] ?? ''));
    $salle = trim((string) ($_POST['salle'] ?? ''));

    if ($classeId <= 0 || $matiereId <= 0 || $enseignantId <= 0 || $jour === '' || $heureDebut === '' || $heureFin === '') {
        $message = 'Veuillez remplir tous les champs obligatoires.';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare('INSERT INTO emplois_du_temps (classe_id, matiere_id, enseignant_id, jour, heure_debut, heure_fin, salle) VALUES (:classe_id, :matiere_id, :enseignant_id, :jour, :heure_debut, :heure_fin, :salle)');
        $stmt->execute([
            ':classe_id' => $classeId,
            ':matiere_id' => $matiereId,
            ':enseignant_id' => $enseignantId,
            ':jour' => $jour,
            ':heure_debut' => $heureDebut,
            ':heure_fin' => $heureFin,
            ':salle' => $salle !== '' ? $salle : null,
        ]);
        $message = 'Le cours a bien été ajouté à l’emploi du temps.';
        $messageType = 'success';
    }
}

$planning = $pdo->query(
    'SELECT e.*, m.nom AS matiere, c.nom AS classe_nom, CONCAT(ens.prenom, " ", ens.nom) AS enseignant
     FROM emplois_du_temps e
     INNER JOIN matieres m ON m.id = e.matiere_id
     INNER JOIN classes c ON c.id = e.classe_id
     INNER JOIN enseignants ens ON ens.id = e.enseignant_id
     ORDER BY CASE e.jour WHEN "Lundi" THEN 1 WHEN "Mardi" THEN 2 WHEN "Mercredi" THEN 3 WHEN "Jeudi" THEN 4 WHEN "Vendredi" THEN 5 WHEN "Samedi" THEN 6 ELSE 7 END, e.heure_debut ASC'
)->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Emploi du temps - École Beree</title>
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
        <div>Emploi du temps</div>
        <div style="display:flex; gap:12px; align-items:center;">
            <a href="dashboard.php">Dashboard</a>
            <a href="fees.php">Paiements</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <div class="container">
        <div class="panel">
            <?php if ($message !== ''): ?>
                <div class="msg <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <h2>Ajouter un cours</h2>
            <form method="post" action="timetable.php">
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
                        <label for="matiere_id">Matière</label>
                        <select id="matiere_id" name="matiere_id" required>
                            <option value="">Choisir</option>
                            <?php foreach ($matieres as $matiere): ?>
                                <option value="<?= (int) $matiere['id'] ?>"><?= htmlspecialchars($matiere['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="enseignant_id">Enseignant</label>
                        <select id="enseignant_id" name="enseignant_id" required>
                            <option value="">Choisir</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= (int) $teacher['id'] ?>"><?= htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="jour">Jour</label>
                        <select id="jour" name="jour" required>
                            <option value="Lundi">Lundi</option>
                            <option value="Mardi">Mardi</option>
                            <option value="Mercredi">Mercredi</option>
                            <option value="Jeudi">Jeudi</option>
                            <option value="Vendredi">Vendredi</option>
                            <option value="Samedi">Samedi</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="heure_debut">Heure début</label>
                        <input type="time" id="heure_debut" name="heure_debut" required>
                    </div>
                    <div class="field">
                        <label for="heure_fin">Heure fin</label>
                        <input type="time" id="heure_fin" name="heure_fin" required>
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="salle">Salle</label>
                        <input type="text" id="salle" name="salle" placeholder="A1, S1...">
                    </div>
                </div>
                <div style="margin-top: 16px;"><button type="submit">Ajouter</button></div>
            </form>
        </div>

        <div class="panel">
            <h2>Planning</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Classe</th><th>Jour</th><th>Matière</th><th>Enseignant</th><th>Heure</th><th>Salle</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($planning as $entry): ?>
                            <tr>
                                <td><?= htmlspecialchars($entry['classe_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($entry['jour'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($entry['matiere'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($entry['enseignant'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($entry['heure_debut'] . ' - ' . $entry['heure_fin'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($entry['salle'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

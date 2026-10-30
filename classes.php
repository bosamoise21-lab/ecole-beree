<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();
$teachers = $pdo->query('SELECT id, nom, prenom FROM enseignants ORDER BY nom, prenom')->fetchAll();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $niveau = trim((string) ($_POST['niveau'] ?? ''));
    $annee = trim((string) ($_POST['annee_scolaire'] ?? ''));
    $capacite = (int) ($_POST['capacite'] ?? 30);
    $enseignantId = (int) ($_POST['enseignant_principal_id'] ?? 0);

    if ($nom === '' || $niveau === '' || $annee === '') {
        $message = 'Le nom, le niveau et l’année scolaire sont obligatoires.';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare('INSERT INTO classes (nom, niveau, annee_scolaire, enseignant_principal_id, capacite) VALUES (:nom, :niveau, :annee_scolaire, :enseignant_principal_id, :capacite)');
        $stmt->execute([
            ':nom' => $nom,
            ':niveau' => $niveau,
            ':annee_scolaire' => $annee,
            ':enseignant_principal_id' => $enseignantId > 0 ? $enseignantId : null,
            ':capacite' => $capacite > 0 ? $capacite : 30,
        ]);
        $message = 'La classe a bien été ajoutée.';
        $messageType = 'success';
    }
}

$classes = $pdo->query(
    'SELECT c.*, e.nom AS enseignant_nom, e.prenom AS enseignant_prenom, COUNT(i.id) AS total_eleves
     FROM classes c
     LEFT JOIN enseignants e ON e.id = c.enseignant_principal_id
     LEFT JOIN inscriptions i ON i.classe_id = c.id
     GROUP BY c.id
     ORDER BY c.annee_scolaire DESC, c.nom ASC'
)->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion des classes - École Beree</title>
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
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 8px 22px rgba(15,23,42,0.08); }
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
        <div>Gestion des classes</div>
        <div style="display:flex; gap:12px; align-items:center;">
            <a href="dashboard.php">Dashboard</a>
            <a href="add_student.php">Ajouter un élève</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <div class="container">
        <div class="panel">
            <?php if ($message !== ''): ?>
                <div class="msg <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <h2>Ajouter une classe</h2>
            <form method="post" action="classes.php">
                <div class="grid">
                    <div class="field">
                        <label for="nom">Nom de la classe *</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                    <div class="field">
                        <label for="niveau">Niveau *</label>
                        <input type="text" id="niveau" name="niveau" placeholder="6e, 5e, ..." required>
                    </div>
                    <div class="field">
                        <label for="annee_scolaire">Année scolaire *</label>
                        <input type="text" id="annee_scolaire" name="annee_scolaire" placeholder="2025-2026" required>
                    </div>
                    <div class="field">
                        <label for="capacite">Capacité</label>
                        <input type="number" id="capacite" name="capacite" value="30" min="1">
                    </div>
                    <div class="field">
                        <label for="enseignant_principal_id">Enseignant principal</label>
                        <select id="enseignant_principal_id" name="enseignant_principal_id">
                            <option value="">Aucun</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= (int) $teacher['id'] ?>"><?= htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 16px;">
                    <button type="submit">Ajouter la classe</button>
                </div>
            </form>
        </div>

        <div class="panel">
            <h2>Liste des classes</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Niveau</th>
                            <th>Année</th>
                            <th>Capacité</th>
                            <th>Enseignant</th>
                            <th>Élèves</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $classe): ?>
                            <tr>
                                <td><?= htmlspecialchars($classe['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($classe['niveau'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($classe['annee_scolaire'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $classe['capacite'] ?></td>
                                <td><?= htmlspecialchars(($classe['enseignant_prenom'] ?? '') . ' ' . ($classe['enseignant_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?: 'Aucun' ?></td>
                                <td><?= (int) $classe['total_eleves'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

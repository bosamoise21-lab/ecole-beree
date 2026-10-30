<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();
$message = '';
$messageType = 'success';

$classes = $pdo->query('SELECT * FROM classes ORDER BY annee_scolaire DESC, nom ASC')->fetchAll();
$matieres = $pdo->query('SELECT * FROM matieres ORDER BY nom ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classeId = (int) ($_POST['classe_id'] ?? 0);
    $eleveId = (int) ($_POST['eleve_id'] ?? 0);
    $matiereId = (int) ($_POST['matiere_id'] ?? 0);
    $typeEvaluation = trim((string) ($_POST['type_evaluation'] ?? ''));
    $note = (float) ($_POST['note'] ?? 0);
    $noteSur = (float) ($_POST['note_sur'] ?? 20);
    $dateEvaluation = trim((string) ($_POST['date_evaluation'] ?? date('Y-m-d')));
    $commentaire = trim((string) ($_POST['commentaire'] ?? ''));

    if ($classeId <= 0 || $eleveId <= 0 || $matiereId <= 0 || $typeEvaluation === '') {
        $message = 'Classe, élève, matière et type d’évaluation sont obligatoires.';
        $messageType = 'error';
    } else {
        $inscription = $pdo->prepare('SELECT id FROM inscriptions WHERE eleve_id = :eleve_id AND classe_id = :classe_id ORDER BY id DESC LIMIT 1');
        $inscription->execute([':eleve_id' => $eleveId, ':classe_id' => $classeId]);
        $inscriptionRow = $inscription->fetch();

        if (!$inscriptionRow) {
            $message = 'Cet élève n’est pas inscrit dans cette classe.';
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare('INSERT INTO notes (inscription_id, matiere_id, type_evaluation, note, note_sur, date_evaluation, commentaire) VALUES (:inscription_id, :matiere_id, :type_evaluation, :note, :note_sur, :date_evaluation, :commentaire)');
            $stmt->execute([
                ':inscription_id' => (int) $inscriptionRow['id'],
                ':matiere_id' => $matiereId,
                ':type_evaluation' => $typeEvaluation,
                ':note' => $note,
                ':note_sur' => $noteSur,
                ':date_evaluation' => $dateEvaluation,
                ':commentaire' => $commentaire !== '' ? $commentaire : null,
            ]);
            $message = 'La note a bien été enregistrée.';
            $messageType = 'success';
        }
    }
}

$eleves = $pdo->query('SELECT e.id, e.matricule, e.nom, e.prenom, c.nom AS classe_nom FROM eleves e LEFT JOIN inscriptions i ON i.eleve_id = e.id LEFT JOIN classes c ON c.id = i.classe_id ORDER BY e.nom, e.prenom')->fetchAll();

$notes = $pdo->query(
    'SELECT n.id, n.note, n.note_sur, n.type_evaluation, n.date_evaluation, m.nom AS matiere, CONCAT(e.prenom, " ", e.nom) AS eleve, c.nom AS classe
     FROM notes n
     INNER JOIN inscriptions i ON i.id = n.inscription_id
     INNER JOIN eleves e ON e.id = i.eleve_id
     INNER JOIN classes c ON c.id = i.classe_id
     INNER JOIN matieres m ON m.id = n.matiere_id
     ORDER BY n.date_evaluation DESC, e.nom ASC'
)->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notes - École Beree</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #172033; }
        .header { background: #172033; color: white; padding: 18px 32px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: #fff; text-decoration: none; }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .panel { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 6px 20px rgba(15,23,42,0.08); margin-bottom: 26px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; }
        .field { margin-bottom: 18px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; }
        textarea { min-height: 90px; resize: vertical; }
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
        <div>Notes</div>
        <div style="display:flex; gap:12px; align-items:center;">
            <a href="dashboard.php">Dashboard</a>
            <a href="attendance.php">Présences</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <div class="container">
        <div class="panel">
            <?php if ($message !== ''): ?>
                <div class="msg <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <h2>Ajouter une note</h2>
            <form method="post" action="notes.php">
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
                                <option value="<?= (int) $eleve['id'] ?>"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom'] . ' (' . $eleve['classe_nom'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
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
                        <label for="type_evaluation">Type</label>
                        <select id="type_evaluation" name="type_evaluation" required>
                            <option value="devoir">Devoir</option>
                            <option value="composition">Composition</option>
                            <option value="examen">Examen</option>
                            <option value="oral">Oral</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="note">Note</label>
                        <input type="number" id="note" name="note" min="0" max="20" step="0.01" value="15" required>
                    </div>
                    <div class="field">
                        <label for="note_sur">Sur</label>
                        <input type="number" id="note_sur" name="note_sur" min="1" max="20" step="0.01" value="20">
                    </div>
                    <div class="field">
                        <label for="date_evaluation">Date</label>
                        <input type="date" id="date_evaluation" name="date_evaluation" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="commentaire">Commentaire</label>
                        <textarea id="commentaire" name="commentaire" placeholder="Commentaire..."></textarea>
                    </div>
                </div>
                <div style="margin-top: 16px;"><button type="submit">Enregistrer la note</button></div>
            </form>
        </div>

        <div class="panel">
            <h2>Historique des notes</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Élève</th><th>Classe</th><th>Matière</th><th>Type</th><th>Note</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notes as $note): ?>
                            <tr>
                                <td><?= htmlspecialchars($note['eleve'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($note['classe'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($note['matiere'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($note['type_evaluation'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $note['note'] . '/' . $note['note_sur'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($note['date_evaluation'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

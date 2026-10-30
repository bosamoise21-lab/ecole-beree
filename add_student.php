<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();
$classes = $pdo->query('SELECT id, nom, niveau, annee_scolaire FROM classes ORDER BY annee_scolaire DESC, nom ASC')->fetchAll();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $prenom = trim((string) ($_POST['prenom'] ?? ''));
    $dateNaissance = trim((string) ($_POST['date_naissance'] ?? ''));
    $sexe = trim((string) ($_POST['sexe'] ?? ''));
    $adresse = trim((string) ($_POST['adresse'] ?? ''));
    $telephone = trim((string) ($_POST['telephone'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $classeId = (int) ($_POST['classe_id'] ?? 0);
    $responsableNom = trim((string) ($_POST['responsable_nom'] ?? ''));
    $responsablePrenom = trim((string) ($_POST['responsable_prenom'] ?? ''));

    if ($nom === '' || $prenom === '' || $dateNaissance === '' || $classeId <= 0) {
        $message = 'Les champs nom, prénom, date de naissance et classe sont obligatoires.';
        $messageType = 'error';
    } else {
        $responsableId = null;
        if ($responsableNom !== '' || $responsablePrenom !== '') {
            $stmt = $pdo->prepare('INSERT INTO responsables (nom, prenom, telephone, email, adresse) VALUES (:nom, :prenom, :telephone, :email, :adresse)');
            $stmt->execute([
                ':nom' => $responsableNom !== '' ? $responsableNom : 'Inconnu',
                ':prenom' => $responsablePrenom !== '' ? $responsablePrenom : 'Inconnu',
                ':telephone' => $telephone !== '' ? $telephone : null,
                ':email' => $email !== '' ? $email : null,
                ':adresse' => $adresse !== '' ? $adresse : null,
            ]);
            $responsableId = (int) $pdo->lastInsertId();
        }

        $matricule = 'ELV-' . sprintf('%04d', ((int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM eleves')->fetchColumn()) + 1);

        $stmt = $pdo->prepare('INSERT INTO eleves (matricule, nom, prenom, date_naissance, sexe, adresse, telephone, email, responsable_id) VALUES (:matricule, :nom, :prenom, :date_naissance, :sexe, :adresse, :telephone, :email, :responsable_id)');
        $stmt->execute([
            ':matricule' => $matricule,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':date_naissance' => $dateNaissance,
            ':sexe' => $sexe !== '' ? $sexe : 'autre',
            ':adresse' => $adresse !== '' ? $adresse : null,
            ':telephone' => $telephone !== '' ? $telephone : null,
            ':email' => $email !== '' ? $email : null,
            ':responsable_id' => $responsableId,
        ]);

        $eleveId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO inscriptions (eleve_id, classe_id, date_inscription, statut) VALUES (:eleve_id, :classe_id, CURRENT_DATE, "inscrit")');
        $stmt->execute([
            ':eleve_id' => $eleveId,
            ':classe_id' => $classeId,
        ]);

        $message = 'L’élève a bien été ajouté avec le matricule ' . $matricule . '.';
        $messageType = 'success';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ajouter un élève - École Beree</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #172033; }
        .header { background: #172033; color: white; padding: 18px 32px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: #fff; text-decoration: none; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .panel { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 6px 20px rgba(15,23,42,0.08); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .field { margin-bottom: 18px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; }
        input, select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; }
        button { background: #2563eb; color: white; border: none; padding: 12px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .msg { padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
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
        <div>Ajouter un élève</div>
        <div style="display:flex; gap:12px; align-items:center;">
            <a href="dashboard.php">Dashboard</a>
            <a href="classes.php">Classes</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <div class="container">
        <div class="panel">
            <?php if ($message !== ''): ?>
                <div class="msg <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="add_student.php">
                <div class="grid">
                    <div class="field">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                    <div class="field">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" required>
                    </div>
                    <div class="field">
                        <label for="date_naissance">Date de naissance *</label>
                        <input type="date" id="date_naissance" name="date_naissance" required>
                    </div>
                    <div class="field">
                        <label for="sexe">Sexe</label>
                        <select id="sexe" name="sexe">
                            <option value="F">F</option>
                            <option value="M">M</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="classe_id">Classe *</label>
                        <select id="classe_id" name="classe_id" required>
                            <option value="">Choisir une classe</option>
                            <?php foreach ($classes as $classe): ?>
                                <option value="<?= (int) $classe['id'] ?>"><?= htmlspecialchars($classe['nom'] . ' - ' . $classe['niveau'] . ' (' . $classe['annee_scolaire'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="telephone">Téléphone</label>
                        <input type="text" id="telephone" name="telephone">
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                    <div class="field">
                        <label for="adresse">Adresse</label>
                        <input type="text" id="adresse" name="adresse">
                    </div>
                    <div class="field">
                        <label for="responsable_nom">Nom du responsable</label>
                        <input type="text" id="responsable_nom" name="responsable_nom">
                    </div>
                    <div class="field">
                        <label for="responsable_prenom">Prénom du responsable</label>
                        <input type="text" id="responsable_prenom" name="responsable_prenom">
                    </div>
                </div>

                <div style="margin-top: 22px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                    <button type="submit">Enregistrer l’élève</button>
                    <a href="dashboard.php" style="color:#2563eb; text-decoration:none; font-weight:bold;">Retour au dashboard</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

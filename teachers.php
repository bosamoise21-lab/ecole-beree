<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/payroll_helpers.php';
requireAdmin();

$pdo = getDb();
$message = '';
$messageType = 'success';

if (empty($_SESSION['teachers_csrf'])) {
    $_SESSION['teachers_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['teachers_csrf'], $token)) {
        http_response_code(403);
        exit('La session du formulaire a expiré. Rechargez la page et réessayez.');
    }

    $matricule = trim((string) ($_POST['matricule'] ?? ''));
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $prenom = trim((string) ($_POST['prenom'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $telephone = trim((string) ($_POST['telephone'] ?? ''));
    $dateEmbauche = trim((string) ($_POST['date_embauche'] ?? ''));
    $salary = trim((string) ($_POST['salaire_mensuel'] ?? ''));

    if ($matricule === '' || $nom === '' || $prenom === '' || $email === '') {
        $message = 'Le matricule, le nom, le prénom et l’email sont obligatoires.';
        $messageType = 'error';
    } elseif ($salary !== '' && !isValidPayrollAmount($salary)) {
        $message = 'Le salaire mensuel doit être positif et contenir deux décimales au maximum.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO enseignants (matricule, nom, prenom, email, telephone, date_embauche, salaire_mensuel)
                 VALUES (:matricule, :nom, :prenom, :email, :telephone, :date_embauche, :salaire_mensuel)'
            );
            $stmt->execute([
                ':matricule' => $matricule,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':telephone' => $telephone !== '' ? $telephone : null,
                ':date_embauche' => $dateEmbauche !== '' ? $dateEmbauche : null,
                ':salaire_mensuel' => $salary !== '' ? $salary : null,
            ]);
            $message = 'L’enseignant a bien été ajouté.';
            $messageType = 'success';
        } catch (PDOException $exception) {
            error_log('Échec de création d’un enseignant: ' . $exception->getMessage());
            $message = 'Enregistrement impossible. Vérifiez le matricule et l’e-mail.';
            $messageType = 'error';
        }
    }
}

$teachers = $pdo->query('SELECT * FROM enseignants ORDER BY nom, prenom')->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enseignants - École Beree</title>
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
        input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; }
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
        <div>Enseignants</div>
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

            <h2>Ajouter un enseignant</h2>
            <form method="post" action="teachers.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['teachers_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="grid">
                    <div class="field"><label for="matricule">Matricule *</label><input type="text" id="matricule" name="matricule" required></div>
                    <div class="field"><label for="nom">Nom *</label><input type="text" id="nom" name="nom" required></div>
                    <div class="field"><label for="prenom">Prénom *</label><input type="text" id="prenom" name="prenom" required></div>
                    <div class="field"><label for="email">Email *</label><input type="email" id="email" name="email" required></div>
                    <div class="field"><label for="telephone">Téléphone</label><input type="text" id="telephone" name="telephone"></div>
                    <div class="field"><label for="date_embauche">Date d’embauche</label><input type="date" id="date_embauche" name="date_embauche"></div>
                    <div class="field"><label for="salaire_mensuel">Salaire mensuel (CDF)</label><input type="number" id="salaire_mensuel" name="salaire_mensuel" min="0.01" max="9999999999.99" step="0.01" placeholder="Ex. 850000"></div>
                </div>
                <div style="margin-top: 18px;"><button type="submit">Enregistrer</button></div>
            </form>
        </div>

        <div class="panel">
            <h2>Liste des enseignants</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Matricule</th><th>Nom</th><th>Email</th><th>Téléphone</th><th>Date embauche</th><th>Salaire mensuel</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <td><?= htmlspecialchars($teacher['matricule'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($teacher['telephone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($teacher['date_embauche'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $teacher['salaire_mensuel'] !== null ? htmlspecialchars(number_format((float) $teacher['salaire_mensuel'], 2, ',', ' ') . ' CDF', ENT_QUOTES, 'UTF-8') : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <p style="text-align:center;"><a href="payroll.php">Ouvrir la gestion des salaires →</a></p>
</body>
</html>

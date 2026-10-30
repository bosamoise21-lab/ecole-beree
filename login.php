<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (isAdminConnected()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        $stmt = getDb()->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        $storedPassword = (string) ($admin['password'] ?? '');
        $passwordIsHashed = $admin && password_verify($password, $storedPassword);
        $passwordMatches = $admin && (
            $passwordIsHashed || hash_equals($storedPassword, $password)
        );

        if ($passwordMatches) {
            if (!$passwordIsHashed) {
                $update = getDb()->prepare('UPDATE admins SET password = :password WHERE id = :id');
                $update->execute([
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':id' => $admin['id'],
                ]);
            }

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_nom'] = $admin['nom'] ?? $admin['username'];

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Identifiant ou mot de passe incorrect.';
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion admin - École Beree</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-brand">
                <img class="school-logo" src="beree-logo.png" alt="Logo de l’École Beree">
                <span>École Beree</span>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <div class="field">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="field">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary" style="width:100%;">Se connecter</button>
                </div>
            </form>

            <div style="text-align:center; margin-top:16px;">
                <a href="index.php" style="color:#176b55; text-decoration:none; font-weight:700;">Retour à l'accueil</a>
            </div>
        </div>
    </div>
</body>
</html>

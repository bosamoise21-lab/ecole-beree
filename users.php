<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();
$csrf = csrfToken('users');
$message = '';
$messageType = 'success';

if (isset($_GET['created'])) {
    $message = 'Le compte gestionnaire a été créé.';
} elseif (isset($_GET['deleted'])) {
    $message = 'Le compte gestionnaire a été supprimé.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken('users');
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create_manager') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $nom = trim((string) ($_POST['nom'] ?? ''));
        $prenom = trim((string) ($_POST['prenom'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $message = 'Le nom d’utilisateur doit contenir 3 à 50 caractères : lettres, chiffres, point, tiret ou soulignement.';
            $messageType = 'error';
        } elseif ($nom === '' || strlen($nom) > 100 || strlen($prenom) > 100) {
            $message = 'Indiquez un nom valide. Le prénom est facultatif.';
            $messageType = 'error';
        } elseif (strlen($password) < 12) {
            $message = 'Le mot de passe doit contenir au moins 12 caractères.';
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO admins (username, password, nom, prenom, role)
                     VALUES (:username, :password, :nom, :prenom, "gestionnaire")'
                );
                $stmt->execute([
                    ':username' => $username,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':nom' => $nom,
                    ':prenom' => $prenom !== '' ? $prenom : null,
                ]);
                header('Location: users.php?created=1');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $message = 'Ce nom d’utilisateur est déjà utilisé.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_manager') {
        $managerId = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM admins WHERE id = :id AND role = "gestionnaire"');
        $stmt->execute([':id' => $managerId]);

        if ($stmt->rowCount() !== 1) {
            $message = 'Ce compte gestionnaire est introuvable ou a déjà été supprimé.';
            $messageType = 'error';
        } else {
            header('Location: users.php?deleted=1');
            exit;
        }
    } else {
        http_response_code(400);
        exit('Action de gestion de compte invalide.');
    }
}

$accounts = $pdo->query(
    'SELECT id, username, nom, prenom, role, created_at
     FROM admins
     ORDER BY CASE role WHEN "admin" THEN 0 ELSE 1 END, username'
)->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comptes utilisateurs - École Beree</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="brand">
                <img class="school-logo" src="beree-logo.png" alt="Logo de l’École Beree">
                <span>École Beree<small>ESPACE ADMINISTRATION</small></span>
            </div>
            <div class="sidebar-label">ADMINISTRATION</div>
            <nav>
                <a href="dashboard.php">Tableau de bord</a>
                <a href="admissions.php">Admissions</a>
                <a href="messages.php">Messages</a>
                <a href="manage_data.php">Gérer les données</a>
                <a href="teachers.php">Enseignants</a>
                <a href="fees.php">Paiements</a>
                <a href="payroll.php">Salaires et paie</a>
                <a href="users.php">Comptes utilisateurs</a>
                <a href="logout.php">Déconnexion</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-topbar">
                <div>
                    <span class="dashboard-overline">ACCÈS AU SYSTÈME</span>
                    <h1 class="page-title">Comptes utilisateurs</h1>
                </div>
                <div class="dashboard-user">
                    <span class="user-avatar">B</span>
                    <span>Bonjour, <strong><?= htmlspecialchars($_SESSION['admin_username'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                </div>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert <?= $messageType === 'success' ? 'success' : 'error' ?>" role="status">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <section class="paper-panel user-management-panel">
                <div class="section-header">
                    <div>
                        <p class="eyebrow">Accès scolaire uniquement</p>
                        <h2>Créer un compte gestionnaire</h2>
                    </div>
                </div>
                <p class="user-role-description">
                    Les gestionnaires peuvent gérer les admissions, messages, élèves, classes, notes, présences et emplois du temps.
                    Ils n’ont pas accès aux paiements, aux salaires, aux enseignants ni à la gestion générale des données.
                </p>
                <form method="post" action="users.php" class="user-create-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="create_manager">
                    <div class="grid">
                        <div class="field">
                            <label for="username">Nom d’utilisateur *</label>
                            <input id="username" name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9._\-]+" autocomplete="username" required>
                        </div>
                        <div class="field">
                            <label for="nom">Nom *</label>
                            <input id="nom" name="nom" maxlength="100" required>
                        </div>
                        <div class="field">
                            <label for="prenom">Prénom</label>
                            <input id="prenom" name="prenom" maxlength="100">
                        </div>
                        <div class="field">
                            <label for="password">Mot de passe temporaire *</label>
                            <input id="password" type="password" name="password" minlength="12" autocomplete="new-password" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Créer le gestionnaire</button>
                </form>
            </section>

            <section class="paper-panel user-account-list">
                <div class="section-header">
                    <div>
                        <p class="eyebrow">Accès actifs</p>
                        <h2>Comptes enregistrés</h2>
                    </div>
                    <span class="payroll-count"><?= count($accounts) ?> compte(s)</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom d’utilisateur</th>
                                <th>Nom</th>
                                <th>Rôle</th>
                                <th>Créé le</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                                <tr>
                                    <td><?= htmlspecialchars($account['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(trim(($account['prenom'] ?? '') . ' ' . ($account['nom'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $account['role'] === 'admin' ? 'Administrateur' : 'Gestionnaire scolaire' ?></td>
                                    <td><?= htmlspecialchars($account['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($account['role'] === 'gestionnaire'): ?>
                                            <form method="post" action="users.php">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="action" value="delete_manager">
                                                <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                                <button type="submit" class="btn-danger">Révoquer l’accès</button>
                                            </form>
                                        <?php else: ?>
                                            <span>Compte principal</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>

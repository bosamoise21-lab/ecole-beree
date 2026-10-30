<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    if ($deleteId > 0) {
        $pdo->prepare('DELETE FROM messages WHERE id = :id')->execute([':id' => $deleteId]);
        header('Location: messages.php');
        exit;
    }
}

$messages = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Messages - École Beree</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="brand">
                <img class="school-logo" src="beree-logo.png" alt="Logo de l’École Beree">
                <span>École Beree</span>
            </div>
            <nav>
                <a href="dashboard.php">Accueil</a>
                <a href="messages.php">Messages</a>
                <a href="admissions.php">Admissions</a>
                <a href="add_student.php">Ajouter un élève</a>
                <a href="classes.php">Classes</a>
                <a href="teachers.php">Enseignants</a>
                <a href="notes.php">Notes</a>
                <a href="attendance.php">Présences</a>
                <a href="fees.php">Paiements</a>
                <a href="timetable.php">Emploi du temps</a>
                <a href="logout.php">Déconnexion</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="section-header">
                <h1 class="page-title">Messages reçus</h1>
            </div>

            <div class="paper-panel">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Sujet</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td><?= (int)$message['id'] ?></td>
                                    <td><?= htmlspecialchars($message['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($message['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($message['sujet'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($message['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a href="mailto:<?= rawurlencode($message['email']) ?>?subject=Réponse%20École%20Beree" class="btn-success" style="padding:8px 10px; text-decoration:none;">Répondre</a>
                                        <a href="messages.php?delete=<?= (int)$message['id'] ?>" class="btn-danger" style="padding:8px 10px; text-decoration:none; margin-left:8px;">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

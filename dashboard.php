<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();

$stats = [
    'eleves' => (int) $pdo->query('SELECT COUNT(*) FROM eleves')->fetchColumn(),
    'enseignants' => (int) $pdo->query('SELECT COUNT(*) FROM enseignants')->fetchColumn(),
    'classes' => (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn(),
    'matieres' => (int) $pdo->query('SELECT COUNT(*) FROM matieres')->fetchColumn(),
];

$newAdmissions = $pdo->query(
    'SELECT id, nom, prenom, niveau_souhaite, created_at
     FROM admissions
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY created_at DESC'
)->fetchAll();

$newMessages = $pdo->query(
    'SELECT id, nom, sujet, created_at
     FROM messages
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY created_at DESC'
)->fetchAll();

$newStudents = $pdo->query(
    'SELECT id, matricule, nom, prenom, created_at
     FROM eleves
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY created_at DESC'
)->fetchAll();

$recentPayments = $pdo->query(
    'SELECT p.id, p.montant, p.type_paiement, p.date_paiement,
            CONCAT(e.prenom, " ", e.nom) AS eleve
     FROM paiements p
     INNER JOIN eleves e ON e.id = p.eleve_id
     WHERE p.date_paiement >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     ORDER BY p.date_paiement DESC, p.id DESC'
)->fetchAll();

$notifications = [];
foreach ($newAdmissions as $admission) {
    $notifications[] = [
        'type' => 'Admission',
        'personne' => trim($admission['prenom'] . ' ' . $admission['nom']),
        'detail' => 'Niveau demandé : ' . $admission['niveau_souhaite'],
        'date' => $admission['created_at'],
        'timestamp' => strtotime($admission['created_at']),
        'href' => 'admissions.php',
    ];
}
foreach ($newMessages as $message) {
    $notifications[] = [
        'type' => 'Message',
        'personne' => $message['nom'],
        'detail' => $message['sujet'],
        'date' => $message['created_at'],
        'timestamp' => strtotime($message['created_at']),
        'href' => 'messages.php',
    ];
}
foreach ($newStudents as $student) {
    $notifications[] = [
        'type' => 'Nouvel élève',
        'personne' => trim($student['prenom'] . ' ' . $student['nom']),
        'detail' => 'Nouveau dossier · ' . $student['matricule'],
        'date' => $student['created_at'],
        'timestamp' => strtotime($student['created_at']),
        'href' => 'student_profile.php?id=' . (int) $student['id'],
    ];
}
foreach ($recentPayments as $payment) {
    $notifications[] = [
        'type' => 'Paiement scolaire',
        'personne' => $payment['eleve'],
        'detail' => number_format((float) $payment['montant'], 2, ',', ' ') . ' CDF · ' . $payment['type_paiement'],
        'date' => $payment['date_paiement'],
        'timestamp' => strtotime($payment['date_paiement']),
        'href' => 'fees.php',
    ];
}
usort($notifications, static fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);
$notificationCount = count($notifications);

$eleves = $pdo->query(
    'SELECT e.matricule, e.nom, e.prenom, e.date_naissance, c.nom AS classe
     FROM eleves e
     LEFT JOIN inscriptions i ON i.eleve_id = e.id
     LEFT JOIN classes c ON c.id = i.classe_id
     ORDER BY e.nom, e.prenom'
)->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard admin - École Beree</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="brand">
                <img class="school-logo" src="beree-logo.png" alt="Logo de l’École Beree">
                <span>École Beree<small>ESPACE ADMINISTRATION</small></span>
            </div>
            <div class="sidebar-label">GESTION DE L’ÉCOLE</div>
            <nav>
                <a href="dashboard.php">Accueil</a>
                <a class="notification-nav-link" href="#notifications">Notifications <span class="notification-nav-count"><?= $notificationCount ?></span></a>
                <a href="manage_data.php">Gérer les données</a>
                <a href="messages.php">Messages</a>
                <a href="admissions.php">Admissions</a>
                <a href="add_student.php">Ajouter un élève</a>
                <a href="classes.php">Classes</a>
                <a href="teachers.php">Enseignants</a>
                <a href="manage_data.php?entity=employes">Employés</a>
                <a href="payroll.php">Salaires et paie</a>
                <a href="notes.php">Notes</a>
                <a href="attendance.php">Présences</a>
                <a href="fees.php">Paiements</a>
                <a href="timetable.php">Emploi du temps</a>
                <a href="student_profile.php">Profil élève</a>
                <a href="logout.php">Déconnexion</a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="dashboard-topbar">
                <div>
                    <span class="dashboard-overline">ESPACE DE GESTION</span>
                    <h1 class="page-title">Tableau de bord</h1>
                </div>
                <div class="dashboard-user"><span class="user-avatar">B</span><span>Bonjour, <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></strong></span></div>
            </div>

            <section class="dashboard-welcome">
                <div>
                    <p class="eyebrow">Tout commence ici</p>
                    <h2>Une belle journée à l’École Beree.</h2>
                    <p>Retrouvez en un coup d’œil les élèves, les classes et les équipes de l’établissement.</p>
                    <a href="manage_data.php" class="btn-primary">Gérer l’établissement <span aria-hidden="true">→</span></a>
                </div>
                <div class="welcome-symbol" aria-hidden="true">B</div>
            </section>

            <section class="section-grid dashboard-stats">
                <div class="stat-card"><span class="stat-icon">É</span><div><div class="label">Élèves inscrits</div><span class="number"><?= $stats['eleves'] ?></span></div><span class="stat-caption">Dossiers élèves</span></div>
                <div class="stat-card"><span class="stat-icon">P</span><div><div class="label">Enseignants</div><span class="number"><?= $stats['enseignants'] ?></span></div><span class="stat-caption">Équipe pédagogique</span></div>
                <div class="stat-card"><span class="stat-icon">C</span><div><div class="label">Classes</div><span class="number"><?= $stats['classes'] ?></span></div><span class="stat-caption">Niveaux scolaires</span></div>
                <div class="stat-card"><span class="stat-icon">M</span><div><div class="label">Matières</div><span class="number"><?= $stats['matieres'] ?></span></div><span class="stat-caption">Programmes enseignés</span></div>
            </section>

            <section id="notifications" class="paper-panel dashboard-notifications" aria-labelledby="notifications-title">
                <div class="section-header">
                    <div>
                        <p class="eyebrow">Admissions, messages, inscriptions et paiements</p>
                        <h2 id="notifications-title">Demandes récentes</h2>
                    </div>
                    <span class="notification-period">7 derniers jours · <?= $notificationCount ?> élément(s)</span>
                </div>

                <div class="notification-categories">
                    <a href="admissions.php" class="notification-category">
                        <span>Admissions</span>
                        <strong><?= count($newAdmissions) ?></strong>
                    </a>
                    <a href="messages.php" class="notification-category">
                        <span>Messages</span>
                        <strong><?= count($newMessages) ?></strong>
                    </a>
                    <a href="add_student.php" class="notification-category">
                        <span>Nouveaux élèves</span>
                        <strong><?= count($newStudents) ?></strong>
                    </a>
                    <a href="fees.php" class="notification-category">
                        <span>Paiements scolaires</span>
                        <strong><?= count($recentPayments) ?></strong>
                    </a>
                </div>
                <p class="notification-footnote">Les paiements sont classés selon leur date de versement.</p>

                <?php if ($notifications === []): ?>
                    <p class="notification-empty">Aucune nouvelle demande ni paiement récent.</p>
                <?php else: ?>
                    <ul class="notification-list">
                        <?php foreach (array_slice($notifications, 0, 10) as $notification): ?>
                            <li>
                                <span class="notification-marker" aria-hidden="true"></span>
                                <div class="notification-content">
                                    <strong><?= htmlspecialchars($notification['type'] . ' · ' . $notification['personne'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars($notification['detail'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <time datetime="<?= htmlspecialchars($notification['date'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($notification['date'], ENT_QUOTES, 'UTF-8') ?></time>
                                <a href="<?= htmlspecialchars($notification['href'], ENT_QUOTES, 'UTF-8') ?>" class="notification-open">Consulter</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <div class="paper-panel" style="margin-bottom: 24px;">
                <div class="section-header">
                    <h2>Actions rapides</h2>
                </div>
                <div class="hero-actions">
                    <a href="manage_data.php" class="btn-primary">Gérer toutes les données</a>
                    <a href="add_student.php" class="btn-primary">Ajouter un élève</a>
                    <a href="classes.php" class="btn-secondary">Gérer les classes</a>
                    <a href="teachers.php" class="btn-success">Gérer les enseignants</a>
                    <a href="payroll.php" class="btn-secondary">Salaires et paie</a>
                    <a href="notes.php" class="btn-warning">Notes</a>
                    <a href="attendance.php" class="btn-danger">Présences</a>
                </div>
            </div>

            <div class="paper-panel">
                <div class="section-header">
                    <div><p class="eyebrow">Vie scolaire</p><h2>Liste des élèves</h2></div>
                    <a href="add_student.php" class="btn-primary">Ajouter un élève <span aria-hidden="true">→</span></a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom</th>
                                <th>Date de naissance</th>
                                <th>Classe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eleves as $eleve): ?>
                                <tr>
                                    <td><?= htmlspecialchars($eleve['matricule'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(($eleve['prenom'] ?? '') . ' ' . ($eleve['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($eleve['date_naissance'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($eleve['classe'] ?? 'Non inscrit', ENT_QUOTES, 'UTF-8') ?></td>
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

<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDb();
$isAdmin = isAdminRole();

$stats = [
    'eleves' => (int) $pdo->query('SELECT COUNT(*) FROM eleves')->fetchColumn(),
    'enseignants' => (int) $pdo->query('SELECT COUNT(*) FROM enseignants')->fetchColumn(),
    'classes' => (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn(),
    'matieres' => (int) $pdo->query('SELECT COUNT(*) FROM matieres')->fetchColumn(),
];

$newAdmissions = $pdo->query(
    'SELECT id, nom, prenom, niveau_souhaite, created_at,
            UNIX_TIMESTAMP(created_at) AS notification_timestamp
     FROM admissions
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY created_at DESC'
)->fetchAll();

$newMessages = $pdo->query(
    'SELECT id, nom, sujet, created_at,
            UNIX_TIMESTAMP(created_at) AS notification_timestamp
     FROM messages
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY created_at DESC'
)->fetchAll();

$newStudents = $pdo->query(
    'SELECT id, matricule, nom, prenom, created_at,
            UNIX_TIMESTAMP(created_at) AS notification_timestamp
     FROM eleves
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY created_at DESC'
)->fetchAll();

$recentPayments = $isAdmin ? $pdo->query(
    'SELECT p.id, p.montant, p.type_paiement, p.trimestre, p.date_paiement,
            UNIX_TIMESTAMP(p.date_paiement) AS notification_timestamp,
            CONCAT(e.prenom, " ", e.nom) AS eleve
     FROM paiements p
     INNER JOIN eleves e ON e.id = p.eleve_id
     WHERE p.date_paiement >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     ORDER BY p.date_paiement DESC, p.id DESC'
)->fetchAll() : [];

$now = time();
$buildNotification = static function (
    string $category,
    string $type,
    string $personne,
    string $detail,
    string $date,
    string $href,
    int $timestamp
) use ($now): array {
    if ($timestamp <= 0) {
        throw new UnexpectedValueException('Date de notification invalide.');
    }

    $age = max(0, $now - $timestamp);
    if ($age < 60) {
        $timeAgo = 'À l’instant';
    } elseif ($age < 3600) {
        $minutes = (int) floor($age / 60);
        $timeAgo = 'Il y a ' . $minutes . ' min';
    } elseif ($age < 86400) {
        $hours = (int) floor($age / 3600);
        $timeAgo = 'Il y a ' . $hours . ' h';
    } elseif ($age < 604800) {
        $days = (int) floor($age / 86400);
        $timeAgo = 'Il y a ' . $days . ' j';
    } else {
        $weeks = (int) floor($age / 604800);
        $timeAgo = 'Il y a ' . $weeks . ' sem.';
    }

    $isDateOnly = preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $date) === 1;

    return [
        'category' => $category,
        'type' => $type,
        'personne' => $personne,
        'detail' => $detail,
        'date' => $date,
        'date_label' => date($isDateOnly ? 'd/m/Y' : 'd/m/Y à H:i', $timestamp),
        'time_ago' => $timeAgo,
        'timestamp' => $timestamp,
        'href' => $href,
    ];
};

$notifications = [];
foreach ($newAdmissions as $admission) {
    $notifications[] = $buildNotification(
        'admissions',
        'Admission',
        trim($admission['prenom'] . ' ' . $admission['nom']),
        'Niveau demandé : ' . $admission['niveau_souhaite'],
        $admission['created_at'],
        'admissions.php',
        (int) $admission['notification_timestamp']
    );
}
foreach ($newMessages as $message) {
    $notifications[] = $buildNotification(
        'messages',
        'Message',
        $message['nom'],
        $message['sujet'],
        $message['created_at'],
        'messages.php',
        (int) $message['notification_timestamp']
    );
}
foreach ($newStudents as $student) {
    $notifications[] = $buildNotification(
        'eleves',
        'Nouvel élève',
        trim($student['prenom'] . ' ' . $student['nom']),
        'Nouveau dossier · ' . $student['matricule'],
        $student['created_at'],
        'student_profile.php?id=' . (int) $student['id'],
        (int) $student['notification_timestamp']
    );
}
if ($isAdmin) {
    foreach ($recentPayments as $payment) {
        $notifications[] = $buildNotification(
            'paiements',
            'Paiement scolaire',
            $payment['eleve'],
            number_format((float) $payment['montant'], 2, ',', ' ') . ' CDF · ' . $payment['type_paiement']
                . ($payment['type_paiement'] === 'trimestre' ? ' · Trimestre ' . (int) $payment['trimestre'] : ''),
            $payment['date_paiement'],
            'fees.php',
            (int) $payment['notification_timestamp']
        );
    }
}
usort($notifications, static fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);
$notificationCount = count($notifications);
$notificationCategories = [
    'all' => ['label' => 'Tout', 'count' => $notificationCount],
    'admissions' => ['label' => 'Admissions', 'count' => count($newAdmissions)],
    'messages' => ['label' => 'Messages', 'count' => count($newMessages)],
    'eleves' => ['label' => 'Nouveaux élèves', 'count' => count($newStudents)],
];
if ($isAdmin) {
    $notificationCategories['paiements'] = [
        'label' => 'Paiements',
        'count' => count($recentPayments),
    ];
}

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
                <a href="messages.php">Messages</a>
                <a href="admissions.php">Admissions</a>
                <a href="add_student.php">Ajouter un élève</a>
                <a href="classes.php">Classes</a>
                <a href="notes.php">Notes</a>
                <a href="attendance.php">Présences</a>
                <a href="timetable.php">Emploi du temps</a>
                <a href="student_profile.php">Profil élève</a>
                <?php if ($isAdmin): ?>
                    <a href="manage_data.php">Gérer les données</a>
                    <a href="teachers.php">Enseignants</a>
                    <a href="manage_data.php?entity=employes">Employés</a>
                    <a href="payroll.php">Salaires et paie</a>
                    <a href="fees.php">Paiements</a>
                    <a href="users.php">Comptes utilisateurs</a>
                <?php endif; ?>
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
                    <a href="<?= $isAdmin ? 'manage_data.php' : 'add_student.php' ?>" class="btn-primary"><?= $isAdmin ? 'Gérer l’établissement' : 'Ajouter un élève' ?> <span aria-hidden="true">→</span></a>
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
                        <p class="eyebrow"><?= $isAdmin ? 'Admissions, messages, inscriptions et paiements' : 'Admissions, messages et inscriptions' ?></p>
                        <h2 id="notifications-title">Activité récente</h2>
                    </div>
                    <span class="notification-period">7 derniers jours · <?= $notificationCount ?> élément(s)</span>
                </div>

                <div class="notification-categories<?= $isAdmin ? '' : ' notification-categories-manager' ?>">
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
                    <?php if ($isAdmin): ?>
                        <a href="fees.php" class="notification-category">
                            <span>Paiements scolaires</span>
                            <strong><?= count($recentPayments) ?></strong>
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ($notifications === []): ?>
                    <p class="notification-empty">Aucune nouvelle demande ni activité récente.</p>
                <?php else: ?>
                    <div class="notification-filters" role="group" aria-label="Filtrer les notifications" hidden>
                        <?php foreach ($notificationCategories as $key => $category): ?>
                            <button
                                type="button"
                                class="notification-filter"
                                data-notification-filter="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>"
                            >
                                <?= htmlspecialchars($category['label'], ENT_QUOTES, 'UTF-8') ?>
                                <span><?= (int) $category['count'] ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <p class="notification-results" data-notification-results aria-live="polite"></p>
                    <ul class="notification-list">
                        <?php foreach ($notifications as $index => $notification): ?>
                            <li
                                data-notification-item
                                data-notification-category="<?= htmlspecialchars($notification['category'], ENT_QUOTES, 'UTF-8') ?>"
                                <?= $index >= 10 ? 'hidden' : '' ?>
                            >
                                <span class="notification-marker notification-marker-<?= htmlspecialchars($notification['category'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></span>
                                <div class="notification-content">
                                    <strong><?= htmlspecialchars($notification['type'] . ' · ' . $notification['personne'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars($notification['detail'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <time datetime="<?= htmlspecialchars($notification['date'], ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($notification['date_label'], ENT_QUOTES, 'UTF-8') ?>">
                                    <strong><?= htmlspecialchars($notification['time_ago'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars($notification['date_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                </time>
                                <a href="<?= htmlspecialchars($notification['href'], ENT_QUOTES, 'UTF-8') ?>" class="notification-open">Consulter <span aria-hidden="true">→</span></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="notification-empty notification-filter-empty" data-notification-empty hidden>Aucun élément dans cette catégorie pour les 7 derniers jours.</p>
                    <button type="button" class="notification-more" data-notification-more hidden>Afficher plus</button>
                <?php endif; ?>
            </section>

            <div class="paper-panel" style="margin-bottom: 24px;">
                <div class="section-header">
                    <h2>Actions rapides</h2>
                </div>
                <div class="hero-actions">
                    <a href="add_student.php" class="btn-primary">Ajouter un élève</a>
                    <a href="classes.php" class="btn-secondary">Gérer les classes</a>
                    <a href="notes.php" class="btn-warning">Notes</a>
                    <a href="attendance.php" class="btn-danger">Présences</a>
                    <?php if ($isAdmin): ?>
                        <a href="manage_data.php" class="btn-primary">Gérer toutes les données</a>
                        <a href="teachers.php" class="btn-success">Gérer les enseignants</a>
                        <a href="payroll.php" class="btn-secondary">Salaires et paie</a>
                    <?php endif; ?>
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
    <script>
        const notificationItems = Array.from(document.querySelectorAll('[data-notification-item]'));
        const notificationFilters = Array.from(document.querySelectorAll('[data-notification-filter]'));
        const notificationMore = document.querySelector('[data-notification-more]');
        const notificationEmpty = document.querySelector('[data-notification-empty]');
        const notificationResults = document.querySelector('[data-notification-results]');

        if (notificationItems.length > 0) {
            let activeNotificationFilter = 'all';
            let notificationLimit = 10;

            const updateNotifications = () => {
                const matchingItems = notificationItems.filter((item) =>
                    activeNotificationFilter === 'all'
                    || item.dataset.notificationCategory === activeNotificationFilter
                );

                matchingItems.forEach((item, index) => {
                    item.hidden = index >= notificationLimit;
                });
                notificationItems
                    .filter((item) => !matchingItems.includes(item))
                    .forEach((item) => { item.hidden = true; });

                if (notificationEmpty) {
                    notificationEmpty.hidden = matchingItems.length > 0;
                }
                if (notificationMore) {
                    notificationMore.hidden = matchingItems.length <= notificationLimit;
                }
                if (notificationResults) {
                    const shown = Math.min(matchingItems.length, notificationLimit);
                    notificationResults.textContent = `${shown} sur ${matchingItems.length} notification(s) affichée(s).`;
                    notificationResults.hidden = matchingItems.length === 0;
                }
            };

            notificationFilters.forEach((button) => {
                button.addEventListener('click', () => {
                    activeNotificationFilter = button.dataset.notificationFilter;
                    notificationLimit = 10;
                    notificationFilters.forEach((filter) => {
                        filter.setAttribute('aria-pressed', filter === button ? 'true' : 'false');
                    });
                    updateNotifications();
                });
            });

            notificationMore?.addEventListener('click', () => {
                notificationLimit += 10;
                updateNotifications();
            });

            document.querySelector('.notification-filters').hidden = false;
            updateNotifications();
        }
    </script>
</body>
</html>

<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $prenom = trim((string) ($_POST['prenom'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $telephone = trim((string) ($_POST['telephone'] ?? ''));
    $niveau = trim((string) ($_POST['niveau'] ?? ''));
    $messageText = trim((string) ($_POST['message'] ?? ''));

    if ($nom === '' || $prenom === '' || $email === '' || $niveau === '') {
        $message = 'Veuillez remplir tous les champs obligatoires.';
        $messageType = 'error';
    } else {
        try {
            $stmt = getDb()->prepare('INSERT INTO admissions (nom, prenom, email, telephone, niveau_souhaite, message) VALUES (:nom, :prenom, :email, :telephone, :niveau_souhaite, :message)');
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':telephone' => $telephone !== '' ? $telephone : null,
                ':niveau_souhaite' => $niveau,
                ':message' => $messageText !== '' ? $messageText : null,
            ]);

            $message = 'Votre demande d’admission a bien été enregistrée. Nous vous contacterons très bientôt.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Une erreur est survenue lors de l’enregistrement de votre demande : ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admission - École Beree</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-topbar">
        <div class="site-brand">
            <img class="school-logo" src="beree-logo.png" alt="Logo de l’École Beree">
            <span>École Beree</span>
        </div>
        <nav class="site-nav">
            <a href="index.php">Accueil</a>
            <a href="about.php">À propos</a>
            <a href="contact.php">Contact</a>
            <a href="admission.php">Admission</a>
            <a href="login.php" class="btn-primary">Connexion admin</a>
        </nav>
    </header>

    <main class="container">
        <section class="hero-section">
            <h1>Admission 2025-2026</h1>
            <p>Rejoignez École Beree et donnez à votre enfant les meilleurs outils pour réussir son avenir.</p>
            <div class="hero-actions">
                <a href="contact.php" class="btn-primary">Nous contacter</a>
                <a href="index.php" class="btn-secondary">Retour à l'accueil</a>
            </div>
        </section>

        <section class="paper-panel">
            <?php if ($message !== ''): ?>
                <div class="alert <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="admission.php">
                <div class="grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px;">
                    <div class="field">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                    <div class="field">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" required>
                    </div>
                    <div class="field">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="field">
                        <label for="telephone">Téléphone</label>
                        <input type="text" id="telephone" name="telephone">
                    </div>
                    <div class="field">
                        <label for="niveau">Niveau souhaité *</label>
                        <select id="niveau" name="niveau" required>
                            <option value="">Choisir</option>
                            <option value="Maternelle">Maternelle</option>
                            <option value="Primaire">Primaire</option>
                            <option value="Collège">Collège</option>
                            <option value="Lycée">Lycée</option>
                        </select>
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" placeholder="Décrivez votre demande..."></textarea>
                    </div>
                </div>

                <div class="form-actions" style="margin-top:20px;">
                    <button type="submit" class="btn-primary">Soumettre ma demande</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>

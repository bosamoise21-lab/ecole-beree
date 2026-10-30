<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $sujet = trim((string) ($_POST['sujet'] ?? ''));
    $messageText = trim((string) ($_POST['message'] ?? ''));

    if ($nom === '' || $email === '' || $sujet === '' || $messageText === '') {
        $message = 'Veuillez remplir tous les champs du formulaire.';
        $messageType = 'error';
    } else {
        try {
            $stmt = getDb()->prepare('INSERT INTO messages (nom, email, sujet, message) VALUES (:nom, :email, :sujet, :message)');
            $stmt->execute([
                ':nom' => $nom,
                ':email' => $email,
                ':sujet' => $sujet,
                ':message' => $messageText,
            ]);

            $message = 'Votre message a bien été envoyé. Nous vous répondrons très bientôt.';
            $messageType = 'success';
        } catch (Throwable $e) {
            $message = 'Une erreur est survenue lors de l’envoi du message : ' . $e->getMessage();
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
    <title>Contact - École Beree</title>
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
            <a href="contact_form.php">Nous écrire</a>
            <a href="login.php" class="btn-primary">Connexion admin</a>
        </nav>
    </header>

    <main class="container">
        <section class="hero-section">
            <h1>Nous écrire</h1>
            <p>Posez une question, demandez un renseignement ou contactez-nous pour toute préoccupation.</p>
        </section>

        <section class="paper-panel">
            <?php if ($message !== ''): ?>
                <div class="alert <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="contact_form.php">
                <div class="grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px;">
                    <div class="field">
                        <label for="nom">Nom complet *</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                    <div class="field">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="sujet">Sujet *</label>
                        <input type="text" id="sujet" name="sujet" required>
                    </div>
                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required placeholder="Décrivez votre demande..."></textarea>
                    </div>
                </div>

                <div class="form-actions" style="margin-top:20px;">
                    <button type="submit" class="btn-primary">Envoyer le message</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>

<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>À propos - École Beree</title>
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
            <h1>Une école tournée vers l’excellence</h1>
            <p>École Beree accompagne chaque élève dans son apprentissage, son épanouissement et son développement personnel, avec des valeurs de discipline, de savoir et d’inclusion.</p>
            <div class="hero-actions">
                <a class="btn-primary" href="login.php">Accéder au portail</a>
                <a class="btn-secondary" href="index.php">Retour à l’accueil</a>
            </div>
        </section>

        <section class="feature-grid">
            <article class="feature-card">
                <h3>Mission</h3>
                <p>Former des jeunes responsables, curieux et prêts à construire un avenir solide.</p>
            </article>
            <article class="feature-card">
                <h3>Vision</h3>
                <p>Devenir une référence locale dans la qualité éducative et la réussite scolaire.</p>
            </article>
            <article class="feature-card">
                <h3>Valeurs</h3>
                <p>Discipline, respect, excellence, innovation, esprit d’équipe.</p>
            </article>
        </section>
    </main>
</body>
</html>

<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
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
            <h1>Contactez l'École Beree</h1>
            <p>Nous sommes à votre écoute pour toute information concernant l’admission, les inscriptions, les frais scolaires ou le suivi des élèves.</p>
            <div class="hero-actions">
                <a class="btn-primary" href="mailto:contact@ecoleberee.edu">contact@ecoleberee.edu</a>
                <a class="btn-secondary" href="tel:+243898530817">+243898530817</a>
            </div>
        </section>

        <section class="feature-grid">
            <article class="feature-card">
                <h3>Adresse</h3>
                <p>Rue de l’Éducation, Dakar, Sénégal</p>
            </article>
            <article class="feature-card">
                <h3>Heures d’ouverture</h3>
                <p>Lundi - Vendredi : 7h30 - 17h30</p>
            </article>
            <article class="feature-card">
                <h3>Support</h3>
                <p>Pour les parents, élèves et partenaires.</p>
            </article>
        </section>
    </main>
</body>
</html>

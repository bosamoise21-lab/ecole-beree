<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>École Beree | Accueil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="home-page">
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
        <section class="home-hero">
            <div class="home-hero-content">
                <p class="eyebrow">École Beree · Année scolaire 2026–2027</p>
                <h1>Le meilleur départ pour son avenir.</h1>
                <p>Un enseignement exigeant, un accompagnement attentif et un environnement où chaque élève peut révéler son potentiel.</p>
                <a class="btn-primary" href="admission.php">Découvrir les admissions <span aria-hidden="true">→</span></a>
            </div>
            <div class="home-hero-note">
                <span class="hero-note-mark" aria-hidden="true">✦</span>
                <span><strong>Grandir avec confiance</strong><small>Apprendre · s’épanouir · réussir</small></span>
            </div>
        </section>

        <section class="admission-search" aria-label="Découvrir les parcours scolaires">
            <div>
                <span class="search-label">Vous cherchez une école ?</span>
                <strong>Trouvez le parcours qui lui correspond.</strong>
            </div>
            <a class="search-field" href="about.php"><span>Nos programmes</span><strong>Primaire, collège et lycée</strong></a>
            <a class="search-field" href="contact.php"><span>Nous trouver</span><strong>Dakar, Sénégal</strong></a>
            <a class="search-submit" href="admission.php" aria-label="Faire une demande d'admission">→</a>
        </section>

        <section class="home-intro">
            <div>
                <p class="eyebrow">Bienvenue à l’École Beree</p>
                <h2>Une école où chaque talent trouve sa voie.</h2>
            </div>
            <p>Des bases solides, des enseignants engagés et une communauté bienveillante : nous accompagnons les élèves à chaque étape de leur parcours.</p>
        </section>

        <section class="section-grid">
            <div class="stat-card">
                <div class="label">Élèves</div>
                <span class="number">1 200+</span>
            </div>
            <div class="stat-card">
                <div class="label">Classes</div>
                <span class="number">42</span>
            </div>
            <div class="stat-card">
                <div class="label">Enseignants</div>
                <span class="number">65</span>
            </div>
            <div class="stat-card">
                <div class="label">Taux de réussite</div>
                <span class="number">96%</span>
            </div>
        </section>

        <section class="feature-grid">
            <article class="feature-card">
                <h3>Apprentissage solide</h3>
                <p>Une pédagogie claire et structurée qui aide les élèves à développer leurs compétences de manière progressive.</p>
            </article>
            <article class="feature-card">
                <h3>Suivi personnalisé</h3>
                <p>Chaque apprenant bénéficie d’un accompagnement sur mesure pour progresser à son rythme.</p>
            </article>
            <article class="feature-card">
                <h3>Vie scolaire inspirante</h3>
                <p>Des clubs, manifestations et activités conviviales pour encourager l’épanouissement et la curiosité.</p>
            </article>
        </section>

        <section class="home-section school-gallery" aria-labelledby="school-gallery-title">
            <div class="section-heading">
                <p class="eyebrow">La vie à l’école</p>
                <h2 id="school-gallery-title">Apprendre, partager, grandir ensemble.</h2>
            </div>
            <div class="school-gallery-grid">
                <figure class="school-gallery-card">
                    <img src="eleve-etude-campus.jpg" alt="Un élève étudie à l’extérieur du campus" loading="lazy">
                    <figcaption>Le goût d’apprendre, partout.</figcaption>
                </figure>
                <figure class="school-gallery-card">
                    <img src="etudiants-etude-groupe.jpg" alt="Des élèves étudient et échangent ensemble" loading="lazy">
                    <figcaption>Progresser ensemble.</figcaption>
                </figure>
                <figure class="school-gallery-card">
                    <img src="eleve-portrait.jpg" alt="Une élève souriante avec ses livres" loading="lazy">
                    <figcaption>Chaque élève a son avenir.</figcaption>
                </figure>
            </div>
        </section>

        <section class="home-section">
            <div class="section-heading">
                <p class="eyebrow">Pourquoi nous choisir ?</p>
                <h2>Une école qui met l’élève au centre de tout.</h2>
            </div>

            <div class="story-grid">
                <div class="story-text">
                    <p>Notre école accompagne les jeunes dans leur développement intellectuel, social et citoyen. Nous créons un environnement stimulant où chacun trouve sa place, ose s’exprimer et avance avec confiance.</p>
                    <ul class="check-list">
                        <li>Un cadre sécurisé et bienveillant</li>
                        <li>Des enseignants passionnés et engagés</li>
                        <li>Des valeurs de discipline, de respect et d’excellence</li>
                    </ul>
                </div>

                <div class="story-panel">
                    <h3>Notre promesse</h3>
                    <p>Donner à chaque élève les outils nécessaires pour apprendre, s’épanouir et réussir dans la vie.</p>
                </div>
            </div>
        </section>

        <section class="home-section">
            <div class="section-heading">
                <p class="eyebrow">Nos parcours</p>
                <h2>Des programmes adaptés à chaque étape.</h2>
            </div>

            <div class="program-grid">
                <article class="program-card">
                    <div class="program-icon">📚</div>
                    <h3>Primaire</h3>
                    <p>Une base solide en français, maths, sciences et culture pour construire confiance et curiosité.</p>
                </article>
                <article class="program-card">
                    <div class="program-icon">🧠</div>
                    <h3>Collège</h3>
                    <p>Un accompagnement structuré autour des fondamentaux et de l’acquisition d’autonomie.</p>
                </article>
                <article class="program-card">
                    <div class="program-icon">🎓</div>
                    <h3>Lycée</h3>
                    <p>Des parcours de préparation à la réussite académique et à l’orientation de demain.</p>
                </article>
            </div>
        </section>

        <section class="home-section">
            <div class="section-heading">
                <p class="eyebrow">Ils nous font confiance</p>
                <h2>Quelques mots de nos familles.</h2>
            </div>

            <div class="testimonial-grid">
                <blockquote class="testimonial">
                    <p>“L’école a su accompagner notre fille avec douceur et exigence. Elle est plus sereine, motivée et confiante.”</p>
                    <footer>— Mme. Koffi, parent</footer>
                </blockquote>
                <blockquote class="testimonial">
                    <p>“Le cadre est excellent, les enseignants très disponibles et le suivi pédagogique vraiment sérieux.”</p>
                    <footer>— M. Diallo, parent</footer>
                </blockquote>
                <blockquote class="testimonial">
                    <p>“Nous apprécions la qualité de l’encadrement et les nombreuses activités qui enrichissent la vie scolaire.”</p>
                    <footer>— Mme. N’Goran, parent</footer>
                </blockquote>
            </div>
        </section>

        <section class="cta-banner">
            <div>
                <p class="eyebrow">Admissions 2026</p>
                <h2>Rejoignez l’École Beree dès aujourd’hui.</h2>
            </div>
            <a class="btn-primary" href="admission.php">Faire une demande d’inscription</a>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <div class="site-brand footer-brand">
                    <img class="school-logo" src="beree-logo.png" alt="Logo de l’École Beree">
                    <span>École Beree</span>
                </div>
                <p>Une école engagée pour une éducation de qualité, inclusive et inspirante.</p>
            </div>
            <div>
                <h3>Navigation</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="about.php">À propos</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="admission.php">Admission</a></li>
                </ul>
            </div>
            <div>
                <h3>Contact</h3>
                <ul class="footer-links">
                    <li>info@ecoleberee.com</li>
                    <li>+243898530817</li>
                    <li>Congo kinshasa</li>
                </ul>
            </div>
        </div>
    </footer>
    <script>
        (() => {
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduceMotion) return;

            const revealItems = document.querySelectorAll(
                '.home-page main > section:not(.home-hero), .home-page .home-hero-note, ' +
                '.home-page .feature-card, .home-page .school-gallery-card, ' +
                '.home-page .program-card, .home-page .testimonial, ' +
                '.home-page .story-text, .home-page .story-panel'
            );

            if (!('IntersectionObserver' in window)) return;

            revealItems.forEach((item) => {
                const siblings = item.parentElement.children;
                const siblingIndex = Array.prototype.indexOf.call(siblings, item);
                item.style.setProperty('--reveal-delay', `${Math.min(siblingIndex * 90, 360)}ms`);
                item.classList.add('home-reveal');
            });

            const observer = new IntersectionObserver((entries, currentObserver) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-visible');
                    currentObserver.unobserve(entry.target);
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -36px 0px' });

            revealItems.forEach((item) => observer.observe(item));
        })();
    </script>
</body>
</html>

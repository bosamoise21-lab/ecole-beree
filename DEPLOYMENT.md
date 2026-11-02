# Mise en ligne et exploitation

## État actuel

L’application est un site PHP/MySQL classique. Aucun hébergeur, domaine ou
environnement de production n’est encore configuré. Ce dépôt n’est pas à lui
seul un site public : il lui faut un hébergement PHP, une base SQL et un domaine
avec HTTPS.

## Hébergement requis

Choisir un hébergeur qui fournit :

- PHP 8.2 ou supérieur avec PDO MySQL et les extensions requises par PHP ;
- MySQL 8+ ou MariaDB 10.4+ ;
- HTTPS automatique (certificat TLS renouvelé) ;
- des tâches planifiées (cron ou Planificateur de tâches Windows) ;
- des sauvegardes téléchargeables hors de l’espace public ;
- l’accès aux journaux PHP et SQL et, de préférence, SSH/SFTP.

Ne pas utiliser le serveur intégré de PHP (`php -S`) comme serveur public de
production.

## Première mise en production

1. Réserver le domaine et créer un hébergement ainsi qu’une base de données
   dédiée. Créer un utilisateur SQL limité à cette base, avec un mot de passe
   unique.
2. Activer HTTPS avant d’ouvrir l’administration. Définir `APP_ENV=production`
   dans la configuration du serveur : les cookies de session seront alors
   marqués `Secure`.
3. Déployer une version vérifiée du dépôt avec Git/SSH ou SFTP. Ne jamais
   déployer `config.local.php`, `.env`, les sauvegardes, les journaux ou les
   exports contenant les données réelles.
4. Fournir `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` et `DB_PASS` par la
   configuration protégée de l’hébergeur, ou créer `config.local.php` hors de
   la racine publique. Ne pas placer les secrets dans Git ou dans une URL.
5. Pour une base **vide uniquement**, créer le schéma avec une procédure
   contrôlée et le fichier de schéma prévu. Ne jamais lancer `ecole.sql` sur
   une base qui contient des données : il supprime et recrée les tables.
6. Pour une base existante, sauvegarder d’abord la base, vérifier l’état des
   migrations déjà appliquées, puis appliquer chaque migration additive une
   seule fois. Ne pas exécuter à nouveau une migration déjà appliquée.
7. Créer ou vérifier le compte administrateur avec un mot de passe fort, puis
   créer des comptes gestionnaires distincts selon les responsabilités.
8. Vérifier en HTTPS : connexion/déconnexion, rôles, formulaires, admissions,
   messages, élèves, notes, frais, paie et accès mobile. Examiner les journaux
   après les tests.
9. Ne publier le domaine qu’après un test de restauration et une vérification
   que les fichiers de configuration et sauvegardes ne sont pas accessibles
   par HTTP.

## Sauvegarde de la base

Depuis le dossier du projet :

```powershell
php scripts\backup_database.php
```

Le script lit la configuration DB habituelle, utilise `mysqldump` et écrit
dans un dossier privé du compte système (`%LOCALAPPDATA%\ecole-beree\backups`
sous Windows ou `$XDG_STATE_HOME/ecole-beree/backups` / `~/.local/state`
sous Linux). Définir `MYSQLDUMP_PATH` si `mysqldump` n’est pas dans le `PATH`.
Un autre emplacement peut être fourni en argument ou via `APP_BACKUP_DIR`.
Les sauvegardes sont vérifiées pour leur taille et la présence des tables
principales ; un fichier de somme de contrôle SHA-256 est créé à côté.

Sur Windows, créer une tâche avec le Planificateur de tâches pour exécuter
quotidiennement `php.exe` avec `scripts\backup_database.php`, sous le même
compte système et avec les variables de configuration DB disponibles.
Sur un serveur Linux, planifier la même commande avec cron ou le service
équivalent de l’hébergeur.

La sauvegarde locale ne protège pas contre une panne ou la perte du serveur.
Copier les sauvegardes, de façon chiffrée, vers un stockage privé séparé,
restreindre l’accès au compte système, fixer une durée de conservation et
tester régulièrement la restauration sur une base de test. Ne jamais mettre
les fichiers SQL dans `public_html`, le dépôt Git, un partage public ou un
ticket d’assistance. Le script ne supprime pas automatiquement les anciennes
sauvegardes.

## Mises à jour de l’application

1. Tester les changements sur une copie de la base.
2. Sauvegarder la production et vérifier le fichier de sauvegarde.
3. Déployer une version identifiée du code.
4. Appliquer uniquement les migrations non encore exécutées.
5. Exécuter les vérifications PHP/tests, puis tester le site et consulter les
   journaux.
6. Conserver un moyen de revenir à la version précédente du code et restaurer
   les données uniquement à partir d’une sauvegarde vérifiée.

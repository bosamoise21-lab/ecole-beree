# École Beree

Application de gestion scolaire en PHP et MySQL.

## Configuration locale

1. Copiez `config.example.php` vers `config.local.php`.
2. Renseignez les paramètres de connexion MySQL dans `config.local.php`.
3. Créez la base de données et appliquez le schéma correspondant à votre installation.
4. Lancez l'application avec PHP depuis le dossier du projet :

   ```powershell
   php -S 127.0.0.1:8000
   ```

### Suivi des frais scolaires

Sur une base existante, sauvegardez d’abord la base puis exécutez
`migration_frais_scolaires.sql` une seule fois après la création des tables
scolaires. Cette migration ajoute les tarifs par classe et année ainsi que
l’affectation des paiements à une inscription. Elle associe automatiquement
les anciens paiements des élèves ayant une seule inscription ; les autres
paiements et les anciens paiements trimestriels doivent être vérifiés dans
**Frais scolaires** avant d’être comptés dans un solde.

Les nouveaux tarifs sont saisis par catégorie. Chaque trimestre a son propre
montant. Un paiement est rattaché à une classe et à une année scolaire, et ne
peut pas dépasser le solde de la catégorie correspondante.

Pour une nouvelle base, utilisez le schéma `ecole.sql`, qui inclut directement
ces tables et colonnes. **Ne relancez pas ce schéma sur une base contenant des
données : il supprime et recrée les tables.**

Les paramètres peuvent aussi être fournis par les variables d'environnement `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` et `DB_PASS`.

## Données et sécurité

Les configurations locales et les exports SQL ne doivent pas être publiés. Les fichiers de configuration et les sauvegardes de base de données sont exclus par `.gitignore`. Utilisez des comptes MySQL dédiés et ne placez pas de données réelles d'élèves dans un dépôt public.

## Sauvegardes et mise en ligne

Le script `scripts/backup_database.php` crée et vérifie une sauvegarde locale
avec `mysqldump` et un checksum SHA-256. Exécutez-le depuis PowerShell avec
`php scripts\backup_database.php`, puis planifiez son exécution quotidienne.
Les sauvegardes doivent rester hors de la racine publique et être copiées de
façon chiffrée vers un stockage séparé.

Consultez [DEPLOYMENT.md](./DEPLOYMENT.md) pour les prérequis, la procédure de
mise en production et les vérifications. Le dépôt n’est pas encore relié à un
hébergeur ni à un domaine de production.

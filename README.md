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

Les paramètres peuvent aussi être fournis par les variables d'environnement `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` et `DB_PASS`.

## Données et sécurité

Les configurations locales et les exports SQL ne doivent pas être publiés. Les fichiers de configuration et les sauvegardes de base de données sont exclus par `.gitignore`. Utilisez des comptes MySQL dédiés et ne placez pas de données réelles d'élèves dans un dépôt public.

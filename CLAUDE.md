# Éditeur partagé pour sites de boulangeries

- Voir README.md pour l'architecture. PHP 8.1+, aucune dépendance, pas de base de données.
- Ne jamais committer config.php, data/ (hors .htaccess), sites/ ni aucun identifiant FTP.
- Après toute modification de src/templates/, rappeler qu'il faut lancer `php bin/regenerer-tout.php` sur le serveur.
- Vérifier avec `php -l` chaque fichier PHP modifié.
- Textes de l'interface en français.

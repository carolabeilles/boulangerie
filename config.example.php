<?php
// Copiez ce fichier en « config.php » (au même niveau que les dossiers src/ et editeur/) et adaptez-le.
// Ce fichier ne doit JAMAIS être dans un dossier accessible depuis Internet.

return [
    // Dossier qui contient UN sous-dossier par boulangerie (index.html généré + images/).
    // Chaque nom de domaine de boulangerie doit pointer vers « sites/<identifiant> ».
    'sites_root' => __DIR__ . '/sites',

    // Données des boulangeries (JSON, mots de passe hachés, sessions). Jamais accessible depuis Internet.
    'data_root' => __DIR__ . '/data',

    // Adresse publique de l'éditeur (sans « / » final).
    'editeur_url' => 'https://editeur.exemple.fr',

    // Image « promo du moment » commune à tous les sites : vous remplacez ce fichier, tous les sites suivent.
    'promo_url' => 'https://editeur.exemple.fr/partage/promo.jpg',

    // Texte des mentions légales (hébergeur et réalisation).
    'hebergeur' => '[Nom et adresse de l’hébergeur]',
    'realisation' => '[Votre nom]',

    // Mention facultative affichée en pied de chaque site (laisser vide pour ne rien afficher).
    'signature' => 'Un service proposé par les Moulins Bourgeois',

    // true en production (HTTPS obligatoire). false uniquement pour un essai local en http.
    'cookie_secure' => true,

    // Protection contre les essais de mots de passe.
    'max_echecs' => 5,
    'blocage_minutes' => 15,

    // Déconnexion automatique après inactivité.
    'inactivite_minutes' => 120,
];

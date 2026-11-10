<?php
declare(strict_types=1);

// Réinitialise le mot de passe d'une boulangerie (mot de passe oublié).
// Usage : php bin/changer-mot-de-passe.php <identifiant> [--mdp=...]

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/src/lib.php';
config();

$slug = (string) ($argv[1] ?? '');
$mdp = null;
foreach (array_slice($argv, 2) as $a) {
    if (str_starts_with($a, '--mdp=')) {
        $mdp = substr($a, 6);
    }
}

$site = charger_site($slug);
if ($site === null) {
    fwrite(STDERR, "Usage : php bin/changer-mot-de-passe.php <identifiant> [--mdp=...]\nSite introuvable : « $slug ».\n");
    exit(1);
}
$mdp ??= mot_de_passe_aleatoire();
if (mb_strlen($mdp, 'UTF-8') < 10) {
    fwrite(STDERR, "Le mot de passe doit contenir au moins 10 caractères.\n");
    exit(1);
}

$site['password_hash'] = password_hash($mdp, PASSWORD_DEFAULT);
sauvegarder_site($site);
echo "Mot de passe de « $slug » réinitialisé : $mdp   (à transmettre au boulanger)\n";

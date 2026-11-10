<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/src/lib.php';

$pos = [];
$opt = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z]+)=(.*)$/s', $a, $m)) {
        $opt[$m[1]] = $m[2];
    } else {
        $pos[] = $a;
    }
}

if (count($pos) < 3) {
    fwrite(STDERR, <<<TXT
Usage :
  php bin/creer-site.php <identifiant> "<Nom de la boulangerie>" <https://adresse-du-site> [options]

Options :
  --mdp=...            mot de passe (sinon un mot de passe aléatoire est généré et affiché une seule fois)
  --hero=images/x.jpg  photo du bandeau (fichier à déposer dans sites/<identifiant>/images/)
  --galerie=images/a.jpg,images/b.jpg   photos des spécialités
  --editeur="..."      mentions légales : éditeur (raison sociale, adresse, SIRET…)
  --responsable="..."  mentions légales : responsable de la publication
  --contact="..."      mentions légales : e-mail / téléphone

TXT);
    exit(1);
}

[$slug, $nom, $url] = $pos;
config();

if (!slug_valide($slug)) {
    fwrite(STDERR, "Identifiant invalide : lettres minuscules, chiffres et tirets (2 à 41 caractères).\n");
    exit(1);
}
if (!preg_match('#^https://[^\s]+$#i', $url)) {
    fwrite(STDERR, "L'adresse du site doit commencer par https://\n");
    exit(1);
}
if (charger_site($slug) !== null) {
    fwrite(STDERR, "Un site « $slug » existe déjà.\n");
    exit(1);
}

$mdp = $opt['mdp'] ?? mot_de_passe_aleatoire();
if (mb_strlen($mdp, 'UTF-8') < 10) {
    fwrite(STDERR, "Le mot de passe doit contenir au moins 10 caractères.\n");
    exit(1);
}

$site = site_par_defaut($slug, nettoyer_texte($nom, 80), $url);
$site['password_hash'] = password_hash($mdp, PASSWORD_DEFAULT);

if (isset($opt['hero'])) {
    if (!image_valide($opt['hero'])) {
        fwrite(STDERR, "Chemin de photo invalide : " . $opt['hero'] . " (attendu : images/nom.jpg)\n");
        exit(1);
    }
    $site['images']['hero'] = $opt['hero'];
}
if (isset($opt['galerie'])) {
    foreach (array_filter(array_map('trim', explode(',', $opt['galerie']))) as $p) {
        if (!image_valide($p)) {
            fwrite(STDERR, "Chemin de photo invalide : $p (attendu : images/nom.jpg)\n");
            exit(1);
        }
        $site['images']['galerie'][] = $p;
    }
}
foreach (['editeur', 'responsable', 'contact'] as $k) {
    if (isset($opt[$k])) {
        $site['legal'][$k] = nettoyer_texte($opt[$k], 400, true);
    }
}

assurer_dossier(site_dir($slug) . '/images', 0755);
sauvegarder_site($site);

echo "Site créé.\n";
echo "  Identifiant : $slug\n";
echo "  Mot de passe : $mdp   (à transmettre au boulanger, il ne sera plus affiché)\n";
echo "  Dossier du site : " . site_dir($slug) . "\n";
echo "  Déposez les photos dans : " . site_dir($slug) . "/images/\n";
echo "  Pointez le nom de domaine vers ce dossier.\n";

<?php
declare(strict_types=1);

// Remet le site de démonstration dans son état d'origine (à lancer chaque nuit par une tâche planifiée).
// Le site « demo » doit d'abord être créé avec bin/creer-site.php. Son mot de passe est conservé.

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/src/lib.php';
config();

$slug = $argv[1] ?? 'demo';
$site = charger_site($slug);
if ($site === null) {
    fwrite(STDERR, "Le site « $slug » n'existe pas. Créez-le d'abord avec bin/creer-site.php.\n");
    exit(1);
}

$neuf = site_par_defaut($slug, (string) $site['nom'], (string) $site['url']);
$neuf['password_hash'] = $site['password_hash'];
$neuf['images'] = $site['images'];
$neuf['legal'] = $site['legal'];
$neuf['accroche'] = 'Pain au levain, viennoiseries et pâtisseries, faits maison chaque jour.';
$neuf['intro'] = 'Baguette tradition, pain de campagne, croissants au beurre, tartes de saison. Commandes sur mesure possibles.';
$neuf['horaires']['samedi'] = ['ferme' => false, 'plages' => [['07:00', '13:00'], ['15:30', '19:30']]];
$neuf['horaires']['dimanche'] = ['ferme' => false, 'plages' => [['07:00', '13:00']]];
$neuf['note'] = 'Fermé les jours fériés.';
$neuf['adresse'] = "12 rue de la Boulangerie\n75000 Ville";
$neuf['telephone'] = '01 23 45 67 89';
$neuf['paiements'] = 'Carte bancaire, espèces';
$neuf['infos_pratiques'] = 'Commandes sur mesure : pièces montées, galettes, bûches. Merci de commander 48 h à l’avance.';

sauvegarder_site($neuf);
echo "Site de démonstration « $slug » réinitialisé.\n";

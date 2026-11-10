<?php
declare(strict_types=1);

// Régénère le site de chaque boulangerie. À lancer après une modification du gabarit
// (src/templates/*.php) ou du design. Compatible avec une tâche planifiée OVH.

if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/src/lib.php';
config();

$n = 0;
foreach (glob(rtrim((string) config()['data_root'], '/') . '/*/site.json') ?: [] as $fichier) {
    $slug = basename(dirname($fichier));
    if (str_starts_with($slug, '_')) {
        continue;
    }
    $site = charger_site($slug);
    if ($site === null) {
        fwrite(STDERR, "Ignoré (illisible) : $slug\n");
        continue;
    }
    publier_site($site);
    echo "OK  $slug\n";
    $n++;
}
echo "$n site(s) régénéré(s).\n";

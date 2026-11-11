<?php
/** @var array $s Données de la boulangerie */
/** @var array $cfg Configuration */

$legal = (array) ($s['legal'] ?? []);
$champ = function (string $cle) use ($legal): string {
    $v = trim((string) ($legal[$cle] ?? ''));
    return $v !== '' ? nl2br(h($v)) : '<em>[à compléter]</em>';
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Mentions légales – <?= h((string) $s['nom']) ?></title>
<style>
body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;line-height:1.6;color:#2a2a2a;background:#fff}
main{max-width:42rem;margin:0 auto;padding:2rem 1rem}
h1,h2{font-family:Georgia,"Times New Roman",serif;line-height:1.2;color:#8c2634}
a{color:#8c2634}
</style>
</head>
<body>
<main>
<h1>Mentions légales</h1>

<h2>Éditeur du site</h2>
<p><?= $champ('editeur') ?></p>

<h2>Responsable de la publication</h2>
<p><?= $champ('responsable') ?></p>

<h2>Contact</h2>
<p><?= $champ('contact') ?></p>

<h2>Hébergeur</h2>
<p><?= nl2br(h((string) $cfg['hebergeur'])) ?></p>

<h2>Réalisation du site</h2>
<p><?= h((string) $cfg['realisation']) ?></p>

<h2>Données personnelles et cookies</h2>
<p>Ce site ne dépose aucun cookie et ne collecte aucune donnée personnelle. Les liens vers des services externes (Google Maps, Google) relèvent des conditions de ces services.</p>

<p><a href="index.html">&larr; Retour au site</a></p>
</main>
</body>
</html>

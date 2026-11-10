<?php
/** @var array $s Données de la boulangerie */
/** @var array $cfg Configuration */

$nom = (string) ($s['nom'] ?? '');
$hero = (string) ($s['images']['hero'] ?? '');
$hero = image_valide($hero) ? $hero : '';
$galerie = array_values(array_filter((array) ($s['images']['galerie'] ?? []), fn($p) => is_string($p) && image_valide($p)));
$tel = (string) ($s['telephone'] ?? '');
$adresse = (string) ($s['adresse'] ?? '');
$info = (array) ($s['info'] ?? []);
$ferm = (array) ($s['fermeture'] ?? []);
$fermActive = !empty($ferm['actif']) && date_valide((string) ($ferm['debut'] ?? '')) && date_valide((string) ($ferm['fin'] ?? ''));
$fermMsg = trim((string) ($ferm['message'] ?? '')) !== '' ? (string) $ferm['message'] : 'Fermé exceptionnellement';
$description = trim((string) ($s['accroche'] ?? ''));

$jsHoraires = [];
foreach (JOURS as $j) {
    $d = (array) ($s['horaires'][$j] ?? []);
    $jsHoraires[$j] = !empty($d['ferme']) ? [] : array_values((array) ($d['plages'] ?? []));
}
$jsData = [
    'horaires' => $jsHoraires,
    'fermeture' => [
        'actif' => $fermActive,
        'debut' => $fermActive ? $ferm['debut'] : '',
        'fin' => $fermActive ? $ferm['fin'] : '',
        'message' => $fermMsg,
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($nom) ?></title>
<?php if ($description !== ''): ?><meta name="description" content="<?= h($description) ?>">
<?php endif; ?>
<style>
:root{--fond:#fffaf3;--texte:#3b2a1e;--doux:#5a4636;--accent:#b5651d;--fonce:#8a4a12;--creme:#f3e6d3;--ok:#2e7d32;--ko:#b3261e}
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;line-height:1.6;color:var(--texte);background:var(--fond)}
img{max-width:100%;height:auto;display:block}
a{color:var(--fonce)}
h1,h2,h3{font-family:Georgia,"Times New Roman",serif;line-height:1.2}
.alerte{background:var(--fonce);color:#fff;text-align:center;padding:.75rem 1rem;font-weight:600;margin:0}
.actions{position:sticky;top:0;z-index:10;display:flex;gap:.5rem;justify-content:center;padding:.5rem;background:var(--fond);border-bottom:1px solid var(--creme)}
.btn{display:inline-block;padding:.6rem 1.2rem;border-radius:999px;background:var(--accent);color:#fff;text-decoration:none;font-weight:600}
.btn-2{background:var(--texte)}
.hero{background:var(--creme)}
.hero img{width:100%;aspect-ratio:2/1;max-height:60vh;object-fit:cover}
.hero-txt{padding:1.5rem 1rem 2rem;max-width:42rem;margin:0 auto;text-align:center}
.hero-txt h1{margin:0 0 .5rem;font-size:clamp(1.8rem,6vw,3rem)}
.bloc{max-width:52rem;margin:0 auto;padding:2.5rem 1rem}
.bloc h2{margin-top:0;font-size:1.8rem}
.promo{max-width:52rem;margin:0 auto;padding:1.5rem 1rem 0}
.promo img{width:100%;border-radius:.75rem}
.galerie{display:grid;grid-template-columns:repeat(auto-fill,minmax(14rem,1fr));gap:.75rem}
.galerie img{border-radius:.5rem;width:100%;aspect-ratio:4/3;object-fit:cover}
.horaires{width:100%;max-width:26rem;border-collapse:collapse}
.horaires th,.horaires td{text-align:left;padding:.35rem .5rem;border-bottom:1px solid var(--creme)}
.horaires tr.aujourdhui{background:var(--creme);font-weight:600}
.statut{font-weight:700;margin:0 0 .75rem}
.statut.ouvert{color:var(--ok)}
.statut.ferme{color:var(--ko)}
.note{font-style:italic;color:var(--doux)}
.infos{display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:1rem 2rem}
.infos h3{margin:0 0 .25rem;font-size:1.1rem}
.infos p{margin:0}
address{font-style:normal}
.pied{text-align:center;padding:1.5rem 1rem;background:var(--creme);font-size:.9rem}
.pied p{margin:0}
</style>
</head>
<body>
<?php if (!empty($info['actif']) && trim((string) ($info['texte'] ?? '')) !== ''): ?>
<p class="alerte" role="status"><?= h((string) $info['texte']) ?></p>
<?php endif; ?>
<p id="fermeture" class="alerte" role="status" hidden></p>
<?php if ($fermActive): ?>
<noscript><p class="alerte"><?= h($fermMsg) ?> (du <?= h(date_courte((string) $ferm['debut'])) ?> au <?= h(date_courte((string) $ferm['fin'])) ?>)</p></noscript>
<?php endif; ?>

<header class="hero" id="accueil">
<?php if ($hero !== ''): ?>
<img src="<?= h($hero) ?>" alt="<?= h($nom) ?>" fetchpriority="high">
<?php endif; ?>
<div class="hero-txt">
<h1><?= h($nom) ?></h1>
<?php if ($description !== ''): ?><p><?= h($description) ?></p>
<?php endif; ?>
</div>
</header>

<?php if ($tel !== '' || $adresse !== ''): ?>
<nav class="actions" aria-label="Actions rapides">
<?php if ($tel !== ''): ?><a class="btn" href="tel:<?= h(tel_href($tel)) ?>">Appeler</a>
<?php endif; ?>
<?php if ($adresse !== ''): ?><a class="btn btn-2" rel="noopener" href="<?= h(maps_url($adresse)) ?>">Itinéraire</a>
<?php endif; ?>
</nav>
<?php endif; ?>

<main>
<section id="promo" class="promo" style="display:<?= !empty($s['promo']) ? 'block' : 'none' ?>">
<img src="<?= h((string) $cfg['promo_url']) ?>" alt="Promotion du moment" loading="lazy">
</section>

<?php if (trim((string) ($s['intro'] ?? '')) !== '' || $galerie): ?>
<section class="bloc" id="specialites">
<h2>Nos spécialités</h2>
<?php if (trim((string) ($s['intro'] ?? '')) !== ''): ?><p><?= nl2br(h((string) $s['intro'])) ?></p>
<?php endif; ?>
<?php if ($galerie): ?>
<div class="galerie">
<?php foreach ($galerie as $p): ?>
<img src="<?= h($p) ?>" alt="<?= h($nom) ?> : spécialité" loading="lazy">
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>
<?php endif; ?>

<section class="bloc" id="horaires">
<h2>Horaires d’ouverture</h2>
<p id="statut" class="statut" aria-live="polite"></p>
<table class="horaires">
<?php foreach (JOURS as $j): ?>
<tr data-jour="<?= h($j) ?>"><th scope="row"><?= h(ucfirst($j)) ?></th><td><?= h(texte_horaire((array) ($s['horaires'][$j] ?? []))) ?></td></tr>
<?php endforeach; ?>
</table>
<?php if (trim((string) ($s['note'] ?? '')) !== ''): ?><p class="note"><?= h((string) $s['note']) ?></p>
<?php endif; ?>
</section>

<section class="bloc" id="infos">
<h2>Nous trouver</h2>
<div class="infos">
<?php if ($adresse !== ''): ?>
<div><h3>Adresse</h3><address><?= nl2br(h($adresse)) ?></address><p><a rel="noopener" href="<?= h(maps_url($adresse)) ?>">Voir l’itinéraire</a></p></div>
<?php endif; ?>
<?php if ($tel !== ''): ?>
<div><h3>Téléphone</h3><p><a href="tel:<?= h(tel_href($tel)) ?>"><?= h($tel) ?></a></p></div>
<?php endif; ?>
<?php if (trim((string) ($s['paiements'] ?? '')) !== ''): ?>
<div><h3>Paiements acceptés</h3><p><?= h((string) $s['paiements']) ?></p></div>
<?php endif; ?>
<?php if (trim((string) ($s['infos_pratiques'] ?? '')) !== ''): ?>
<div><h3>Bon à savoir</h3><p><?= nl2br(h((string) $s['infos_pratiques'])) ?></p></div>
<?php endif; ?>
</div>
<?php if (trim((string) ($s['lien_avis'] ?? '')) !== ''): ?>
<p><a rel="noopener" href="<?= h((string) $s['lien_avis']) ?>">Laisser un avis sur Google</a></p>
<?php endif; ?>
</section>
</main>

<footer class="pied">
<p>&copy; <?= h(date('Y')) ?> <?= h($nom) ?> &middot; <a href="mentions-legales.html">Mentions légales</a></p>
</footer>

<script>
(function () {
  var D = <?= json_encode($jsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
  var statut = document.getElementById('statut');
  var barre = document.getElementById('fermeture');
  var f = new Intl.DateTimeFormat('fr-FR', {
    timeZone: 'Europe/Paris', weekday: 'long', year: 'numeric', month: '2-digit',
    day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
  });
  var p = {};
  f.formatToParts(new Date()).forEach(function (x) { p[x.type] = x.value; });
  var iso = p.year + '-' + p.month + '-' + p.day;
  var jour = p.weekday.toLowerCase();
  var minutes = (parseInt(p.hour, 10) % 24) * 60 + parseInt(p.minute, 10);
  var lignes = document.querySelectorAll('tr[data-jour]');
  for (var i = 0; i < lignes.length; i++) {
    if (lignes[i].getAttribute('data-jour') === jour) { lignes[i].className = 'aujourdhui'; }
  }
  var F = D.fermeture;
  if (F.actif && iso >= F.debut && iso <= F.fin) {
    barre.textContent = F.message;
    barre.hidden = false;
    statut.textContent = 'Fermé exceptionnellement';
    statut.className = 'statut ferme';
    return;
  }
  function enMinutes(h) { var t = h.split(':'); return parseInt(t[0], 10) * 60 + parseInt(t[1], 10); }
  var ouvert = (D.horaires[jour] || []).some(function (r) {
    return minutes >= enMinutes(r[0]) && minutes < enMinutes(r[1]);
  });
  statut.textContent = ouvert ? 'Ouvert actuellement' : 'Fermé actuellement';
  statut.className = 'statut ' + (ouvert ? 'ouvert' : 'ferme');
})();
</script>
</body>
</html>

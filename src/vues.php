<?php
declare(strict_types=1);

function page_editeur(string $titre, string $corps): string
{
    return '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex, nofollow">'
        . '<title>' . h($titre) . '</title>'
        . '<link rel="stylesheet" href="editeur.css"></head><body>' . $corps . '</body></html>';
}

function vue_message(string $message): string
{
    return '<main class="carte etroite"><p>' . h($message) . '</p></main>';
}

function vue_connexion(string $erreur, string $csrf, string $identifiant = ''): string
{
    ob_start(); ?>
<main class="carte etroite">
  <h1>Espace boulangerie</h1>
  <p class="aide">Connectez-vous pour modifier vos horaires et vos informations.</p>
  <?php if ($erreur !== ''): ?><p class="erreur" role="alert"><?= h($erreur) ?></p><?php endif; ?>
  <form method="post" autocomplete="on">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="action" value="connexion">
    <label>Identifiant
      <input name="identifiant" value="<?= h($identifiant) ?>" autocomplete="username" autocapitalize="none" spellcheck="false" required>
    </label>
    <label>Mot de passe
      <input type="password" name="mot_de_passe" autocomplete="current-password" required>
    </label>
    <button type="submit">Se connecter</button>
  </form>
</main>
<?php
    return page_editeur('Connexion', (string) ob_get_clean());
}

/** @param list<string> $erreurs */
function vue_edition(array $s, array $erreurs, string $flash, string $csrf, string $erreurMdp = ''): string
{
    $info = (array) ($s['info'] ?? []);
    $ferm = (array) ($s['fermeture'] ?? []);
    $coche = fn(bool $b): string => $b ? ' checked' : '';
    ob_start(); ?>
<header class="entete">
  <div>
    <h1><?= h((string) $s['nom']) ?></h1>
    <?php if (!empty($s['url'])): ?><a href="<?= h((string) $s['url']) ?>" target="_blank" rel="noopener">Voir mon site</a><?php endif; ?>
  </div>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="action" value="deconnexion">
    <button type="submit" class="secondaire">Se déconnecter</button>
  </form>
</header>

<main class="contenu">
  <?php if ($flash !== ''): ?><p class="succes" role="status"><?= h($flash) ?></p><?php endif; ?>
  <?php if ($erreurs): ?>
    <div class="erreur" role="alert">
      <p><strong>Rien n’a été enregistré. Merci de corriger :</strong></p>
      <ul><?php foreach ($erreurs as $m): ?><li><?= h($m) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" class="edition">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <input type="hidden" name="action" value="enregistrer">

    <section class="carte">
      <h2>Promo du moment</h2>
      <label class="case"><input type="checkbox" name="promo" value="1"<?= $coche(!empty($s['promo'])) ?>> Afficher l’affiche de la promotion sur mon site</label>
    </section>

    <section class="carte">
      <h2>Message d’information</h2>
      <p class="aide">Un bandeau en haut du site (par exemple : « Pensez à commander vos galettes »).</p>
      <label class="case"><input type="checkbox" name="info_actif" value="1"<?= $coche(!empty($info['actif'])) ?>> Afficher le message</label>
      <label>Texte du message
        <input name="info_texte" maxlength="200" value="<?= h((string) ($info['texte'] ?? '')) ?>">
      </label>
    </section>

    <section class="carte">
      <h2>Fermeture exceptionnelle</h2>
      <p class="aide">Congés, travaux… Le site indique « Fermé exceptionnellement » pendant ces dates, puis revient tout seul à la normale.</p>
      <label class="case"><input type="checkbox" name="fermeture_actif" value="1"<?= $coche(!empty($ferm['actif'])) ?>> Activer la fermeture</label>
      <div class="deux">
        <label>Du <input type="date" name="fermeture_debut" value="<?= h((string) ($ferm['debut'] ?? '')) ?>"></label>
        <label>Au (inclus) <input type="date" name="fermeture_fin" value="<?= h((string) ($ferm['fin'] ?? '')) ?>"></label>
      </div>
      <label>Message affiché
        <input name="fermeture_message" maxlength="160" placeholder="Fermé pour congés, réouverture le 18 août" value="<?= h((string) ($ferm['message'] ?? '')) ?>">
      </label>
    </section>

    <section class="carte">
      <h2>Horaires d’ouverture</h2>
      <p class="aide">Cochez « Fermé » pour un jour de repos. En cas de pause le midi, remplissez les deux plages.</p>
      <?php foreach (JOURS as $j):
          $d = (array) ($s['horaires'][$j] ?? ['ferme' => true, 'plages' => []]);
          $p = (array) ($d['plages'] ?? []);
          $b = (array) ($d['brut'] ?? [
              'o1' => $p[0][0] ?? '', 'c1' => $p[0][1] ?? '',
              'o2' => $p[1][0] ?? '', 'c2' => $p[1][1] ?? '',
          ]); ?>
        <fieldset class="jour">
          <legend><?= h(ucfirst($j)) ?></legend>
          <label class="case"><input type="checkbox" name="h[<?= h($j) ?>][ferme]" value="1"<?= $coche(!empty($d['ferme'])) ?>> Fermé</label>
          <div class="plages">
            <label>Ouvre <input type="time" name="h[<?= h($j) ?>][o1]" value="<?= h((string) $b['o1']) ?>"></label>
            <label>Ferme <input type="time" name="h[<?= h($j) ?>][c1]" value="<?= h((string) $b['c1']) ?>"></label>
            <label>Rouvre <input type="time" name="h[<?= h($j) ?>][o2]" value="<?= h((string) $b['o2']) ?>"></label>
            <label>Referme <input type="time" name="h[<?= h($j) ?>][c2]" value="<?= h((string) $b['c2']) ?>"></label>
          </div>
        </fieldset>
      <?php endforeach; ?>
      <label>Remarque permanente
        <input name="note" maxlength="160" placeholder="Fermé les jours fériés" value="<?= h((string) ($s['note'] ?? '')) ?>">
      </label>
    </section>

    <section class="carte">
      <h2>Textes</h2>
      <label>Phrase d’accroche
        <textarea name="accroche" rows="2" maxlength="200"><?= h((string) ($s['accroche'] ?? '')) ?></textarea>
      </label>
      <label>Présentation de vos spécialités
        <textarea name="intro" rows="4" maxlength="600"><?= h((string) ($s['intro'] ?? '')) ?></textarea>
      </label>
    </section>

    <section class="carte">
      <h2>Coordonnées</h2>
      <label>Adresse
        <textarea name="adresse" rows="3" maxlength="200"><?= h((string) ($s['adresse'] ?? '')) ?></textarea>
      </label>
      <label>Téléphone
        <input type="tel" name="telephone" maxlength="30" value="<?= h((string) ($s['telephone'] ?? '')) ?>">
      </label>
      <label>Moyens de paiement
        <input name="paiements" maxlength="160" value="<?= h((string) ($s['paiements'] ?? '')) ?>">
      </label>
      <label>Bon à savoir (parking, commandes sur mesure…)
        <textarea name="infos_pratiques" rows="4" maxlength="600"><?= h((string) ($s['infos_pratiques'] ?? '')) ?></textarea>
      </label>
      <label>Lien pour laisser un avis Google (facultatif)
        <input type="url" name="lien_avis" maxlength="300" placeholder="https://" value="<?= h((string) ($s['lien_avis'] ?? '')) ?>">
      </label>
    </section>

    <div class="barre"><button type="submit">Enregistrer</button></div>
  </form>

  <details class="carte"<?= $erreurMdp !== '' ? ' open' : '' ?>>
    <summary>Changer mon mot de passe</summary>
    <?php if ($erreurMdp !== ''): ?><p class="erreur" role="alert"><?= h($erreurMdp) ?></p><?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="mot_de_passe">
      <label>Mot de passe actuel <input type="password" name="actuel" autocomplete="current-password" required></label>
      <label>Nouveau mot de passe (10 caractères minimum) <input type="password" name="nouveau" autocomplete="new-password" minlength="10" required></label>
      <label>Confirmer le nouveau mot de passe <input type="password" name="confirmation" autocomplete="new-password" minlength="10" required></label>
      <button type="submit" class="secondaire">Changer le mot de passe</button>
    </form>
  </details>
</main>
<?php
    return page_editeur('Modifier mon site', (string) ob_get_clean());
}

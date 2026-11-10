<?php
declare(strict_types=1);

/** Nettoie un texte saisi : UTF-8 valide, sans caractères de contrôle, longueur limitée. */
function nettoyer_texte(mixed $v, int $max, bool $multiligne = false): string
{
    $v = is_string($v) ? $v : '';
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = preg_replace('/[^\P{C}\n]/u', '', $v) ?? '';
    if (!$multiligne) {
        $v = str_replace("\n", ' ', $v);
    }
    return mb_substr(trim($v), 0, $max, 'UTF-8');
}

function heure_valide(string $v): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v);
}

function date_valide(string $v): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $v);
    return $d !== false && $d->format('Y-m-d') === $v;
}

/**
 * Lit le formulaire d'édition. Retourne [site mis à jour, liste d'erreurs].
 * Rien n'est enregistré s'il y a des erreurs.
 *
 * @return array{0: array, 1: list<string>}
 */
function lire_formulaire(array $post, array $site): array
{
    $e = [];
    $s = $site;

    $s['accroche'] = nettoyer_texte($post['accroche'] ?? '', 200);
    $s['intro'] = nettoyer_texte($post['intro'] ?? '', 600, true);
    $s['note'] = nettoyer_texte($post['note'] ?? '', 160);
    $s['adresse'] = nettoyer_texte($post['adresse'] ?? '', 200, true);
    $s['paiements'] = nettoyer_texte($post['paiements'] ?? '', 160);
    $s['infos_pratiques'] = nettoyer_texte($post['infos_pratiques'] ?? '', 600, true);

    $tel = nettoyer_texte($post['telephone'] ?? '', 30);
    if ($tel !== '' && !preg_match('/^\+?[0-9][0-9 .()-]{4,28}$/', $tel)) {
        $e[] = 'Téléphone : utilisez uniquement des chiffres, des espaces, des points ou des tirets.';
    }
    $s['telephone'] = $tel;

    $avis = nettoyer_texte($post['lien_avis'] ?? '', 300);
    if ($avis !== '' && (!preg_match('#^https://#i', $avis) || filter_var($avis, FILTER_VALIDATE_URL) === false)) {
        $e[] = 'Lien d’avis Google : l’adresse doit commencer par https://';
    }
    $s['lien_avis'] = $avis;

    $infoActif = !empty($post['info_actif']);
    $infoTexte = nettoyer_texte($post['info_texte'] ?? '', 200);
    if ($infoActif && $infoTexte === '') {
        $e[] = 'Message d’information : écrivez le message ou décochez la case.';
    }
    $s['info'] = ['actif' => $infoActif, 'texte' => $infoTexte];

    $fermActif = !empty($post['fermeture_actif']);
    $debut = is_string($post['fermeture_debut'] ?? null) ? trim($post['fermeture_debut']) : '';
    $fin = is_string($post['fermeture_fin'] ?? null) ? trim($post['fermeture_fin']) : '';
    $debutOk = date_valide($debut);
    $finOk = date_valide($fin);
    if ($fermActif) {
        if (!$debutOk || !$finOk) {
            $e[] = 'Fermeture exceptionnelle : indiquez la date de début et la date de fin.';
        } elseif ($fin < $debut) {
            $e[] = 'Fermeture exceptionnelle : la date de fin doit être après la date de début.';
        }
    }
    $s['fermeture'] = [
        'actif' => $fermActif,
        'debut' => $debutOk ? $debut : '',
        'fin' => $finOk ? $fin : '',
        'message' => nettoyer_texte($post['fermeture_message'] ?? '', 160),
    ];

    $s['promo'] = !empty($post['promo']);

    $hp = is_array($post['h'] ?? null) ? $post['h'] : [];
    $horaires = [];
    foreach (JOURS as $j) {
        $p = is_array($hp[$j] ?? null) ? $hp[$j] : [];
        $brut = [];
        foreach (['o1', 'c1', 'o2', 'c2'] as $k) {
            $brut[$k] = is_string($p[$k] ?? null) ? substr(trim($p[$k]), 0, 5) : '';
        }
        $ferme = !empty($p['ferme']);
        $plages = [];
        $erreurJour = false;
        if (!$ferme) {
            $prec = '';
            foreach ([[1, 'o1', 'c1'], [2, 'o2', 'c2']] as [$n, $ko, $kc]) {
                $o = $brut[$ko];
                $c = $brut[$kc];
                if ($o === '' && $c === '') {
                    continue;
                }
                if (!heure_valide($o) || !heure_valide($c) || $o >= $c || ($prec !== '' && $o <= $prec)) {
                    $e[] = ucfirst($j) . ' : vérifiez la plage ' . $n . ' (ouverture avant fermeture, dans l’ordre).';
                    $erreurJour = true;
                    continue;
                }
                $plages[] = [$o, $c];
                $prec = $c;
            }
            if (!$plages && !$erreurJour) {
                $e[] = ucfirst($j) . ' : indiquez des horaires ou cochez « Fermé ».';
            }
        }
        $horaires[$j] = ['ferme' => $ferme, 'plages' => $plages, 'brut' => $brut];
    }
    $s['horaires'] = $horaires;

    return [$s, $e];
}

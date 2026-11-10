<?php
declare(strict_types=1);

function image_valide(string $chemin): bool
{
    return (bool) preg_match('#^images/[A-Za-z0-9._-]{1,80}\.(jpe?g|png|webp|gif|svg)$#i', $chemin)
        && !str_contains($chemin, '..');
}

function tel_href(string $tel): string
{
    return preg_replace('/[^0-9+]/', '', $tel) ?? '';
}

function maps_url(string $adresse): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(str_replace("\n", ' ', $adresse));
}

function texte_horaire(array $jour): string
{
    if (!empty($jour['ferme']) || empty($jour['plages'])) {
        return 'Fermé';
    }
    return implode(' / ', array_map(fn($p) => $p[0] . ' – ' . $p[1], $jour['plages']));
}

function date_courte(string $iso): string
{
    return implode('/', array_reverse(explode('-', $iso)));
}

function rendre_page(string $gabarit, array $s): string
{
    $cfg = config();
    ob_start();
    try {
        require __DIR__ . '/templates/' . $gabarit;
        return (string) ob_get_clean();
    } catch (Throwable $t) {
        ob_end_clean();
        throw $t;
    }
}

function publier_site(array $s): void
{
    $dir = site_dir((string) $s['slug']);
    assurer_dossier($dir, 0755);
    ecrire_atomique($dir . '/index.html', rendre_page('site.php', $s), 0644);
    ecrire_atomique($dir . '/mentions-legales.html', rendre_page('mentions.php', $s), 0644);
}

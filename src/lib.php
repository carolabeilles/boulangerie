<?php
declare(strict_types=1);

const JOURS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

/* ---------------------------------------------------------------- Configuration */

function config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $chemin = getenv('BOULANGERIE_CONFIG') ?: dirname(__DIR__) . '/config.php';
        if (!is_file($chemin)) {
            if (PHP_SAPI !== 'cli') {
                http_response_code(500);
            }
            exit("Configuration manquante : copiez config.example.php en config.php et adaptez-la.\n");
        }
        $cfg = array_merge([
            'cookie_secure' => true,
            'max_echecs' => 5,
            'blocage_minutes' => 15,
            'inactivite_minutes' => 120,
        ], require $chemin);
        date_default_timezone_set('Europe/Paris');
    }
    return $cfg;
}

/* ---------------------------------------------------------------- Utilitaires */

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function assurer_dossier(string $dossier, int $mode = 0750): void
{
    if (!is_dir($dossier) && !mkdir($dossier, $mode, true) && !is_dir($dossier)) {
        throw new RuntimeException('Impossible de créer le dossier ' . $dossier);
    }
}

function ecrire_atomique(string $fichier, string $contenu, int $mode = 0640): void
{
    $tmp = $fichier . '.tmp' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $contenu, LOCK_EX) === false) {
        throw new RuntimeException('Écriture impossible : ' . $fichier);
    }
    @chmod($tmp, $mode);
    if (!rename($tmp, $fichier)) {
        @unlink($tmp);
        throw new RuntimeException('Remplacement impossible : ' . $fichier);
    }
}

function nettoyer_anciens_fichiers(string $dossier, int $age): void
{
    if (random_int(1, 100) !== 1) {
        return;
    }
    foreach (glob($dossier . '/*') ?: [] as $f) {
        if (is_file($f) && filemtime($f) < time() - $age) {
            @unlink($f);
        }
    }
}

/* ---------------------------------------------------------------- Sites (stockage JSON) */

function slug_valide(string $slug): bool
{
    return (bool) preg_match('/^[a-z0-9][a-z0-9-]{1,40}$/', $slug);
}

function data_dir(string $slug): string
{
    return rtrim((string) config()['data_root'], '/') . '/' . $slug;
}

function site_dir(string $slug): string
{
    return rtrim((string) config()['sites_root'], '/') . '/' . $slug;
}

function site_par_defaut(string $slug, string $nom, string $url): array
{
    $horaires = [];
    foreach (JOURS as $j) {
        $horaires[$j] = $j === 'lundi'
            ? ['ferme' => true, 'plages' => []]
            : ['ferme' => false, 'plages' => [['07:00', '19:30']]];
    }
    return [
        'slug' => $slug,
        'nom' => $nom,
        'url' => $url,
        'password_hash' => '',
        'accroche' => '',
        'intro' => '',
        'horaires' => $horaires,
        'note' => '',
        'info' => ['actif' => false, 'texte' => ''],
        'fermeture' => ['actif' => false, 'debut' => '', 'fin' => '', 'message' => ''],
        'promo' => false,
        'adresse' => '',
        'telephone' => '',
        'paiements' => '',
        'infos_pratiques' => '',
        'lien_avis' => '',
        'images' => ['hero' => '', 'galerie' => []],
        'legal' => ['editeur' => '', 'responsable' => '', 'contact' => ''],
        'maj' => '',
    ];
}

function charger_site(string $slug): ?array
{
    if (!slug_valide($slug)) {
        return null;
    }
    $fichier = data_dir($slug) . '/site.json';
    if (!is_file($fichier)) {
        return null;
    }
    $d = json_decode((string) file_get_contents($fichier), true);
    if (!is_array($d)) {
        return null;
    }
    $d['slug'] = $slug;
    return $d + site_par_defaut($slug, $slug, '');
}

function sauvegarder_site(array $site): void
{
    $slug = (string) ($site['slug'] ?? '');
    if (!slug_valide($slug)) {
        throw new InvalidArgumentException('Identifiant invalide');
    }
    foreach ($site['horaires'] as $j => $jour) {
        unset($site['horaires'][$j]['brut']);
    }
    $dir = data_dir($slug);
    assurer_dossier($dir);
    $verrou = fopen($dir . '/.verrou', 'c');
    if ($verrou === false || !flock($verrou, LOCK_EX)) {
        throw new RuntimeException('Verrou impossible');
    }
    try {
        $site['maj'] = date('c');
        ecrire_atomique(
            $dir . '/site.json',
            json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            0640
        );
        publier_site($site);
    } finally {
        flock($verrou, LOCK_UN);
        fclose($verrou);
    }
}

/* ---------------------------------------------------------------- Session et CSRF */

function demarrer_session(): void
{
    $cfg = config();
    $dossier = rtrim((string) $cfg['data_root'], '/') . '/_sessions';
    assurer_dossier($dossier);
    nettoyer_anciens_fichiers($dossier, 86400);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_save_path($dossier);
    session_cache_limiter('');
    session_name('ed_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (bool) $cfg['cookie_secure'],
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    $maintenant = time();
    if (isset($_SESSION['derniere_activite'])
        && $maintenant - (int) $_SESSION['derniere_activite'] > (int) $cfg['inactivite_minutes'] * 60) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['derniere_activite'] = $maintenant;
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function csrf_ok(): bool
{
    $envoye = $_POST['csrf'] ?? '';
    return is_string($envoye) && $envoye !== ''
        && hash_equals((string) ($_SESSION['csrf'] ?? ''), $envoye);
}

function entetes_securite(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    header("Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
}

/* ---------------------------------------------------------------- Protection contre les essais répétés */

function throttle_fichier(string $cle): string
{
    return rtrim((string) config()['data_root'], '/') . '/_throttle/' . hash('sha256', $cle) . '.json';
}

function throttle_bloque(string $cle, int $max): int
{
    $f = throttle_fichier($cle);
    if (!is_file($f)) {
        return 0;
    }
    $d = json_decode((string) @file_get_contents($f), true);
    if (!is_array($d)) {
        return 0;
    }
    $reste = (int) ($d['jusqua'] ?? 0) - time();
    return ((int) ($d['n'] ?? 0) >= $max && $reste > 0) ? $reste : 0;
}

function throttle_echec(string $cle): void
{
    $f = throttle_fichier($cle);
    assurer_dossier(dirname($f));
    nettoyer_anciens_fichiers(dirname($f), 86400);
    $d = is_file($f) ? json_decode((string) @file_get_contents($f), true) : [];
    $d = is_array($d) ? $d : [];
    $maintenant = time();
    $n = $maintenant < (int) ($d['jusqua'] ?? 0) ? (int) ($d['n'] ?? 0) + 1 : 1;
    ecrire_atomique($f, json_encode(['n' => $n, 'jusqua' => $maintenant + (int) config()['blocage_minutes'] * 60]), 0600);
}

function throttle_reset(string $cle): void
{
    @unlink(throttle_fichier($cle));
}

/* ---------------------------------------------------------------- Connexion */

/** @return array{0: bool, 1: string} */
function tenter_connexion(string $slug, string $mdp): array
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '-');
    $max = (int) config()['max_echecs'];
    $cleSite = 'site|' . $ip . '|' . substr($slug, 0, 60);
    $cleIp = 'ip|' . $ip;

    $reste = max(throttle_bloque($cleSite, $max), throttle_bloque($cleIp, $max * 4));
    if ($reste > 0) {
        return [false, 'Trop de tentatives. Réessayez dans ' . (int) ceil($reste / 60) . ' minute(s).'];
    }

    $site = charger_site($slug);
    static $faux = null;
    $faux ??= password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $hash = (string) ($site['password_hash'] ?? '');
    if ($hash === '') {
        $hash = $faux;
        $site = null;
    }
    $ok = password_verify($mdp, $hash) && $site !== null;

    if (!$ok) {
        throttle_echec($cleSite);
        throttle_echec($cleIp);
        return [false, 'Identifiant ou mot de passe incorrect.'];
    }

    throttle_reset($cleSite);
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $site['password_hash'] = password_hash($mdp, PASSWORD_DEFAULT);
        sauvegarder_site($site);
    }
    session_regenerate_id(true);
    $_SESSION['slug'] = $slug;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return [true, ''];
}

function mot_de_passe_aleatoire(int $longueur = 16): string
{
    $alphabet = 'abcdefghjkmnpqrstuvwxyzACDEFGHJKLMNPQRTUVWXYZ2346789';
    $r = '';
    for ($i = 0; $i < $longueur; $i++) {
        $r .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $r;
}

require_once __DIR__ . '/formulaire.php';
require_once __DIR__ . '/rendu.php';

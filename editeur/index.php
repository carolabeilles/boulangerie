<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/lib.php';
require dirname(__DIR__) . '/src/vues.php';

config();
demarrer_session();
entetes_securite();

$self = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
$methode = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

function rediriger(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

if ($methode !== 'GET' && $methode !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}

$slug = isset($_SESSION['slug']) && is_string($_SESSION['slug']) ? $_SESSION['slug'] : '';
$site = $slug !== '' ? charger_site($slug) : null;
if ($slug !== '' && $site === null) {
    $_SESSION = [];
    $slug = '';
}
$csrf = (string) $_SESSION['csrf'];

if ($methode === 'POST') {
    if (!csrf_ok()) {
        http_response_code(403);
        echo page_editeur('Session expirée', vue_message('Votre session a expiré. Rechargez la page puis recommencez.'));
        exit;
    }
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

    if ($action === 'connexion') {
        $identifiant = strtolower(trim(is_string($_POST['identifiant'] ?? null) ? $_POST['identifiant'] : ''));
        $mdp = is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';
        [$ok, $message] = tenter_connexion($identifiant, $mdp);
        if ($ok) {
            rediriger($self);
        }
        http_response_code(str_starts_with($message, 'Trop') ? 429 : 401);
        echo vue_connexion($message, $csrf, $identifiant);
        exit;
    }

    if ($site === null) {
        rediriger($self);
    }

    if ($action === 'deconnexion') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $c = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $c['path'], 'secure' => $c['secure'], 'httponly' => true, 'samesite' => 'Strict']);
        }
        session_destroy();
        rediriger($self);
    }

    if ($action === 'enregistrer') {
        [$nouveau, $erreurs] = lire_formulaire($_POST, $site);
        if ($erreurs) {
            http_response_code(422);
            echo vue_edition($nouveau, $erreurs, '', $csrf);
            exit;
        }
        sauvegarder_site($nouveau);
        $_SESSION['flash'] = 'Modifications enregistrées. Votre site est à jour.';
        rediriger($self);
    }

    if ($action === 'mot_de_passe') {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '-');
        $cle = 'mdp|' . $ip . '|' . $slug;
        $actuel = is_string($_POST['actuel'] ?? null) ? $_POST['actuel'] : '';
        $nouveauMdp = is_string($_POST['nouveau'] ?? null) ? $_POST['nouveau'] : '';
        $confirmation = is_string($_POST['confirmation'] ?? null) ? $_POST['confirmation'] : '';
        $erreur = '';
        if (throttle_bloque($cle, (int) config()['max_echecs']) > 0) {
            $erreur = 'Trop de tentatives. Réessayez plus tard.';
        } elseif (!password_verify($actuel, (string) $site['password_hash'])) {
            throttle_echec($cle);
            $erreur = 'Le mot de passe actuel est incorrect.';
        } elseif (mb_strlen($nouveauMdp, 'UTF-8') < 10) {
            $erreur = 'Le nouveau mot de passe doit contenir au moins 10 caractères.';
        } elseif (!hash_equals($nouveauMdp, $confirmation)) {
            $erreur = 'La confirmation ne correspond pas au nouveau mot de passe.';
        }
        if ($erreur !== '') {
            http_response_code(422);
            echo vue_edition($site, [], '', $csrf, $erreur);
            exit;
        }
        throttle_reset($cle);
        $site['password_hash'] = password_hash($nouveauMdp, PASSWORD_DEFAULT);
        sauvegarder_site($site);
        session_regenerate_id(true);
        $_SESSION['flash'] = 'Votre mot de passe a été changé.';
        rediriger($self);
    }

    rediriger($self);
}

// Affichage (GET)
if ($site === null) {
    echo vue_connexion('', $csrf);
    exit;
}
$flash = (string) ($_SESSION['flash'] ?? '');
unset($_SESSION['flash']);
echo vue_edition($site, [], $flash, $csrf);

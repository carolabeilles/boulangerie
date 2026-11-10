# Éditeur partagé pour sites de boulangeries

Un seul code pour tous vos clients : chaque boulanger se connecte à un petit espace (depuis son téléphone), modifie ses horaires et ses informations, et son site HTML est régénéré aussitôt. Pas de CMS, pas de base de données, aucune bibliothèque externe.

## Principe

- **Le site public** de chaque boulangerie est un fichier `index.html` statique (CSS intégré, aucun PHP à l'exécution). Si l'éditeur tombe en panne, les sites restent en ligne.
- **L'éditeur** (`editeur/index.php`) est commun à tous : on s'y connecte avec un identifiant (par exemple `demo`) et un mot de passe. À chaque enregistrement, il écrit les données dans un fichier JSON et régénère `index.html` et `mentions-legales.html`.
- **Les photos** ne sont pas modifiables par le boulanger : vous les déposez à la création (ou sur demande).
- **La promo du moment** : une case à cocher dans l'éditeur affiche ou masque (`display:block` / `display:none`) une image commune `editeur/partage/promo.jpg`. Vous remplacez ce seul fichier, tous les sites qui ont la case cochée affichent la nouvelle image.
- **Les fermetures exceptionnelles** ont une date de début et une date de fin : le site revient à la normale tout seul.

## Contenu du paquet

```
config.example.php     à copier en config.php
editeur/               ce qui est servi sur editeur.votredomaine.fr (index.php, CSS, partage/promo.jpg)
src/                   le code (jamais servi publiquement)
bin/                   scripts en ligne de commande : creer-site, changer-mot-de-passe, regenerer-tout, reset-demo
data/                  données des boulangeries (créé automatiquement, jamais servi publiquement)
exemple-images/        5 images de démonstration
```

## Installation (hébergement mutualisé, à adapter)

**Prérequis** : PHP 8.1 ou plus avec les extensions `mbstring` et `session` (testé en PHP 8.3). Apache avec `.htaccess` (directives Apache 2.4). Tout ceci est à vérifier sur votre offre : je n'ai pas pu tester sur un hébergement OVH.

1. **Envoyez tout le dossier** par FTP, par exemple dans `~/boulangerie/`.
2. **Copiez `config.example.php` en `config.php`** et adaptez-le (adresse de l'éditeur, texte de l'hébergeur, votre nom). Laissez `cookie_secure` à `true` : l'éditeur exige HTTPS.
3. **Domaine de l'éditeur** : pointez `editeur.votredomaine.fr` vers le dossier `boulangerie/editeur`. Activez le HTTPS.
4. **Créez un site** (en ligne de commande, en SSH ou en local, voir ci-dessous), puis **pointez le domaine de la boulangerie** vers `boulangerie/sites/<identifiant>`.
5. **Déposez les photos** dans `boulangerie/sites/<identifiant>/images/`.

Avec cette organisation, seuls `editeur/` et chaque `sites/<identifiant>/` sont publics. `src/`, `data/`, `bin/` et `config.php` restent hors des dossiers servis. Des `.htaccess` « Require all denied » protègent en plus `src/` et `data/`.

## Créer un site

```
php bin/creer-site.php boulangerie-dupont "Boulangerie Dupont" https://www.boulangerie-dupont.fr \
  --hero=images/hero.jpg --galerie=images/pain.jpg,images/croissants.jpg \
  --editeur="Boulangerie Dupont, 3 rue du Four, 75000 Ville, SIRET ..." \
  --responsable="Marie Dupont" --contact="contact@boulangerie-dupont.fr"
```

Le script affiche un mot de passe aléatoire, une seule fois. Transmettez-le au boulanger avec l'adresse de l'éditeur. Les mentions légales sont générées à partir des champs `--editeur`, `--responsable`, `--contact` : à vérifier et à faire valider.

Sans accès SSH, vous pouvez lancer ces scripts depuis votre ordinateur (PHP en ligne de commande) avec un `config.php` local, puis envoyer `data/` et `sites/` par FTP, ou utiliser une tâche planifiée de l'hébergeur.

## Au quotidien

| Besoin | Action |
|---|---|
| Changer l'image de promo pour tous | Remplacez `editeur/partage/promo.jpg` par FTP (même nom). Les navigateurs la revérifient toutes les 5 minutes (si le module d'en-têtes d'Apache est actif chez votre hébergeur). Un JPEG léger (moins de 300 Ko) suffit. |
| Mot de passe oublié | `php bin/changer-mot-de-passe.php <identifiant>` |
| Changer une photo | Déposez le fichier dans `sites/<identifiant>/images/`. Si le nom change, modifiez `images` dans `data/<identifiant>/site.json`, puis lancez `php bin/regenerer-tout.php`. |
| Modifier le design ou les gabarits | Éditez `src/templates/site.php`, puis lancez `php bin/regenerer-tout.php` pour mettre à jour tous les sites. |
| Site de démonstration pour un prospect | Créez un site `demo`, puis planifiez chaque nuit `php bin/reset-demo.php` pour le remettre à zéro. |

## Sécurité : ce qui est en place

- Mots de passe hachés (`password_hash`), jamais stockés en clair ; 10 caractères minimum.
- Blocage temporaire après 5 échecs de connexion (par identifiant et par adresse IP). Les compteurs sont stockés sous une empreinte hachée, sans adresse IP en clair.
- Message d'erreur identique pour un identifiant inconnu et un mauvais mot de passe.
- Jeton anti-CSRF sur tous les formulaires ; cookie de session `HttpOnly`, `SameSite=Strict`, `Secure` ; sessions stockées dans `data/_sessions` et expirées après 2 h d'inactivité.
- Tout texte saisi est nettoyé (UTF-8, longueur limitée) et échappé à l'affichage. Les liens d'avis doivent commencer par `https://`.
- En-têtes de sécurité (CSP restrictive, `X-Frame-Options`, `nosniff`) ; l'éditeur n'utilise aucun JavaScript.
- Écritures atomiques et verrou par site ; aucun envoi de fichier par les boulangers (donc aucun risque lié aux envois).

À faire de votre côté : HTTPS partout, sauvegardes régulières du dossier `data/` (ce sont de simples fichiers JSON), PHP à jour, comptes FTP protégés, et mots de passe transmis par un canal sûr.

## Tests réalisés

Sur PHP 8.3 avec le serveur intégré : 47 scénarios automatiques réussis (connexion, blocage, CSRF, injection de code, horaires incohérents, liens dangereux, données malformées, accès sans session, changement de mot de passe, déconnexion, promo activée/désactivée, génération des pages). Le script « Ouvert actuellement » a été vérifié dans un navigateur avec horloge simulée (7 cas, dont minuit à Paris avec un navigateur réglé sur un autre fuseau) et la fermeture exceptionnelle avec retour automatique à la normale.

**Non testé** : un vrai hébergement OVH (version de PHP, directives `.htaccess`, tâches planifiées), l'envoi sur de vrais téléphones, et les mentions légales (squelette à faire valider).

## Limites connues et pistes

- Le texte alternatif de l'image de promo est générique (« Promotion du moment »), car l'image est commune à tous.
- Le statut « Ouvert actuellement » nécessite JavaScript côté visiteur (sans JavaScript, le tableau des horaires reste affiché).
- Pas de réinitialisation du mot de passe par e-mail : elle passe par `bin/changer-mot-de-passe.php`.
- Les pages sont figées à l'enregistrement : après une modification des gabarits, il faut relancer `bin/regenerer-tout.php`.
- Pistes : données structurées pour Google (type « Bakery »), photos modifiables, synchronisation avec la fiche Google (à vérifier juridiquement), affichage de la date de dernière mise à jour.

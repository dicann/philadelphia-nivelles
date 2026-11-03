<?php
/**
 * Point d'entrée unique du site (toutes les pages passent par ici grâce à .htaccess).
 *   /fr/...  pages en français     /ro/...  pages en roumain
 */
declare(strict_types=1);

// Serveur PHP intégré (tests locaux) : servir directement les fichiers existants.
if (PHP_SAPI === 'cli-server') {
    $f = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($f !== __DIR__ . '/' && is_file($f) && !preg_match('~/(app|content/pages)/~', $f)) {
        return false;
    }
}

require __DIR__ . '/app/bootstrap.php';

send_security_headers();

$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$path = trim(substr($path, strlen(base_path()) - 1), '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ------------------------------------------------------------ fichiers spéciaux
switch ($path) {
    case '':
        // Langue selon le navigateur : roumain si demandé en premier, sinon français.
        $l = 'fr';
        foreach (explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')) as $part) {
            $code = substr(trim($part), 0, 2);
            if ($code === 'ro' || $code === 'fr') {
                $l = $code;
                break;
            }
        }
        header('Vary: Accept-Language');
        redirect(url('accueil', $l), 302);
    case 'sitemap.xml':
        header('Content-Type: application/xml; charset=utf-8');
        echo render('sitemap');
        exit;
    case 'robots.txt':
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nDisallow: /app/\nDisallow: /cache/\nDisallow: /fr/faire-un-don/merci\nDisallow: /ro/doneaza/multumim\n\nSitemap: " . site_url() . "/sitemap.xml\n";
        exit;
    case 'qr-virement.svg':
        qr_endpoint();
        exit;
}

// ------------------------------------------------------------ pages
$parts = explode('/', $path, 2);
$lang = $parts[0];
$rest = $parts[1] ?? '';

if (!in_array($lang, Content::LANGS, true)) {
    // Ancienne adresse sans langue (ex. /faire-un-don) : redirection vers la version française.
    foreach (Content::ROUTES as $key => $slugs) {
        if ($slugs[0] !== '' && $slugs[0] === $path) {
            redirect(url($key, 'fr'), 301);
        }
    }
    not_found('fr');
}
set_lang($lang);
$idx = $lang === 'ro' ? 1 : 0;

$page = null;
foreach (Content::ROUTES as $key => $slugs) {
    if ($slugs[$idx] === $rest) {
        $page = $key;
        break;
    }
}

// Détail d'une actualité : /fr/actualites/mon-article
$newsPrefix = Content::ROUTES['actualites'][$idx] . '/';
if ($page === null && str_starts_with($rest, $newsPrefix)) {
    $item = Content::newsItem(substr($rest, strlen($newsPrefix)));
    if ($item) {
        echo render('pages/actualite', ['item' => $item]);
        exit;
    }
}

if ($page === null) {
    not_found($lang);
}

switch ($page) {
    case 'don_paiement':
        if ($method !== 'POST') {
            redirect(url('don'), 303);
        }
        handle_donation();
        exit;
    case 'mentions':
    case 'confidentialite':
        $doc = Content::page($page === 'mentions' ? 'mentions-legales' : 'confidentialite', $lang);
        echo render('pages/texte', ['doc' => $doc, 'pageKey' => $page]);
        exit;
    default:
        echo render('pages/' . $page);
        exit;
}

// =====================================================================

function redirect(string $to, int $code = 302): void
{
    header('Location: ' . $to, true, $code);
    exit;
}

function not_found(string $lang): void
{
    set_lang($lang);
    http_response_code(404);
    echo render('pages/404');
    exit;
}

/** Formulaire de don envoyé : validation puis redirection vers la page de paiement du prestataire. */
function handle_donation(): void
{
    $lang = lang();
    // Le formulaire doit venir de ce site.
    $origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url(site_url(), PHP_URL_HOST)) {
        http_response_code(403);
        exit('Origine refusée.');
    }
    [$don, $errors] = Payments::validate($_POST, $lang);
    if (isset($errors['robot'])) {
        redirect(url('don'), 303);
    }
    if ($errors || !Payments::enabled()) {
        echo render('pages/don', ['errors' => $errors, 'old' => $_POST]);
        return;
    }
    try {
        $to = Payments::checkoutUrl($don, $lang);
    } catch (\Throwable $e) {
        error_log('[dons] ' . $e->getMessage());
        echo render('pages/don', ['errors' => ['paiement' => __('erreur_paiement')] + (Config::get('debug') ? ['debug' => $e->getMessage()] : []), 'old' => $_POST]);
        return;
    }
    redirect($to, 303);
}

/** Code QR EPC (virement SEPA) en SVG. ?montant=50&lang=fr */
function qr_endpoint(): void
{
    $lang = in_array($_GET['lang'] ?? '', Content::LANGS, true) ? $_GET['lang'] : 'fr';
    if (!Epc::ready()) {
        http_response_code(404);
        exit;
    }
    $amount = null;
    if (isset($_GET['montant']) && is_numeric(str_replace(',', '.', $_GET['montant']))) {
        $a = round((float) str_replace(',', '.', $_GET['montant']), 2);
        $amount = ($a >= 0.01 && $a <= 999999999) ? $a : null;
    }
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    echo Epc::svg($amount, $lang);
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; media-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; "
        . "frame-src https://www.youtube-nocookie.com https://player.vimeo.com https://www.openstreetmap.org; "
        . "form-action 'self' https://checkout.stripe.com https://www.mollie.com; base-uri 'self'; frame-ancestors 'self'");
}

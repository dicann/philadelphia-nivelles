<?php
/**
 * Petites fonctions utilisées dans les gabarits (templates).
 */

$GLOBALS['LANG'] = 'fr';

function lang(): string
{
    return $GLOBALS['LANG'];
}

function set_lang(string $l): void
{
    $GLOBALS['LANG'] = in_array($l, Content::LANGS, true) ? $l : 'fr';
}

/** Texte d'interface (app/lang/fr.php, app/lang/ro.php). */
function __(string $key, ?string $lang = null)
{
    static $cache = [];
    $lang = $lang ?? lang();
    if (!isset($cache[$lang])) {
        $cache[$lang] = require APP . "/lang/$lang.php";
    }
    return $cache[$lang][$key] ?? ($lang !== 'fr' ? __($key, 'fr') : $key);
}

/** Valeur bilingue de site.json : {"fr": "...", "ro": "..."} => texte dans la langue courante. */
function t($v, ?string $lang = null): string
{
    $lang = $lang ?? lang();
    if (is_array($v)) {
        return (string) ($v[$lang] ?? $v['fr'] ?? '');
    }
    return (string) ($v ?? '');
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Le texte contient-il encore un [À COMPLÉTER …] / [À VÉRIFIER …] ? */
function is_todo(?string $s): bool
{
    return $s === null || trim($s) === '' || (bool) preg_match('/\[À (COMPLÉTER|VÉRIFIER)/u', $s);
}

/** Échappe le texte et surligne les mentions à compléter. */
function tx($s): string
{
    return mark_todo(e($s));
}

function mark_todo(string $html): string
{
    return preg_replace('/\[À (COMPLÉTER|VÉRIFIER)[^\]]*\]/u', '<mark class="todo">$0</mark>', $html);
}

/** Chemin de base du site (« / » ou « /sous-dossier/ »). */
function base_path(): string
{
    static $b = null;
    if ($b === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $b = rtrim($dir, '/') . '/';
    }
    return $b;
}

/** Adresse complète du site, sans « / » final. */
function site_url(): string
{
    $cfg = rtrim((string) Config::get('site_url'), '/');
    if ($cfg !== '') {
        return $cfg;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https' : 'http') . '://' . $host . rtrim(base_path(), '/');
}

/** URL relative d'une page : url('projet') => /fr/le-projet */
function url(string $page, ?string $lang = null, string $suffix = ''): string
{
    $lang = $lang ?? lang();
    $slug = Content::ROUTES[$page][$lang === 'ro' ? 1 : 0] ?? '';
    return base_path() . $lang . '/' . ($slug !== '' ? $slug : '') . $suffix;
}

function abs_url(string $page, ?string $lang = null, string $suffix = ''): string
{
    return site_url() . substr(url($page, $lang, $suffix), strlen(rtrim(base_path(), '/')));
}

function news_url(string $slug, ?string $lang = null): string
{
    return url('actualites', $lang, '/' . $slug);
}

function asset(string $path): string
{
    $f = ROOT . '/assets/' . $path;
    $v = is_file($f) ? substr((string) filemtime($f), -6) : '1';
    return base_path() . 'assets/' . $path . '?v=' . $v;
}

/** Montant à la belge : « 1 250 € » (fr) / « 1.250 € » (ro). */
function money(float $v, ?string $lang = null): string
{
    $lang = $lang ?? lang();
    $dec = fmod($v, 1.0) != 0.0 ? 2 : 0;
    $s = $lang === 'ro'
        ? number_format($v, $dec, ',', '.')
        : number_format($v, $dec, ',', "\u{202F}");
    return $s . "\u{00A0}€";
}

function date_fmt(string $ymd, ?string $lang = null): string
{
    $lang = $lang ?? lang();
    $ts = strtotime($ymd);
    if (!$ts) {
        return $ymd;
    }
    $months = __('mois', $lang);
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

/**
 * Image responsive, ou image de remplacement si le fichier n'a pas encore été déposé.
 *
 * @param string|null $rel   chemin dans public_html (ex. uploads/images/accueil.jpg)
 * @param string      $alt   texte alternatif (description de l'image)
 * @param string      $sizes attribut sizes (largeur affichée)
 */
function picture(?string $rel, string $alt, string $sizes = '100vw', string $class = '', bool $eager = false): string
{
    $found = Media::find($rel);
    if (!$found) {
        return placeholder_img($rel, $alt, $class);
    }
    $size = @getimagesize(ROOT . '/' . $found);
    $wh = $size ? sprintf(' width="%d" height="%d"', $size[0], $size[1]) : '';
    return sprintf(
        '<img class="%s" src="%s" srcset="%s" sizes="%s" alt="%s"%s %s decoding="async">',
        e($class),
        e(Media::url($found, 960)),
        e(Media::srcset($found)),
        e($sizes),
        e($alt),
        $wh,
        $eager ? 'fetchpriority="high"' : 'loading="lazy"'
    );
}

/** Image neutre de remplacement (arc + mention « photo à venir »). */
function placeholder_img(?string $rel, string $alt, string $class = ''): string
{
    $label = __('photo_a_venir');
    $path = $rel ? '<small>' . e($rel) . '</small>' : '';
    return '<div class="ph ' . e($class) . '" role="img" aria-label="' . e($alt . ' — ' . $label) . '">'
        . '<svg viewBox="0 0 200 210" aria-hidden="true" focusable="false"><use href="#arch"/></svg>'
        . '<span>' . e($label) . $path . '</span></div>';
}

/** Barre de progression de la campagne. */
function progress_bar(bool $large = false): string
{
    ob_start();
    include APP . '/templates/partials/progress.php';
    return (string) ob_get_clean();
}

function stage_status_label(string $s): string
{
    return __('statut_' . (in_array($s, ['a_venir', 'en_cours', 'termine'], true) ? $s : 'a_venir'));
}

/** Lieu des cultes pendant les travaux (ou null). */
function worship_place(): ?array
{
    $l = Content::get('contact.lieu_cultes');
    return (is_array($l) && !empty($l['afficher']) && !empty($l['adresse'])) ? $l : null;
}

/** Lien OpenStreetMap vers une adresse (sans coordonnées GPS). */
function osm_search(string $address): string
{
    return 'https://www.openstreetmap.org/search?query=' . rawurlencode($address . ', Belgique');
}

/** Numéros de téléphone de site.json (contact.telephones). */
function phones(): array
{
    return array_values(array_filter(Content::get('contact.telephones', []), fn($p) => !empty($p['numero'])));
}

/** Lien tel: (format international belge). */
function tel_href(string $n): string
{
    $d = preg_replace('/[^\d+]/', '', $n);
    if (str_starts_with($d, '0') && !str_starts_with($d, '00')) {
        $d = '+32' . substr($d, 1);
    }
    return 'tel:' . $d;
}

/** Rendu d'un gabarit avec variables. */
function render(string $template, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    include APP . '/templates/' . $template . '.php';
    return (string) ob_get_clean();
}

/** Icône SVG (sprite défini dans le gabarit principal). */
function icon(string $name, string $class = 'icon'): string
{
    return '<svg class="' . e($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . e($name) . '"/></svg>';
}

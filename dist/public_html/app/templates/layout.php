<?php
/**
 * Gabarit principal (en-tête, menu, pied de page).
 * Variables attendues : $content (HTML), $title, $pageKey ; facultatives : $description, $alternates, $ogImage, $noindex
 */
$lang = lang();
$siteName = t(Content::get('site.nom'));
$description = $description ?? t(Content::get('site.description'));
$fullTitle = ($pageKey ?? '') === 'accueil' ? $siteName . ' — ' . $title : $title . ' — ' . $siteName;
$alternates = $alternates ?? (isset($pageKey) && isset(Content::ROUTES[$pageKey])
    ? ['fr' => abs_url($pageKey, 'fr'), 'ro' => abs_url($pageKey, 'ro')] : []);
$canonical = $alternates[$lang] ?? site_url() . ($_SERVER['REQUEST_URI'] ?? '/');
$other = $lang === 'fr' ? 'ro' : 'fr';
$switchUrl = isset($alternates[$other]) ? substr($alternates[$other], strlen(site_url())) ?: '/' : url('accueil', $other);
$ogImage = $ogImage ?? site_url() . '/assets/img/og-image-' . $lang . '.jpg?v=' . substr((string) @filemtime(ROOT . '/assets/img/og-image-' . $lang . '.jpg'), -6);
if (($pageKey ?? '') === 'accueil') {
    $ogTitle = $siteName . ' — ' . t(Content::get('accueil.hero_titre'));
    $ogDescription = t(Content::get('accueil.hero_texte'));
} else {
    $ogTitle = $fullTitle;
    $ogDescription = strip_tags($description);
}
$nav = array_values(array_filter((array) Content::get('menu', ['accueil', 'histoire', 'communaute', 'projet', 'galerie', 'contact']), fn($k) => isset(Content::ROUTES[$k])));
$c = Content::get('contact');
$testMode = Payments::enabled() && !Config::isLive();
?><!doctype html>
<html lang="<?= $lang === 'fr' ? 'fr-BE' : 'ro' ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e(strip_tags($description)) ?>">
<?php if (!empty($noindex)): ?><meta name="robots" content="noindex"><?php endif; ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<?php foreach ($alternates as $l => $href): ?>
<link rel="alternate" hreflang="<?= $l === 'fr' ? 'fr-BE' : 'ro' ?>" href="<?= e($href) ?>">
<?php endforeach; ?>
<?php if (isset($alternates['fr'])): ?><link rel="alternate" hreflang="x-default" href="<?= e($alternates['fr']) ?>"><?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($ogTitle) ?>">
<meta property="og:description" content="<?= e($ogDescription) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php if (str_contains($ogImage, '/assets/img/og-image-')): ?>
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<?php endif; ?>
<meta property="og:image:alt" content="<?= e($siteName) ?>">
<meta property="og:locale" content="<?= $lang === 'fr' ? 'fr_BE' : 'ro_RO' ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#23514A">
<link rel="icon" href="<?= base_path() ?>favicon.ico" sizes="48x48">
<link rel="icon" href="<?= base_path() ?>assets/img/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= base_path() ?>assets/img/apple-touch-icon.png">
<link rel="manifest" href="<?= base_path() ?>site.webmanifest">
<link rel="preload" href="<?= base_path() ?>assets/fonts/cormorant-garamond-500-normal-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= base_path() ?>assets/fonts/source-sans-3-400-normal-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<script src="<?= asset('js/main.js') ?>" defer></script>
<script type="application/ld+json"><?= json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Church',
    'name' => $siteName,
    'url' => site_url() . '/',
    'logo' => site_url() . '/assets/img/logo.png',
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => is_todo($c['adresse']) ? null : $c['adresse'],
        'postalCode' => $c['code_postal'],
        'addressLocality' => $c['ville'],
        'addressCountry' => 'BE',
    ],
    'email' => is_todo($c['email']) ? null : $c['email'],
    'telephone' => phones() ? str_replace('tel:', '', tel_href(phones()[0]['numero'])) : null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="page-<?= e($pageKey ?? 'autre') ?>">
<?php include __DIR__ . '/partials/sprite.php'; ?>
<a class="skip" href="#contenu"><?= e(__('aller_contenu')) ?></a>

<?php if ($testMode): ?>
<aside class="testbar" aria-label="Test"><?= e(__('mode_test')) ?></aside>
<?php endif; ?>

<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?= url('accueil') ?>" aria-label="<?= e($siteName) ?> — <?= e(__('nav_accueil')) ?>">
      <svg class="brand-mark" viewBox="0 0 200 210" aria-hidden="true" focusable="false"><use href="#logo"/></svg>
      <span class="brand-text"><span class="brand-name">Philadelphia</span><span class="brand-city">Nivelles</span></span>
    </a>

    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-principale">
      <?= icon('menu') ?><span><?= e(__('menu')) ?></span>
    </button>

    <nav id="nav-principale" class="site-nav" aria-label="<?= e(__('nav_principale')) ?>">
      <ul>
        <?php foreach ($nav as $k): ?>
        <li><a href="<?= url($k) ?>"<?= ($pageKey ?? '') === $k ? ' aria-current="page"' : '' ?>><?= e(__('nav_' . $k)) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-extra">
        <div class="lang-switch" role="group" aria-label="<?= e(__('changer_langue')) ?>">
          <?php foreach (Content::LANGS as $l): ?>
            <?php if ($l === $lang): ?>
              <span aria-current="true" lang="<?= $l ?>" title="<?= e(__('lang_nom', $l)) ?>"><?= strtoupper($l) ?></span>
            <?php else: ?>
              <a href="<?= e($switchUrl) ?>" hreflang="<?= $l ?>" lang="<?= $l ?>" title="<?= e(__('lang_nom', $l)) ?>"><?= strtoupper($l) ?></a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <a class="btn btn-gold btn-sm" href="<?= url('don') ?>"<?= ($pageKey ?? '') === 'don' ? ' aria-current="page"' : '' ?>><?= icon('heart') ?><?= e(__('faire_un_don')) ?></a>
      </div>
    </nav>
  </div>
</header>

<main id="contenu" tabindex="-1">
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <svg class="footer-mark" viewBox="0 0 200 210" aria-hidden="true" focusable="false"><use href="#logo"/></svg>
      <p class="footer-name"><?= e(__('eglise_nom')) ?><br><span>Nivelles</span></p>
    </div>
    <div>
      <h2 class="footer-title"><?= e(__('pied_adresse')) ?></h2>
      <address>
        <?= tx($c['adresse']) ?><br>
        <?= e($c['code_postal']) ?> <?= e($c['ville']) ?>, <?= e(t($c['pays'])) ?><br>
        <?php if ($wp = worship_place()): ?>
          <small><?= e(__('cultes_a')) ?> <?= e($wp['adresse'] . ', ' . $wp['code_postal'] . ' ' . $wp['ville']) ?></small><br>
        <?php endif; ?>
        <?php foreach (phones() as $p): ?>
          <?= e(__('gsm') . colon()) ?><a href="<?= e(tel_href($p['numero'])) ?>"><?= e($p['numero']) ?></a> <small>(<?= e($p['nom']) ?>)</small><br>
        <?php endforeach; ?>
        <?php if (!is_todo($c['email'])): ?>
          <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>
        <?php else: ?>
          <?= tx($c['email']) ?>
        <?php endif; ?>
      </address>
    </div>
    <div>
      <h2 class="footer-title"><?= e(__('pied_liens')) ?></h2>
      <ul class="footer-links">
        <?php foreach (array_merge(array_intersect(['histoire', 'projet'], $nav), ['don'], array_values(array_diff(array_intersect(['galerie', 'actualites', 'contact'], $nav), []))) as $k): ?>
          <li><a href="<?= url($k) ?>"><?= e(__('nav_' . $k)) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php $socials = array_filter(['facebook' => $c['facebook'] ?? '', 'youtube' => $c['youtube'] ?? '', 'instagram' => $c['instagram'] ?? '']); ?>
      <?php if ($socials): ?>
      <ul class="socials">
        <?php foreach ($socials as $net => $href): ?>
          <li><a href="<?= e($href) ?>" rel="noopener" target="_blank" aria-label="<?= ucfirst($net) ?>"><?= icon($net) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
    <div class="footer-support">
      <h2 class="footer-title"><?= e(__('pied_soutenir')) ?></h2>
      <?= progress_bar() ?>
      <a class="btn btn-gold" href="<?= url('don') ?>"><?= icon('heart') ?><?= e(__('faire_un_don')) ?></a>
    </div>
  </div>
  <div class="wrap footer-bottom">
    <p>© <?= date('Y') ?> <?= tx(Content::get('association.nom_legal')) ?><?php if ($bce = Content::get('association.numero_entreprise')): ?> · BCE <?= e($bce) ?><?php endif; ?> — <?= e(__('tous_droits')) ?></p>
    <p><a href="<?= url('mentions') ?>"><?= e(__('nav_mentions')) ?></a> · <a href="<?= url('confidentialite') ?>"><?= e(__('nav_confidentialite')) ?></a></p>
  </div>
</footer>
</body>
</html>

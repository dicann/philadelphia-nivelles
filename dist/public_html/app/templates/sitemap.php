<?php
$pages = ['accueil', 'histoire', 'communaute', 'projet', 'don', 'galerie', 'actualites', 'contact', 'mentions', 'confidentialite'];
$entries = [];
foreach ($pages as $p) {
    $entries[] = ['fr' => abs_url($p, 'fr'), 'ro' => abs_url($p, 'ro'), 'lastmod' => null];
}
foreach (Content::news() as $n) {
    $entries[] = ['fr' => abs_url('actualites', 'fr', '/' . $n['slug']), 'ro' => abs_url('actualites', 'ro', '/' . $n['slug']), 'lastmod' => $n['date']];
}
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($entries as $en): foreach (['fr', 'ro'] as $l): ?>
  <url>
    <loc><?= e($en[$l]) ?></loc>
<?php if ($en['lastmod']): ?>    <lastmod><?= e($en['lastmod']) ?></lastmod>
<?php endif; ?>
    <xhtml:link rel="alternate" hreflang="fr-BE" href="<?= e($en['fr']) ?>"/>
    <xhtml:link rel="alternate" hreflang="ro" href="<?= e($en['ro']) ?>"/>
  </url>
<?php endforeach; endforeach; ?>
</urlset>

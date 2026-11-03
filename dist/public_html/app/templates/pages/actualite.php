<?php
/** Une actualité. Variable : $item */
$lang = lang();
$doc = $item[$lang] ?? null;
$missing = $doc === null;
$doc = $doc ?? $item['fr'] ?? $item['ro'];
$titreNews = $doc['meta']['titre'] ?? Media::humanize($item['slug']);
ob_start();
?>
<article class="section article">
  <div class="wrap narrow">
    <p class="back"><a href="<?= url('actualites') ?>"><?= icon('prev') ?> <?= e(__('toutes_actus')) ?></a></p>
    <p class="card-date"><?= e(__('publie_le')) ?> <time datetime="<?= e($item['date']) ?>"><?= e(date_fmt($item['date'])) ?></time></p>
    <h1><?= tx($titreNews) ?></h1>
    <?php if ($missing): ?><p class="notice"><?= e(__('traduction_absente')) ?></p><?php endif; ?>
    <?php if ($item['image']): ?>
      <figure class="article-image"><?= picture($item['image'], $titreNews, '(min-width: 760px) 720px, 100vw', '', true) ?></figure>
    <?php endif; ?>
    <div class="prose"<?= $missing ? ' lang="' . ($item['fr'] ? 'fr' : 'ro') . '"' : '' ?>><?= mark_todo($doc['html']) ?></div>
    <p class="article-cta"><a class="btn btn-gold" href="<?= url('don') ?>"><?= icon('heart') ?><?= e(__('faire_un_don')) ?></a></p>
  </div>
</article>
<?php
$content = ob_get_clean();
$title = $titreNews;
$pageKey = 'actualites';
$description = $doc['meta']['resume'] ?? mb_strimwidth($doc['text'], 0, 160, '…');
$alternates = ['fr' => abs_url('actualites', 'fr', '/' . $item['slug']), 'ro' => abs_url('actualites', 'ro', '/' . $item['slug'])];
if ($item['image']) {
    $ogImage = site_url() . substr(Media::url($item['image'], 1600), strlen(rtrim(base_path(), '/')));
}
include APP . '/templates/layout.php';

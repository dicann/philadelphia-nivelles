<?php
$news = Content::news();
ob_start();
$title = __('actualites_titre');
$chapo = __('actualites_chapo');
include APP . '/templates/partials/page-head.php';
?>
<section class="section">
  <div class="wrap">
    <h2 class="visually-hidden"><?= e(__('actualites_titre')) ?></h2>
    <?php if ($news): ?>
      <div class="grid-3"><?php foreach ($news as $n) { include APP . '/templates/partials/news-card.php'; } ?></div>
    <?php else: ?>
      <p class="empty"><?= e(__('aucune_actu')) ?></p>
    <?php endif; ?>
  </div>
</section>
<?php
$content = ob_get_clean();
$pageKey = 'actualites';
include APP . '/templates/layout.php';

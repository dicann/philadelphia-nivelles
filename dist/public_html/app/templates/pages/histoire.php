<?php
$h = Content::get('histoire');
ob_start();
$title = __('histoire_titre');
$chapo = __('histoire_chapo');
include APP . '/templates/partials/page-head.php';
?>
<section class="section">
  <div class="wrap narrow">
    <p class="lead"><?= tx(t($h['intro'])) ?></p>
  </div>
  <div class="wrap">
    <ol class="timeline">
      <?php foreach ($h['chronologie'] as $i => $step): ?>
      <li class="timeline-item">
        <div class="timeline-marker" aria-hidden="true"></div>
        <div class="timeline-media arch-frame"><?= picture($step['image'], t($step['titre']), '(min-width: 900px) 40vw, 100vw') ?></div>
        <div class="timeline-body">
          <p class="timeline-date"><?= tx(t($step['periode'])) ?></p>
          <h2 class="h3"><?= tx(t($step['titre'])) ?></h2>
          <p><?= tx(t($step['texte'])) ?></p>
        </div>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="section section-stone">
  <div class="wrap split">
    <div class="split-media arch-frame"><?= picture($h['architecture_image'], t($h['architecture_titre']), '(min-width: 900px) 45vw, 100vw') ?></div>
    <div>
      <h2><?= tx(t($h['architecture_titre'])) ?></h2>
      <p><?= tx(t($h['architecture_texte'])) ?></p>
      <a class="btn btn-green" href="<?= url('projet') ?>"><?= e(__('nav_projet')) ?> <?= icon('arrow') ?></a>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
$pageKey = 'histoire';
include APP . '/templates/layout.php';

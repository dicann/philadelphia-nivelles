<?php ob_start(); ?>
<section class="section result">
  <div class="wrap narrow center">
    <svg class="result-mark" viewBox="0 0 200 210" aria-hidden="true" focusable="false"><use href="#logo"/></svg>
    <h1><?= e(__('annule_titre')) ?></h1>
    <p class="lead"><?= e(__('annule_texte')) ?></p>
    <p class="result-actions">
      <a class="btn btn-gold" href="<?= url('don') ?>"><?= e(__('reessayer')) ?></a>
      <a class="btn btn-outline" href="<?= url('don') ?>#virement"><?= e(__('virement_titre')) ?></a>
    </p>
  </div>
</section>
<?php
$content = ob_get_clean();
$title = __('annule_titre');
$pageKey = 'don_annule';
$noindex = true;
include APP . '/templates/layout.php';

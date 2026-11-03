<?php ob_start(); ?>
<section class="section result">
  <div class="wrap narrow center">
    <svg class="result-mark" viewBox="0 0 200 210" aria-hidden="true" focusable="false"><use href="#arch"/></svg>
    <h1><?= e(__('introuvable_titre')) ?></h1>
    <p class="lead"><?= e(__('introuvable_texte')) ?></p>
    <a class="btn btn-green" href="<?= url('accueil') ?>"><?= e(__('retour_accueil')) ?></a>
  </div>
</section>
<?php
$content = ob_get_clean();
$title = __('introuvable_titre');
$pageKey = '404';
$noindex = true;
$alternates = [];
include APP . '/templates/layout.php';

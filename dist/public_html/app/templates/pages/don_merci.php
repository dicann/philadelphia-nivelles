<?php
// Affichage seulement : l'enregistrement du don est fait par le webhook.
$info = null;
if (Payments::enabled()) {
    if (!empty($_GET['session_id']) && Payments::provider() === 'stripe') {
        $info = StripePay::sessionInfo((string) $_GET['session_id']);
    } elseif (!empty($_GET['ref']) && Payments::provider() === 'mollie') {
        $info = MolliePay::infoByRef((string) $_GET['ref']);
    }
}
$failed = !empty($info['echec']);
ob_start();
?>
<section class="section result">
  <div class="wrap narrow center">
    <svg class="result-mark" viewBox="0 0 200 210" aria-hidden="true" focusable="false"><use href="#logo"/></svg>
    <?php if ($failed): ?>
      <h1><?= e(__('annule_titre')) ?></h1>
      <p class="lead"><?= e(__('merci_echec')) ?></p>
      <a class="btn btn-gold" href="<?= url('don') ?>"><?= e(__('reessayer')) ?></a>
    <?php else: ?>
      <h1><?= e(__('merci_titre')) ?></h1>
      <p class="lead">
        <?php if ($info && $info['mensuel'] && $info['paye']): ?>
          <?= e(sprintf(__('merci_mensuel'), money($info['montant']))) ?>
        <?php elseif ($info && $info['paye']): ?>
          <?= e(sprintf(__('merci_montant'), money($info['montant']))) ?>
        <?php elseif ($info): ?>
          <?= e(sprintf(__('merci_attente'), money($info['montant']))) ?>
        <?php else: ?>
          <?= e(__('merci_texte')) ?>
        <?php endif; ?>
      </p>
      <p><?= e(__('merci_partager')) ?></p>
      <p class="result-actions">
        <a class="btn btn-green" href="<?= url('projet') ?>"><?= e(__('nav_projet')) ?></a>
        <a class="btn btn-outline" href="<?= url('accueil') ?>"><?= e(__('retour_accueil')) ?></a>
      </p>
    <?php endif; ?>
  </div>
</section>
<?php
$content = ob_get_clean();
$title = $failed ? __('annule_titre') : __('merci_titre');
$pageKey = 'don_merci';
$noindex = true;
include APP . '/templates/layout.php';

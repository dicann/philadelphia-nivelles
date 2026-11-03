<?php
$p = Content::get('projet');
$tot = Donations::totals();
$budgetTotal = 0;
$allBudgets = true;
foreach ($p['etapes'] as $e) {
    if (is_numeric($e['budget'] ?? null)) {
        $budgetTotal += $e['budget'];
    } else {
        $allBudgets = false;
    }
}
ob_start();
$title = __('projet_titre');
$chapo = __('projet_chapo');
include APP . '/templates/partials/page-head.php';
?>
<section class="section">
  <div class="wrap narrow">
    <p class="lead"><?= tx(t($p['intro'])) ?></p>
  </div>
  <div class="wrap narrow">
    <div class="progress-panel"><?= progress_bar(true) ?></div>
  </div>
</section>

<section class="section section-paper">
  <div class="wrap">
    <ol class="stages">
      <?php foreach ($p['etapes'] as $i => $e):
        $before = Media::find($e['image_avant'] ?? null);
        $after = Media::find($e['image_apres'] ?? null);
        $st = $e['statut'] ?? 'a_venir';
        $raisedStage = $tot['par_etape'][$e['id']] ?? 0;
      ?>
      <li class="stage stage-<?= e($st) ?>" id="<?= e($e['id']) ?>">
        <div class="stage-media">
          <?php if ($before && $after): ?>
            <div class="compare" data-compare>
              <div class="compare-img compare-before"><?= picture($before, __('avant') . ' — ' . t($e['titre']), '(min-width: 900px) 50vw, 100vw') ?><span class="compare-tag"><?= e(__('avant')) ?></span></div>
              <div class="compare-img compare-after"><?= picture($after, __('apres') . ' — ' . t($e['titre']), '(min-width: 900px) 50vw, 100vw') ?><span class="compare-tag"><?= e(__('apres')) ?></span></div>
              <input class="compare-range" type="range" min="0" max="100" value="50" aria-label="<?= e(__('comparer')) ?>" hidden>
            </div>
          <?php else: ?>
            <div class="compare-pair">
              <figure><?= picture($e['image_avant'] ?? null, __('avant') . ' — ' . t($e['titre']), '(min-width: 900px) 25vw, 50vw') ?><figcaption><?= e(__('avant')) ?></figcaption></figure>
              <figure><?= picture($e['image_apres'] ?? null, __('apres') . ' — ' . t($e['titre']), '(min-width: 900px) 25vw, 50vw') ?><figcaption><?= e(__('apres')) ?></figcaption></figure>
            </div>
          <?php endif; ?>
        </div>
        <div class="stage-body">
          <p class="stage-num"><?= e(__('etape')) ?> <?= $i + 1 ?> <span class="badge badge-<?= e($st) ?>"><?= e(stage_status_label($st)) ?></span></p>
          <h2 class="h3"><?= tx(t($e['titre'])) ?></h2>
          <p><?= tx(t($e['description'])) ?></p>
          <dl class="stage-figures">
            <div><dt><?= e(__('budget')) ?></dt><dd><?= is_numeric($e['budget'] ?? null) ? e(money((float) $e['budget'])) : mark_todo(e(__('montant_a_completer'))) ?></dd></div>
            <?php if ($raisedStage > 0): ?>
            <div><dt><?= e(__('dons_etape')) ?></dt><dd><?= e(money($raisedStage)) ?></dd></div>
            <?php endif; ?>
          </dl>
          <?php if ($st !== 'termine'): ?>
            <a class="btn btn-green btn-sm" href="<?= url('don') ?>?etape=<?= e(rawurlencode($e['id'])) ?>#formulaire-don"><?= icon('heart') ?><?= e(__('soutenir_etape')) ?></a>
          <?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php if ($allBudgets && $budgetTotal > 0): ?>
      <p class="stages-total"><?= e(__('budget_total')) ?> : <strong><?= e(money($budgetTotal)) ?></strong></p>
    <?php endif; ?>
  </div>
</section>

<section class="section section-green">
  <div class="wrap narrow center">
    <h2><?= e(__('pied_soutenir')) ?></h2>
    <a class="btn btn-gold btn-lg" href="<?= url('don') ?>"><?= icon('heart') ?><?= e(__('faire_un_don')) ?></a>
  </div>
</section>
<?php
$content = ob_get_clean();
$pageKey = 'projet';
include APP . '/templates/layout.php';

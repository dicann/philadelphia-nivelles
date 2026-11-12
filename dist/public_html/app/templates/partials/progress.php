<?php
/** Barre de progression de la campagne. Variable : $large (bool) */
$tot = Donations::totals();
$pct = $tot['pourcentage'];
$showCount = (bool) Content::get('campagne.afficher_nombre_dons', true) && $tot['nombre'] > 0;
?>
<div class="progress<?= !empty($large) ? ' progress-lg' : '' ?>">
  <?php if ($tot['collecte'] <= 0 && $tot['objectif']): ?>
  <p class="progress-figures">
    <span class="progress-kicker"><?= e(__('objectif_campagne')) ?></span>
    <strong class="progress-raised"><?= e(money($tot['objectif'])) ?></strong>
  </p>
  <?php else: ?>
  <p class="progress-figures">
    <strong class="progress-raised"><?= e(money($tot['collecte'])) ?></strong>
    <span><?= e(t(Content::get('campagne.libelle_montant')) ?: __('collecte')) ?></span>
    <?php if ($tot['objectif']): ?>
      <span class="progress-goal"><?= e(__('objectif') . colon() . money($tot['objectif'])) ?></span>
    <?php endif; ?>
  </p>
  <?php endif; ?>
  <?php if ($tot['objectif']): ?>
    <div class="progress-track" role="progressbar" aria-label="<?= e(__('progression_label')) ?>"
         aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= e((string) round($pct)) ?>"
         aria-valuetext="<?= e(money($tot['collecte']) . ' / ' . money($tot['objectif'])) ?>">
      <span class="progress-fill" style="width: <?= e((string) max($pct, $pct > 0 ? 1.5 : 0)) ?>%"></span>
    </div>
    <p class="progress-meta">
      <span><?= e(percent((float) $pct)) ?></span>
      <?php if ($tot['collecte'] <= 0): ?><span><?= e(__('premiers_dons')) ?></span><?php endif; ?>
      <?php if ($showCount): ?><span><?= e(sprintf(__($tot['nombre'] === 1 ? 'nb_don' : ($tot['nombre'] % 100 === 0 || $tot['nombre'] % 100 >= 20 ? 'nb_dons_de' : 'nb_dons')), $tot['nombre'])) ?></span><?php endif; ?>
    </p>
  <?php else: ?>
    <p class="progress-meta"><?= mark_todo(e(__('objectif_a_definir'))) ?></p>
  <?php endif; ?>
  <?php $inv = Content::get('campagne.deja_investi'); if (is_numeric($inv) && $inv > 0): ?>
    <p class="progress-invested"><?= e(__('deja_investi') . colon() . money((float) $inv)) ?></p>
  <?php endif; ?>
</div>

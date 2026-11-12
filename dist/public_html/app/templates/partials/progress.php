<?php
/** Barre de progression de la campagne. Variable : $large (bool) */
$tot = Donations::totals();
$pct = $tot['pourcentage'];
$showCount = (bool) Content::get('campagne.afficher_nombre_dons', true) && $tot['nombre'] > 0;
?>
<div class="progress<?= !empty($large) ? ' progress-lg' : '' ?>">
  <p class="progress-figures">
    <strong class="progress-raised"><?= e(money($tot['collecte'])) ?></strong>
    <span><?= e(t(Content::get('campagne.libelle_montant')) ?: __('collecte')) ?></span>
    <?php if ($tot['objectif']): ?>
      <span class="progress-goal"><?= e(__('objectif')) ?> : <?= e(money($tot['objectif'])) ?></span>
    <?php endif; ?>
  </p>
  <?php if ($tot['objectif']): ?>
    <div class="progress-track" role="progressbar" aria-label="<?= e(__('progression_label')) ?>"
         aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= e((string) round($pct)) ?>"
         aria-valuetext="<?= e(money($tot['collecte']) . ' / ' . money($tot['objectif'])) ?>">
      <span class="progress-fill" style="width: <?= e((string) max($pct, $pct > 0 ? 1.5 : 0)) ?>%"></span>
    </div>
    <p class="progress-meta">
      <span><?= e(str_replace('.', ',', (string) $pct)) ?>&nbsp;%</span>
      <?php if ($showCount): ?><span><?= e(sprintf(__($tot['nombre'] > 1 ? 'nb_dons' : 'nb_don'), $tot['nombre'])) ?></span><?php endif; ?>
    </p>
  <?php else: ?>
    <p class="progress-meta"><?= mark_todo(e(__('objectif_a_definir'))) ?></p>
  <?php endif; ?>
</div>

<?php
$cm = Content::get('communaute');
$c = Content::get('contact');
ob_start();
$title = __('communaute_titre');
$chapo = __('communaute_chapo');
include APP . '/templates/partials/page-head.php';
?>
<section class="section">
  <div class="wrap split">
    <div>
      <h2><?= e(__('qui_sommes_nous')) ?></h2>
      <p class="lead"><?= tx(t($cm['intro'])) ?></p>
      <p><?= tx(t($cm['langues_cultes'])) ?></p>
      <p><?= tx(t($cm['affiliation'])) ?></p>
    </div>
    <div class="split-media arch-frame"><?= picture($cm['image'], __('communaute_titre'), '(min-width: 900px) 45vw, 100vw') ?></div>
  </div>
</section>

<section class="section section-stone">
  <div class="wrap grid-2">
    <div>
      <h2><?= tx(t($cm['croyances_titre'])) ?></h2>
      <ul class="list-check">
        <?php foreach ($cm['croyances'] as $item): ?>
          <li><?= icon('check') ?><span><?= tx(t($item)) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="schedule">
      <h2><?= e(__('horaires')) ?></h2>
      <dl>
        <?php foreach ($cm['horaires'] as $row): ?>
        <div class="schedule-row">
          <dt><?= icon('clock') ?><?= tx(t($row['jour'])) ?></dt>
          <dd><strong><?= tx(t($row['heure'])) ?></strong> — <?= tx(t($row['libelle'])) ?></dd>
        </div>
        <?php endforeach; ?>
      </dl>
      <p class="schedule-address"><?= icon('pin') ?><span><?= tx($c['adresse']) ?>, <?= e($c['code_postal'] . ' ' . $c['ville']) ?></span></p>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <h2 class="center"><?= e(__('activites')) ?></h2>
    <div class="grid-3">
      <?php foreach ($cm['activites'] as $act): ?>
      <article class="card card-arch">
        <div class="card-media arch-frame"><?= picture($act['image'], t($act['titre']), '(min-width: 900px) 33vw, 100vw') ?></div>
        <div class="card-body">
          <h3 class="card-title"><?= tx(t($act['titre'])) ?></h3>
          <p><?= tx(t($act['texte'])) ?></p>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-green">
  <div class="wrap narrow center">
    <p class="lead"><?= e(__('bienvenue_visite')) ?></p>
    <a class="btn btn-gold" href="<?= url('contact') ?>"><?= e(__('nous_contacter')) ?></a>
  </div>
</section>
<?php
$content = ob_get_clean();
$pageKey = 'communaute';
include APP . '/templates/layout.php';

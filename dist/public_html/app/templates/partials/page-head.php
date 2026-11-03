<?php /** En-tête de page intérieure. Variables : $title, $chapo */ ?>
<header class="page-head">
  <div class="wrap">
    <svg class="page-head-mark" viewBox="0 0 200 210" aria-hidden="true" focusable="false"><use href="#arch"/></svg>
    <h1><?= tx($title) ?></h1>
    <?php if (!empty($chapo)): ?><p class="lead"><?= tx($chapo) ?></p><?php endif; ?>
  </div>
</header>

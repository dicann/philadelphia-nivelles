<?php
/** Page de texte (mentions légales / confidentialité). Variables : $doc, $pageKey */
ob_start();
$title = $doc['meta']['titre'] ?? __('nav_' . $pageKey);
include APP . '/templates/partials/page-head.php';
?>
<section class="section">
  <div class="wrap narrow prose">
    <?= $doc ? mark_todo($doc['html']) : '' ?>
  </div>
</section>
<?php
$content = ob_get_clean();
include APP . '/templates/layout.php';

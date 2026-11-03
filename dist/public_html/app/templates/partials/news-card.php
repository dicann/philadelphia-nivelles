<?php
/** Carte d'actualité. Variable : $n */
$doc = $n[lang()] ?? $n['fr'] ?? $n['ro'];
$titre = $doc['meta']['titre'] ?? Media::humanize($n['slug']);
$resume = $doc['meta']['resume'] ?? mb_strimwidth($doc['text'], 0, 180, '…');
?>
<article class="card news-card">
  <div class="card-media"><?= picture($n['image'], $titre, '(min-width: 900px) 33vw, 100vw') ?></div>
  <div class="card-body">
    <p class="card-date"><time datetime="<?= e($n['date']) ?>"><?= e(date_fmt($n['date'])) ?></time></p>
    <h3 class="card-title"><a href="<?= e(news_url($n['slug'])) ?>"><?= tx($titre) ?></a></h3>
    <p><?= tx($resume) ?></p>
  </div>
</article>

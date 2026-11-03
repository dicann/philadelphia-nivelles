<?php
$albums = Gallery::albums();
$videos = Content::videos();
ob_start();
$title = __('galerie_titre');
$chapo = __('galerie_chapo');
include APP . '/templates/partials/page-head.php';
?>
<section class="section" aria-labelledby="t-photos">
  <div class="wrap">
    <h2 id="t-photos" class="visually-hidden"><?= e(__('photos')) ?></h2>
    <?php if (count($albums) > 1): ?>
      <nav class="album-nav" aria-label="<?= e(__('albums')) ?>">
        <?php foreach ($albums as $al): ?><a href="#album-<?= e($al['id']) ?>"><?= e(t($al['titre'])) ?> <span>(<?= count($al['images']) ?>)</span></a><?php endforeach; ?>
      </nav>
    <?php endif; ?>
    <?php if (!$albums): ?>
      <p class="empty"><?= e(__('aucune_photo')) ?></p>
    <?php endif; ?>
    <?php foreach ($albums as $al): ?>
      <section class="album" id="album-<?= e($al['id']) ?>" aria-labelledby="h-<?= e($al['id']) ?>">
        <h3 class="album-title" id="h-<?= e($al['id']) ?>"><?= e(t($al['titre'])) ?></h3>
        <ul class="gallery" data-gallery>
          <?php foreach ($al['images'] as $i => $img): ?>
          <li>
            <a href="<?= e(Media::url($img['src'], 1600)) ?>" data-lightbox data-caption="<?= e($img['legende']) ?>">
              <img src="<?= e(Media::url($img['src'], 480)) ?>" alt="<?= e($img['legende']) ?>" loading="lazy" decoding="async"
                   width="<?= (int) $img['w'] ?>" height="<?= (int) $img['h'] ?>">
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section-stone" id="videos" aria-labelledby="t-videos">
  <div class="wrap">
    <h2 id="t-videos"><?= e(__('videos')) ?></h2>
    <?php if ($videos): ?>
      <div class="grid-2 videos"><?php foreach ($videos as $v) { include APP . '/templates/partials/video.php'; } ?></div>
    <?php else: ?>
      <p class="empty"><?= e(__('aucune_video')) ?></p>
    <?php endif; ?>
  </div>
</section>

<div class="lightbox" data-lightbox-root hidden role="dialog" aria-modal="true" aria-label="<?= e(__('galerie_titre')) ?>">
  <button class="lb-btn lb-close" type="button" data-lb-close aria-label="<?= e(__('fermer')) ?>"><?= icon('close') ?></button>
  <button class="lb-btn lb-prev" type="button" data-lb-prev aria-label="<?= e(__('precedente')) ?>"><?= icon('prev') ?></button>
  <figure class="lb-figure"><img alt="" data-lb-img><figcaption data-lb-caption></figcaption></figure>
  <button class="lb-btn lb-next" type="button" data-lb-next aria-label="<?= e(__('suivante')) ?>"><?= icon('next') ?></button>
  <p class="lb-count" data-lb-count data-format="<?= e(__('photo_n')) ?>" aria-live="polite"></p>
</div>
<?php
$content = ob_get_clean();
$pageKey = 'galerie';
include APP . '/templates/layout.php';

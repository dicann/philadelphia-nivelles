<?php
$a = Content::get('accueil');
$heroImg = Media::find($a['hero_image'] ?? null);
$heroVideo = !empty($a['hero_video']) && is_file(ROOT . '/' . $a['hero_video']) && str_ends_with(strtolower($a['hero_video']), '.mp4') ? $a['hero_video'] : null;
$videos = Content::videos();
$news = array_slice(Content::news(), 0, 3);
$blocs = ['histoire' => 'histoire', 'projet' => 'projet', 'aider' => 'don'];
ob_start();
?>
<section class="hero<?= $heroImg || $heroVideo ? ' hero-has-media' : '' ?>">
  <div class="hero-media" aria-hidden="true">
    <?php if ($heroVideo): ?>
      <video class="hero-video" autoplay muted loop playsinline preload="metadata"<?= $heroImg ? ' poster="' . e(Media::url($heroImg, 1600)) . '"' : '' ?>>
        <source src="<?= e(base_path() . $heroVideo) ?>" type="video/mp4">
      </video>
    <?php elseif ($heroImg): ?>
      <?= picture($heroImg, '', '100vw', 'hero-img', true) ?>
    <?php else: ?>
      <svg class="hero-arch" viewBox="0 0 200 210" focusable="false"><use href="#logo"/></svg>
    <?php endif; ?>
  </div>
  <div class="wrap hero-inner">
    <div class="hero-text">
      <p class="kicker"><?= e(__('eglise_nom')) ?> · Nivelles</p>
      <h1><?= tx(t($a['hero_titre'])) ?></h1>
      <p class="lead"><?= tx(t($a['hero_texte'])) ?></p>
      <div class="hero-actions">
        <a class="btn btn-gold btn-lg" href="<?= url('don') ?>"><?= icon('heart') ?><?= e(__('faire_un_don')) ?></a>
        <a class="btn btn-ghost-light" href="<?= url('projet') ?>"><?= e(__('nav_projet')) ?><?= icon('arrow') ?></a>
      </div>
    </div>
    <div class="hero-card">
      <?= progress_bar(true) ?>
    </div>
  </div>
</section>

<section class="section section-paper" aria-labelledby="t-blocs">
  <div class="wrap">
    <h2 id="t-blocs" class="visually-hidden"><?= e(__('accueil_titre_page')) ?></h2>
    <div class="grid-3">
      <?php foreach ($blocs as $key => $target): $b = $a['blocs'][$key]; ?>
      <article class="card card-arch">
        <div class="card-media arch-frame"><?= picture($b['image'], t($b['titre']), '(min-width: 900px) 33vw, 100vw') ?></div>
        <div class="card-body">
          <h3 class="card-title"><a href="<?= url($target) ?>"><?= tx(t($b['titre'])) ?></a></h3>
          <p><?= tx(t($b['texte'])) ?></p>
          <span class="card-more" aria-hidden="true"><?= e(__('en_savoir_plus')) ?> <?= icon('arrow') ?></span>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-green" aria-labelledby="t-video">
  <div class="wrap narrow center">
    <p class="kicker"><?= e(__('videos')) ?></p>
    <h2 id="t-video"><?= tx(t($a['video_titre'])) ?></h2>
    <p class="lead"><?= tx(t($a['video_texte'])) ?></p>
    <?php if ($videos): $v = $videos[0]; include APP . '/templates/partials/video.php'; ?>
      <?php if (count($videos) > 1): ?><p><a class="link-light" href="<?= url('galerie') ?>#videos"><?= e(__('voir_tout')) ?> <?= icon('arrow') ?></a></p><?php endif; ?>
    <?php else: ?>
      <p class="empty"><?= e(__('aucune_video')) ?></p>
    <?php endif; ?>
  </div>
</section>

<?php if (Content::newsEnabled()): ?>
<section class="section" aria-labelledby="t-actus">
  <div class="wrap">
    <div class="section-head">
      <h2 id="t-actus"><?= e(__('dernieres_actus')) ?></h2>
      <a href="<?= url('actualites') ?>"><?= e(__('toutes_actus')) ?> <?= icon('arrow') ?></a>
    </div>
    <?php if ($news): ?>
      <div class="grid-3"><?php foreach ($news as $n) { include APP . '/templates/partials/news-card.php'; } ?></div>
    <?php else: ?>
      <p class="empty"><?= e(__('aucune_actu')) ?></p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section class="section section-stone cta-band">
  <div class="wrap narrow center">
    <h2><?= e(__('autres_aides_titre')) ?></h2>
    <p class="lead"><?= tx(t($a['blocs']['aider']['texte'])) ?></p>
    <a class="btn btn-green btn-lg" href="<?= url('don') ?>"><?= icon('heart') ?><?= e(__('faire_un_don')) ?></a>
  </div>
</section>
<?php
$content = ob_get_clean();
$title = __('accueil_titre_page');
$pageKey = 'accueil';
include APP . '/templates/layout.php';

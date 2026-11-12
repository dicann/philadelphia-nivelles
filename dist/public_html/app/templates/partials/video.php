<?php
/** Une vidéo : YouTube (mode confidentialité), Vimeo (dnt) ou fichier MP4. Chargée au clic. Variable : $v */
$titre = t($v['titre']) ?: __('videos');
?>
<figure class="video<?= !empty($v['vertical']) ? ' video-vertical' : '' ?>">
<?php if ($v['type'] === 'fichier'): ?>
  <video controls preload="none" playsinline<?= $v['poster'] ? ' poster="' . e(Media::url($v['poster'], 960)) . '"' : '' ?>>
    <source src="<?= e(base_path() . implode('/', array_map('rawurlencode', explode('/', $v['src'])))) ?>" type="video/mp4">
  </video>
<?php else:
  $provider = $v['type'] === 'youtube' ? 'YouTube' : 'Vimeo';
  $src = $v['type'] === 'youtube'
      ? 'https://www.youtube-nocookie.com/embed/' . $v['id'] . '?autoplay=1&rel=0'
      : 'https://player.vimeo.com/video/' . $v['id'] . '?autoplay=1&dnt=1';
?>
  <button class="video-facade" type="button" data-embed="<?= e($src) ?>" data-title="<?= e($titre) ?>">
    <svg class="video-arch" viewBox="0 0 200 210" aria-hidden="true" focusable="false"><use href="#logo"/></svg>
    <span class="video-play"><?= icon('play') ?></span>
    <span class="video-label"><?= e(__('lire_video') . colon() . $titre) ?></span>
    <span class="video-note"><?= e(sprintf(__('video_consentement'), $provider)) ?></span>
  </button>
<?php endif; ?>
  <figcaption><?= tx($titre) ?></figcaption>
</figure>

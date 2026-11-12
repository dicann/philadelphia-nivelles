<?php
$c = Content::get('contact');
$lang = lang();
$errors = [];
$old = [];
$status = isset($_GET['envoye']) ? 'ok' : null;

// La page contient un jeton horodaté : elle ne doit jamais être servie depuis un cache.
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    [$data, $errors, $robot] = Contact::validate($_POST, $lang);
    $old = $data;
    if ($robot === 'jeton invalide' || $robot === 'jeton expiré') {
        // Page restée ouverte trop longtemps ou servie depuis un cache : on demande simplement de renvoyer.
        $status = 'expire';
    } elseif ($robot !== '') {
        if (Config::get('debug')) {
            $status = 'robot';
        } else {
            // Robot détecté : on fait semblant que tout va bien, sans rien envoyer.
            header('Location: ' . url('contact') . '?envoye=1#formulaire', true, 303);
            exit;
        }
    }
    if (!$errors && $status === null) {
        if (Contact::rateLimited()) {
            $status = 'limite';
        } elseif (Contact::send($data, $lang)) {
            header('Location: ' . url('contact') . '?envoye=1#formulaire', true, 303);
            exit;
        } else {
            $status = 'echec';
        }
    }
}
$gq = trim((string) ($c['carte']['google_recherche'] ?? ''));
$glink = trim((string) ($c['carte']['google_lien'] ?? ''));
$lat = $c['carte']['latitude'] ?? null;
$lon = $c['carte']['longitude'] ?? null;
$hasMap = is_numeric($lat) && is_numeric($lon);
$fieldErr = function (string $k) use ($errors) {
    return isset($errors[$k]) ? '<p class="field-error" id="err-' . $k . '">' . e($errors[$k]) . '</p>' : '';
};
$aria = function (string $k) use ($errors) {
    return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : '';
};
ob_start();
$title = __('contact_titre');
$chapo = __('contact_chapo');
include APP . '/templates/partials/page-head.php';
?>
<section class="section">
  <div class="wrap contact-grid">
    <div class="contact-info">
      <ul class="contact-list">
        <li><?= icon('pin') ?><div><strong><?= e(__('adresse')) ?></strong><br>
          <?= tx($c['adresse']) ?><br><?= e($c['code_postal'] . ' ' . $c['ville']) ?>, <?= e(t($c['pays'])) ?>
          <br><small><?= e(__('eglise_en_travaux')) ?></small></div></li>
        <?php if ($wp = worship_place()): $wpa = $wp['adresse'] . ', ' . $wp['code_postal'] . ' ' . $wp['ville']; ?>
        <li><?= icon('clock') ?><div><strong><?= tx(t($wp['titre'])) ?></strong><br>
          <?= e($wp['adresse']) ?><br><?= e($wp['code_postal'] . ' ' . $wp['ville']) ?>, <?= e(t($c['pays'])) ?>
          <br><a href="<?= e(osm_search($wpa)) ?>" rel="noopener" target="_blank"><?= e(__('itineraire')) ?> ↗</a> · <a href="<?= url('communaute') ?>"><?= e(__('horaires')) ?></a></div></li>
        <?php endif; ?>
        <li><?= icon('phone') ?><div><strong><?= e(__('gsm')) ?></strong>
          <?php foreach (phones() as $p): ?>
            <br><a href="<?= e(tel_href($p['numero'])) ?>"><?= e($p['numero']) ?></a> — <?= e($p['nom']) ?><?php if (!empty($p['role'])): ?>, <small><?= tx(t($p['role'])) ?></small><?php endif; ?>
          <?php endforeach; ?></div></li>
        <li><?= icon('mail') ?><div><strong><?= e(__('email')) ?></strong><br>
          <?php if (is_todo($c['email'])): ?><?= tx($c['email']) ?>
          <?php else: ?><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a><?php endif; ?></div></li>
      </ul>

      <h2 class="h3"><?= e(__('carte')) ?></h2>
      <?php if ($gq !== ''):
        $embed = 'https://www.google.com/maps?q=' . rawurlencode($gq) . '&z=16&hl=' . ($lang === 'ro' ? 'ro' : 'fr') . '&output=embed';
        $open = preg_match('~^https://(maps\.app\.goo\.gl|www\.google\.[a-z.]+/maps|maps\.google\.[a-z.]+)/~', $glink) ? $glink : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($gq);
      ?>
        <div class="map" data-map="<?= e($embed) ?>" data-title="<?= e(__('carte')) ?>">
          <button class="btn btn-green" type="button" data-map-load><?= icon('pin') ?><?= e(__('afficher_carte')) ?></button>
          <p class="field-help"><?= e(__('carte_info')) ?></p>
        </div>
        <p><a href="<?= e($open) ?>" rel="noopener" target="_blank"><?= e(__('ouvrir_google')) ?> ↗</a></p>
      <?php elseif ($hasMap):
        $d = 0.004;
        $bbox = implode(',', [$lon - $d * 1.6, $lat - $d, $lon + $d * 1.6, $lat + $d]);
        $embed = 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode($bbox) . '&layer=mapnik&marker=' . rawurlencode($lat . ',' . $lon);
      ?>
        <div class="map" data-map="<?= e($embed) ?>" data-title="<?= e(__('carte')) ?>">
          <button class="btn btn-green" type="button" data-map-load><?= icon('pin') ?><?= e(__('afficher_carte')) ?></button>
          <p class="field-help"><?= e(__('carte_info')) ?></p>
        </div>
        <p><a href="https://www.openstreetmap.org/directions?to=<?= e(rawurlencode($lat . ',' . $lon)) ?>" rel="noopener" target="_blank"><?= e(__('itineraire')) ?> ↗</a></p>
      <?php else: ?>
        <div class="map map-missing"><?= mark_todo(e(__('carte_indisponible'))) ?></div>
      <?php endif; ?>
    </div>

    <div class="panel" id="formulaire">
      <h2><?= e(__('formulaire')) ?></h2>
      <?php if ($status === 'ok'): ?>
        <div class="alert alert-ok" role="status" tabindex="-1" data-autofocus><?= e(__('envoye')) ?></div>
      <?php elseif ($status === 'echec'): ?>
        <div class="alert alert-error" role="alert" tabindex="-1" data-autofocus><?= e(__('envoi_echec')) ?>
          <?php if (Config::get('debug') && Contact::$error !== ''): ?><br><small>Diagnostic : <?= e(Contact::$error) ?></small><?php endif; ?></div>
      <?php elseif ($status === 'limite'): ?>
        <div class="alert alert-error" role="alert" tabindex="-1" data-autofocus><?= e(__('trop_messages')) ?></div>
      <?php elseif ($status === 'expire'): ?>
        <div class="alert alert-error" role="alert" tabindex="-1" data-autofocus><?= e(__('form_expire')) ?></div>
      <?php elseif ($status === 'robot'): ?>
        <div class="alert alert-error" role="alert" tabindex="-1" data-autofocus>Diagnostic : message bloqué par l'anti-spam (<?= e($robot) ?>).</div>
      <?php elseif ($errors): ?>
        <div class="alert alert-error" role="alert" tabindex="-1" data-autofocus><?= e(__('formulaire_erreurs')) ?></div>
      <?php endif; ?>
      <form method="post" action="<?= url('contact') ?>#formulaire" novalidate>
        <p class="field-help"><?= e(__('champs_requis')) ?></p>
        <div class="field">
          <label for="c-nom"><?= e(__('nom')) ?> *</label>
          <input id="c-nom" name="nom" type="text" autocomplete="name" required maxlength="120" value="<?= e($old['nom'] ?? '') ?>"<?= $aria('nom') ?>>
          <?= $fieldErr('nom') ?>
        </div>
        <div class="field-row">
          <div class="field">
            <label for="c-email"><?= e(__('email')) ?> *</label>
            <input id="c-email" name="email" type="email" autocomplete="email" required maxlength="200" value="<?= e($old['email'] ?? '') ?>"<?= $aria('email') ?>>
            <?= $fieldErr('email') ?>
          </div>
          <div class="field">
            <label for="c-gsm"><?= e(__('gsm_facultatif')) ?></label>
            <input id="c-gsm" name="gsm" type="tel" autocomplete="tel" maxlength="40" value="<?= e($old['gsm'] ?? '') ?>">
          </div>
        </div>
        <div class="field">
          <label for="c-sujet"><?= e(__('sujet')) ?></label>
          <select id="c-sujet" name="sujet">
            <?php $pre = $old['sujet'] ?? ($_GET['sujet'] ?? 'general'); foreach (__('sujets') as $k => $label): ?>
              <option value="<?= e($k) ?>"<?= $pre === $k ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="c-message"><?= e(__('message')) ?> *</label>
          <textarea id="c-message" name="message" rows="6" required maxlength="5000"<?= $aria('message') ?>><?= e($old['message'] ?? '') ?></textarea>
          <?= $fieldErr('message') ?>
        </div>
        <div class="field field-check">
          <input id="c-consent" name="consentement" type="checkbox" value="1"<?= !empty($old['consentement']) ? ' checked' : '' ?><?= $aria('consentement') ?>>
          <label for="c-consent"><?= e(__('consentement')) ?> <a href="<?= url('confidentialite') ?>"><?= e(__('voir_politique')) ?></a></label>
          <?= $fieldErr('consentement') ?>
        </div>
        <div class="hp" aria-hidden="true">
          <label for="c-zx">Ne pas remplir</label>
          <input id="c-zx" name="zx_note" type="text" tabindex="-1" autocomplete="off" data-lpignore="true" data-1p-ignore>
        </div>
        <input type="hidden" name="jeton" value="<?= e(Contact::token()) ?>">
        <button class="btn btn-green btn-lg" type="submit"><?= e(__('envoyer')) ?></button>
      </form>
    </div>
  </div>
</section>
<?php
$content = ob_get_clean();
$pageKey = 'contact';
include APP . '/templates/layout.php';

<?php
$c = Content::get('contact');
$lang = lang();
$errors = [];
$old = [];
$status = isset($_GET['envoye']) ? 'ok' : null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    [$data, $errors, $robot] = Contact::validate($_POST, $lang);
    $old = $data;
    if ($robot) {
        // Robot détecté : on fait semblant que tout va bien, sans rien envoyer.
        header('Location: ' . url('contact') . '?envoye=1#formulaire', true, 303);
        exit;
    }
    if (!$errors) {
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
          <?= tx($c['adresse']) ?><br><?= e($c['code_postal'] . ' ' . $c['ville']) ?>, <?= e(t($c['pays'])) ?></div></li>
        <li><?= icon('phone') ?><div><strong><?= e(__('gsm')) ?></strong><br>
          <?php if (is_todo($c['gsm'])): ?><?= tx($c['gsm']) ?>
          <?php else: ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $c['gsm'])) ?>"><?= e($c['gsm']) ?></a><?php endif; ?>
          <br><small><?= tx(t($c['gsm_nom'])) ?></small></div></li>
        <li><?= icon('mail') ?><div><strong><?= e(__('email')) ?></strong><br>
          <?php if (is_todo($c['email'])): ?><?= tx($c['email']) ?>
          <?php else: ?><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a><?php endif; ?></div></li>
      </ul>

      <h2 class="h3"><?= e(__('carte')) ?></h2>
      <?php if ($hasMap):
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
        <div class="alert alert-error" role="alert" tabindex="-1" data-autofocus><?= e(__('envoi_echec')) ?></div>
      <?php elseif ($status === 'limite'): ?>
        <div class="alert alert-error" role="alert" tabindex="-1" data-autofocus><?= e(__('trop_messages')) ?></div>
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
          <label for="c-consent"><?= e(__('consentement')) ?> <a href="<?= url('confidentialite') ?>"><?= e(__('nav_confidentialite')) ?></a></label>
          <?= $fieldErr('consentement') ?>
        </div>
        <div class="hp" aria-hidden="true">
          <label for="c-site">Site web</label>
          <input id="c-site" name="site_web" type="text" tabindex="-1" autocomplete="off">
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

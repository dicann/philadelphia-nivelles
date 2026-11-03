<?php
$d = Content::get('don');
$errors = $errors ?? [];
$old = $old ?? [];
$enabled = Payments::enabled();
$amounts = array_values(array_filter($d['montants'] ?? [25, 50, 100, 250], 'is_numeric'));
$selAmount = (string) ($old['montant'] ?? ($amounts[1] ?? $amounts[0] ?? ''));
$freq = ($old['frequence'] ?? 'unique') === 'mensuel' ? 'mensuel' : 'unique';
$stageSel = (string) ($old['etape'] ?? ($_GET['etape'] ?? ''));
$stages = Content::get('projet.etapes', []);
$v = $d['virement'];
$provider = Payments::provider() === 'mollie' ? 'Mollie' : 'Stripe';
$lang = lang();
$copyField = function (string $label, string $value) {
    $todo = is_todo($value);
    $out = '<div class="bank-row"><dt>' . e($label) . '</dt><dd><span class="bank-value"' . ($todo ? '' : ' data-copy-src') . '>' . tx($value) . '</span>';
    if (!$todo) {
        $out .= ' <button type="button" class="btn-copy" data-copy data-copied="' . e(__('copie')) . '">' . icon('copy') . '<span>' . e(__('copier')) . '</span></button>';
    }
    return $out . '</dd></div>';
};
ob_start();
$title = __('don_titre');
$chapo = __('don_chapo');
include APP . '/templates/partials/page-head.php';
?>
<section class="section">
  <div class="wrap narrow">
    <p class="lead"><?= tx(t($d['intro'])) ?></p>
    <div class="progress-panel"><?= progress_bar(true) ?></div>
  </div>
</section>

<section class="section section-paper" id="formulaire-don">
  <div class="wrap donate-grid">
    <div class="panel donate-panel">
      <h2><?= e(__('don_en_ligne')) ?></h2>
      <?php if (!$enabled): ?>
        <p class="notice"><?= e(__('dons_indisponibles')) ?></p>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-error" role="alert" tabindex="-1" data-autofocus>
          <p><?= e($errors['paiement'] ?? __('formulaire_erreurs')) ?></p>
          <?php if (!empty($errors['debug'])): ?><p><small><?= e($errors['debug']) ?></small></p><?php endif; ?>
        </div>
      <?php endif; ?>

      <form class="donate-form" method="post" action="<?= url('don_paiement') ?>" data-donate novalidate>
        <fieldset <?= $enabled ? '' : 'disabled' ?>>
          <fieldset class="choice-group">
            <legend><?= e(__('frequence')) ?></legend>
            <div class="segmented">
              <label><input type="radio" name="frequence" value="unique"<?= $freq === 'unique' ? ' checked' : '' ?>><span><?= e(__('unique')) ?></span></label>
              <label><input type="radio" name="frequence" value="mensuel"<?= $freq === 'mensuel' ? ' checked' : '' ?>><span><?= e(__('mensuel')) ?></span></label>
            </div>
          </fieldset>

          <fieldset class="choice-group">
            <legend><?= e(__('montant')) ?></legend>
            <div class="amounts">
              <?php foreach ($amounts as $amt): ?>
                <label class="amount"><input type="radio" name="montant" value="<?= e((string) $amt) ?>"<?= $selAmount === (string) $amt ? ' checked' : '' ?>><span><?= e(money((float) $amt)) ?></span></label>
              <?php endforeach; ?>
              <label class="amount amount-other"><input type="radio" name="montant" value="autre"<?= $selAmount === 'autre' ? ' checked' : '' ?>><span><?= e(__('autre_montant')) ?></span></label>
            </div>
            <div class="field field-other" data-other>
              <label for="montant_libre"><?= e(__('montant_libre_label')) ?></label>
              <div class="input-euro">
                <input id="montant_libre" name="montant_libre" type="text" inputmode="decimal" autocomplete="off"
                       value="<?= e((string) ($old['montant_libre'] ?? '')) ?>"
                       aria-describedby="<?= isset($errors['montant']) ? 'err-montant' : '' ?>"<?= isset($errors['montant']) ? ' aria-invalid="true"' : '' ?>>
                <span aria-hidden="true">€</span>
              </div>
            </div>
            <?php if (isset($errors['montant'])): ?><p class="field-error" id="err-montant"><?= e($errors['montant']) ?></p><?php endif; ?>
          </fieldset>

          <?php if ($stages): ?>
          <div class="field">
            <label for="etape"><?= e(__('etape_choix')) ?></label>
            <select id="etape" name="etape">
              <option value=""><?= e(__('etape_toutes')) ?></option>
              <?php foreach ($stages as $i => $s): if (($s['statut'] ?? '') === 'termine') continue; ?>
                <option value="<?= e($s['id']) ?>"<?= $stageSel === $s['id'] ? ' selected' : '' ?>><?= e(__('etape') . ' ' . ($i + 1) . ' — ' . t($s['titre'])) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>

          <fieldset class="choice-group">
            <legend><?= e(__('vos_coordonnees')) ?></legend>
            <div class="field" data-name-field>
              <label for="nom"><?= e(__('nom')) ?></label>
              <input id="nom" name="nom" type="text" autocomplete="name" maxlength="100" value="<?= e((string) ($old['nom'] ?? '')) ?>">
            </div>
            <div class="field">
              <label for="email"><?= e(__('email')) ?></label>
              <input id="email" name="email" type="email" autocomplete="email" maxlength="200" value="<?= e((string) ($old['email'] ?? '')) ?>"
                     aria-describedby="email-aide<?= isset($errors['email']) ? ' err-email' : '' ?>"<?= isset($errors['email']) ? ' aria-invalid="true"' : '' ?>>
              <p class="field-help" id="email-aide"><?= e(__('email_aide')) ?></p>
              <?php if (isset($errors['email'])): ?><p class="field-error" id="err-email"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>
            <div class="field field-check">
              <input id="anonyme" name="anonyme" type="checkbox" value="1"<?= !empty($old['anonyme']) ? ' checked' : '' ?> aria-describedby="anonyme-aide">
              <label for="anonyme"><?= e(__('anonyme')) ?></label>
              <p class="field-help" id="anonyme-aide"><?= e(__('anonyme_aide')) ?></p>
            </div>
          </fieldset>

          <div class="hp" aria-hidden="true">
            <label for="site_web">Site web</label>
            <input id="site_web" name="site_web" type="text" tabindex="-1" autocomplete="off">
          </div>

          <p class="donate-summary" aria-live="polite" data-summary
             data-label="<?= e(__('resume_don')) ?>" data-month="<?= e(__('par_mois')) ?>" data-lang="<?= e($lang) ?>"></p>

          <button class="btn btn-gold btn-lg btn-block" type="submit"><?= icon('heart') ?><?= e(__('payer')) ?></button>
          <p class="secure-note"><?= e(sprintf(__('paiement_securise'), $provider)) ?><br><span><?= e(__('moyens')) ?></span></p>
        </fieldset>
      </form>
    </div>

    <div class="panel bank-panel" id="virement">
      <h2><?= e(__('virement_titre')) ?></h2>
      <p><?= e(__('virement_texte')) ?></p>
      <dl class="bank">
        <?= $copyField(__('beneficiaire'), (string) $v['beneficiaire']) ?>
        <?= $copyField(__('iban'), (string) $v['iban']) ?>
        <?= $copyField(__('bic'), (string) $v['bic']) ?>
        <?= $copyField(__('communication'), t($v['communication'])) ?>
      </dl>
      <div class="qr">
        <h3 class="h4"><?= e(__('qr_titre')) ?></h3>
        <?php if (Epc::ready()): ?>
          <img class="qr-img" src="<?= base_path() ?>qr-virement.svg?lang=<?= $lang ?>" width="220" height="220"
               alt="<?= e(__('qr_titre')) ?> — <?= e(Epc::normalizeIban((string) $v['iban'])) ?>" data-qr data-base="<?= base_path() ?>qr-virement.svg?lang=<?= $lang ?>">
          <div class="field qr-amount">
            <label for="qr-montant"><?= e(__('qr_montant')) ?></label>
            <div class="input-euro"><input id="qr-montant" type="text" inputmode="decimal" autocomplete="off" data-qr-amount><span aria-hidden="true">€</span></div>
          </div>
          <p class="field-help"><?= e(__('qr_aide')) ?></p>
        <?php else: ?>
          <div class="qr-missing"><?= mark_todo(e('[À COMPLÉTER : ' . __('qr_indisponible') . ']')) ?></div>
        <?php endif; ?>
      </div>
      <?php if (!empty($d['fiscalite']['afficher'])): ?>
        <div class="fiscal">
          <h3 class="h4"><?= e(__('fiscalite_titre')) ?></h3>
          <p><?= tx(t($d['fiscalite']['texte'])) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="t-aides">
  <div class="wrap">
    <h2 id="t-aides" class="center"><?= e(__('autres_aides_titre')) ?></h2>
    <div class="grid-4">
      <?php foreach ($d['autres_aides'] as $aid): ?>
      <article class="help-card">
        <span class="help-icon"><?= icon($aid['icone'] ?? 'heart') ?></span>
        <h3 class="h4"><?= tx(t($aid['titre'])) ?></h3>
        <p><?= tx(t($aid['texte'])) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
    <p class="center"><a class="btn btn-green" href="<?= url('contact') ?>"><?= e(__('proposer_aide')) ?></a></p>
  </div>
</section>
<?php
$content = ob_get_clean();
$pageKey = 'don';
include APP . '/templates/layout.php';

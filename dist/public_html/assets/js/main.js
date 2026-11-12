/* Église Philadelphia Nivelles — scripts du site (sans dépendance). */
(function () {
  'use strict';
  document.documentElement.classList.add('js');
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* ---------- Menu mobile */
  var toggle = $('.nav-toggle'), nav = $('#nav-principale');
  if (toggle && nav) {
    var setOpen = function (open) {
      nav.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    toggle.addEventListener('click', function () { setOpen(!nav.classList.contains('is-open')); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) { setOpen(false); toggle.focus(); }
    });
    document.addEventListener('click', function (e) {
      if (nav.classList.contains('is-open') && !nav.contains(e.target) && !toggle.contains(e.target)) setOpen(false);
    });
  }

  /* ---------- Focus sur les messages d'erreur / de confirmation */
  var af = $('[data-autofocus]');
  if (af) { af.focus(); }

  /* ---------- Vidéo de fond : respecter « réduire les animations » */
  var hv = $('.hero-video');
  if (hv && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { hv.removeAttribute('autoplay'); hv.pause(); }

  /* ---------- Formulaire de don */
  var form = $('[data-donate]');
  if (form) {
    var other = $('[data-other]', form), otherInput = $('#montant_libre', form);
    var summary = $('[data-summary]', form), anon = $('#anonyme', form), nameField = $('[data-name-field]', form);
    var lang = summary ? summary.getAttribute('data-lang') : 'fr';
    var fmt = function (v) {
      var dec = v % 1 !== 0 ? 2 : 0;
      // Montant + « € » à la belge / roumaine (Intl affiche parfois « EUR » en roumain).
      var n;
      try {
        n = new Intl.NumberFormat(lang === 'ro' ? 'ro-RO' : 'fr-BE', { minimumFractionDigits: dec, maximumFractionDigits: dec }).format(v);
      } catch (e) { n = String(v); }
      return n + '\u00a0€';
    };
    var amount = function () {
      var r = $('input[name=montant]:checked', form);
      if (!r) return 0;
      if (r.value === 'autre') return parseFloat((otherInput.value || '').replace(/\s/g, '').replace(',', '.')) || 0;
      return parseFloat(r.value) || 0;
    };
    var update = function () {
      var r = $('input[name=montant]:checked', form);
      var isOther = r && r.value === 'autre';
      if (other) other.classList.toggle('is-visible', !!isOther);
      var monthly = ($('input[name=frequence]:checked', form) || {}).value === 'mensuel';
      var a = amount();
      if (summary) {
        summary.textContent = a > 0 ? summary.getAttribute('data-label') + ' ' + fmt(a) + (monthly ? ' ' + summary.getAttribute('data-month') : '') : '';
      }
      if (anon && nameField) { nameField.hidden = anon.checked; }
    };
    form.addEventListener('change', function (e) {
      update();
      if (e.target.name === 'montant' && e.target.value === 'autre' && otherInput) otherInput.focus();
    });
    if (otherInput) {
      otherInput.addEventListener('input', update);
      otherInput.addEventListener('focus', function () {
        var r = $('input[name=montant][value=autre]', form);
        if (r && !r.checked) { r.checked = true; update(); }
      });
    }
    form.addEventListener('submit', function () {
      var b = $('button[type=submit]', form);
      if (b) { setTimeout(function () { b.disabled = true; }, 0); }
    });
    // Retour arrière depuis la page de paiement : réactiver le bouton.
    window.addEventListener('pageshow', function () { var b = $('button[type=submit]', form); if (b) b.disabled = false; });
    update();
  }

  /* ---------- Copier IBAN / communication */
  $$('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var src = btn.parentNode.querySelector('[data-copy-src]');
      if (!src) return;
      var text = src.textContent.trim();
      var label = btn.querySelector('span'), orig = label.textContent;
      var done = function () { label.textContent = btn.getAttribute('data-copied'); setTimeout(function () { label.textContent = orig; }, 2000); };
      if (navigator.clipboard) { navigator.clipboard.writeText(text).then(done, function () {}); }
    });
  });

  /* ---------- QR : montant facultatif */
  var qr = $('[data-qr]'), qrAmount = $('[data-qr-amount]');
  if (qr && qrAmount) {
    var timer;
    qrAmount.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        var v = parseFloat((qrAmount.value || '').replace(/\s/g, '').replace(',', '.'));
        qr.src = qr.getAttribute('data-base') + (v > 0 ? '&montant=' + v.toFixed(2) : '');
      }, 400);
    });
  }

  /* ---------- Vidéos YouTube / Vimeo : chargées seulement au clic (aucun cookie avant) */
  $$('.video-facade').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var f = document.createElement('iframe');
      f.src = btn.getAttribute('data-embed');
      f.title = btn.getAttribute('data-title') || 'Vidéo';
      f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
      f.allowFullscreen = true;
      f.referrerPolicy = 'strict-origin-when-cross-origin';
      btn.parentNode.replaceChild(f, btn);
      f.focus();
    });
  });

  /* ---------- Carte OpenStreetMap : chargée au clic */
  $$('[data-map-load]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var box = btn.closest('[data-map]');
      var f = document.createElement('iframe');
      f.src = box.getAttribute('data-map');
      f.title = box.getAttribute('data-title') || 'Carte';
      box.textContent = '';
      box.classList.add('is-loaded');
      box.appendChild(f);
    });
  });

  /* ---------- Avant / après */
  $$('[data-compare]').forEach(function (box) {
    var range = $('.compare-range', box);
    if (!range) return;
    box.classList.add('is-active');
    range.hidden = false;
    var set = function () { box.style.setProperty('--pos', range.value + '%'); };
    range.addEventListener('input', set);
    set();
  });

  /* ---------- Lightbox de la galerie */
  var lb = $('[data-lightbox-root]');
  if (lb) {
    var img = $('[data-lb-img]', lb), cap = $('[data-lb-caption]', lb), count = $('[data-lb-count]', lb);
    var items = [], index = 0, opener = null;
    var show = function (i) {
      index = (i + items.length) % items.length;
      var a = items[index];
      img.src = a.getAttribute('href');
      img.alt = a.querySelector('img') ? a.querySelector('img').alt : '';
      cap.textContent = a.getAttribute('data-caption') || '';
      count.textContent = count.getAttribute('data-format').replace('%1$d', index + 1).replace('%2$d', items.length);
      var next = items[(index + 1) % items.length];
      if (next) { (new Image()).src = next.getAttribute('href'); } // préchargement
    };
    var open = function (list, i, from) {
      items = list; opener = from; lb.hidden = false; document.body.style.overflow = 'hidden';
      show(i); $('[data-lb-close]', lb).focus();
    };
    var close = function () {
      lb.hidden = true; document.body.style.overflow = ''; img.removeAttribute('src');
      if (opener) opener.focus();
    };
    $$('[data-gallery]').forEach(function (g) {
      var links = $$('a[data-lightbox]', g);
      links.forEach(function (a, i) {
        a.addEventListener('click', function (e) { e.preventDefault(); open(links, i, a); });
      });
    });
    $('[data-lb-close]', lb).addEventListener('click', close);
    $('[data-lb-prev]', lb).addEventListener('click', function () { show(index - 1); });
    $('[data-lb-next]', lb).addEventListener('click', function () { show(index + 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) return;
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowLeft') show(index - 1);
      else if (e.key === 'ArrowRight') show(index + 1);
      else if (e.key === 'Tab') { // garder le focus dans la fenêtre
        var f = $$('button', lb), first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    var x0 = null; // glisser au doigt
    lb.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) {
      if (x0 === null) return;
      var dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1));
    });
  }
})();

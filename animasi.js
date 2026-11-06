(function () {
  'use strict';

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var io = null;

  if ('IntersectionObserver' in window && !reduce) {
    io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
  }

  function watch(el, delay, arah) {
    if (!io || el.classList.contains('reveal')) return;
    el.classList.add('reveal');
    if (arah) el.classList.add(arah);
    el.style.setProperty('--d', (delay || 0) + 's');
    io.observe(el);
  }
  function semua(selector, fn) {
    Array.prototype.forEach.call(document.querySelectorAll(selector), fn);
  }

  semua('.collage__heading', function (el) { watch(el, 0, 'left'); });
  semua('.collage__desc',    function (el) { watch(el, 0.15, 'left'); });
  semua('.collage__item',    function (el, i) { watch(el, 0.1 + i * 0.15); });

  // Siapa Kami? / Kenapa Callista?
  semua('.intro-card', function (el, i) { watch(el, i * 0.15, i % 2 ? 'right' : 'left'); });

  // Kami Menjamin Kualitas
  semua('.quality__heading',   function (el) { watch(el); });
  semua('.quality__card',      function (el, i) { watch(el, (i % 2) * 0.12, i % 2 ? 'right' : 'left'); });
  semua('.quality__cta-text',  function (el) { watch(el, 0, 'left'); });
  semua('.quality__cta-actions', function (el) { watch(el, 0.15, 'right'); });

  // Pilih Dessertmu! (Beranda) + judul/kartu di halaman Menu
  semua('.menu__heading, .banner-wrap, .cta-card', function (el) { watch(el); });
  semua('.menu__card', function (el, i) { watch(el, (i % 4) * 0.1); });

  // Promo
  semua('.promo__heading',    function (el) { watch(el); });
  semua('.promo__desc',       function (el) { watch(el, 0.1); });
  semua('.promo__badge',      function (el, i) { watch(el, 0.2 + i * 0.12, 'pop'); });
  semua('.promo__left > .btn', function (el) { watch(el, 0.45); });
  semua('.promo__right',      function (el) { watch(el, 0.1, 'fade'); });

  // Lokasi
  semua('.location__badge',   function (el) { watch(el); });
  semua('.location__heading', function (el) { watch(el, 0.1); });
  semua('.location__desc',    function (el) { watch(el, 0.2); });
  semua('.location__text > .btn', function (el) { watch(el, 0.3); });
  semua('.location__map-col', function (el) { watch(el, 0.15, 'right'); });

  // Lokasi Kami (cabang)
  semua('.branches__heading', function (el) { watch(el); });
  semua('.branches__tabs',    function (el) { watch(el, 0.1); });
  semua('.branches__hint',    function (el) { watch(el, 0.15); });
  semua('.branches__panel', function (panel) {
    Array.prototype.forEach.call(panel.querySelectorAll('.branches__item'), function (el, i) {
      watch(el, Math.min(i, 8) * 0.05);
    });
  });

  // Ganti kota -> kartu cabang muncul lagi
  semua('.branches__tab', function (tab) {
    tab.addEventListener('click', function () {
      var panel = document.querySelector('.branches__panel:not(.is-hidden)');
      if (!panel || !io) return;
      Array.prototype.forEach.call(panel.querySelectorAll('.branches__item'), function (el, i) {
        if (!el.classList.contains('is-visible')) return; // belum pernah tampil: diurus observer
        el.style.setProperty('--d', Math.min(i, 8) * 0.05 + 's');
        el.classList.remove('is-visible');
        void el.offsetWidth;
        el.classList.add('is-visible');
      });
    });
  });

  // Footer
  semua('.footer__logo, .footer__nav, .footer__actions', function (el, i) { watch(el, i * 0.12); });

  /* ---------- Menu ---------- */
  // Kartu produk dibuat ulang oleh menu.js tiap ganti kategori -> pantau terus.
  var grid = document.getElementById('productGrid');
  if (grid) {
    var tandai = function () {
      Array.prototype.forEach.call(grid.children, function (el, i) { watch(el, (i % 4) * 0.08); });
    };
    new MutationObserver(tandai).observe(grid, { childList: true });
    tandai();
  }

  // Judul banner "membal" tiap ganti kategori.
  var title = document.getElementById('bannerTitle');
  if (title && !reduce) {
    new MutationObserver(function () {
      title.classList.remove('is-pop');
      void title.offsetWidth;
      title.classList.add('is-pop');
    }).observe(title, { childList: true, characterData: true, subtree: true });
  }
})();
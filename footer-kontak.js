(function () {
  var footer = document.querySelector('.site-footer');
  if (!footer) return;

  function setText(el, text) {
    if (!el) return;
    var node = null;
    el.childNodes.forEach(function (n) {
      if (n.nodeType === 3 && n.nodeValue.trim() !== '') node = n;
    });
    if (node) node.nodeValue = text;
    else el.appendChild(document.createTextNode(text));
  }

  function waLink(nomor) {
    var digit = String(nomor).replace(/\D/g, '');
    if (digit.charAt(0) === '0') digit = '62' + digit.slice(1);
    return 'https://wa.me/' + digit;
  }

  function sosmedLink(platform, nilai) {
    if (/^https?:\/\//i.test(nilai)) return nilai;
    var user = nilai.replace(/^@/, '');
    var p = platform.toLowerCase();
    if (p === 'instagram') return 'https://www.instagram.com/' + user + '/';
    if (p === 'tiktok') return 'https://www.tiktok.com/@' + user;
    if (p === 'facebook') return 'https://www.facebook.com/' + user;
    return '#';
  }

  fetch('admin.php?aksi=publik', { cache: 'no-store' })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (!d || d.error) return;
      var k = d.kontak || {};

      if (k.whatsapp) {
        var wa = footer.querySelector('a[href^="https://wa.me"]');
        if (wa) { wa.href = waLink(k.whatsapp); setText(wa, k.whatsapp); }
      }
      if (k.email) {
        var em = footer.querySelector('a[href*="mail.google.com"]');
        if (em) {
          em.href = 'https://mail.google.com/mail/?view=cm&fs=1&to=' + encodeURIComponent(k.email);
          setText(em, k.email);
        }
      }
      var baris = footer.querySelectorAll('.site-footer__col--line .site-footer__item');
      if (k.alamat && baris[0]) setText(baris[0], k.alamat);
      if (k.jam_operasional && baris[1]) setText(baris[1], k.jam_operasional);

      (d.media_sosial || []).forEach(function (s) {
        var a = null;
        footer.querySelectorAll('.site-footer__social a').forEach(function (x) {
          if ((x.getAttribute('aria-label') || '').toLowerCase() === s.platform.toLowerCase()) a = x;
        });
        if (!a) return;
        a.href = sosmedLink(s.platform, s.link);
        a.target = '_blank';
        a.rel = 'noopener';
        a.style.display = s.aktif ? '' : 'none';
      });
    })
    .catch(function () {});
})();

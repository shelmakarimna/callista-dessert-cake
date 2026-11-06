const $ = (selector, root = document) =>
  root.querySelector(selector);

const $$ = (selector, root = document) =>
  [...root.querySelectorAll(selector)];

/* DATA PRODUK & KATEGORI (diambil dari database lewat admin.php) */
let products = [];
let categories = [];

let riwayat = [];

let stock = [];
let mitraList = [];
let produkList = [];
let editingStockId = null;
const PERAN = window.PERAN || 'admin';

const fmtTanggal = text => {
  const d = new Date(String(text) + 'T00:00:00');
  return isNaN(d) ? text : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

/* DATA KONTAK & MEDIA SOSIAL (diambil dari database;*/
let contact = {
  whatsapp: '+62 123 456 7890',
  email: 'email@gmail.com',
  alamat: 'Jl. Cabang, Banyumas',
  jam_operasional: '08.00–21.00'
};

let socials = [
  { id: 0, platform: 'Instagram', handle: '@callista.dessertcake', active: true },
  { id: 0, platform: 'TikTok', handle: '@callista.dessertcake', active: true },
  { id: 0, platform: 'Facebook', handle: '@callista.dessertcake', active: true }
];

/* PENGHUBUNG KE DATABASE */
async function api(aksi, data = {}) {
  const res = await fetch('admin.php?aksi=' + aksi, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'fetch'
    },
    body: JSON.stringify(data)
  });

  let json = {};

  try {
    json = await res.json();
  } catch (error) {
    throw new Error('Database belum tersambung');
  }

  if (!res.ok || json.error) {
    throw new Error(json.error || 'Gagal menyimpan');
  }

  return json;
}

// Dipakai untuk form yang ada upload foto (FormData)
async function apiForm(aksi, formData) {
  const res = await fetch('admin.php?aksi=' + aksi, {
    method: 'POST',
    headers: { 'X-Requested-With': 'fetch' },
    body: formData
  });

  let json = {};

  try {
    json = await res.json();
  } catch (error) {
    throw new Error('Database belum tersambung');
  }

  if (!res.ok || json.error) {
    throw new Error(json.error || 'Gagal menyimpan');
  }

  return json;
}

function renderRiwayat() {
  const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  const fmtWaktu = w => {
    const m = String(w || '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
    return m ? `${Number(m[3])} ${bulan[Number(m[2]) - 1]} ${m[1]}, ${m[4]}.${m[5]}` : '';
  };

  $('#historyRows').innerHTML = riwayat
    .slice(0, 6)
    .map(item => `
      <tr class="${/hapus/i.test(item.aksi) ? 'row-del' : 'row-add'}">
        <td>${esc(item.nama_produk)}</td>
        <td>${esc(item.nama_kategori)}</td>
        <td>${money(item.harga)}</td>
        <td>${esc(item.aksi)}<br><small>${esc(fmtWaktu(item.waktu))}</small></td>
      </tr>
    `)
    .join('');
}

function terapkan(data) {
  if (data.riwayat) {
    riwayat = data.riwayat;
    renderRiwayat();
  }

  if (data.kontak && Object.keys(data.kontak).length) {
    contact = Object.assign(contact, data.kontak);
  }

  socials = (data.media_sosial || []).map(item => ({
    id: item.id,
    platform: item.platform,
    handle: item.link,
    active: item.aktif
  }));

  if (data.kategori) categories = data.kategori;

  if (data.produk) {
    products = data.produk.map(p => ({
      id: Number(p.id),
      id_kategori: Number(p.id_kategori),
      name: p.nama,
      category: p.kategori,
      price: Number(p.harga),
      desc: p.deskripsi || '',
      gambar: p.gambar
    }));
  }

  renderContact();
  renderSocials();
  renderProducts();
}

/* IKON (SVG) */
const svg = (inner, extra = '') =>
  `<svg class="i" viewBox="0 0 24 24" ${extra}>${inner}</svg>`;

const ICON = {
  phone: svg('<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M9.2 8.4c.3 1.6 1.7 3.6 3.2 4.6 1 .6 1.7.5 2.2-.3l-.9-1.1-.9.2c-.8-.4-1.5-1.1-1.9-1.9l.3-.8-1.1-.9c-.6.5-.9 1.2-.9 2.2z" stroke-width="1.2"/>'),
  mail: svg('<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>'),
  pin: svg('<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>'),
  clock: svg('<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>'),
  edit: svg('<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>'),
  trash: svg('<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>')
};

const contactIcons = [ICON.phone, ICON.mail, ICON.pin, ICON.clock];

const SOCIAL_ICON = {
  instagram: '<svg viewBox="0 0 32 32"><defs><linearGradient id="ig" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#fcb045"/><stop offset=".5" stop-color="#fd1d1d"/><stop offset="1" stop-color="#833ab4"/></linearGradient></defs><rect width="32" height="32" rx="9" fill="url(#ig)"/><rect x="8" y="8" width="16" height="16" rx="5" fill="none" stroke="#fff" stroke-width="2"/><circle cx="16" cy="16" r="3.8" fill="none" stroke="#fff" stroke-width="2"/><circle cx="21" cy="11" r="1.2" fill="#fff"/></svg>',
  tiktok: '<svg viewBox="0 0 32 32"><circle cx="16" cy="16" r="16" fill="#111"/><path d="M17.5 8v10.2a2.7 2.7 0 1 1-2.7-2.7" fill="none" stroke="#25f4ee" stroke-width="2.2" stroke-linecap="round" transform="translate(-.7 .5)"/><path d="M17.5 8v10.2a2.7 2.7 0 1 1-2.7-2.7M17.5 8c.3 2.2 1.8 3.6 4 3.8" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>',
  facebook: '<svg viewBox="0 0 32 32"><circle cx="16" cy="16" r="16" fill="#1877f2"/><path d="M18 26V17h3l.5-3.5H18v-2c0-1 .4-1.8 1.9-1.8h1.7V6.6c-.3 0-1.4-.2-2.6-.2-2.7 0-4.4 1.6-4.4 4.5v2.6h-3V17h3v9z" fill="#fff"/></svg>',
  other: '<svg viewBox="0 0 32 32"><circle cx="16" cy="16" r="16" fill="#111"/><path d="M11 21 21 11M13 11h8v8" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
};

function socialIcon(platform) {
  return SOCIAL_ICON[String(platform).toLowerCase()] || SOCIAL_ICON.other;
}

const PRODUCT_IMG = {
  'Mochi': 'asset-callista/images/mix.png',
  'Mochi Cake': 'asset-callista/images/cake_mochi.png',
  'Crepe Cake': 'asset-callista/images/mile_crepe.png',
  'Chewy Cookie': 'asset-callista/images/dubai.png',
  'Dubai Chewy': 'asset-callista/images/dubai.png'
};

let editingProductId = null;
let editingSocialIndex = null;

const esc = text =>
  String(text).replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[char]));

const money = number =>
  Number(number).toLocaleString('id-ID');

function toast(message) {
  const element = $('#toast');

  element.textContent = message;
  element.classList.add('show');

  clearTimeout(toast.timer);

  toast.timer = setTimeout(() => {
    element.classList.remove('show');
  }, 2400);
}

function categoryClass(category) {
  if (category === 'Mochi') return 'category-mochi';
  if (category === 'Crepe Cake') return 'category-crepe';
  if (category === 'Chewy Cookie' || category === 'Dubai Chewy') return 'category-cookie';

  return 'category-cake';
}

/* FOTO PRODUK LAMA (sama seperti di menu.js) */
const IMG_DIR = 'asset-callista/images/';
const IMG_EXT = ['.png', '.webp', '.jpg', '.jpeg', '.avif', '', '.PNG', '.JPG'];
const fotoProduk = {
  1: 'Anggur 5.5k', 2: 'Mille crepes 16k_slice', 3: 'Bday mochi vanilla stw 79k',
  4: 'Beng beng vanilla 8.9k', 5: 'Milo Mille crepes 16k', 6: 'Bday mochi 79k',
  7: 'Brownies mochi 8.9k', 8: 'Mille crepes 150-155k_loyang', 9: 'Bday mochi stw choco start 79k',
  10: 'Chese 8.9k', 11: 'Choco beng beng 8.9k', 12: 'Choco chese 8.9k', 13: 'Choco paste 8.9k',
  14: 'Cookies n cream 5.5k', 15: 'Creamy durian 8.9k', 16: 'Dubai chewy cookie 28k',
  17: 'Dubai chewy ube 25k', 18: 'Full durian 10k', 19: 'Lotus 8.9k', 20: 'Mangga vanilla 8.9k',
  21: 'Matcha cookies 8.9k', 22: 'Matcha mangga 8.9k', 23: 'Muscat 8.9k', 24: 'Peach 10k',
  25: 'Pistachio kunafa mochi 9.5k', 26: 'Red bean 8.9k', 27: 'Redvelvet 8.9k',
  28: 'Silverqueen 8.9k', 29: 'Stw choco cream 8.9k', 30: 'Stw choco paste 8.9k',
  31: 'Stw matcha 5.5k', 32: 'Stw redbean 8.9k', 33: 'Stw vanilla 5.5k', 34: 'Tiramisu 8.9k',
  35: 'Bday mochhi oreo 79k', 36: 'Mille crepes 150-155k_loyang(1)'
};

// Gambar dicoba satu per satu sampai ada yang berhasil dimuat
document.addEventListener('error', event => {
  const img = event.target;
  if (!img || img.tagName !== 'IMG' || !img.dataset.alt) return;

  const list = JSON.parse(img.dataset.alt);

  if (list.length) {
    img.src = list.shift();
    img.dataset.alt = JSON.stringify(list);
  } else {
    img.removeAttribute('data-alt');
  }
}, true);

function thumb(category, gambar, id) {
  let list = [];

  if (gambar) {
    list.push(gambar);
  } else if (id && fotoProduk[id]) {
    list = IMG_EXT.map(ext => IMG_DIR + encodeURIComponent(fotoProduk[id]) + ext);
  }

  list.push(PRODUCT_IMG[category] || PRODUCT_IMG['Mochi']);

  const first = list.shift();

  return `
    <div class="product-thumb">
      <img src="${esc(first)}" data-alt="${esc(JSON.stringify(list))}" alt="">
    </div>
  `;
}

/* PAGINATION (nomor halaman) */
const PER_PAGE = 8;
let productPageNow = 1;
let stockPageNow = 1;

function pageNumbers(total, page) {
  const out = [];

  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || Math.abs(i - page) <= 1) {
      out.push(i);
    } else if (out[out.length - 1] !== '…') {
      out.push('…');
    }
  }

  return out;
}

function renderPager(box, total, page, go) {
  if (!box) return;

  if (total <= 1) {
    box.innerHTML = '';
    return;
  }

  box.innerHTML =
    `<button data-go="${page - 1}" ${page === 1 ? 'disabled' : ''}>‹</button>` +
    pageNumbers(total, page).map(n =>
      n === '…'
        ? '<span class="dots">…</span>'
        : `<button data-go="${n}" class="${n === page ? 'active' : ''}">${n}</button>`
    ).join('') +
    `<button data-go="${page + 1}" ${page === total ? 'disabled' : ''}>›</button>`;

  box.onclick = event => {
    const button = event.target.closest('button[data-go]');
    if (!button || button.disabled) return;
    go(Number(button.dataset.go));
  };
}

/* NAVIGASI HALAMAN */
function showPage(page) {
  $$('.view').forEach(view => {
    view.classList.add('hidden');
  });

  $('#view-' + page).classList.remove('hidden');

  $$('.nav-item').forEach(button => {
    button.classList.toggle(
      'active',
      button.dataset.page === page
    );
  });

  const labels = {
    menu: 'Admin - Menu',
    stock: PERAN === 'mitra' ? 'Mitra - Stok' : 'Admin - Stok',
    contact: 'Admin - Kontak'
  };

  $('#pageLabel').textContent = labels[page];

  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  });
}

$$('.nav-item').forEach(button => {
  button.addEventListener('click', () => {
    showPage(button.dataset.page);
  });
});

/* KATALOG PRODUK */
function renderProducts() {
  const query = $('#productSearch').value.toLowerCase();

  const filteredProducts = products.filter(product =>
    (product.name + ' ' + product.category)
      .toLowerCase()
      .includes(query)
  );

  const totalPages = Math.max(1, Math.ceil(filteredProducts.length / PER_PAGE));
  productPageNow = Math.min(productPageNow, totalPages);

  const pageProducts = filteredProducts.slice(
    (productPageNow - 1) * PER_PAGE,
    productPageNow * PER_PAGE
  );

  renderPager($('.product-panel .pagination'), totalPages, productPageNow, n => {
    productPageNow = n;
    renderProducts();
    $('#selectAllProducts').checked = false;
  });

  $('#productRows').innerHTML = pageProducts.map(product => `
    <tr>
      <td>
        <input type="checkbox"
          class="product-check"
          data-id="${product.id}">
      </td>
      <td>${thumb(product.category, product.gambar, product.id)}</td>
      <td>${esc(product.name)}</td>
      <td>${esc(product.category)}</td>
      <td>${money(product.price)}</td>
    </tr>
  `).join('');

  $('#totalProducts').textContent = products.length;

  renderRiwayat();
}

$('#productSearch').addEventListener('input', () => {
  productPageNow = 1;
  renderProducts();
});

function openProduct(id = null) {
  editingProductId = id;

  const form = $('#productForm');

  form.reset();

  form.elements.id_kategori.innerHTML = categories
    .map(c => `<option value="${c.id}">${esc(c.nama)}</option>`)
    .join('');

  $('#productModalTitle').textContent =
    id ? 'Edit Produk' : 'Tambah Produk';

  if (id !== null) {
    const product = products.find(item => item.id === id);

    if (product) {
      form.elements.nama.value = product.name;
      form.elements.id_kategori.value = product.id_kategori;
      form.elements.harga.value = product.price;
      form.elements.deskripsi.value = product.desc;
    }
  }

  $('#productDialog').showModal();
}

$('#addProduct').addEventListener('click', () => {
  openProduct();
});

$('#addProductTop').addEventListener('click', () => {
  openProduct();
});

$('#productForm').addEventListener('submit', async event => {
  event.preventDefault();

  const form = event.currentTarget;
  const formData = new FormData(form);
  const sedangEdit = editingProductId !== null;

  if (sedangEdit) formData.append('id', editingProductId);

  try {
    terapkan(await apiForm(sedangEdit ? 'ubah_produk' : 'tambah_produk', formData));


    $('#productDialog').close();
    renderProducts();
    toast(sedangEdit ? 'Produk berhasil diperbarui' : 'Produk berhasil ditambahkan');
  } catch (error) {
    toast(error.message);
  }
});

/* EDIT PRODUK DENGAN KLIK DUA KALI */
$('#productRows').addEventListener('dblclick', event => {
  const row = event.target.closest('tr');

  if (!row) return;

  const checkbox = $('.product-check', row);
  const id = Number(checkbox?.dataset.id);

  if (id) openProduct(id);
});

/* HAPUS PRODUK */
$('#deleteProducts').addEventListener('click', async () => {
  const ids = $$('.product-check:checked')
    .map(checkbox => Number(checkbox.dataset.id));

  if (!ids.length) {
    toast('Pilih produk yang ingin dihapus terlebih dahulu');
    return;
  }

  if (!confirm(`Hapus ${ids.length} produk? Tindakan ini tidak bisa dibatalkan.`)) return;

  try {
    terapkan(await api('hapus_produk', { ids }));


    $('#selectAllProducts').checked = false;
    renderProducts();
    toast(`${ids.length} produk berhasil dihapus`);
  } catch (error) {
    toast(error.message);
  }
});

$('#selectAllProducts').addEventListener('change', event => {
  $$('.product-check').forEach(checkbox => {
    checkbox.checked = event.target.checked;
  });
});

/* PEMANTAUAN STOK MITRA */
function statusFor(item) {
  const remaining = item.start - item.sold;

  if (remaining === 0) {
    return '<span class="status empty">Habis</span>';
  }

  if (remaining <= 3) {
    return '<span class="status low">Stok Menipis</span>';
  }

  return '<span class="status safe">Aman</span>';
}

function renderStock() {
  const query = $('#stockSearch').value.toLowerCase();
  const category = $('#categoryFilter').value;
  const partner = $('#partnerFilter').value;

  const filteredStock = stock.filter(item =>
    (item.name + ' ' + item.partner).toLowerCase().includes(query) &&
    (!category || item.category === category) &&
    (!partner || item.partner === partner)
  );

  const totalPages = Math.max(1, Math.ceil(filteredStock.length / PER_PAGE));
  stockPageNow = Math.min(stockPageNow, totalPages);

  const pageStock = filteredStock.slice(
    (stockPageNow - 1) * PER_PAGE,
    stockPageNow * PER_PAGE
  );

  renderPager($('.stock-panel .pagination'), totalPages, stockPageNow, n => {
    stockPageNow = n;
    renderStock();
    $('#selectAllStock').checked = false;
  });

  $('#stockRows').innerHTML = pageStock.map(item => `
    <tr>
      <td>
        <input type="checkbox"
          class="stock-check"
          data-id="${item.id}">
      </td>
      <td>
        <div class="stock-product">
          ${thumb(item.category, item.gambar, item.produkId)}
          <span>${esc(item.name)}</span>
        </div>
      </td>
      <td>
        <span class="category-pill ${categoryClass(item.category)}">
          ${esc(item.category)}
        </span>
      </td>
      <td>${esc(item.partner)}</td>
      <td>${item.start}</td>
      <td>${item.sold}</td>
      <td>${item.start - item.sold}</td>
      <td>${statusFor(item)}</td>
      <td>${fmtTanggal(item.date)}</td>
    </tr>
  `).join('');

  $('#stockProductCount').textContent =
    new Set(stock.map(item => item.produkId)).size;
  $('#stockMitraCount').textContent = mitraList.length;
}

function terapkanStok(data) {
  if (data.riwayat) {
    riwayat = data.riwayat;
    renderRiwayat();
  }

  mitraList = data.mitra || [];
  produkList = data.produk || [];

  stock = (data.stok || []).map(item => ({
    id: item.id,
    mitraId: item.id_mitra,
    produkId: item.id_produk,
    name: item.produk,
    category: item.kategori,
    gambar: item.gambar,
    partner: item.mitra,
    start: item.awal,
    sold: item.terjual,
    date: item.tanggal
  }));

  const isiFilter = (selector, label, values) => {
    const el = $(selector);
    const dipilih = el.value;
    el.innerHTML = `<option value="">${label}</option>` +
      values.map(v => `<option>${esc(v)}</option>`).join('');
    el.value = dipilih;
  };

  isiFilter('#partnerFilter', 'Semua Mitra', mitraList.map(m => m.nama));
  isiFilter('#categoryFilter', 'Semua Kategori', [...new Set(stock.map(s => s.category))]);

  renderStock();
}

['stockSearch', 'categoryFilter', 'partnerFilter'].forEach(id => {
  $('#' + id).addEventListener('input', () => {
    stockPageNow = 1;
    renderStock();
  });
});

$('#selectAllStock').addEventListener('change', event => {
  $$('.stock-check').forEach(checkbox => {
    checkbox.checked = event.target.checked;
  });
});

/* TAMBAH / EDIT / HAPUS STOK */
function openStock(id = null) {
  editingStockId = id;

  const form = $('#stockForm');
  form.reset();

  $('#stockModalTitle').textContent = id ? 'Edit Stok' : 'Tambah Stok';

  form.elements.mitra.innerHTML = mitraList
    .map(m => `<option value="${m.id}">${esc(m.nama)}</option>`).join('');
  form.elements.produk.innerHTML = produkList
    .map(p => `<option value="${p.id}">${esc(p.nama)} (${esc(p.kategori)})</option>`).join('');

  $('#stockMitraField').hidden = PERAN === 'mitra';
  form.elements.mitra.disabled = id !== null;
  form.elements.produk.disabled = id !== null;

  if (id !== null) {
    const item = stock.find(s => s.id === id);
    if (!item) return;
    form.elements.mitra.value = item.mitraId;
    form.elements.produk.value = item.produkId;
    form.elements.awal.value = item.start;
    form.elements.terjual.value = item.sold;
  }

  $('#stockDialog').showModal();
}

$('#addStock').addEventListener('click', () => openStock());

$('#stockRows').addEventListener('dblclick', event => {
  const row = event.target.closest('tr');
  if (!row) return;
  const id = Number($('.stock-check', row)?.dataset.id);
  if (id) openStock(id);
});

$('#stockForm').addEventListener('submit', async event => {
  event.preventDefault();

  const form = event.currentTarget;
  const wasEditing = editingStockId !== null;

  try {
    terapkanStok(await api('stok_simpan', {
      id: wasEditing ? editingStockId : 0,
      id_mitra: form.elements.mitra.value,
      id_produk: form.elements.produk.value,
      awal: form.elements.awal.value,
      terjual: form.elements.terjual.value
    }));

    $('#stockDialog').close();
    toast(wasEditing ? 'Stok berhasil diperbarui' : 'Stok berhasil ditambahkan');
  } catch (error) {
    toast(error.message);
  }
});

$('#deleteStock').addEventListener('click', async () => {
  const ids = $$('.stock-check:checked').map(box => Number(box.dataset.id));

  if (!ids.length) {
    toast('Pilih stok yang ingin dihapus terlebih dahulu');
    return;
  }

  if (!confirm(`Hapus ${ids.length} data stok?`)) return;

  try {
    terapkanStok(await api('stok_hapus', { ids }));
    $('#selectAllStock').checked = false;
    toast(`${ids.length} data stok berhasil dihapus`);
  } catch (error) {
    toast(error.message);
  }
});

/* EKSPOR DATA STOK KE EXCEL */
$('#exportStock').addEventListener('click', () => {
  const headers = [
    'Produk',
    'Kategori',
    'Mitra',
    'Stok Awal',
    'Stok Terjual',
    'Sisa Stok',
    'Status',
    'Terakhir Update'
  ];

  const rows = stock.map(item => {
    const remaining = item.start - item.sold;

    const status =
      remaining === 0
        ? 'Habis'
        : remaining <= 3
          ? 'Stok Menipis'
          : 'Aman';

    return [
      item.name,
      item.category,
      item.partner,
      item.start,
      item.sold,
      remaining,
      status,
      fmtTanggal(item.date)
    ];
  });

  const csv = [headers, ...rows]
    .map(row =>
      row.map(value =>
        '"' + String(value).replaceAll('"', '""') + '"'
      ).join(',')
    )
    .join('\r\n');

  const blob = new Blob(
    ['\ufeff' + csv],
    { type: 'text/csv;charset=utf-8;' }
  );

  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');

  link.href = url;
  link.download = 'data-stok-mitra.csv';
  link.click();

  URL.revokeObjectURL(url);

  toast('Data stok berhasil diekspor');
});

/* INFORMASI KONTAK */
function renderContact() {
  const items = [
    ['WhatsApp', contact.whatsapp],
    ['Email', contact.email],
    ['Alamat', contact.alamat],
    ['Jam Operasional', contact.jam_operasional]
  ];

  $('#contactCards').innerHTML = items.map(([label, value], index) => `
    <div class="contact-card">
      <span class="contact-icon">${contactIcons[index]}</span>
      <strong>${label}</strong>
      <p>${esc(value)}</p>
    </div>
  `).join('');
}

$('#editContact').addEventListener('click', () => {
  const form = $('#contactForm');

  form.elements.whatsapp.value = contact.whatsapp;
  form.elements.email.value = contact.email;
  form.elements.address.value = contact.alamat;
  form.elements.hours.value = contact.jam_operasional;

  $('#contactDialog').showModal();
});

$('#contactForm').addEventListener('submit', async event => {
  event.preventDefault();

  const form = event.currentTarget;

  try {
    terapkan(await api('simpan_kontak', {
      whatsapp: form.elements.whatsapp.value,
      email: form.elements.email.value,
      alamat: form.elements.address.value,
      jam_operasional: form.elements.hours.value
    }));

    $('#contactDialog').close();

    toast('Informasi kontak berhasil diperbarui');
  } catch (error) {
    toast(error.message);
  }
});

/* MEDIA SOSIAL */
function renderSocials() {
  $('#socialRows').innerHTML = socials.map((social, index) => `
    <tr>
      <td>
        <input type="checkbox"
          class="social-check"
          data-index="${index}">
      </td>
      <td>
        <div class="platform">
          <span class="platform-icon">
            ${socialIcon(social.platform)}
          </span>
          ${esc(social.platform)}
        </div>
      </td>
      <td>${esc(social.handle)}</td>
      <td>
        <span class="active-status ${social.active ? '' : 'off'}"
          data-index="${index}"
          title="Klik untuk ${social.active ? 'menonaktifkan' : 'mengaktifkan'}">
          ${social.active ? 'Aktif' : 'Nonaktif'}
        </span>
      </td>
      <td>
        <button class="action-btn edit-social"
          data-index="${index}" title="Edit">${ICON.edit}</button>
        <button class="action-btn delete-social"
          data-index="${index}" title="Hapus">${ICON.trash}</button>
      </td>
    </tr>
  `).join('');
}

function openSocial(index = null) {
  editingSocialIndex = index;

  const form = $('#socialForm');

  form.reset();

  $('#socialModalTitle').textContent =
    index === null
      ? 'Tambah Media Sosial'
      : 'Edit Media Sosial';

  if (index !== null) {
    form.elements.platform.value = socials[index].platform;
    form.elements.handle.value = socials[index].handle;
  }

  $('#socialDialog').showModal();
}

$('#addInfo').addEventListener('click', () => {
  openSocial();
});

$('#socialRows').addEventListener('click', async event => {
  const editButton = event.target.closest('.edit-social');
  const deleteButton = event.target.closest('.delete-social');
  const statusButton = event.target.closest('.active-status');

  try {
    if (editButton) {
      openSocial(Number(editButton.dataset.index));
    }

    if (deleteButton) {
      const social = socials[Number(deleteButton.dataset.index)];

      terapkan(await api('hapus_sosmed', { id: social.id }));

      toast('Akun media sosial dihapus');
    }

    if (statusButton) {
      const social = socials[Number(statusButton.dataset.index)];

      terapkan(await api('status_sosmed', {
        id: social.id,
        aktif: !social.active
      }));

      toast(social.active ? 'Media sosial diaktifkan' : 'Media sosial dinonaktifkan');
    }
  } catch (error) {
    toast(error.message);
  }
});

$('#selectAllSocial').addEventListener('change', event => {
  $$('.social-check').forEach(checkbox => {
    checkbox.checked = event.target.checked;
  });
});

$('#socialForm').addEventListener('submit', async event => {
  event.preventDefault();

  const form = event.currentTarget;

  const data = {
    platform: form.elements.platform.value.trim(),
    link: form.elements.handle.value.trim()
  };

  try {
    if (editingSocialIndex === null) {
      terapkan(await api('tambah_sosmed', data));
    } else {
      data.id = socials[editingSocialIndex].id;
      terapkan(await api('ubah_sosmed', data));
    }

    $('#socialDialog').close();

    toast('Informasi media sosial berhasil disimpan');
  } catch (error) {
    toast(error.message);
  }
});

/* TUTUP MODAL */
$$('[data-close]').forEach(button => {
  button.addEventListener('click', () => {
    $('#' + button.dataset.close).close();
  });
});

/* LOGOUT DEMO */
$('#logoutBtn').addEventListener('click', () => {
  toast('Sesi admin demo tetap aktif');
});

/* TAMPILKAN DATA AWAL */
renderProducts();
renderStock();
renderContact();
renderSocials();

/* AMBIL PRODUK, KONTAK & MEDIA SOSIAL DARI DATABASE */
api('stok_ambil')
  .then(data => {
    terapkanStok(data);

    if (PERAN === 'mitra' && mitraList[0]) {
      $('#profileName').textContent = mitraList[0].nama;
      $('#profileSub').textContent = 'Mitra Callista';
    }
  })
  .catch(error => toast(error.message));

if (PERAN === 'mitra') {
  $$('.nav-item').forEach(button => {
    if (button.dataset.page !== 'stock') button.style.display = 'none';
  });
  showPage('stock');
}

if (PERAN === 'admin') {
  api('ambil')
    .then(terapkan)
    .catch(error => toast(error.message));
}
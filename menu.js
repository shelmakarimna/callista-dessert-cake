// ====== FOTO ======
// Semua foto ada di folder asset-callista/images/. Cukup tulis nama file-nya
const IMG_DIR = 'asset-callista/images/';
const IMG_EXT = ['.png', '.webp', '.jpg', '.jpeg', '.avif', '', '.PNG', '.JPG'];
const foto = nama => IMG_EXT.map(ext => IMG_DIR + encodeURIComponent(nama) + ext);

// "produk-<id>" (contoh: produk-36.png).
const fotoProduk = {
  1: 'Anggur 5.5k',
  2: 'Mille crepes 16k_slice',
  3: 'Bday mochi vanilla stw 79k',
  4: 'Beng beng vanilla 8.9k',
  5: 'Milo Mille crepes 16k',
  6: 'Bday mochi 79k',
  7: 'Brownies mochi 8.9k',
  8: 'Mille crepes 150-155k_loyang',
  9: 'Bday mochi stw choco start 79k',
  10: 'Chese 8.9k',
  11: 'Choco beng beng 8.9k',
  12: 'Choco chese 8.9k',
  13: 'Choco paste 8.9k',
  14: 'Cookies n cream 5.5k',
  15: 'Creamy durian 8.9k',
  16: 'Dubai chewy cookie 28k',
  17: 'Dubai chewy ube 25k',
  18: 'Full durian 10k',
  19: 'Lotus 8.9k',
  20: 'Mangga vanilla 8.9k',
  21: 'Matcha cookies 8.9k',
  22: 'Matcha mangga 8.9k',
  23: 'Muscat 8.9k',
  24: 'Peach 10k',
  25: 'Pistachio kunafa mochi 9.5k',
  26: 'Red bean 8.9k',
  27: 'Redvelvet 8.9k',
  28: 'Silverqueen 8.9k',
  29: 'Stw choco cream 8.9k',
  30: 'Stw choco paste 8.9k',
  31: 'Stw matcha 5.5k',
  32: 'Stw redbean 8.9k',
  33: 'Stw vanilla 5.5k',
  34: 'Tiramisu 8.9k',
  35: 'Bday mochhi oreo 79k',
  36: 'Mille crepes 150-155k_loyang(1)'
};

const categories = [
  {
    id: 'mochi',
    name: 'Mochi',
    desc: 'Lembut, kenyal, dan manis dalam setiap gigitan.',
    img: 'assets/mochi.png',
    emoji: '🍡',
    bannerImg: foto('mochi_varian'),
    banner: ['assets/banner-mochi-1.jpg', 'assets/banner-mochi-2.jpg', 'assets/banner-mochi-3.jpg', 'assets/banner-mochi-4.jpg'],
    items: [
      { name: 'Anggur', price: 5500, desc: 'Mochi lembut dengan isian krim dan buah anggur', img: 'assets/Anggur.png' },
      { name: 'Stroberi', price: 5500, desc: 'Mochi lembut dengan isian krim dan buah stroberi', img: 'assets/mochi-stroberi.png' },
      { name: 'Matcha', price: 5500, desc: 'Mochi lembut dengan isian krim matcha', img: 'assets/mochi-matcha.png' },
      { name: 'Coklat', price: 5500, desc: 'Mochi lembut dengan isian krim coklat', img: 'assets/mochi-coklat.png' },
      { name: 'Taro', price: 5500, desc: 'Mochi lembut dengan isian krim taro', img: 'assets/mochi-taro.png' },
      { name: 'Mangga', price: 5500, desc: 'Mochi lembut dengan isian krim dan buah mangga', img: 'assets/mochi-mangga.png' },
      { name: 'Pistachio', price: 6500, desc: 'Mochi lembut dengan isian krim pistachio', img: 'assets/mochi-pistachio.png' },
      { name: 'Tiramisu', price: 6500, desc: 'Mochi lembut dengan isian krim tiramisu', img: 'assets/mochi-tiramisu.png' },
      { name: 'Anggur', price: 5500, desc: 'Mochi lembut dengan isian krim dan buah anggur', img: 'assets/mochi-anggur.png' },
      { name: 'Stroberi', price: 5500, desc: 'Mochi lembut dengan isian krim dan buah stroberi', img: 'assets/mochi-stroberi.png' },
      { name: 'Matcha', price: 5500, desc: 'Mochi lembut dengan isian krim matcha', img: 'assets/mochi-matcha.png' },
      { name: 'Coklat', price: 5500, desc: 'Mochi lembut dengan isian krim coklat', img: 'assets/mochi-coklat.png' },
      { name: 'Taro', price: 5500, desc: 'Mochi lembut dengan isian krim taro', img: 'assets/mochi-taro.png' },
      { name: 'Mangga', price: 5500, desc: 'Mochi lembut dengan isian krim dan buah mangga', img: 'assets/mochi-mangga.png' },
      { name: 'Pistachio', price: 6500, desc: 'Mochi lembut dengan isian krim pistachio', img: 'assets/mochi-pistachio.png' },
      { name: 'Tiramisu', price: 6500, desc: 'Mochi lembut dengan isian krim tiramisu', img: 'assets/mochi-tiramisu.png' }
    ]
  },
  {
    id: 'crepe',
    name: 'Crepe Cake',
    desc: 'Lapisan crepe lembut dengan cream yang manis.',
    img: 'assets/crepe-cake.png',
    emoji: '🍰',
    bannerImg: foto('mille_cake'),
    banner: [],
    items: [
      { name: 'Tiramisu', price: 18000, desc: 'Crepe cake lapis dengan krim tiramisu', img: 'assets/crepe-tiramisu.png' },
      { name: 'Coklat', price: 18000, desc: 'Crepe cake lapis dengan krim coklat', img: 'assets/crepe-coklat.png' },
      { name: 'Matcha', price: 18000, desc: 'Crepe cake lapis dengan krim matcha', img: 'assets/crepe-matcha.png' },
      { name: 'Stroberi', price: 18000, desc: 'Crepe cake lapis dengan krim stroberi', img: 'assets/crepe-stroberi.png' },
      { name: 'Red Velvet', price: 20000, desc: 'Crepe cake lapis dengan krim red velvet', img: 'assets/crepe-redvelvet.png' },
      { name: 'Taro', price: 18000, desc: 'Crepe cake lapis dengan krim taro', img: 'assets/crepe-taro.png' },
      { name: 'Mangga', price: 20000, desc: 'Crepe cake lapis dengan krim dan mangga', img: 'assets/crepe-mangga.png' },
      { name: 'Oreo', price: 20000, desc: 'Crepe cake lapis dengan krim oreo', img: 'assets/crepe-oreo.png' }
    ]
  },
  {
    id: 'mochicake',
    name: 'Mochi Cake',
    desc: 'Mochi kenyal yang disusun cantik menjadi bentuk cake.',
    img: 'assets/mochi-cake.png',
    emoji: '🎂',
    bannerImg: foto('mochi_cake4'),
    items: [
      { name: 'Mochi Cake Mini', price: 55000, desc: 'Isi 6 mochi aneka rasa dalam kotak cantik', img: 'image/asset-callista/' },
      { name: 'Mochi Cake Medium', price: 85000, desc: 'Isi 9 mochi aneka rasa dalam kotak cantik', img: 'assets/mochicake-medium.png' },
      { name: 'Mochi Cake Large', price: 120000, desc: 'Isi 12 mochi aneka rasa dalam kotak cantik', img: 'assets/mochicake-large.png' },
      { name: 'Mochi Cake Special', price: 150000, desc: 'Mochi cake dengan topping buah dan hiasan spesial', img: 'assets/mochicake-special.png' }
    ]
  }, 
  {
    id: 'dubai',
    name: 'Dubai Chewy',
    desc: 'Mochi kenyal yang disusun cantik menjadi bentuk cake.',
    img: 'assets/mochi-cake.png',
    emoji: '🎂',
    bannerImg: foto('ube_cookie'),
    items: [
      { name: 'Dubai Chewy Cookie', 
        price: 28000, 
        desc: 'Isi 6 mochi aneka rasa dalam kotak cantik', 
        img: 'assets/mochicake-mini.png' 
      },
      { name: 'Dubai Chewy Ube', 
        price: 25000, 
        desc: 'Isi 9 mochi aneka rasa dalam kotak cantik', 
        img: 'assets/mochicake-medium.png' 
      }
    ]
  }
];

// ====== STATE ======
let current = 0;

const $cards = document.getElementById('categoryCards');
const $grid = document.getElementById('productGrid');
const $bannerTitle = document.getElementById('bannerTitle');
const $bannerImages = document.getElementById('bannerImages');

// ====== HELPERS ======
const formatPrice = n => n.toLocaleString('id-ID');

// Gambar dengan fallback emoji kalau file belum ada
// src boleh satu alamat, atau daftar alamat yang dicoba satu per satu.
function imgWithFallback(src, emoji, alt) {
  const wrap = document.createElement('span');
  wrap.style.display = 'contents';
  const list = Array.isArray(src) ? src.slice() : [src];
  const img = new Image();
  img.alt = alt;
  img.loading = 'lazy';
  img.onerror = () => {
    if (list.length) img.src = list.shift();
    else wrap.textContent = emoji;
  };
  img.src = list.shift();
  wrap.appendChild(img);
  return wrap;
}

// ====== RENDER ======
function renderCategoryCards() {
  $cards.innerHTML = '';
  categories.forEach((cat, i) => {
    const card = document.createElement('article');
    card.className = 'cat-card' + (i === current ? ' active' : '');
    card.innerHTML = `<h3>${cat.name}</h3><div class="cat-img"></div><p>${cat.desc}</p>`;
    card.querySelector('.cat-img').appendChild(imgWithFallback(cat.img, cat.emoji, cat.name));
    card.addEventListener('click', () => {
      setCategory(i);
      document.querySelector('.menu').scrollIntoView({ behavior: 'smooth' });
    });
    $cards.appendChild(card);
  });
}

function renderBanner(cat) {
  $bannerTitle.textContent = cat.name;
  $bannerImages.innerHTML = '';
  // Satu foto banner lebar (sesuai Figma); kalau tidak ada, pakai foto produk kategori itu.
  const fotos = cat.bannerImg ? [cat.bannerImg] : cat.items.slice(0, 4).map(item => item.img);
  if (fotos.length) {
    fotos.forEach(src => {
      const cell = document.createElement('div');
      if (!cat.bannerImg) cell.className = 'is-produk';
      cell.appendChild(imgWithFallback(src, cat.emoji, cat.name));
      $bannerImages.appendChild(cell);
    });
    return;
  }
  const fallbackEmoji = [cat.emoji, '🍬', '🍫', '🍇'];
  for (let i = 0; i < 4; i++) {
    const cell = document.createElement('div');
    if (cat.banner[i]) cell.appendChild(imgWithFallback(cat.banner[i], fallbackEmoji[i], cat.name));
    else cell.textContent = fallbackEmoji[i];
    $bannerImages.appendChild(cell);
  }
}

function renderProducts(cat) {
  $grid.innerHTML = '';
  cat.items.forEach(item => {
    const el = document.createElement('article');
    el.className = 'product';
    el.innerHTML = `
      <div class="product-img"></div>
      <div class="product-head">
        <h3 class="product-name">${item.name}</h3>
        <span class="price">${formatPrice(item.price)}</span>
      </div>
      <p class="product-desc">${item.desc}</p>`;
    el.querySelector('.product-img').appendChild(imgWithFallback(item.img, cat.emoji, item.name));
    // Klik produk -> buka WhatsApp dengan pesan pesanan
    el.style.cursor = 'pointer';
    el.tabIndex = 0;
    el.setAttribute('role', 'link');
    const pesanWA = () => {
      const teks = 'Halo, Callista Dessert & Cake.\n' +
        'Saya tertarik untuk memesan ' + item.name + '. Apakah produk tersebut masih tersedia?\n' +
        'Jika masih tersedia, saya ingin melanjutkan pemesanan.\n' +
        'Terima kasih.';
      window.open('https://wa.me/6281215555886?text=' + encodeURIComponent(teks), '_blank', 'noopener');
    };
    el.addEventListener('click', pesanWA);
    el.addEventListener('keydown', e => { if (e.key === 'Enter') pesanWA(); });
    $grid.appendChild(el);
  });
}

function setCategory(i) {
  current = (i + categories.length) % categories.length;
  const cat = categories[current];
  renderBanner(cat);
  renderProducts(cat);
  document.querySelectorAll('.cat-card').forEach((c, idx) => c.classList.toggle('active', idx === current));
  document.querySelectorAll('.menu__card[data-kategori]').forEach(c => {
    c.classList.toggle('is-active', c.dataset.kategori === cat.id);
  });
}

// ====== EVENTS ======
document.getElementById('prevBtn').addEventListener('click', () => setCategory(current - 1));
document.getElementById('nextBtn').addEventListener('click', () => setCategory(current + 1));

const nav = document.querySelector('.nav');
document.getElementById('navToggle').addEventListener('click', () => nav.classList.toggle('open'));
const dbCategoryMap = { 1: 'mochi', 2: 'crepe', 3: 'mochicake', 4: 'dubai' };

// Salinan data produk dari database. Dipakai HANYA kalau api.php tidak bisa dijalankan
// (misalnya dibuka lewat Live Server tanpa PHP). Di hosting, data asli dari api.php yang dipakai.
const produkCadangan = [
  { "id_produk": 1, "id_kategori": 1, "nama_produk": "Mochi Anggur", "harga": 5500, "deskripsi": "Mochi lembut dengan isian anggur yang manis dan segar." },
  { "id_produk": 2, "id_kategori": 2, "nama_produk": "Crepe Cake Cokelat", "harga": 16000, "deskripsi": "Crepe cake berlapis dengan rasa cokelat yang lezat dan lembut." },
  { "id_produk": 3, "id_kategori": 3, "nama_produk": "Mochi Cake vanilla stawberry", "harga": 79000, "deskripsi": "Mochi cake lembut dengan perpaduan rasa vanilla dan strawberry yang manis." },
  { "id_produk": 4, "id_kategori": 1, "nama_produk": "Mochi beng-beng vanilla", "harga": 8900, "deskripsi": "Mochi lembut dengan isian vanilla yang creamy dan cokelat Beng-Beng yang lezat." },
  { "id_produk": 5, "id_kategori": 2, "nama_produk": "Crepe Millo Mille", "harga": 16000, "deskripsi": "Crepe cake berlapis dengan rasa Milo yang manis dan kaya cokelat." },
  { "id_produk": 6, "id_kategori": 3, "nama_produk": "Mochi Cake Stawberry Red Bean", "harga": 16000, "deskripsi": "Mochi cake lembut dengan perpaduan strawberry dan pasta kacang merah yang khas." },
  { "id_produk": 7, "id_kategori": 1, "nama_produk": "Mochi brownies", "harga": 8900, "deskripsi": "Mochi kenyal dengan isian brownies bercita rasa cokelat yang manis." },
  { "id_produk": 8, "id_kategori": 2, "nama_produk": "Crepe Cake Loyang Custom", "harga": 150000, "deskripsi": "Crepe cake berlapis yang bisa disesuaikan dengan pilihan rasa sesuai keinginan." },
  { "id_produk": 9, "id_kategori": 3, "nama_produk": "Mochi Cake stawberry choco", "harga": 79000, "deskripsi": "Mochi cake lembut dengan perpaduan rasa strawberry dan cokelat yang menggugah selera." },
  { "id_produk": 10, "id_kategori": 1, "nama_produk": "Mochi Cheese", "harga": 8900, "deskripsi": "Mochi kenyal dengan isian keju yang gurih dan creamy." },
  { "id_produk": 11, "id_kategori": 1, "nama_produk": "Mochi Chocco Beng-Beng", "harga": 8900, "deskripsi": "Mochi lembut dengan isian cokelat Beng-Beng yang manis dan kaya rasa." },
  { "id_produk": 12, "id_kategori": 1, "nama_produk": "Mochi Choco Cheese", "harga": 8900, "deskripsi": "Mochi kenyal dengan perpaduan cokelat manis dan keju yang gurih." },
  { "id_produk": 13, "id_kategori": 1, "nama_produk": "Mochi Paste", "harga": 8900, "deskripsi": "Mochi lembut dengan isian pasta cokelat yang manis dan lumer." },
  { "id_produk": 14, "id_kategori": 1, "nama_produk": "Mochi Cookies and Cream", "harga": 5500, "deskripsi": "Mochi kenyal dengan isian krim lembut dan remahan cookies yang renyah." },
  { "id_produk": 15, "id_kategori": 1, "nama_produk": "Mochi Durian", "harga": 8900, "deskripsi": "Mochi lembut dengan isian durian yang creamy dan beraroma khas." },
  { "id_produk": 16, "id_kategori": 4, "nama_produk": "Dubai Chewy Cookie", "harga": 28000, "deskripsi": "Dubai chewy cookie dengan perpaduan pistachio dan kunafa yang kaya rasa dan renyah." },
  { "id_produk": 17, "id_kategori": 4, "nama_produk": "Dubai Chewy Ube", "harga": 25000, "deskripsi": "Dubai chewy dengan cita rasa ube yang khas dan tekstur kenyal yang menggugah selera." },
  { "id_produk": 18, "id_kategori": 1, "nama_produk": "Mochi Full Durian", "harga": 10000, "deskripsi": "Mochi lembut dengan isian durian melimpah untuk pencinta rasa durian." },
  { "id_produk": 19, "id_kategori": 1, "nama_produk": "Mochi Lotus", "harga": 8900, "deskripsi": "Mochi kenyal dengan isian krim Lotus yang manis dan lembut." },
  { "id_produk": 20, "id_kategori": 1, "nama_produk": "Mochi Mangga Vanilla", "harga": 8900, "deskripsi": "Mochi lembut dengan perpaduan mangga segar dan vanilla yang creamy." },
  { "id_produk": 21, "id_kategori": 1, "nama_produk": "Mochi Matcha Cookies", "harga": 8900, "deskripsi": "Mochi kenyal dengan perpaduan matcha khas dan cookies yang renyah." },
  { "id_produk": 22, "id_kategori": 1, "nama_produk": "Mochi Matcha Mangga", "harga": 8900, "deskripsi": "Mochi lembut dengan perpaduan rasa matcha yang khas dan mangga yang manis." },
  { "id_produk": 23, "id_kategori": 1, "nama_produk": "Mochi Muscat", "harga": 8900, "deskripsi": "Mochi lembut dengan isian anggur muscat yang manis dan menyegarkan." },
  { "id_produk": 24, "id_kategori": 1, "nama_produk": "Mochi Peach", "harga": 10000, "deskripsi": "Mochi kenyal dengan isian buah peach yang manis dan menyegarkan." },
  { "id_produk": 25, "id_kategori": 1, "nama_produk": "Mochi Pistachio Kunafa", "harga": 9500, "deskripsi": "Mochi lembut dengan isian pistachio dan kunafa yang gurih serta renyah." },
  { "id_produk": 26, "id_kategori": 1, "nama_produk": "Mochi Red Bean", "harga": 8900, "deskripsi": "Mochi kenyal dengan isian pasta kacang merah yang manis dan lembut." },
  { "id_produk": 27, "id_kategori": 1, "nama_produk": "Mochi Red Velvet", "harga": 8900, "deskripsi": "Mochi lembut dengan isian red velvet bercita rasa manis dan khas." },
  { "id_produk": 28, "id_kategori": 1, "nama_produk": "Mochi Silverqueen", "harga": 8900, "deskripsi": "Mochi kenyal dengan isian cokelat SilverQueen yang manis dan lezat." },
  { "id_produk": 29, "id_kategori": 1, "nama_produk": "Mochi Stawberry Choco Cream", "harga": 8900, "deskripsi": "Mochi lembut dengan perpaduan strawberry dan krim cokelat yang manis." },
  { "id_produk": 30, "id_kategori": 1, "nama_produk": "Mochi Stawberry Choco Paste", "harga": 8900, "deskripsi": "Mochi kenyal dengan isian pasta cokelat dan sentuhan rasa strawberry." },
  { "id_produk": 31, "id_kategori": 1, "nama_produk": "Mochi Stawberry Matcha", "harga": 5500, "deskripsi": "Mochi lembut dengan perpaduan strawberry yang manis dan matcha yang khas." },
  { "id_produk": 32, "id_kategori": 1, "nama_produk": "Mochi Stawberry Read Bean", "harga": 8900, "deskripsi": "Mochi kenyal dengan perpaduan strawberry dan pasta kacang merah yang manis." },
  { "id_produk": 33, "id_kategori": 1, "nama_produk": "Mochi Vanilla Stawberry", "harga": 5500, "deskripsi": "Mochi lembut dengan perpaduan rasa vanilla yang manis dan strawberry yang segar." },
  { "id_produk": 34, "id_kategori": 1, "nama_produk": "Mochi Tiramisu", "harga": 8900, "deskripsi": "Mochi kenyal dengan isian tiramisu yang creamy dan bercita rasa kopi." },
  { "id_produk": 35, "id_kategori": 3, "nama_produk": "Mochi Cake Oreo", "harga": 79000, "deskripsi": "Mochi cake lembut dengan perpaduan krim dan biskuit Oreo yang manis." },
  { "id_produk": 36, "id_kategori": 2, "nama_produk": "Crepe Cake Mix", "harga": 150000, "deskripsi": "Crepe cake berlapis yang bisa disesuaikan dengan pilihan rasa sesuai keinginan." }
];

function terapkanData(rows) {
  categories.forEach(cat => {
    const fromDb = rows
      .filter(r => dbCategoryMap[r.id_kategori] === cat.id)
      .map(r => ({
        name: r.nama_produk,
        price: Number(r.harga),
        desc: r.deskripsi,
        img: [
          ...(r.gambar ? [r.gambar] : []),
          ...foto(fotoProduk[r.id_produk] || 'produk-' + r.id_produk)
        ]
      }));
    cat.items = fromDb;
  });
  setCategory(current);
}

function loadProducts() {
  fetch('api.php')
    .then(res => {
      if (!res.ok) throw new Error('Gagal memuat menu');
      return res.json();
    })
    .then(rows => {
      if (!Array.isArray(rows)) throw new Error('api.php tidak mengembalikan data');
      terapkanData(rows);
    })
    .catch(err => {
      console.error(err);
      terapkanData(produkCadangan);
    });
}

// ====== INIT ======
const indexKategori = id => categories.findIndex(c => c.id === id);
function scrollKeMenu() {
  document.querySelector('.menu').scrollIntoView({ behavior: 'smooth' });
}

// klik kartu kategori di bagian atas halaman menu
document.querySelectorAll('.menu__card[data-kategori]').forEach(card => {
  card.addEventListener('click', e => {
    e.preventDefault();
    const i = indexKategori(card.dataset.kategori);
    if (i < 0) return;
    setCategory(i);
    scrollKeMenu();
  });
});

renderCategoryCards();

// baca ?kategori=... dari URL (datang dari beranda)
const paramKategori = new URLSearchParams(window.location.search).get('kategori');
const awal = indexKategori(paramKategori);

setCategory(awal >= 0 ? awal : 0);
loadProducts();

if (awal >= 0) setTimeout(scrollKeMenu, 150);
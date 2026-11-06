<?php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS'])
]);
session_start();

if (empty($_SESSION['admin'])) {
    header('Location: admin.php');
    exit;
}

$idAdmin = (int) ($_SESSION['admin_id'] ?? 0);

$daftarMitra = [
    2 => 'Rita Supermall Purwokerto',
    3 => 'Rita Isola Purwokerto',
    4 => 'Rita Kebondalem',
    5 => 'Indogrosir Purwokerto'
];

if (!isset($daftarMitra[$idAdmin])) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$namaMitra = $daftarMitra[$idAdmin];

$lokal = in_array(
    $_SERVER['SERVER_NAME'] ?? '',
    ['localhost', '127.0.0.1'],
    true
);

$dbPassHosting = '?9J%;@4,UGkv^VjA'; 
$cfg = $lokal
    ? ['db' => 'callista_db', 'user' => 'root', 'pass' => '']
    : ['db' => 'callista_db', 'user' => 'callista_app', 'pass' => $dbPassHosting];

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=' . $cfg['db'] . ';charset=utf8mb4',
        $cfg['user'],
        $cfg['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (Throwable $e) {
    http_response_code(500);
    exit('Koneksi database gagal.');
}

function response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Pastikan ID mitra memang tersedia.
$cekMitra = $pdo->prepare(
    'SELECT id_mitra FROM mitra WHERE id_mitra = ?'
);
$cekMitra->execute([$idAdmin]);

if (!$cekMitra->fetch()) {
    http_response_code(403);
    exit('Data mitra belum tersedia di tabel mitra.');
}

// Semua query stok wajib menggunakan ID mitra dari SESSION.
function ambilStok(PDO $pdo, int $idMitra) {
    $stmt = $pdo->prepare(
        'SELECT
            s.id_stok,
            s.id_produk,
            p.nama_produk,
            k.nama_kategori,
            s.stok_awal,
            s.stok_terjual,
            (s.stok_awal - s.stok_terjual) AS sisa_stok,
            s.terakhir_update
         FROM stok_mitra s
         JOIN produk p ON p.id_produk = s.id_produk
         JOIN kategori k ON k.id_kategori = p.id_kategori
         WHERE s.id_mitra = ?
         ORDER BY s.terakhir_update DESC, s.id_stok DESC'
    );

    $stmt->execute([$idMitra]);
    return $stmt->fetchAll();
}

// Catat aktivitas ke tabel riwayat_katalog (tampil di dasbor admin).
function catat(PDO $pdo, $idProduk, $aksi) {
    global $namaMitra;
    try {
        $q = $pdo->prepare('SELECT p.nama_produk, p.harga, k.nama_kategori FROM produk p JOIN kategori k ON k.id_kategori = p.id_kategori WHERE p.id_produk = ?');
        $q->execute([(int)$idProduk]);
        $p = $q->fetch();
        if (!$p) return;
        $pdo->prepare('INSERT INTO riwayat_katalog (nama_produk, nama_kategori, harga, aksi, waktu, id_produk) VALUES (?, ?, ?, ?, NOW(), NULL)')
            ->execute([$p['nama_produk'], $p['nama_kategori'], (int)$p['harga'], mb_substr($namaMitra . ' ' . $aksi, 0, 250)]);
    } catch (Throwable $e) { 
    }
}

function idProdukDariStok(PDO $pdo, int $idStok, int $idMitra) {
    $q = $pdo->prepare('SELECT id_produk FROM stok_mitra WHERE id_stok = ? AND id_mitra = ?');
    $q->execute([$idStok, $idMitra]);
    return (int)$q->fetchColumn();
}

function dataAwal(PDO $pdo, int $idMitra) {
    $stok = ambilStok($pdo, $idMitra);

    $produk = $pdo->query(
        'SELECT
            p.id_produk,
            p.nama_produk,
            k.nama_kategori
         FROM produk p
         JOIN kategori k ON k.id_kategori = p.id_kategori
         ORDER BY p.nama_produk ASC'
    )->fetchAll();

    return [
        'stok' => $stok,
        'produk' => $produk,
        'total_produk' => count($stok),
        'total_stok' => array_sum(
            array_map(
                fn($r) => (int)$r['sisa_stok'],
                $stok
            )
        ),
        'stok_menipis' => count(array_filter(
            $stok,
            fn($r) => (int)$r['sisa_stok'] > 0 &&
                      (int)$r['sisa_stok'] <= 3
        )),
        'stok_habis' => count(array_filter(
            $stok,
            fn($r) => (int)$r['sisa_stok'] <= 0
        ))
    ];
}

// API DASHBOARD MITRA
if (isset($_GET['aksi'])) {

    $aksi = $_GET['aksi'];

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        if (
            ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch'
        ) {
            response(['error' => 'Permintaan tidak valid'], 400);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (
        empty($_SESSION['mitra_csrf']) ||
        !hash_equals($_SESSION['mitra_csrf'], $token)
    ) {
        response(['error' => 'Token keamanan tidak valid.'], 403);
    }

        if (!is_array($input)) {
            response(['error' => 'Data tidak valid'], 400);
        }
    }

    try {
        // TAMPILKAN DATA STOK MILIK MITRA
        if ($aksi === 'ambil') {
            response(dataAwal($pdo, $idAdmin));
        }

        // TAMBAH STOK / RESTOCK
        if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {

            $idProduk = (int)($input['id_produk'] ?? 0);
            $jumlah = (int)($input['jumlah'] ?? 0);

            if ($idProduk <= 0 || $jumlah <= 0) {
                response(['error' => 'Produk dan jumlah stok wajib diisi.'], 422);
            }

            $cekProduk = $pdo->prepare(
                'SELECT id_produk FROM produk WHERE id_produk = ?'
            );
            $cekProduk->execute([$idProduk]);

            if (!$cekProduk->fetch()) {
                response(['error' => 'Produk tidak ditemukan.'], 404);
            }

            // Restock hanya untuk mitra yang sedang login.
            $pdo->beginTransaction();
            $cekStok = $pdo->prepare(
                'SELECT id_stok FROM stok_mitra
                 WHERE id_mitra = ? AND id_produk = ?
                 ORDER BY id_stok DESC LIMIT 1 FOR UPDATE'
            );
            $cekStok->execute([$idAdmin, $idProduk]);
            $barisStok = $cekStok->fetch();

            if ($barisStok) {
                $stmt = $pdo->prepare(
                    'UPDATE stok_mitra
                     SET stok_awal = stok_awal + ?, terakhir_update = CURDATE()
                     WHERE id_stok = ? AND id_mitra = ?'
                );
                $stmt->execute([$jumlah, $barisStok['id_stok'], $idAdmin]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO stok_mitra
                     (id_mitra, id_produk, stok_awal, stok_terjual, terakhir_update)
                     VALUES (?, ?, ?, 0, CURDATE())'
                );
                $stmt->execute([$idAdmin, $idProduk, $jumlah]);
            }
            $pdo->commit();
            catat($pdo, $idProduk, "menambah stok (+$jumlah)");

            response([
                'success' => true,
                'message' => 'Stok berhasil ditambahkan.',
                'data' => dataAwal($pdo, $idAdmin)
            ]);
        }

        // EDIT STOK
        if ($aksi === 'ubah' && $_SERVER['REQUEST_METHOD'] === 'POST') {

            $idStok = (int)($input['id_stok'] ?? 0);
            $stokAwal = (int)($input['stok_awal'] ?? -1);
            $stokTerjual = (int)($input['stok_terjual'] ?? -1);

            if (
                $idStok <= 0 ||
                $stokAwal < 0 ||
                $stokTerjual < 0 ||
                $stokTerjual > $stokAwal
            ) {
                response(['error' => 'Jumlah stok tidak valid.'], 422);
            }

            $idProdukLog = idProdukDariStok($pdo, $idStok, $idAdmin);
            $stmt = $pdo->prepare(
                'UPDATE stok_mitra
                 SET stok_awal = ?,
                     stok_terjual = ?,
                     terakhir_update = CURDATE()
                 WHERE id_stok = ?
                   AND id_mitra = ?'
            );

            $stmt->execute([
                $stokAwal,
                $stokTerjual,
                $idStok,
                $idAdmin
            ]);

            if ($stmt->rowCount() === 0) {
                $cek = $pdo->prepare(
                    'SELECT id_stok FROM stok_mitra
                     WHERE id_stok = ? AND id_mitra = ?'
                );
                $cek->execute([$idStok, $idAdmin]);

                if (!$cek->fetch()) {
                    response(['error' => 'Data stok tidak ditemukan.'], 404);
                }
            }

            if ($idProdukLog) catat($pdo, $idProdukLog, "memperbarui stok (awal $stokAwal, terjual $stokTerjual)");

            response([
                'success' => true,
                'message' => 'Stok berhasil diperbarui.',
                'data' => dataAwal($pdo, $idAdmin)
            ]);
        }

        // HAPUS STOK
        if ($aksi === 'hapus' && $_SERVER['REQUEST_METHOD'] === 'POST') {

            $idStok = (int)($input['id_stok'] ?? 0);

            if ($idStok <= 0) {
                response(['error' => 'ID stok tidak valid.'], 422);
            }

            $idProdukLog = idProdukDariStok($pdo, $idStok, $idAdmin);
            $stmt = $pdo->prepare(
                'DELETE FROM stok_mitra
                 WHERE id_stok = ? AND id_mitra = ?'
            );

            $stmt->execute([$idStok, $idAdmin]);

            if ($stmt->rowCount() === 0) {
                response(['error' => 'Data stok tidak ditemukan.'], 404);
            }

            if ($idProdukLog) catat($pdo, $idProdukLog, 'menghapus stok');

            response([
                'success' => true,
                'message' => 'Stok berhasil dihapus.',
                'data' => dataAwal($pdo, $idAdmin)
            ]);
        }

        response(['error' => 'Aksi tidak dikenal.'], 400);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        response(['error' => 'Gagal memproses data database.'], 500);
    }
}

$csrf = bin2hex(random_bytes(32));
$_SESSION['mitra_csrf'] = $csrf;
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pemantauan Stok - <?= htmlspecialchars($namaMitra) ?></title>

<link rel="stylesheet" href="admin.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
.mitra-content {
    padding-top: 5.5rem;
}

.mitra-profile {
    height: 10rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 4.1rem 1.3rem 0 2rem;
}

.mitra-profile .avatar {
    flex-shrink: 0;
}

.mitra-profile strong {
    display: block;
    color: var(--ink);
    font-size: 1.25rem;
    line-height: 1.5;
}

.mitra-profile small {
    display: block;
    color: var(--pink);
    font-size: .78rem;
    line-height: 1.5;
}

.mitra-nav {
    height: 4.25rem;
    width: 100%;
    justify-content: flex-start;
}

.mitra-heading {
    margin-bottom: 1.5rem;
}

.mitra-heading h1 {
    white-space: normal;
}

.mitra-intro {
    color: #aaa;
    font-size: .9rem;
    margin-bottom: 1.3rem;
}

.mitra-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.mitra-stats .stat {
    background: white;
    border: 1px solid #eadfe3;
    border-radius: 12px;
    padding: 1.2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.mitra-stats .stat strong {
    font-size: 1.8rem;
    color: var(--ink);
}

.mitra-toolbar {
    min-height: 5rem;
    display: flex;
    align-items: center;
    gap: .8rem;
    flex-wrap: wrap;
    padding: 1rem 1.4rem;
}

.mitra-toolbar .search {
    flex: 1;
    min-width: 220px;
}

.mitra-table {
    min-width: 850px;
}

.mitra-table td,
.mitra-table th {
    text-align: center;
    padding: .8rem;
}

.mitra-table td:nth-child(2),
.mitra-table th:nth-child(2) {
    text-align: left;
}

.mitra-product {
    color: var(--ink);
    font-weight: 600;
}

.mitra-actions {
    display: flex;
    justify-content: center;
    gap: .4rem;
}

.mitra-actions button {
    border: 0;
    padding: .45rem .7rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: .75rem;
    font-weight: 600;
}

.mitra-edit {
    background: #fff0f5;
    color: var(--pink);
}

.mitra-delete {
    background: #ffe5e5;
    color: #df5151;
}

.mitra-modal {
    width: min(95%, 460px);
    border: 0;
    border-radius: 16px;
    padding: 25px;
    margin: auto;
}

.mitra-modal::backdrop {
    background: rgba(60, 20, 40, .4);
}

.mitra-modal h2 {
    color: var(--pink);
    margin-bottom: 20px;
    font-size: 1.4rem;
}

.mitra-modal label {
    display: block;
    margin: 14px 0 6px;
    font-size: .9rem;
    font-weight: 600;
}

.mitra-modal input,
.mitra-modal select {
    width: 100%;
    padding: 12px;
    border: 1px solid #eadfe3;
    border-radius: 8px;
    outline: none;
    font-family: inherit;
}

.mitra-modal input:focus,
.mitra-modal select:focus {
    border-color: var(--pink);
}

.mitra-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

.mitra-empty {
    padding: 35px !important;
    text-align: center !important;
    color: #aaa !important;
}

.mitra-toast {
    position: fixed;
    bottom: 25px;
    right: 25px;
    z-index: 99;
    background: var(--ink);
    color: white;
    padding: 13px 20px;
    border-radius: 9px;
    opacity: 0;
    pointer-events: none;
    transition: .2s;
}

.mitra-toast.show {
    opacity: 1;
}

@media(max-width:1000px) {
    .mitra-stats {
        grid-template-columns: 1fr;
    }
}

@media(max-width:760px) {
    .dashboard {
        grid-template-columns: minmax(0,1fr);
        grid-template-rows: 96px auto 1fr;
    }

    .topbar {
        padding: 0 20px;
    }

    .brand-logo {
        height: 56px;
    }

    .sidebar {
        grid-column: 1;
        grid-row: 2;
        min-height: 0;
        margin-top: 22px;
    }

    .mitra-profile {
        display: none;
    }

    .sidebar nav {
        flex-direction: row;
    }

    .mitra-nav {
        justify-content: center;
        min-width: 100%;
    }

    .main-content {
        grid-column: 1;
        grid-row: 3;
        padding: 28px 16px 40px;
    }

    .mitra-content {
        padding-top: 1rem;
    }

    .mitra-heading h1 {
        font-size: 1.8rem;
    }
}
</style>
</head>

<body>

<div class="dashboard">

    <!-- HEADER -->
    <header class="topbar">
        <div class="brand">
            <img class="brand-logo"
                 src="asset-callista/images/logo.png"
                 alt="Callista Dessert & Cake">
        </div>

        <button class="logout" onclick="location.href='admin.php?logout=1'">
            Logout
            <svg class="i" viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <path d="m16 17 5-5-5-5"/>
                <path d="M21 12H9"/>
            </svg>
        </button>
    </header>

    <!-- SIDEBAR: HANYA SATU MENU -->
    <aside class="sidebar">

        <div class="mitra-profile">
            <div class="avatar">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="9" r="4.2" fill="#fff"/>
                    <path d="M3.5 24c0-5 3.8-8 8.5-8s8.5 3 8.5 8z" fill="#fff"/>
                </svg>
            </div>

            <div>
                <strong><?= htmlspecialchars($namaMitra) ?></strong>
                <small>Admin Mitra</small>
            </div>
        </div>

        <nav>
            <button class="nav-item active mitra-nav">
                <span class="nav-icon">
                    <svg class="i" viewBox="0 0 24 24">
                        <path d="M21 8v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8"/>
                        <path d="M1 3h22v5H1z"/>
                        <path d="M12 18v-6"/>
                        <path d="m9 14.5 3-3 3 3"/>
                    </svg>
                </span>
                Pemantauan Stok
            </button>
        </nav>

    </aside>

    <!-- KONTEN -->
    <main class="main-content mitra-content">

        <div class="section-heading mitra-heading">
            <div>
                <h1>Pemantauan Stok Mitra</h1>
                <p class="mitra-intro">
                    Kelola persediaan produk <?= htmlspecialchars($namaMitra) ?>.
                </p>
            </div>
        </div>

        <!-- STATISTIK -->
        <section class="mitra-stats">

            <div class="stat">
                <span class="stat-icon">▣</span>
                <div>
                    <small>Total Produk</small>
                    <strong id="totalProduk">0</strong>
                </div>
            </div>

            <div class="stat">
                <span class="stat-icon basket">▤</span>
                <div>
                    <small>Total Sisa Stok</small>
                    <strong id="totalStok">0</strong>
                </div>
            </div>

            <div class="stat">
                <span class="stat-icon">!</span>
                <div>
                    <small>Stok Menipis / Habis</small>
                    <strong id="stokPeringatan">0</strong>
                </div>
            </div>

        </section>

        <!-- TABEL -->
        <section class="panel stock-panel">

            <div class="mitra-toolbar">

                <label class="search">
                    <svg class="i" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input id="cariStok" placeholder="Cari produk...">
                </label>

                <button class="primary" onclick="bukaTambah()">
                    + Tambah Stok
                </button>

            </div>

            <div class="table-wrap">
                <table class="mitra-table">

                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Stok Awal</th>
                            <th>Stok Terjual</th>
                            <th>Sisa Stok</th>
                            <th>Status</th>
                            <th>Terakhir Update</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="barisStok">
                        <tr>
                            <td colspan="8" class="mitra-empty">
                                Memuat data stok...
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>

        </section>

    </main>

</div>

<!-- MODAL TAMBAH STOK -->
<dialog id="modalTambah" class="mitra-modal">

    <form id="formTambah">

        <h2>Tambah Stok Produk</h2>

        <label>Pilih Produk</label>
        <select name="id_produk" id="pilihProduk" required>
            <option value="">Memuat produk...</option>
        </select>

        <label>Jumlah Stok Baru</label>
        <input
            type="number"
            name="jumlah"
            min="1"
            required
            placeholder="Masukkan jumlah stok">

        <div class="mitra-modal-actions">
            <button type="button" class="secondary"
                    onclick="modalTambah.close()">
                Batal
            </button>

            <button type="submit" class="primary">
                Simpan Stok
            </button>
        </div>

    </form>

</dialog>

<!-- MODAL EDIT STOK -->
<dialog id="modalEdit" class="mitra-modal">

    <form id="formEdit">

        <h2>Edit Stok Produk</h2>

        <input type="hidden" name="id_stok" id="editId">

        <label>Stok Awal</label>
        <input
            type="number"
            name="stok_awal"
            id="editAwal"
            min="0"
            required>

        <label>Stok Terjual</label>
        <input
            type="number"
            name="stok_terjual"
            id="editTerjual"
            min="0"
            required>

        <div class="mitra-modal-actions">
            <button type="button" class="secondary"
                    onclick="modalEdit.close()">
                Batal
            </button>

            <button type="submit" class="primary">
                Simpan Perubahan
            </button>
        </div>

    </form>

</dialog>

<div class="mitra-toast" id="toast"></div>

<script>
const CSRF = <?= json_encode($csrf) ?>;

let dataStok = [];
let daftarProduk = [];

const $ = id => document.getElementById(id);

function toast(pesan) {
    const el = $('toast');
    el.textContent = pesan;
    el.classList.add('show');

    clearTimeout(toast.timer);

    toast.timer = setTimeout(() => {
        el.classList.remove('show');
    }, 2500);
}

function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[char]);
}

async function api(aksi, data = null) {
    const options = data === null
        ? {}
        : {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'fetch',
                'X-CSRF-Token': CSRF
            },
            body: JSON.stringify(data)
        };

    const response = await fetch(
        'mitra.php?aksi=' + encodeURIComponent(aksi),
        options
    );

    const json = await response.json();

    if (!response.ok || json.error) {
        throw new Error(json.error || 'Terjadi kesalahan.');
    }

    return json;
}

function statusStok(sisa) {
    if (sisa <= 0) {
        return '<span class="status empty">Habis</span>';
    }

    if (sisa <= 3) {
        return '<span class="status low">Stok Menipis</span>';
    }

    return '<span class="status safe">Aman</span>';
}

function tanggalIndonesia(value) {
    if (!value) return '-';

    const date = new Date(value + 'T00:00:00');

    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
}

function renderStok() {
    const keyword = $('cariStok').value.toLowerCase();

    const filtered = dataStok.filter(item =>
        (
            item.nama_produk + ' ' +
            item.nama_kategori
        ).toLowerCase().includes(keyword)
    );

    if (!filtered.length) {
        $('barisStok').innerHTML = `
            <tr>
                <td colspan="8" class="mitra-empty">
                    Belum ada stok produk.
                </td>
            </tr>
        `;
        return;
    }

    $('barisStok').innerHTML = filtered.map(item => {
        const sisa = Number(item.sisa_stok);

        return `
            <tr>
                <td class="mitra-product">
                    ${esc(item.nama_produk)}
                </td>

                <td>
                    <span class="category-pill category-mochi">
                        ${esc(item.nama_kategori)}
                    </span>
                </td>

                <td>${Number(item.stok_awal)}</td>

                <td>${Number(item.stok_terjual)}</td>

                <td><strong>${sisa}</strong></td>

                <td>${statusStok(sisa)}</td>

                <td>${tanggalIndonesia(item.terakhir_update)}</td>

                <td>
                    <div class="mitra-actions">
                        <button class="mitra-edit"
                            onclick="bukaEdit(${Number(item.id_stok)})">
                            Edit
                        </button>

                        <button class="mitra-delete"
                            onclick="hapusStok(${Number(item.id_stok)})">
                            Hapus
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderRingkasan(data) {
    $('totalProduk').textContent = data.total_produk;
    $('totalStok').textContent = data.total_stok;
    $('stokPeringatan').textContent =
        data.stok_menipis + data.stok_habis;
}

function renderProduk() {
    $('pilihProduk').innerHTML = `
        <option value="">Pilih produk</option>
        ${daftarProduk.map(item => `
            <option value="${Number(item.id_produk)}">
                ${esc(item.nama_produk)}
            </option>
        `).join('')}
    `;
}

async function muatData() {
    try {
        const data = await api('ambil');

        dataStok = data.stok;
        daftarProduk = data.produk;

        renderRingkasan(data);
        renderProduk();
        renderStok();

    } catch (error) {
        toast(error.message);
    }
}

function bukaTambah() {
    $('formTambah').reset();
    $('modalTambah').showModal();
}

function bukaEdit(id) {
    const item = dataStok.find(
        row => Number(row.id_stok) === Number(id)
    );

    if (!item) return;

    $('editId').value = item.id_stok;
    $('editAwal').value = item.stok_awal;
    $('editTerjual').value = item.stok_terjual;

    $('modalEdit').showModal();
}

// SIMPAN STOK BARU
$('formTambah').addEventListener('submit', async event => {
    event.preventDefault();

    const form = event.currentTarget;

    try {
        const result = await api('tambah', {
            id_produk: Number(form.elements.id_produk.value),
            jumlah: Number(form.elements.jumlah.value)
        });

        dataStok = result.data.stok;

        renderRingkasan(result.data);
        renderStok();

        $('modalTambah').close();
        form.reset();

        toast(result.message);

    } catch (error) {
        toast(error.message);
    }
});

// UPDATE STOK
$('formEdit').addEventListener('submit', async event => {
    event.preventDefault();

    const form = event.currentTarget;

    try {
        const awal = Number(form.elements.stok_awal.value);
        const terjual = Number(form.elements.stok_terjual.value);

        if (terjual > awal) {
            toast('Stok terjual tidak boleh melebihi stok awal.');
            return;
        }

        const result = await api('ubah', {
            id_stok: Number(form.elements.id_stok.value),
            stok_awal: awal,
            stok_terjual: terjual
        });

        dataStok = result.data.stok;

        renderRingkasan(result.data);
        renderStok();

        $('modalEdit').close();

        toast(result.message);

    } catch (error) {
        toast(error.message);
    }
});

// HAPUS STOK
async function hapusStok(id) {
    if (!confirm('Yakin ingin menghapus data stok ini?')) {
        return;
    }

    try {
        const result = await api('hapus', {
            id_stok: id
        });

        dataStok = result.data.stok;

        renderRingkasan(result.data);
        renderStok();

        toast(result.message);

    } catch (error) {
        toast(error.message);
    }
}

// PENCARIAN
$('cariStok').addEventListener('input', renderStok);

// LOAD DATA PERTAMA
muatData();
</script>

</body>
</html>
<?php
const ADMIN_PASSWORD = 'callista26';
const DB_PASS_HOSTING = '?9J%;@4,UGkv^VjA';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
header('Cache-Control: no-store');

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (isset($_GET['aksi'])) {
    $lokal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true);
    $cfg = $lokal
        ? ['db' => 'callista_db', 'user' => 'root', 'pass' => '']
        : ['db' => 'callista_db',        'user' => 'callista_app', 'pass' => DB_PASS_HOSTING];

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    function balas($data, $kode = 200) {
        http_response_code($kode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$lokal && $cfg['pass'] === 'ISI-PASSWORD-DATABASE-DI-SINI') {
        balas(['error' => 'Password database belum diisi di admin.php (baris 2)'], 500);
    }

    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=' . $cfg['db'] . ';charset=utf8mb4',
            $cfg['user'], $cfg['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Throwable $e) {
        balas(['error' => 'Tidak bisa terhubung ke database'], 500);
    }

    function ambilData(PDO $pdo) {
        $kontak = [];
        foreach ($pdo->query('SELECT jenis, nilai FROM kontak') as $r) {
            $kontak[$r['jenis']] = $r['nilai'];
        }
        $sosmed = [];
        foreach ($pdo->query('SELECT id_sosmed, platform, username_link, status FROM media_sosial ORDER BY id_sosmed') as $r) {
            $sosmed[] = [
                'id'       => (int)$r['id_sosmed'],
                'platform' => $r['platform'],
                'link'     => $r['username_link'],
                'aktif'    => $r['status'] === 'Aktif',
            ];
        }
        $kategori = $pdo->query('SELECT id_kategori AS id, nama_kategori AS nama FROM kategori ORDER BY id_kategori')->fetchAll();
        $produk = $pdo->query(
            'SELECT p.id_produk AS id, p.id_kategori, k.nama_kategori AS kategori,
                    p.nama_produk AS nama, p.harga, p.deskripsi, p.gambar
             FROM produk p JOIN kategori k ON k.id_kategori = p.id_kategori
             ORDER BY p.id_produk DESC'
        )->fetchAll();

        // Foto produk lama: dicocokkan ke file asli di folder, tidak peduli huruf besar/kecil
        $fotoLama = [
            1 => 'Anggur 5.5k', 2 => 'Mille crepes 16k_slice', 3 => 'Bday mochi vanilla stw 79k',
            4 => 'Beng beng vanilla 8.9k', 5 => 'Milo Mille crepes 16k', 6 => 'Bday mochi 79k',
            7 => 'Brownies mochi 8.9k', 8 => 'Mille crepes 150-155k_loyang', 9 => 'Bday mochi stw choco start 79k',
            10 => 'Chese 8.9k', 11 => 'Choco beng beng 8.9k', 12 => 'Choco chese 8.9k', 13 => 'Choco paste 8.9k',
            14 => 'Cookies n cream 5.5k', 15 => 'Creamy durian 8.9k', 16 => 'Dubai chewy cookie 28k',
            17 => 'Dubai chewy ube 25k', 18 => 'Full durian 10k', 19 => 'Lotus 8.9k', 20 => 'Mangga vanilla 8.9k',
            21 => 'Matcha cookies 8.9k', 22 => 'Matcha mangga 8.9k', 23 => 'Muscat 8.9k', 24 => 'Peach 10k',
            25 => 'Pistachio kunafa mochi 9.5k', 26 => 'Red bean 8.9k', 27 => 'Redvelvet 8.9k',
            28 => 'Silverqueen 8.9k', 29 => 'Stw choco cream 8.9k', 30 => 'Stw choco paste 8.9k',
            31 => 'Stw matcha 5.5k', 32 => 'Stw redbean 8.9k', 33 => 'Stw vanilla 5.5k', 34 => 'Tiramisu 8.9k',
            35 => 'Bday mochhi oreo 79k', 36 => 'Mille crepes 150-155k_loyang(1)'
        ];
        $dir = __DIR__ . '/asset-callista/images/';
        $files = [];
        if (is_dir($dir)) {
            foreach (scandir($dir) as $f) {
                if (is_file($dir . $f)) $files[strtolower($f)] = $f;
            }
        }
        foreach ($produk as &$p) {
            // foto upload yang masih ada di server dibiarkan
            if (!empty($p['gambar']) && is_file(__DIR__ . '/' . $p['gambar'])) continue;

            $p['gambar'] = null;
            $n = $fotoLama[(int)$p['id']] ?? null;
            if ($n) {
                foreach (['.png', '.webp', '.jpg', '.jpeg', '.avif', ''] as $e) {
                    $k = strtolower($n . $e);
                    if (isset($files[$k])) {
                        $p['gambar'] = 'asset-callista/images/' . $files[$k];
                        break;
                    }
                }
            }
        }
        unset($p);

        return ['kontak' => $kontak, 'media_sosial' => $sosmed, 'kategori' => $kategori, 'produk' => $produk];
    }

    $aksi = $_GET['aksi'] ?? '';
    if ($aksi === 'publik' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        try { balas(ambilData($pdo)); }
        catch (Throwable $e) { balas(['error' => 'Gagal membaca data'], 500); }
    }

    if (empty($_SESSION['admin'])) {
        balas(['error' => 'Belum login'], 401);
    }

    // Endpoint pengelolaan (katalog, kontak, stok semua mitra) hanya untuk admin utama (ID 1).
    // Mitra (ID 2-5) memakai mitra.php.
    if ((int)($_SESSION['admin_id'] ?? 0) !== 1) {
        balas(['error' => 'Akses khusus admin utama'], 403);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
        balas(['error' => 'Permintaan tidak valid'], 400);
    }

    $in = json_decode(file_get_contents('php://input'), true);
    if (!is_array($in)) $in = [];

    function teks($v, $maks) {
        $v = trim((string)$v);
        if ($v === '' || mb_strlen($v) > $maks) balas(['error' => 'Isi data dengan benar (tidak kosong, maksimal ' . $maks . ' huruf)'], 422);
        return $v;
    }
    function linkSosmed($v) {
        $v = teks($v, 255);
        if (!preg_match('#^https?://\S+$#i', $v) && !preg_match('/^@?[A-Za-z0-9._-]+$/', $v)) {
            balas(['error' => 'Link harus diawali https:// atau berupa @username'], 422);
        }
        return $v;
    }

    // ---------- RIWAYAT (tersimpan di tabel riwayat_katalog) ----------
    function catat(PDO $pdo, $nama, $kategori, $harga, $aksi) {
        try {
            $pdo->prepare('INSERT INTO riwayat_katalog (nama_produk, nama_kategori, harga, aksi, waktu, id_produk) VALUES (?, ?, ?, ?, NOW(), NULL)')
                ->execute([mb_substr((string)$nama, 0, 120), mb_substr((string)$kategori, 0, 80), (int)$harga, mb_substr((string)$aksi, 0, 250)]);
        } catch (Throwable $e) { /* gagal mencatat tidak boleh menggagalkan aksi utama */ }
    }
    function ambilRiwayat(PDO $pdo) {
        try {
            return $pdo->query('SELECT nama_produk, nama_kategori, harga, aksi, waktu FROM riwayat_katalog ORDER BY waktu DESC, id_riwayat DESC LIMIT 50')->fetchAll();
        } catch (Throwable $e) { return []; }
    }
    function infoStok(PDO $pdo, $idStok) {
        $q = $pdo->prepare('SELECT m.nama_mitra, p.nama_produk, p.harga, k.nama_kategori
            FROM stok_mitra s JOIN mitra m ON m.id_mitra = s.id_mitra JOIN produk p ON p.id_produk = s.id_produk
            JOIN kategori k ON k.id_kategori = p.id_kategori WHERE s.id_stok = ?');
        $q->execute([$idStok]);
        return $q->fetch();
    }

    // ---------- STOK MITRA (admin utama melihat semua mitra) ----------
    function ambilStok(PDO $pdo) {
        $rows = $pdo->query(
            "SELECT s.id_stok, s.id_mitra, s.id_produk, s.stok_awal, s.stok_terjual, s.terakhir_update,
                    m.nama_mitra, p.nama_produk, p.gambar, k.nama_kategori
             FROM stok_mitra s
             JOIN mitra m    ON m.id_mitra    = s.id_mitra
             JOIN produk p   ON p.id_produk   = s.id_produk
             JOIN kategori k ON k.id_kategori = p.id_kategori
             ORDER BY s.terakhir_update DESC, s.id_stok DESC"
        )->fetchAll();
        $mitra = $pdo->query('SELECT id_mitra, nama_mitra FROM mitra WHERE aktif = 1 ORDER BY id_mitra')->fetchAll();
        $produk = $pdo->query('SELECT p.id_produk, p.nama_produk, k.nama_kategori FROM produk p JOIN kategori k ON k.id_kategori = p.id_kategori ORDER BY p.nama_produk')->fetchAll();
        return [
            'riwayat' => ambilRiwayat($pdo),
            'mitra'  => array_map(fn($r) => ['id' => (int)$r['id_mitra'], 'nama' => $r['nama_mitra']], $mitra),
            'produk' => array_map(fn($r) => ['id' => (int)$r['id_produk'], 'nama' => $r['nama_produk'], 'kategori' => $r['nama_kategori']], $produk),
            'stok'   => array_map(fn($r) => [
                'id'        => (int)$r['id_stok'],
                'id_mitra'  => (int)$r['id_mitra'],
                'id_produk' => (int)$r['id_produk'],
                'produk'    => $r['nama_produk'],
                'kategori'  => $r['nama_kategori'],
                'gambar'    => ($r['gambar'] && is_file(__DIR__ . '/' . $r['gambar'])) ? $r['gambar'] : null,
                'mitra'     => $r['nama_mitra'],
                'awal'      => (int)$r['stok_awal'],
                'terjual'   => (int)$r['stok_terjual'],
                'tanggal'   => $r['terakhir_update'],
            ], $rows),
        ];
    }

    // ---------- Fungsi bantu produk ----------
    function dataProduk(PDO $pdo) {
        $nama  = teks($_POST['nama'] ?? '', 120);
        $idKat = (int)($_POST['id_kategori'] ?? 0);
        $harga = (int)($_POST['harga'] ?? 0);
        $desk  = trim((string)($_POST['deskripsi'] ?? ''));
        if ($harga <= 0) balas(['error' => 'Harga harus lebih dari 0'], 422);
        if (mb_strlen($desk) > 1000) balas(['error' => 'Deskripsi terlalu panjang (maks 1000 huruf)'], 422);
        $cek = $pdo->prepare('SELECT 1 FROM kategori WHERE id_kategori = ?');
        $cek->execute([$idKat]);
        if (!$cek->fetch()) balas(['error' => 'Kategori tidak ditemukan'], 422);
        return [$nama, $idKat, $harga, $desk];
    }

    // Mengembalikan path foto baru, atau null kalau tidak ada foto yang diupload
    function simpanFoto() {
        if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] === UPLOAD_ERR_NO_FILE) return null;
        $f = $_FILES['gambar'];
        if ($f['error'] !== UPLOAD_ERR_OK) balas(['error' => 'Upload foto gagal'], 422);
        if ($f['size'] > 2 * 1024 * 1024) balas(['error' => 'Ukuran foto maksimal 2 MB'], 422);

        $ekstensi = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset($ekstensi[$mime])) balas(['error' => 'Foto harus berformat JPG, PNG, atau WebP'], 422);

        $dir = __DIR__ . '/asset-callista/images/produk/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) balas(['error' => 'Folder foto tidak bisa dibuat'], 500);

        $nama = 'produk_' . bin2hex(random_bytes(8)) . '.' . $ekstensi[$mime];
        if (!move_uploaded_file($f['tmp_name'], $dir . $nama)) balas(['error' => 'Foto gagal disimpan'], 500);

        return 'asset-callista/images/produk/' . $nama;
    }

    function hapusFoto($path) {
        if ($path && strpos($path, 'asset-callista/images/produk/') === 0) {
            $file = __DIR__ . '/asset-callista/images/produk/' . basename($path);
            if (is_file($file)) @unlink($file);
        }
    }

    try {
        switch ($aksi) {
            case 'ambil':
                break;

            case 'stok_ambil':
                balas(ambilStok($pdo));

            case 'stok_simpan':
                $id      = (int)($in['id'] ?? 0);
                $awal    = (int)($in['awal'] ?? 0);
                $terjual = (int)($in['terjual'] ?? 0);
                if ($awal < 0 || $awal > 100000 || $terjual < 0) balas(['error' => 'Jumlah stok tidak valid'], 422);
                if ($terjual > $awal) balas(['error' => 'Stok terjual tidak boleh lebih besar dari stok awal'], 422);

                if ($id > 0) {
                    $cek = $pdo->prepare('SELECT id_mitra FROM stok_mitra WHERE id_stok = ?');
                    $cek->execute([$id]);
                    $row = $cek->fetch();
                    if (!$row) balas(['error' => 'Data stok tidak ditemukan'], 404);
                    $pdo->prepare('UPDATE stok_mitra SET stok_awal = ?, stok_terjual = ?, terakhir_update = CURDATE() WHERE id_stok = ?')
                        ->execute([$awal, $terjual, $id]);
                    if ($i = infoStok($pdo, $id)) catat($pdo, $i['nama_produk'], $i['nama_kategori'], $i['harga'], "Admin memperbarui stok di {$i['nama_mitra']} (awal $awal, terjual $terjual)");
                } else {
                    $idMitra  = (int)($in['id_mitra'] ?? 0);
                    $idProduk = (int)($in['id_produk'] ?? 0);
                    $a = $pdo->prepare('SELECT 1 FROM mitra WHERE id_mitra = ?');   $a->execute([$idMitra]);
                    $b = $pdo->prepare('SELECT 1 FROM produk WHERE id_produk = ?'); $b->execute([$idProduk]);
                    if (!$a->fetch()) balas(['error' => 'Mitra tidak valid'], 422);
                    if (!$b->fetch()) balas(['error' => 'Produk tidak valid'], 422);
                    try {
                        $pdo->prepare('INSERT INTO stok_mitra (id_mitra, id_produk, stok_awal, stok_terjual, terakhir_update) VALUES (?, ?, ?, ?, CURDATE())')
                            ->execute([$idMitra, $idProduk, $awal, $terjual]);
                        if ($i = infoStok($pdo, (int)$pdo->lastInsertId())) catat($pdo, $i['nama_produk'], $i['nama_kategori'], $i['harga'], "Admin menambah stok di {$i['nama_mitra']} (awal $awal)");
                    } catch (PDOException $e) {
                        if ($e->getCode() === '23000') balas(['error' => 'Produk ini sudah ada di stok mitra tersebut. Edit saja stoknya.'], 409);
                        throw $e;
                    }
                }
                balas(ambilStok($pdo));

            case 'stok_hapus':
                $ids = array_values(array_filter(array_map('intval', (array)($in['ids'] ?? []))));
                foreach ($ids as $id) {
                    $i = infoStok($pdo, $id);
                    $pdo->prepare('DELETE FROM stok_mitra WHERE id_stok = ?')->execute([$id]);
                    if ($i) catat($pdo, $i['nama_produk'], $i['nama_kategori'], $i['harga'], "Admin menghapus stok di {$i['nama_mitra']}");
                }
                balas(ambilStok($pdo));

            case 'simpan_kontak':
                $data = [
                    'whatsapp'        => teks($in['whatsapp'] ?? '', 40),
                    'email'           => teks($in['email'] ?? '', 120),
                    'alamat'          => teks($in['alamat'] ?? '', 255),
                    'jam_operasional' => teks($in['jam_operasional'] ?? '', 60),
                ];
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    balas(['error' => 'Format email tidak benar'], 422);
                }
                $st = $pdo->prepare('INSERT INTO kontak (jenis, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)');
                foreach ($data as $jenis => $nilai) $st->execute([$jenis, $nilai]);
                break;

            case 'tambah_sosmed':
                $pdo->prepare('INSERT INTO media_sosial (platform, username_link, status) VALUES (?, ?, "Aktif")')
                    ->execute([teks($in['platform'] ?? '', 30), linkSosmed($in['link'] ?? '')]);
                break;

            case 'ubah_sosmed':
                $pdo->prepare('UPDATE media_sosial SET platform = ?, username_link = ? WHERE id_sosmed = ?')
                    ->execute([teks($in['platform'] ?? '', 30), linkSosmed($in['link'] ?? ''), (int)($in['id'] ?? 0)]);
                break;

            case 'status_sosmed':
                $pdo->prepare('UPDATE media_sosial SET status = ? WHERE id_sosmed = ?')
                    ->execute([!empty($in['aktif']) ? 'Aktif' : 'Nonaktif', (int)($in['id'] ?? 0)]);
                break;

            case 'hapus_sosmed':
                $pdo->prepare('DELETE FROM media_sosial WHERE id_sosmed = ?')->execute([(int)($in['id'] ?? 0)]);
                break;

            case 'tambah_produk':
                [$nama, $idKat, $harga, $desk] = dataProduk($pdo);
                $foto = simpanFoto();
                try {
                    $pdo->prepare('INSERT INTO produk (id_kategori, nama_produk, harga, deskripsi, gambar) VALUES (?, ?, ?, ?, ?)')
                        ->execute([$idKat, $nama, $harga, $desk, $foto]);
                } catch (Throwable $e) {
                    hapusFoto($foto);
                    throw $e;
                }
                catat($pdo, $nama, $pdo->query('SELECT nama_kategori FROM kategori WHERE id_kategori = ' . (int)$idKat)->fetchColumn(), $harga, 'Ditambahkan');
                break;

            case 'ubah_produk':
                $id = (int)($_POST['id'] ?? 0);
                [$nama, $idKat, $harga, $desk] = dataProduk($pdo);
                $lama = $pdo->prepare('SELECT gambar FROM produk WHERE id_produk = ?');
                $lama->execute([$id]);
                $row = $lama->fetch();
                if (!$row) balas(['error' => 'Produk tidak ditemukan'], 404);

                $foto = simpanFoto();
                try {
                    if ($foto) {
                        $pdo->prepare('UPDATE produk SET id_kategori=?, nama_produk=?, harga=?, deskripsi=?, gambar=? WHERE id_produk=?')
                            ->execute([$idKat, $nama, $harga, $desk, $foto, $id]);
                    } else {
                        $pdo->prepare('UPDATE produk SET id_kategori=?, nama_produk=?, harga=?, deskripsi=? WHERE id_produk=?')
                            ->execute([$idKat, $nama, $harga, $desk, $id]);
                    }
                } catch (Throwable $e) {
                    hapusFoto($foto);
                    throw $e;
                }
                if ($foto) hapusFoto($row['gambar']);
                catat($pdo, $nama, $pdo->query('SELECT nama_kategori FROM kategori WHERE id_kategori = ' . (int)$idKat)->fetchColumn(), $harga, 'Diperbarui');
                break;

            case 'hapus_produk':
                $ids = array_values(array_filter(array_map('intval', (array)($in['ids'] ?? []))));
                if (!$ids) balas(['error' => 'Pilih produk yang ingin dihapus'], 422);
                $ambil = $pdo->prepare('SELECT p.gambar, p.nama_produk, p.harga, k.nama_kategori FROM produk p JOIN kategori k ON k.id_kategori = p.id_kategori WHERE p.id_produk = ?');
                $hapus = $pdo->prepare('DELETE FROM produk WHERE id_produk = ?');
                foreach ($ids as $id) {
                    $ambil->execute([$id]);
                    $row = $ambil->fetch();
                    $hapus->execute([$id]);
                    if ($row) {
                        hapusFoto($row['gambar']);
                        catat($pdo, $row['nama_produk'], $row['nama_kategori'], $row['harga'], 'Dihapus');
                    }
                }
                break;

            default:
                balas(['error' => 'Aksi tidak dikenal'], 400);
        }
        balas(ambilData($pdo) + ['riwayat' => ambilRiwayat($pdo)]);
    } catch (Throwable $e) {
        balas(['error' => 'Gagal memproses data'], 500);
    }
}


// Proses login menggunakan tabel admin
$error = '';

if (empty($_SESSION['admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {

        try {
            $lokal = in_array(
                $_SERVER['SERVER_NAME'] ?? '',
                ['localhost', '127.0.0.1'],
                true
            );

            $cfg = $lokal
                ? ['db' => 'callista_db', 'user' => 'root', 'pass' => '']
                : ['db' => 'callista_db', 'user' => 'callista_app', 'pass' => DB_PASS_HOSTING];

            $pdoLogin = new PDO(
                'mysql:host=localhost;dbname=' . $cfg['db'] . ';charset=utf8mb4',
                $cfg['user'],
                $cfg['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            // Cari akun berdasarkan username
            $stmt = $pdoLogin->prepare(
                'SELECT id_admin, username, password_hash, nama
                 FROM admin
                 WHERE username = ?
                 LIMIT 1'
            );

            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            $valid = false;

            if ($admin) {
                $storedPassword = (string)$admin['password_hash'];
                if (password_verify($password, $storedPassword)) {
                    $valid = true;
                }

                elseif (hash_equals($storedPassword, $password)) {

                    $hashBaru = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $update = $pdoLogin->prepare(
                        'UPDATE admin
                         SET password_hash = ?
                         WHERE id_admin = ?'
                    );

                    $update->execute([
                        $hashBaru,
                        $admin['id_admin']
                    ]);

                    $valid = true;
                }
            }

            if ($valid) {

                session_regenerate_id(true);

                $_SESSION['admin'] = true;
                $_SESSION['admin_id'] = $admin['id_admin'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_nama'] = $admin['nama'];

                if ((int)$admin['id_admin'] === 1) {
                    header('Location: admin.php');
                } elseif ((int)$admin['id_admin'] >= 2 && (int)$admin['id_admin'] <= 5) {
                    header('Location: mitra.php');
                } else {
                    $_SESSION = [];
                    session_destroy();
                    header('Location: admin.php');
                }
                exit;

            } else {
                sleep(1);
                $error = 'Username atau password salah.';
            }

        } catch (Throwable $e) {
            $error = 'Login gagal. Periksa koneksi database.';
        }
    }
}

if (!empty($_SESSION['admin'])) {
    $sessionAdminId = (int)($_SESSION['admin_id'] ?? 0);
    if ($sessionAdminId >= 2 && $sessionAdminId <= 5) {
        header('Location: mitra.php');
        exit;
    }
    if ($sessionAdminId !== 1) {
        $_SESSION = [];
        session_destroy();
        header('Location: admin.php');
        exit;
    }
}

if (empty($_SESSION['admin'])) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Login Admin — Callista Dessert & Cake</title>
<link rel="icon" type="image/png" href="asset-callista/images/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
@font-face{font-family:'Baby Gumbout';src:url('font/BabyGumbout.ttf') format('truetype');font-display:swap}
:root{--pink-bg:#FFD2E0;--pink-accent:#EA6D95;--pink-mid:#F6A9C1;--cream:#FFFCFA;--paper:#FFFBF7;--baby-blue:#BFE9F8}
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:90px 16px 50px;font-family:'Poppins',sans-serif;background-color:var(--pink-bg);background-image:radial-gradient(circle,rgba(255,255,255,.7) 0 12px,transparent 13px),radial-gradient(circle,rgba(255,255,255,.7) 0 12px,transparent 13px);background-size:150px 150px;background-position:0 0,75px 75px;position:relative;overflow:hidden}
body::before{content:"";position:fixed;left:0;right:0;top:0;height:calc(100vw / 32);min-height:18px;pointer-events:none;background:radial-gradient(ellipse 50% 100% at 50% 0,var(--cream) 98%,transparent 100%) 0 0/calc(100% / 16) 100% repeat-x}
.card{position:relative;z-index:1;width:100%;max-width:500px;background:var(--cream);border-radius:26px;padding:52px 48px 46px;text-align:center;box-shadow:0 14px 40px rgba(234,109,149,.22)}
.logo{height:84px;width:auto;max-width:100%;display:block;margin:0 auto 12px}
h1{font-family:'Baby Gumbout','Poppins',sans-serif;font-weight:400;font-size:40px;line-height:1.1;color:var(--pink-accent);margin-bottom:8px}
.sub{font-size:14px;color:#a09097;margin-bottom:32px}
.err{background:#fff0f4;color:#d6446f;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:14px}
.field{position:relative;margin-bottom:18px}
.field .lead{position:absolute;left:17px;top:50%;transform:translateY(-50%);width:18px;height:18px;color:var(--pink-accent);pointer-events:none}
.field input{width:100%;height:56px;padding:0 48px 0 48px;border:1.5px solid var(--pink-bg);border-radius:14px;background:var(--paper);font:inherit;font-size:15px;color:#555;outline:none;transition:border-color .2s,box-shadow .2s}
.field input:focus{border-color:var(--pink-accent);box-shadow:0 0 0 4px rgba(234,109,149,.12)}
.eye{position:absolute;right:6px;top:10px;width:36px;height:36px;border:0;background:none;color:#b8a6ae;cursor:pointer;display:flex;align-items:center;justify-content:center}
.eye:hover{color:var(--pink-accent)}
.eye svg{width:20px;height:20px}
.go{width:100%;height:56px;border:0;border-radius:14px;background:var(--pink-accent);color:#fff;font:inherit;font-weight:600;font-size:15px;cursor:pointer;transition:background .2s}
.go:hover{background:#dc5b84}
.back{display:inline-block;margin-top:26px;font-size:14px;color:var(--pink-accent);text-decoration:none}
.back:hover{text-decoration:underline}
@media (max-width:520px){body{padding-top:80px}.card{padding:38px 24px 34px}h1{font-size:32px}}
</style>
</head>
<body>

<main class="card">
  <form method="post" autocomplete="off">
    <img class="logo" src="asset-callista/images/logo.png" alt="Callista Dessert & Cake">

    <h1>Login Admin</h1>
    <p class="sub">Masukkan username dan password untuk masuk ke dashboard</p>

    <?php if ($error): ?>
      <div class="err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- INPUT USERNAME -->
    <div class="field">
      <svg class="lead" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="1.8"
        stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="8" r="4"/>
        <path d="M5 21a7 7 0 0 1 14 0"/>
      </svg>

      <input
        type="text"
        id="username"
        name="username"
        placeholder="Username"
        autocomplete="username"
        required
        autofocus
      >
    </div>

    <!-- INPUT PASSWORD -->
    <div class="field">
      <svg class="lead" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="1.8"
        stroke-linecap="round" stroke-linejoin="round">
        <rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/>
        <path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>
      </svg>

      <input
        type="password"
        id="pw"
        name="password"
        placeholder="Password"
        autocomplete="current-password"
        required
      >

      <button
        type="button"
        class="eye"
        id="eye"
        aria-label="Tampilkan password"
      >
        <svg viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="1.8"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
      </button>
    </div>

    <button type="submit" class="go">Masuk</button>

    <a class="back" href="index.html">← Kembali ke website</a>
  </form>
</main>

<script>
document.getElementById('eye').addEventListener('click', function () {
  var input = document.getElementById('pw');

  input.type = input.type === 'password' ? 'text' : 'password';
});

</script>
</body>
</html>
<?php
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Callista Dessert & Cake — Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <div class="page-label" id="pageLabel">Admin - Menu</div>

  <div class="dashboard">
    <header class="topbar">
      <div class="brand">
        <img class="brand-logo" src="asset-callista/images/logo.png" alt="Callista Dessert &amp; Cake">
      </div>
      <button class="logout" id="logoutBtn">Logout <svg class="i" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg></button>
    </header>

    <aside class="sidebar">
      <div class="profile">
        <div class="avatar"><svg viewBox="0 0 24 24"><circle cx="12" cy="9" r="4.2" fill="#fff"/><path d="M3.5 24c0-5 3.8-8 8.5-8s8.5 3 8.5 8z" fill="#fff"/></svg></div>
        <div>
          <strong id="profileName">Admin</strong>
          <small id="profileSub">Callista Dessert &amp; Cake</small>
        </div>
      </div>

      <nav>
        <button class="nav-item" data-page="contact">
          <span class="nav-icon"><svg class="i" viewBox="0 0 24 24"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M9.2 8.4c.3 1.6 1.7 3.6 3.2 4.6 1 .6 1.7.5 2.2-.3l-.9-1.1-.9.2c-.8-.4-1.5-1.1-1.9-1.9l.3-.8-1.1-.9c-.6.5-.9 1.2-.9 2.2z" stroke-width="1.2"/></svg></span> Kontak &amp; Media Sosial
        </button>
        <button class="nav-item active" data-page="menu">
          <span class="nav-icon"><svg class="i" viewBox="0 0 24 24"><path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="M4.5 15.5h15"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/></svg></span> Kelola Katalog Produk
        </button>
        <button class="nav-item" data-page="stock">
          <span class="nav-icon"><svg class="i" viewBox="0 0 24 24"><path d="M21 8v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8"/><path d="M1 3h22v5H1z"/><path d="M12 18v-6"/><path d="m9 14.5 3-3 3 3"/></svg></span> Pemantauan Stok Mitra
        </button>
      </nav>
    </aside>

    <main class="main-content">
      <!-- HALAMAN KATALOG PRODUK -->
      <section class="view" id="view-menu">
        <div class="section-heading">
          <h1>Kelola Katalog Produk</h1>
          <button class="primary" id="addProductTop"><svg class="i" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg> Tambah Produk</button>
        </div>

        <div class="menu-layout">
          <div class="panel product-panel">
            <div class="toolbar">
              <label class="search">
                <svg class="i" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                <input id="productSearch" placeholder="Cari Produk...">
              </label>
              <div class="toolbar-actions">
                <button class="primary small" id="addProduct">Tambah Produk</button>
                <button class="danger small" id="deleteProducts">Hapus</button>
              </div>
            </div>

            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th class="check-col"><input type="checkbox" id="selectAllProducts"></th>
                    <th>Gambar</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                  </tr>
                </thead>
                <tbody id="productRows"></tbody>
              </table>
            </div>

            <div class="pagination">
              <button disabled>‹</button>
              <span id="productPage">1 2 3 ... 10</span>
              <button>›</button>
            </div>
          </div>

          <div class="right-column">
            <div class="panel total-card">
              <div class="scallop-strip"></div>
              <p>Total Produk di Katalog:</p>
              <strong id="totalProducts">0</strong>
            </div>

            <div class="panel category-card">
              <div class="scallop-strip"></div>
              <h3>Kategori:</h3>
              <div class="chips">
                <span class="chip pink">Mochi</span>
                <span class="chip rose">Crepe Cake</span>
                <span class="chip pink">Mochi Cake</span>
                <span class="chip pink">Dubai Chewy</span>
              </div>
            </div>

            <div class="panel history-card">
              <div class="history-title"><svg class="i" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg> Riwayat Katalog:</div>
              <table>
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
                <tbody id="historyRows"></tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- HALAMAN PEMANTAUAN STOK -->
      <section class="view hidden" id="view-stock">
        <div class="section-heading stock-heading">
          <h1>Pemantauan Stok Mitra</h1>
        </div>

        <div class="stock-stats">
          <div class="stat">
            <span class="stat-icon"><svg class="i" viewBox="0 0 24 24"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2 2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/></svg></span>
            <div>
              <small>Total Mitra</small>
              <strong id="stockMitraCount">0</strong>
            </div>
          </div>
          <div class="stat">
            <span class="stat-icon basket"><svg class="i" viewBox="0 0 24 24"><path d="m15 11-1 9"/><path d="m19 11-4-7"/><path d="M2 11h20"/><path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4"/><path d="M4.5 15.5h15"/><path d="m5 11 4-7"/><path d="m9 11 1 9"/></svg></span>
            <div>
              <small>Total Produk</small>
              <strong id="stockProductCount">0</strong>
            </div>
          </div>
        </div>

        <p class="muted intro">Unduh data untuk memudahkan pengelolaan persediaan.</p>

        <div class="panel stock-panel">
          <div class="stock-toolbar">
            <label class="search">
              <svg class="i" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
              <input id="stockSearch" placeholder="Cari Produk atau Mitra...">
            </label>

            <select id="categoryFilter">
              <option value="">Semua Kategori</option>
            </select>

            <select id="partnerFilter">
              <option value="">Semua Mitra</option>
            </select>

            <div class="stock-actions">
              <button class="danger small" id="deleteStock">Hapus</button>
              <button class="primary small" id="addStock">Tambah Stok</button>
            </div>

            <button class="primary export" id="exportStock"><svg class="i" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg> Export Data</button>
          </div>

          <div class="table-wrap">
            <table class="stock-table">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllStock"></th>
                  <th>Produk</th>
                  <th>Kategori</th>
                  <th>Mitra</th>
                  <th>Stok<br>Awal</th>
                  <th>Stok<br>Terjual</th>
                  <th>Sisa<br>Stok</th>
                  <th>Status</th>
                  <th>Terakhir<br>Update</th>
                </tr>
              </thead>
              <tbody id="stockRows"></tbody>
            </table>
          </div>

          <div class="pagination">
            <button disabled>‹</button>
            <span>1 2 3 ... 10</span>
            <button>›</button>
          </div>
        </div>
      </section>

      <!-- HALAMAN KONTAK DAN MEDIA SOSIAL -->
      <section class="view hidden" id="view-contact">
        <div class="section-heading">
          <h1>Kontak &amp; Media Sosial</h1>
          <button class="primary" id="addInfo"><svg class="i" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg> Tambah Informasi</button>
        </div>

        <div class="panel contact-panel">
          <div class="pink-panel-title">
            <div class="round-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
            <div>
              <strong>Informasi Kontak</strong>
              <small>Atur informasi kontak utama yang akan ditampilkan di website</small>
            </div>
            <button class="light-button" id="editContact"><svg class="i" viewBox="0 0 24 24"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg> Edit Kontak</button>
          </div>

          <div class="contact-cards" id="contactCards">
            <div class="contact-card">
              <span class="contact-icon"><svg class="i" viewBox="0 0 24 24"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M9.2 8.4c.3 1.6 1.7 3.6 3.2 4.6 1 .6 1.7.5 2.2-.3l-.9-1.1-.9.2c-.8-.4-1.5-1.1-1.9-1.9l.3-.8-1.1-.9c-.6.5-.9 1.2-.9 2.2z" stroke-width="1.2"/></svg></span>
              <strong>WhatsApp</strong>
              <p>+62 123 456 7890</p>
            </div>
            <div class="contact-card">
              <span class="contact-icon"><svg class="i" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
              <strong>Email</strong>
              <p>email@gmail.com</p>
            </div>
            <div class="contact-card">
              <span class="contact-icon"><svg class="i" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span>
              <strong>Alamat</strong>
              <p>Jl. Cabang, Banyumas</p>
            </div>
            <div class="contact-card">
              <span class="contact-icon"><svg class="i" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
              <strong>Jam Operasional</strong>
              <p>08.00–21.00</p>
            </div>
          </div>
        </div>

        <div class="panel social-panel">
          <div class="pink-panel-title">
            <div class="round-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22z"/><circle cx="8.5" cy="12" r="1.2" fill="#fff"/><circle cx="12" cy="12" r="1.2" fill="#fff"/><circle cx="15.5" cy="12" r="1.2" fill="#fff"/></svg></div>
            <div>
              <strong>Media Sosial</strong>
              <small>Kelola akun media sosial Callista Dessert &amp; Cake</small>
            </div>
          </div>

          <div class="table-wrap">
            <table class="social-table">
              <thead>
                <tr>
                  <th><input type="checkbox" id="selectAllSocial"></th>
                  <th>Platform</th>
                  <th>Username/Link</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="socialRows"></tbody>
            </table>
          </div>
        </div>

        <p class="footer-note">Informasi Kontak akan muncul di bagian footer website</p>
      </section>
    </main>
  </div>

  <!-- FORM TAMBAH / EDIT STOK -->
  <dialog id="stockDialog" class="modal">
    <form id="stockForm" method="dialog">
      <div class="modal-head">
        <h2 id="stockModalTitle">Tambah Stok</h2>
        <button type="button" class="close-modal" data-close="stockDialog">×</button>
      </div>

      <label id="stockMitraField">Mitra
        <select name="mitra"></select>
      </label>

      <label>Produk
        <select name="produk"></select>
      </label>

      <label>Stok Awal
        <input name="awal" type="number" min="0" required placeholder="20">
      </label>

      <label>Stok Terjual
        <input name="terjual" type="number" min="0" required placeholder="0" value="0">
      </label>

      <div class="modal-actions">
        <button type="button" class="secondary" data-close="stockDialog">Batal</button>
        <button class="primary" type="submit">Simpan Stok</button>
      </div>
    </form>
  </dialog>

  <!-- FORM TAMBAH / EDIT PRODUK -->
  <dialog id="productDialog" class="modal">
    <form id="productForm" method="dialog" enctype="multipart/form-data">
      <div class="modal-head">
        <h2 id="productModalTitle">Tambah Produk</h2>
        <button type="button" class="close-modal" data-close="productDialog">×</button>
      </div>

      <label>Nama Produk
        <input name="nama" required maxlength="120" placeholder="Contoh: Mochi Anggur">
      </label>

      <label>Kategori
        <select name="id_kategori" required></select>
      </label>

      <label>Harga (Rp)
        <input name="harga" type="number" min="1" required placeholder="5500">
      </label>

      <label>Deskripsi
        <textarea name="deskripsi" rows="3" maxlength="1000" placeholder="Deskripsi singkat produk"></textarea>
      </label>

      <label>Foto Produk (JPG/PNG/WebP, maks 2 MB)
        <input name="gambar" type="file" accept="image/png,image/jpeg,image/webp">
        <small>Saat edit, kosongkan kalau tidak ingin mengganti foto.</small>
      </label>

      <div class="modal-actions">
        <button type="button" class="secondary" data-close="productDialog">Batal</button>
        <button class="primary" type="submit">Simpan Produk</button>
      </div>
    </form>
  </dialog>

  <!-- FORM EDIT KONTAK -->
  <dialog id="contactDialog" class="modal">
    <form id="contactForm" method="dialog">
      <div class="modal-head">
        <h2>Edit Informasi Kontak</h2>
        <button type="button" class="close-modal" data-close="contactDialog">×</button>
      </div>

      <label>WhatsApp
        <input name="whatsapp" value="+62 123 456 7890" required>
      </label>
      <label>Email
        <input name="email" value="email@gmail.com" required>
      </label>
      <label>Alamat
        <input name="address" value="Jl. Cabang, Banyumas" required>
      </label>
      <label>Jam Operasional
        <input name="hours" value="08.00–21.00" required>
      </label>

      <div class="modal-actions">
        <button type="button" class="secondary" data-close="contactDialog">Batal</button>
        <button class="primary" type="submit">Simpan Perubahan</button>
      </div>
    </form>
  </dialog>

  <!-- FORM TAMBAH / EDIT MEDIA SOSIAL -->
  <dialog id="socialDialog" class="modal">
    <form id="socialForm" method="dialog">
      <div class="modal-head">
        <h2 id="socialModalTitle">Tambah Media Sosial</h2>
        <button type="button" class="close-modal" data-close="socialDialog">×</button>
      </div>

      <label>Platform
        <input name="platform" required placeholder="Instagram">
      </label>
      <label>Username/Link
        <input name="handle" required placeholder="@callista.dessertcake">
      </label>

      <div class="modal-actions">
        <button type="button" class="secondary" data-close="socialDialog">Batal</button>
        <button class="primary" type="submit">Simpan</button>
      </div>
    </form>
  </dialog>

  <div class="toast" id="toast"></div>
  <script src="admin.js"></script>
  <script>document.getElementById('logoutBtn').addEventListener('click',function(e){e.stopImmediatePropagation();location.href='admin.php?logout=1';},true);</script>
</body>
</html>
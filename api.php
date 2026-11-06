<?php
header('Content-Type: application/json; charset=utf-8');
$host = 'localhost';
$db   = 'callista_db';
$user = 'callista_app';
$pass = '?9J%;@4,UGkv^VjA';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $rows = $pdo->query(
        'SELECT id_produk, id_kategori, nama_produk, harga, deskripsi, gambar
         FROM produk
         ORDER BY id_kategori, id_produk'
    )->fetchAll();

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
    foreach ($rows as &$r) {
        // foto upload yang masih ada di server dibiarkan
        if (!empty($r['gambar']) && is_file(__DIR__ . '/' . $r['gambar'])) continue;

        $r['gambar'] = null;
        $n = $fotoLama[(int)$r['id_produk']] ?? null;
        if ($n) {
            foreach (['.png', '.webp', '.jpg', '.jpeg', '.avif', ''] as $e) {
                $k = strtolower($n . $e);
                if (isset($files[$k])) {
                    $r['gambar'] = 'asset-callista/images/' . $files[$k];
                    break;
                }
            }
        }
    }
    unset($r);

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database tidak dapat diakses.']);
}
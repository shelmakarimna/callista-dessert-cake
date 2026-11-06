<?php
// Hasil file ini selalu berupa JSON supaya mudah dibaca script.js
header('Content-Type: application/json; charset=utf-8');
// Kunci akses ke Gemini (Google AI Studio). Jangan dibagikan ke siapa pun.
$apiKey = 'ISI-API-KEY-GEMINI-DI-SINI';
// Password database callista_app (sama dengan di admin.php dan mitra.php).
$dbPass = 'ISI-PASSWORD-DATABASE-DI-SINI';
// Nama model AI yang dipakai.
$model = 'gemini-3.5-flash-lite';

// Fungsi bantu: kirim balasan JSON lalu hentikan program.
// $code = kode status (200 berhasil, 400/500/502 = error).
function jawab($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- 1. TERIMA PERTANYAAN ----------
// Baca pesan yang dikirim chatbot dari halaman website.
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
// Tolak kalau pesannya kosong.
if ($message === '') {
    jawab(['error' => 'Pesan tidak boleh kosong.'], 400);
}
// Batasi maksimal 500 huruf supaya tidak disalahgunakan.
$message = mb_substr($message, 0, 500);

// ---------- 2. AMBIL DATA DARI DATABASE ----------
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=callista_db;charset=utf8mb4',
        'callista_app',
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    // Daftar semua produk beserta kategori, harga, dan deskripsinya.
    $rows = $pdo->query(
        'SELECT k.nama_kategori, p.nama_produk, p.harga, p.deskripsi
         FROM produk p
         JOIN kategori k ON k.id_kategori = p.id_kategori
         ORDER BY k.nama_kategori, p.nama_produk'
    )->fetchAll(PDO::FETCH_ASSOC);

    // Data kontak toko (alamat, whatsapp, email, jam_operasional) dalam bentuk jenis => nilai.
    $kontak = $pdo->query('SELECT jenis, nilai FROM kontak')->fetchAll(PDO::FETCH_KEY_PAIR);
    // Daftar mitra yang masih aktif, diurutkan per kota.
    $mitra = $pdo->query('SELECT nama_mitra, kota FROM mitra WHERE aktif = 1 ORDER BY kota, nama_mitra')->fetchAll(PDO::FETCH_ASSOC);
    // Kalau database bermasalah, tampilkan pesan ramah (detail error tidak ditampilkan ke pengunjung).
} catch (PDOException $e) {
    jawab(['error' => "Maaf, Callista's AI lagi gangguan. Coba lagi ya! 💗"], 500);
}

// ---------- 3. SUSUN DATA JADI TEKS UNTUK AI ----------
$grup = [];
foreach ($rows as $r) {
    $grup[$r['nama_kategori']][] = '- ' . $r['nama_produk']
        . ' | Rp' . number_format($r['harga'], 0, ',', '.')
        . ' | ' . ($r['deskripsi'] ?: 'tanpa deskripsi');
}
$katalog = '';
foreach ($grup as $kategori => $items) {
    $katalog .= "[$kategori]\n" . implode("\n", $items) . "\n\n";
}
if ($katalog === '') {
    $katalog = '(katalog masih kosong)';
}

// Daftar mitra: kelompokkan per kota, misalnya "- Purwokerto: Rita Isola, Indogrosir".
$perKota = [];
foreach ($mitra as $m) {
    $perKota[$m['kota']][] = $m['nama_mitra'];
}
$daftarMitra = '';
foreach ($perKota as $kota => $nama) {
    $daftarMitra .= "- $kota: " . implode(', ', $nama) . "\n";
}
if ($daftarMitra === '') {
    $daftarMitra = '(belum ada data mitra)';
}

// Info toko: gabungan alamat, kontak, jam buka, dan daftar mitra.
$infoToko = "Alamat: " . ($kontak['alamat'] ?? '-') . "\n"
    . "WhatsApp: " . ($kontak['whatsapp'] ?? '-') . "\n"
    . "Email: " . ($kontak['email'] ?? '-') . "\n"
    . "Jam operasional: " . ($kontak['jam_operasional'] ?? '-') . "\n"
    . "Mitra / tempat produk Callista tersedia:\n" . $daftarMitra;

// Petunjuk untuk AI: siapa dia, cara menjawab, aturan, lalu data (INFO TOKO + KATALOG).
// AI hanya boleh menjawab dari data ini supaya tidak mengarang.
$systemPrompt = "Kamu adalah \"Callista's AI\", asisten ramah untuk toko Callista Dessert & Cake.
Jawab dalam bahasa Indonesia, singkat (maksimal 3-4 kalimat), santai, dan boleh pakai emoji secukupnya.

ATURAN:
- Jawab pertanyaan soal produk, harga, varian, dan deskripsi HANYA berdasarkan KATALOG di bawah.
- Jawab pertanyaan soal lokasi, alamat, mitra, cabang, kontak, dan jam buka HANYA berdasarkan INFO TOKO di bawah.
- Jangan mengarang produk, harga, varian, alamat, atau mitra yang tidak ada di data.
- Kalau informasinya tidak ada, bilang belum punya infonya dan sarankan hubungi admin lewat WhatsApp.
- Kalau pertanyaan tidak berhubungan dengan Callista/dessert, tolak dengan sopan dan arahkan balik ke topik dessert.

INFO TOKO:
$infoToko

KATALOG:
$katalog";

// ---------- 4. KIRIM KE GEMINI ----------
// Susun isi permintaan: petunjuk (systemInstruction) + pertanyaan pengunjung (contents).
$body = json_encode([
    'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
    'contents' => [['role' => 'user', 'parts' => [['text' => $message]]]],
    'generationConfig' => ['temperature' => 0.4],
], JSON_UNESCAPED_UNICODE);

// Alamat layanan Gemini.
$url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent";
// Coba maksimal 2 kali, karena Gemini kadang sibuk.
$reply = null;
for ($i = 0; $i < 2; $i++) {
    // Kirim permintaan lewat cURL (batas waktu 12 detik).
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $apiKey],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
    ]);
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Ambil teks jawaban dari balasan Gemini.
    $data = json_decode((string) $res, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

    // Berhasil: simpan jawaban dan berhenti mengulang.
    if ($status === 200 && $text) {
        $reply = $text;
        break;
    }
    
    if (!in_array($status, [0, 429, 500, 503], true)) {
        break;
    }
    sleep(1);
}

// ---------- 5. KIRIM JAWABAN KE WEBSITE ----------
// Kalau dua kali percobaan gagal, tampilkan pesan maaf.
if ($reply === null) {
    jawab(['error' => "Maaf, Callista's AI lagi gangguan. Coba lagi ya! 💗"], 502);
}

// Berhasil: kirim jawaban AI ke chatbot.
jawab(['reply' => $reply]);
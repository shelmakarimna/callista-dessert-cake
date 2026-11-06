import 'dotenv/config';
import express from 'express';
import cors from 'cors';
import mysql from 'mysql2/promise';
import { GoogleGenAI } from '@google/genai';

const app = express();
app.use(cors());
app.use(express.json());

const ai = new GoogleGenAI({ apiKey: process.env.GEMINI_API_KEY });
const MODEL = process.env.GEMINI_MODEL || 'gemini-2.5-flash';
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'db_callistadessert',
  waitForConnections: true,
  connectionLimit: 5,
});

async function getKatalogText() {
  const [rows] = await pool.query(`
    SELECT k.nama_kategori, p.nama_produk, p.harga, p.deskripsi
    FROM produk p
    JOIN kategori k ON k.id_kategori = p.id_kategori
    ORDER BY k.nama_kategori, p.nama_produk
  `);

  if (!rows.length) return '(katalog masih kosong)';

  const rupiah = (n) => 'Rp' + Number(n).toLocaleString('id-ID');
  const grouped = {};
  rows.forEach((r) => {
    (grouped[r.nama_kategori] ||= []).push(
      `- ${r.nama_produk} | ${rupiah(r.harga)} | ${r.deskripsi || 'tanpa deskripsi'}`
    );
  });

  return Object.entries(grouped)
    .map(([kategori, items]) => `[${kategori}]\n${items.join('\n')}`)
    .join('\n\n');
}

function buildSystemPrompt(katalog) {
  return `Kamu adalah "Callista's AI", asisten ramah untuk toko Callista Dessert & Cake.
Jawab dalam bahasa Indonesia, singkat (maksimal 3-4 kalimat), santai, dan boleh pakai emoji secukupnya.

ATURAN:
- Jawab pertanyaan soal produk, harga, varian, dan deskripsi HANYA berdasarkan KATALOG di bawah.
- Jangan mengarang produk, harga, atau varian yang tidak ada di katalog.
- Kalau informasinya tidak ada di katalog, bilang belum punya infonya dan sarankan hubungi admin lewat WhatsApp.
- Kalau pertanyaan tidak berhubungan dengan Callista/dessert, tolak dengan sopan dan arahkan balik ke topik dessert.

KATALOG:
${katalog}`;
}

app.post('/chat', async (req, res) => {
  const message = String(req.body?.message || '').trim();
  if (!message) return res.status(400).json({ error: 'Pesan tidak boleh kosong.' });

  try {
    const katalog = await getKatalogText();
    const result = await ai.models.generateContent({
      model: MODEL,
      contents: message.slice(0, 500),
      config: { systemInstruction: buildSystemPrompt(katalog), temperature: 0.4 },
    });
    res.json({ reply: result.text });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Maaf, Callista\'s AI lagi gangguan. Coba lagi ya! 💗' });
  }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Chatbot server jalan di http://localhost:${PORT}`));
const fs = require('fs');
const path = require('path');
const PDFDocument = require('pdfkit');

const outputPath = path.join(__dirname, 'panduan-pemakaian-mbc-cbt.pdf');
const doc = new PDFDocument({
  size: 'A4',
  margin: 48,
  bufferPages: true,
  info: {
    Title: 'Panduan Pemakaian MBC CBT',
    Author: 'MBC',
    Subject: 'Panduan alur dan pemakaian aplikasi CBT Online MBC',
  },
});

doc.pipe(fs.createWriteStream(outputPath));

const colors = {
  emerald900: '#064e3b',
  emerald800: '#065f46',
  emerald700: '#047857',
  emerald600: '#059669',
  emerald50: '#ecfdf5',
  zinc950: '#18181b',
  zinc700: '#3f3f46',
  zinc600: '#52525b',
  zinc500: '#71717a',
  zinc200: '#e4e4e7',
  zinc100: '#f4f4f5',
  white: '#ffffff',
};

function addPageIfNeeded(height = 70) {
  if (doc.y + height > doc.page.height - doc.page.margins.bottom) {
    doc.addPage();
  }
}

function section(title) {
  addPageIfNeeded(80);
  doc.moveDown(0.8);
  doc
    .font('Helvetica-Bold')
    .fontSize(17)
    .fillColor(colors.emerald900)
    .text(title);
  doc
    .moveTo(doc.page.margins.left, doc.y + 5)
    .lineTo(doc.page.width - doc.page.margins.right, doc.y + 5)
    .strokeColor(colors.zinc200)
    .lineWidth(1)
    .stroke();
  doc.moveDown(0.9);
}

function sub(title) {
  addPageIfNeeded(50);
  doc
    .font('Helvetica-Bold')
    .fontSize(12.5)
    .fillColor(colors.zinc950)
    .text(title);
  doc.moveDown(0.25);
}

function paragraph(text) {
  addPageIfNeeded(50);
  doc
    .font('Helvetica')
    .fontSize(10.5)
    .fillColor(colors.zinc700)
    .text(text, { lineGap: 3 });
  doc.moveDown(0.55);
}

function bullets(items) {
  items.forEach((item) => {
    addPageIfNeeded(34);
    doc
      .font('Helvetica')
      .fontSize(10.5)
      .fillColor(colors.zinc700)
      .text(`- ${item}`, { indent: 12, lineGap: 3 });
  });
  doc.moveDown(0.45);
}

function numbered(items) {
  items.forEach((item, index) => {
    addPageIfNeeded(36);
    doc
      .font('Helvetica')
      .fontSize(10.5)
      .fillColor(colors.zinc700)
      .text(`${index + 1}. ${item}`, { indent: 12, lineGap: 3 });
  });
  doc.moveDown(0.45);
}

function pill(text, x, y) {
  const width = doc.widthOfString(text, { size: 9 }) + 18;
  doc.roundedRect(x, y, width, 22, 7).fillAndStroke(colors.emerald50, '#a7f3d0');
  doc.font('Helvetica-Bold').fontSize(8.5).fillColor(colors.emerald800).text(text, x + 9, y + 7);
  return width;
}

function infoBox(title, lines, x, y, width) {
  doc.roundedRect(x, y, width, 92, 8).fillAndStroke('#fafafa', colors.zinc200);
  doc.font('Helvetica-Bold').fontSize(12).fillColor(colors.zinc950).text(title, x + 14, y + 14);
  let textY = y + 36;
  lines.forEach((line) => {
    doc.font('Helvetica').fontSize(10).fillColor(colors.zinc600).text(line, x + 14, textY, { width: width - 28 });
    textY += 16;
  });
}

function simpleTable(headers, rows) {
  const left = doc.page.margins.left;
  const width = doc.page.width - doc.page.margins.left - doc.page.margins.right;
  const colWidth = width / headers.length;
  const rowHeight = 34;

  addPageIfNeeded(rowHeight * (rows.length + 1) + 20);
  let y = doc.y;

  headers.forEach((header, index) => {
    const x = left + index * colWidth;
    doc.rect(x, y, colWidth, rowHeight).fillAndStroke(colors.emerald50, colors.zinc200);
    doc.font('Helvetica-Bold').fontSize(9.5).fillColor(colors.emerald900).text(header, x + 8, y + 10, {
      width: colWidth - 16,
    });
  });

  y += rowHeight;
  rows.forEach((row) => {
    const calculatedHeight = Math.max(rowHeight, ...row.map((cell) => doc.heightOfString(cell, {
      width: colWidth - 16,
      lineGap: 2,
    }) + 20));
    addPageIfNeeded(calculatedHeight + 8);

    row.forEach((cell, index) => {
      const x = left + index * colWidth;
      doc.rect(x, y, colWidth, calculatedHeight).fillAndStroke(colors.white, colors.zinc200);
      doc.font('Helvetica').fontSize(9.2).fillColor(colors.zinc700).text(cell, x + 8, y + 10, {
        width: colWidth - 16,
        lineGap: 2,
      });
    });
    y += calculatedHeight;
  });
  doc.y = y + 12;
}

function cover() {
  const left = doc.page.margins.left;
  const top = 52;
  const width = doc.page.width - doc.page.margins.left - doc.page.margins.right;

  doc.roundedRect(left, top, width, 190, 8).fill(colors.emerald700);
  doc.font('Helvetica-Bold').fontSize(10).fillColor('#d1fae5').text('PANDUAN OPERASIONAL', left + 28, top + 30, {
    characterSpacing: 1.8,
  });
  doc.font('Helvetica-Bold').fontSize(34).fillColor(colors.white).text('MBC CBT Online', left + 28, top + 58, {
    width: width - 56,
  });
  doc.font('Helvetica').fontSize(12.5).fillColor('#ecfdf5').text(
    'Panduan pemakaian aplikasi CBT MBC untuk admin dan siswa, termasuk alur pembuatan ujian, input soal, token, pengerjaan ujian, koreksi esai, dan rekap nilai.',
    left + 28,
    top + 112,
    { width: width - 70, lineGap: 4 },
  );

  doc.y = top + 225;
  const boxWidth = (width - 12) / 2;
  infoBox('Akses Admin', ['URL: /admin/login', 'Email: admin@mbc.test', 'Password: password'], left, doc.y, boxWidth);
  infoBox('Akses Siswa', ['URL: /ujian', 'Token demo: DEMO-TEST-2026', 'Token lain ada di menu Token'], left + boxWidth + 12, doc.y, boxWidth);
  doc.y += 118;
}

cover();

section('1. Ringkasan Aplikasi');
paragraph('MBC CBT Online adalah aplikasi tes berbasis Laravel Livewire untuk mengelola ujian online SD dan SMP. Sistem memakai pembayaran manual: siswa menghubungi admin, admin memverifikasi pembayaran, lalu admin memberikan token ujian satu kali pakai.');
let x = doc.page.margins.left;
let y = doc.y;
['Pilihan ganda teks', 'PG dengan gambar soal', 'PG dengan gambar opsi', 'Esai teks', 'Esai dengan gambar', 'Stimulus bacaan/gambar'].forEach((item) => {
  const width = doc.widthOfString(item, { size: 9 }) + 18;
  if (x + width > doc.page.width - doc.page.margins.right) {
    x = doc.page.margins.left;
    y += 28;
  }
  x += pill(item, x, y) + 6;
});
doc.y = y + 36;

section('2. Alur Besar Sistem');
numbered([
  'Admin login ke dashboard.',
  'Admin membuat paket ujian dan mengatur jadwal, durasi, status, serta aturan tampil hasil.',
  'Admin menambahkan stimulus jika satu bacaan/gambar dipakai untuk beberapa soal.',
  'Admin membuat soal pilihan ganda atau esai, termasuk gambar pada pertanyaan atau opsi jawaban.',
  'Siswa melakukan pembayaran manual kepada admin.',
  'Admin membuat token ujian dan memberikannya kepada siswa.',
  'Siswa membuka portal ujian, memasukkan token, dan mengisi data diri.',
  'Siswa mengerjakan ujian. Jawaban tersimpan otomatis dan ujian auto-submit saat waktu habis.',
  'Sistem menghitung nilai pilihan ganda secara otomatis.',
  'Admin menilai esai secara manual, lalu melihat rekap nilai dan detail jawaban.',
]);

section('3. Panduan Admin');
sub('3.1 Login Admin');
numbered(['Buka halaman /admin/login.', 'Masukkan email dan password admin.', 'Setelah berhasil, admin diarahkan ke dashboard.']);
sub('3.2 Dashboard');
bullets(['Jumlah paket ujian.', 'Jumlah soal aktif.', 'Jumlah token dibuat.', 'Jumlah sesi siswa.', 'Jumlah esai yang menunggu koreksi.', 'Aktivitas ujian terbaru.']);
sub('3.3 Membuat Paket Ujian');
numbered(['Buka menu Paket Ujian.', 'Isi nama ujian, jenjang, kelas, mata pelajaran, durasi, jadwal mulai, dan jadwal selesai.', 'Pilih status Aktif agar siswa bisa memakai token.', 'Aktifkan Tampilkan hasil ke siswa jika nilai boleh langsung terlihat setelah ujian.', 'Klik Simpan paket.']);
sub('3.4 Membuat Stimulus');
paragraph('Stimulus digunakan untuk bacaan, data, grafik, tabel, atau gambar yang dipakai beberapa soal sekaligus.');
numbered(['Buka menu Soal.', 'Pilih paket ujian aktif untuk input soal.', 'Isi judul stimulus, tipe stimulus, dan konten bacaan atau upload gambar.', 'Klik Simpan stimulus.']);
sub('3.5 Membuat Soal');
numbered(['Buka menu Soal.', 'Pilih nomor soal dan tipe soal: pilihan ganda atau esai.', 'Pilih stimulus jika soal memakai bacaan/gambar yang sama.', 'Tulis pertanyaan dan upload gambar soal jika dibutuhkan.', 'Untuk pilihan ganda, isi opsi A sampai E dan pilih kunci jawaban.', 'Upload gambar pada opsi jika jawaban berupa gambar.', 'Isi bobot nilai dan pembahasan opsional.', 'Klik Simpan soal.']);
sub('3.6 Membuat Token');
numbered(['Buka menu Token.', 'Pilih paket ujian.', 'Isi jumlah token yang ingin dibuat.', 'Isi waktu kedaluwarsa jika diperlukan.', 'Klik Buat token.', 'Berikan token kepada siswa yang sudah membayar.']);
paragraph('Catatan: token hanya bisa dipakai satu kali. Setelah siswa mulai ujian, token berubah menjadi in progress. Setelah submit, token berubah menjadi used.');
sub('3.7 Melihat Rekap Nilai');
bullets(['Total nilai.', 'Nilai pilihan ganda.', 'Nilai esai.', 'Jumlah benar, salah, dan kosong.', 'Durasi pengerjaan.', 'Waktu mulai dan selesai.', 'Status lulus atau belum lulus.', 'Detail jawaban per soal.']);
sub('3.8 Menilai Esai');
numbered(['Buka menu Hasil.', 'Klik Lihat detail pada hasil siswa.', 'Cari baris soal bertipe esai.', 'Isi skor esai pada kolom skor.', 'Klik Simpan.', 'Total nilai akan diperbarui berdasarkan nilai pilihan ganda + nilai esai.']);

section('4. Panduan Siswa');
sub('4.1 Masuk Ujian');
numbered(['Buka halaman /ujian.', 'Masukkan token dari admin.', 'Isi nama lengkap, kelas, nomor HP, dan asal sekolah.', 'Klik Mulai ujian.']);
sub('4.2 Mengerjakan Ujian');
bullets(['Timer muncul di bagian atas ruang ujian.', 'Siswa dapat berpindah soal menggunakan tombol sebelumnya, berikutnya, atau navigasi nomor soal.', 'Jawaban pilihan ganda tersimpan otomatis saat dipilih.', 'Jawaban esai tersimpan otomatis saat diketik.', 'Tombol ragu-ragu dapat dipakai untuk menandai soal.', 'Jika waktu habis, sistem melakukan auto-submit.']);
sub('4.3 Selesai Ujian');
numbered(['Klik Submit jika sudah selesai.', 'Konfirmasi submit ujian.', 'Jika admin mengaktifkan hasil siswa, nilai akan tampil setelah submit.', 'Jika ada esai, nilai akhir menunggu koreksi admin.']);

section('5. Data Dummy Yang Tersedia');
simpleTable(['Jenis Data', 'Isi'], [
  ['Akun admin', 'admin@mbc.test / password'],
  ['Paket ujian', 'Try Out CBT SD Matematika; Try Out CBT SMP IPA; Tes Diagnostik SD Bahasa Indonesia'],
  ['Soal', '17 soal campuran pilihan ganda dan esai'],
  ['Stimulus', '5 stimulus bacaan/data'],
  ['Token demo', 'DEMO-TEST-2026; SD-MTK-0001; SD-MTK-0002; SMP-IPA-0001; SMP-IPA-0002; SD-BINDO-0001; SD-BINDO-0002'],
  ['Hasil dummy', '2 hasil siswa untuk contoh dashboard dan rekap nilai'],
]);

section('6. Tips Operasional');
bullets(['Pastikan status paket ujian adalah Aktif sebelum token diberikan ke siswa.', 'Gunakan stimulus untuk bacaan panjang agar soal lebih mudah dikelola.', 'Gunakan token berbeda untuk setiap siswa agar riwayat pengerjaan tidak bercampur.', 'Periksa menu Hasil setelah ujian selesai, terutama jika ada soal esai.', 'Jangan memberikan ulang token yang statusnya sudah used atau in progress.']);

section('7. Fitur Lanjutan Yang Disarankan');
bullets(['Export rekap nilai ke Excel.', 'Import soal massal dari Excel + ZIP gambar.', 'Ranking siswa per paket ujian.', 'Analisis nilai per mata pelajaran atau kategori soal.', 'Integrasi WhatsApp untuk pengiriman token.', 'Pembayaran otomatis dengan payment gateway.']);

const pageCount = doc.bufferedPageRange().count;
for (let i = 0; i < pageCount; i += 1) {
  doc.switchToPage(i);
  doc.font('Helvetica').fontSize(8).fillColor(colors.zinc500).text(
    `MBC CBT Online - Panduan Pemakaian | Halaman ${i + 1}`,
    doc.page.margins.left,
    doc.page.height - 28,
    { align: 'center', width: doc.page.width - doc.page.margins.left - doc.page.margins.right },
  );
}

doc.end();

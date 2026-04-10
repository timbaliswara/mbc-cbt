<?php

use Livewire\Component;

new class extends Component
{
    public string $tab = 'admin';

    public function show(string $tab): void
    {
        $this->tab = in_array($tab, ['admin', 'student', 'faq'], true) ? $tab : 'admin';
    }
};
?>

<div class="space-y-6">
    <section class="hero-panel rounded-md p-6 shadow-2xl shadow-emerald-950/10">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-3xl">
                <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-50/75">Panduan TIM MBC</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">Alur kerja admin dan peserta dibuat sederhana.</h2>
                <p class="mt-3 text-sm leading-6 text-emerald-50/75">Halaman ini bisa dipakai TIM MBC sebagai pegangan saat menyiapkan ujian, membagikan token, mendampingi peserta, dan membaca hasil.</p>
            </div>
            <div class="rounded-md border border-white/15 bg-white/10 p-4 text-sm text-emerald-50/80 backdrop-blur">
                <p class="font-semibold text-white">Akun awal admin</p>
                <p class="mt-1">admin@mbc.test / password</p>
            </div>
        </div>
    </section>

    <div class="surface rounded-md p-2">
        <div class="grid gap-2 sm:grid-cols-3">
            <button wire:click="show('admin')" @class([
                'rounded-md px-4 py-3 text-sm font-semibold transition',
                'bg-emerald-700 text-white shadow-sm' => $tab === 'admin',
                'text-zinc-600 hover:bg-zinc-100' => $tab !== 'admin',
            ])>Panduan Admin</button>
            <button wire:click="show('student')" @class([
                'rounded-md px-4 py-3 text-sm font-semibold transition',
                'bg-emerald-700 text-white shadow-sm' => $tab === 'student',
                'text-zinc-600 hover:bg-zinc-100' => $tab !== 'student',
            ])>Panduan Peserta</button>
            <button wire:click="show('faq')" @class([
                'rounded-md px-4 py-3 text-sm font-semibold transition',
                'bg-emerald-700 text-white shadow-sm' => $tab === 'faq',
                'text-zinc-600 hover:bg-zinc-100' => $tab !== 'faq',
            ])>FAQ & Tips</button>
        </div>
    </div>

    @if ($tab === 'admin')
        <section class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <div class="surface rounded-md p-6">
                <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-700">Alur Admin</p>
                <h3 class="mt-3 text-2xl font-semibold tracking-tight text-zinc-950">Dari paket ujian sampai rekap nilai.</h3>
                <p class="mt-3 text-sm leading-6 text-zinc-600">TIM MBC menyiapkan paket, soal, token, lalu mengecek hasil. Pembayaran tetap manual agar admin bisa memastikan peserta yang masuk sudah sesuai.</p>
                <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    Pastikan status paket ujian sudah <strong>Aktif</strong> sebelum token dibagikan ke peserta.
                </div>
            </div>

            <div class="space-y-4">
                @foreach ([
                    ['title' => '1. Login admin', 'body' => 'Buka /admin/login, lalu masuk dengan akun admin TIM MBC. Setelah itu admin akan diarahkan ke ruang pantau.'],
                    ['title' => '2. Buat paket ujian', 'body' => 'Masuk ke menu Paket Ujian. Isi nama ujian, jenjang, kelas, mapel, jadwal, durasi, passing grade, dan status. Pilih Aktif jika paket sudah siap dipakai peserta.'],
                    ['title' => '3. Siapkan stimulus', 'body' => 'Masuk ke menu Soal. Pilih paket ujian, lalu tambahkan stimulus jika ada bacaan, grafik, tabel, atau gambar yang dipakai untuk beberapa soal.'],
                    ['title' => '4. Input soal', 'body' => 'Pilih tipe pilihan ganda atau esai. Tulis pertanyaan, unggah gambar jika perlu, isi opsi A-E, pilih kunci jawaban, dan tentukan bobot nilai.'],
                    ['title' => '5. Buat token peserta', 'body' => 'Masuk ke menu Token. Pilih paket ujian, isi jumlah token, atur kedaluwarsa bila perlu, lalu berikan token ke peserta yang pembayarannya sudah dikonfirmasi.'],
                    ['title' => '6. Pantau dan nilai', 'body' => 'Masuk ke menu Hasil. TIM MBC bisa melihat nilai, benar/salah/kosong, durasi, detail jawaban, dan mengisi skor esai bila ada.'],
                ] as $item)
                    <div class="surface rounded-md p-5">
                        <h4 class="font-semibold text-zinc-950">{{ $item['title'] }}</h4>
                        <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($tab === 'student')
        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="surface rounded-md p-6">
                <p class="text-sm font-medium uppercase tracking-[0.16em] text-emerald-700">Panduan Peserta</p>
                <h3 class="mt-3 text-2xl font-semibold tracking-tight text-zinc-950">Cara peserta mengikuti tes MBC.</h3>
                <div class="mt-6 grid gap-4">
                    @foreach ([
                        ['title' => '1. Buka portal ujian', 'body' => 'Peserta membuka halaman /ujian. Di sana peserta mengisi token dan data diri sebelum masuk ke ruang ujian.'],
                        ['title' => '2. Masukkan token', 'body' => 'Peserta memasukkan token dari TIM MBC. Token hanya berlaku satu kali dan harus sesuai dengan paket ujian yang aktif.'],
                        ['title' => '3. Isi data diri', 'body' => 'Isi nama lengkap, kelas, nomor HP, dan asal sekolah. Data ini membantu TIM MBC membaca rekap nilai dengan lebih rapi.'],
                        ['title' => '4. Kerjakan soal', 'body' => 'Peserta menjawab soal pilihan ganda atau esai. Jawaban disimpan saat peserta berpindah soal atau mengumpulkan ujian.'],
                        ['title' => '5. Gunakan navigasi soal', 'body' => 'Peserta bisa pindah soal lewat tombol sebelumnya/berikutnya atau nomor soal. Tombol ragu-ragu bisa dipakai untuk menandai soal yang ingin dicek lagi.'],
                        ['title' => '6. Kumpulkan ujian', 'body' => 'Jika sudah selesai dan tidak ada jawaban kosong, klik Kumpulkan ujian lalu konfirmasi. Jika waktu habis, sistem tetap mengumpulkan jawaban otomatis.'],
                    ] as $item)
                        <div class="rounded-md border border-zinc-200 bg-white/80 p-4">
                            <h4 class="font-semibold text-zinc-950">{{ $item['title'] }}</h4>
                            <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $item['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="space-y-4">
                <div class="hero-panel rounded-md p-5">
                    <p class="text-sm font-semibold text-emerald-50/80">Token demo</p>
                    <div class="mt-3 space-y-2 font-mono text-sm text-white">
                        <p>DEMO-TEST-2026</p>
                        <p>SD-MTK-0001</p>
                        <p>SMP-IPA-0001</p>
                        <p>SD-BINDO-0001</p>
                    </div>
                </div>
                <div class="surface rounded-md p-5">
                    <h4 class="font-semibold text-zinc-950">Catatan untuk pendamping TIM MBC</h4>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-zinc-600">
                        <li>Pastikan token belum pernah dipakai.</li>
                        <li>Pastikan peserta memakai koneksi stabil.</li>
                        <li>Jangan refresh halaman saat upload/jawaban belum stabil.</li>
                        <li>Jika ada esai, hasil akhir menunggu koreksi dari TIM MBC.</li>
                    </ul>
                </div>
            </aside>
        </section>
    @endif

    @if ($tab === 'faq')
        <section class="grid gap-4 lg:grid-cols-2">
            @foreach ([
                ['q' => 'Token tidak bisa dipakai?', 'a' => 'Cek status token di menu Token. Token harus berstatus unused, belum kedaluwarsa, dan paket ujiannya sudah Aktif.'],
                ['q' => 'Nilai esai belum masuk?', 'a' => 'Masuk ke menu Hasil, buka detail peserta, isi skor esai, lalu klik Simpan.'],
                ['q' => 'Hasil tidak tampil ke peserta?', 'a' => 'Cek pengaturan paket ujian. Aktifkan Tampilkan hasil ke siswa jika TIM MBC memang ingin peserta langsung melihat nilai.'],
                ['q' => 'Bagaimana melihat jawaban per soal?', 'a' => 'Buka menu Hasil, pilih peserta, lalu klik Detail. Admin bisa melihat jawaban siswa, kunci, status, dan skor per soal.'],
                ['q' => 'Kapan token diberikan?', 'a' => 'Token diberikan setelah pembayaran manual diverifikasi TIM MBC. Satu token dipakai untuk satu peserta dan satu paket ujian.'],
                ['q' => 'Apa yang terjadi jika waktu habis?', 'a' => 'Sistem otomatis mengumpulkan ujian. Nilai pilihan ganda dihitung otomatis, sedangkan esai menunggu koreksi TIM MBC.'],
            ] as $item)
                <div class="surface rounded-md p-5">
                    <h4 class="font-semibold text-zinc-950">{{ $item['q'] }}</h4>
                    <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $item['a'] }}</p>
                </div>
            @endforeach
        </section>
    @endif
</div>

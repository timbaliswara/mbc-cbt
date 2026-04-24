<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\ExamToken;
use App\Models\Question;
use App\Models\Stimulus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@mbc.test'],
            ['name' => 'Admin MBC', 'password' => 'password', 'role' => 'admin'],
        );

        $indonesia = $this->exam([
            'title' => 'TKA SD Bahasa Indonesia - Paket B',
            'level' => 'SD',
            'grade' => '6',
            'subject' => 'Bahasa Indonesia',
            'description' => 'Paket TKA SD Bahasa Indonesia berdasarkan PDF Paket B.',
            'duration_minutes' => 90,
            'passing_grade' => 75,
            'show_result_to_student' => true,
        ]);

        $uv = $this->stimulus($indonesia, 'Bahaya Sinar Ultraviolet bagi Kesehatan', <<<'TEXT'
Sinar ultraviolet (UV) merupakan bagian dari sinar matahari yang tidak dapat dilihat oleh mata manusia. Sinar ini sebenarnya bermanfaat dalam jumlah tertentu, misalnya membantu pembentukan vitamin D dalam tubuh. Namun, paparan sinar UV yang berlebihan dapat menimbulkan berbagai masalah kesehatan, terutama pada kulit dan mata.

Paparan sinar UV secara terus-menerus dapat menyebabkan kulit menjadi terbakar, iritasi, hingga mempercepat penuaan dini. Selain itu, sinar UV juga berisiko merusak mata jika seseorang terlalu lama berada di bawah sinar matahari tanpa perlindungan. Risiko tersebut akan semakin besar jika seseorang sering beraktivitas di luar ruangan pada siang hari.

Oleh karena itu, kita perlu melindungi diri dari bahaya sinar UV dengan cara yang tepat, seperti menggunakan topi, pakaian tertutup, kacamata hitam, atau tabir surya. Dengan perlindungan yang baik, manfaat sinar matahari tetap bisa diperoleh tanpa membahayakan kesehatan.
TEXT);

        $teknologi = $this->stimulus($indonesia, 'Teknologi dalam Kehidupan Sehari-hari', <<<'TEXT'
Perkembangan teknologi telah mengubah cara manusia beraktivitas. Berbagai pekerjaan yang dahulu membutuhkan waktu lama kini dapat diselesaikan dengan lebih cepat dan efisien. Teknologi juga memudahkan manusia dalam berkomunikasi dan memperoleh informasi.

Namun, penggunaan teknologi perlu disikapi secara bijak. Ketergantungan yang berlebihan dapat mengurangi kemampuan berpikir mandiri dan interaksi sosial. Oleh karena itu, teknologi seharusnya dimanfaatkan sebagai alat bantu, bukan sebagai pengganti peran manusia sepenuhnya.

Dengan sikap yang tepat, teknologi dapat memberikan manfaat besar bagi pendidikan dan kehidupan sehari-hari. Pengguna yang cerdas adalah mereka yang mampu memilih dan menggunakan teknologi sesuai kebutuhan.
TEXT);

        $jamTua = $this->stimulus($indonesia, 'Jejak Waktu di Jam Tua', <<<'TEXT'
Di sudut rumah nenek, terdapat sebuah jam tua yang sudah lama berhenti berdetak. Banyak orang menganggapnya sekadar barang usang, tetapi bagi Rendi, jam itu selalu menarik perhatiannya. Ia sering membayangkan berapa banyak waktu yang telah dilewati jam tersebut tanpa pernah benar-benar dihargai.

Suatu sore, Rendi mencoba membersihkan jam itu dengan hati-hati. Ia tidak berharap jam itu kembali berfungsi, tetapi proses membersihkannya membuat Rendi merasa tenang. Dalam diam, ia belajar bahwa merawat sesuatu membutuhkan kesabaran dan perhatian.

Sejak hari itu, Rendi mulai mengubah kebiasaannya. Ia tidak lagi terburu-buru dalam mengerjakan tugas dan mulai menghargai proses belajar. Baginya, waktu bukan lagi sesuatu yang harus dikejar, melainkan dipahami.

Jam tua itu memang tidak pernah berdetak kembali, tetapi telah meninggalkan pelajaran berharga bagi Rendi. Ia menyadari bahwa perubahan tidak selalu datang dari benda yang sempurna, melainkan dari kesadaran diri sendiri.
TEXT);

        $kuraKura = $this->stimulus($indonesia, 'Kura-kura dan Sungai Keruh', <<<'TEXT'
Di tepi hutan, hiduplah seekor kura-kura yang dikenal tekun dan sabar. Setiap hari ia membersihkan tepian sungai agar air tetap mengalir dengan baik. Sementara itu, seekor monyet sering mengejek kura-kura karena bergerak lambat dan dianggap tidak berguna.

Suatu hari, hujan deras membuat sungai menjadi keruh dan hampir meluap. Banyak hewan panik dan berlari tanpa arah. Kura-kura tetap tenang dan mengajak hewan lain membersihkan ranting yang menyumbat aliran air. Ia percaya bahwa sedikit demi sedikit, lama-lama menjadi bukit.

Monyet yang awalnya menertawakan kura-kura akhirnya ikut membantu. Berkat kerja sama mereka, air sungai kembali surut dan hutan terhindar dari banjir. Monyet pun menyadari bahwa sikap terburu-buru tidak selalu membawa hasil baik.

Sejak saat itu, kura-kura dihormati oleh hewan lain. Ia mengajarkan bahwa ketekunan dan kesabaran mampu mengatasi masalah besar, meskipun dilakukan melalui langkah-langkah kecil.
TEXT);

        $sim = $this->stimulus($indonesia, 'Cara Membuat SIM (Surat Izin Mengemudi)', <<<'TEXT'
SIM adalah surat resmi yang wajib dimiliki oleh pengendara kendaraan bermotor. SIM berfungsi sebagai bukti bahwa seseorang telah memenuhi syarat dan mampu mengemudi sesuai aturan. Pembuatan SIM dilakukan melalui kepolisian.

Langkah pertama, pemohon menyiapkan persyaratan seperti KTP, surat keterangan sehat, dan lulus tes psikologi. Setelah itu, pemohon mendaftar secara langsung di kantor Satpas atau melalui layanan pendaftaran daring. Data diri harus diisi dengan benar agar proses berjalan lancar.

Tahap selanjutnya adalah mengikuti ujian teori dan ujian praktik. Ujian teori bertujuan menguji pemahaman tentang rambu dan aturan lalu lintas, sedangkan ujian praktik menilai kemampuan mengemudi. Jika seluruh tahapan dilalui dengan baik, pemohon akan memperoleh SIM.
TEXT);

        $dialog = $this->stimulus($indonesia, 'Dialog Adit dan Naya', <<<'TEXT'
Di ruang kelas yang hampir kosong, Adit masih duduk menatap hasil ujiannya.
"Aku belajar lebih lama dari biasanya, tapi hasilnya tetap biasa saja," katanya lirih kepada Naya.
Naya tidak langsung menjawab. "Mungkin kamu terlalu fokus pada hasil, bukan pada cara memahami pelajarannya," ujarnya setelah beberapa saat.

Adit mengerutkan dahi. "Jadi, semua usahaku sia-sia?"
"Tidak," jawab Naya pelan. "Usaha itu penting, tapi tanpa refleksi, usaha bisa berjalan di tempat."

Tak lama kemudian, Pak Arman menghampiri mereka. "Nilai bukan satu-satunya ukuran belajar," katanya. "Yang lebih penting adalah kesadaran untuk memperbaiki diri."

Adit terdiam. Ia mulai menyadari bahwa kegagalannya bukan akhir, melainkan tanda bahwa ia perlu mengubah cara berpikirnya.
TEXT);

        $surat = $this->stimulus($indonesia, 'Surat Pribadi Dina', <<<'TEXT'
Bandung, 12 Agustus 2025
Untuk Sahabatku, Rani

Apa kabarmu di kampung? Semoga kamu dan keluargamu selalu sehat. Sudah lama rasanya kita tidak saling bertukar cerita sejak aku pindah sekolah. Awalnya aku merasa sulit beradaptasi di tempat baru ini, terutama karena suasana belajar yang berbeda dan teman-teman yang belum terlalu akrab.

Namun, perlahan aku mulai belajar menyesuaikan diri. Aku mengikuti beberapa kegiatan sekolah agar lebih mengenal lingkungan sekitar. Dari pengalaman ini, aku sadar bahwa keberanian untuk mencoba hal baru sangat penting, meskipun terasa menakutkan di awal.

Aku berharap suatu hari nanti kita bisa bertemu dan berbagi cerita secara langsung. Jangan lupa balas surat ini, ya. Ceritakan juga pengalamanmu di sana.

Salam hangat,
Dina
TEXT);

        $puisi = $this->stimulus($indonesia, 'Jejak Pagi', <<<'TEXT'
Pagi menata cahaya di jendela sunyi
Harap tumbuh di sela waktu yang rapuh
Langkah kecil mencari arah
Di antara ragu dan percaya

Angin membawa bisik yang tertahan
Menyentuh hati yang belajar teguh
Tak semua luka harus disesali
Sebagian mengajarkan makna

Saat mentari naik perlahan
Aku memahami arti menunggu
Bahwa jatuh bukan akhir cerita
Melainkan awal untuk bangkit
TEXT);

        $proklamasi = $this->stimulus($indonesia, 'Peristiwa Proklamasi Kemerdekaan Indonesia', <<<'TEXT'
Peristiwa Proklamasi Kemerdekaan Indonesia pada 17 Agustus 1945 merupakan puncak perjuangan bangsa Indonesia dalam melepaskan diri dari penjajahan. Setelah melalui tekanan politik dan kekosongan kekuasaan pasca-kekalahan Jepang, para tokoh bangsa memanfaatkan momentum tersebut untuk menyatakan kemerdekaan secara tegas kepada dunia.

Perumusan naskah proklamasi dilakukan dengan penuh kehati-hatian di rumah Laksamana Maeda. Ir. Soekarno, Drs. Mohammad Hatta, dan Achmad Soebardjo berdiskusi intensif untuk merangkai kalimat yang singkat, padat, namun sarat makna. Setiap kata dipilih dengan pertimbangan agar mencerminkan kehendak seluruh rakyat Indonesia.

Pembacaan teks proklamasi di Jalan Pegangsaan Timur No. 56 menjadi titik awal lahirnya negara Indonesia yang merdeka dan berdaulat. Peristiwa tersebut bukan sekadar seremonial, melainkan penegasan sikap bangsa Indonesia untuk menentukan nasibnya sendiri tanpa campur tangan pihak asing.
TEXT);

        $fotosintesis = $this->stimulus($indonesia, 'Proses Fotosintesis pada Tanaman', <<<'TEXT'
Fotosintesis merupakan proses penting yang dilakukan tumbuhan hijau untuk menghasilkan makanan. Proses ini terjadi di bagian daun yang mengandung klorofil. Dengan bantuan cahaya matahari, tumbuhan mampu mengolah zat-zat sederhana menjadi sumber energi yang dibutuhkan untuk tumbuh dan berkembang.

Dalam proses fotosintesis, tumbuhan menyerap air melalui akar dan karbon dioksida melalui stomata pada daun. Energi cahaya matahari kemudian digunakan untuk mengubah kedua zat tersebut menjadi glukosa dan oksigen. Glukosa dimanfaatkan sebagai makanan, sedangkan oksigen dilepaskan ke udara.

Fotosintesis tidak hanya penting bagi tumbuhan, tetapi juga bagi makhluk hidup lainnya. Oksigen yang dihasilkan sangat dibutuhkan oleh manusia dan hewan untuk bernapas. Oleh karena itu, keberadaan tumbuhan berperan besar dalam menjaga keseimbangan lingkungan.
TEXT);

        $this->multipleChoice($indonesia, 1, 'Berdasarkan bacaan, tindakan paling tepat saat beraktivitas di luar ruangan pada siang hari adalah ...', [
            'A' => 'Beraktivitas lebih lama agar terbiasa.',
            'B' => 'Menghindari sinar matahari sepenuhnya.',
            'C' => 'Tidak memedulikan dampak sinar matahari.',
            'D' => 'Menggunakan perlindungan diri dari sinar UV.',
        ], 'D', $uv);
        $this->multiSelect($indonesia, 2, 'Sinar ultraviolet memiliki beberapa manfaat. Pilih semua pernyataan yang benar.', [
            'A' => 'Sinar ultraviolet bermanfaat bagi tubuh karena membantu pembentukan vitamin D.',
            'B' => 'Manfaat sinar ultraviolet dapat diperoleh tanpa risiko jika paparan dilakukan secara berlebihan.',
            'C' => 'Dengan perlindungan yang tepat, manfaat sinar matahari tetap bisa diperoleh tanpa membahayakan kesehatan.',
        ], ['A', 'C'], $uv);
        $this->multiSelect($indonesia, 3, 'Berdasarkan teks, pilih semua pernyataan yang benar.', [
            'A' => 'Sinar ultraviolet dapat memberikan manfaat dan juga menimbulkan bahaya bagi manusia.',
            'B' => 'Paparan sinar UV dalam waktu lama dapat berdampak buruk bagi kulit dan mata.',
            'C' => 'Sinar ultraviolet harus dihindari sepenuhnya karena pasti membahayakan kulit.',
        ], ['A', 'B'], $uv);

        $this->multipleChoice($indonesia, 4, 'Makna kata "efisien" pada paragraf pertama adalah ...', [
            'A' => 'Menggunakan banyak tenaga untuk melakukan suatu pekerjaan.',
            'B' => 'Menghasilkan sesuatu dengan waktu dan usaha yang tepat.',
            'C' => 'Dilakukan tanpa perencanaan yang matang.',
            'D' => 'Dikerjakan secara cepat dan terburu-buru.',
        ], 'B', $teknologi);
        $this->multiSelect($indonesia, 5, 'Ide pokok yang tepat adalah ... Pilih semua pernyataan yang benar.', [
            'A' => 'Teknologi membantu manusia menyelesaikan pekerjaan dengan lebih cepat dan tepat.',
            'B' => 'Teknologi sebaiknya digunakan secara bijak agar tidak menimbulkan dampak negatif.',
            'C' => 'Teknologi membuat manusia tidak perlu berpikir dan berinteraksi sosial.',
        ], ['A', 'B'], $teknologi);
        $this->multiSelect($indonesia, 6, 'Teks tersebut memuat unsur kebahasaan. Pilih semua pernyataan yang tepat.', [
            'A' => 'Kata "memudahkan" menggunakan imbuhan me- yang menunjukkan tindakan aktif.',
            'B' => 'Kata "dimanfaatkan" dengan awalan di- menunjukkan subjek melakukan tindakan.',
            'C' => 'Kata "pengguna" dengan awalan pe- membentuk kata benda.',
        ], ['A'], $teknologi);

        $this->multipleChoice($indonesia, 7, 'Ringkasan yang paling tepat untuk cerita tersebut adalah ...', [
            'A' => 'Rendi belajar menghargai waktu dan proses melalui pengalaman sederhana dengan jam tua.',
            'B' => 'Rendi memperbaiki jam tua milik nenek hingga kembali berfungsi.',
            'C' => 'Jam tua di rumah nenek memiliki nilai sejarah yang tinggi.',
            'D' => 'Rendi merasa sedih karena jam tua tidak dapat diperbaiki.',
        ], 'A', $jamTua);
        $this->multiSelect($indonesia, 8, 'Nilai yang terdapat dalam cerita adalah ... Pilih semua pernyataan yang benar.', [
            'A' => 'Menghargai proses sama pentingnya dengan hasil.',
            'B' => 'Kesabaran dapat menumbuhkan perubahan sikap.',
            'C' => 'Keberhasilan hanya ditentukan oleh hasil akhir.',
        ], ['A', 'B'], $jamTua);
        $this->multiSelect($indonesia, 9, 'Makna kata "proses" yang sesuai dengan konteks cerita adalah ... Pilih semua yang benar.', [
            'A' => 'Hasil akhir yang ingin dicapai.',
            'B' => 'Tahapan usaha yang dijalani secara bertahap.',
            'C' => 'Perjalanan yang memerlukan waktu dan kesabaran.',
        ], ['B', 'C'], $jamTua);

        $this->multipleChoice($indonesia, 10, 'Maksud peribahasa yang dicetak miring dalam cerita tersebut adalah ...', [
            'A' => 'Masalah besar harus dihindari.',
            'B' => 'Pekerjaan sebaiknya dilakukan dengan cepat.',
            'C' => 'Pekerjaan berat tidak perlu diselesaikan dengan terburu-buru.',
            'D' => 'Usaha kecil yang dilakukan terus-menerus dapat menghasilkan sesuatu yang besar.',
        ], 'D', $kuraKura);
        $this->multiSelect($indonesia, 11, 'Apa saja keteladanan tokoh dalam fabel tersebut? Pilih semua pernyataan yang sesuai.', [
            'A' => 'Monyet sejak awal bersikap menghargai kura-kura.',
            'B' => 'Kura-kura menunjukkan sikap sabar dan tekun.',
            'C' => 'Cerita mengajarkan pentingnya kerja sama.',
        ], ['B', 'C'], $kuraKura);
        $this->multiSelect($indonesia, 12, 'Pernyataan yang sesuai dengan isi fabel adalah ... Pilih semua pernyataan yang benar.', [
            'A' => 'Semua hewan memilih meninggalkan sungai tanpa berbuat apa-apa.',
            'B' => 'Kura-kura tetap tenang saat sungai hampir meluap.',
            'C' => 'Monyet akhirnya menyadari kesalahannya.',
        ], ['B', 'C'], $kuraKura);

        $this->multiSelect($indonesia, 13, 'Berdasarkan isi teks, pilih semua pernyataan yang benar.', [
            'A' => 'SIM merupakan surat resmi yang wajib dimiliki pengendara kendaraan bermotor.',
            'B' => 'Pemohon SIM perlu menyiapkan persyaratan yang banyak.',
            'C' => 'Ujian praktik dilakukan untuk menilai kemampuan mengemudi.',
        ], ['A', 'C'], $sim);
        $this->multipleChoice($indonesia, 14, 'Makna imbuhan me- pada kata "mengemudi" adalah ...', [
            'A' => 'Menunjukkan hasil dari suatu pekerjaan.',
            'B' => 'Menunjukkan peristiwa yang terjadi tanpa pelaku.',
            'C' => 'Menunjukkan tindakan aktif yang dilakukan oleh subjek.',
            'D' => 'Menunjukkan keadaan yang sedang dialami oleh subjek.',
        ], 'C', $sim);
        $this->multiSelect($indonesia, 15, 'Berdasarkan teks, manakah pernyataan berikut yang benar? Pilih semua yang tepat.', [
            'A' => 'Kalimat "SIM adalah surat resmi yang wajib dimiliki oleh pengendara kendaraan bermotor" efektif karena jelas dan tidak bertele-tele.',
            'B' => 'Kalimat "Pemohon menyiapkan persyaratan seperti KTP, surat sehat, dan tes psikologi" tidak efektif karena informasinya tidak runtut.',
            'C' => 'Kalimat "Jika seluruh tahapan dilalui dengan baik, pemohon akan memperoleh SIM" efektif karena hubungan sebab-akibatnya jelas.',
        ], ['A', 'C'], $sim);

        $this->multipleChoice($indonesia, 16, 'Sinopsis yang paling tepat untuk kutipan cerita tersebut adalah ...', [
            'A' => 'Adit menyalahkan soal ujian yang terlalu sulit.',
            'B' => 'Adit berhenti belajar karena merasa usahanya sia-sia.',
            'C' => 'Adit kecewa karena nilainya rendah meskipun sudah belajar cukup lama.',
            'D' => 'Adit mendapatkan nasihat dari Naya dan gurunya untuk menilai ulang cara belajarnya.',
        ], 'D', $dialog);
        $this->multiSelect($indonesia, 17, 'Tentukan pernyataan yang sesuai dengan realitas kehidupan sehari-hari. Pilih semua yang sesuai.', [
            'A' => 'Usaha tanpa evaluasi dapat membuat seseorang tidak berkembang.',
            'B' => 'Hasil belajar selalu mencerminkan kemampuan seseorang secara utuh.',
            'C' => 'Nasihat dari orang lain dapat membantu seseorang melihat masalah dari sudut pandang berbeda.',
        ], ['A', 'C'], $dialog);
        $this->multiSelect($indonesia, 18, 'Tokoh dalam cerita tersebut memiliki watak tertentu. Pilih semua pernyataan yang sesuai.', [
            'A' => 'Naya berpikir kritis dan impulsif.',
            'B' => 'Adit bersikap terbuka terhadap nasihat.',
            'C' => 'Pak Arman acuh terhadap perkembangan siswa.',
        ], ['A', 'B'], $dialog);

        $this->multipleChoice($indonesia, 19, 'Tujuan utama Dina menulis surat tersebut adalah ...', [
            'A' => 'Menceritakan pengalaman pribadi sekaligus menjaga hubungan persahabatan.',
            'B' => 'Memberi nasihat kepada Rani agar berani pindah sekolah.',
            'C' => 'Mengeluhkan kesulitan belajar di sekolah baru.',
            'D' => 'Meminta Rani datang ke Bandung.',
        ], 'A', $surat);
        $this->multiSelect($indonesia, 20, 'Berdasarkan isi surat tersebut, pilih semua pernyataan yang tepat.', [
            'A' => 'Dina menyesal telah pindah sekolah.',
            'B' => 'Dina mengalami kesulitan beradaptasi di lingkungan barunya.',
            'C' => 'Dina belajar bahwa mencoba hal baru membutuhkan keberanian.',
        ], ['B', 'C'], $surat);
        $this->multipleChoice($indonesia, 21, 'Manakah bukti yang paling kuat bahwa teks tersebut merupakan surat pribadi?', [
            'A' => 'Ditujukan kepada lembaga tertentu.',
            'B' => 'Memuat informasi umum untuk banyak orang.',
            'C' => 'Mengungkapkan perasaan dan pengalaman pribadi penulis.',
            'D' => 'Menggunakan bahasa resmi dan baku sesuai KBBI.',
        ], 'C', $surat);

        $this->multipleChoice($indonesia, 22, 'Makna kata "rapuh" pada larik "Harap tumbuh di sela waktu yang rapuh" adalah ...', [
            'A' => 'Mudah pecah secara fisik.',
            'B' => 'Rentan dan tidak stabil.',
            'C' => 'Penuh kekuatan.',
            'D' => 'Sangat lama.',
        ], 'B', $puisi);
        $this->multipleChoice($indonesia, 23, 'Amanat yang paling tepat dari puisi tersebut adalah ...', [
            'A' => 'Luka sebaiknya dilupakan.',
            'B' => 'Hidup harus dijalani tanpa ragu.',
            'C' => 'Menunggu adalah hal yang sia-sia.',
            'D' => 'Setiap kegagalan mengandung pelajaran untuk bangkit.',
        ], 'D', $puisi);
        $this->multiSelect($indonesia, 24, 'Pilih semua pernyataan yang benar tentang puisi tersebut.', [
            'A' => 'Puisi menggunakan bahasa konotatif untuk menyampaikan perasaan.',
            'B' => 'Puisi terdiri atas tiga bait dan setiap bait berisi baris bersajak sama.',
            'C' => 'Puisi lebih menekankan makna dan perasaan daripada alur cerita.',
        ], ['A', 'C'], $puisi);

        $this->multipleChoice($indonesia, 25, 'Kata "sarat" pada kalimat "kalimat yang singkat, padat, namun sarat makna" bermakna ...', [
            'A' => 'Sedikit dan terbatas.',
            'B' => 'Sulit untuk dipahami.',
            'C' => 'Disusun secara terburu-buru.',
            'D' => 'Penuh dan mengandung banyak hal.',
        ], 'D', $proklamasi);
        $this->multiSelect($indonesia, 26, 'Penulisan yang benar berdasarkan teks tersebut adalah ... Pilih semua pernyataan yang tepat.', [
            'A' => 'Kata Proklamasi seharusnya selalu ditulis dengan huruf kecil karena bukan nama diri.',
            'B' => 'Penulisan Ir. dan Drs. menggunakan huruf kapital sudah tepat karena merupakan gelar.',
            'C' => 'Penulisan tanggal 17 Agustus 1945 sudah tepat karena menggunakan huruf kapital pada nama bulan.',
        ], ['B', 'C'], $proklamasi);
        $this->multiSelect($indonesia, 27, 'Kesimpulan dari peristiwa proklamasi dapat dilihat dalam teks. Pilih semua pernyataan yang tepat.', [
            'A' => 'Kemerdekaan Indonesia terjadi secara tiba-tiba tanpa perencanaan tokoh bangsa.',
            'B' => 'Peristiwa Proklamasi menjadi dasar lahirnya negara Indonesia yang berdaulat.',
            'C' => 'Proklamasi merupakan wujud keberanian dan kesadaran bangsa untuk menentukan masa depannya sendiri.',
        ], ['B', 'C'], $proklamasi);

        $this->multipleChoice($indonesia, 28, 'Manakah kelompok kata berikut yang berhubungan langsung dengan proses fotosintesis?', [
            'A' => 'klorofil - cahaya matahari - karbon dioksida',
            'B' => 'manusia - hewan - tumbuhan',
            'C' => 'udara - tanah - air hujan',
            'D' => 'akar - batang - ranting',
        ], 'A', $fotosintesis);
        $this->multiSelect($indonesia, 29, 'Pernyataan yang sesuai dengan proses fotosintesis adalah ... Pilih semua yang benar.', [
            'A' => 'Fotosintesis menghasilkan glukosa dan oksigen.',
            'B' => 'Karbon dioksida diserap tumbuhan melalui stomata pada daun.',
            'C' => 'Fotosintesis hanya bermanfaat bagi tumbuhan dan tidak berdampak pada makhluk hidup lain.',
        ], ['A', 'B'], $fotosintesis);
        $this->multiSelect($indonesia, 30, 'Teks tersebut mengandung kalimat fakta. Pilih semua pernyataan yang tepat.', [
            'A' => 'Fotosintesis terjadi pada daun yang mengandung klorofil.',
            'B' => 'Semua tumbuhan melakukan fotosintesis tanpa memerlukan cahaya matahari.',
            'C' => 'Oksigen hasil fotosintesis dibutuhkan oleh manusia dan hewan.',
        ], ['A', 'C'], $fotosintesis);

        $matematika = $this->exam([
            'title' => 'TKA SD Matematika - Paket B',
            'level' => 'SD',
            'grade' => '6',
            'subject' => 'Matematika',
            'description' => 'Paket TKA SD Matematika berdasarkan PDF Paket B.',
            'duration_minutes' => 100,
            'passing_grade' => 75,
            'show_result_to_student' => true,
        ]);

        $this->multiSelect($matematika, 1, 'Manakah operasi hitung berikut yang benar? Pilih semua pernyataan yang benar.', [
            'A' => '795 : (15 + 250) x 15 = 45',
            'B' => '795 : 15 + 250 x 15 = 3.803',
            'C' => '795 : 15 + 250 x 15 = 4.545',
        ], ['A', 'B']);
        $this->multiSelect($matematika, 2, 'Untuk membuat 6 dekorasi, pilih semua pernyataan yang benar.', [
            'A' => 'Aira membutuhkan 30 balon biru.',
            'B' => 'Aira membutuhkan 45 balon ungu.',
            'C' => 'Aira membutuhkan 54 balon merah muda.',
        ], ['A', 'C']);
        $this->multipleChoice($matematika, 3, 'Sebuah pabrik membuat 50 kotak buku tulis. Setiap kotak berisi 20 buku tulis. Jika 25 kotak sudah diantar ke toko, berapa buku tulis yang masih ada di pabrik?', [
            'A' => '250 buku tulis',
            'B' => '500 buku tulis',
            'C' => '745 buku tulis',
            'D' => '980 buku tulis',
        ], 'B');
        $this->multiSelect($matematika, 4, 'Pak Budi memanen 24 karung padi. Pilih semua pernyataan yang benar.', [
            'A' => 'Satu karung padi dijual seharga Rp200.000.',
            'B' => 'Hasil penjualan padi seluruhnya Rp4.800.000.',
            'C' => 'Total padi yang dipanen Pak Budi adalah 500 kg.',
        ], ['A', 'B']);
        $this->multipleChoice($matematika, 5, 'Hasil dari 0,75 + 40% x 1/2 adalah ...', [
            'A' => '19/20',
            'B' => '15/21',
            'C' => '23/40',
            'D' => '23/80',
        ], 'A');
        $this->multipleChoice($matematika, 6, 'Urutan dari yang terkecil adalah ...', [
            'A' => '2 1/4 ; 125% ; 0,75 ; 16/32',
            'B' => '16/32 ; 0,75 ; 125% ; 2 1/4',
            'C' => '2 1/4 ; 16/32 ; 0,75 ; 125%',
            'D' => '125% ; 0,65 ; 2 1/4 ; 16/32',
        ], 'B');
        $this->multiSelect($matematika, 7, 'Diketahui a = 2 1/2, b = 3 2/3, c = 5/6. Pilih semua pernyataan yang benar.', [
            'A' => 'a + b x c = 5 5/9',
            'B' => 'a + b x c = 5 5/36',
            'C' => 'a x b + c = 10',
        ], ['A', 'C']);
        $this->multipleChoice($matematika, 8, 'Umur ayah 6 windu, sedangkan umur kakak 20 tahun. Berapa rasio umur ayah dan kakak?', [
            'A' => '3 : 1',
            'B' => '3 : 2',
            'C' => '5 : 12',
            'D' => '12 : 5',
        ], 'D');
        $this->multiSelect($matematika, 9, 'Tasya membeli 4 botol minuman. Pilih semua pernyataan yang benar.', [
            'A' => 'Setiap botol berisi kurang dari 1 liter.',
            'B' => 'Jumlah seluruh minuman yang dibeli Tasya adalah 3 liter.',
            'C' => 'Jika Tasya membeli 1 botol lagi, maka jumlah minuman menjadi 4,75 liter.',
        ], ['A', 'B']);
        $this->multipleChoice($matematika, 10, 'Ibu membeli 5 kg beras. Jika sisa beras adalah 20%, berapa kg beras yang telah digunakan?', [
            'A' => '1 kg',
            'B' => '2 kg',
            'C' => '3 kg',
            'D' => '4 kg',
        ], 'D');
        $this->multiSelect($matematika, 11, 'Sebuah sekolah memiliki 60 pensil dan 45 penghapus. Pilih semua pernyataan yang benar.', [
            'A' => 'Jumlah maksimal siswa yang mendapat alat tulis adalah 15 siswa.',
            'B' => 'Setiap siswa akan memperoleh 3 penghapus.',
            'C' => 'Setiap siswa akan memperoleh 5 pensil.',
        ], ['A', 'B']);
        $this->multipleChoice($matematika, 12, 'Alma les melukis setiap seminggu sekali, sedangkan Revi setiap dua minggu sekali. Mereka les bersama pada 20 Agustus. Pada tanggal berapa mereka akan les bersama lagi?', [
            'A' => '27 Agustus',
            'B' => '30 Agustus',
            'C' => '3 September',
            'D' => '4 September',
        ], 'C');
        $this->multipleChoice($matematika, 13, 'Sifat-sifat bangun datar layang-layang ditunjukkan oleh nomor ...', [
            'A' => '(i), (ii), (iii)',
            'B' => '(ii), (iii), (iv)',
            'C' => '(iii), (iv), (v)',
            'D' => '(i), (iii), (v)',
        ], 'B');
        $this->multiSelect($matematika, 14, 'Pilih susunan yang terdiri dari dua belas kubus.', [
            'A' => 'Bangun pertama',
            'B' => 'Bangun kedua',
            'C' => 'Bangun ketiga',
        ], ['B', 'C'], null, 10, 'demo/imports/paket-b/mtk-q14.png');
        $this->multiSelect($matematika, 15, 'Indi membeli pita sepanjang 1,2 meter dan 2,3 meter. Pilih semua pernyataan yang benar.', [
            'A' => 'Total panjang pita yang dibeli Indi adalah 3,5 meter.',
            'B' => 'Sisa pita yang belum digunakan Indi adalah 2,25 cm.',
            'C' => 'Jika pita yang digunakan adalah 1,5 meter, maka sisa pitanya adalah 2 meter.',
        ], ['A', 'C']);
        $this->multiSelect($matematika, 16, 'Di sebuah toko terdapat beras 2,4 kg dan gula 850 gram. Pilih semua pernyataan yang benar.', [
            'A' => 'Berat beras sama dengan 2400 gram.',
            'B' => 'Total berat beras dan gula adalah 3,5 kg.',
            'C' => 'Selisih berat beras dan gula adalah 1,55 kg.',
        ], ['A', 'C']);
        $this->multipleChoice($matematika, 17, 'Sebuah ember memiliki kapasitas 0,02 m3. Ember diisi 15 liter air lalu ditambahkan 3.000 ml. Berapa banyak air yang perlu ditambahkan agar ember penuh?', [
            'A' => '1,5 liter',
            'B' => '2 liter',
            'C' => '2,5 liter',
            'D' => '3 liter',
        ], 'B');
        $this->multiSelect($matematika, 18, 'Usia Kakek Pardi adalah 1 abad kurang 30 tahun, sedangkan Kakek Harno 7 windu lebih 4 tahun. Pilih semua pernyataan yang benar.', [
            'A' => 'Usia Kakek Harno adalah 74 tahun.',
            'B' => 'Kakek Pardi lebih tua daripada Kakek Harno.',
            'C' => 'Selisih usia Kakek Pardi dan Kakek Harno adalah 10 tahun.',
        ], ['B', 'C']);
        $this->multiSelect($matematika, 19, 'Pak Fatan dan Pak Rafif berangkat ke kantor. Pilih semua pernyataan yang benar.', [
            'A' => 'Pak Fatan akan tiba di kantor lebih awal daripada Pak Rafif.',
            'B' => 'Pak Rafif akan tiba di kantor lebih awal daripada Pak Fatan.',
            'C' => 'Pak Fatan dan Pak Rafif akan tiba di kantor pada waktu yang bersamaan.',
        ], ['C']);
        $this->multiSelect($matematika, 20, 'Rasyid pergi ke suatu tempat mengendarai mobil dengan kecepatan rata-rata 42 km/jam. Pilih semua pernyataan yang benar.', [
            'A' => 'Pada pukul 08.00, Rasyid sudah menempuh jarak sejauh 21 km.',
            'B' => 'Dalam waktu dua jam, Rasyid sudah menempuh jarak sejauh 42 km.',
            'C' => 'Jika tempat tujuan Rasyid berjarak 126 km, maka Rasyid akan tiba pukul 10.30.',
        ], ['A', 'C']);
        $this->multipleChoice($matematika, 21, 'Keliling bangun datar pada gambar adalah ...', [
            'A' => '33 cm',
            'B' => '37 cm',
            'C' => '42 cm',
            'D' => '44 cm',
        ], 'C', null, 10, 'demo/imports/paket-b/mtk-q21.png');
        $this->multipleChoice($matematika, 22, 'Luas bangun datar pada gambar adalah ...', [
            'A' => '1.197 cm2',
            'B' => '1.251 cm2',
            'C' => '1.386 cm2',
            'D' => '1.575 cm2',
        ], 'C', null, 10, 'demo/imports/paket-b/mtk-q22.png');
        $this->multipleChoice($matematika, 23, 'Zain memiliki papan kayu berbentuk persegi panjang dengan ukuran panjang 1,2 meter dan lebar 90 cm. Jika dipotong menjadi persegi sisi 30 cm, berapa banyak potongan yang dihasilkan?', [
            'A' => '9 persegi',
            'B' => '12 persegi',
            'C' => '15 persegi',
            'D' => '20 persegi',
        ], 'B');
        $this->multiSelect($matematika, 24, 'Sebuah aquarium berbentuk balok berukuran 40 cm x 30 cm x 35 cm. Pilih semua pernyataan yang benar.', [
            'A' => 'Volume aquarium adalah 42.000 cm3.',
            'B' => 'Dibutuhkan 42 liter air untuk mengisi penuh aquarium.',
            'C' => 'Jika aquarium telah terisi 20 liter air, maka dibutuhkan tambahan 20 liter air untuk mengisi penuh aquarium hingga penuh.',
        ], ['A', 'B']);
        $this->multipleChoice($matematika, 25, 'Bilqis membeli satu kotak jus jeruk dengan ukuran seperti gambar tersebut. Berapakah volumenya?', [
            'A' => '500 ml',
            'B' => '750 ml',
            'C' => '500 liter',
            'D' => '750 liter',
        ], 'B', null, 10, 'demo/imports/paket-b/mtk-q25.png');
        $this->multiSelect($matematika, 26, 'Jam dinding menunjukkan pukul 15.00. Pilih semua pernyataan yang benar.', [
            'A' => 'Sudut terkecil yang terbentuk pada pukul 15.00 adalah 90 derajat.',
            'B' => 'Sudut terbesar yang terbentuk pada pukul 15.00 adalah 210 derajat.',
            'C' => 'Sudut terkecil yang terbentuk pada pukul 15.00 sama besar dengan sudut terkecil pada pukul 09.00.',
        ], ['A', 'C']);
        $this->multiSelect($matematika, 27, 'Perhatikan diagram batang berikut, lalu pilih semua pernyataan yang benar.', [
            'A' => 'Jumlah pengunjung selama satu minggu ada 58 orang.',
            'B' => 'Jumlah pengunjung perempuan selama satu minggu lebih banyak dari jumlah pengunjung laki-laki.',
            'C' => 'Jumlah pengunjung pada hari ke-1 sama dengan jumlah pengunjung pada hari ke-3.',
        ], ['A', 'C'], null, 10, 'demo/imports/paket-b/mtk-q27.png');
        $this->multipleChoice($matematika, 28, "Ukuran seragam siswa kelas enam adalah:\nS M L XL XL L M S S M\nM L S L M L L XL L M\nL S M M S L L S S XL\nUkuran seragam yang paling banyak digunakan oleh siswa kelas enam adalah ...", [
            'A' => 'S',
            'B' => 'M',
            'C' => 'L',
            'D' => 'XL',
        ], 'C');
        $this->multiSelect($matematika, 29, "Perhatikan data nilai ulangan berikut:\n80 = 2 siswa\n82 = 4 siswa\n84 = 3 siswa\n85 = 6 siswa\n90 = 5 siswa\nRata-rata nilai matematika siswa adalah 85. Pilih semua pernyataan yang benar.", [
            'A' => 'Jumlah siswa yang mendapat nilai di atas rata-rata adalah 11 siswa.',
            'B' => 'Jumlah siswa yang mendapat nilai di bawah rata-rata adalah 9 siswa.',
            'C' => 'Selisih jumlah siswa yang mendapat nilai di atas dan di bawah rata-rata adalah 4 siswa.',
        ], ['A', 'B']);
        $this->multiSelect($matematika, 30, 'Berdasarkan pictograph penjualan donat berikut, pilih semua pernyataan yang benar.', [
            'A' => 'Donat yang terjual pada hari Minggu adalah 51 buah.',
            'B' => 'Selisih donat yang terjual pada hari Rabu dan Jumat adalah 3 buah.',
            'C' => 'Donat yang terjual pada hari Sabtu dua kali lipat lebih banyak daripada hari Rabu.',
        ], ['A', 'C'], null, 10, 'demo/imports/paket-b/mtk-q30.png');

        $this->tokens($indonesia, ['INDSD1', 'INDSD2', 'INDSD3']);
        $this->tokens($matematika, ['MTKSD1', 'MTKSD2', 'MTKSD3']);

        $this->dummyFinishedAttempt($indonesia, 'Nadia Putri', 'SD Harapan Bangsa', '6', 160);
        $this->dummyFinishedAttempt($matematika, 'Rafi Pratama', 'SD Maju Bersama', '6', 170);
    }

    private function exam(array $data): Exam
    {
        return Exam::updateOrCreate(
            ['title' => $data['title']],
            array_merge([
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addWeeks(2),
                'status' => 'active',
                'instructions' => 'Baca soal dengan teliti. Untuk soal yang memuat beberapa pernyataan, pilih semua pernyataan yang benar. Jawaban otomatis tersimpan saat dipilih.',
                'shuffle_questions' => false,
                'shuffle_options' => false,
            ], $data),
        );
    }

    private function stimulus(Exam $exam, string $title, string $content): Stimulus
    {
        return Stimulus::updateOrCreate(
            ['exam_id' => $exam->id, 'title' => $title],
            ['type' => 'text', 'content' => $content],
        );
    }

    private function multipleChoice(
        Exam $exam,
        int $number,
        string $text,
        array $options,
        string $answerKey,
        ?Stimulus $stimulus = null,
        int $weight = 10,
        ?string $imagePath = null,
    ): Question {
        $question = Question::updateOrCreate(
            ['exam_id' => $exam->id, 'order_number' => $number],
            [
                'stimulus_id' => $stimulus?->id,
                'type' => 'multiple_choice',
                'question_text' => $text,
                'image_path' => $imagePath,
                'answer_key' => $answerKey,
                'score_weight' => $weight,
                'is_active' => true,
            ],
        );

        $question->options()->delete();
        foreach ($options as $index => $optionText) {
            $label = is_string($index) ? $index : chr(65 + $index);
            $question->options()->create([
                'label' => $label,
                'option_text' => $optionText,
                'is_correct' => $label === $answerKey,
                'order_number' => array_search($label, ['A', 'B', 'C', 'D', 'E'], true) + 1,
            ]);
        }

        return $question;
    }

    private function multiSelect(
        Exam $exam,
        int $number,
        string $text,
        array $options,
        array $correctKeys,
        ?Stimulus $stimulus = null,
        int $weight = 10,
        ?string $imagePath = null,
    ): Question {
        $question = Question::updateOrCreate(
            ['exam_id' => $exam->id, 'order_number' => $number],
            [
                'stimulus_id' => $stimulus?->id,
                'type' => 'multiple_choice_complex',
                'question_text' => $text,
                'image_path' => $imagePath,
                'answer_key' => implode(',', $correctKeys),
                'score_weight' => $weight,
                'is_active' => true,
            ],
        );

        $question->options()->delete();
        foreach ($options as $index => $optionText) {
            $label = is_string($index) ? $index : chr(65 + $index);
            $question->options()->create([
                'label' => $label,
                'option_text' => $optionText,
                'is_correct' => in_array($label, $correctKeys, true),
                'order_number' => array_search($label, ['A', 'B', 'C', 'D', 'E'], true) + 1,
            ]);
        }

        return $question;
    }

    private function tokens(Exam $exam, array $tokens): void
    {
        foreach ($tokens as $token) {
            ExamToken::updateOrCreate(
                ['token' => $token],
                ['exam_id' => $exam->id, 'status' => 'active', 'expires_at' => now()->addMonths(3)],
            );
        }
    }

    private function dummyFinishedAttempt(Exam $exam, string $name, string $school, string $grade, int $totalScore): void
    {
        $student = Student::updateOrCreate(
            ['name' => $name, 'school' => $school],
            ['phone' => '08'.random_int(1000000000, 9999999999), 'grade' => $grade],
        );

        $token = ExamToken::updateOrCreate(
            ['token' => 'USED-'.$exam->id.'-'.$student->id],
            ['exam_id' => $exam->id, 'student_id' => $student->id, 'status' => 'active', 'used_at' => now()->subHours(2)],
        );

        $attempt = ExamAttempt::updateOrCreate(
            ['exam_id' => $exam->id, 'student_id' => $student->id, 'exam_token_id' => $token->id],
            ['started_at' => now()->subHours(2), 'finished_at' => now()->subHour(), 'status' => 'finished'],
        );

        $maxScore = (int) $exam->questions()->sum('score_weight');
        $correct = (int) floor($totalScore / 10);

        ExamResult::updateOrCreate(
            ['exam_attempt_id' => $attempt->id],
            [
                'correct_count' => $correct,
                'wrong_count' => max(0, $exam->questions()->count() - $correct),
                'blank_count' => 0,
                'multiple_choice_score' => $totalScore,
                'essay_score' => 0,
                'total_score' => $totalScore,
                'is_passed' => $exam->passing_grade ? $totalScore >= $exam->passing_grade : null,
                'essay_status' => 'not_needed',
            ],
        );
    }
}

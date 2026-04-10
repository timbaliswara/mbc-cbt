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

        $sdMath = $this->exam([
            'title' => 'Try Out CBT SD Matematika',
            'level' => 'SD',
            'grade' => '6',
            'subject' => 'Matematika',
            'description' => 'Paket latihan berhitung, pecahan, bangun datar, dan cerita kontekstual.',
            'duration_minutes' => 90,
            'passing_grade' => 70,
            'show_result_to_student' => true,
        ]);

        $salesStimulus = $this->stimulus($sdMath, 'Data penjualan koperasi', "Koperasi sekolah menjual 25 buku tulis pada hari Senin dan 35 buku tulis pada hari Selasa.\nPada hari Rabu, koperasi menjual 18 buku tulis lebih sedikit dari hari Selasa.");
        $gardenStimulus = $this->stimulus(
            $sdMath,
            'Denah taman sekolah',
            "Sebuah taman sekolah berbentuk persegi panjang memiliki panjang 18 meter dan lebar 12 meter.\nDi sekeliling taman akan dipasang pagar.",
            'mixed',
            'demo/questions/denah-taman.svg',
        );

        $this->multipleChoice($sdMath, 1, 'Berapa total buku tulis yang terjual pada hari Senin dan Selasa?', ['A' => '50', 'B' => '60', 'C' => '70', 'D' => '80'], 'B', $salesStimulus);
        $this->multipleChoice($sdMath, 2, 'Berapa buku tulis yang terjual pada hari Rabu?', ['A' => '17', 'B' => '18', 'C' => '23', 'D' => '53'], 'A', $salesStimulus);
        $this->multipleChoice($sdMath, 3, 'Hasil dari 36 x 12 adalah ...', ['A' => '322', 'B' => '392', 'C' => '432', 'D' => '462'], 'C');
        $this->multipleChoice($sdMath, 4, 'Pecahan yang senilai dengan 3/4 adalah ...', ['A' => '6/8', 'B' => '4/6', 'C' => '5/8', 'D' => '7/12'], 'A');
        $this->multipleChoice($sdMath, 5, 'Perhatikan gambar denah taman. Keliling taman sekolah tersebut adalah ...', ['A' => '30 meter', 'B' => '48 meter', 'C' => '60 meter', 'D' => '216 meter'], 'C', $gardenStimulus, 10, 'demo/questions/denah-taman.svg');
        $this->essay($sdMath, 6, 'Jelaskan langkah menghitung keliling taman berdasarkan stimulus denah taman sekolah.', $gardenStimulus, 10);
        $this->essay($sdMath, 7, 'Sebuah kelas memiliki 32 siswa. Jika 3/8 siswa mengikuti lomba matematika, berapa siswa yang mengikuti lomba? Jelaskan caranya.', null, 10);
        $this->multipleChoice($sdMath, 8, 'Perhatikan gambar pecahan berikut. Bagian hijau menunjukkan pecahan ...', ['A' => '1/2', 'B' => '1/3', 'C' => '1/4', 'D' => '3/4'], 'C', null, 10, 'demo/questions/pecahan-lingkaran.svg');
        $this->multipleChoice(
            $sdMath,
            9,
            'Pilih gambar yang menunjukkan bangun persegi panjang.',
            ['A' => '', 'B' => '', 'C' => '', 'D' => ''],
            'D',
            null,
            10,
            null,
            [
                'A' => 'demo/options/segitiga.svg',
                'B' => 'demo/options/persegi.svg',
                'C' => 'demo/options/lingkaran.svg',
                'D' => 'demo/options/persegi-panjang.svg',
            ],
        );

        $smpScience = $this->exam([
            'title' => 'Try Out CBT SMP IPA',
            'level' => 'SMP',
            'grade' => '9',
            'subject' => 'IPA',
            'description' => 'Paket latihan ekosistem, gaya, energi, dan sistem organ.',
            'duration_minutes' => 100,
            'passing_grade' => 75,
            'show_result_to_student' => true,
        ]);

        $ecosystemStimulus = $this->stimulus(
            $smpScience,
            'Rantai makanan sawah',
            "Perhatikan rantai makanan pada gambar.\nGunakan rantai makanan ini untuk menjawab soal.",
            'mixed',
            'demo/stimuli/rantai-makanan.svg',
        );
        $motionStimulus = $this->stimulus($smpScience, 'Data gerak benda', "Sebuah benda bergerak lurus sejauh 120 meter dalam waktu 20 detik.\nGeraknya dianggap beraturan.");

        $this->multipleChoice($smpScience, 1, 'Pada rantai makanan tersebut, belalang berperan sebagai ...', ['A' => 'Produsen', 'B' => 'Konsumen tingkat I', 'C' => 'Konsumen tingkat II', 'D' => 'Pengurai'], 'B', $ecosystemStimulus);
        $this->multipleChoice($smpScience, 2, 'Jika populasi katak menurun drastis, dampak yang paling mungkin terjadi adalah ...', ['A' => 'Populasi belalang meningkat', 'B' => 'Populasi padi meningkat', 'C' => 'Populasi elang langsung meningkat', 'D' => 'Populasi ular selalu tetap'], 'A', $ecosystemStimulus);
        $this->multipleChoice($smpScience, 3, 'Kelajuan benda berdasarkan data tersebut adalah ...', ['A' => '4 m/s', 'B' => '5 m/s', 'C' => '6 m/s', 'D' => '8 m/s'], 'C', $motionStimulus);
        $this->multipleChoice($smpScience, 4, 'Organ yang berfungsi menyaring darah dan menghasilkan urine adalah ...', ['A' => 'Paru-paru', 'B' => 'Hati', 'C' => 'Ginjal', 'D' => 'Jantung'], 'C');
        $this->multipleChoice($smpScience, 5, 'Perubahan energi pada lampu senter adalah ...', ['A' => 'Kimia menjadi cahaya', 'B' => 'Panas menjadi gerak', 'C' => 'Cahaya menjadi listrik', 'D' => 'Gerak menjadi bunyi'], 'A');
        $this->essay($smpScience, 6, 'Jelaskan mengapa keseimbangan ekosistem dapat terganggu jika salah satu populasi dalam rantai makanan berubah drastis.', $ecosystemStimulus, 15);

        $sdIndonesian = $this->exam([
            'title' => 'Tes Diagnostik SD Bahasa Indonesia',
            'level' => 'SD',
            'grade' => '5',
            'subject' => 'Bahasa Indonesia',
            'description' => 'Paket membaca, ide pokok, informasi tersurat, dan menulis singkat.',
            'duration_minutes' => 60,
            'passing_grade' => 70,
            'show_result_to_student' => false,
        ]);

        $readingStimulus = $this->stimulus($sdIndonesian, 'Bacaan: Kebun Sekolah', 'Setiap Jumat, siswa kelas lima merawat kebun sekolah. Mereka menyiram tanaman, mencabut rumput liar, dan memberi pupuk. Kegiatan ini membuat halaman sekolah menjadi lebih hijau dan bersih.');

        $this->multipleChoice($sdIndonesian, 1, 'Ide pokok bacaan tersebut adalah ...', ['A' => 'Siswa kelas lima belajar di perpustakaan', 'B' => 'Siswa kelas lima merawat kebun sekolah', 'C' => 'Halaman sekolah dipenuhi rumput liar', 'D' => 'Tanaman sekolah tidak pernah disiram'], 'B', $readingStimulus);
        $this->multipleChoice($sdIndonesian, 2, 'Kapan siswa kelas lima merawat kebun sekolah?', ['A' => 'Setiap Senin', 'B' => 'Setiap Rabu', 'C' => 'Setiap Jumat', 'D' => 'Setiap Minggu'], 'C', $readingStimulus);
        $this->multipleChoice($sdIndonesian, 3, 'Antonim kata bersih adalah ...', ['A' => 'Kotor', 'B' => 'Indah', 'C' => 'Rapi', 'D' => 'Hijau'], 'A');
        $this->essay($sdIndonesian, 4, 'Tulislah satu kalimat ajakan untuk menjaga kebersihan sekolah.', $readingStimulus, 10);

        $this->tokens($sdMath, ['DEMO-TEST-2026', 'SD-MTK-0001', 'SD-MTK-0002']);
        $this->tokens($smpScience, ['SMP-IPA-0001', 'SMP-IPA-0002']);
        $this->tokens($sdIndonesian, ['SD-BINDO-0001', 'SD-BINDO-0002']);

        $this->dummyFinishedAttempt($sdMath, 'Nadia Putri', 'SD Harapan Bangsa', '6', 40, 10, 50);
        $this->dummyFinishedAttempt($smpScience, 'Rafi Pratama', 'SMP Negeri 2', '9', 50, 12, 62);
    }

    private function exam(array $data): Exam
    {
        return Exam::updateOrCreate(
            ['title' => $data['title']],
            array_merge([
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addWeeks(2),
                'status' => 'active',
                'instructions' => 'Baca soal dengan teliti. Jawaban otomatis tersimpan saat dipilih atau diketik. Submit ujian sebelum waktu habis.',
                'shuffle_questions' => false,
                'shuffle_options' => false,
            ], $data),
        );
    }

    private function stimulus(Exam $exam, string $title, string $content, string $type = 'text', ?string $filePath = null): Stimulus
    {
        return Stimulus::updateOrCreate(
            ['exam_id' => $exam->id, 'title' => $title],
            ['type' => $type, 'content' => $content, 'file_path' => $filePath],
        );
    }

    private function multipleChoice(Exam $exam, int $number, string $text, array $options, string $answerKey, ?Stimulus $stimulus = null, int $weight = 10, ?string $imagePath = null, array $optionImages = []): Question
    {
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
                'image_path' => $optionImages[$label] ?? null,
                'is_correct' => $label === $answerKey,
                'order_number' => array_search($label, ['A', 'B', 'C', 'D', 'E'], true) + 1,
            ]);
        }

        return $question;
    }

    private function essay(Exam $exam, int $number, string $text, ?Stimulus $stimulus = null, int $weight = 10): Question
    {
        return Question::updateOrCreate(
            ['exam_id' => $exam->id, 'order_number' => $number],
            [
                'stimulus_id' => $stimulus?->id,
                'type' => 'essay',
                'question_text' => $text,
                'answer_key' => null,
                'score_weight' => $weight,
                'is_active' => true,
            ],
        );
    }

    private function tokens(Exam $exam, array $tokens): void
    {
        foreach ($tokens as $token) {
            ExamToken::firstOrCreate(
                ['token' => $token],
                ['exam_id' => $exam->id, 'status' => 'unused', 'expires_at' => now()->addWeeks(2)],
            );
        }
    }

    private function dummyFinishedAttempt(Exam $exam, string $name, string $school, string $grade, int $multipleChoiceScore, int $essayScore, int $totalScore): void
    {
        $student = Student::updateOrCreate(
            ['name' => $name, 'school' => $school],
            ['phone' => '08'.random_int(1000000000, 9999999999), 'grade' => $grade],
        );

        $token = ExamToken::firstOrCreate(
            ['token' => 'USED-'.$exam->id.'-'.$student->id],
            ['exam_id' => $exam->id, 'student_id' => $student->id, 'status' => 'used', 'used_at' => now()->subHours(2)],
        );

        $attempt = ExamAttempt::updateOrCreate(
            ['exam_id' => $exam->id, 'student_id' => $student->id, 'exam_token_id' => $token->id],
            ['started_at' => now()->subHours(2), 'finished_at' => now()->subHour(), 'status' => 'finished'],
        );

        ExamResult::updateOrCreate(
            ['exam_attempt_id' => $attempt->id],
            [
                'correct_count' => max(0, (int) ($multipleChoiceScore / 10)),
                'wrong_count' => 1,
                'blank_count' => 0,
                'multiple_choice_score' => $multipleChoiceScore,
                'essay_score' => $essayScore,
                'total_score' => $totalScore,
                'is_passed' => $exam->passing_grade ? $totalScore >= $exam->passing_grade : null,
                'essay_status' => 'reviewed',
            ],
        );
    }
}

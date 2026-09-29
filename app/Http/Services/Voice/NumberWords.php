<?php

namespace App\Http\Services\Voice;

/**
 * Mengubah bilangan Bahasa Indonesia menjadi angka.
 *
 * Diperlukan karena petugas sering menyebut "asrama dua belas" atau
 * "tiga orang", bukan hanya digit.
 */
class NumberWords
{
    /**
     * Bilangan satuan 1 sampai 9.
     *
     * @var array<string, int>
     */
    private const UNITS = [
        'satu' => 1,
        'dua' => 2,
        'tiga' => 3,
        'empat' => 4,
        'lima' => 5,
        'enam' => 6,
        'tujuh' => 7,
        'delapan' => 8,
        'sembilan' => 9,
    ];

    /**
     * Istilah tersendiri yang tidak mengikuti pola satuan kali sepuluh.
     *
     * @var array<string, int>
     */
    private const STANDALONE = [
        'nol' => 0,
        'sepuluh' => 10,
        'sebelas' => 11,
        'seratus' => 100,
        'seribu' => 1000,
    ];

    /**
     * Cari nilai numerik dalam potongan teks, baik digit maupun kata.
     *
     * Mengembalikan null bila tidak ada bilangan.
     */
    public static function find(string $text, int $max = 99): ?int
    {
        $text = TextNormalizer::squish(TextNormalizer::toSlug($text));

        if ($text === '') {
            return null;
        }

        if (preg_match('/\b(\d{1,3})\b/', $text, $m) === 1) {
            $value = (int) $m[1];

            if ($value <= $max) {
                return $value;
            }
        }

        return self::fromWords($text, $max);
    }

    /**
     * Parse dari kata bilangan saja.
     *
     * Memindai token dari kiri dan selalu memilih bentuk terpanjang, sehingga
     * "dua belas" menghasilkan 12 dan bukan 2, serta "tiga puluh" menghasilkan
     * 30 dan bukan 3.
     */
    public static function fromWords(string $text, int $max = 99): ?int
    {
        $tokens = explode(' ', TextNormalizer::squish(TextNormalizer::toSlug($text)));
        $count = count($tokens);

        for ($index = 0; $index < $count; $index++) {
            $hasil = self::atPosition($tokens, $index, $count);

            if ($hasil !== null && $hasil['value'] <= $max) {
                return $hasil['value'];
            }
        }

        return null;
    }

    /**
     * Nilai bilangan yang dimulai pada posisi tertentu, dengan memakai token
     * berikutnya bila keduanya membentuk satu ekspresi utuh.
     *
     * Alongside nilainya, jumlah token yang terpakai ikut dikembalikan karena
     * pemanggil seperti pembaca tanggal perlu menandai token mana saja yang
     * sudah menjadi bagian bilangan.
     *
     * @param  list<string>  $tokens
     * @return array{value: int, span: int}|null
     */
    private static function atPosition(array $tokens, int $index, int $count): ?array
    {
        $token = $tokens[$index];
        $next = $index + 1 < $count ? $tokens[$index + 1] : null;
        $afterNext = $index + 2 < $count ? $tokens[$index + 2] : null;

        if (preg_match('/^(\d{1,4})$/', $token, $m) === 1) {
            return ['value' => (int) $m[1], 'span' => 1];
        }

        if (isset(self::STANDALONE[$token])) {
            $sisa = self::tail($tokens, $index + 1, $count);

            return ['value' => self::STANDALONE[$token] + $sisa['value'], 'span' => 1 + $sisa['span']];
        }

        if (! isset(self::UNITS[$token])) {
            $joined = self::joined($token);

            return $joined === null ? null : ['value' => $joined, 'span' => 1];
        }

        $unit = self::UNITS[$token];

        // "dua belas" -> 12
        if ($next === 'belas') {
            return ['value' => $unit + 10, 'span' => 2];
        }

        // "dua puluh" -> 20, "dua puluh enam" -> 26
        if ($next === 'puluh') {
            return self::tens($unit, $tokens, $index + 2, $count);
        }

        // "dua ratus" -> 200, "sembilan ratus sembilan puluh sembilan" -> 999
        if ($next === 'ratus') {
            $sisa = self::tail($tokens, $index + 2, $count);

            return ['value' => ($unit * 100) + $sisa['value'], 'span' => 2 + $sisa['span']];
        }

        // "dua ribu" -> 2000, "dua ribu dan enam" -> 2006,
        // "dua ribu dua puluh enam" -> 2026
        if ($next === 'ribu') {
            $sisa = self::tail($tokens, $index + 2, $count);

            return ['value' => ($unit * 1000) + $sisa['value'], 'span' => 2 + $sisa['span']];
        }

        return ['value' => $unit, 'span' => 1];
    }

    /**
     * Ekor bilangan setelah kata "ribu": boleh diawali "dan", lalu berupa
     * satuan atau "X puluh Y". Bentuk yang tidak dikenali diabaikan agar
     * bagian yang tidak bisa dipastikan tidak ikut dihitung.
     *
     * @param  list<string>  $tokens
     * @return array{value: int, span: int}
     */
    private static function tail(array $tokens, int $index, int $count): array
    {
        if ($index >= $count) {
            return ['value' => 0, 'span' => 0];
        }

        if ($tokens[$index] === 'dan') {
            $sisa = self::tail($tokens, $index + 1, $count);

            return ['value' => $sisa['value'], 'span' => 1 + $sisa['span']];
        }

        $unit = self::UNITS[$tokens[$index]] ?? null;

        if ($unit === null) {
            return ['value' => 0, 'span' => 0];
        }

        if (($tokens[$index + 1] ?? null) === 'puluh') {
            return self::tens($unit, $tokens, $index + 2, $count);
        }

        if (($tokens[$index + 1] ?? null) === 'belas') {
            return ['value' => $unit + 10, 'span' => 2];
        }

        return ['value' => $unit, 'span' => 1];
    }

    /**
     * "X puluh" dengan digit satuan yang opsional, misalnya 20 atau 26.
     *
     * @param  list<string>  $tokens
     * @return array{value: int, span: int}
     */
    private static function tens(int $unit, array $tokens, int $index, int $count): array
    {
        $ones = self::UNITS[$tokens[$index] ?? ''] ?? 0;
        $span = isset(self::UNITS[$tokens[$index] ?? '']) ? 3 : 2;

        return ['value' => ($unit * 10) + $ones, 'span' => $span];
    }

    /**
     * Baca bilangan pada posisi tertentu dari daftar token, sekaligus
     * melaporkan berapa token yang terpakai.
     *
     * @param  list<string>  $tokens
     * @return array{value: int, span: int}|null
     */
    public static function readAt(array $tokens, int $index, int $max = 99): ?array
    {
        if (! isset($tokens[$index])) {
            return null;
        }

        $hasil = self::atPosition($tokens, $index, count($tokens));

        if ($hasil === null || $hasil['value'] > $max) {
            return null;
        }

        return $hasil;
    }

    /**
     * Bentuk bilangan tanpa spasi, misalnya "duapuluh" atau "duabelas".
     *
     * Transkripsi Whisper sesekali menghilangkan spasi di tengah bilangan,
     * sehingga bentuk ini perlu dikenali agar tidak lolos ke LLM fallback.
     */
    private static function joined(string $token): ?int
    {
        foreach (self::UNITS as $word => $value) {
            if (! str_starts_with($token, $word)) {
                continue;
            }

            $rest = substr($token, strlen($word));

            if ($rest === 'puluh') {
                return $value * 10;
            }

            if ($rest === 'belas') {
                return $value + 10;
            }
        }

        return null;
    }
}

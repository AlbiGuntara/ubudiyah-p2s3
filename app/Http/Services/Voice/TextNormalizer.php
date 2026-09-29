<?php

namespace App\Http\Services\Voice;

/**
 * Normalisasi teks Bahasa Indonesia.
 *
 * Dipakai bersama oleh RulesIntentParser dan SantriNameMatcher supaya
 * perbandingan nama selalu memakai bentuk kanonik yang sama, apa pun
 * hasil transkripsi Whisper.
 */
class TextNormalizer
{
    /**
     * Kata pengisi yang sering muncul di percakapan tetapi bukan bagian dari
     * data pelanggaran. Dibuang sebelum parsing.
     *
     * @var list<string>
     */
    public const FILLER_WORDS = [
        'tolong', 'tolongin', 'catat', 'catatin', 'please', 'mohon', 'permisi',
        'dong', 'ya', 'yaa', 'yah', 'sih', 'deh', 'kok', 'lah', 'ih', 'wah', 'aduh',
        'pak', 'pakde', 'bu', 'bunda', 'mas', 'mba', 'mbak', 'ustadz', 'ustaz',
        'saya', 'aku', 'kita', 'kami', 'silakan', 'oke', 'ok', 'baik', 'baiklah',
        'nih', 'tuh', 'loh', 'kayaknya', 'sepertinya', 'mungkin',
        'agak', 'agaknya', 'banget', 'benar', 'betul', 'memang', 'emang',
        'ayo', 'yuk', 'nah', 'ntar', 'nanti', 'ini', 'itu',
        'buat', 'untuk', 'yg', 'dgn', 'utk', 'spt', 'kpd',
        'di', 'ke', 'dari', 'pada', 'lewat', 'terhadap',
        'sama', 'biar', 'supaya', 'karena', 'soal', 'tentang', 'wadah',
        'gimana', 'gak', 'enggak', 'nggak', 'udah', 'sudah', 'baru', 'masih',
        'lagi', 'juga', 'banget', 'pake', 'pakai', 'aja', 'doang', 'kayada',
    ];

    /**
     * Kata pemicu yang menandai awal sebuah nilai, dibuang setelah nilai
     * diambil, misalnya "nama rizky" menjadi "rizky".
     *
     * @var list<string>
     */
    public const LABEL_WORDS = [
        'nama', 'namanya', 'santri', 'santrinya', 'murid', 'muridnya',
        'anak', 'anaknya', 'adik', 'kakak', 'polwan', 'santriyin', 'yang',
        'daerah', 'daerahnya', 'asrama', 'asramanya', 'kamar', 'kamarnya',
        'nomor', 'no', 'tanggal', 'tgl', 'keterangan', 'catatan',
        'jenis', 'jenisnya', 'pelanggaran',
    ];

    /**
     * Huruf vokal Indonesia. Diabaikan saat menghitung Soundex.
     *
     * @var list<string>
     */
    private const VOWELS = ['a', 'e', 'i', 'o', 'u'];

    /**
     * Bentuk kanonik untuk disimpan dan dibandingkan.
     *
     * Menghapus peran dalam kurung, gelar di depan nama, dan sufiks
     * kebangsawanan, sehingga "M. Ahmad Fauzi (ketua)" menjadi "ahmad fauzi".
     */
    public static function canonical(string $text): string
    {
        $text = (string) preg_replace('/\([^)]*\)/', ' ', $text);
        $text = self::toSlug($text);

        $text = (string) preg_replace(
            '/\b(?:m|mh|dr|ir|s|spd|mpd|st|drs|ust|ustadz|ustaz|kh|habib|syekh|hj|haji|hajjah|prof)\s+/i',
            ' ',
            $text,
        );

        $text = (string) preg_replace('/\s+(?:bin|binti)\s+/i', ' ', $text);

        return self::squish($text);
    }

    /**
     * Huruf kecil, apostrof dihapus, tanda baca lain menjadi spasi, spasi rapat.
     *
     * Menangani apostrof yang lazim di master data, misalnya Jama'ah menjadi
     * jamaah, Qur'an menjadi quran, dan Isya' menjadi isya.
     */
    public static function toSlug(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');

        $text = str_replace("'", '', $text);
        $text = (string) preg_replace("/[\x{2018}\x{2019}\x{02BC}\x{00B4}]/u", '', $text);
        $text = (string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);

        return self::squish($text);
    }

    /**
     * Hapus spasi berlebih.
     */
    public static function squish(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @return list<string>
     */
    public static function tokens(string $text): array
    {
        $normalized = self::toSlug($text);

        if ($normalized === '') {
            return [];
        }

        return array_map(
            static fn (string $token): string => self::PRAYER_ALIASES[$token] ?? $token,
            explode(' ', $normalized),
        );
    }

    /**
     * Ejaan waktu salat yang keluar dari mulut orang, dipetakan ke ejaan
     * yang dipakai master data.
     *
     * Master data memakai "Dhuhur", "Subuh", "Ashar", "Maghrib". Orang
     * Indonesia lebih sering mengucapkannya sebagai "zuhur", "suboh",
     * "asar", atau "magrib", dan Whisper cenderung menulis apa yang
     * terdengar. Master data juga memuat
     * typo "Shubuh" pada "Tidak Wirid Shubuh", jadi ejaan itu ikut diluruskan.
     *
     * @var array<string, string>
     */
    public const PRAYER_ALIASES = [
        'zuhur' => 'dhuhur',
        'zuhore' => 'dhuhur',
        'dzuhur' => 'dhuhur',
        'dzohor' => 'dhuhur',
        'dlohor' => 'dhuhur',
        'shubuh' => 'subuh',
        'suboh' => 'subuh',
        'soboh' => 'subuh',
        'asar' => 'ashar',
        'ashad' => 'ashar',
        'magrib' => 'maghrib',
        'magribh' => 'maghrib',
        'wired' => 'wirid',
        'wiring' => 'wirid',
        'wirit' => 'wirid',
        'isyaa' => 'isya',
        'jumaat' => 'jumat',
        'berjamaah' => 'jamaah',
        'berjama' => 'jamaah',
    ];

    /**
     * Buang kata pengisi, kata label, dan kata tambahan.
     *
     * @param  list<string>  $tokens
     * @param  list<string>  $extra
     * @param  list<string>  $keep  kata yang harus dipertahankan meski ada di daftar buang
     * @return list<string>
     */
    public static function stripNoise(array $tokens, array $extra = [], array $keep = []): array
    {
        $remove = array_flip(array_merge(self::FILLER_WORDS, self::LABEL_WORDS, $extra));

        foreach ($keep as $word) {
            unset($remove[$word]);
        }

        return array_values(array_filter(
            $tokens,
            fn (string $t): bool => $t !== '' && ! isset($remove[$t]),
        ));
    }

    /**
     * Soundex standar, diadaptasi agar stabil untuk ejaan Arab-transkripsi
     * seperti Sunan, Qur'an, Mahallul, dan Qiyam yang huruf awalnya vary.
     *
     * @return string 4 karakter: huruf pertama diikuti 3 digit
     */
    public static function soundex(string $word): string
    {
        $word = self::toSlug($word);

        if ($word === '') {
            return '';
        }

        $letters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        /** @var string $first */
        $first = (string) array_shift($letters);

        $map = [
            'b' => '1', 'f' => '1', 'p' => '1', 'v' => '1',
            'c' => '2', 'g' => '2', 'j' => '2', 'k' => '2', 'q' => '2', 's' => '2', 'x' => '2', 'z' => '2',
            'd' => '3', 't' => '3',
            'l' => '4',
            'm' => '5', 'n' => '5',
            'r' => '6',
        ];

        $digits = '';
        $previous = $map[$first] ?? '';

        foreach ($letters as $letter) {
            // Vokal serta h dan w tidak memutus rangkaian kode sebelumnya.
            if (in_array($letter, self::VOWELS, true) || $letter === 'h' || $letter === 'w') {
                continue;
            }

            $code = $map[$letter] ?? '';

            if ($code !== '' && $code !== $previous) {
                $digits .= $code;

                if (strlen($digits) === 3) {
                    break;
                }
            }

            $previous = $code;
        }

        return strtoupper($first).str_pad($digits, 3, '0', STR_PAD_RIGHT);
    }

    /**
     * Soundex gabungan beberapa token nama. Nama Indonesia kerap berawalan
     * gelar atau marga, jadi menggabungkan lebih stabil daripada satu token.
     */
    public static function soundexPhrase(string $text): string
    {
        $tokens = array_slice(self::tokens(self::canonical($text)), 0, 3);

        if ($tokens === []) {
            return '';
        }

        return implode('-', array_map(self::soundex(...), $tokens));
    }

    /**
     * Jarak Levenshtein. Diimplementasi manual agar tidak bergantung
     * ekstensi intl yang belum tentu terpasang di server produksi.
     */
    public static function levenshtein(string $a, string $b): int
    {
        $a = self::toSlug($a);
        $b = self::toSlug($b);

        if ($a === $b) {
            return 0;
        }

        if ($a === '' || $b === '') {
            return max(mb_strlen($a), mb_strlen($b));
        }

        if (mb_strlen($a) < mb_strlen($b)) {
            [$a, $b] = [$b, $a];
        }

        $charsA = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $charsB = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $lenB = count($charsB);
        $previous = range(0, $lenB);

        foreach ($charsA as $i => $charA) {
            $current = [$i + 1];

            foreach ($charsB as $j => $charB) {
                $current[$j + 1] = min(
                    $current[$j] + 1,
                    $previous[$j + 1] + 1,
                    $previous[$j] + ($charA === $charB ? 0 : 1),
                );
            }

            $previous = $current;
        }

        return $previous[$lenB];
    }

    /**
     * Skor kemiripan 0 sampai 1 dari jarak Levenshtein.
     */
    public static function similarity(string $a, string $b): float
    {
        $max = max(mb_strlen(self::toSlug($a)), mb_strlen(self::toSlug($b)));

        if ($max === 0) {
            return 1.0;
        }

        return 1.0 - (self::levenshtein($a, $b) / $max);
    }

    /**
     * Berapa banyak token needle yang muncul sebagai substring utuh di
     * dalam haystack. Ini jalur utama untuk nama panggilan, karena kolom
     * nama_panggilan nyaris kosong di data produksi.
     *
     * @param  list<string>  $needle
     * @param  list<string>  $haystack
     */
    public static function tokenCoverage(array $needle, array $haystack): float
    {
        $needle = array_values(array_filter($needle));
        $haystack = array_values(array_filter($haystack));

        if ($needle === [] || $haystack === []) {
            return 0.0;
        }

        $joined = ' '.implode(' ', $haystack).' ';
        $matched = 0;

        foreach ($needle as $token) {
            if (str_contains($joined, ' '.$token.' ')) {
                $matched++;
            }
        }

        return $matched / count($needle);
    }
}

<?php

namespace App\Http\Services\Voice;

use Carbon\CarbonImmutable;

/**
 * Membaca tanggal Bahasa Indonesia dari transkripsi.
 *
 * Carbon tidak mengenali kata relatif seperti "kemarin" maupun "Senin lalu",
 * jadi setiap pola dipetakan eksplisit di sini.
 *
 * Setiap penemu mengembalikan rentang token yang terpakai supaya pemanggil
 * bisa membuangnya dari teks dan tidak ikut membaca sisa-sisanya sebagai nama.
 */
class IndonesianDateParser
{
    /**
     * @var array<string, int>
     */
    private const MONTHS = [
        'januari' => 1, 'jan' => 1, 'january' => 1,
        'februari' => 2, 'feb' => 2, 'february' => 2, 'pebruari' => 2,
        'maret' => 3, 'mar' => 3, 'march' => 3,
        'april' => 4, 'apr' => 4,
        'mei' => 5, 'may' => 5,
        'juni' => 6, 'jun' => 6, 'june' => 6,
        'juli' => 7, 'jul' => 7, 'july' => 7,
        'agustus' => 8, 'agu' => 8, 'augustus' => 8,
        'september' => 9, 'sep' => 9, 'sept' => 9,
        'oktober' => 10, 'okt' => 10, 'oct' => 10, 'october' => 10,
        'november' => 11, 'nov' => 11,
        'desember' => 12, 'des' => 12, 'december' => 12,
    ];

    /**
     * @var array<string, int> 0 berarti Minggu
     */
    private const WEEKDAYS = [
        'minggu' => 0, 'ahad' => 0,
        'senin' => 1, 'selasa' => 2, 'rabo' => 3, 'rabu' => 3,
        'kamis' => 4, 'jumat' => 5,
        'sabtu' => 6,
    ];

    /**
     * Kata relatif dengan selisih hari tetap.
     *
     * @var array<string, int>
     */
    private const RELATIVE = [
        'semalam' => -1,
        'kemarin' => -1,
        'lusa' => 2,
        'besok' => 1,
        'esok' => 1,
    ];

    /**
     * @var list<string>
     */
    private const PAST_MARKERS = ['lalu', 'terakhir', 'kemarin', 'dulu', 'lewat'];

    /**
     * @var list<string>
     */
    private const TIME_HINTS = ['pagi', 'siang', 'sore', 'malam', 'subuh', 'dhuha', 'zuhur', 'ashar', 'magrib'];

    /**
     * Cari tanggal pada token yang sudah dinormalisasi.
     *
     * @param  list<string>  $tokens
     * @return array{date: string, start: int, end: int, confidence: float, label: string}|null
     */
    public static function find(array $tokens): ?array
    {
        return self::absolute($tokens)
            ?? self::relativeCount($tokens)
            ?? self::weekday($tokens)
            ?? self::relative($tokens);
    }

    /**
     * Baca bilangan pada posisi tertentu, baik digit maupun kata.
     *
     * Ucapan petugas hampir selalu mengeja angka, jadi "satu" harus dibaca
     * sama seperti "1". Nilai di atas batas dianggap bukan bagian tanggal
     * supaya kata lain tidak ikut dimakan.
     *
     * @param  list<string>  $tokens
     * @return array{value: int, span: int}|null
     */
    private static function numberAt(array $tokens, int $index, int $max): ?array
    {
        $token = $tokens[$index] ?? null;

        if ($token === null) {
            return null;
        }

        if (preg_match('/^(\d{1,4})$/', $token, $m) === 1) {
            return ['value' => (int) $m[1], 'span' => 1];
        }

        return NumberWords::readAt($tokens, $index, $max);
    }

    /**
     * Tahun hanya dibaca dalam rentang yang masuk akal, yaitu 1900 sampai 2100.
     *
     * Pembatas ini penting: tanpa itu, angka kecil apa pun yang kebetulan
     * berada setelah nama bulan akan terbaca sebagai tahun.
     *
     * @param  list<string>  $tokens
     * @return array{value: int, span: int}|null
     */
    private static function yearAt(array $tokens, int $index): ?array
    {
        $hasil = self::numberAt($tokens, $index, 2100);

        if ($hasil === null || $hasil['value'] < 1900) {
            return null;
        }

        return $hasil;
    }

    /**
     * Tanggal mutlak: "12 oktober 2026", "tanggal 5 desember", "5/12/2026".
     *
     * @param  list<string>  $tokens
     * @return array{date: string, start: int, end: int, confidence: float, label: string}|null
     */
    private static function absolute(array $tokens): ?array
    {
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (in_array($token, self::TIME_HINTS, true)) {
                continue;
            }

            $start = $i;

            if (in_array($token, ['tanggal', 'tgl', 'pada'], true)) {
                $start = $i + 1;

                if ($start >= $count) {
                    continue;
                }

                $token = $tokens[$start];
            }

            $hari = self::numberAt($tokens, $start, 31);

            if ($hari === null) {
                continue;
            }

            $day = $hari['value'];
            $month = null;
            $year = null;
            $end = $start + $hari['span'] - 1;

            $next = $end + 1 < $count ? $tokens[$end + 1] : null;

            if ($next !== null && self::isMonth($next)) {
                $month = self::MONTHS[$next];
                $end++;

                $tahun = self::yearAt($tokens, $end + 1);

                if ($tahun !== null) {
                    $year = $tahun['value'];
                    $end += $tahun['span'];
                }
            } else {
                // "12 10 2026" hasil tokenisasi dari "12/10/2026"
                $second = $next;
                $third = $start + 2 < $count ? $tokens[$start + 2] : null;

                if ($second !== null
                    && preg_match('/^(1[0-2]|0?[1-9])$/', $second) === 1
                    && $third !== null
                    && preg_match('/^(19|20)\d{2}$/', $third) === 1
                ) {
                    $month = (int) $second;
                    $year = (int) $third;
                    $end = $start + 2;
                }
            }

            if ($month === null) {
                continue;
            }

            $date = self::build($year, $month, $day);

            if ($date === null) {
                continue;
            }

            return [
                'date' => $date->toDateString(),
                'start' => $start,
                'end' => $end + 1,
                'confidence' => $year === null ? 0.8 : 1.0,
                'label' => implode(' ', array_slice($tokens, $start, $end + 1 - $start)),
            ];
        }

        return null;
    }

    /**
     * "3 hari yang lalu", "2 minggu lalu", "minggu lalu".
     *
     * @param  list<string>  $tokens
     * @return array{date: string, start: int, end: int, confidence: float, label: string}|null
     */
    private static function relativeCount(array $tokens): ?array
    {
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $unit = $tokens[$i];

            if ($unit === 'hari' || $unit === 'minggu' || $unit === 'bulan') {
                $previous = $i > 0 ? $tokens[$i - 1] : null;
                $amount = $previous !== null ? NumberWords::fromWords($previous, 60) : null;

                if ($amount === null) {
                    if ($unit !== 'minggu') {
                        continue;
                    }

                    $amount = 1;
                } else {
                    $i--;
                }

                $end = $i + 2;

                for ($j = $end; $j < min($count, $end + 3); $j++) {
                    if (in_array($tokens[$j], self::PAST_MARKERS, true)
                        || in_array($tokens[$j], ['yang', 'yg'], true)) {
                        $end = $j + 1;
                    }
                }

                $date = match ($unit) {
                    'minggu' => CarbonImmutable::now()->subWeeks($amount),
                    'bulan' => CarbonImmutable::now()->subMonthsNoOverflow($amount),
                    default => CarbonImmutable::now()->subDays($amount),
                };

                return [
                    'date' => $date->toDateString(),
                    'start' => $i,
                    'end' => min($end, $count),
                    'confidence' => 0.95,
                    'label' => implode(' ', array_slice($tokens, $i, min($end, $count) - $i)),
                ];
            }
        }

        return null;
    }

    /**
     * "senin lalu", "jumat terakhir".
     *
     * @param  list<string>  $tokens
     * @return array{date: string, start: int, end: int, confidence: float, label: string}|null
     */
    private static function weekday(array $tokens): ?array
    {
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (! isset(self::WEEKDAYS[$tokens[$i]])) {
                continue;
            }

            $weekday = self::WEEKDAYS[$tokens[$i]];
            $end = $i + 1;
            $modifier = 'next';

            if ($end < $count) {
                if (in_array($tokens[$end], self::PAST_MARKERS, true)) {
                    $modifier = 'last';
                    $end++;
                } elseif ($tokens[$end] === 'ini') {
                    $modifier = 'this';
                    $end++;
                }
            }

            $date = match ($modifier) {
                'last' => CarbonImmutable::now()
                    ->subDays((int) CarbonImmutable::now()->dayOfWeekIso - 1)
                    ->subWeeks(1)
                    ->startOfWeek(CarbonImmutable::SUNDAY)
                    ->addDays($weekday),
                'this' => CarbonImmutable::now()
                    ->startOfWeek(CarbonImmutable::SUNDAY)
                    ->addDays($weekday),
                default => CarbonImmutable::now()
                    ->startOfWeek(CarbonImmutable::SUNDAY)
                    ->addDays($weekday)
                    ->addWeek(),
            };

            return [
                'date' => $date->toDateString(),
                'start' => $i,
                'end' => $end,
                'confidence' => $modifier === 'next' ? 0.6 : 0.9,
                'label' => implode(' ', array_slice($tokens, $i, $end - $i)),
            ];
        }

        return null;
    }

    /**
     * "hari ini", "kemarin", "lusa", "besok".
     *
     * @param  list<string>  $tokens
     * @return array{date: string, start: int, end: int, confidence: float, label: string}|null
     */
    private static function relative(array $tokens): ?array
    {
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            $offset = null;
            $span = 1;

            if ($token === 'hari' && $i + 1 < $count && $tokens[$i + 1] === 'ini') {
                $offset = 0;
                $span = 2;
            } elseif (isset(self::RELATIVE[$token])) {
                $offset = self::RELATIVE[$token];
            }

            if ($offset === null) {
                continue;
            }

            $date = CarbonImmutable::now()->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'start' => $i,
                'end' => $i + $span,
                'confidence' => 1.0,
                'label' => implode(' ', array_slice($tokens, $i, $span)),
            ];
        }

        return null;
    }

    private static function isMonth(string $token): bool
    {
        return isset(self::MONTHS[$token]);
    }

    /**
     * Susun tanggal, memakai tahun berjalan bila tidak disebutkan.
     *
     * Penyesuaian ke tahun sebelumnya hanya berlaku bila tahun tidak disebut
     * eksplisit, sehingga "12 Oktober 2026" tetap berarti 2026 walaupun
     * tanggalnya masih beberapa hari ke depan.
     */
    private static function build(?int $year, int $month, int $day): ?CarbonImmutable
    {
        $today = CarbonImmutable::now();
        $explicit = $year !== null;
        $year ??= $today->year;

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }

        try {
            $date = CarbonImmutable::create($year, $month, $day);
        } catch (\Throwable) {
            return null;
        }

        if ($date === null) {
            return null;
        }

        if (! $explicit && $year === $today->year && $date->greaterThan($today)) {
            $date = $date->subYear();
        }

        return $date;
    }
}

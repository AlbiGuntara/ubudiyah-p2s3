<?php

namespace App\Http\Services\Voice;

/**
 * Membaca kalimat transkripsi menjadi draft pelanggaran memakai aturan.
 *
 * Urutan ekstraksi penting. Jenis pelanggaran harus dibaca lebih dulu
 * karena hampir seluruh nama di master data diawali kata "Tidak", yang
 * ada di daftar kata pengisi. Kata pengisi baru dibuang dari sisa teks
 * setelah semua entitas terstruktur terbaca, bukan sebelumnya.
 */
class RulesIntentParser
{
    /**
     * Penanda bahwa catatan dibuat tanpa nama Santri.
     *
     * @var list<string>
     */
    private const ANONYMOUS_MARKERS = ['tanpanama', 'tanpanya', 'anonym', 'anonim'];

    /**
     * @var array<string, string>
     */
    private const SUMBER = [
        'ketuakamar' => 'ketua_kamar',
        'ketuaasrama' => 'ketua_kamar',
        'kepalakamar' => 'ketua_kamar',
        'kepalaasrama' => 'ketua_kamar',
        'kamar' => 'ketua_kamar',
        'petugas' => 'petugas',
        'petugasubudiyah' => 'petugas',
        'ubudiyah' => 'petugas',
    ];

    /**
     * Sumber pencatatan berupa dua kata. Harus dicek sebelum token tunggal,
     * kalau tidak hanya kata "ketua" yang tersisa di teks.
     *
     * @var array<string, string>
     */
    private const SUMBER_PHRASES = [
        'ketua kamar' => 'ketua_kamar',
        'ketua asrama' => 'ketua_kamar',
        'kepala kamar' => 'ketua_kamar',
        'kepala asrama' => 'ketua_kamar',
        'petugas ubudiyah' => 'petugas',
    ];

    /**
     * Penanda awal keterangan, yang harus bertahan melewati pembersihan
     * kata pengisi agar sisa teks bisa dipotong pada titik ini.
     *
     * @var list<string>
     */
    private const NOTE_MARKERS = ['keterangan', 'catatan', 'alasan', 'soal', 'karena'];

    /**
     * Kata yang boleh mendahului kode daerah satu huruf, seperti "daerah B".
     *
     * Kode "B" sampai "N" adalah satu token pendek yang bisa muncul tanpa
     * sengaja di kalimat biasa, sehingga tidak boleh dicocokkan di posisi
     * bebas. Nama daerah seperti "Sunan Ampel" aman karena banyak tokennya.
     *
     * @var list<string>
     */
    private const DAERAH_ANCHORS = ['daerah', 'daerahnya', 'wilayah', 'di', 'unit', 'asrama', 'kamar'];

    /**
     * Token yang tidak membedakan satu jenis pelanggaran dari jenis lain.
     *
     * Hampir tiga puluh dari tiga puluh lima nama di master data diawali kata
     * "tidak", sehingga kecocokan yang hanya memuat kata itu tidak membawa
     * informasi apa pun. Kalau tidak ada token lain yang cocok, jenis dianggap
     * tidak ditemukan agar urusannya jatuh ke parser LLM.
     *
     * @var list<string>
     */
    private const NON_DISCRIMINATIVE = ['tidak', 'belum', 'gak', 'nggak', 'enggak', 'absen', 'terlewat', 'bolos'];

    public function __construct(private readonly VoiceMasterData $master) {}

    /**
     * @return array{
     *     anonymous: bool, anonymous_explicit: bool, jumlah: int, daerah_id: int|null,
     *     asrama_id: int|null, asrama_nomor: string|null, daftar_pelanggaran_ids: list<int>,
     *     daftar_pelanggaran_label: string|null, tanggal: string|null,
     *     keterangan: string|null, sumber_pencatatan: string, santri_query: string|null,
     *     raw_remainder: list<string>, confidence: array<string, float>
     * }
     */
    public function parse(string $transcript): array
    {
        $all = TextNormalizer::tokens($transcript);

        // Catatan bebas dimulai setelah penanda seperti "keterangan". Isian
        // lain hanya boleh diambil dari bagian sebelum penanda, supaya kata
        // di dalam catatan tidak ikut terangkat menjadi nama atau asrama.
        $noteAt = $this->noteMarkerIndex($all);
        $tokens = $noteAt === null ? $all : array_slice($all, 0, $noteAt);
        $note = $noteAt === null
            ? null
            : TextNormalizer::squish(implode(' ', array_slice($all, $noteAt + 1)));

        $used = array_fill(0, count($tokens), false);

        $anonymous = $this->extractAnonymous($tokens, $used);
        $jumlah = $anonymous > 0 ? $anonymous : 1;
        $jumlah = $this->extractJumlah($tokens, $used, $jumlah);
        $sumber = $this->extractSumber($tokens, $used);
        $daftar = $this->extractJenisPelanggaran($tokens, $used);
        $tanggal = IndonesianDateParser::find($tokens);
        $daerah = $this->extractDaerah($tokens, $used);
        $asrama = $this->extractAsrama($tokens, $used, $daerah['id']);

        if ($tanggal !== null) {
            for ($i = $tanggal['start']; $i < $tanggal['end']; $i++) {
                $used[$i] = true;
            }
        }

        // Kata bising hanya dibuang dari bagian nama, bukan dari catatan.
        $leftover = $this->leftover($tokens, $used);
        $name = TextNormalizer::squish(implode(' ', $this->stripNoise($leftover)));

        $asramaId = $asrama['id'] ?? $daerah['asrama_hint'] ?? null;

        return [
            'anonymous' => $anonymous > 0 || $name === '',
            'anonymous_explicit' => $anonymous > 0,
            'jumlah' => $jumlah,
            'daerah_id' => $daerah['id'],
            'asrama_id' => $asramaId,
            'asrama_nomor' => $asrama['nomor'],
            'daftar_pelanggaran_ids' => $daftar['ids'],
            'daftar_pelanggaran_label' => $daftar['label'],
            'tanggal' => $tanggal['date'] ?? null,
            'keterangan' => $note === '' ? null : $note,
            'sumber_pencatatan' => $sumber,
            'santri_query' => $name === '' ? null : $name,
            'raw_remainder' => $leftover,
            'confidence' => [
                'jenis' => $daftar['confidence'],
                'daerah' => $daerah['confidence'],
                'asrama' => $asrama['confidence'],
                'tanggal' => $tanggal['confidence'] ?? 0.0,
            ],
        ];
    }

    /**
     * Posisi penanda catatan, atau null bila tidak ada.
     *
     * Penanda di posisi nol diabaikan, karena kata seperti "karena" sering
     * muncul di awal kalimat tanpa bermaksud memulai catatan.
     *
     * @param  list<string>  $tokens
     */
    private function noteMarkerIndex(array $tokens): ?int
    {
        foreach ($tokens as $index => $token) {
            if (in_array($token, self::NOTE_MARKERS, true)) {
                return $index > 0 ? $index : null;
            }
        }

        return null;
    }

    /**
     * Deteksi mode tanpa nama beserta jumlahnya.
     *
     * @param  list<string>  $tokens
     * @param  array<int, bool>  $used
     * @return int 0 bila bukan catatan anonim
     */
    private function extractAnonymous(array $tokens, array &$used): int
    {
        foreach ($tokens as $index => $token) {
            $squashed = (string) preg_replace('/\s+/', '', implode(' ', array_slice($tokens, $index, 2)));

            foreach (self::ANONYMOUS_MARKERS as $marker) {
                if (str_contains($squashed, $marker)) {
                    $used[$index] = true;
                    if ($index + 1 < count($tokens)) {
                        $used[$index + 1] = true;
                    }

                    return 1;
                }
            }
        }

        return 0;
    }

    /**
     * Jumlah Santri untuk catatan anonim: "tiga orang", "3 orang", "2 jamaah".
     *
     * @param  list<string>  $tokens
     * @param  array<int, bool>  $used
     */
    private function extractJumlah(array $tokens, array &$used, int $fallback): int
    {
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if ($used[$i] || ! in_array($tokens[$i], ['orang', 'santri', 'murid', 'jamaah', 'anak'], true)) {
                continue;
            }

            $previous = $i > 0 && ! $used[$i - 1] ? $tokens[$i - 1] : null;

            if ($previous === null) {
                continue;
            }

            $amount = NumberWords::fromWords($previous, 99);

            if ($amount !== null && $amount > 0) {
                $used[$i - 1] = true;
                $used[$i] = true;

                return $amount;
            }
        }

        return $fallback;
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<int, bool>  $used
     */
    private function extractSumber(array $tokens, array &$used): string
    {
        foreach (self::SUMBER_PHRASES as $phrase => $value) {
            $needle = explode(' ', $phrase);
            $start = null;

            for ($i = 0; $i + count($needle) <= count($tokens); $i++) {
                if (array_slice($tokens, $i, count($needle)) === $needle) {
                    $start = $i;
                    break;
                }
            }

            if ($start === null) {
                continue;
            }

            for ($i = $start; $i < $start + count($needle); $i++) {
                $used[$i] = true;
            }

            return $value;
        }

        foreach ($tokens as $index => $token) {
            $squashed = (string) preg_replace('/\s+/', '', $token);

            if (! isset(self::SUMBER[$squashed])) {
                continue;
            }

            $used[$index] = true;

            return self::SUMBER[$squashed];
        }

        return 'petugas';
    }

    /**
     * Cocokkan jenis pelanggaran.
     *
     * Seluruh nama di master data berbentuk frasa, jadi pencocokan memakai
     * token berurutan. Master data produksi memuat nama duplikat, misal
     * "Tidak Khotmil Qur'an" dan "Tidak Mahallul Qiyam" masing-masing dua
     * baris, sehingga hasilnya selalu berupa daftar id.
     *
     * @param  list<string>  $tokens
     * @param  array<int, bool>  $used
     * @return array{ids: list<int>, label: string|null, confidence: float}
     */
    private function extractJenisPelanggaran(array $tokens, array &$used): array
    {
        $best = null;

        foreach ($this->master->daftarPelanggaran() as $jenis) {
            $needle = TextNormalizer::tokens($jenis['nama']);

            if ($needle === []) {
                continue;
            }

            $match = $this->bestSequence($tokens, $needle, $used);

            if ($match === null) {
                continue;
            }

            if ($best === null || $match['score'] > $best['score']) {
                $best = ['ids' => [$jenis['id']], 'label' => $jenis['nama'], 'score' => $match['score'], 'spans' => $match['spans']];
            } elseif (abs($match['score'] - $best['score']) < 0.0001) {
                $best['ids'][] = $jenis['id'];
            }
        }

        if ($best === null) {
            return ['ids' => [], 'label' => null, 'confidence' => 0.0];
        }

        foreach ($best['spans'] as $span) {
            $used[$span] = true;
        }

        return ['ids' => array_values(array_unique($best['ids'])), 'label' => $best['label'], 'confidence' => $best['score']];
    }

    /**
     * Cari kemunculan terbaik dari token nama pelanggaran di dalam kalimat.
     *
     * Frasa penuh bernilai 1. Karena hampir semua nama di master data diawali
     * kata "tidak" dan pemicara sering melewatkannya, kecocokan berurutan
     * yang kehilangan paling banyak satu token tetap dianggap kuat. Kehilangan
     * lebih dari itu diturunkan agar urusannya diserahkan ke parser LLM.
     *
     * @param  list<string>  $haystack
     * @param  list<string>  $needle
     * @param  array<int, bool>  $used
     * @return array{score: float, spans: list<int>}|null
     */
    private function bestSequence(array $haystack, array $needle, array $used): ?array
    {
        $count = count($haystack);
        $spans = [];
        $cursor = 0;
        $hit = 0;

        foreach ($needle as $token) {
            $found = null;

            for ($i = $cursor; $i < $count; $i++) {
                if (! $used[$i] && $haystack[$i] === $token) {
                    $found = $i;
                    break;
                }
            }

            // Token yang tidak diucapkan tidak menghentikan pencarian. Kalau
            // pemicara melewatkan "tidak", sisa frasa masih harus dicocokkan.
            if ($found === null) {
                continue;
            }

            $spans[] = $found;
            $cursor = $found + 1;
            $hit++;
        }

        if ($hit === 0) {
            return null;
        }

        $discriminative = false;

        foreach ($spans as $span) {
            if (! in_array($haystack[$span], self::NON_DISCRIMINATIVE, true)) {
                $discriminative = true;
                break;
            }
        }

        if (! $discriminative) {
            return null;
        }

        $coverage = $hit / count($needle);
        $contiguous = $spans === range($spans[0], $spans[0] + $hit - 1);

        // Waktu salat yang diulang di depan frasa, misalnya "risky dzuhur
        // jubah gamis dhuhur", ikut dikonsumsi supaya tidak tertinggal di
        // sisa teks dan merusak pencarian nama. Penilaian kontiguitas harus
        // selesai sebelum rentang ini melebar.
        while ($spans[0] > 0 && in_array($haystack[$spans[0] - 1], $needle, true)) {
            array_unshift($spans, $spans[0] - 1);
        }

        $score = match (true) {
            $coverage === 1.0 && $contiguous => 1.0,
            $contiguous && $coverage >= 0.66 => 0.60 + 0.40 * $coverage,
            $contiguous => 0.45 + 0.30 * $coverage,
            default => min(0.45, $coverage * 0.6 - 0.1),
        };

        return $score >= 0.4 ? ['score' => $score, 'spans' => $spans] : null;
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<int, bool>  $used
     * @return array{id: int|null, asrama_hint: int|null, confidence: float}
     */
    private function extractDaerah(array $tokens, array &$used): array
    {
        $count = count($tokens);
        $best = ['id' => null, 'asrama_hint' => null, 'confidence' => 0.0];

        foreach ($this->master->daerah() as $daerah) {
            foreach ([$daerah['kode'], $daerah['nama']] as $candidate) {
                $needle = TextNormalizer::tokens((string) $candidate);

                if ($needle === []) {
                    continue;
                }

                $index = $this->findSequence($tokens, $needle, $used);

                if ($index === null) {
                    continue;
                }

                // Kode satu token pendek hanya dipercaya di belakang kata
                // jangkar, kalau tidak huruf yang kebetulan muncul di
                // kalimat biasa akan dianggap sebagai nama daerah.
                $anchored = $index > 0 && in_array($tokens[$index - 1], self::DAERAH_ANCHORS, true);

                if (count($needle) === 1 && strlen($needle[0]) <= 2 && ! $anchored) {
                    continue;
                }

                $confidence = match (true) {
                    count($needle) >= 2 => 0.95,
                    $anchored => 0.75,
                    default => 0.4,
                };

                for ($i = $index; $i < $index + count($needle); $i++) {
                    $used[$i] = true;
                }

                if ($index > 0 && in_array($tokens[$index - 1], self::DAERAH_ANCHORS, true)) {
                    $used[$index - 1] = true;
                }

                if ($confidence >= $best['confidence']) {
                    $best = [
                        'id' => (int) $daerah['id'],
                        'asrama_hint' => null,
                        'confidence' => $confidence,
                    ];
                }

                break;
            }
        }

        return $best;
    }

    /**
     * @param  list<string>  $haystack
     * @param  list<string>  $needle
     * @param  array<int, bool>  $used
     */
    private function findSequence(array $haystack, array $needle, array $used): ?int
    {
        $count = count($haystack);

        for ($start = 0; $start + count($needle) <= $count; $start++) {
            $ok = true;

            for ($offset = 0; $offset < count($needle); $offset++) {
                if ($used[$start + $offset] || $haystack[$start + $offset] !== $needle[$offset]) {
                    $ok = false;
                    break;
                }
            }

            if ($ok) {
                return $start;
            }
        }

        return null;
    }

    /**
     * Nomor asrama, dipadukan dengan daerah bila disebut.
     *
     * Karena nomor asrama berulang di setiap daerah, nomor saja tidak pernah
     * cukup untuk menentukan satu asrama secara pasti.
     *
     * @param  list<string>  $tokens
     * @param  array<int, bool>  $used
     * @return array{id: int|null, nomor: string|null, confidence: float}
     */
    private function extractAsrama(array $tokens, array &$used, ?int $daerahId): array
    {
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if ($used[$i] || ! in_array($tokens[$i], ['asrama', 'kamar'], true)) {
                continue;
            }

            $cursor = $i + 1;

            if ($cursor < $count && in_array($tokens[$cursor], ['nomor', 'no', 'number'], true)) {
                $cursor++;
            }

            $nomor = null;

            for ($j = $cursor; $j < min($count, $cursor + 3); $j++) {
                $candidate = $tokens[$j];

                if (preg_match('/^(\d{1,2})$/', $candidate, $m) === 1) {
                    $nomor = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                    $used[$j] = true;
                    break;
                }

                if (! in_array($candidate, ['satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas', 'belas', 'puluh'], true)) {
                    break;
                }

                $amount = NumberWords::fromWords(implode(' ', array_slice($tokens, $j, min(3, $count - $j))), 60);

                if ($amount !== null) {
                    $nomor = str_pad((string) $amount, 2, '0', STR_PAD_LEFT);

                    for ($k = $j; $k < min($count, $j + 3); $k++) {
                        if (in_array($tokens[$k], ['satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas', 'belas', 'puluh'], true)) {
                            $used[$k] = true;
                        }
                    }

                    break;
                }
            }

            if ($nomor === null) {
                continue;
            }

            $used[$i] = true;

            $asrama = $this->resolveAsrama($nomor, $daerahId);

            return [
                'id' => $asrama === null ? null : (int) $asrama['id'],
                'nomor' => $nomor,
                'confidence' => $asrama === null ? 0.4 : ($daerahId === null ? 0.6 : 0.95),
            ];
        }

        return ['id' => null, 'nomor' => null, 'confidence' => 0.0];
    }

    /**
     * @return array{id: int}|null
     */
    private function resolveAsrama(string $nomor, ?int $daerahId): ?array
    {
        $matches = array_values(array_filter(
            $this->master->asrama(),
            fn (array $a): bool => $a['nomor'] === $nomor && ($daerahId === null || $a['daerah_id'] === $daerahId),
        ));

        if ($daerahId !== null) {
            return $matches[0] ?? null;
        }

        return count($matches) === 1 ? $matches[0] : null;
    }

    /**
     * Token yang belum dipakai untuk isian lain, apa adanya.
     *
     * @param  list<string>  $tokens
     * @param  array<int, bool>  $used
     * @return list<string>
     */
    private function leftover(array $tokens, array $used): array
    {
        $left = [];

        foreach ($tokens as $index => $token) {
            if (! $used[$index]) {
                $left[] = $token;
            }
        }

        return $left;
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    private function stripNoise(array $tokens): array
    {
        return TextNormalizer::stripNoise($tokens, self::NON_DISCRIMINATIVE, self::NOTE_MARKERS);
    }
}

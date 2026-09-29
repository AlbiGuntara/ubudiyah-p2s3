<?php

namespace App\Http\Services\Voice;

use App\Models\Asrama;
use App\Models\Santri;
use Illuminate\Support\Facades\Cache;

/**
 * Mencari Santri yang paling mendekati nama yang diucapkan.
 *
 * Kolom `nama_panggilan` pada data produksi praktis kosong, hanya 1 dari 5.476
 * baris terisi. Karena itu kecocokan nama panggilan tidak bisa bergantung
 * pada kolom itu, melainkan ditentukan dari isi `nama`. Strategi 3 sampai 5
 * di bawah inilah yang menutup kebutuhan tersebut.
 *
 * @phpstan-type Candidate array{
 *     id: int, nama: string, nis: string, asrama_id: int|null,
 *     asrama_label: string, daerah_kode: string, score: float, strategies: list<string>
 * }
 */
class SantriNameMatcher
{
    /**
     * Bobot tiap strategi, dipakai untuk menggabungkan skor.
     *
     * @var array<string, float>
     */
    private const WEIGHTS = [
        'exact' => 1.00,
        'panggilan' => 0.98,
        'token' => 0.92,
        'nickname' => 0.88,
        'fuzzy' => 0.78,
    ];

    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $index = null;

    /**
     * @var array<int, array{nomor: string, label: string, daerah_kode: string, daerah_nama: string}>|null
     */
    private ?array $asramaMeta = null;

    /**
     * Cari kandidat Santri untuk nama yang diucapkan.
     *
     * @param  string  $query  nama yang terdengar, boleh tidak lengkap
     * @param  int|null  $asramaId  asrama yang sudah diketahui, bila ada
     * @param  string|null  $nis  NIS yang disebut LLM, dicek lebih dulu
     * @return list<Candidate>
     */
    public function match(string $query, ?int $asramaId = null, ?string $nis = null): array
    {
        // NIS dicek lebih dulu karena pencocokannya pasti, tanpa tebakan.
        // Angka yang dikarang model tidak akan cocok, sehingga pencarian
        // nama tetap dijalankan sebagai gantinya.
        if ($nis !== null) {
            $byNisLlm = $this->matchNis(TextNormalizer::canonical($nis));

            if ($byNisLlm !== null) {
                return [$byNisLlm];
            }
        }

        $querySlug = TextNormalizer::canonical($query);

        if ($querySlug === '') {
            return [];
        }

        // NIS disebut langsung, tanpa perlu fuzzy.
        $byNis = $this->matchNis($querySlug);

        if ($byNis !== null) {
            return [$byNis];
        }

        $queryTokens = explode(' ', $querySlug);
        $querySoundex = array_map(TextNormalizer::soundex(...), $queryTokens);
        $results = [];

        foreach ($this->loadIndex() as $entry) {
            $scores = $this->scoreEntry($entry, $querySlug, $queryTokens, $querySoundex);

            if ($scores === []) {
                continue;
            }

            $score = $this->combine($scores);

            if ($entry['asrama_id'] !== null && $asramaId !== null && (int) $entry['asrama_id'] === $asramaId) {
                $score = min(1.0, $score + 0.05);
            }

            if ($score < (float) config('voice.matching.candidate_threshold')) {
                continue;
            }

            $results[] = [
                'id' => (int) $entry['id'],
                'nama' => (string) $entry['nama'],
                'nis' => (string) $entry['nis'],
                'asrama_id' => $entry['asrama_id'] === null ? null : (int) $entry['asrama_id'],
                'asrama_label' => $this->asramaLabel($entry['asrama_id']),
                'daerah_kode' => $this->daerahKode($entry['asrama_id']),
                'score' => round($score, 3),
                'strategies' => array_values(array_unique(array_keys($scores))),
            ];
        }

        usort($results, fn (array $a, array $b): int => $b['score'] <=> $a['score'] ?: strcmp($a['nama'], $b['nama']));

        return array_slice($results, 0, max(3, (int) config('voice.matching.max_candidates')));
    }

    /**
     * Skor gabungan sebuah entri, mengambil strategi terkuat sebagai dasar
     * lalu menambah bonus kecil bila strategi lain juga mendukung.
     *
     * @param  array<string, float>  $scores
     */
    private function combine(array $scores): float
    {
        arsort($scores);

        $best = (float) array_shift($scores);
        $bonus = 0.0;

        foreach ($scores as $score) {
            $bonus += $score * 0.10;
        }

        return min(1.0, $best + $bonus);
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  list<string>  $queryTokens
     * @param  list<string>  $querySoundex
     * @return array<string, float>
     */
    private function scoreEntry(array $entry, string $querySlug, array $queryTokens, array $querySoundex): array
    {
        $scores = [];

        $nameSlug = (string) $entry['slug'];
        $nameTokens = $entry['tokens'];
        $nameTokens = is_array($nameTokens) ? array_values($nameTokens) : [];

        if ($querySlug === $nameSlug) {
            $scores['exact'] = self::WEIGHTS['exact'];
        }

        $panggilanSlug = $entry['slug_panggilan'] ?? '';

        if ($panggilanSlug !== '' && $querySlug === $panggilanSlug) {
            $scores['panggilan'] = self::WEIGHTS['panggilan'];
        }

        // Nama panggilan sebagai potongan nama lengkap: "Fauzi" di "Ahmad Fauzi".
        if ($this->isContiguous($queryTokens, $nameTokens)) {
            $scores['token'] = self::WEIGHTS['token'];
        }

        // Satu token yang cocok dengan nama tengah atau nama akhir.
        $nicknameScore = $this->nicknameScore($queryTokens, $nameTokens);

        if ($nicknameScore !== null) {
            $scores['nickname'] = $nicknameScore;
        }

        $fuzzyScore = $this->fuzzyScore($querySlug, $nameSlug, $querySoundex, $entry['soundex'] ?? []);

        if ($fuzzyScore !== null) {
            $scores['fuzzy'] = $fuzzyScore;
        }

        return $scores;
    }

    /**
     * Apakah seluruh token query muncul berurutan di dalam nama.
     *
     * @param  list<string>  $needle
     * @param  list<string>  $haystack
     */
    private function isContiguous(array $needle, array $haystack): bool
    {
        if ($needle === [] || $haystack === [] || count($needle) > count($haystack)) {
            return false;
        }

        $limit = count($haystack) - count($needle);

        for ($start = 0; $start <= $limit; $start++) {
            $slice = array_slice($haystack, $start, count($needle));

            if ($slice === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Skor untuk nama panggilan berupa satu token.
     *
     * Token di posisi tengah atau akhir diberi skor tinggi karena di situ nama
     * panggilan biasanya diambil. Token pertama diberi skor lebih rendah
     * karena nama depan seperti "Ahmad" sangat umum dan memicu banyak
     * kecocokan yang keliru.
     *
     * @param  list<string>  $queryTokens
     * @param  list<string>  $nameTokens
     */
    private function nicknameScore(array $queryTokens, array $nameTokens): ?float
    {
        if (count($queryTokens) !== 1 || $nameTokens === []) {
            return null;
        }

        $token = $queryTokens[0];
        $position = array_search($token, $nameTokens, true);

        if ($position === false) {
            return null;
        }

        $position = (int) $position;
        $last = count($nameTokens) - 1;

        if ($position === 0) {
            return count($nameTokens) > 1 ? 0.58 : 0.92;
        }

        if ($position === $last) {
            return self::WEIGHTS['nickname'];
        }

        return 0.84;
    }

    /**
     * Skor dari kemiripan fonetik dan jarak edit.
     *
     * @param  list<string>  $querySoundex
     */
    private function fuzzyScore(string $querySlug, string $nameSlug, array $querySoundex, mixed $nameSoundex): ?float
    {
        $nameSoundex = is_array($nameSoundex) ? array_values($nameSoundex) : [];

        if ($nameSoundex === []) {
            return null;
        }

        $phoneticHit = 0;

        foreach ($querySoundex as $code) {
            if ($code !== '' && in_array($code, $nameSoundex, true)) {
                $phoneticHit++;
            }
        }

        $coverage = $phoneticHit / max(1, count($querySoundex));

        if ($coverage === 0.0) {
            return null;
        }

        $edit = TextNormalizer::similarity($querySlug, $nameSlug);

        return self::WEIGHTS['fuzzy'] * (0.6 * $coverage + 0.4 * $edit);
    }

    /**
     * Cari persis berdasarkan NIS, baik yang disebut lengkap maupun separuh.
     *
     * @return Candidate|null
     */
    private function matchNis(string $querySlug): ?array
    {
        $digits = preg_replace('/\D/', '', $querySlug) ?? '';

        if (strlen($digits) < 4) {
            return null;
        }

        foreach ($this->loadIndex() as $entry) {
            $nis = preg_replace('/\D/', '', (string) $entry['nis']) ?? '';

            if ($nis !== '' && ($nis === $digits || str_ends_with($nis, $digits))) {
                return [
                    'id' => (int) $entry['id'],
                    'nama' => (string) $entry['nama'],
                    'nis' => (string) $entry['nis'],
                    'asrama_id' => $entry['asrama_id'] === null ? null : (int) $entry['asrama_id'],
                    'asrama_label' => $this->asramaLabel($entry['asrama_id']),
                    'daerah_kode' => $this->daerahKode($entry['asrama_id']),
                    'score' => 1.0,
                    'strategies' => ['exact'],
                ];
            }
        }

        return null;
    }

    /**
     * Indeks nama Santri, di-cache agar tidak_query database tiap rekaman
     * dan tidak pernah dikirim ke browser.
     *
     * @return list<array<string, mixed>>
     */
    private function loadIndex(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $key = (string) config('voice.cache.key');
        $ttl = (int) config('voice.cache.ttl');

        /** @var list<array<string, mixed>> $index */
        $index = Cache::remember($key, $ttl, function (): array {
            return Santri::query()
                ->orderBy('id')
                ->get(['id', 'nama', 'nama_panggilan', 'nis', 'asrama_id'])
                ->map(function (Santri $s): array {
                    $slug = TextNormalizer::canonical((string) $s->nama);
                    $panggilan = TextNormalizer::canonical((string) ($s->nama_panggilan ?? ''));
                    $tokens = $slug === '' ? [] : explode(' ', $slug);

                    return [
                        'id' => (int) $s->id,
                        'nama' => (string) $s->nama,
                        'nis' => (string) $s->nis,
                        'asrama_id' => $s->asrama_id,
                        'slug' => $slug,
                        'slug_panggilan' => $panggilan,
                        'tokens' => $tokens,
                        'soundex' => array_map(TextNormalizer::soundex(...), $tokens),
                    ];
                })
                ->all();
        });

        return $this->index = $index;
    }

    /**
     * @return array{nomor: string, label: string, daerah_kode: string, daerah_nama: string}
     */
    private function meta(int|string|null $asramaId): array
    {
        $asramaId = (int) ($asramaId ?? 0);

        if ($this->asramaMeta === null) {
            $this->asramaMeta = [];

            Asrama::query()
                ->join('daerah', 'daerah.id', '=', 'asrama.daerah_id')
                ->orderBy('asrama.id')
                ->get([
                    'asrama.id',
                    'asrama.nomor',
                    'asrama.daerah_id',
                    'daerah.kode as daerah_kode',
                    'daerah.nama_daerah',
                ])
                ->each(function (Asrama $asrama): void {
                    $kode = (string) $asrama->getAttribute('daerah_kode');
                    $nomor = str_pad((string) $asrama->nomor, 2, '0', STR_PAD_LEFT);

                    $this->asramaMeta[(int) $asrama->id] = [
                        'nomor' => $nomor,
                        'label' => $kode === '' ? 'Asrama '.$nomor : $kode.'.'.$nomor,
                        'daerah_kode' => $kode,
                        'daerah_nama' => (string) $asrama->getAttribute('nama_daerah'),
                    ];
                });
        }

        return $this->asramaMeta[(int) $asramaId] ?? [
            'nomor' => '',
            'label' => '',
            'daerah_kode' => '',
            'daerah_nama' => '',
        ];
    }

    private function asramaLabel(int|string|null $asramaId): string
    {
        return $this->meta($asramaId)['label'];
    }

    private function daerahKode(int|string|null $asramaId): string
    {
        return $this->meta($asramaId)['daerah_kode'];
    }

    /**
     * Buang cache indeks nama, dipakai setelah data Santri berubah.
     */
    public function flush(): void
    {
        Cache::forget((string) config('voice.cache.key'));

        $this->index = null;
        $this->asramaMeta = null;
    }
}

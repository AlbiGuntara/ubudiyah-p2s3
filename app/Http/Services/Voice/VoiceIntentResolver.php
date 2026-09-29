<?php

namespace App\Http\Services\Voice;

/**
 * Menggabungkan hasil parser aturan, cadangan LLM, dan pencocokan nama
 * menjadi satu draft yang siap ditampilkan di modal.
 *
 * Aturan adalah sumber utama. LLM hanya dipanggil untuk isian yang tidak
 * berhasil dibaca aturan, dan hasilnya tidak pernah menimpa isian aturan
 * yang sudah yakin. Urutan ini dipilih karena aturan gratis, instan, dan bisa
 * diuji, sedangkan panggilan LLM memakan beberapa detik dan sering ditolak
 * provider saat sibuk.
 */
class VoiceIntentResolver
{
    /**
     * Ambang di bawah ini isian dianggap belum pasti dan dicoba lewat LLM.
     */
    private const LLM_TRIGGER_CONFIDENCE = 0.60;

    public function __construct(
        private readonly RulesIntentParser $rules,
        private readonly LlmIntentParser $llm,
        private readonly SantriNameMatcher $matcher,
        private readonly VoiceMasterData $master,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolve(string $transcript): array
    {
        $parsed = $this->rules->parse($transcript);

        $llm = $this->shouldAskLlm($parsed) ? $this->llm->parse($transcript) : null;

        $merged = $this->merge($parsed, $llm);
        $santri = $this->resolveSantri($merged);

        // Asrama tidak harus diucapkan. Kalau Santri sudah pasti, asramanya
        // bisa dipakai sebagai isian supaya petugas tidak perlu memilih lagi.
        if ($merged['asrama_id'] === null
            && $santri['selected'] !== null
            && $santri['selected']['asrama_id'] !== null) {
            $merged['asrama_id'] = (int) $santri['selected']['asrama_id'];
            $merged['daerah_id'] ??= $santri['selected']['daerah_kode'] === ''
                ? null
                : $this->matchDaerah($santri['selected']['daerah_kode']);
        }

        return [
            'transcript' => $transcript,
            'anonymous' => $merged['anonymous'],
            'jumlah' => $merged['jumlah'],
            'daerah_id' => $merged['daerah_id'],
            'asrama_id' => $merged['asrama_id'],
            'asrama_nomor' => $merged['asrama_nomor'],
            'daftar_pelanggaran_ids' => $merged['daftar_pelanggaran_ids'],
            'daftar_pelanggaran_label' => $merged['daftar_pelanggaran_label'],
            'tanggal' => $merged['tanggal'],
            'keterangan' => $merged['keterangan'],
            'sumber_pencatatan' => $merged['sumber_pencatatan'],
            'santri' => $santri,
            'needs_review' => $this->needsReview($merged, $santri),
            'used_llm' => $llm !== null,
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function shouldAskLlm(array $parsed): bool
    {
        $confidence = $parsed['confidence'];

        if ($confidence['jenis'] < self::LLM_TRIGGER_CONFIDENCE) {
            return true;
        }

        return $parsed['santri_query'] === null && ! $parsed['anonymous'];
    }

    /**
     * Isian aturan menang bila yakin; LLM hanya mengisi celah.
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, mixed>|null  $llm
     * @return array<string, mixed>
     */
    private function merge(array $rules, ?array $llm): array
    {
        $daerahId = $rules['daerah_id'];
        $asramaId = $rules['asrama_id'];
        $asramaNomor = $rules['asrama_nomor'];

        if ($llm !== null) {
            if ($daerahId === null) {
                $daerahId = $this->matchDaerah((string) ($llm['daerah_text'] ?? ''));
            }

            if ($asramaId === null && $asramaNomor === null) {
                $asramaNomor = $this->normalizeNomor((string) ($llm['asrama_text'] ?? ''));
                $asramaId = $asramaNomor === null ? null : $this->resolveAsrama($asramaNomor, $daerahId);
            }

            if ($rules['daftar_pelanggaran_ids'] === [] && $llm['jenis_ids'] !== []) {
                $rules['daftar_pelanggaran_ids'] = $llm['jenis_ids'];
                $rules['daftar_pelanggaran_label'] = $llm['jenis_label'];
            }

            if ($rules['tanggal'] === null) {
                $rules['tanggal'] = $llm['tanggal'];
            }

            if ($rules['keterangan'] === null) {
                $rules['keterangan'] = $llm['keterangan'];
            }

            // Sisa kalimat yang dijadikan nama oleh aturan sebenarnya muncul
            // justru saat jenis pelanggaran belum dikenali, sehingga hampir
            // pasti bukan nama. Nama temuan LLM boleh menggantikannya.
            $jenisBelumYakin = $rules['confidence']['jenis'] < self::LLM_TRIGGER_CONFIDENCE;
            $bolehPakaiNamaLlm = $rules['santri_query'] === null || $jenisBelumYakin;

            if ($bolehPakaiNamaLlm && $llm['name'] !== null && ! $rules['anonymous_explicit']) {
                $rules['santri_query'] = $llm['name'];
                $rules['anonymous'] = false;
            }

            // NIS dipakai apa adanya, termasuk ketika nama tidak disebut.
            // NIS yang ada berarti Santri-nya sudah pasti, jadi catatan ini
            // bukan lagi tanpa nama.
            if ($llm['nis'] !== null && ! $rules['anonymous_explicit']) {
                $rules['santri_nis'] = $llm['nis'];
                $rules['anonymous'] = false;
            }

            if ($llm['anonymous']
                && $rules['santri_query'] === null
                && ! isset($rules['santri_nis'])) {
                $rules['anonymous'] = true;
            }
        }

        $rules['daerah_id'] = $daerahId;
        $rules['asrama_id'] = $asramaId;
        $rules['asrama_nomor'] = $asramaNomor;

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $merged
     * @return array{status: string, selected: array<string, mixed>|null, candidates: list<array<string, mixed>>}
     */
    private function resolveSantri(array $merged): array
    {
        if ($merged['anonymous']) {
            return ['status' => 'anonymous', 'selected' => null, 'candidates' => []];
        }

        $query = $merged['santri_query'];
        $nis = $merged['santri_nis'] ?? null;
        $nis = is_string($nis) && trim($nis) !== '' ? $nis : null;

        // NIS boleh berdiri sendiri, misalnya kalimat yang hanya menyebut
        // nomor tanpa nama.
        if (($query === null || trim((string) $query) === '') && $nis === null) {
            return ['status' => 'none', 'selected' => null, 'candidates' => []];
        }

        $candidates = $this->matcher->match(
            (string) ($query ?? ''),
            $merged['asrama_id'],
            $nis,
        );

        if ($candidates === []) {
            return ['status' => 'none', 'selected' => null, 'candidates' => []];
        }

        $top = $candidates[0];
        $runnerUp = $candidates[1]['score'] ?? 0.0;

        $confident = $top['score'] >= (float) config('voice.matching.auto_threshold')
            && ($top['score'] - $runnerUp) >= (float) config('voice.matching.min_margin');

        // Asrama yang disebutkan tidak boleh diabaikan. Kalau Santri terbaik
        // ada di asrama lain, nama yang sama persis di asrama yang diminta
        // mungkin tidak terdengar, sehingga hasil terbaik justru milik kamar
        // sebelah. Pilihan otomatis lalu ditahan untuk diperiksa petugas.
        $asramaDiminta = $merged['asrama_id'];
        $asramaBeda = $asramaDiminta !== null && $top['asrama_id'] !== $asramaDiminta;

        if (! $confident || $asramaBeda) {
            return ['status' => 'candidates', 'selected' => null, 'candidates' => $candidates];
        }

        return ['status' => 'auto', 'selected' => $top, 'candidates' => $candidates];
    }

    /**
     * Daftar isian yang perlu diperiksa petugas sebelum disimpan.
     *
     * @param  array<string, mixed>  $merged
     * @param  array{status: string, selected: array<string, mixed>|null, candidates: list<array<string, mixed>>}  $santri
     * @return list<string>
     */
    private function needsReview(array $merged, array $santri): array
    {
        $review = [];

        if ($merged['daftar_pelanggaran_ids'] === []) {
            $review[] = 'jenis';
        }

        if ($merged['asrama_id'] === null) {
            $review[] = 'asrama';
        }

        if (! $merged['anonymous'] && in_array($santri['status'], ['candidates', 'none'], true)) {
            $review[] = 'santri';
        }

        return $review;
    }

    private function matchDaerah(string $text): ?int
    {
        $needle = TextNormalizer::canonical($text);

        if ($needle === '') {
            return null;
        }

        foreach ($this->master->daerah() as $daerah) {
            if (TextNormalizer::canonical($daerah['kode']) === $needle
                || TextNormalizer::canonical($daerah['nama']) === $needle) {
                return (int) $daerah['id'];
            }
        }

        return null;
    }

    private function normalizeNomor(string $text): ?string
    {
        $digits = preg_replace('/\D/', '', $text) ?? '';

        if ($digits === '' || strlen($digits) > 2) {
            return null;
        }

        return str_pad($digits, 2, '0', STR_PAD_LEFT);
    }

    private function resolveAsrama(string $nomor, ?int $daerahId): ?int
    {
        $matches = array_values(array_filter(
            $this->master->asrama(),
            fn (array $a): bool => $a['nomor'] === $nomor && ($daerahId === null || $a['daerah_id'] === $daerahId),
        ));

        if ($daerahId !== null) {
            return $matches === [] ? null : (int) $matches[0]['id'];
        }

        return count($matches) === 1 ? (int) $matches[0]['id'] : null;
    }
}

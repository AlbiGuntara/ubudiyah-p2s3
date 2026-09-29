<?php

namespace App\Http\Services\Voice;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cadangan berbasis LLM untuk kalimat yang tidak bisa dibaca aturan.
 *
 * Model hanya boleh mengembalikan teks, bukan id. Pemetaan teks ke id tetap
 * dilakukan di server memakai daftar tertutup dari master data, sehingga model
 * tidak mungkin mengarang nilai yang tidak ada. Dari uji langsung, Gemini
 * cenderung jawaban seperti "tidak memakai jubah saat kiper" untuk kalimat yang
 * sebenarnya berarti "Tidak Jubah/Gamis Isya", jadi pemetaan ini bukan
 * formalitas.
 *
 * Kegagalan tidak pernah menggagalkan pemrosesan. Hasil null berarti aturan
 * yang dipakai, dan petugas tetap bisa mengoreksi isian di modal.
 */
class LlmIntentParser
{
    public function __construct(private readonly VoiceMasterData $master) {}

    /**
     * @return array{
     *     jenis_label: string|null, jenis_ids: list<int>, name: string|null,
     *     nis: string|null, daerah_text: string|null, asrama_text: string|null,
     *     tanggal: string|null, keterangan: string|null, anonymous: bool
     * }|null
     */
    public function parse(string $transcript): ?array
    {
        $key = (string) config('voice.llm.key');

        if ($key === '' || ! (bool) config('voice.enabled')) {
            return null;
        }

        $body = $this->send($key, $transcript);

        if ($body === null) {
            return null;
        }

        $raw = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $data = json_decode($raw, true);

        if (! is_array($data)) {
            return null;
        }

        return $this->normalize($data);
    }

    /**
     * Kirim permintaan, mengulang hanya bila kena rate limit.
     *
     * Pengamatan dari uji langsung: Gemini sering membalas 503 "high demand"
     * dan percobaan ulang seketika jarang menolong, sedangkan 429 rate limit
     * membaik setelah jeda. Karena parser ini hanya cadangan, batas waktu
     * dibuat pendek supaya petugas tidak menunggu lama, dan kegagalan
     *diteruskan sebagai null agar isian aturan tetap dipakai.
     *
     * @return array<string, mixed>|null
     */
    private function send(string $key, string $transcript): ?array
    {
        $url = rtrim((string) config('voice.llm.base_url'), '/')
            .'/models/'.rawurlencode((string) config('voice.llm.model')).':generateContent';

        $payload = [
            'contents' => [['parts' => [['text' => $this->instruction($transcript)]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0,
                'responseSchema' => $this->schema(),
            ],
        ];

        $attempts = 2;

        for ($attempt = 1; ; $attempt++) {
            try {
                $response = Http::withHeaders(['x-goog-api-key' => $key])
                    ->timeout((int) config('voice.llm.timeout'))
                    ->post($url, $payload);
            } catch (Throwable $e) {
                Log::warning('LLM voice fallback gagal: '.$e->getMessage());

                return null;
            }

            if ($response->successful()) {
                $body = $response->json();

                return is_array($body) ? $body : null;
            }

            $status = $response->status();
            $retryable = $status === 429 || $status === 500 || $status === 502;

            if ($attempt >= $attempts || ! $retryable) {
                Log::warning('LLM voice fallback HTTP '.$status.': '.$response->body());

                return null;
            }

            usleep(500_000);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     jenis_label: string|null, jenis_ids: list<int>, name: string|null,
     *     nis: string|null, daerah_text: string|null, asrama_text: string|null,
     *     tanggal: string|null, keterangan: string|null, anonymous: bool
     * }
     */
    private function normalize(array $data): array
    {
        $jenisLabel = $this->text($data, 'jenis_pelanggaran');
        $jenis = $jenisLabel === null ? null : $this->matchJenis($jenisLabel);

        return [
            'jenis_label' => $jenis['nama'] ?? null,
            'jenis_ids' => $jenis['ids'] ?? [],
            'name' => $this->text($data, 'nama_santri'),
            'nis' => $this->text($data, 'nis'),
            'daerah_text' => $this->text($data, 'daerah'),
            'asrama_text' => $this->text($data, 'asrama'),
            'tanggal' => $this->date($data),
            'keterangan' => $this->text($data, 'keterangan'),
            'anonymous' => ($data['tanpa_nama'] ?? false) === true,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function text(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * Tanggal dari LLM hanya diterima kalau benar-benar ada di kalender,
     * karena model sering mengarang tanggal yang formatnya terlihat sah.
     *
     * @param  array<string, mixed>  $data
     */
    private function date(array $data): ?string
    {
        $value = $this->text($data, 'tanggal');

        if ($value === null || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) !== 1) {
            return null;
        }

        [, $year, $month, $day] = $m;

        if (! checkdate((int) $month, (int) $day, (int) $year)) {
            return null;
        }

        return $value;
    }

    /**
     * Cocokkan hasil LLM ke daftar master.
     *
     * @return array{ids: list<int>, nama: string}|null
     */
    private function matchJenis(string $label): ?array
    {
        $needle = TextNormalizer::canonical($label);

        if ($needle === '') {
            return null;
        }

        $best = null;

        foreach ($this->master->daftarPelanggaran() as $jenis) {
            $name = TextNormalizer::canonical($jenis['nama']);
            $score = 0.6 * TextNormalizer::tokenCoverage(
                explode(' ', $needle),
                explode(' ', $name),
            ) + 0.4 * TextNormalizer::similarity($needle, $name);

            if ($best === null || $score > $best['score'] + 0.0001) {
                $best = ['ids' => [(int) $jenis['id']], 'nama' => $jenis['nama'], 'score' => $score];
            } elseif (abs($score - $best['score']) < 0.0001) {
                $best['ids'][] = (int) $jenis['id'];
            }
        }

        if ($best === null || $best['score'] < 0.45) {
            return null;
        }

        return ['ids' => array_values(array_unique($best['ids'])), 'nama' => $best['nama']];
    }

    private function instruction(string $transcript): string
    {
        $jenis = implode(' | ', array_map(
            fn (array $j): string => $j['nama'],
            $this->master->daftarPelanggaran(),
        ));

        // Nomor asrama diulang di tiap daerah, jadi labelnya ikut disertakan
        // supaya model bisa melihat bahwa "asrama 3" tanpa daerah tidak
        // menentukan satu asrama.
        $asrama = implode(' | ', array_map(
            fn (array $a): string => $a['label'],
            $this->master->asrama(),
        ));

        $daerah = implode(' | ', array_map(
            fn (array $d): string => $d['kode'].'='.$d['nama'],
            $this->master->daerah(),
        ));

        return <<<PROMPT
        Petugas sedang mencatat pelanggaran Santri di pesantren. Pecah kalimat yang
        didengar menjadi isian formulir.

        aturan:
        - jenis_pelanggaran HARUS dipilih ulang dari daftar ini, tulis persis seperti
          tertulis di daftar, jangan mengarang: {$jenis}
        - daerah HARUS salah satu dari daftar ini, boleh kode atau nama: {$daerah}
        - asrama ditulis sebagai nomor saja, misalnya 07. Nomor asrama berulang di
          tiap daerah, jadi kalau daerah tidak disebut biarkan null: {$asrama}
        - tanggal format YYYY-MM-DD, null kalau tidak disebut
        - tanpa_nama true bila memang tidak ada nama yang disebut
        - isian yang tidak ada ditulis null, jangan ditebak
        - buang kata pengisi seperti "nama" dan "tolong catat"

        Kalimat yang didengar: {$transcript}
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'jenis_pelanggaran' => $string,
                'nama_santri' => $string,
                'nis' => $string,
                'daerah' => $string,
                'asrama' => $string,
                'tanggal' => $string,
                'keterangan' => $string,
                'tanpa_nama' => ['type' => 'BOOLEAN'],
            ],
            'required' => [
                'jenis_pelanggaran',
                'nama_santri',
                'nis',
                'daerah',
                'asrama',
                'tanggal',
                'keterangan',
                'tanpa_nama',
            ],
        ];
    }
}

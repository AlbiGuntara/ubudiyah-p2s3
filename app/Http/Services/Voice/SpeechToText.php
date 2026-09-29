<?php

namespace App\Http\Services\Voice;

use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mengubah rekaman suara menjadi teks memakai Groq Whisper.
 *
 * Memakai HTTP client bawaan Laravel dengan endpoint OpenAI-compatible,
 * sehingga tidak ada SDK yang perlu dipasang dan provider cukup ditukar
 * lewat .env.
 */
class SpeechToText
{
    /**
     * @return array{text: string, model: string, duration: float|null, confidence: float|null}
     *
     * @throws VoiceTranscriptionException
     */
    public function transcribe(UploadedFile $audio): array
    {
        $key = (string) config('voice.stt.key');

        if ($key === '') {
            throw VoiceTranscriptionException::notConfigured();
        }

        $path = $audio->getRealPath();

        if ($path === false || ! is_readable($path)) {
            throw VoiceTranscriptionException::unreadable();
        }

        // Dipakai lewat nama file, bukan handle resource, supaya PHPUnit
        // bisa mengunci berkas rekaman sementara pada pengujian.
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw VoiceTranscriptionException::unreadable();
        }

        $response = $this->send($contents, $audio->guessExtension() ?: 'webm');

        if ($response === null) {
            throw VoiceTranscriptionException::serviceUnavailable();
        }

        if ($response->failed()) {
            Log::warning('Voice transcription ditolak provider', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Kunci ditolak tidak akan membaik dengan dicoba ulang, jadi
            // percobaan dihentikan lebih dulu dan pesan dibuat berbeda.
            if (in_array($response->status(), [401, 403], true)) {
                throw VoiceTranscriptionException::rejected();
            }

            throw $response->status() === 429
                ? new VoiceTranscriptionException('Layanan suara sedang ramai. Tunggu sebentar lalu ulangi.')
                : VoiceTranscriptionException::serviceUnavailable();
        }

        $text = TextNormalizer::squish((string) $response->json('text', ''));

        if ($text === '') {
            throw VoiceTranscriptionException::empty();
        }

        return [
            'text' => $text,
            'model' => (string) $response->json('model', config('voice.stt.model')),
            'duration' => $this->averageDuration($response->json('segments', [])),
            'confidence' => $this->averageConfidence($response->json('segments', [])),
        ];
    }

    /**
     * Kirim rekaman ke provider, dengan pengulangan singkat untuk gangguan
     * jaringan sesaat seperti resolver DNS yang sempat tidak merespons.
     *
     * Hanya kegagalan jaringan dan status sementara yang diulang. Kunci yang
     * ditolak langsung dihentikan, karena mengulangnya tidak akan membantu.
     */
    private function send(string $contents, string $extension): ?Response
    {
        $attempts = max(1, (int) config('voice.stt.retry.attempts'));
        $backoff = max(0, (int) config('voice.stt.retry.backoff_ms')) * 1000;
        $url = rtrim((string) config('voice.stt.base_url'), '/').'/audio/transcriptions';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::withToken((string) config('voice.stt.key'))
                    ->timeout((int) config('voice.stt.timeout'))
                    ->asMultipart()
                    ->attach('file', $contents, 'rekaman.'.$extension)
                    ->post($url, [
                        'model' => (string) config('voice.stt.model'),
                        'language' => (string) config('voice.stt.language'),
                        'temperature' => 0,
                        'response_format' => 'verbose_json',
                        'prompt' => (string) config('voice.biasing_prompt'),
                    ]);

                if (! $this->shouldRetry($response->status()) || $attempt === $attempts) {
                    return $response;
                }

                Log::warning('Voice transcription diulang', [
                    'attempt' => $attempt,
                    'status' => $response->status(),
                ]);
            } catch (Throwable $e) {
                Log::warning('Voice transcription gagal: '.$e->getMessage(), [
                    'attempt' => $attempt,
                ]);

                if ($attempt === $attempts) {
                    return null;
                }
            }

            if ($backoff > 0) {
                usleep($backoff);
            }
        }

        return null;
    }

    /**
     * Status yang biasanya bersifat sementara: 429, 408, dan 5xx.
     */
    private function shouldRetry(int $status): bool
    {
        return $status === 429 || $status === 408 || $status >= 500;
    }

    /**
     * Rata-rata durasi segmen, dipakai sebagai sinyal kualitas rekaman.
     */
    private function averageDuration(mixed $segments): ?float
    {
        if (! is_array($segments) || $segments === []) {
            return null;
        }

        $total = 0.0;

        foreach ($segments as $segment) {
            if (is_array($segment) && isset($segment['end'], $segment['start'])) {
                $total += (float) $segment['end'] - (float) $segment['start'];
            }
        }

        return $total > 0 ? round($total, 2) : null;
    }

    /**
     * Rata-rata avg_logprob dari Whisper. Nilai mendekati nol berarti
     * transkripsi sangat yakin, jauh di bawah nol berarti ragu.
     */
    private function averageConfidence(mixed $segments): ?float
    {
        if (! is_array($segments) || $segments === []) {
            return null;
        }

        $values = [];

        foreach ($segments as $segment) {
            if (is_array($segment) && isset($segment['avg_logprob'])) {
                $values[] = (float) $segment['avg_logprob'];
            }
        }

        if ($values === []) {
            return null;
        }

        return round(array_sum($values) / count($values), 3);
    }
}

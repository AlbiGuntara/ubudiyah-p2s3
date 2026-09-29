<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

/**
 * Memastikan berkas unggahan benar-benar rekaman suara.
 *
 * Aturan bawaan `mimes` dan `mimetypes` sama-sama mempercayai Content-Type
 * yang dikirim klien. Perekam `MediaRecorder` di peramban mengirim
 * `audio/webm;codecs=opus` atau `application/octet-stream`, sehingga kedua
 * aturan itu menolak rekaman yang isinya memang audio. Aturan ini memeriksa
 * magic bytes berkas, bukan header yang diklaim klien.
 */
class AudioRecording implements ValidationRule
{
    /**
     * Tipe hasil pembacaan `finfo` yang diterima.
     *
     * @var list<string>
     */
    private const ALLOWED_MIME_TYPES = [
        'audio/webm',
        'video/webm',
        'audio/mpeg',
        'audio/mp3',
        'audio/mp4',
        'video/mp4',
        'audio/x-m4a',
        'video/x-m4v',
        'audio/wav',
        'audio/x-wav',
        'audio/vnd.wave',
        'audio/wave',
        'audio/x-pn-wav',
        'audio/ogg',
        'video/ogg',
        'application/ogg',
        'audio/aac',
        'audio/x-aac',
        'video/quicktime',
        'audio/flac',
        'audio/x-flac',
        'application/x-ogg',
    ];

    /**
     * Awal berkas (magic bytes) untuk format yang `finfo` sering gagal
     * dikenali, terutama rekaman `MediaRecorder` yang hanya berisi
     * beberapa frame.
     *
     * @var array<string, list<string>>
     */
    private const MAGIC_BYTES = [
        // EBML, penanda berkas webm/mkv.
        "\x1a\x45\xdf\xa3" => ['audio/webm', 'video/webm', 'video/x-matroska'],
        'ID3' => ['audio/mpeg', 'audio/mp3'],
        'OggS' => ['audio/ogg', 'video/ogg', 'application/ogg', 'application/x-ogg'],
        'fLaC' => ['audio/flac', 'audio/x-flac'],
        'FORM' => ['audio/x-aiff', 'audio/aiff'],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('Rekaman suara tidak valid.');

            return;
        }

        $path = $value->getRealPath();

        if ($path === false || ! is_readable($path)) {
            $fail('Rekaman suara tidak dapat dibaca.');

            return;
        }

        if (! $this->isAllowedAudio($path)) {
            $fail('Format rekaman tidak didukung. Gunakan perekam bawaan HP atau komputer.');
        }
    }

    private function isAllowedAudio(string $path): bool
    {
        $head = (string) @file_get_contents($path, false, null, 0, 64);

        if ($head === '') {
            return false;
        }

        foreach (self::MAGIC_BYTES as $signature => $types) {
            if (str_starts_with($head, $signature)) {
                return $this->anyAllowed($types);
            }
        }

        if (str_starts_with($head, 'RIFF')) {
            return substr($head, 8, 4) === 'WAVE';
        }

        // MP3 tanpa tag ID3 diawali frame sync 0xFFEx atau 0xFFFx.
        if (preg_match('/^\xFF[\xE0-\xFF]/', $head) === 1) {
            return true;
        }

        return $this->anyAllowed([$this->sniff($path)]);
    }

    private function sniff(string $path): string
    {
        try {
            return (string) MimeTypes::getDefault()->guessMimeType($path);
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * @param  list<string>  $types
     */
    private function anyAllowed(array $types): bool
    {
        foreach ($types as $type) {
            if ($type !== '' && in_array($type, self::ALLOWED_MIME_TYPES, true)) {
                return true;
            }
        }

        return false;
    }
}

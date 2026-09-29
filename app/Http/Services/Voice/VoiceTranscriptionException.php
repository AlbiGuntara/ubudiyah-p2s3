<?php

namespace App\Http\Services\Voice;

use RuntimeException;

/**
 * Kegagalan saat mengubah rekaman suara menjadi teks.
 *
 * Pesan yang sama ditampilkan ke pengguna, sehingga isinya ditulis dalam
 * bahasa awam, bukan istilah teknis.
 */
class VoiceTranscriptionException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Fitur voice belum dikonfigurasi. Hubungi administrator.');
    }

    public static function unreadable(): self
    {
        return new self('Rekaman tidak dapat dibaca. Silakan ulangi.');
    }

    public static function empty(): self
    {
        return new self('Tidak ada suara yang tertangkap. Silakan ulangi lebih dekat ke mikrofon.');
    }

    public static function serviceUnavailable(): self
    {
        return new self('Layanan suara sedang tidak tersedia. Silakan coba lagi.');
    }

    /**
     * Kunci ditolak atau akses dicabut. Ini bukan gangguan sementara, jadi
     * pesan dibuat berbeda agar petugas tidak menunggu percobaan yang tak
     * akan pernah berhasil.
     */
    public static function rejected(): self
    {
        return new self('Layanan suara ditolak karena konfigurasi tidak valid. Hubungi administrator.');
    }
}

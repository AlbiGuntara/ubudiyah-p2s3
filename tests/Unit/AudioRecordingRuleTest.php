<?php

use App\Rules\AudioRecording;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

pest()->uses(TestCase::class)->in('Unit');

/**
 * Rakit berkas rekaman sungguhan: magic bytes format diikuti isi apa pun.
 */
function berkas(string $extensi, string $isi): UploadedFile
{
    return UploadedFile::fake()->createWithContent('blob', $isi);
}

function terValidasi(UploadedFile $file): bool
{
    $ditolak = false;
    (new AudioRecording)->validate('audio', $file, function () use (&$ditolak) {
        $ditolak = true;
    });

    return ! $ditolak;
}

it('menerima berkas rekaman yang nama berklientnya tidak punya ekstensi', function (string $nama, string $isi) {
    expect(terValidasi(berkas($nama, $isi)))->toBeTrue();
})->with([
    'webm opus' => ['webm', "\x1a\x45\xdf\xa3".str_repeat("\x00", 512)],
    'mp4 dari Safari' => ['mp4', "\x00\x00\x00\x20ftypM4A "],
    'mp3 bertag ID3' => ['mp3', 'ID3'."\x00\x00\x00\x00\x00\x00".str_repeat("\x00", 512)],
    'ogg' => ['ogg', 'OggS'."\x00\x02".str_repeat("\x00", 512)],
    'wav' => ['wav', 'RIFF'."\x24\x00\x00\x00".'WAVEfmt '],
    'flac' => ['flac', 'fLaC'."\x00\x00\x00\x22"],
]);

it('menerima mp3 yang tidak memakai tag ID3', function () {
    // Frame sync MPEG: 11 set bit lalu tiga bit couche/layer.
    expect(terValidasi(berkas('mp3', "\xff\xfb\x90\x00".str_repeat("\x00", 512))))->toBeTrue();
});

it('menolak berkas yang bukan rekaman audio', function (string $nama, string $isi) {
    expect(terValidasi(berkas($nama, $isi)))->toBeFalse();
})->with([
    'skrip php' => ['php', "<?php system('id');"],
    'teks biasa' => ['txt', 'ini bukan audio sama sekali'],
    'berkas kosong' => ['webm', ''],
    'RIFF yang bukan WAVE' => ['avi', 'RIFF'."\x00\x00\x00\x00".'AVI LIST'],
]);

it('menolak berkas kosong walau klien mengaku audio', function () {
    // Klien boleh mengklaim apa saja lewat header; yang menentukan adalah
    // isi berkasnya.
    $file = UploadedFile::fake()->createWithContent('blob', '');

    expect(terValidasi($file))->toBeFalse();
});

<?php

use App\Models\Asrama;
use App\Models\Daerah;
use App\Models\DaftarPelanggaran;
use App\Models\Pelanggaran;
use App\Models\Pembinaan;
use App\Models\Santri;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo(Permission::findOrCreate('create_pelanggaran'));

    $this->daerah = Daerah::factory()->create(['kode' => 'X', 'nama_daerah' => 'Daerah Uji']);
    $this->asramaA = Asrama::factory()->create(['daerah_id' => $this->daerah->id, 'nomor' => '01']);
    $this->asramaB = Asrama::factory()->create(['daerah_id' => $this->daerah->id, 'nomor' => '02']);
    $this->jenis = DaftarPelanggaran::factory()->create(['nama_pelanggaran' => 'Telat']);
});

it('menyimpan asrama per baris sesuai isian masing-masing', function () {
    $santriA = Santri::factory()->create(['asrama_id' => $this->asramaA->id]);
    $santriB = Santri::factory()->create(['asrama_id' => $this->asramaB->id]);

    $this->actingAs($this->user)->post('/pelanggaran', [
        // Nilai tingkat form sengaja diisi asrama A. Baris kedua harus tetap
        // memakai asramanya sendiri, bukan nilai form.
        'asrama_id' => $this->asramaA->id,
        'sumber_pencatatan' => 'petugas',
        'keterangan' => 'keterangan tingkat form',
        'santri_pelanggaran' => [
            [
                'santri_id' => $santriA->id,
                'daftar_pelanggaran_id' => $this->jenis->id,
                'tanggal' => '2026-09-29',
                'asrama_id' => $this->asramaA->id,
                'sumber_pencatatan' => 'ketua_kamar',
                'keterangan' => 'baris pertama',
            ],
            [
                'santri_id' => $santriB->id,
                'daftar_pelanggaran_id' => $this->jenis->id,
                'tanggal' => '2026-09-29',
                'asrama_id' => $this->asramaB->id,
                'sumber_pencatatan' => 'petugas',
                'keterangan' => 'baris kedua',
            ],
        ],
    ])->assertRedirect();

    $baris = Pelanggaran::orderBy('id')->get();

    expect($baris)->toHaveCount(2);
    expect($baris[0]->asrama_id)->toBe($this->asramaA->id);
    expect($baris[0]->sumber_pencatatan)->toBe('ketua_kamar');
    expect($baris[0]->keterangan)->toBe('baris pertama');
    expect($baris[1]->asrama_id)->toBe($this->asramaB->id);
    expect($baris[1]->sumber_pencatatan)->toBe('petugas');
    expect($baris[1]->keterangan)->toBe('baris kedua');
});

it('tetap memakai asrama tingkat form saat baris tidak mengirim asrama', function () {
    // Ini jalur pencatatan manual, yang tidak mengirim asrama per baris.
    $santri = Santri::factory()->create(['asrama_id' => $this->asramaA->id]);

    $this->actingAs($this->user)->post('/pelanggaran', [
        'asrama_id' => $this->asramaB->id,
        'sumber_pencatatan' => 'petugas',
        'keterangan' => 'catatan form',
        'santri_pelanggaran' => [
            [
                'santri_id' => $santri->id,
                'daftar_pelanggaran_id' => $this->jenis->id,
                'tanggal' => '2026-09-29',
            ],
        ],
    ])->assertRedirect();

    $baris = Pelanggaran::first();

    expect($baris->asrama_id)->toBe($this->asramaB->id);
    expect($baris->keterangan)->toBe('catatan form');
});

it('menyimpan baris tanpa nama di asrama yang berbeda dan menyinkronkan pembinaan tiap asrama', function () {
    $this->actingAs($this->user)->post('/pelanggaran', [
        'asrama_id' => $this->asramaA->id,
        'sumber_pencatatan' => 'petugas',
        'anonymous_entries' => [
            [
                'jumlah' => 3,
                'daftar_pelanggaran_id' => $this->jenis->id,
                'tanggal' => '2026-09-29',
                'asrama_id' => $this->asramaA->id,
            ],
            [
                'jumlah' => 2,
                'daftar_pelanggaran_id' => $this->jenis->id,
                'tanggal' => '2026-09-29',
                'asrama_id' => $this->asramaB->id,
            ],
        ],
    ])->assertRedirect();

    $baris = Pelanggaran::orderBy('id')->get();

    expect($baris)->toHaveCount(2);
    expect($baris[0]->asrama_id)->toBe($this->asramaA->id);
    expect($baris[0]->jumlah)->toBe(3);
    expect($baris[0]->santri_id)->toBeNull();
    expect($baris[1]->asrama_id)->toBe($this->asramaB->id);
    expect($baris[1]->jumlah)->toBe(2);

    // Pembinaan harus dibuat untuk kedua asrama, bukan hanya asrama pertama.
    $pembinaan = Pembinaan::pluck('asrama_id')->all();

    expect($pembinaan)->toContain($this->asramaA->id)
        ->and($pembinaan)->toContain($this->asramaB->id);
});

it('menolak asrama per baris yang tidak dikenal', function () {
    $santri = Santri::factory()->create(['asrama_id' => $this->asramaA->id]);

    $this->actingAs($this->user)->post('/pelanggaran', [
        'asrama_id' => $this->asramaA->id,
        'sumber_pencatatan' => 'petugas',
        'santri_pelanggaran' => [
            [
                'santri_id' => $santri->id,
                'daftar_pelanggaran_id' => $this->jenis->id,
                'tanggal' => '2026-09-29',
                'asrama_id' => 99999,
            ],
        ],
    ])->assertSessionHasErrors('santri_pelanggaran.0.asrama_id');

    expect(Pelanggaran::count())->toBe(0);
});

it('menyimpan rekaman voice yang seluruh barisnya tanpa nama', function () {
    // Tabel voice bisa berisi baris tanpa nama saja. Asrama tingkat form
    // tetap wajib oleh validasi, jadi klien mengirim asrama baris pertama
    // sebagai cadangan walau tidak ada Santri yang dipilih.
    $response = $this->actingAs($this->user)->post('/pelanggaran', [
        'asrama_id' => $this->asramaB->id,
        'sumber_pencatatan' => 'ketua_kamar',
        'keterangan' => null,
        'santri_pelanggaran' => [],
        'anonymous_entries' => [
            [
                'jumlah' => 3,
                'daftar_pelanggaran_id' => $this->jenis->id,
                'tanggal' => '2026-09-29',
                'asrama_id' => $this->asramaB->id,
                'sumber_pencatatan' => 'ketua_kamar',
                'keterangan' => 'tiga orang tidak hadir',
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $baris = Pelanggaran::first();

    expect($baris->santri_id)->toBeNull();
    expect($baris->jumlah)->toBe(3);
    expect($baris->asrama_id)->toBe($this->asramaB->id);
    expect($baris->sumber_pencatatan)->toBe('ketua_kamar');
    expect($baris->keterangan)->toBe('tiga orang tidak hadir');

    // Pembinaan ikut tersinkron di asrama baris tanpa nama tersebut.
    expect(Pembinaan::where('asrama_id', $this->asramaB->id)->exists())->toBeTrue();
});

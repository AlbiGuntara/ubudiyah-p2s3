<?php

use App\Http\Services\Voice\SantriNameMatcher;
use App\Http\Services\Voice\VoiceMasterData;
use App\Models\Asrama;
use App\Models\Daerah;
use App\Models\DaftarPelanggaran;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;

/**
 * Rekaman uji menyerupai hasil `MediaRecorder` di peramban: magic bytes
 * EBML/webm dan Content-Type `audio/webm;codecs=opus`, yang justru ditolak
 * aturan `mimes` bawaan Laravel.
 */
function rekamanUji(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('blob', "\x1a\x45\xdf\xa3".str_repeat("\x00", 512));
}

function transkrip(string $teks): void
{
    Http::fake([
        'api.groq.com/*' => Http::response(['text' => $teks], 200),
    ]);
}

/**
 * Berapa kali request benar-benar dikirim ke Groq, dipakai untuk memastikan
 * pengulangan percobaan bekerja dan tidak berlebihan.
 */
function jumlahKeGroq(): int
{
    $terkirim = Http::recorded()->filter(
        fn (array $pasangan): bool => str_contains($pasangan[0]->url(), 'api.groq.com'),
    );

    return $terkirim->count();
}

function masterUji(): array
{
    if (isset($GLOBALS['masterUji'])) {
        return $GLOBALS['masterUji'];
    }

    $daerah = Daerah::factory()->create(['kode' => 'X', 'nama_daerah' => 'Daerah Uji']);

    $GLOBALS['masterUji'] = [
        'daerah' => $daerah,
        'asrama' => Asrama::factory()->create(['daerah_id' => $daerah->id, 'nomor' => '07']),
    ];

    return $GLOBALS['masterUji'];
}

function balasanLlm(array $payload)
{
    return Http::response([
        'candidates' => [[
            'content' => ['parts' => [['text' => json_encode($payload)]]],
        ]],
    ], 200);
}

function muatanLlm(array $override = []): array
{
    return array_merge([
        'jenis_pelanggaran' => null,
        'nama_santri' => null,
        'nis' => null,
        'daerah' => null,
        'asrama' => null,
        'tanggal' => null,
        'keterangan' => null,
        'tanpa_nama' => false,
    ], $override);
}

beforeEach(function () {
    config([
        'voice.enabled' => true,
        'voice.stt.key' => 'test-key',
        'voice.llm.key' => 'test-key',
    ]);

    // Tanpa ini, test yang tidak mem-fake LLM akan benar-benar memanggil
    // Gemini ke internet, sehingga hasil test ikut bergantung pada koneksi.
    Http::preventStrayRequests();

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(
        Permission::findOrCreate('create_pelanggaran')
    );

    // Cache master bertahan antar-request karena VoiceMasterData diikat
    // sebagai singleton, jadi harus dibuang sebelum tiap test agar data
    // hasil factory tidak tertimpa hasil test sebelumnya.
    app(VoiceMasterData::class)->flush();
    app(SantriNameMatcher::class)->flush();
    Cache::forget((string) config('voice.cache.key'));
    unset($GLOBALS['masterUji']);
});

it('menolak berkas yang bukan rekaman audio', function () {
    transkrip('nama fauzi tidak jubah gamis isya');

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => UploadedFile::fake()->createWithContent('jahat.php', "<?php system('id');"),
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('audio');
    Http::assertNothingSent();
});

it('menerima rekaman webm yang tidak punya ekstensi', function () {
    transkrip('nama fauzi tidak jubah gamis isya');

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    $response->assertOk()->assertJsonStructure(['draft' => [
        'transcript', 'daftar_pelanggaran_ids', 'daftar_pelanggaran_label',
        'santri', 'needs_review', 'used_llm',
    ]]);
});

it('menolak pengguna tanpa izin membuat pelanggaran', function () {
    transkrip('nama fauzi tidak jubah gamis isya');

    $this->actingAs(User::factory()->create())
        ->post(route('pelanggaran.voice'), ['audio' => rekamanUji()])
        ->assertForbidden();
});

it('mengembalikan pesan ramah saat provider voice sedang-limiting', function () {
    Http::fake(['api.groq.com/*' => Http::response(['error' => 'rate_limit'], 429)]);

    $this->actingAs($this->user)
        ->post(route('pelanggaran.voice'), ['audio' => rekamanUji()])
        ->assertStatus(422)
        ->assertJsonStructure(['message']);
});

it('mengembalikan pesan ramah saat transkrip kosong', function () {
    Http::fake(['api.groq.com/*' => Http::response(['text' => '   '], 200)]);

    $this->actingAs($this->user)
        ->post(route('pelanggaran.voice'), ['audio' => rekamanUji()])
        ->assertStatus(422)
        ->assertJsonStructure(['message']);
});

it('mengulangi percobaan saat provider gagal sementara lalu berhasil', function () {
    Http::fake([
        'api.groq.com/*' => Http::sequence()
            ->push(['error' => 'server_error'], 503)
            ->push(['text' => 'nama fauzi telat jam subuh'], 200),
        'generativelanguage.googleapis.com/*' => balasanLlm(muatanLlm()),
    ]);

    $this->actingAs($this->user)
        ->post(route('pelanggaran.voice'), ['audio' => rekamanUji()])
        ->assertOk()
        ->assertJsonPath('draft.transcript', 'nama fauzi telat jam subuh');

    // Dua percobaan ke Groq: yang pertama ditolak 503, lalu diulang.
    expect(jumlahKeGroq())->toBe(2);
});

it('tidak mengulang percobaan saat kunci ditolak', function () {
    Http::fake(['api.groq.com/*' => Http::response(['error' => 'invalid_api_key'], 401)]);

    $this->actingAs($this->user)
        ->post(route('pelanggaran.voice'), ['audio' => rekamanUji()])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Layanan suara ditolak karena konfigurasi tidak valid. Hubungi administrator.']);

    // Kunci yang salah tidak akan membaik dengan dicoba ulang.
    expect(jumlahKeGroq())->toBe(1);
});

it('menyusun draft dari rules tanpa memanggil LLM', function () {
    masterUji();
    DaftarPelanggaran::factory()->create([
        'nama_pelanggaran' => 'Tidak Jubah/Gamis Isya\'',
    ]);
    Santri::factory()->create(['nama' => 'Fauzi Amanah', 'asrama_id' => masterUji()['asrama']->id]);

    transkrip('nama fauzi tidak jubah gamis isya');

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    $response->assertOk()
        ->assertJsonPath('draft.transcript', 'nama fauzi tidak jubah gamis isya')
        ->assertJsonPath('draft.used_llm', false);

    expect($response->json('draft.daftar_pelanggaran_label'))->toBe("Tidak Jubah/Gamis Isya'");
    expect($response->json('draft.daftar_pelanggaran_ids'))->toHaveCount(1);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'generativelanguage'));
});

it('memakai LLM hanya saat rules tidak menemukan jenis', function () {
    masterUji();
    DaftarPelanggaran::factory()->create(['nama_pelanggaran' => 'Tidak Jubah/Gamis Subuh']);

    transkrip('nama bilaluddin tidak pakai gamis waktu subuh');

    Http::fake([
        'api.groq.com/*' => Http::response([
            'text' => 'nama bilaluddin tidak pakai gamis waktu subuh',
        ], 200),
        'generativelanguage.googleapis.com/*' => balasanLlm(muatanLlm([
            'jenis_pelanggaran' => 'Tidak Jubah/Gamis Subuh',
            'nama_santri' => 'bilaluddin',
        ])),
    ]);

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    $response->assertOk()->assertJsonPath('draft.used_llm', true);

    expect($response->json('draft.daftar_pelanggaran_label'))->toBe('Tidak Jubah/Gamis Subuh');
});

it('tetap menghasilkan draft saat LLM sedang tidak tersedia', function () {
    masterUji();
    Santri::factory()->create(['nama' => 'Fauzi Amanah', 'asrama_id' => masterUji()['asrama']->id]);

    transkrip('nama fauzi tidak santai');

    Http::fake([
        'api.groq.com/*' => Http::response(['text' => 'nama fauzi tidak santai'], 200),
        'generativelanguage.googleapis.com/*' => Http::response(['error' => 'overloaded'], 503),
    ]);

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    $response->assertOk()
        ->assertJsonPath('draft.daftar_pelanggaran_ids', [])
        ->assertJsonPath('draft.daftar_pelanggaran_label', null);

    // Jenis tidak terisi harus masuk daftar yang perlu diperiksa petugas.
    expect($response->json('draft.needs_review'))->toContain('jenis');
});

it('memakai nama temuan LLM saat rules tidak menemukan nama', function () {
    $master = masterUji();
    DaftarPelanggaran::factory()->create(['nama_pelanggaran' => 'Tidak Jubah/Gamis Subuh']);
    Santri::factory()->create(['nama' => 'Fauzi Amanah', 'asrama_id' => $master['asrama']->id]);

    Http::fake([
        'api.groq.com/*' => Http::response(['text' => 'tidak pakai gamis waktu subuh'], 200),
        'generativelanguage.googleapis.com/*' => balasanLlm(muatanLlm([
            'jenis_pelanggaran' => 'Tidak Jubah/Gamis Subuh',
            'nama_santri' => 'fauzi',
        ])),
    ]);

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    // Dulu nama temuan LLM dibuang karena rules menandai anonim saat nama
    // tidak ketemu; sekarang anonim harus dibatalkan oleh nama tersebut.
    $response->assertOk()
        ->assertJsonPath('draft.anonymous', false)
        ->assertJsonPath('draft.used_llm', true);

    expect($response->json('draft.santri.selected'))->not->toBeNull();
    expect($response->json('draft.santri.selected.nama'))->toBe('Fauzi Amanah');
});

it('menghormati penanda tanpa nama eksplisit meski LLM menyebut nama', function () {
    $master = masterUji();
    DaftarPelanggaran::factory()->create(['nama_pelanggaran' => 'Tidak Jubah/Gamis Subuh']);
    Santri::factory()->create(['nama' => 'Fauzi Amanah', 'asrama_id' => $master['asrama']->id]);

    Http::fake([
        'api.groq.com/*' => Http::response(['text' => 'tanpa nama tidak pakai gamis waktu subuh'], 200),
        'generativelanguage.googleapis.com/*' => balasanLlm(muatanLlm([
            'jenis_pelanggaran' => 'Tidak Jubah/Gamis Subuh',
            'nama_santri' => 'fauzi',
        ])),
    ]);

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    $response->assertOk()
        ->assertJsonPath('draft.anonymous', true)
        ->assertJsonPath('draft.santri.status', 'anonymous');

    expect($response->json('draft.santri.selected'))->toBeNull();
});

it('memakai NIS dari LLM saat nama tidak disebut', function () {
    $master = masterUji();
    DaftarPelanggaran::factory()->create(['nama_pelanggaran' => 'Tidak Jubah/Gamis Subuh']);
    $nis = (string) Santri::factory()->create([
        'nama' => 'Fauzi Amanah',
        'nis' => '7788',
        'asrama_id' => $master['asrama']->id,
    ])->nis;

    Http::fake([
        'api.groq.com/*' => Http::response(['text' => 'tidak pakai gamis waktu subuh'], 200),
        'generativelanguage.googleapis.com/*' => balasanLlm(muatanLlm([
            'jenis_pelanggaran' => 'Tidak Jubah/Gamis Subuh',
            'nis' => $nis,
        ])),
    ]);

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    // NIS tidak pernah hilang, jadi catatan ini bukan lagi tanpa nama.
    $response->assertOk()
        ->assertJsonPath('draft.anonymous', false)
        ->assertJsonPath('draft.used_llm', true);

    expect($response->json('draft.santri.selected.nis'))->toBe($nis);
    expect($response->json('draft.santri.selected.nama'))->toBe('Fauzi Amanah');
});

it('tidak memakai NIS karangan LLM dan tetap mencari lewat nama', function () {
    $master = masterUji();
    DaftarPelanggaran::factory()->create(['nama_pelanggaran' => 'Tidak Jubah/Gamis Subuh']);
    Santri::factory()->create([
        'nama' => 'Fauzi Amanah',
        'nis' => '7788',
        'asrama_id' => $master['asrama']->id,
    ]);

    Http::fake([
        'api.groq.com/*' => Http::response(['text' => 'tidak pakai gamis waktu subuh'], 200),
        'generativelanguage.googleapis.com/*' => balasanLlm(muatanLlm([
            'jenis_pelanggaran' => 'Tidak Jubah/Gamis Subuh',
            'nama_santri' => 'Fauzi',
            'nis' => '9999',
        ])),
    ]);

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    $response->assertOk();

    // NIS 9999 tidak ada, jadi pencarian harus jatuh ke nama "Fauzi".
    expect($response->json('draft.santri.selected.nis'))->toBe('7788');
});

it('menyimpan catatan bebas apa adanya tanpa membuang kata bising', function () {
    $master = masterUji();
    DaftarPelanggaran::factory()->create(['nama_pelanggaran' => 'Tidak Jubah/Gamis Subuh']);
    Santri::factory()->create([
        'nama' => 'Fauzi Amanah',
        'nis' => '7788',
        'asrama_id' => $master['asrama']->id,
    ]);

    Http::fake([
        'api.groq.com/*' => Http::response([
            'text' => 'nama fauzi tidak jubah gamis isya keterangan sudah di check wali belum cek ke kepala asrama',
        ], 200),
        'generativelanguage.googleapis.com/*' => balasanLlm(muatanLlm([
            'jenis_pelanggaran' => 'Tidak Jubah/Gamis Subuh',
            'keterangan' => 'diubah oleh LLM',
        ])),
    ]);

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    // Catatan petugas ditulis bebas. Kata yang dibuang untuk pencocokan nama
    // harus tetap utuh, dan jawaban LLM tidak boleh menimpanya.
    $response->assertOk()->assertJsonPath(
        'draft.keterangan',
        'sudah di check wali belum cek ke kepala asrama'
    );
});

it('membaca tanggal yang diucapkan dengan kata, bukan hanya digit', function () {
    $master = masterUji();
    DaftarPelanggaran::factory()->create(['nama_pelanggaran' => 'Tidak Jamaah Subuh']);
    Santri::factory()->create(['nama' => 'Jimmy', 'asrama_id' => $master['asrama']->id]);

    transkrip('jimmy tidak jamaah subuh tanggal satu september dua ribu dan enam');

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    $response->assertOk()->assertJsonPath('draft.tanggal', '2006-09-01');

    // Kata tanggal tidak boleh bocor ke pencarian nama. Kalau bocor, nama
    // Santri tidak akan ditemukan dan kolom Santri tetap kosong.
    expect($response->json('draft.santri.status'))->toBe('auto');
    expect($response->json('draft.santri.selected.nama'))->toBe('Jimmy');
});

it('menawarkan nama yang mirip saat nama yang diucapkan tidak ada persis', function () {
    $master = masterUji();
    DaftarPelanggaran::factory()->create(['nama_pelanggaran' => 'Tidak Jamaah Subuh']);

    foreach (['Jimmie', 'Jamil'] as $nama) {
        Santri::factory()->create(['nama' => $nama, 'asrama_id' => $master['asrama']->id]);
    }

    transkrip('jimmy tidak jamaah subuh');

    $response = $this->actingAs($this->user)->post(route('pelanggaran.voice'), [
        'audio' => rekamanUji(),
    ]);

    $response->assertOk();

    // Belum ada yang pasti cocok, jadi tabel harus menampilkan daftar nama
    // mirip untuk dipilih petugas. "Jamil" kelewat jauh dan tidak boleh
    // ikut menawarkan diri.
    $response->assertJsonPath('draft.santri.selected', null);
    expect(array_column($response->json('draft.santri.candidates'), 'nama'))
        ->toContain('Jimmie')
        ->not->toContain('Jamil');

    // Santri sendiri belum terisi, jadi petugas wajib memilih.
    expect($response->json('draft.needs_review'))->toContain('santri');
});

<?php

use App\Http\Services\SantriExportService;
use App\Models\Asrama;
use App\Models\Daerah;
use App\Models\Santri;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo(Permission::findOrCreate('export_laporan'));

    $this->daerahA = Daerah::factory()->create(['kode' => 'A', 'nama_daerah' => 'Daerah A']);
    $this->daerahB = Daerah::factory()->create(['kode' => 'B', 'nama_daerah' => 'Daerah B']);
    $this->asramaA1 = Asrama::factory()->create(['daerah_id' => $this->daerahA->id, 'nomor' => '01']);
    $this->asramaA2 = Asrama::factory()->create(['daerah_id' => $this->daerahA->id, 'nomor' => '02']);
    $this->asramaB1 = Asrama::factory()->create(['daerah_id' => $this->daerahB->id, 'nomor' => '01']);

    $this->daerahId = $this->daerahA->id;
    $this->service = app(SantriExportService::class);
});

it('menjawab pdf untuk pengguna yang punya izin export', function () {
    Santri::factory()->create(['asrama_id' => $this->asramaA1->id]);

    $response = $this->actingAs($this->user)
        ->get('/export/santri-validasi/pdf?daerah_id='.$this->daerahId);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))
        ->toContain('attachment')
        ->toContain('validasi-santri-daerah-'.$this->daerahId)
        ->toContain('.pdf');
    expect(str_starts_with((string) $response->getContent(), '%PDF'))->toBeTrue();
});

it('menolak pengguna tanpa izin export', function () {
    Santri::factory()->create(['asrama_id' => $this->asramaA1->id]);

    $this->actingAs(User::factory()->create())
        ->get('/export/santri-validasi/pdf?daerah_id='.$this->daerahId)
        ->assertForbidden();
});

it('menolak export tanpa daerah yang dipilih', function () {
    Santri::factory()->create(['asrama_id' => $this->asramaA1->id]);

    $this->actingAs($this->user)
        ->get('/export/santri-validasi/pdf')
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('menolak export untuk daerah yang tidak ada', function () {
    Santri::factory()->create(['asrama_id' => $this->asramaA1->id]);

    $this->actingAs($this->user)
        ->get('/export/santri-validasi/pdf?daerah_id=999999')
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('hanya memuat Santri dari daerah yang dipilih', function () {
    Santri::factory()->count(3)->create(['asrama_id' => $this->asramaA1->id]);
    Santri::factory()->create(['asrama_id' => $this->asramaB1->id]);

    $groups = $this->service->laporanValidasiSantri($this->daerahId);

    // Hanya satu daerah per panggilan, supaya dokumen PDF tetap kecil.
    expect($groups)->toHaveCount(1);
    expect($groups[0]['nama_daerah'])->toBe('Daerah A');
    expect($groups[0]['total'])->toBe(3);
});

it('mengelompokkan Santri per asrama di dalam daerah', function () {
    Santri::factory()->create(['asrama_id' => $this->asramaA1->id]);
    Santri::factory()->create(['asrama_id' => $this->asramaA2->id]);

    $groups = $this->service->laporanValidasiSantri($this->daerahId);
    $asrama = $groups[0]['asrama'];

    expect($asrama)->toHaveCount(2);
    expect($asrama[0]['label'])->toBe('A.01');
    expect($asrama[0]['santri'])->toHaveCount(1);
    expect($asrama[1]['label'])->toBe('A.02');
});

it('memuat kolom nama, nis, iksass, asrama, dan status dengan jabatan kosong', function () {
    Santri::factory()->create([
        'nama' => 'Ahmad Fauzi',
        'nis' => '12345',
        'iksass' => 'Jember',
        'status' => 'aktif',
        'asrama_id' => $this->asramaA1->id,
    ]);

    $groups = $this->service->laporanValidasiSantri($this->daerahId);
    $row = $groups[0]['asrama'][0]['santri'][0];

    expect($row['nama'])->toBe('Ahmad Fauzi');
    expect($row['nis'])->toBe('12345');
    expect($row['iksass'])->toBe('Jember');
    expect($row['asrama'])->toBe('A.01');
    expect($row['status'])->toBe('Aktif');
    // Jabatan sengaja dikosongkan untuk diisi manual oleh asrama.
    expect($row['jabatan'])->toBe('');
});

it('memuat semua status Santri', function () {
    Santri::factory()->create(['status' => 'aktif', 'asrama_id' => $this->asramaA1->id]);
    Santri::factory()->create(['status' => 'tidak aktif', 'asrama_id' => $this->asramaA1->id]);
    Santri::factory()->create(['status' => 'berhenti', 'asrama_id' => $this->asramaA1->id]);

    $groups = $this->service->laporanValidasiSantri($this->daerahId);
    $status = array_column($groups[0]['asrama'][0]['santri'], 'status');

    expect($status)->toContain('Aktif')->toContain('Tidak Aktif')->toContain('Berhenti');
});

it('mengabaikan Santri yang sudah dihapus', function () {
    Santri::factory()->create(['asrama_id' => $this->asramaA1->id]);
    Santri::factory()->create(['asrama_id' => $this->asramaA1->id])->delete();

    $groups = $this->service->laporanValidasiSantri($this->daerahId);

    expect($groups[0]['total'])->toBe(1);
});

it('menampilkan judul daerah, kolom tabel, dan tiga catatan validasi', function () {
    Santri::factory()->create(['asrama_id' => $this->asramaA1->id]);

    $html = view('exports.validasi-santri', [
        'daerahGroups' => $this->service->laporanValidasiSantri($this->daerahId),
        'tanggal_cetak' => '01/01/2026 10:00',
        'user' => 'Petugas Uji',
    ])->render();

    // Judul daerah dicetak kapital.
    expect(strtolower($html))->toContain(strtolower('Daerah A'));
    expect($html)->toContain('ASRAMA A.01');

    foreach (['Nama', 'NIS', 'IKSASS', 'Asrama', 'Status', 'Jabatan'] as $kolom) {
        expect($html)->toContain($kolom);
    }

    // Catatan untuk asrama.
    expect($html)->toContain('(Ketua)');
    expect($html)->toContain('Mohon melengkapi data yang masih kosong');
    expect($html)->toContain('di dalam tanda kurung pada kolom Jabatan');
    expect($html)->toContain('kolom Asrama harap diisi dengan nama asrama yang baru');
});

it('menampilkan pesan ketika daerah tidak punya data Santri', function () {
    $html = view('exports.validasi-santri', [
        'daerahGroups' => $this->service->laporanValidasiSantri($this->daerahId),
        'tanggal_cetak' => '01/01/2026 10:00',
        'user' => 'Petugas Uji',
    ])->render();

    expect($html)->toContain('Tidak ada data Santri');
});

it('guard daerah menolak id yang tidak valid', function () {
    expect($this->service->daerahMemilikiSantri(null))->toBeFalse();
    expect($this->service->daerahMemilikiSantri('abc'))->toBeFalse();
    expect($this->service->daerahMemilikiSantri(999999))->toBeFalse();
});

it('guard daerah menerima daerah yang punya Santri', function () {
    expect($this->service->daerahMemilikiSantri($this->daerahA->id))->toBeFalse();

    Santri::factory()->create(['asrama_id' => $this->asramaA1->id]);

    expect($this->service->daerahMemilikiSantri($this->daerahA->id))->toBeTrue();
});

it('memakai huruf awalan kode daerah pada label asrama', function () {
    $daerah = Daerah::factory()->create(['kode' => 'W/OS', 'nama_daerah' => 'Daerah Os']);
    $asrama = Asrama::factory()->create(['daerah_id' => $daerah->id, 'nomor' => '07']);

    Santri::factory()->create(['asrama_id' => $asrama->id]);

    $groups = $this->service->laporanValidasiSantri($daerah->id);

    // Cukup huruf awalan kode daerah, bukan kode penuh.
    expect($groups[0]['asrama'][0]['label'])->toBe('W.07');
    expect($groups[0]['asrama'][0]['santri'][0]['asrama'])->toBe('W.07');
});

it('memberi kop surat pada tiap blok asrama', function () {
    Santri::factory()->create(['asrama_id' => $this->asramaA1->id]);
    Santri::factory()->create(['asrama_id' => $this->asramaA2->id]);

    $html = view('exports.validasi-santri', [
        'daerahGroups' => $this->service->laporanValidasiSantri($this->daerahId),
        'tanggal_cetak' => '01/01/2026 10:00',
        'user' => 'Petugas Uji',
    ])->render();

    // Kop surat sama dengan surat panggilan.
    expect($html)->toContain('PONDOK PESANTREN "SALAFIYAH SYAFI\'IYAH" SUKOREJO');
    expect($html)->toContain('BIDANG KEPESANTRENAN');
    expect($html)->toContain('SUMBEREJO BANYUPUTIH SITUBONDO JAWA TIMUR');
    expect($html)->toContain('Akte Notaris');
    expect($html)->toContain('logo/p2s3.png');

    // Tiap asrama dipisah menjadi blok sendiri dengan kopnya masing-masing.
    expect(substr_count($html, 'class="kop"'))->toBe(2);
    expect($html)->toContain('DATA SANTRI ASRAMA A.01');
    expect($html)->toContain('DATA SANTRI ASRAMA A.02');

    // Tiap blok memuat nama ketua kamarnya sendiri.
    expect($html)->toContain('Ketua Kamar Daerah A No. 01');
    expect($html)->toContain('Ketua Kamar Daerah A No. 02');
});

it('mengatur lebar kolom dengan satuan absolut, bukan persentase', function () {
    Santri::factory()->create(['asrama_id' => $this->asramaA1->id]);

    $html = view('exports.validasi-santri', [
        'daerahGroups' => $this->service->laporanValidasiSantri($this->daerahId),
        'tanggal_cetak' => '01/01/2026 10:00',
        'user' => 'Petugas Uji',
    ])->render();

    // DomPDF mengabaikan table-layout: fixed dan lebar persentase, sehingga
    // lebar kolom harus dinyatakan dalam pt pada setiap kolom.
    expect($html)->not->toContain('table-layout: fixed');
    expect($html)->not->toContain('<colgroup');

    foreach (['20.2', '156.6', '62.2', '77.9', '62.2', '51.7'] as $lebar) {
        expect($html)->toContain('width: '.$lebar.'pt');
    }

    // Tujuh kolom, dari No sampai Jabatan.
    expect(preg_match_all('/th:nth-child\(/', $html))->toBe(7);
});

it('menyediakan 5 baris kosong untuk mencatat santri baru per asrama', function () {
    Santri::factory()->count(2)->create(['asrama_id' => $this->asramaA1->id]);

    $html = view('exports.validasi-santri', [
        'daerahGroups' => $this->service->laporanValidasiSantri($this->daerahId),
        'tanggal_cetak' => '01/01/2026 10:00',
        'user' => 'Petugas Uji',
    ])->render();

    // Dua data asli + lima baris kosong + satu baris kepala.
    expect(preg_match_all('/<tr[ >]/', $html))->toBe(8);

    preg_match_all('/<tr class="baris-kosong">\s*<td class="no">(\d+)<\/td>(.*?)<\/tr>/s', $html, $m);

    // Nomor melanjutkan data yang ada, yaitu 3 sampai 7.
    expect($m[1])->toBe(['3', '4', '5', '6', '7']);

    // Selain kolom No, seluruh sel baris kosong harus benar-benar kosong.
    foreach ($m[2] as $sel) {
        foreach (['nama', 'nis', 'iksass', 'asrama', 'status', 'jabatan'] as $kolom) {
            expect($sel)->toContain('<td class="'.$kolom.'"></td>');
        }
    }
});

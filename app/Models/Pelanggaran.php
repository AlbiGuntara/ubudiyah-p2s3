<?php
namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pelanggaran extends Model
{
    use Auditable, HasFactory;

    protected $table = 'pelanggaran';

    protected $fillable = [
        'santri_id',
        'asrama_id',
        'daftar_pelanggaran_id',
        'petugas_id',
        'jumlah',
        'sumber_pencatatan',
        'tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }

    public function asrama()
    {
        return $this->belongsTo(Asrama::class);
    }

    public function daftarPelanggaran()
    {
        return $this->belongsTo(DaftarPelanggaran::class);
    }

    public function petugas()
    {
        return $this->belongsTo(Petugas::class);
    }

    public function suratPanggilan(): BelongsToMany
    {
        return $this->belongsToMany(SuratPanggilan::class, 'pelanggaran_surat_panggilan', 'pelanggaran_id', 'surat_panggilan_id');
    }

    public function scopeBelumTercetak($query)
    {
        return $query->whereDoesntHave('suratPanggilan');
    }

    public function scopeSumberPetugas($query)
    {
        return $query->where('sumber_pencatatan', 'petugas');
    }
}

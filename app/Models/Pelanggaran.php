<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pelanggaran extends Model
{
    use HasFactory;

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

    public function scopeSumberPetugas($query)
    {
        return $query->where('sumber_pencatatan', 'petugas');
    }
}

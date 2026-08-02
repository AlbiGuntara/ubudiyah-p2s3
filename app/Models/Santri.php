<?php
namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Santri extends Model
{
    use Auditable, SoftDeletes, HasFactory;

    protected $table = 'santri';

    protected $fillable = [
        'nama',
        'nis',
        'iksass',
        'foto',
        'nama_panggilan',
        'status',
        'asrama_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function asrama()
    {
        return $this->belongsTo(Asrama::class);
    }

    public function pelanggaran()
    {
        return $this->hasMany(Pelanggaran::class);
    }

    public function pembinaan()
    {
        return $this->hasMany(Pembinaan::class);
    }

    public function getDaerahAttribute()
    {
        return $this->asrama->daerah;
    }

    public function getTotalPelanggaranAttribute()
    {
        return $this->pelanggaran()->count();
    }

    public function getTotalShalawatAttribute()
    {
        return $this->pembinaan()->sum('shalawat_tertulis');
    }
}
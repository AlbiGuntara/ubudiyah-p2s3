<?php
namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Petugas extends Model
{
    use Auditable, HasFactory;

    protected $table = 'petugas';

    protected $fillable = [
        'nama',
        'foto',
        'daerah_id',
        'asrama_id',
        'jabatan',
        'tugas',
    ];

    public function daerah()
    {
        return $this->belongsTo(Daerah::class);
    }

    public function asrama()
    {
        return $this->belongsTo(Asrama::class);
    }

    public function pelanggaran()
    {
        return $this->hasMany(Pelanggaran::class);
    }
}

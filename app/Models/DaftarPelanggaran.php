<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DaftarPelanggaran extends Model
{
    use HasFactory;

    protected $table = 'daftar_pelanggaran';

    protected $fillable = [
        'nama_pelanggaran',
        'poin',
    ];

    public function pelanggaran()
    {
        return $this->hasMany(Pelanggaran::class);
    }
}

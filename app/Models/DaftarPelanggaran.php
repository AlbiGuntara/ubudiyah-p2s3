<?php
namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DaftarPelanggaran extends Model
{
    use Auditable, HasFactory;

    protected $table = 'daftar_pelanggaran';

    protected $fillable = [
        'nama_pelanggaran',
    ];

    public function pelanggaran()
    {
        return $this->hasMany(Pelanggaran::class);
    }
}

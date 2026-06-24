<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asrama extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'asrama';

    protected $fillable = [
        'daerah_id',
        'nomor',
    ];

    public function daerah()
    {
        return $this->belongsTo(Daerah::class);
    }

    public function santri()
    {
        return $this->hasMany(Santri::class);
    }

    public function pelanggaran()
    {
        return $this->hasMany(Pelanggaran::class);
    }
}

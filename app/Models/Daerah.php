<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Daerah extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'daerah';

    protected $fillable = [
        'kode',
        'nama_daerah',
    ];

    public function asrama()
    {
        return $this->hasMany(Asrama::class);
    }
}

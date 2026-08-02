<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PembinaanSetoran extends Model
{
    use HasFactory;

    protected $table = 'pembinaan_setoran';

    protected $fillable = [
        'pembinaan_id',
        'jumlah',
        'tanggal_setor',
        'user_id',
        'user_name',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal_setor' => 'date:Y-m-d',
        ];
    }

    public function pembinaan()
    {
        return $this->belongsTo(Pembinaan::class);
    }
}

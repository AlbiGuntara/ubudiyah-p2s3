<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembinaan extends Model
{
    use Auditable, HasFactory;

    protected $table = 'pembinaan';

    protected $fillable = [
        'santri_id',
        'asrama_id',
        'jenis_pelanggaran',
        'tanggal_pelanggaran',
        'sanksi',
        'shalawat_tertulis',
        'sisa_sanksi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pelanggaran' => 'date:Y-m-d',
            'sanksi' => 'integer',
            'shalawat_tertulis' => 'integer',
            'sisa_sanksi' => 'integer',
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

    public function setoran()
    {
        return $this->hasMany(PembinaanSetoran::class);
    }
}

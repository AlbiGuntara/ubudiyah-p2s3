<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembinaan extends Model
{
    use HasFactory;

    protected $table = 'pembinaan';

    protected $fillable = [
        'santri_id',
        'asrama_id',
        'sanksi',
        'shalawat_tertulis',
        'sisa_sanksi',
    ];

    protected function casts(): array
    {
        return [
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
}

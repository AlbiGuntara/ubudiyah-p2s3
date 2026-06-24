<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pembinaan extends Model
{
    use HasFactory;

    protected $table = 'pembinaan';

    protected $fillable = [
        'santri_id',
        'panggilan',
        'tanggal_panggilan',
        'sanksi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_panggilan' => 'date',
        ];
    }

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }
}

<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Daerah extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'daerah';

    protected $fillable = [
        'kode',
        'nama_daerah',
    ];

    /**
     * @return HasMany<Asrama, $this>
     */
    public function asrama(): HasMany
    {
        return $this->hasMany(Asrama::class);
    }
}

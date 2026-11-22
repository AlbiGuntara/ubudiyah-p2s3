<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Iksass extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'iksass';

    protected $fillable = [
        'kode',
        'nama_iksass',
    ];

    /**
     * @return HasMany<Santri, $this>
     */
    public function santri(): HasMany
    {
        return $this->hasMany(Santri::class, 'iksass_id');
    }
}

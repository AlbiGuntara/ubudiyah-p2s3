<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asrama extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'asrama';

    protected $fillable = [
        'daerah_id',
        'nomor',
    ];

    /**
     * @return BelongsTo<Daerah, $this>
     */
    public function daerah(): BelongsTo
    {
        return $this->belongsTo(Daerah::class);
    }

    /**
     * @return HasMany<Santri, $this>
     */
    public function santri(): HasMany
    {
        return $this->hasMany(Santri::class);
    }

    /**
     * @return HasMany<Pelanggaran, $this>
     */
    public function pelanggaran(): HasMany
    {
        return $this->hasMany(Pelanggaran::class);
    }

    /**
     * @return HasMany<SuratPanggilan, $this>
     */
    public function suratPanggilan(): HasMany
    {
        return $this->hasMany(SuratPanggilan::class);
    }
}

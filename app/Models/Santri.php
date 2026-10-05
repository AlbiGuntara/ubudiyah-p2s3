<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Santri extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'santri';

    protected $fillable = [
        'nama',
        'nis',
        'iksass',
        'foto',
        'nama_panggilan',
        'status',
        'asrama_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * @return BelongsTo<Asrama, $this>
     */
    public function asrama(): BelongsTo
    {
        return $this->belongsTo(Asrama::class);
    }

    /**
     * @return HasMany<Pelanggaran, $this>
     */
    public function pelanggaran(): HasMany
    {
        return $this->hasMany(Pelanggaran::class);
    }

    /**
     * @return HasMany<Pembinaan, $this>
     */
    public function pembinaan(): HasMany
    {
        return $this->hasMany(Pembinaan::class);
    }

    public function getDaerahAttribute()
    {
        return $this->asrama->daerah;
    }

    public function getTotalPelanggaranAttribute()
    {
        return $this->pelanggaran()->count();
    }

    public function getTotalShalawatAttribute()
    {
        return $this->pembinaan()->sum('shalawat_tertulis');
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SuratPanggilan extends Model
{
    protected $table = 'surat_panggilan';

    protected $fillable = [
        'asrama_id',
        'kode_surat',
        'tanggal_cetak',
        'printed_at',
        'dicetak_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_cetak' => 'datetime',
            'printed_at' => 'datetime',
        ];
    }

    public function asrama(): BelongsTo
    {
        return $this->belongsTo(Asrama::class);
    }

    public function pelanggaran(): BelongsToMany
    {
        return $this->belongsToMany(Pelanggaran::class, 'pelanggaran_surat_panggilan', 'surat_panggilan_id', 'pelanggaran_id');
    }

    public function pencetak(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicetak_oleh');
    }
}

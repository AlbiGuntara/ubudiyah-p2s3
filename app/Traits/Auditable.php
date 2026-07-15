<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::logActivity($model, 'menambahkan');
        });

        static::updated(function ($model) {
            static::logActivity($model, 'mengubah');
        });

        static::deleted(function ($model) {
            static::logActivity($model, 'menghapus');
        });
    }

    protected static function logActivity($model, string $aktivitas): void
    {
        if (!Auth::check()) return;

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'aktivitas' => $aktivitas . ' ' . class_basename($model),
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'data' => $model->toArray(),
        ]);
    }
}
<?php
namespace App\Providers;

use App\Models\Daerah;
use App\Models\Pelanggaran;
use App\Models\Santri;
use App\Policies\DaerahPolicy;
use App\Policies\PelanggaranPolicy;
use App\Policies\SantriPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Daerah::class => DaerahPolicy::class,
        Santri::class => SantriPolicy::class,
        Pelanggaran::class => PelanggaranPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

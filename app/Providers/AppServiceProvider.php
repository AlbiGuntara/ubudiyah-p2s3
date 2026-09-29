<?php

namespace App\Providers;

use App\Http\Services\Voice\LlmIntentParser;
use App\Http\Services\Voice\RulesIntentParser;
use App\Http\Services\Voice\SantriNameMatcher;
use App\Http\Services\Voice\VoiceIntentResolver;
use App\Http\Services\Voice\VoiceMasterData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerVoiceServices();
    }

    /**
     * Parser voice stateless, tetapi `VoiceMasterData` dan `SantriNameMatcher`
     * membaca cache. Diikat sebagai singleton supaya satu rekaman tidak
     * membangun ulang rantai parser dari awal.
     */
    protected function registerVoiceServices(): void
    {
        $this->app->singleton(VoiceMasterData::class);
        $this->app->singleton(SantriNameMatcher::class);
        $this->app->singleton(RulesIntentParser::class);
        $this->app->singleton(LlmIntentParser::class);
        $this->app->singleton(VoiceIntentResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

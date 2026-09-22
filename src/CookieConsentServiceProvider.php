<?php

namespace QuebecStudioMods\ConsentKit\Laravel;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use QuebecStudioMods\ConsentKit\Core\Paths;
use QuebecStudioMods\ConsentKit\Laravel\Http\Controllers\ThumbnailController;
use QuebecStudioMods\ConsentKit\Laravel\Http\Middleware\InjectConsent;

final class CookieConsentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/cookie-consent.php', 'cookie-consent');

        $this->app->scoped(CookieConsent::class);
    }

    public function boot(): void
    {

        $this->loadViewsFrom(Paths::views('blade'), 'cookie-consent');

        $kernel = $this->app->make(Kernel::class);

        if ($kernel instanceof HttpKernel) {
            $kernel->appendMiddlewareToGroup('web', InjectConsent::class);
        }

        Route::get('cookie-consent/thumbnail', ThumbnailController::class)->name('cookie-consent.thumbnail');

        $service = '\\' . CookieConsent::class;

        Blade::directive('cookieConsentBanner', static fn () => "<?php echo app($service::class)->banner(); ?>");

        Blade::directive('withCookieTable', static fn (string $expression) => "<?php echo app($service::class)->withCookieTableHtml($expression); ?>");

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }
    }

    private function registerPublishing(): void
    {
        $this->publishes([
            dirname(__DIR__) . '/config/cookie-consent.php' => config_path('cookie-consent.php'),
        ], 'cookie-consent-config');

        $assets = [
            Paths::asset('consent.css') => public_path('vendor/cookie-consent/consent.css'),
            Paths::asset('consent.js') => public_path('vendor/cookie-consent/consent.js'),
        ];

        $this->publishes($assets, 'cookie-consent-assets');
        $this->publishes($assets, 'laravel-assets');

        $this->publishes([
            Paths::lang() => lang_path('vendor/cookie-consent'),
        ], 'cookie-consent-lang');

        $this->publishes([
            Paths::views('blade') => resource_path('views/vendor/cookie-consent'),
        ], 'cookie-consent-views');
    }
}

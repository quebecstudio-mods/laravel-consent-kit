<?php

namespace QuebecStudioMods\ConsentKit\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\HtmlString banner()
 * @method static \Illuminate\Support\HtmlString cookieTable(array $options = [])
 * @method static \Illuminate\Support\HtmlString withCookieTable(mixed $content, array $options = [])
 * @method static \Illuminate\Support\HtmlString videoFacade(?string $youtubeId, ?string $title = null, ?string $poster = null)
 * @method static array categories()
 * @method static array config()
 *
 * @see \QuebecStudioMods\ConsentKit\Laravel\CookieConsent
 */
final class CookieConsent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \QuebecStudioMods\ConsentKit\Laravel\CookieConsent::class;
    }
}

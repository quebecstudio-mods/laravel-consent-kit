# Cookie Consent for Laravel — `quebecstudio-mods/laravel-consent-kit`

Cookie consent banner for Laravel 11 to 13, built for Quebec's Law 25 and usable
under the GDPR. Built on `quebecstudio-mods/consent-kit-core`, shared with the
Craft CMS plugin.

- No third-party cookie is set before consent.
- The HTML is the same for every visitor; pages stay cacheable.
- English and French included; an application adds any language with one file.
- YouTube videos load on click, with the thumbnail served by the application.

## Installation

Requires PHP 8.2. Declare the private repositories in the application's
`composer.json`:

```jsonc
"repositories": [
    { "type": "vcs", "url": "git@github.com:quebecstudio-mods/laravel-consent-kit.git" },
    { "type": "vcs", "url": "git@github.com:quebecstudio-mods/consent-kit-core.git" }
]
```

```bash
composer require quebecstudio-mods/laravel-consent-kit
php artisan vendor:publish --tag=cookie-consent-assets
php artisan vendor:publish --tag=cookie-consent-config
```

The service provider is discovered automatically. The banner is added to every
HTML page served through the `web` middleware group.

## How it works

`InjectConsent`, appended to the `web` group, edits HTML responses:

| Position | Markup |
|---|---|
| first in `<head>` | the inline bootstrap: reads the consent cookie, primes Matomo and Google Consent Mode in a refused state |
| end of `<head>` | `consent.css` |
| end of `<body>` | the `<qsm-consent-kit>` banner (when `autoInject` is on) and `consent.js` |

JSON, redirects, file and streamed responses are left untouched.

## Configuration

`config/cookie-consent.php`. The keys follow the core's settings; the
Craft-only ones (`policySource`, `policyEntry`, `templateRoot`) do not apply.

| Key | Default | What it does |
|---|---|---|
| `cookieName` | `cookie_consent` | Cookie that stores the decision |
| `cookieMaxAge` | `15552000` | Its lifetime, in seconds |
| `version` | `1` | Increase to ask every visitor again |
| `policyUrl` | `/privacy-policy` | Policy link in the banner; empty shows none |
| `defaultLanguage` | `en` | Language when the locale has no wording file |
| `autoInject` | `true` | Adds the banner to every HTML page the middleware sees |
| `colorScheme` | `auto` | `auto`, `light`, `dark` |
| `backdropStyle` | `blur` | `blur`, `dim`, `none` |
| `displayMode` | `full` | `full`, `floating`, `corner-left`, `corner-right`; full width under 40rem |
| `reopenButton` | `true` | Tab that reopens the banner |
| `reopenPosition` | `auto` | Its side: `left`, `right`, or `auto`, right for `corner-right` |
| `gpcHidesBanner` | `true` | Skips the banner under Global Privacy Control |
| `analyticsCategory` | `statistics` | Category granting Matomo and `analytics_storage` |
| `marketingCategory` | `marketing` | Category granting the Google ad signals |
| `videoFacade` | `true` | YouTube videos load on click |
| `videoThumbnails` | `true` | The facade shows the YouTube thumbnail |
| `videoConsentCategory` | `''` | Category whose consent skips the facade |
| `inventoryFramework` | `''` | Cookie table classes: `''`, `bootstrap`, `bulma`, `tailwind`, `custom` |
| `inventoryClasses` | `[]` | Classes per table element, with `custom` |
| `categories` | `null` | Categories and cookies; `null` uses the shipped ones |

### Categories

`null` ships three categories: `necessary`, declaring the session cookie
(`config('session.cookie')`), `XSRF-TOKEN` and the consent cookie; `statistics`
and `marketing`, empty. A category with no declared cookie is not shown.

Wording in `categories` is a string, or keyed by locale:

```php
'categories' => [
    'necessary' => [
        'required' => true,
        'label' => ['en' => 'Necessary', 'fr' => 'Nécessaires'],
        'cookies' => [
            'session' => ['name' => 'laravel_session', 'purpose' => ['en' => 'Keeps your session.'], 'duration' => ['en' => 'Session']],
        ],
    ],
    'statistics' => [
        'label' => ['en' => 'Statistics', 'fr' => 'Statistiques'],
        'cookies' => [
            'ga' => ['name' => '_ga', 'provider' => 'Google', 'purpose' => ['en' => 'Distinguishes visitors.'], 'duration' => ['en' => '2 years']],
        ],
    ],
],
```

## Wording

The banner speaks the application locale (`app()->getLocale()`): the exact
locale, then its base language, then `defaultLanguage`.

`lang/vendor/cookie-consent/<language>.php` replaces strings or adds a
language. A file holds only the keys it changes:

```php
<?php

return [
    'texts' => ['accept' => 'J’accepte'],
];
```

```bash
php artisan vendor:publish --tag=cookie-consent-lang
```

copies the shipped files there as a starting point. A published file keeps
every key, so shipped wording updates no longer reach it; keep only the keys
you change.

## In templates

```blade
@cookieConsentBanner                               {{-- with autoInject off --}}
{{ CookieConsent::cookieTable() }}
{{ CookieConsent::cookieTable(['category' => 'statistics', 'headingLevel' => 2]) }}
@withCookieTable($page->body)
@withCookieTable($page->body, ['heading' => false])
{{ CookieConsent::videoFacade($youtubeId, $title) }}
```

`@withCookieTable` outputs its content as HTML with the `[cookie-table]`
markers replaced; like `{!! !!}`, use it only for HTML you trust.
`CookieConsent::withCookieTable()` escapes content that is not `Htmlable`.

| Method | Returns |
|---|---|
| `banner()` | the banner, once per request |
| `cookieTable(options)` | the declared cookies; options `classes`, `category`, `heading`, `headingLevel` |
| `withCookieTable(content, options)` | content with its `[cookie-table]` markers replaced; content that is not `Htmlable` is escaped |
| `videoFacade(id, title, poster)` | a YouTube facade |
| `categories()` | visible categories and their cookies |
| `config()` | the resolved configuration |

YouTube posters are served by the route `cookie-consent.thumbnail`
(`/cookie-consent/thumbnail?v=<id>`), outside the `web` group, and cached in
`storage/app/cookie-consent-thumbnails/`.

## Overriding templates

`resources/views/vendor/cookie-consent/<name>.blade.php` replaces a shipped
template: `banner`, `cookie-table`, `video-facade`, `video-embed`.

```bash
php artisan vendor:publish --tag=cookie-consent-views
```

The variables each template receives are listed in the core package's README.
Keep the `data-qsm-ck-*` attributes: the script finds every control through them.

## Assets

`vendor:publish --tag=cookie-consent-assets` copies `consent.js` and
`consent.css` to `public/vendor/cookie-consent/`. They are also published with
the `laravel-assets` tag, which the Laravel application skeleton republishes on
`composer update`.

## Front end

Styling, the JavaScript API, conditional tags and integration recipes are the
same as for the Craft plugin; see its documentation.

## Recording consents

The package does not keep a register of decisions, but everything needed to
build one is there: an event, a beacon script, and the server-side fingerprint
of what the banner was showing.
[Recording consents](docs/recording-consents.md) walks through it.

## Tests

```bash
composer install
vendor/bin/pest
```

## Licence

Proprietary. See [LICENSE.md](LICENSE.md).

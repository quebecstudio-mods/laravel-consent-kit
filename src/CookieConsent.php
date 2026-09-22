<?php

namespace QuebecStudioMods\ConsentKit\Laravel;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use QuebecStudioMods\ConsentKit\Core\Bootstrap;
use QuebecStudioMods\ConsentKit\Core\CookieTableMarkers;
use QuebecStudioMods\ConsentKit\Core\Defaults;
use QuebecStudioMods\ConsentKit\Core\Languages;
use QuebecStudioMods\ConsentKit\Core\Resolver;
use QuebecStudioMods\ConsentKit\Core\SiteContext;
use QuebecStudioMods\ConsentKit\Core\Templates;

/**
 * The consent banner and its companions for one request, from the
 * `cookie-consent` config and the application locale.
 */
final class CookieConsent
{
    private ?Resolver $resolver = null;

    private bool $rendered = false;

    /** The inline `<head>` script: reads the decision and primes the trackers. */
    public function bootstrap(): HtmlString
    {
        return new HtmlString('<script>' . Bootstrap::script($this->resolver()->bootstrapConfig()) . '</script>');
    }

    /** `<link>` to the stylesheet. */
    public function styles(): HtmlString
    {
        return new HtmlString('<link rel="stylesheet" href="' . e($this->assetUrl('consent.css')) . '">');
    }

    /** `<script>` loading the component. */
    public function script(): HtmlString
    {
        return new HtmlString('<script src="' . e($this->assetUrl('consent.js')) . '" defer></script>');
    }

    /** The `<qsm-consent-kit>` element, once per request. */
    public function banner(): HtmlString
    {
        if ($this->rendered) {
            return new HtmlString('');
        }

        $this->rendered = true;
        $config = $this->config();

        return $this->render('banner', Templates::banner([
            'config' => $config,
            'texts' => $config['texts'],
            'categories' => $config['categories'],
        ]));
    }

    public function bannerRendered(): bool
    {
        return $this->rendered;
    }

    /**
     * The declared cookies as tables. Options: `classes`, `category`,
     * `heading`, `headingLevel`.
     */
    public function cookieTable(array $options = []): HtmlString
    {
        $categories = $this->categories();
        $only = array_filter(array_map('trim', (array)($options['category'] ?? [])));

        if ($only !== []) {
            $categories = array_values(array_filter(
                $categories,
                static fn (array $category) => in_array($category['handle'], $only, true)
            ));
        }

        if ($categories === []) {
            return new HtmlString('');
        }

        return $this->render('cookie-table', Templates::cookieTable([
            'categories' => $categories,
            'texts' => $this->resolver()->texts(),
            'classes' => $this->resolver()->inventoryClasses($options['classes'] ?? []),
            'headingLevel' => (int)($options['headingLevel'] ?? 3),
            'heading' => (bool)($options['heading'] ?? true),
        ]));
    }

    /**
     * Content with its `[cookie-table]` markers replaced. Content that is not
     * already HTML (an Htmlable) is escaped first.
     */
    public function withCookieTable(mixed $content, array $options = []): HtmlString
    {
        $html = $content instanceof Htmlable
            ? $content->toHtml()
            : htmlspecialchars((string)$content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if (!CookieTableMarkers::contains($html)) {
            return new HtmlString($html);
        }

        return new HtmlString(CookieTableMarkers::replace($html, fn (array $opts) => (string)$this->cookieTable($opts), $options));
    }

    /**
     * The same, for content the caller vouches for as HTML, as `{!! !!}`
     * does: `@withCookieTable($page->body)`.
     */
    public function withCookieTableHtml(mixed $html, array $options = []): HtmlString
    {
        return $this->withCookieTable(new HtmlString((string)$html), $options);
    }

    /** A YouTube video loaded on click, or embedded directly when `videoFacade` is off. */
    public function videoFacade(?string $youtubeId, ?string $title = null, ?string $poster = null): HtmlString
    {
        if (!$youtubeId) {
            return new HtmlString('');
        }

        if (!config('cookie-consent.videoFacade', true)) {
            return $this->render('video-embed', Templates::videoEmbed([
                'youtubeId' => $youtubeId,
                'title' => $title,
            ]));
        }

        return $this->render('video-facade', Templates::videoFacade([
            'youtubeId' => $youtubeId,
            'title' => $title,
            'poster' => $poster,
            'thumbnail' => $poster || !config('cookie-consent.videoThumbnails', true)
                ? null
                : route('cookie-consent.thumbnail', ['v' => $youtubeId]),
            'consentCategory' => $this->resolver()->videoConsentCategory(),
            'texts' => $this->resolver()->texts(),
        ]));
    }

    /** Visible categories, each with its cookies. */
    public function categories(): array
    {
        return $this->resolver()->visibleCategories();
    }

    /** The resolved configuration, as serialised into `<qsm-consent-kit>`. */
    public function config(): array
    {
        return $this->resolver()->bannerConfig($this->resolver()->policyUrl());
    }

    /** The core's view of the application: settings, locale, wording files. */
    public function resolver(): Resolver
    {
        return $this->resolver ??= new Resolver(
            $this->settings(),
            new SiteContext(1, 'default', str_replace('_', '-', app()->getLocale())),
            null,
            self::languages(),
        );
    }

    /** The shipped wording files, then the application's in `lang/vendor/cookie-consent/`. */
    public static function languages(): Languages
    {
        return new Languages([lang_path('vendor/cookie-consent')]);
    }

    /**
     * The shipped categories, with Laravel's own cookies in place of the
     * platform ones the core declares.
     */
    public static function defaultCategories(): array
    {
        $categories = Defaults::categories(self::languages());
        $necessary = &$categories['necessary']['cookies'];

        $necessary['craft-session']['name'] = (string)config('session.cookie', 'laravel_session');
        $necessary['craft-csrf']['name'] = 'XSRF-TOKEN';

        return $categories;
    }

    private function settings(): array
    {
        $settings = (array)config('cookie-consent', []);
        $settings['categories'] = $settings['categories'] ?? null ?: self::defaultCategories();

        return $settings;
    }

    private function assetUrl(string $file): string
    {
        $published = public_path("vendor/cookie-consent/$file");
        $version = is_file($published) ? substr(md5((string)filemtime($published)), 0, 8) : '';

        return asset("vendor/cookie-consent/$file") . ($version !== '' ? "?v=$version" : '');
    }

    private function render(string $template, array $variables): HtmlString
    {
        /** @var view-string $view */
        $view = "cookie-consent::$template";

        return new HtmlString(view($view, $variables)->render());
    }
}

<?php

namespace QuebecStudioMods\ConsentKit\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use QuebecStudioMods\ConsentKit\Laravel\CookieConsent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Adds the consent markup to every HTML page: the bootstrap first in
 * `<head>`, ahead of any tracker, the stylesheet at the end of `<head>`, and
 * the banner and its script at the end of `<body>`.
 *
 * The markup is the same for every visitor, so pages stay cacheable.
 */
final class InjectConsent
{
    public function __construct(private readonly CookieConsent $consent)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->isHtmlPage($response)) {
            return $response;
        }

        $html = (string)$response->getContent();

        if (!preg_match('/<head\b[^>]*>/i', $html, $head, PREG_OFFSET_CAPTURE) || stripos($html, '</body>') === false) {
            return $response;
        }

        $headEnd = $head[0][1] + strlen($head[0][0]);
        $html = substr($html, 0, $headEnd) . $this->consent->bootstrap() . substr($html, $headEnd);
        $html = $this->insertBefore($html, '</head>', (string)$this->consent->styles());

        $bodyEnd = config('cookie-consent.autoInject', true) ? (string)$this->consent->banner() : '';
        $html = $this->insertBefore($html, '</body>', $bodyEnd . $this->consent->script());

        $response->setContent($html);

        return $response;
    }

    private function isHtmlPage(Response $response): bool
    {
        return !$response instanceof StreamedResponse
            && !$response instanceof BinaryFileResponse
            && !$response->isRedirection()
            && str_contains((string)$response->headers->get('Content-Type', 'text/html'), 'text/html');
    }

    /** Before the last occurrence, so a `</body>` inside a script is left alone. */
    private function insertBefore(string $html, string $tag, string $markup): string
    {
        $position = strripos($html, $tag);

        return $position === false ? $html : substr($html, 0, $position) . $markup . substr($html, $position);
    }
}

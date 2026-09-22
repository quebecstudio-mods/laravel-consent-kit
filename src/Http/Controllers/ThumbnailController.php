<?php

namespace QuebecStudioMods\ConsentKit\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Serves YouTube poster images from the application's own domain, so the
 * visitor's browser never contacts Google before clicking. The image is
 * fetched once, on the first request, and served from disk afterwards.
 */
final class ThumbnailController
{
    /** An 11-character YouTube id: the request never chooses a host or a path. */
    private const ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

    /** In order of preference: maxres does not exist for every video. */
    private const QUALITIES = ['maxresdefault', 'hqdefault'];

    private const MAX_BYTES = 2097152;

    private const TIMEOUT = 5;

    public function __invoke(Request $request): Response
    {
        $id = (string)$request->query('v', '');

        if (!preg_match(self::ID_PATTERN, $id)) {
            return $this->blank();
        }

        $path = storage_path("app/cookie-consent-thumbnails/$id.jpg");

        if (!is_file($path) && !$this->fetch($id, $path)) {

            return $this->blank(300);
        }

        return response()->file($path, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function fetch(string $id, string $path): bool
    {
        foreach (self::QUALITIES as $quality) {
            try {
                $response = Http::timeout(self::TIMEOUT)
                    ->accept('image/jpeg,image/*')
                    ->get("https://i.ytimg.com/vi/$id/$quality.jpg");
            } catch (Throwable) {
                continue;
            }

            if (!$response->successful()) {
                continue;
            }

            $body = $response->body();

            if (strlen($body) < 1024 || strlen($body) > self::MAX_BYTES) {
                continue;
            }

            try {
                File::ensureDirectoryExists(dirname($path));
                File::put($path, $body);
            } catch (Throwable $e) {
                Log::warning("Could not cache the poster for $id: {$e->getMessage()}");

                return false;
            }

            return true;
        }

        return false;
    }

    /** A transparent pixel: the facade's gradient shows through. */
    private function blank(int $maxAge = 31536000): Response
    {
        return new Response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => "public, max-age=$maxAge",
        ]);
    }
}

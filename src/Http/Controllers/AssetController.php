<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webmarka\CookieConsent\CookieConsent;

final class AssetController
{
    public function __invoke(Request $request, CookieConsent $cookieConsent, string $file): BinaryFileResponse
    {
        $contentType = CookieConsent::ASSETS[$file] ?? null;
        $path = $cookieConsent->assetPath($file);

        if ($contentType === null || ! is_file($path)) {
            throw new NotFoundHttpException;
        }

        $response = new BinaryFileResponse($path, 200, [
            'Content-Type' => $contentType,
            'X-Content-Type-Options' => 'nosniff',
        ], public: true, autoEtag: true, autoLastModified: true);

        // Long-term caching only when the URL carries the current content hash.
        if ($request->query('v') === $cookieConsent->assetVersion($file)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        } else {
            $response->setMaxAge(300);
        }

        $response->isNotModified($request);

        return $response;
    }
}

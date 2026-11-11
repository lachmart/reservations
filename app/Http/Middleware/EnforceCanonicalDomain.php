<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceCanonicalDomain
{
    /**
     * In production, 301-redirect any non-canonical host (e.g. the old
     * api.marakarental.cz) to the canonical host from APP_URL
     * (www.marakarental.cz), preserving the path and query string.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $canonical = parse_url((string) config('app.url'), PHP_URL_HOST);

            if ($canonical && $request->getHost() !== $canonical) {
                return redirect()->away(
                    rtrim((string) config('app.url'), '/').$request->getRequestUri(),
                    301
                );
            }
        }

        return $next($request);
    }
}

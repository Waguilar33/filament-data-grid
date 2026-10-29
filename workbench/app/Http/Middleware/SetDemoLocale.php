<?php

declare(strict_types=1);

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetDemoLocale
{
    private const array LOCALES = ['en', 'es'];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('locale');

        if (is_string($requested) && in_array($requested, self::LOCALES, true)) {
            $request->session()->put('demo_locale', $requested);
        }

        $locale = $request->session()->get('demo_locale', 'en');

        app()->setLocale(is_string($locale) ? $locale : 'en');

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->query('locale'), ['en', 'ar', 'fr'], true)) {
            $request->session()->put('locale', $request->query('locale'));
        }

        app()->setLocale((string) $request->session()->get('locale', 'en'));

        return $next($request);
    }
}

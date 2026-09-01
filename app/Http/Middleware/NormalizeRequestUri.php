<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NormalizeRequestUri
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->getPathInfo();

        if ($path !== '/' && str_contains($path, '//')) {
            $normalized = preg_replace('#/+#', '/', $path) ?: '/';
            $query = $request->getQueryString();

            return redirect()->to($normalized . ($query ? '?' . $query : ''), 301);
        }

        return $next($request);
    }
}

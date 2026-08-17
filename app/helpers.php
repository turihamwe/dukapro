<?php

if (! function_exists('tenant_route')) {
    function tenant_route(string $name, array $parameters = [], bool $absolute = true): string
    {
        $user = auth()->user();

        if (! $user || ! $user->business) {
            throw new RuntimeException('Cannot generate tenant route without an authenticated business user.');
        }

        return route($name, array_merge(['business' => $user->business->slug], $parameters), $absolute);
    }
}

if (! function_exists('tenant_slug')) {
    function tenant_slug(): string
    {
        $user = auth()->user();

        return $user && $user->business ? $user->business->slug : '';
    }
}

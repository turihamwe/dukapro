<?php

namespace App\Http\Controllers;

use App\Support\AppLogoUrl;
use Illuminate\Http\RedirectResponse;

class HomeRedirectController extends Controller
{
    public function dashboard(): RedirectResponse
    {
        abort_unless(auth()->check(), 403);

        $url = AppLogoUrl::resolve();

        if ($url === route('home') && auth()->check()) {
            abort(403);
        }

        return redirect()->to($url);
    }
}

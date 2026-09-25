<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->business_id, 403);

        return view('downloads.index');
    }
}

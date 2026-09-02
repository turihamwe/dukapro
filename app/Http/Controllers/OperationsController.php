<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OperationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user->can('view-inventory')
                || $user->can('record-expenses')
                || $user->can('log-damages'),
            403
        );

        return view('operations.index');
    }
}

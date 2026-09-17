<?php

namespace App\Http\Controllers;

use App\Services\WeafAccountProvisioner;
use App\Support\EfrisCompliance;
use Illuminate\Http\Request;
use RuntimeException;

class BusinessEfrisController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-settings');
        $this->middleware('management.access');
    }

    public function connect(Request $request, WeafAccountProvisioner $provisioner)
    {
        $request->validate([
            'weaf_password' => 'nullable|string|max:255',
        ]);

        if (! EfrisCompliance::globallyEnabled()) {
            return back()->withErrors([
                'efris_connect' => 'EFRIS is not available on this platform.',
            ]);
        }

        $business = $request->user()->business;
        $business->load('efrisSetting');

        $settings = $business->efrisSetting;

        if (! EfrisCompliance::isAdminUnlocked($settings)) {
            return back()->withErrors([
                'efris_connect' => 'EFRIS is not enabled for your business. Contact support or your administrator to unlock EFRIS compliance options.',
            ]);
        }

        try {
            $result = $provisioner->connect(
                $business,
                $request->user(),
                $request->filled('weaf_password') ? $request->input('weaf_password') : null
            );
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->withErrors(['efris_connect' => $e->getMessage()]);
        }

        return back()->with('efris_connect_result', $result);
    }
}

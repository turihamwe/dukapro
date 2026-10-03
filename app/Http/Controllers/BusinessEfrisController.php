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

        $settingsUrl = tenant_route('tenant.business.edit') . '#efris-weaf';

        if (! EfrisCompliance::globallyEnabled()) {
            return redirect()->to($settingsUrl)->withErrors([
                'efris_connect' => 'EFRIS is not available on this platform.',
            ]);
        }

        $business = $request->user()->business;
        $business->load('efrisSetting');

        $settings = $business->efrisSetting;

        if (! EfrisCompliance::isAdminUnlocked($settings)) {
            return redirect()->to($settingsUrl)->withErrors([
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
            return redirect()
                ->to(tenant_route('tenant.business.edit') . '#efris-weaf')
                ->withInput()
                ->withErrors(['efris_connect' => $e->getMessage()]);
        }

        return $this->redirectAfterConnect($result);
    }

    protected function redirectAfterConnect(array $result)
    {
        $status = (string) ($result['status'] ?? '');
        $message = (string) ($result['message'] ?? 'WEAF connection updated.');

        $redirect = redirect()
            ->to(tenant_route('tenant.business.edit') . '#efris-weaf')
            ->with('efris_connect_result', $result);

        if (in_array($status, [
            WeafAccountProvisioner::STATUS_CONNECTED,
            WeafAccountProvisioner::STATUS_TOKEN_READY,
        ], true)) {
            return $redirect->with('success', $message);
        }

        if (in_array($status, [
            WeafAccountProvisioner::STATUS_EMAIL_VERIFICATION,
            WeafAccountProvisioner::STATUS_COMPANY_PENDING,
            WeafAccountProvisioner::STATUS_REGISTERED,
        ], true)) {
            return $redirect->with('warning', $message);
        }

        return $redirect->with('success', $message);
    }
}

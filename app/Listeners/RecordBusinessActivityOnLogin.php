<?php

namespace App\Listeners;

use App\Services\BusinessActivityService;
use Illuminate\Auth\Events\Login;

class RecordBusinessActivityOnLogin
{
    protected BusinessActivityService $activityService;

    public function __construct(BusinessActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user || ! $user->business_id) {
            return;
        }

        $this->activityService->recordFromId((int) $user->business_id, true);
    }
}

<?php

return [
    /*
    | Level 1 — platform master. When false, branch context switching is hidden everywhere.
    */
    'use_branch_impersonation' => env('USE_BRANCH_IMPERSONATION', false),

    'unlock_setting' => 'branch_impersonation_admin_unlocked',
];

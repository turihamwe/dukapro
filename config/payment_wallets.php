<?php

return [
    /*
    | Level 1 — platform master. When false, ledger edit/delete is invisible everywhere.
    */
    'use_wallet_ledger_adjustments' => env('USE_WALLET_LEDGER_ADJUSTMENTS', false),

    'unlock_setting' => 'wallet_ledger_adjustments_admin_unlocked',
];

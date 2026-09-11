<?php

return [
    'default_commission_rate' => 0.10,
    'recruitment_open' => env('AFFILIATE_RECRUITMENT_OPEN', true),
    'referral_session_key' => 'affiliate_referral_code',
    'sub_referral_session_key' => 'affiliate_sub_referral_code',
    'referral_code_length' => 6,
    'referral_code_max_length' => 8,
    'referral_code_charset' => '23456789abcdefghjkmnpqrstuvwxyz',
    'system_default_code' => 'admin',
    'system_default_name' => 'DukaPro Direct',
    'system_default_email' => 'admin@dukapro.com',
    'onboarding_targets' => [
        1 => ['label' => 'Target 1', 'min' => 10],
        2 => ['label' => 'Target 2', 'min' => 50],
        3 => ['label' => 'Target 3', 'min' => 100],
    ],
];

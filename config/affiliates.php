<?php

return [
    'default_commission_rate' => 0.10,
    'recruitment_open' => env('AFFILIATE_RECRUITMENT_OPEN', true),
    'referral_session_key' => 'affiliate_referral_code',
    'referral_code_length' => 6,
    'referral_code_max_length' => 8,
    'referral_code_charset' => '23456789abcdefghjkmnpqrstuvwxyz',
];

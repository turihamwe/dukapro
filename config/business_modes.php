<?php

return [
    'modes' => [
        'service' => [
            'system_setting' => 'use_service_based_mode',
            'owner_setting' => 'service_based_mode_enabled',
            'unlock_setting' => 'service_mode_admin_unlocked',
            'label' => 'Service-based catalog',
        ],
        'rental' => [
            'system_setting' => 'use_rental_mode',
            'owner_setting' => 'rental_mode_enabled',
            'unlock_setting' => 'rental_mode_admin_unlocked',
            'label' => 'Rentals (car hire / property)',
        ],
        'inventory_only' => [
            'system_setting' => 'use_inventory_only_mode',
            'owner_setting' => 'inventory_only_mode_enabled',
            'unlock_setting' => 'inventory_only_mode_admin_unlocked',
            'label' => 'Inventory-only tracking',
        ],
    ],
];

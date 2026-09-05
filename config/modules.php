<?php

/**
 * Registered tenant capability modules.
 *
 * To add a module: create app/Modules/{Name}/{Name}Module.php, add its class here,
 * then follow app/Modules/README.md for routes, gates, and UI.
 */
return [
    App\Modules\Restaurant\RestaurantModule::class,
    App\Modules\BarShift\BarShiftModule::class,
    App\Modules\CatalogVariants\CatalogVariantsModule::class,
];

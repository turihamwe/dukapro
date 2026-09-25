<?php

namespace App\Support;

use App\Enums\BusinessType;
use Illuminate\Support\Str;

class BusinessIndustryCatalog
{
    public const CUSTOM_SUBCATEGORY = '__custom__';

    /**
     * @return array<string, string> slug => label
     */
    public static function masterCategories(): array
    {
        return [
            'retail_wholesale' => 'Retail, Wholesale & Supermarkets',
            'health_pharma' => 'Health & Pharmaceuticals (Pharmacies, Clinics, Drug Shops)',
            'hospitality_food_drink' => 'Hospitality, Food & Drink (Restaurants, Bars, Hotels)',
            'services' => 'Services (Salons, Repair, Professional Services)',
            'rentals_transport' => 'Rentals, Real Estate & Transport (Car Hire, Property, Fleets)',
            'manufacturing_agriculture' => 'Manufacturing & Agriculture',
            'education_training' => 'Education & Training',
            'fuel_energy_automotive' => 'Fuel, Energy & Automotive',
        ];
    }

    /**
     * @return array<string, list<array{slug: string, label: string, legacy_type?: string}>>
     */
    public static function subcategoriesByMaster(): array
    {
        return [
            'retail_wholesale' => [
                ['slug' => 'general_retail', 'label' => 'General retail shop', 'legacy_type' => BusinessType::GENERAL_RETAIL],
                ['slug' => 'supermarket', 'label' => 'Supermarket / hypermarket', 'legacy_type' => BusinessType::SUPERMARKET],
                ['slug' => 'wholesale', 'label' => 'Wholesale / distribution', 'legacy_type' => BusinessType::GENERAL_RETAIL],
                ['slug' => 'grocery_provisions', 'label' => 'Grocery / provisions', 'legacy_type' => BusinessType::GROCERY],
                ['slug' => 'hardware', 'label' => 'Hardware store', 'legacy_type' => BusinessType::HARDWARE],
                ['slug' => 'electronics', 'label' => 'Electronics & appliances', 'legacy_type' => BusinessType::ELECTRONICS],
                ['slug' => 'boutique_clothing', 'label' => 'Boutique / clothing & fashion', 'legacy_type' => BusinessType::BOUTIQUE],
                ['slug' => 'mobile_phones', 'label' => 'Mobile phones & accessories', 'legacy_type' => BusinessType::ELECTRONICS],
                ['slug' => 'furniture_home', 'label' => 'Furniture & home goods', 'legacy_type' => BusinessType::GENERAL_RETAIL],
                ['slug' => 'building_materials', 'label' => 'Building materials & cement', 'legacy_type' => BusinessType::HARDWARE],
                ['slug' => 'stationery_books', 'label' => 'Stationery, books & office supplies', 'legacy_type' => BusinessType::GENERAL_RETAIL],
                ['slug' => 'sports_toys', 'label' => 'Sports, toys & gifts', 'legacy_type' => BusinessType::GENERAL_RETAIL],
            ],
            'health_pharma' => [
                ['slug' => 'pharmacy', 'label' => 'Pharmacy', 'legacy_type' => BusinessType::PHARMACY],
                ['slug' => 'drug_shop', 'label' => 'Licensed drug shop', 'legacy_type' => BusinessType::PHARMACY],
                ['slug' => 'clinic', 'label' => 'Clinic / medical centre', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'dental', 'label' => 'Dental practice', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'optical', 'label' => 'Optical / eyewear', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'veterinary', 'label' => 'Veterinary / animal health', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'medical_lab', 'label' => 'Medical laboratory', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'wellness_herbal', 'label' => 'Wellness / herbal supplements', 'legacy_type' => BusinessType::PHARMACY],
            ],
            'hospitality_food_drink' => [
                ['slug' => 'restaurant', 'label' => 'Restaurant', 'legacy_type' => BusinessType::RESTAURANT],
                ['slug' => 'cafe', 'label' => 'Café / coffee shop', 'legacy_type' => BusinessType::RESTAURANT],
                ['slug' => 'bar_pub', 'label' => 'Bar / pub / lounge', 'legacy_type' => BusinessType::BAR_PUB],
                ['slug' => 'hotel', 'label' => 'Hotel / motel / inn', 'legacy_type' => BusinessType::OTHER, 'enables_hospitality_mode' => true],
                ['slug' => 'guesthouse', 'label' => 'Guest house / B&B', 'legacy_type' => BusinessType::OTHER, 'enables_hospitality_mode' => true],
                ['slug' => 'fast_food', 'label' => 'Fast food / takeaway', 'legacy_type' => BusinessType::RESTAURANT],
                ['slug' => 'bakery', 'label' => 'Bakery / confectionery', 'legacy_type' => BusinessType::RESTAURANT],
                ['slug' => 'catering', 'label' => 'Catering & events food', 'legacy_type' => BusinessType::RESTAURANT],
                ['slug' => 'nightclub', 'label' => 'Nightclub / entertainment venue', 'legacy_type' => BusinessType::BAR_PUB],
            ],
            'services' => [
                ['slug' => 'salon_spa', 'label' => 'Salon / spa / beauty', 'legacy_type' => BusinessType::SALON],
                ['slug' => 'barber', 'label' => 'Barber shop', 'legacy_type' => BusinessType::SALON],
                ['slug' => 'phone_repair', 'label' => 'Phone & electronics repair', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'auto_repair', 'label' => 'Auto / motorcycle repair', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'laundry_dry_clean', 'label' => 'Laundry / dry cleaning', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'professional_consulting', 'label' => 'Consulting & advisory', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'legal_services', 'label' => 'Legal services', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'accounting_bookkeeping', 'label' => 'Accounting / bookkeeping', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'cleaning_services', 'label' => 'Cleaning & fumigation', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'photography_video', 'label' => 'Photography / videography', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'events_decor', 'label' => 'Events & décor', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'it_services', 'label' => 'IT support & software services', 'legacy_type' => BusinessType::OTHER],
            ],
            'rentals_transport' => [
                ['slug' => 'car_hire', 'label' => 'Car hire / rental', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'boda_fleet', 'label' => 'Boda-boda / motorcycle fleet', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'property_rental', 'label' => 'Property rental / real estate', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'equipment_rental', 'label' => 'Equipment & tools rental', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'logistics_courier', 'label' => 'Logistics / courier / delivery', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'warehouse_storage', 'label' => 'Warehouse / storage', 'legacy_type' => BusinessType::OTHER],
            ],
            'manufacturing_agriculture' => [
                ['slug' => 'farm_produce', 'label' => 'Farm produce & fresh market', 'legacy_type' => BusinessType::GROCERY],
                ['slug' => 'agro_inputs', 'label' => 'Agro-inputs (seeds, feeds, fertiliser)', 'legacy_type' => BusinessType::HARDWARE],
                ['slug' => 'food_processing', 'label' => 'Food processing / packaging', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'light_manufacturing', 'label' => 'Light manufacturing / workshop', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'livestock', 'label' => 'Livestock / poultry', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'fishery', 'label' => 'Fishery / aquaculture', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'timber_wood', 'label' => 'Timber / wood products', 'legacy_type' => BusinessType::OTHER],
            ],
            'education_training' => [
                ['slug' => 'school', 'label' => 'School (primary / secondary)', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'nursery_daycare', 'label' => 'Nursery / daycare', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'training_centre', 'label' => 'Training centre / institute', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'vocational', 'label' => 'Vocational / skills academy', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'driving_school', 'label' => 'Driving school', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'tutoring', 'label' => 'Tutoring / coaching', 'legacy_type' => BusinessType::OTHER],
            ],
            'fuel_energy_automotive' => [
                ['slug' => 'fuel_station', 'label' => 'Fuel station / pump', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'lpg_gas', 'label' => 'LPG / gas refill', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'garage_mechanic', 'label' => 'Garage / mechanic workshop', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'car_wash', 'label' => 'Car wash / detailing', 'legacy_type' => BusinessType::OTHER],
                ['slug' => 'auto_spares', 'label' => 'Auto spare parts shop', 'legacy_type' => BusinessType::HARDWARE],
                ['slug' => 'tyre_battery', 'label' => 'Tyres & batteries', 'legacy_type' => BusinessType::HARDWARE],
                ['slug' => 'solar_energy', 'label' => 'Solar & renewable energy', 'legacy_type' => BusinessType::ELECTRONICS],
            ],
        ];
    }

    /**
     * Lightweight JSON-friendly payload for Alpine.js on registration.
     */
    public static function forClient(): array
    {
        $masters = [];
        foreach (self::masterCategories() as $key => $label) {
            $masters[] = ['key' => $key, 'label' => $label];
        }

        $subcategories = [];
        foreach (self::subcategoriesByMaster() as $masterKey => $items) {
            $subcategories[$masterKey] = array_map(static function (array $item) {
                return [
                    'slug' => $item['slug'],
                    'label' => $item['label'],
                ];
            }, $items);
        }

        return [
            'masters' => $masters,
            'subcategories' => $subcategories,
            'customValue' => self::CUSTOM_SUBCATEGORY,
            'customOptionLabel' => 'Other — type your own subcategory',
        ];
    }

    public static function isValidMaster(?string $master): bool
    {
        return $master !== null && $master !== '' && isset(self::masterCategories()[$master]);
    }

    public static function isValidSubcategorySlug(string $master, string $slug): bool
    {
        if (! self::isValidMaster($master)) {
            return false;
        }

        foreach (self::subcategoriesByMaster()[$master] ?? [] as $item) {
            if ($item['slug'] === $slug) {
                return true;
            }
        }

        return false;
    }

    public static function sanitizeCustomSubcategory(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return Str::limit($value, 80, '');
    }

    public static function resolveLegacyBusinessType(string $master, string $storedSubcategory): string
    {
        if (self::isValidSubcategorySlug($master, $storedSubcategory)) {
            foreach (self::subcategoriesByMaster()[$master] as $item) {
                if ($item['slug'] === $storedSubcategory && ! empty($item['legacy_type'])) {
                    return $item['legacy_type'];
                }
            }
        }

        return self::masterDefaultLegacyType($master);
    }

    public static function masterDefaultLegacyType(string $master): string
    {
        $map = [
            'retail_wholesale' => BusinessType::GENERAL_RETAIL,
            'health_pharma' => BusinessType::PHARMACY,
            'hospitality_food_drink' => BusinessType::RESTAURANT,
            'services' => BusinessType::SALON,
            'rentals_transport' => BusinessType::OTHER,
            'manufacturing_agriculture' => BusinessType::GROCERY,
            'education_training' => BusinessType::OTHER,
            'fuel_energy_automotive' => BusinessType::OTHER,
        ];

        return $map[$master] ?? BusinessType::OTHER;
    }

    public static function masterLabel(?string $master): string
    {
        if ($master === null || $master === '') {
            return 'Not set';
        }

        return self::masterCategories()[$master] ?? ucfirst(str_replace('_', ' ', $master));
    }

    public static function subcategoryLabel(?string $master, ?string $subcategory): string
    {
        if ($subcategory === null || $subcategory === '') {
            return 'Not set';
        }

        if (self::isValidMaster($master) && self::isValidSubcategorySlug($master, $subcategory)) {
            foreach (self::subcategoriesByMaster()[$master] as $item) {
                if ($item['slug'] === $subcategory) {
                    return $item['label'];
                }
            }
        }

        return $subcategory;
    }

    public static function industrySummary(?string $master, ?string $subcategory): string
    {
        if (! self::isValidMaster($master)) {
            return BusinessType::label(null);
        }

        $subLabel = self::subcategoryLabel($master, $subcategory);

        return $subLabel . ' · ' . self::masterLabel($master);
    }

    public static function subcategoryEnablesHospitalityMode(?string $master, ?string $subcategory): bool
    {
        if (! self::isValidMaster($master) || $subcategory === null || $subcategory === '') {
            return false;
        }

        foreach (self::subcategoriesByMaster()[$master] ?? [] as $item) {
            if ($item['slug'] === $subcategory) {
                return ! empty($item['enables_hospitality_mode']);
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function hospitalityRegistrationPreset(): array
    {
        return [
            'hospitality_mode_admin_unlocked' => true,
            'hospitality_mode' => true,
        ];
    }
}

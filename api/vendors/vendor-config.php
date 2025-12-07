<?php
/**
 * WYATT XXX COLE - Vendor Integration Configuration
 * Centralized configuration for all POD, Dropshipping, and Fulfillment vendors
 */

// Load environment
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

/**
 * VENDOR CONFIGURATIONS
 * All POD, Dropshipping, and Fulfillment Partners
 */
return [
    // ═══════════════════════════════════════════════════════════════
    // PRINT ON DEMAND (POD) VENDORS
    // ═══════════════════════════════════════════════════════════════

    'pod_vendors' => [
        // PRINTFUL - Premium POD (US/EU fulfillment)
        'printful' => [
            'name' => 'Printful',
            'enabled' => true,
            'priority' => 1,
            'api_base' => 'https://api.printful.com',
            'api_key' => $_ENV['PRINTFUL_API_KEY'] ?? '',
            'webhook_secret' => $_ENV['PRINTFUL_WEBHOOK_SECRET'] ?? '',
            'fulfillment_countries' => ['US', 'EU', 'CA', 'AU', 'JP'],
            'categories' => ['apparel', 'accessories', 'home', 'prints'],
            'features' => ['embroidery', 'dtg', 'sublimation', 'cut_sew'],
            'avg_production_days' => 3,
            'shipping_methods' => ['standard', 'express', 'overnight'],
            'docs' => 'https://developers.printful.com/docs/'
        ],

        // PRINTIFY - Multi-provider POD network
        'printify' => [
            'name' => 'Printify',
            'enabled' => true,
            'priority' => 2,
            'api_base' => 'https://api.printify.com/v1',
            'api_key' => $_ENV['PRINTIFY_API_KEY'] ?? '',
            'shop_id' => $_ENV['PRINTIFY_SHOP_ID'] ?? '',
            'fulfillment_countries' => ['US', 'EU', 'UK', 'CA', 'AU'],
            'categories' => ['apparel', 'accessories', 'home', 'stickers'],
            'features' => ['multi_provider', 'dtg', 'sublimation'],
            'avg_production_days' => 4,
            'docs' => 'https://developers.printify.com/'
        ],

        // CUSTOMCAT - Budget-friendly POD
        'customcat' => [
            'name' => 'CustomCat',
            'enabled' => true,
            'priority' => 3,
            'api_base' => 'https://api.customcat.com',
            'api_key' => $_ENV['CUSTOMCAT_API_KEY'] ?? '',
            'fulfillment_countries' => ['US'],
            'categories' => ['apparel', 'drinkware', 'accessories'],
            'features' => ['dtg', 'sublimation', 'fast_turnaround'],
            'avg_production_days' => 2,
            'docs' => 'https://customcat.com/api-documentation/'
        ],

        // GOOTEN - Global POD network
        'gooten' => [
            'name' => 'Gooten',
            'enabled' => true,
            'priority' => 4,
            'api_base' => 'https://api.gooten.com/api',
            'api_key' => $_ENV['GOOTEN_API_KEY'] ?? '',
            'recipe_id' => $_ENV['GOOTEN_RECIPE_ID'] ?? '',
            'fulfillment_countries' => ['US', 'EU', 'UK', 'AU', 'CN'],
            'categories' => ['apparel', 'home', 'accessories', 'wall_art', 'photo'],
            'features' => ['global_network', 'photo_products', 'home_decor'],
            'avg_production_days' => 5,
            'docs' => 'https://www.gooten.com/api/'
        ],

        // GELATO - Global print network
        'gelato' => [
            'name' => 'Gelato',
            'enabled' => true,
            'priority' => 5,
            'api_base' => 'https://api.gelato.com/v4',
            'api_key' => $_ENV['GELATO_API_KEY'] ?? '',
            'fulfillment_countries' => ['US', 'EU', 'UK', 'CA', 'AU', 'JP', 'SG'],
            'categories' => ['apparel', 'wall_art', 'photo_books', 'cards', 'mugs'],
            'features' => ['local_production', 'eco_friendly', '32_countries'],
            'avg_production_days' => 3,
            'docs' => 'https://developers.gelato.com/'
        ],

        // PRODIGI (formerly Pwinty) - Premium prints
        'prodigi' => [
            'name' => 'Prodigi',
            'enabled' => true,
            'priority' => 6,
            'api_base' => 'https://api.prodigi.com/v4.0',
            'api_key' => $_ENV['PRODIGI_API_KEY'] ?? '',
            'fulfillment_countries' => ['US', 'UK', 'EU', 'AU'],
            'categories' => ['wall_art', 'prints', 'canvas', 'photo'],
            'features' => ['premium_prints', 'fine_art', 'framing'],
            'avg_production_days' => 4,
            'docs' => 'https://www.prodigi.com/print-api/docs/'
        ],

        // TEELAUNCH - Shopify-integrated POD
        'teelaunch' => [
            'name' => 'Teelaunch',
            'enabled' => true,
            'priority' => 7,
            'api_base' => 'https://app.teelaunch.com/api/v1',
            'api_key' => $_ENV['TEELAUNCH_API_KEY'] ?? '',
            'fulfillment_countries' => ['US'],
            'categories' => ['apparel', 'drinkware', 'accessories', 'home'],
            'features' => ['jewelry', 'pet_products', 'tech_accessories'],
            'avg_production_days' => 4,
            'docs' => 'https://teelaunch.com/pages/api'
        ],

        // SCALABLE PRESS - Enterprise POD
        'scalablepress' => [
            'name' => 'Scalable Press',
            'enabled' => true,
            'priority' => 8,
            'api_base' => 'https://api.scalablepress.com/v3',
            'api_key' => $_ENV['SCALABLEPRESS_API_KEY'] ?? '',
            'fulfillment_countries' => ['US'],
            'categories' => ['apparel', 'posters', 'stickers'],
            'features' => ['screen_print', 'dtg', 'embroidery', 'bulk_orders'],
            'avg_production_days' => 5,
            'docs' => 'https://scalablepress.com/docs'
        ],

        // MERCHIZE - Asian POD
        'merchize' => [
            'name' => 'Merchize',
            'enabled' => true,
            'priority' => 9,
            'api_base' => 'https://api.merchize.com/v1',
            'api_key' => $_ENV['MERCHIZE_API_KEY'] ?? '',
            'fulfillment_countries' => ['VN', 'US', 'EU'],
            'categories' => ['apparel', 'home', 'accessories'],
            'features' => ['low_cost', 'jewelry', 'shoes'],
            'avg_production_days' => 7,
            'docs' => 'https://merchize.com/api-documentation/'
        ],

        // AWKWARD STYLES - US POD
        'awkwardstyles' => [
            'name' => 'Awkward Styles',
            'enabled' => true,
            'priority' => 10,
            'api_base' => 'https://api.awkwardstyles.com/v1',
            'api_key' => $_ENV['AWKWARDSTYLES_API_KEY'] ?? '',
            'fulfillment_countries' => ['US'],
            'categories' => ['apparel', 'baby', 'accessories'],
            'features' => ['baby_clothes', 'matching_sets', 'family_apparel'],
            'avg_production_days' => 3,
            'docs' => 'https://awkwardstyles.com/api'
        ],

        // T-POP - EU Eco-friendly POD
        'tpop' => [
            'name' => 'T-Pop',
            'enabled' => true,
            'priority' => 11,
            'api_base' => 'https://api.t-pop.fr/v2',
            'api_key' => $_ENV['TPOP_API_KEY'] ?? '',
            'fulfillment_countries' => ['FR', 'EU'],
            'categories' => ['apparel', 'bags', 'accessories'],
            'features' => ['eco_friendly', 'organic', 'eu_based'],
            'avg_production_days' => 5,
            'docs' => 'https://t-pop.fr/en/api'
        ],

        // MONSTER DIGITAL - Enterprise DTG
        'monsterdigital' => [
            'name' => 'Monster Digital',
            'enabled' => true,
            'priority' => 12,
            'api_base' => 'https://api.monsterdigital.com/v1',
            'api_key' => $_ENV['MONSTERDIGITAL_API_KEY'] ?? '',
            'fulfillment_countries' => ['US'],
            'categories' => ['apparel'],
            'features' => ['dtg_specialist', 'all_over_print', 'cut_sew'],
            'avg_production_days' => 4,
            'docs' => 'https://monsterdigital.com/api-docs'
        ],

        // APLIIQ - Premium custom streetwear
        'apliiq' => [
            'name' => 'Apliiq',
            'enabled' => true,
            'priority' => 13,
            'api_base' => 'https://api.apliiq.com/v1',
            'api_key' => $_ENV['APLIIQ_API_KEY'] ?? '',
            'fulfillment_countries' => ['US'],
            'categories' => ['apparel'],
            'features' => ['private_label', 'custom_tags', 'premium_blanks'],
            'avg_production_days' => 7,
            'docs' => 'https://apliiq.com/api'
        ],

        // SUBLIMINATOR - Sublimation specialist
        'subliminator' => [
            'name' => 'Subliminator',
            'enabled' => true,
            'priority' => 14,
            'api_base' => 'https://api.subliminator.com/v1',
            'api_key' => $_ENV['SUBLIMINATOR_API_KEY'] ?? '',
            'fulfillment_countries' => ['US', 'EU'],
            'categories' => ['apparel', 'accessories', 'home'],
            'features' => ['all_over_print', 'sublimation_specialist'],
            'avg_production_days' => 5,
            'docs' => 'https://subliminator.com/api'
        ],

        // PRINT AURA - No minimums POD
        'printaura' => [
            'name' => 'Print Aura',
            'enabled' => true,
            'priority' => 15,
            'api_base' => 'https://api.printaura.com/api',
            'api_key' => $_ENV['PRINTAURA_API_KEY'] ?? '',
            'fulfillment_countries' => ['US'],
            'categories' => ['apparel', 'accessories'],
            'features' => ['no_minimums', 'dtg', 'screen_print'],
            'avg_production_days' => 5,
            'docs' => 'https://printaura.com/api'
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    // DROPSHIPPING VENDORS
    // ═══════════════════════════════════════════════════════════════

    'dropship_vendors' => [
        // ALIEXPRESS - Massive catalog
        'aliexpress' => [
            'name' => 'AliExpress',
            'enabled' => true,
            'priority' => 1,
            'api_base' => 'https://api-sg.aliexpress.com/sync',
            'app_key' => $_ENV['ALIEXPRESS_APP_KEY'] ?? '',
            'app_secret' => $_ENV['ALIEXPRESS_APP_SECRET'] ?? '',
            'access_token' => $_ENV['ALIEXPRESS_ACCESS_TOKEN'] ?? '',
            'fulfillment_countries' => ['CN', 'RU', 'ES', 'FR', 'PL'],
            'categories' => ['electronics', 'fashion', 'home', 'beauty', 'toys', 'sports'],
            'features' => ['massive_catalog', 'dropship_center', 'image_search'],
            'avg_shipping_days' => 15,
            'docs' => 'https://developers.aliexpress.com/'
        ],

        // ALIBABA - B2B sourcing
        'alibaba' => [
            'name' => 'Alibaba',
            'enabled' => true,
            'priority' => 2,
            'api_base' => 'https://api.alibaba.com',
            'app_key' => $_ENV['ALIBABA_APP_KEY'] ?? '',
            'app_secret' => $_ENV['ALIBABA_APP_SECRET'] ?? '',
            'fulfillment_countries' => ['CN'],
            'categories' => ['wholesale', 'manufacturing', 'bulk'],
            'features' => ['bulk_pricing', 'custom_manufacturing', 'trade_assurance'],
            'min_order_qty' => 100,
            'docs' => 'https://developers.alibaba.com/'
        ],

        // CJ DROPSHIPPING - Fast China fulfillment
        'cjdropshipping' => [
            'name' => 'CJ Dropshipping',
            'enabled' => true,
            'priority' => 3,
            'api_base' => 'https://developers.cjdropshipping.com/api2.0',
            'api_key' => $_ENV['CJ_API_KEY'] ?? '',
            'email' => $_ENV['CJ_EMAIL'] ?? '',
            'fulfillment_countries' => ['CN', 'US', 'EU', 'TH'],
            'categories' => ['general', 'fashion', 'electronics', 'home'],
            'features' => ['us_warehouse', 'product_sourcing', 'branding', 'fast_shipping'],
            'avg_shipping_days' => 7,
            'docs' => 'https://developers.cjdropshipping.com/'
        ],

        // SPOCKET - US/EU dropshipping
        'spocket' => [
            'name' => 'Spocket',
            'enabled' => true,
            'priority' => 4,
            'api_base' => 'https://api.spocket.co/v1',
            'api_key' => $_ENV['SPOCKET_API_KEY'] ?? '',
            'fulfillment_countries' => ['US', 'EU', 'CA', 'UK'],
            'categories' => ['fashion', 'home', 'beauty', 'tech'],
            'features' => ['us_eu_suppliers', 'fast_shipping', 'branded_invoicing'],
            'avg_shipping_days' => 5,
            'docs' => 'https://spocket.co/integrations'
        ],

        // MODALYST - Premium dropshipping
        'modalyst' => [
            'name' => 'Modalyst',
            'enabled' => true,
            'priority' => 5,
            'api_base' => 'https://api.modalyst.co/v1',
            'api_key' => $_ENV['MODALYST_API_KEY'] ?? '',
            'fulfillment_countries' => ['US', 'EU'],
            'categories' => ['fashion', 'accessories', 'beauty'],
            'features' => ['brand_names', 'us_suppliers', 'low_cost_goods'],
            'avg_shipping_days' => 6,
            'docs' => 'https://modalyst.co/integrations'
        ],

        // SYNCEE - Global dropshipping marketplace
        'syncee' => [
            'name' => 'Syncee',
            'enabled' => true,
            'priority' => 6,
            'api_base' => 'https://api.syncee.co/v1',
            'api_key' => $_ENV['SYNCEE_API_KEY'] ?? '',
            'fulfillment_countries' => ['US', 'EU', 'UK', 'CA', 'AU'],
            'categories' => ['fashion', 'electronics', 'home', 'pet'],
            'features' => ['auto_updates', 'multi_supplier', 'global_catalog'],
            'avg_shipping_days' => 7,
            'docs' => 'https://syncee.co/api'
        ],

        // DOBA - US dropshipping
        'doba' => [
            'name' => 'Doba',
            'enabled' => true,
            'priority' => 7,
            'api_base' => 'https://api.doba.com/v4',
            'api_key' => $_ENV['DOBA_API_KEY'] ?? '',
            'fulfillment_countries' => ['US'],
            'categories' => ['general', 'electronics', 'fashion', 'home'],
            'features' => ['us_based', 'curated_suppliers', 'inventory_sync'],
            'avg_shipping_days' => 5,
            'docs' => 'https://doba.com/api'
        ],

        // SALEHOO - Wholesale directory
        'salehoo' => [
            'name' => 'SaleHoo',
            'enabled' => true,
            'priority' => 8,
            'api_base' => 'https://api.salehoo.com/v1',
            'api_key' => $_ENV['SALEHOO_API_KEY'] ?? '',
            'fulfillment_countries' => ['US', 'UK', 'AU', 'NZ'],
            'categories' => ['wholesale', 'dropship', 'general'],
            'features' => ['vetted_suppliers', 'market_research', 'training'],
            'avg_shipping_days' => 7,
            'docs' => 'https://salehoo.com/api'
        ],

        // MEGAGOODS - US electronics dropship
        'megagoods' => [
            'name' => 'MegaGoods',
            'enabled' => true,
            'priority' => 9,
            'api_base' => 'https://api.megagoods.com/v1',
            'api_key' => $_ENV['MEGAGOODS_API_KEY'] ?? '',
            'fulfillment_countries' => ['US'],
            'categories' => ['electronics', 'consumer_tech'],
            'features' => ['electronics_specialist', 'blind_dropship'],
            'avg_shipping_days' => 3,
            'docs' => 'https://megagoods.com/api'
        ],

        // INVENTORY SOURCE - Supplier automation
        'inventorysource' => [
            'name' => 'Inventory Source',
            'enabled' => true,
            'priority' => 10,
            'api_base' => 'https://api.inventorysource.com/v2',
            'api_key' => $_ENV['INVENTORYSOURCE_API_KEY'] ?? '',
            'fulfillment_countries' => ['US', 'EU'],
            'categories' => ['general', 'multi_supplier'],
            'features' => ['multi_vendor', 'automation', 'inventory_sync'],
            'avg_shipping_days' => 5,
            'docs' => 'https://inventorysource.com/api'
        ],

        // DSERS - AliExpress partner
        'dsers' => [
            'name' => 'DSers',
            'enabled' => true,
            'priority' => 11,
            'api_base' => 'https://api.dsers.com/v1',
            'api_key' => $_ENV['DSERS_API_KEY'] ?? '',
            'fulfillment_countries' => ['CN'],
            'categories' => ['aliexpress'],
            'features' => ['bulk_orders', 'supplier_optimizer', 'auto_order'],
            'avg_shipping_days' => 12,
            'docs' => 'https://dsers.com/api'
        ],

        // ZENDROP - US fulfillment focus
        'zendrop' => [
            'name' => 'Zendrop',
            'enabled' => true,
            'priority' => 12,
            'api_base' => 'https://api.zendrop.com/v1',
            'api_key' => $_ENV['ZENDROP_API_KEY'] ?? '',
            'fulfillment_countries' => ['US', 'CN'],
            'categories' => ['general', 'fashion', 'electronics'],
            'features' => ['us_fulfillment', 'branding', 'subscription_boxes'],
            'avg_shipping_days' => 6,
            'docs' => 'https://zendrop.com/api'
        ],

        // DROPIFIED - Automation platform
        'dropified' => [
            'name' => 'Dropified',
            'enabled' => true,
            'priority' => 13,
            'api_base' => 'https://api.dropified.com/v1',
            'api_key' => $_ENV['DROPIFIED_API_KEY'] ?? '',
            'fulfillment_countries' => ['US', 'CN'],
            'categories' => ['multi_source'],
            'features' => ['auto_fulfill', 'profit_dashboard', 'multi_store'],
            'avg_shipping_days' => 10,
            'docs' => 'https://dropified.com/api'
        ],

        // WHOLESALE2B - US dropshipping
        'wholesale2b' => [
            'name' => 'Wholesale2B',
            'enabled' => true,
            'priority' => 14,
            'api_base' => 'https://api.wholesale2b.com/v2',
            'api_key' => $_ENV['WHOLESALE2B_API_KEY'] ?? '',
            'fulfillment_countries' => ['US'],
            'categories' => ['general', 'home', 'toys', 'electronics'],
            'features' => ['us_suppliers', 'auto_listing', '1m_products'],
            'avg_shipping_days' => 5,
            'docs' => 'https://wholesale2b.com/api'
        ],

        // BANGGOOD - Electronics/gadgets
        'banggood' => [
            'name' => 'Banggood',
            'enabled' => true,
            'priority' => 15,
            'api_base' => 'https://api.banggood.com/v1',
            'api_key' => $_ENV['BANGGOOD_API_KEY'] ?? '',
            'fulfillment_countries' => ['CN', 'US', 'EU'],
            'categories' => ['electronics', 'gadgets', 'outdoor', 'toys'],
            'features' => ['electronics_focus', 'global_warehouse', 'fast_ship'],
            'avg_shipping_days' => 8,
            'docs' => 'https://developer.banggood.com/'
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    // SHIPPING PARTNERS
    // ═══════════════════════════════════════════════════════════════

    'shipping_partners' => [
        // EASYPOST - Multi-carrier shipping API
        'easypost' => [
            'name' => 'EasyPost',
            'enabled' => true,
            'priority' => 1,
            'api_base' => 'https://api.easypost.com/v2',
            'api_key' => $_ENV['EASYPOST_API_KEY'] ?? '',
            'carriers' => ['USPS', 'UPS', 'FedEx', 'DHL', 'Canada Post', 'Australia Post'],
            'features' => ['rate_shopping', 'tracking', 'insurance', 'returns', 'address_verification'],
            'docs' => 'https://www.easypost.com/docs/api'
        ],

        // SHIPSTATION - Order management & shipping
        'shipstation' => [
            'name' => 'ShipStation',
            'enabled' => true,
            'priority' => 2,
            'api_base' => 'https://ssapi.shipstation.com',
            'api_key' => $_ENV['SHIPSTATION_API_KEY'] ?? '',
            'api_secret' => $_ENV['SHIPSTATION_API_SECRET'] ?? '',
            'carriers' => ['USPS', 'UPS', 'FedEx', 'DHL', 'Amazon', 'Stamps.com'],
            'features' => ['order_management', 'automation_rules', 'branded_tracking', 'inventory'],
            'docs' => 'https://www.shipstation.com/docs/api/'
        ],

        // SHIPPO - Multi-carrier shipping
        'shippo' => [
            'name' => 'Shippo',
            'enabled' => true,
            'priority' => 3,
            'api_base' => 'https://api.goshippo.com',
            'api_key' => $_ENV['SHIPPO_API_KEY'] ?? '',
            'carriers' => ['USPS', 'UPS', 'FedEx', 'DHL', 'Sendle', 'Purolator'],
            'features' => ['discounted_rates', 'tracking', 'returns', 'batch_shipping'],
            'docs' => 'https://goshippo.com/docs/'
        ],

        // AFTERSHIP - Global tracking
        'aftership' => [
            'name' => 'AfterShip',
            'enabled' => true,
            'priority' => 4,
            'api_base' => 'https://api.aftership.com/v4',
            'api_key' => $_ENV['AFTERSHIP_API_KEY'] ?? '',
            'carriers' => ['900+ carriers worldwide'],
            'features' => ['tracking', 'notifications', 'analytics', 'branded_page'],
            'docs' => 'https://docs.aftership.com/'
        ],

        // PIRATESHIP - USPS discounts
        'pirateship' => [
            'name' => 'Pirate Ship',
            'enabled' => true,
            'priority' => 5,
            'api_base' => 'https://api.pirateship.com/v1',
            'api_key' => $_ENV['PIRATESHIP_API_KEY'] ?? '',
            'carriers' => ['USPS', 'UPS'],
            'features' => ['cheapest_usps_rates', 'simple_pricing', 'free_platform'],
            'docs' => 'https://pirateship.com/api'
        ],

        // SHIPHERO - 3PL & fulfillment
        'shiphero' => [
            'name' => 'ShipHero',
            'enabled' => true,
            'priority' => 6,
            'api_base' => 'https://public-api.shiphero.com/graphql',
            'api_key' => $_ENV['SHIPHERO_API_KEY'] ?? '',
            'carriers' => ['Multi-carrier'],
            'features' => ['warehouse_management', '3pl', 'inventory', 'fulfillment'],
            'docs' => 'https://shiphero.com/api'
        ],

        // EASYSHIP - International shipping
        'easyship' => [
            'name' => 'Easyship',
            'enabled' => true,
            'priority' => 7,
            'api_base' => 'https://api.easyship.com/v2',
            'api_key' => $_ENV['EASYSHIP_API_KEY'] ?? '',
            'carriers' => ['250+ couriers globally'],
            'features' => ['international', 'duties_taxes', 'insurance', 'crowdfunding'],
            'docs' => 'https://developers.easyship.com/'
        ],

        // SHIPENGINE - Enterprise shipping
        'shipengine' => [
            'name' => 'ShipEngine',
            'enabled' => true,
            'priority' => 8,
            'api_base' => 'https://api.shipengine.com/v1',
            'api_key' => $_ENV['SHIPENGINE_API_KEY'] ?? '',
            'carriers' => ['USPS', 'UPS', 'FedEx', 'DHL', 'Stamps.com', 'Endicia'],
            'features' => ['address_validation', 'rate_comparison', 'label_generation', 'tracking'],
            'docs' => 'https://www.shipengine.com/docs/'
        ],

        // ORDORO - Multi-channel shipping
        'ordoro' => [
            'name' => 'Ordoro',
            'enabled' => true,
            'priority' => 9,
            'api_base' => 'https://api.ordoro.com',
            'api_key' => $_ENV['ORDORO_API_KEY'] ?? '',
            'carriers' => ['USPS', 'UPS', 'FedEx', 'DHL'],
            'features' => ['multi_channel', 'dropship', 'inventory', 'kitting'],
            'docs' => 'https://docs.ordoro.com/'
        ],

        // SHIPBOB - 3PL fulfillment
        'shipbob' => [
            'name' => 'ShipBob',
            'enabled' => true,
            'priority' => 10,
            'api_base' => 'https://api.shipbob.com/1.0',
            'api_key' => $_ENV['SHIPBOB_API_KEY'] ?? '',
            'carriers' => ['Multi-carrier'],
            'features' => ['2_day_shipping', 'nationwide_warehouses', 'inventory_distribution'],
            'docs' => 'https://developer.shipbob.com/'
        ],

        // DHL ECOMMERCE
        'dhl_ecommerce' => [
            'name' => 'DHL eCommerce',
            'enabled' => true,
            'priority' => 11,
            'api_base' => 'https://api.dhlecs.com/v2',
            'api_key' => $_ENV['DHL_API_KEY'] ?? '',
            'api_secret' => $_ENV['DHL_API_SECRET'] ?? '',
            'carriers' => ['DHL'],
            'features' => ['international', 'parcel', 'returns', 'tracking'],
            'docs' => 'https://developer.dhl.com/'
        ],

        // STAMPS.COM
        'stamps' => [
            'name' => 'Stamps.com',
            'enabled' => true,
            'priority' => 12,
            'api_base' => 'https://api.stamps.com/v1',
            'integration_id' => $_ENV['STAMPS_INTEGRATION_ID'] ?? '',
            'username' => $_ENV['STAMPS_USERNAME'] ?? '',
            'password' => $_ENV['STAMPS_PASSWORD'] ?? '',
            'carriers' => ['USPS'],
            'features' => ['usps_commercial_rates', 'batch', 'scan_forms'],
            'docs' => 'https://developer.stamps.com/'
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    // PAYMENT PROCESSORS (Adult Industry Friendly)
    // ═══════════════════════════════════════════════════════════════

    'payment_processors' => [
        // CCBILL - #1 Adult Industry Payment Processor
        'ccbill' => [
            'name' => 'CCBill',
            'enabled' => true,
            'priority' => 1,
            'api_base' => 'https://api.ccbill.com',
            'account_number' => $_ENV['CCBILL_ACCOUNT'] ?? '',
            'sub_account' => $_ENV['CCBILL_SUBACCOUNT'] ?? '',
            'flex_id' => $_ENV['CCBILL_FLEX_ID'] ?? '',
            'salt' => $_ENV['CCBILL_SALT'] ?? '',
            'features' => ['subscriptions', 'one_time', 'trials', 'crypto', 'global'],
            'adult_friendly' => true,
            'fees' => '10-15% + $0.50',
            'docs' => 'https://ccbill.com/developers'
        ],

        // EPOCH - Major Adult Payment Processor
        'epoch' => [
            'name' => 'Epoch',
            'enabled' => true,
            'priority' => 2,
            'api_base' => 'https://epoch.com/api',
            'company_id' => $_ENV['EPOCH_COMPANY_ID'] ?? '',
            'api_key' => $_ENV['EPOCH_API_KEY'] ?? '',
            'features' => ['subscriptions', 'one_time', 'micropayments', 'global'],
            'adult_friendly' => true,
            'fees' => '10-14% + transaction fee',
            'docs' => 'https://epoch.com/documentation'
        ],

        // SEGPAY - Adult-Focused Payment Solution
        'segpay' => [
            'name' => 'Segpay',
            'enabled' => true,
            'priority' => 3,
            'api_base' => 'https://api.segpay.com',
            'merchant_id' => $_ENV['SEGPAY_MERCHANT_ID'] ?? '',
            'api_key' => $_ENV['SEGPAY_API_KEY'] ?? '',
            'features' => ['subscriptions', 'one_time', 'trials', 'affiliate_payouts'],
            'adult_friendly' => true,
            'fees' => '10-12% + $0.35',
            'docs' => 'https://segpay.com/developers'
        ],

        // VEROTEL - European Adult Payment Processor
        'verotel' => [
            'name' => 'Verotel',
            'enabled' => true,
            'priority' => 4,
            'api_base' => 'https://secure.verotel.com/api',
            'shop_id' => $_ENV['VEROTEL_SHOP_ID'] ?? '',
            'signature_key' => $_ENV['VEROTEL_SIGNATURE_KEY'] ?? '',
            'features' => ['subscriptions', 'one_time', 'eu_focused', 'sepa'],
            'adult_friendly' => true,
            'fees' => '12-15%',
            'docs' => 'https://www.verotel.com/en/integration.html'
        ],

        // STICKY.IO (formerly LimeLight) - High-Risk Merchant
        'sticky' => [
            'name' => 'Sticky.io',
            'enabled' => true,
            'priority' => 5,
            'api_base' => 'https://api.sticky.io/api/v2',
            'api_key' => $_ENV['STICKY_API_KEY'] ?? '',
            'campaign_id' => $_ENV['STICKY_CAMPAIGN_ID'] ?? '',
            'features' => ['subscriptions', 'one_time', 'upsells', 'retention'],
            'adult_friendly' => true,
            'fees' => 'Custom pricing',
            'docs' => 'https://developers.sticky.io/'
        ],

        // ZOMBAIO - Adult Payment Processor
        'zombaio' => [
            'name' => 'Zombaio',
            'enabled' => true,
            'priority' => 6,
            'api_base' => 'https://www.zombaio.com/api',
            'site_id' => $_ENV['ZOMBAIO_SITE_ID'] ?? '',
            'gcs_id' => $_ENV['ZOMBAIO_GCS_ID'] ?? '',
            'features' => ['subscriptions', 'one_time', 'trials'],
            'adult_friendly' => true,
            'fees' => '12-15%',
            'docs' => 'https://www.zombaio.com/developers'
        ],

        // NATS/TOO MUCH MEDIA - Adult Affiliate + Billing
        'nats' => [
            'name' => 'NATS (Too Much Media)',
            'enabled' => true,
            'priority' => 7,
            'api_base' => 'https://api.toomuchmedia.com',
            'api_key' => $_ENV['NATS_API_KEY'] ?? '',
            'affiliate_id' => $_ENV['NATS_AFFILIATE_ID'] ?? '',
            'features' => ['affiliate_tracking', 'billing', 'content_delivery'],
            'adult_friendly' => true,
            'fees' => 'Platform + processor fees',
            'docs' => 'https://toomuchmedia.com/products/nats/'
        ],

        // CRYPTOCURRENCY OPTIONS
        'crypto' => [
            'name' => 'Crypto Payments',
            'enabled' => true,
            'priority' => 8,
            'providers' => [
                'coinbase_commerce' => [
                    'name' => 'Coinbase Commerce',
                    'api_base' => 'https://api.commerce.coinbase.com',
                    'api_key' => $_ENV['COINBASE_COMMERCE_KEY'] ?? '',
                    'webhook_secret' => $_ENV['COINBASE_WEBHOOK_SECRET'] ?? '',
                    'coins' => ['BTC', 'ETH', 'LTC', 'USDC', 'DAI'],
                    'docs' => 'https://commerce.coinbase.com/docs/'
                ],
                'nowpayments' => [
                    'name' => 'NOWPayments',
                    'api_base' => 'https://api.nowpayments.io/v1',
                    'api_key' => $_ENV['NOWPAYMENTS_API_KEY'] ?? '',
                    'coins' => ['BTC', 'ETH', 'LTC', 'USDT', 'XMR', '100+ coins'],
                    'adult_friendly' => true,
                    'docs' => 'https://nowpayments.io/help/api-documentation'
                ],
                'btcpay' => [
                    'name' => 'BTCPay Server',
                    'api_base' => $_ENV['BTCPAY_HOST'] ?? '',
                    'api_key' => $_ENV['BTCPAY_API_KEY'] ?? '',
                    'store_id' => $_ENV['BTCPAY_STORE_ID'] ?? '',
                    'coins' => ['BTC', 'LTC', 'XMR'],
                    'self_hosted' => true,
                    'docs' => 'https://docs.btcpayserver.org/'
                ],
                'plisio' => [
                    'name' => 'Plisio',
                    'api_base' => 'https://plisio.net/api/v1',
                    'api_key' => $_ENV['PLISIO_API_KEY'] ?? '',
                    'coins' => ['BTC', 'ETH', 'LTC', 'DASH', 'XMR', 'USDT'],
                    'adult_friendly' => true,
                    'fees' => '0.5%',
                    'docs' => 'https://plisio.net/documentation'
                ],
            ],
            'features' => ['anonymous', 'low_fees', 'no_chargebacks', 'global'],
            'adult_friendly' => true,
        ],

        // PAXUM - Adult Industry E-Wallet
        'paxum' => [
            'name' => 'Paxum',
            'enabled' => true,
            'priority' => 9,
            'api_base' => 'https://www.paxum.com/payment/api',
            'merchant_id' => $_ENV['PAXUM_MERCHANT_ID'] ?? '',
            'api_key' => $_ENV['PAXUM_API_KEY'] ?? '',
            'features' => ['ewallet', 'bank_transfers', 'payouts', 'mass_pay'],
            'adult_friendly' => true,
            'fees' => '1-3%',
            'docs' => 'https://www.paxum.com/developers'
        ],

        // COSMO PAYMENT - High Risk Processor
        'cosmo' => [
            'name' => 'Cosmo Payment',
            'enabled' => true,
            'priority' => 10,
            'api_base' => 'https://api.cosmopayment.com',
            'merchant_id' => $_ENV['COSMO_MERCHANT_ID'] ?? '',
            'api_key' => $_ENV['COSMO_API_KEY'] ?? '',
            'features' => ['subscriptions', 'one_time', 'high_risk'],
            'adult_friendly' => true,
            'fees' => '8-12%',
            'docs' => 'https://cosmopayment.com/api-docs'
        ],

        // WEBBILLING - Adult Payment Gateway
        'webbilling' => [
            'name' => 'WebBilling',
            'enabled' => true,
            'priority' => 11,
            'api_base' => 'https://www.webbilling.com/api',
            'site_id' => $_ENV['WEBBILLING_SITE_ID'] ?? '',
            'api_key' => $_ENV['WEBBILLING_API_KEY'] ?? '',
            'features' => ['subscriptions', 'one_time', 'trials'],
            'adult_friendly' => true,
            'fees' => '12-15%',
            'docs' => 'https://www.webbilling.com/developers'
        ],

        // PROBILLER - MindGeek's Payment Processor
        'probiller' => [
            'name' => 'Probiller',
            'enabled' => true,
            'priority' => 12,
            'api_base' => 'https://api.probiller.com',
            'site_id' => $_ENV['PROBILLER_SITE_ID'] ?? '',
            'api_key' => $_ENV['PROBILLER_API_KEY'] ?? '',
            'features' => ['subscriptions', 'one_time', 'trials', 'enterprise'],
            'adult_friendly' => true,
            'fees' => 'Enterprise pricing',
            'docs' => 'https://probiller.com/integration'
        ],
    ],
];

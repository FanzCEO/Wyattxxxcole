<?php
/**
 * WYATT XXX COLE - Product Taxonomy System
 * Comprehensive product categorization for POD, Dropshipping, and Merch
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * MASTER PRODUCT TAXONOMY
 * Google Product Category compatible with custom extensions
 */
$TAXONOMY = [
    // ═══════════════════════════════════════════════════════════════
    // APPAREL & CLOTHING
    // ═══════════════════════════════════════════════════════════════
    'apparel' => [
        'name' => 'Apparel & Clothing',
        'google_category' => '166',
        'icon' => '👕',
        'subcategories' => [
            't-shirts' => [
                'name' => 'T-Shirts',
                'google_category' => '212',
                'variants' => ['short-sleeve', 'long-sleeve', 'sleeveless', 'crop-top', 'muscle-tee'],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'],
                'fits' => ['regular', 'slim', 'relaxed', 'oversized'],
                'pod_vendors' => ['printful', 'printify', 'customcat', 'gooten', 'gelato'],
                'print_methods' => ['dtg', 'sublimation', 'screen-print', 'vinyl'],
                'base_cost_range' => [8, 25],
                'retail_price_range' => [19.99, 49.99]
            ],
            'hoodies' => [
                'name' => 'Hoodies & Sweatshirts',
                'google_category' => '5322',
                'variants' => ['pullover-hoodie', 'zip-hoodie', 'crewneck', 'quarter-zip'],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'],
                'fits' => ['regular', 'slim', 'oversized'],
                'pod_vendors' => ['printful', 'printify', 'customcat', 'gooten'],
                'print_methods' => ['dtg', 'embroidery', 'screen-print'],
                'base_cost_range' => [20, 45],
                'retail_price_range' => [44.99, 89.99]
            ],
            'tank-tops' => [
                'name' => 'Tank Tops',
                'google_category' => '5344',
                'variants' => ['standard', 'muscle', 'racerback', 'stringer'],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL'],
                'pod_vendors' => ['printful', 'printify', 'customcat'],
                'print_methods' => ['dtg', 'sublimation'],
                'base_cost_range' => [8, 20],
                'retail_price_range' => [22.99, 39.99]
            ],
            'shorts' => [
                'name' => 'Shorts',
                'google_category' => '5379',
                'variants' => ['athletic', 'swim', 'casual', 'compression'],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL'],
                'pod_vendors' => ['printful', 'printify', 'gooten'],
                'print_methods' => ['sublimation', 'embroidery'],
                'base_cost_range' => [15, 35],
                'retail_price_range' => [34.99, 59.99]
            ],
            'joggers' => [
                'name' => 'Joggers & Sweatpants',
                'google_category' => '5462',
                'variants' => ['joggers', 'sweatpants', 'track-pants'],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL'],
                'pod_vendors' => ['printful', 'printify'],
                'print_methods' => ['dtg', 'embroidery'],
                'base_cost_range' => [20, 40],
                'retail_price_range' => [44.99, 74.99]
            ],
            'underwear' => [
                'name' => 'Underwear',
                'google_category' => '213',
                'variants' => ['boxers', 'briefs', 'boxer-briefs', 'jockstraps', 'thongs'],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL'],
                'pod_vendors' => ['printful', 'merchize', 'subliminator'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [10, 25],
                'retail_price_range' => [24.99, 44.99],
                'adult_content' => true
            ],
            'swimwear' => [
                'name' => 'Swimwear',
                'google_category' => '5424',
                'variants' => ['swim-trunks', 'speedos', 'board-shorts', 'swim-briefs'],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL'],
                'pod_vendors' => ['printful', 'gooten', 'subliminator'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [18, 35],
                'retail_price_range' => [39.99, 64.99]
            ],
            'jackets' => [
                'name' => 'Jackets & Outerwear',
                'google_category' => '5506',
                'variants' => ['bomber', 'denim', 'windbreaker', 'coach', 'varsity'],
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', '2XL'],
                'pod_vendors' => ['printful', 'printify', 'apliiq'],
                'print_methods' => ['embroidery', 'dtg', 'patches'],
                'base_cost_range' => [35, 80],
                'retail_price_range' => [69.99, 149.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // HEADWEAR & ACCESSORIES
    // ═══════════════════════════════════════════════════════════════
    'headwear' => [
        'name' => 'Headwear',
        'google_category' => '173',
        'icon' => '🧢',
        'subcategories' => [
            'caps' => [
                'name' => 'Caps & Hats',
                'google_category' => '179',
                'variants' => ['snapback', 'dad-hat', 'trucker', 'fitted', 'flexfit', '5-panel'],
                'sizes' => ['one-size', 'S/M', 'L/XL', 'fitted-sizes'],
                'pod_vendors' => ['printful', 'printify', 'customcat', 'scalablepress'],
                'print_methods' => ['embroidery', 'patch', 'screen-print'],
                'base_cost_range' => [10, 25],
                'retail_price_range' => [24.99, 44.99]
            ],
            'beanies' => [
                'name' => 'Beanies',
                'google_category' => '176',
                'variants' => ['cuffed', 'slouchy', 'pom-pom'],
                'sizes' => ['one-size'],
                'pod_vendors' => ['printful', 'printify'],
                'print_methods' => ['embroidery', 'patch'],
                'base_cost_range' => [10, 20],
                'retail_price_range' => [22.99, 34.99]
            ],
            'bandanas' => [
                'name' => 'Bandanas',
                'google_category' => '178',
                'variants' => ['square', 'triangle'],
                'sizes' => ['one-size'],
                'pod_vendors' => ['printful', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [5, 12],
                'retail_price_range' => [12.99, 24.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // BAGS & CARRIERS
    // ═══════════════════════════════════════════════════════════════
    'bags' => [
        'name' => 'Bags & Carriers',
        'google_category' => '110',
        'icon' => '👜',
        'subcategories' => [
            'backpacks' => [
                'name' => 'Backpacks',
                'google_category' => '115',
                'variants' => ['school', 'travel', 'mini', 'drawstring'],
                'pod_vendors' => ['printful', 'printify', 'gooten'],
                'print_methods' => ['sublimation', 'dtg'],
                'base_cost_range' => [15, 45],
                'retail_price_range' => [39.99, 89.99]
            ],
            'tote-bags' => [
                'name' => 'Tote Bags',
                'google_category' => '502987',
                'variants' => ['canvas', 'cotton', 'beach'],
                'pod_vendors' => ['printful', 'printify', 'customcat', 'gooten'],
                'print_methods' => ['dtg', 'screen-print'],
                'base_cost_range' => [5, 15],
                'retail_price_range' => [14.99, 29.99]
            ],
            'fanny-packs' => [
                'name' => 'Fanny Packs',
                'google_category' => '6984',
                'variants' => ['classic', 'crossbody'],
                'pod_vendors' => ['printful', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [12, 25],
                'retail_price_range' => [29.99, 49.99]
            ],
            'duffle-bags' => [
                'name' => 'Duffle Bags',
                'google_category' => '119',
                'variants' => ['gym', 'travel', 'weekender'],
                'pod_vendors' => ['printful', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [25, 50],
                'retail_price_range' => [54.99, 99.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // DRINKWARE
    // ═══════════════════════════════════════════════════════════════
    'drinkware' => [
        'name' => 'Drinkware',
        'google_category' => '672',
        'icon' => '☕',
        'subcategories' => [
            'mugs' => [
                'name' => 'Mugs',
                'google_category' => '2169',
                'variants' => ['11oz', '15oz', 'magic', 'two-tone', 'enamel-camp'],
                'pod_vendors' => ['printful', 'printify', 'customcat', 'gooten', 'gelato'],
                'print_methods' => ['sublimation', 'decal'],
                'base_cost_range' => [5, 15],
                'retail_price_range' => [14.99, 29.99]
            ],
            'tumblers' => [
                'name' => 'Tumblers',
                'google_category' => '674',
                'variants' => ['20oz', '30oz', 'wine', 'skinny'],
                'pod_vendors' => ['printful', 'printify', 'customcat'],
                'print_methods' => ['sublimation', 'vinyl'],
                'base_cost_range' => [12, 28],
                'retail_price_range' => [29.99, 49.99]
            ],
            'water-bottles' => [
                'name' => 'Water Bottles',
                'google_category' => '6912',
                'variants' => ['stainless', 'plastic', 'glass', 'infuser'],
                'pod_vendors' => ['printful', 'printify', 'gooten'],
                'print_methods' => ['sublimation', 'vinyl'],
                'base_cost_range' => [10, 25],
                'retail_price_range' => [24.99, 44.99]
            ],
            'shot-glasses' => [
                'name' => 'Shot Glasses',
                'google_category' => '675',
                'variants' => ['standard', 'tall'],
                'pod_vendors' => ['printify', 'customcat'],
                'print_methods' => ['sublimation', 'decal'],
                'base_cost_range' => [4, 10],
                'retail_price_range' => [9.99, 19.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // HOME & LIVING
    // ═══════════════════════════════════════════════════════════════
    'home' => [
        'name' => 'Home & Living',
        'google_category' => '536',
        'icon' => '🏠',
        'subcategories' => [
            'pillows' => [
                'name' => 'Throw Pillows',
                'google_category' => '3511',
                'variants' => ['square-14', 'square-16', 'square-18', 'square-20', 'lumbar'],
                'pod_vendors' => ['printful', 'printify', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [12, 30],
                'retail_price_range' => [29.99, 54.99]
            ],
            'blankets' => [
                'name' => 'Blankets',
                'google_category' => '569',
                'variants' => ['fleece', 'sherpa', 'woven'],
                'sizes' => ['50x60', '60x80'],
                'pod_vendors' => ['printful', 'printify', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [25, 60],
                'retail_price_range' => [54.99, 119.99]
            ],
            'towels' => [
                'name' => 'Towels',
                'google_category' => '604',
                'variants' => ['beach', 'bath', 'hand', 'gym'],
                'pod_vendors' => ['printful', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [12, 30],
                'retail_price_range' => [29.99, 59.99]
            ],
            'tapestries' => [
                'name' => 'Tapestries',
                'google_category' => '592',
                'variants' => ['wall', 'throw'],
                'sizes' => ['36x26', '60x50', '88x104'],
                'pod_vendors' => ['printful', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [20, 50],
                'retail_price_range' => [44.99, 99.99]
            ],
            'shower-curtains' => [
                'name' => 'Shower Curtains',
                'google_category' => '601',
                'variants' => ['standard-71x74'],
                'pod_vendors' => ['printful', 'printify'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [20, 40],
                'retail_price_range' => [49.99, 79.99]
            ],
            'rugs' => [
                'name' => 'Rugs',
                'google_category' => '596',
                'variants' => ['area', 'bath-mat', 'door-mat'],
                'pod_vendors' => ['printify', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [20, 80],
                'retail_price_range' => [49.99, 149.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // WALL ART & PRINTS
    // ═══════════════════════════════════════════════════════════════
    'wall-art' => [
        'name' => 'Wall Art & Prints',
        'google_category' => '500044',
        'icon' => '🖼️',
        'subcategories' => [
            'posters' => [
                'name' => 'Posters',
                'google_category' => '500045',
                'variants' => ['matte', 'glossy', 'semi-gloss'],
                'sizes' => ['8x10', '11x14', '12x18', '16x20', '18x24', '24x36'],
                'pod_vendors' => ['printful', 'printify', 'gooten', 'gelato', 'prodigi'],
                'print_methods' => ['giclée', 'offset'],
                'base_cost_range' => [5, 25],
                'retail_price_range' => [14.99, 49.99]
            ],
            'canvas' => [
                'name' => 'Canvas Prints',
                'google_category' => '500046',
                'variants' => ['wrapped', 'framed', 'floating'],
                'sizes' => ['8x10', '11x14', '16x20', '18x24', '24x36', '30x40'],
                'pod_vendors' => ['printful', 'printify', 'gooten', 'gelato', 'prodigi'],
                'print_methods' => ['giclée'],
                'base_cost_range' => [15, 80],
                'retail_price_range' => [39.99, 199.99]
            ],
            'metal-prints' => [
                'name' => 'Metal Prints',
                'google_category' => '500044',
                'variants' => ['glossy', 'matte'],
                'sizes' => ['8x10', '12x16', '16x20', '20x30'],
                'pod_vendors' => ['printful', 'gooten', 'prodigi'],
                'print_methods' => ['dye-sublimation'],
                'base_cost_range' => [25, 100],
                'retail_price_range' => [59.99, 249.99]
            ],
            'acrylic-prints' => [
                'name' => 'Acrylic Prints',
                'google_category' => '500044',
                'variants' => ['standard', 'thick'],
                'sizes' => ['8x10', '12x16', '16x20', '20x30'],
                'pod_vendors' => ['printful', 'gooten', 'prodigi'],
                'print_methods' => ['direct-print'],
                'base_cost_range' => [30, 120],
                'retail_price_range' => [69.99, 299.99]
            ],
            'framed-prints' => [
                'name' => 'Framed Prints',
                'google_category' => '500047',
                'variants' => ['black-frame', 'white-frame', 'natural-wood'],
                'sizes' => ['8x10', '11x14', '16x20', '18x24'],
                'pod_vendors' => ['printful', 'printify', 'gelato', 'prodigi'],
                'print_methods' => ['giclée'],
                'base_cost_range' => [25, 80],
                'retail_price_range' => [54.99, 179.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // STICKERS & DECALS
    // ═══════════════════════════════════════════════════════════════
    'stickers' => [
        'name' => 'Stickers & Decals',
        'google_category' => '505374',
        'icon' => '🏷️',
        'subcategories' => [
            'die-cut-stickers' => [
                'name' => 'Die Cut Stickers',
                'google_category' => '505374',
                'variants' => ['vinyl', 'clear', 'holographic'],
                'sizes' => ['2x2', '3x3', '4x4', 'custom'],
                'pod_vendors' => ['printful', 'printify', 'scalablepress'],
                'print_methods' => ['vinyl-cut', 'digital'],
                'base_cost_range' => [1, 5],
                'retail_price_range' => [3.99, 9.99]
            ],
            'sticker-sheets' => [
                'name' => 'Sticker Sheets',
                'google_category' => '505374',
                'variants' => ['kiss-cut', 'die-cut'],
                'sizes' => ['4x6', '6x8', '8.5x11'],
                'pod_vendors' => ['printful', 'printify'],
                'print_methods' => ['digital'],
                'base_cost_range' => [3, 10],
                'retail_price_range' => [8.99, 19.99]
            ],
            'vinyl-decals' => [
                'name' => 'Vinyl Decals',
                'google_category' => '505374',
                'variants' => ['car', 'laptop', 'window'],
                'pod_vendors' => ['printify'],
                'print_methods' => ['vinyl-cut'],
                'base_cost_range' => [2, 8],
                'retail_price_range' => [5.99, 14.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // PHONE CASES & TECH
    // ═══════════════════════════════════════════════════════════════
    'tech' => [
        'name' => 'Tech Accessories',
        'google_category' => '264',
        'icon' => '📱',
        'subcategories' => [
            'phone-cases' => [
                'name' => 'Phone Cases',
                'google_category' => '2797',
                'variants' => ['slim', 'tough', 'clear', 'wallet', 'biodegradable'],
                'devices' => ['iPhone 15', 'iPhone 14', 'iPhone 13', 'Samsung Galaxy S24', 'Samsung Galaxy S23', 'Google Pixel'],
                'pod_vendors' => ['printful', 'printify', 'gooten', 'teelaunch'],
                'print_methods' => ['sublimation', 'uv-print'],
                'base_cost_range' => [8, 25],
                'retail_price_range' => [24.99, 49.99]
            ],
            'laptop-sleeves' => [
                'name' => 'Laptop Sleeves',
                'google_category' => '284',
                'variants' => ['neoprene', 'padded'],
                'sizes' => ['13"', '15"', '17"'],
                'pod_vendors' => ['printful', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [15, 30],
                'retail_price_range' => [34.99, 59.99]
            ],
            'mouse-pads' => [
                'name' => 'Mouse Pads',
                'google_category' => '298',
                'variants' => ['standard', 'xl-gaming', 'wrist-rest'],
                'pod_vendors' => ['printful', 'printify', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [5, 20],
                'retail_price_range' => [14.99, 39.99]
            ],
            'airpod-cases' => [
                'name' => 'AirPod Cases',
                'google_category' => '264',
                'variants' => ['gen1', 'gen2', 'pro', 'pro2'],
                'pod_vendors' => ['printify', 'teelaunch'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [8, 15],
                'retail_price_range' => [19.99, 34.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // JEWELRY & ACCESSORIES
    // ═══════════════════════════════════════════════════════════════
    'jewelry' => [
        'name' => 'Jewelry & Accessories',
        'google_category' => '188',
        'icon' => '💍',
        'subcategories' => [
            'necklaces' => [
                'name' => 'Necklaces',
                'google_category' => '191',
                'variants' => ['pendant', 'chain', 'dog-tag'],
                'materials' => ['stainless-steel', 'gold-plated', 'silver'],
                'pod_vendors' => ['printify', 'teelaunch', 'merchize'],
                'print_methods' => ['engraving', 'sublimation'],
                'base_cost_range' => [10, 35],
                'retail_price_range' => [29.99, 79.99]
            ],
            'bracelets' => [
                'name' => 'Bracelets',
                'google_category' => '189',
                'variants' => ['bangle', 'cuff', 'beaded', 'leather'],
                'pod_vendors' => ['printify', 'teelaunch'],
                'print_methods' => ['engraving'],
                'base_cost_range' => [8, 25],
                'retail_price_range' => [19.99, 54.99]
            ],
            'earrings' => [
                'name' => 'Earrings',
                'google_category' => '190',
                'variants' => ['stud', 'drop', 'hoop'],
                'pod_vendors' => ['printify', 'merchize'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [5, 20],
                'retail_price_range' => [14.99, 44.99]
            ],
            'keychains' => [
                'name' => 'Keychains',
                'google_category' => '3291',
                'variants' => ['acrylic', 'metal', 'leather', 'wood'],
                'pod_vendors' => ['printful', 'printify', 'gooten'],
                'print_methods' => ['sublimation', 'engraving', 'uv-print'],
                'base_cost_range' => [3, 12],
                'retail_price_range' => [9.99, 24.99]
            ],
            'pins' => [
                'name' => 'Pins & Buttons',
                'google_category' => '196',
                'variants' => ['enamel', 'button', 'lapel'],
                'sizes' => ['1"', '1.25"', '1.5"', '2.25"'],
                'pod_vendors' => ['printify', 'scalablepress'],
                'print_methods' => ['digital', 'enamel'],
                'base_cost_range' => [2, 10],
                'retail_price_range' => [5.99, 19.99]
            ],
            'socks' => [
                'name' => 'Socks',
                'google_category' => '194',
                'variants' => ['crew', 'ankle', 'no-show', 'knee-high'],
                'sizes' => ['S', 'M', 'L', 'XL'],
                'pod_vendors' => ['printful', 'printify', 'gooten'],
                'print_methods' => ['sublimation'],
                'base_cost_range' => [8, 18],
                'retail_price_range' => [14.99, 29.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // DIGITAL PRODUCTS
    // ═══════════════════════════════════════════════════════════════
    'digital' => [
        'name' => 'Digital Products',
        'google_category' => '4',
        'icon' => '💾',
        'subcategories' => [
            'wallpapers' => [
                'name' => 'Digital Wallpapers',
                'google_category' => '4',
                'variants' => ['phone', 'desktop', 'tablet'],
                'formats' => ['jpg', 'png'],
                'fulfillment' => 'instant-download',
                'base_cost_range' => [0, 0],
                'retail_price_range' => [2.99, 14.99]
            ],
            'photo-packs' => [
                'name' => 'Photo Packs',
                'google_category' => '4',
                'variants' => ['exclusive', 'behind-scenes', 'themed'],
                'formats' => ['jpg', 'zip'],
                'fulfillment' => 'instant-download',
                'base_cost_range' => [0, 0],
                'retail_price_range' => [9.99, 49.99],
                'adult_content' => true
            ],
            'videos' => [
                'name' => 'Digital Videos',
                'google_category' => '4',
                'variants' => ['clips', 'full-length', 'exclusive'],
                'formats' => ['mp4', 'mov'],
                'fulfillment' => 'streaming',
                'base_cost_range' => [0, 0],
                'retail_price_range' => [4.99, 99.99],
                'adult_content' => true
            ],
            'presets' => [
                'name' => 'Photo Presets',
                'google_category' => '4',
                'variants' => ['lightroom', 'photoshop'],
                'formats' => ['xmp', 'dng', 'lrtemplate'],
                'fulfillment' => 'instant-download',
                'base_cost_range' => [0, 0],
                'retail_price_range' => [9.99, 29.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // LIMITED EDITIONS & COLLECTIBLES
    // ═══════════════════════════════════════════════════════════════
    'limited' => [
        'name' => 'Limited Editions',
        'google_category' => '499848',
        'icon' => '⭐',
        'subcategories' => [
            'signed-prints' => [
                'name' => 'Signed Prints',
                'google_category' => '500045',
                'variants' => ['numbered', 'certificate'],
                'sizes' => ['8x10', '11x14', '16x20'],
                'fulfillment' => 'manual',
                'limited_quantity' => true,
                'base_cost_range' => [10, 30],
                'retail_price_range' => [49.99, 199.99]
            ],
            'polaroids' => [
                'name' => 'Polaroids',
                'google_category' => '499848',
                'variants' => ['standard', 'signed'],
                'fulfillment' => 'manual',
                'limited_quantity' => true,
                'base_cost_range' => [2, 5],
                'retail_price_range' => [19.99, 49.99]
            ],
            'worn-items' => [
                'name' => 'Worn Items',
                'google_category' => '499848',
                'variants' => ['apparel', 'accessories'],
                'fulfillment' => 'manual',
                'limited_quantity' => true,
                'base_cost_range' => [0, 50],
                'retail_price_range' => [99.99, 499.99],
                'adult_content' => true
            ],
            'custom-items' => [
                'name' => 'Custom/Personalized Items',
                'google_category' => '499848',
                'variants' => ['custom-video', 'custom-photo', 'custom-message'],
                'fulfillment' => 'manual',
                'base_cost_range' => [0, 0],
                'retail_price_range' => [49.99, 299.99]
            ],
        ]
    ],

    // ═══════════════════════════════════════════════════════════════
    // DROPSHIP GENERAL MERCHANDISE
    // ═══════════════════════════════════════════════════════════════
    'dropship' => [
        'name' => 'Dropship Products',
        'google_category' => '1',
        'icon' => '📦',
        'subcategories' => [
            'electronics' => [
                'name' => 'Electronics',
                'google_category' => '222',
                'dropship_vendors' => ['aliexpress', 'cjdropshipping', 'banggood', 'spocket'],
                'categories' => ['gadgets', 'accessories', 'audio', 'smart-devices']
            ],
            'fitness' => [
                'name' => 'Fitness & Sports',
                'google_category' => '990',
                'dropship_vendors' => ['aliexpress', 'cjdropshipping', 'spocket'],
                'categories' => ['equipment', 'apparel', 'supplements', 'accessories']
            ],
            'beauty' => [
                'name' => 'Beauty & Personal Care',
                'google_category' => '469',
                'dropship_vendors' => ['aliexpress', 'modalyst', 'spocket'],
                'categories' => ['skincare', 'grooming', 'tools']
            ],
            'novelty' => [
                'name' => 'Novelty & Gag Gifts',
                'google_category' => '99',
                'dropship_vendors' => ['aliexpress', 'cjdropshipping'],
                'categories' => ['gag-gifts', 'party', 'adult-novelty'],
                'adult_content' => true
            ],
        ]
    ],
];

/**
 * SHIPPING ZONES & METHODS
 */
$SHIPPING_ZONES = [
    'domestic_us' => [
        'name' => 'United States',
        'countries' => ['US'],
        'methods' => [
            'standard' => ['name' => 'Standard Shipping', 'days' => '5-7', 'base_rate' => 4.99],
            'express' => ['name' => 'Express Shipping', 'days' => '2-3', 'base_rate' => 9.99],
            'overnight' => ['name' => 'Overnight', 'days' => '1', 'base_rate' => 24.99],
        ]
    ],
    'canada' => [
        'name' => 'Canada',
        'countries' => ['CA'],
        'methods' => [
            'standard' => ['name' => 'Standard International', 'days' => '7-14', 'base_rate' => 9.99],
            'express' => ['name' => 'Express International', 'days' => '3-5', 'base_rate' => 19.99],
        ]
    ],
    'europe' => [
        'name' => 'Europe',
        'countries' => ['GB', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'CH', 'SE', 'NO', 'DK', 'FI', 'IE', 'PT', 'PL', 'CZ', 'HU', 'GR'],
        'methods' => [
            'standard' => ['name' => 'Standard International', 'days' => '10-21', 'base_rate' => 12.99],
            'express' => ['name' => 'Express International', 'days' => '5-7', 'base_rate' => 24.99],
        ]
    ],
    'australia' => [
        'name' => 'Australia/NZ',
        'countries' => ['AU', 'NZ'],
        'methods' => [
            'standard' => ['name' => 'Standard International', 'days' => '14-21', 'base_rate' => 14.99],
            'express' => ['name' => 'Express International', 'days' => '5-10', 'base_rate' => 29.99],
        ]
    ],
    'worldwide' => [
        'name' => 'Rest of World',
        'countries' => ['*'],
        'methods' => [
            'standard' => ['name' => 'International Shipping', 'days' => '14-30', 'base_rate' => 19.99],
        ]
    ],
];

// API Response
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'full':
        respond([
            'success' => true,
            'taxonomy' => $TAXONOMY,
            'shipping_zones' => $SHIPPING_ZONES
        ]);
        break;

    case 'categories':
        $categories = [];
        foreach ($TAXONOMY as $key => $cat) {
            $categories[$key] = [
                'name' => $cat['name'],
                'icon' => $cat['icon'],
                'google_category' => $cat['google_category'],
                'subcategory_count' => count($cat['subcategories'])
            ];
        }
        respond(['success' => true, 'categories' => $categories]);
        break;

    case 'category':
        $catId = $_GET['id'] ?? '';
        if (!isset($TAXONOMY[$catId])) {
            respond(['success' => false, 'error' => 'Category not found'], 404);
        }
        respond(['success' => true, 'category' => $TAXONOMY[$catId]]);
        break;

    case 'subcategory':
        $catId = $_GET['category'] ?? '';
        $subId = $_GET['id'] ?? '';
        if (!isset($TAXONOMY[$catId]['subcategories'][$subId])) {
            respond(['success' => false, 'error' => 'Subcategory not found'], 404);
        }
        respond(['success' => true, 'subcategory' => $TAXONOMY[$catId]['subcategories'][$subId]]);
        break;

    case 'by-vendor':
        $vendor = $_GET['vendor'] ?? '';
        $results = [];
        foreach ($TAXONOMY as $catKey => $cat) {
            foreach ($cat['subcategories'] as $subKey => $sub) {
                $vendors = $sub['pod_vendors'] ?? $sub['dropship_vendors'] ?? [];
                if (in_array($vendor, $vendors)) {
                    $results[] = [
                        'category' => $catKey,
                        'subcategory' => $subKey,
                        'name' => $sub['name'],
                        'full_path' => "{$cat['name']} > {$sub['name']}"
                    ];
                }
            }
        }
        respond(['success' => true, 'vendor' => $vendor, 'products' => $results]);
        break;

    case 'shipping':
        respond(['success' => true, 'zones' => $SHIPPING_ZONES]);
        break;

    case 'shipping-rate':
        $country = $_GET['country'] ?? 'US';
        $zone = null;
        foreach ($SHIPPING_ZONES as $zoneKey => $zoneData) {
            if (in_array($country, $zoneData['countries']) || in_array('*', $zoneData['countries'])) {
                $zone = $zoneData;
                break;
            }
        }
        if (!$zone) $zone = $SHIPPING_ZONES['worldwide'];
        respond(['success' => true, 'country' => $country, 'zone' => $zone]);
        break;

    default:
        respond([
            'success' => true,
            'message' => 'WYATT XXX COLE Product Taxonomy API',
            'version' => '1.0.0',
            'endpoints' => [
                'full' => 'Get complete taxonomy',
                'categories' => 'List all top-level categories',
                'category?id=X' => 'Get specific category',
                'subcategory?category=X&id=Y' => 'Get specific subcategory',
                'by-vendor?vendor=X' => 'Get products supported by vendor',
                'shipping' => 'Get all shipping zones',
                'shipping-rate?country=XX' => 'Get shipping rate for country'
            ],
            'category_count' => count($TAXONOMY),
            'supported_vendors' => [
                'pod' => ['printful', 'printify', 'customcat', 'gooten', 'gelato', 'prodigi', 'teelaunch', 'scalablepress', 'merchize', 'awkwardstyles', 'apliiq', 'subliminator'],
                'dropship' => ['aliexpress', 'cjdropshipping', 'spocket', 'modalyst', 'banggood']
            ]
        ]);
}

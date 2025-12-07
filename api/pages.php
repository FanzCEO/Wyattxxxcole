<?php
/**
 * Page Manager API
 * Handles page configuration for the site
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$configFile = __DIR__ . '/data/pages-config.json';
$dataDir = dirname($configFile);

// Ensure data directory exists
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Default pages configuration
$defaultPages = [
    [
        'id' => 'home',
        'title' => 'Home',
        'slug' => '',
        'file' => 'index.html',
        'type' => 'content',
        'description' => 'Welcome to Wyatt XXX Cole - Country Bred. Fully Loaded.',
        'enabled' => true,
        'inNav' => true,
        'inFooter' => false,
        'hidden' => false,
        'requiresAuth' => false,
        'order' => 0,
        'isSystem' => true
    ],
    [
        'id' => 'gallery',
        'title' => 'Gallery',
        'slug' => 'gallery',
        'file' => 'gallery.html',
        'type' => 'gallery',
        'description' => 'Photo and video gallery',
        'enabled' => true,
        'inNav' => true,
        'inFooter' => true,
        'hidden' => false,
        'requiresAuth' => false,
        'order' => 1,
        'isSystem' => false
    ],
    [
        'id' => 'shop',
        'title' => 'Shop',
        'slug' => 'shop',
        'file' => 'shop.html',
        'type' => 'shop',
        'description' => 'Official merchandise and products',
        'enabled' => true,
        'inNav' => true,
        'inFooter' => true,
        'hidden' => false,
        'requiresAuth' => false,
        'order' => 2,
        'isSystem' => false
    ],
    [
        'id' => 'links',
        'title' => 'Links',
        'slug' => 'links',
        'file' => 'links.html',
        'type' => 'links',
        'description' => 'All my links in one place',
        'enabled' => true,
        'inNav' => true,
        'inFooter' => true,
        'hidden' => false,
        'requiresAuth' => false,
        'order' => 3,
        'isSystem' => false
    ],
    [
        'id' => 'contact',
        'title' => 'Contact',
        'slug' => 'contact',
        'file' => 'contact.html',
        'type' => 'contact',
        'description' => 'Get in touch',
        'enabled' => true,
        'inNav' => true,
        'inFooter' => true,
        'hidden' => false,
        'requiresAuth' => false,
        'order' => 4,
        'isSystem' => false
    ]
];

// Handle requests
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get all pages or filter
        $pages = loadPages();

        // Apply filters
        $filter = $_GET['filter'] ?? 'all';
        switch ($filter) {
            case 'nav':
                $pages = array_filter($pages, fn($p) => $p['enabled'] && $p['inNav']);
                break;
            case 'footer':
                $pages = array_filter($pages, fn($p) => $p['enabled'] && $p['inFooter']);
                break;
            case 'active':
                $pages = array_filter($pages, fn($p) => $p['enabled'] && !$p['hidden']);
                break;
            case 'hidden':
                $pages = array_filter($pages, fn($p) => $p['hidden']);
                break;
            case 'disabled':
                $pages = array_filter($pages, fn($p) => !$p['enabled']);
                break;
        }

        // Sort by order
        usort($pages, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

        echo json_encode([
            'success' => true,
            'data' => array_values($pages)
        ]);
        break;

    case 'POST':
        // Save pages configuration
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['pages'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid input']);
            exit;
        }

        $pages = $input['pages'];

        // Validate pages
        foreach ($pages as &$page) {
            $page['id'] = $page['id'] ?? uniqid('page_');
            $page['title'] = trim($page['title'] ?? 'Untitled');
            $page['slug'] = preg_replace('/[^a-z0-9-]/', '', strtolower($page['slug'] ?? ''));
            $page['enabled'] = (bool)($page['enabled'] ?? true);
            $page['order'] = (int)($page['order'] ?? 0);
        }

        // Save to file
        $saved = file_put_contents($configFile, json_encode($pages, JSON_PRETTY_PRINT));

        if ($saved === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save configuration']);
            exit;
        }

        // Generate navigation include file
        generateNavigation($pages);

        echo json_encode([
            'success' => true,
            'message' => 'Pages configuration saved',
            'count' => count($pages)
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

/**
 * Load pages from config file or return defaults
 */
function loadPages(): array {
    global $configFile, $defaultPages;

    if (file_exists($configFile)) {
        $data = json_decode(file_get_contents($configFile), true);
        if ($data) return $data;
    }

    return $defaultPages;
}

/**
 * Generate navigation HTML snippet
 */
function generateNavigation(array $pages): void {
    $navDir = dirname(__DIR__) . '/includes/';
    if (!is_dir($navDir)) {
        mkdir($navDir, 0755, true);
    }

    // Filter and sort nav items
    $navItems = array_filter($pages, fn($p) => $p['enabled'] && $p['inNav'] && !$p['hidden']);
    usort($navItems, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    // Generate nav HTML
    $navHtml = "<!-- Auto-generated navigation - DO NOT EDIT DIRECTLY -->\n";
    $navHtml .= "<nav class=\"nav\">\n";
    $navHtml .= "    <ul class=\"nav__list\">\n";

    foreach ($navItems as $item) {
        $href = $item['slug'] ? $item['slug'] . '.html' : 'index.html';
        $navHtml .= "        <li class=\"nav__item\">\n";
        $navHtml .= "            <a href=\"{$href}\" class=\"nav__link\">{$item['title']}</a>\n";
        $navHtml .= "        </li>\n";
    }

    $navHtml .= "    </ul>\n";
    $navHtml .= "</nav>\n";

    file_put_contents($navDir . 'nav.html', $navHtml);

    // Generate footer links
    $footerItems = array_filter($pages, fn($p) => $p['enabled'] && $p['inFooter'] && !$p['hidden']);
    usort($footerItems, fn($a, $b) => ($a['order'] ?? 0) - ($b['order'] ?? 0));

    $footerHtml = "<!-- Auto-generated footer links - DO NOT EDIT DIRECTLY -->\n";
    $footerHtml .= "<ul class=\"footer__links\">\n";

    foreach ($footerItems as $item) {
        $href = $item['slug'] ? $item['slug'] . '.html' : 'index.html';
        $footerHtml .= "    <li><a href=\"{$href}\">{$item['title']}</a></li>\n";
    }

    $footerHtml .= "</ul>\n";

    file_put_contents($navDir . 'footer-links.html', $footerHtml);

    // Generate JSON for JavaScript use
    $jsConfig = [
        'navigation' => array_values($navItems),
        'footer' => array_values($footerItems),
        'all' => $pages,
        'generated' => date('c')
    ];

    file_put_contents(dirname(__DIR__) . '/js/pages-config.json', json_encode($jsConfig, JSON_PRETTY_PRINT));
}

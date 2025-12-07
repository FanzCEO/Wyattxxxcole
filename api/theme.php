<?php
/**
 * THEME EDITOR API
 * Comprehensive branding and theme customization system
 * Supports: Colors, Typography, Backgrounds, Templates
 */

// Load centralized security configuration
require_once __DIR__ . '/security.php';

// Initialize security (CORS, headers, rate limiting)
initSecurity([
    'cors' => true,
    'headers' => true,
    'rateLimit' => true,
    'csrf' => false
]);

define('THEME_FILE', __DIR__ . '/theme.json');
define('THEME_CSS_FILE', __DIR__ . '/../css/theme-custom.css');

// Auth check
function checkAuth() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $_GET['token'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    $tokenFile = __DIR__ . '/.admin_token';
    if (file_exists($tokenFile) && !empty($token)) {
        $storedToken = trim(file_get_contents($tokenFile));
        return !empty($storedToken) && hash_equals($storedToken, $token);
    }
    return false;
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

function handleError($message, $code = 400) {
    respond(['success' => false, 'error' => $message], $code);
}

// ========== THEME PRESETS ==========
function getThemePresets() {
    return [
        'country' => [
            'id' => 'country',
            'name' => 'Country Classic',
            'description' => 'Warm rustic tones with western charm',
            'preview' => '/images/themes/country-preview.jpg',
            'colors' => [
                'primary' => '#C68E3F',
                'secondary' => '#A44A2A',
                'accent' => '#D4AF37',
                'background' => '#0D0B09',
                'backgroundSecondary' => '#1A1614',
                'card' => '#1F1B18',
                'text' => '#F5EDE4',
                'textSecondary' => '#D4C4A8',
                'textMuted' => '#8B7355',
                'border' => 'rgba(198, 142, 63, 0.2)',
                'success' => '#4CAF50',
                'error' => '#A44A2A',
                'warning' => '#FF9800'
            ],
            'typography' => [
                'headingFont' => 'Playfair Display',
                'bodyFont' => 'Inter',
                'condensedFont' => 'Roboto Condensed',
                'headingWeight' => '700',
                'bodyWeight' => '400',
                'baseSize' => '16px',
                'lineHeight' => '1.6',
                'letterSpacing' => '0.02em'
            ],
            'background' => [
                'type' => 'gradient',
                'value' => 'linear-gradient(135deg, #0D0B09 0%, #1A1614 100%)',
                'overlay' => 'rgba(0,0,0,0.3)',
                'pattern' => 'none'
            ],
            'effects' => [
                'borderRadius' => '8px',
                'cardShadow' => '0 4px 20px rgba(0,0,0,0.3)',
                'glowEffect' => true,
                'animations' => true
            ]
        ],
        'newage' => [
            'id' => 'newage',
            'name' => 'New Age Modern',
            'description' => 'Sleek, minimalist design with bold accents',
            'preview' => '/images/themes/newage-preview.jpg',
            'colors' => [
                'primary' => '#6366F1',
                'secondary' => '#8B5CF6',
                'accent' => '#EC4899',
                'background' => '#09090B',
                'backgroundSecondary' => '#18181B',
                'card' => '#27272A',
                'text' => '#FAFAFA',
                'textSecondary' => '#A1A1AA',
                'textMuted' => '#71717A',
                'border' => 'rgba(99, 102, 241, 0.2)',
                'success' => '#22C55E',
                'error' => '#EF4444',
                'warning' => '#F59E0B'
            ],
            'typography' => [
                'headingFont' => 'Space Grotesk',
                'bodyFont' => 'Inter',
                'condensedFont' => 'DM Sans',
                'headingWeight' => '600',
                'bodyWeight' => '400',
                'baseSize' => '16px',
                'lineHeight' => '1.7',
                'letterSpacing' => '-0.01em'
            ],
            'background' => [
                'type' => 'gradient',
                'value' => 'linear-gradient(135deg, #09090B 0%, #1E1B4B 100%)',
                'overlay' => 'rgba(0,0,0,0.4)',
                'pattern' => 'dots'
            ],
            'effects' => [
                'borderRadius' => '12px',
                'cardShadow' => '0 8px 32px rgba(99, 102, 241, 0.15)',
                'glowEffect' => true,
                'animations' => true
            ]
        ],
        'badass' => [
            'id' => 'badass',
            'name' => 'Badass Dark',
            'description' => 'Bold, edgy design with high contrast',
            'preview' => '/images/themes/badass-preview.jpg',
            'colors' => [
                'primary' => '#DC2626',
                'secondary' => '#991B1B',
                'accent' => '#FBBF24',
                'background' => '#000000',
                'backgroundSecondary' => '#0A0A0A',
                'card' => '#171717',
                'text' => '#FFFFFF',
                'textSecondary' => '#D4D4D4',
                'textMuted' => '#737373',
                'border' => 'rgba(220, 38, 38, 0.3)',
                'success' => '#16A34A',
                'error' => '#DC2626',
                'warning' => '#D97706'
            ],
            'typography' => [
                'headingFont' => 'Oswald',
                'bodyFont' => 'Roboto',
                'condensedFont' => 'Bebas Neue',
                'headingWeight' => '700',
                'bodyWeight' => '400',
                'baseSize' => '16px',
                'lineHeight' => '1.5',
                'letterSpacing' => '0.05em'
            ],
            'background' => [
                'type' => 'solid',
                'value' => '#000000',
                'overlay' => 'none',
                'pattern' => 'noise'
            ],
            'effects' => [
                'borderRadius' => '4px',
                'cardShadow' => '0 4px 16px rgba(220, 38, 38, 0.2)',
                'glowEffect' => true,
                'animations' => true
            ]
        ],
        'luxe' => [
            'id' => 'luxe',
            'name' => 'Luxe Gold',
            'description' => 'Elegant luxury with gold accents',
            'preview' => '/images/themes/luxe-preview.jpg',
            'colors' => [
                'primary' => '#B8860B',
                'secondary' => '#8B6914',
                'accent' => '#FFD700',
                'background' => '#0A0A0A',
                'backgroundSecondary' => '#141414',
                'card' => '#1C1C1C',
                'text' => '#FFFAF0',
                'textSecondary' => '#D4C4A8',
                'textMuted' => '#8B8B7A',
                'border' => 'rgba(184, 134, 11, 0.3)',
                'success' => '#32CD32',
                'error' => '#CD5C5C',
                'warning' => '#DAA520'
            ],
            'typography' => [
                'headingFont' => 'Cormorant Garamond',
                'bodyFont' => 'Lato',
                'condensedFont' => 'Montserrat',
                'headingWeight' => '600',
                'bodyWeight' => '400',
                'baseSize' => '17px',
                'lineHeight' => '1.8',
                'letterSpacing' => '0.03em'
            ],
            'background' => [
                'type' => 'gradient',
                'value' => 'linear-gradient(180deg, #0A0A0A 0%, #1A1A1A 50%, #0A0A0A 100%)',
                'overlay' => 'rgba(0,0,0,0.2)',
                'pattern' => 'marble'
            ],
            'effects' => [
                'borderRadius' => '2px',
                'cardShadow' => '0 4px 24px rgba(184, 134, 11, 0.15)',
                'glowEffect' => true,
                'animations' => true
            ]
        ],
        'clean' => [
            'id' => 'clean',
            'name' => 'Clean Light',
            'description' => 'Bright, clean minimalist design',
            'preview' => '/images/themes/clean-preview.jpg',
            'colors' => [
                'primary' => '#2563EB',
                'secondary' => '#3B82F6',
                'accent' => '#06B6D4',
                'background' => '#FFFFFF',
                'backgroundSecondary' => '#F8FAFC',
                'card' => '#FFFFFF',
                'text' => '#0F172A',
                'textSecondary' => '#475569',
                'textMuted' => '#94A3B8',
                'border' => 'rgba(37, 99, 235, 0.1)',
                'success' => '#10B981',
                'error' => '#EF4444',
                'warning' => '#F59E0B'
            ],
            'typography' => [
                'headingFont' => 'Plus Jakarta Sans',
                'bodyFont' => 'Inter',
                'condensedFont' => 'Work Sans',
                'headingWeight' => '700',
                'bodyWeight' => '400',
                'baseSize' => '16px',
                'lineHeight' => '1.6',
                'letterSpacing' => '-0.02em'
            ],
            'background' => [
                'type' => 'solid',
                'value' => '#FFFFFF',
                'overlay' => 'none',
                'pattern' => 'none'
            ],
            'effects' => [
                'borderRadius' => '16px',
                'cardShadow' => '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                'glowEffect' => false,
                'animations' => true
            ]
        ]
    ];
}

// ========== GET DEFAULT THEME ==========
function getDefaultTheme() {
    $presets = getThemePresets();
    return array_merge($presets['country'], [
        'activePreset' => 'country',
        'isCustom' => false,
        'brand' => [
            'name' => 'WYATT XXX COLE',
            'tagline' => 'Country Bred. Fully Loaded.',
            'logo' => '/images/logo.png',
            'favicon' => '/images/favicon.ico',
            'socialImage' => '/images/hero.jpg'
        ],
        'layout' => [
            'maxWidth' => '1400px',
            'sidebarPosition' => 'left',
            'headerStyle' => 'fixed',
            'footerStyle' => 'standard'
        ]
    ]);
}

// ========== LOAD THEME ==========
function loadTheme() {
    if (file_exists(THEME_FILE)) {
        $data = json_decode(file_get_contents(THEME_FILE), true);
        if ($data) {
            return array_merge(getDefaultTheme(), $data);
        }
    }
    return getDefaultTheme();
}

// ========== SAVE THEME ==========
function saveTheme($theme) {
    // Validate theme structure
    if (!isset($theme['colors']) || !isset($theme['typography'])) {
        return false;
    }

    $theme['updatedAt'] = date('c');
    $theme['isCustom'] = true;

    $result = file_put_contents(THEME_FILE, json_encode($theme, JSON_PRETTY_PRINT));

    if ($result) {
        // Generate custom CSS file
        generateThemeCSS($theme);
    }

    return $result !== false;
}

// ========== GENERATE CSS ==========
function generateThemeCSS($theme) {
    $css = "/* Generated Theme CSS - " . date('Y-m-d H:i:s') . " */\n\n";

    // CSS Custom Properties
    $css .= ":root {\n";

    // Colors
    if (isset($theme['colors'])) {
        $css .= "    /* Colors */\n";
        $css .= "    --color-primary: {$theme['colors']['primary']};\n";
        $css .= "    --color-secondary: {$theme['colors']['secondary']};\n";
        $css .= "    --color-accent: {$theme['colors']['accent']};\n";
        $css .= "    --bg-primary: {$theme['colors']['background']};\n";
        $css .= "    --bg-secondary: {$theme['colors']['backgroundSecondary']};\n";
        $css .= "    --bg-card: {$theme['colors']['card']};\n";
        $css .= "    --text-primary: {$theme['colors']['text']};\n";
        $css .= "    --text-secondary: {$theme['colors']['textSecondary']};\n";
        $css .= "    --text-muted: {$theme['colors']['textMuted']};\n";
        $css .= "    --border-color: {$theme['colors']['border']};\n";
        $css .= "    --success: {$theme['colors']['success']};\n";
        $css .= "    --error: {$theme['colors']['error']};\n";
        $css .= "    --warning: {$theme['colors']['warning']};\n";

        // Legacy variable mappings
        $css .= "    --whiskey: {$theme['colors']['primary']};\n";
        $css .= "    --rust: {$theme['colors']['secondary']};\n";
        $css .= "    --gold: {$theme['colors']['accent']};\n";
    }

    // Typography
    if (isset($theme['typography'])) {
        $css .= "\n    /* Typography */\n";
        $css .= "    --font-heading: '{$theme['typography']['headingFont']}', serif;\n";
        $css .= "    --font-body: '{$theme['typography']['bodyFont']}', sans-serif;\n";
        $css .= "    --font-condensed: '{$theme['typography']['condensedFont']}', sans-serif;\n";
        $css .= "    --font-weight-heading: {$theme['typography']['headingWeight']};\n";
        $css .= "    --font-weight-body: {$theme['typography']['bodyWeight']};\n";
        $css .= "    --font-size-base: {$theme['typography']['baseSize']};\n";
        $css .= "    --line-height-base: {$theme['typography']['lineHeight']};\n";
        $css .= "    --letter-spacing: {$theme['typography']['letterSpacing']};\n";
    }

    // Effects
    if (isset($theme['effects'])) {
        $css .= "\n    /* Effects */\n";
        $css .= "    --radius-base: {$theme['effects']['borderRadius']};\n";
        $css .= "    --shadow-card: {$theme['effects']['cardShadow']};\n";
    }

    $css .= "}\n\n";

    // Background styles
    if (isset($theme['background'])) {
        $css .= "body {\n";
        if ($theme['background']['type'] === 'gradient') {
            $css .= "    background: {$theme['background']['value']};\n";
        } elseif ($theme['background']['type'] === 'image') {
            $css .= "    background: url('{$theme['background']['value']}') center/cover fixed;\n";
        } else {
            $css .= "    background-color: {$theme['background']['value']};\n";
        }

        // Pattern overlay
        if (isset($theme['background']['pattern']) && $theme['background']['pattern'] !== 'none') {
            $pattern = $theme['background']['pattern'];
            if ($pattern === 'dots') {
                $css .= "    background-image: radial-gradient(circle, rgba(255,255,255,0.03) 1px, transparent 1px);\n";
                $css .= "    background-size: 20px 20px;\n";
            } elseif ($pattern === 'noise') {
                $css .= "    background-image: url('data:image/svg+xml,<svg viewBox=\"0 0 200 200\" xmlns=\"http://www.w3.org/2000/svg\"><filter id=\"noise\"><feTurbulence type=\"fractalNoise\" baseFrequency=\"0.9\" numOctaves=\"4\" stitchTiles=\"stitch\"/></filter><rect width=\"100%\" height=\"100%\" filter=\"url(%23noise)\" opacity=\"0.03\"/></svg>');\n";
            }
        }
        $css .= "}\n\n";
    }

    // Glow effect
    if (isset($theme['effects']['glowEffect']) && $theme['effects']['glowEffect']) {
        $css .= ".btn--primary:hover, .card:hover {\n";
        $css .= "    box-shadow: 0 0 20px var(--color-primary);\n";
        $css .= "}\n\n";
    }

    // Animation toggle
    if (isset($theme['effects']['animations']) && !$theme['effects']['animations']) {
        $css .= "*, *::before, *::after {\n";
        $css .= "    animation: none !important;\n";
        $css .= "    transition: none !important;\n";
        $css .= "}\n";
    }

    return file_put_contents(THEME_CSS_FILE, $css);
}

// ========== ROUTE HANDLING ==========
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'get':
        // Public - get current theme
        respond(['success' => true, 'theme' => loadTheme()]);
        break;

    case 'presets':
        // Public - get available presets
        respond(['success' => true, 'presets' => getThemePresets()]);
        break;

    case 'save':
        if (!checkAuth()) {
            handleError('Unauthorized', 401);
        }
        if ($method !== 'POST') {
            handleError('Method not allowed', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            handleError('Invalid input');
        }

        $currentTheme = loadTheme();
        $newTheme = array_merge($currentTheme, $input);

        if (saveTheme($newTheme)) {
            respond(['success' => true, 'theme' => $newTheme]);
        } else {
            handleError('Failed to save theme');
        }
        break;

    case 'apply-preset':
        if (!checkAuth()) {
            handleError('Unauthorized', 401);
        }
        if ($method !== 'POST') {
            handleError('Method not allowed', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $presetId = $input['presetId'] ?? $input['preset'] ?? '';

        $presets = getThemePresets();
        if (!isset($presets[$presetId])) {
            handleError('Invalid preset: ' . htmlspecialchars($presetId, ENT_QUOTES, 'UTF-8'));
        }

        $currentTheme = loadTheme();
        $newTheme = array_merge($currentTheme, $presets[$presetId]);
        $newTheme['activePreset'] = $presetId;
        $newTheme['isCustom'] = false;

        if (saveTheme($newTheme)) {
            respond(['success' => true, 'theme' => $newTheme]);
        } else {
            handleError('Failed to apply preset');
        }
        break;

    case 'generate-css':
        if (!checkAuth()) {
            handleError('Unauthorized', 401);
        }

        $theme = loadTheme();
        $result = generateThemeCSS($theme);

        if ($result !== false) {
            respond(['success' => true, 'cssPath' => '/css/theme-custom.css', 'message' => 'CSS generated successfully']);
        } else {
            handleError('Failed to generate CSS');
        }
        break;

    case 'reset':
        if (!checkAuth()) {
            handleError('Unauthorized', 401);
        }

        $defaultTheme = getDefaultTheme();
        if (saveTheme($defaultTheme)) {
            respond(['success' => true, 'theme' => $defaultTheme]);
        } else {
            handleError('Failed to reset theme');
        }
        break;

    case 'export':
        // Export can be public for sharing themes
        $theme = loadTheme();
        respond(['success' => true, 'theme' => $theme]);
        break;

    case 'import':
        if (!checkAuth()) {
            handleError('Unauthorized', 401);
        }
        if ($method !== 'POST') {
            handleError('Method not allowed', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // Support both direct theme object and wrapped {theme: ...} format
        $themeData = isset($input['theme']) ? $input['theme'] : $input;

        if (!$themeData || (!isset($themeData['colors']) && !isset($themeData['typography']))) {
            handleError('Invalid theme format');
        }

        $currentTheme = loadTheme();
        $newTheme = array_merge($currentTheme, $themeData);
        $newTheme['isCustom'] = true;
        $newTheme['importedAt'] = date('c');

        if (saveTheme($newTheme)) {
            respond(['success' => true, 'theme' => $newTheme]);
        } else {
            handleError('Failed to import theme');
        }
        break;

    case 'fonts':
        // Return available Google Fonts
        $fonts = [
            'heading' => [
                'Playfair Display', 'Space Grotesk', 'Oswald', 'Cormorant Garamond',
                'Plus Jakarta Sans', 'Poppins', 'Montserrat', 'Libre Baskerville',
                'Bebas Neue', 'Righteous', 'Abril Fatface', 'Cinzel'
            ],
            'body' => [
                'Inter', 'Roboto', 'Lato', 'Open Sans', 'Source Sans Pro',
                'Work Sans', 'DM Sans', 'Nunito', 'Raleway', 'Outfit'
            ],
            'condensed' => [
                'Roboto Condensed', 'DM Sans', 'Bebas Neue', 'Montserrat',
                'Work Sans', 'Oswald', 'Barlow Condensed', 'Fjalla One'
            ]
        ];
        respond(['success' => true, 'fonts' => $fonts]);
        break;

    default:
        respond(['success' => true, 'message' => 'Theme API', 'actions' => [
            'get', 'presets', 'save', 'apply-preset', 'reset', 'export', 'import', 'fonts'
        ]]);
}

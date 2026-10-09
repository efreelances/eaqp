<?php
// ✅ Iniciar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'elitearequipa';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ============================================
// LANGUAGE SYSTEM
// ============================================
function get_current_lang() {
    if (isset($_SESSION['site_lang'])) {
        return $_SESSION['site_lang'];
    }
    $db_lang = get_setting($GLOBALS['pdo'], 'site_language', 'es');
    return in_array($db_lang, ['es', 'en']) ? $db_lang : 'es';
}

function set_lang($lang) {
    $allowed = ['es', 'en'];
    if (in_array($lang, $allowed)) {
        $_SESSION['site_lang'] = $lang;
    }
}

function load_translations($lang = null) {
    if ($lang === null) $lang = get_current_lang();
    $file = __DIR__ . '/lang/' . $lang . '.php';
    if (file_exists($file)) {
        return require $file;
    }
    return require __DIR__ . '/lang/es.php';
}

function t($key, $default = '') {
    static $translations = null;
    static $current_lang = null;
    $lang = get_current_lang();
    if ($translations === null || $current_lang !== $lang) {
        $translations = load_translations($lang);
        $current_lang = $lang;
    }
    return $translations[$key] ?? ($default ?: $key);
}

// ============================================
// SETTINGS HELPER
// ============================================
function get_setting($pdo, $key, $default = '') {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        $value = $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        $value = $default;
    }
    $cache[$key] = $value;
    return $value;
}

// ============================================
// THEME HELPER
// ============================================
function get_active_theme($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM themes WHERE is_active = 1 LIMIT 1");
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

function generate_theme_css($theme) {
    if (!$theme) return '';
    return "
:root {
    --color-black: {$theme['color_black']};
    --color-dark-gray: {$theme['color_dark_gray']};
    --color-gray: {$theme['color_gray']};
    --color-gold: {$theme['color_gold']};
    --color-gold-hover: {$theme['color_gold_hover']};
    --color-white: {$theme['color_white']};
    --color-light-gray: {$theme['color_light_gray']};
    --border-radius: {$theme['border_radius']};
    --admin-sidebar-bg: {$theme['admin_sidebar_bg']};
    --admin-sidebar-text: {$theme['admin_sidebar_text']};
    --admin-sidebar-active: {$theme['admin_sidebar_active']};
    --admin-content-bg: {$theme['admin_content_bg']};
    --admin-card-bg: {$theme['admin_card_bg']};
    --admin-accent: {$theme['admin_accent']};
}
body { font-family: {$theme['font_family']}; }
";
}

// ============================================
// CURRENCY FORMATTER
// ============================================
function format_currency($pdo, $amount) {
    $currency = get_setting($pdo, 'currency', 'PEN');
    $symbol = get_setting($pdo, 'currency_symbol', 'S/');
    
    if ($currency === 'USD') {
        return '$' . number_format($amount, 2);
    }
    return $symbol . ' ' . number_format($amount, 2);
}

// ============================================
// SOCIAL MEDIA
// ============================================
function get_social_media($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM social_media WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}
?>
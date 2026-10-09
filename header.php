<?php
// ============================================
// HANDLE LANGUAGE CHANGE
// ============================================
if (isset($_GET['lang'])) {
    set_lang($_GET['lang']);
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirect);
    exit;
}

// ============================================
// LOAD THEME AND SETTINGS
// ============================================
$active_theme = get_active_theme($pdo);
$theme_css = generate_theme_css($active_theme);
$site_name = get_setting($pdo, 'site_name', 'ELITE AREQUIPA');
$logo_text = get_setting($pdo, 'logo_text', 'ELITE');
$logo_highlight = get_setting($pdo, 'logo_highlight', 'AREQUIPA');
$current_lang = get_current_lang();
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($site_name); ?> | Discotecas 2026</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="style.css">
<link rel="icon" href="logo-copa.png">
<!-- 🎨 TEMA DINÁMICO INYECTADO DESDE LA BD -->
<style id="dynamic-theme"><?php echo $theme_css; ?></style>
</head>
<body>
<header class="main-header">
<div class="container header-container">
    <!-- 1. LOGO (fijo a la izquierda) -->
    <div class="logo">
        <a href="https://elitearequipa.com" title="Elite - Discotecas 2026"><img src="elite-dorado.png" width="80" alt="Logo"></a>
    </div>
    
    <!-- 2. BARRA DE BÚSQUEDA (ocupa todo el espacio disponible) -->
    <div class="search-bar">
        <form>
            <input type="text" placeholder="<?= t('nav_search') ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    
    <!-- 3. MENÚ (fijo a la derecha) -->
    <nav class="main-nav">
        <ul>
            <li><a href="#home"><?= t('nav_home') ?></a></li>
            <li><a href="#proximos"><?= t('nav_events') ?></a></li>
        </ul>
    </nav>
    
    <!-- 4. SELECTOR DE IDIOMA (fijo a la derecha) -->
    <div class="lang-selector">
        <button class="lang-btn" id="langToggle">
            <i class="fas fa-globe"></i>
            <span class="lang-current"><?= strtoupper($current_lang) ?></span>
        </button>
        <div class="lang-dropdown" id="langDropdown">
            <a href="?lang=es" class="<?= $current_lang === 'es' ? 'active' : '' ?>">
                🇪🇸 Español
            </a>
            <a href="?lang=en" class="<?= $current_lang === 'en' ? 'active' : '' ?>">
                🇺🇸 English
            </a>
        </div>
    </div>
    
    <!-- 5. MENÚ MÓVIL -->
    <div class="mobile-menu-toggle"><i class="fas fa-bars"></i></div>
</div>
</header>
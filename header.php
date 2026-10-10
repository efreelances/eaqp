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


<!-- ============================================
     LOADER CON IMAGEN Y PORCENTAJE
     ============================================ -->
<div id="page-loader" class="page-loader">
    <div class="loader-container">
        <!-- Círculos decorativos -->
        <div class="loader-circle"></div>
        <div class="loader-circle-inner"></div>
        
        <!-- Logo/Imagen central -->
        <div class="loader-image">
            <img src="logo-copa.png" alt="Élite Arequipa">
        </div>
        
        <!-- Porcentaje de carga -->
        <div class="loader-percentage">
            <!--<span id="loader-text">0%</span>-->
        </div>
    </div>
</div>

<script>
(function() {
    const loader = document.getElementById('page-loader');
    const percentageText = document.getElementById('loader-text');
    
    if (!loader) return;
    
    // Animación del porcentaje
    let percentage = 0;
    const interval = setInterval(function() {
        // Aumentar porcentaje de forma realista (más lento al inicio, más rápido después)
        if (percentage < 30) {
            percentage += 1;
        } else if (percentage < 70) {
            percentage += 2;
        } else if (percentage < 90) {
            percentage += 3;
        } else {
            percentage += 5;
        }
        
        // Actualizar texto
        if (percentageText) {
            percentageText.textContent = percentage + '%';
        }
        
        // Cuando llegue a 100%, ocultar loader
        if (percentage >= 100) {
            clearInterval(interval);
            
            setTimeout(function() {
                loader.style.opacity = '0';
                setTimeout(function() {
                    loader.style.display = 'none';
                }, 500);
            }, 300); // Pequeña pausa en 100%
        }
    }, 30); // Actualizar cada 30ms
    
    // Timeout de seguridad (máximo 4 segundos)
    setTimeout(function() {
        if (percentageText) percentageText.textContent = '100%';
        setTimeout(function() {
            loader.style.opacity = '0';
            setTimeout(function() {
                loader.style.display = 'none';
            }, 500);
        }, 300);
    }, 4000);
})();
</script>



<header class="main-header">
<div class="container header-container">
    <!-- 1. LOGO (fijo a la izquierda) -->
    <div class="logo">
        <a href="index.php" title="Elite - Discotecas 2026"><img src="elite-dorado.png" width="80" alt="Logo"></a>
    </div>
    
    <!-- 2. BARRA DE BÚSQUEDA (ocupa todo el espacio disponible) -->
    <div class="search-bar">
       <form action="search.php" method="GET">
            <input type="text" name="q" placeholder="<?= t('nav_search') ?>" required>
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    
    <!-- 3. MENÚ (fijo a la derecha) -->
    <nav class="main-nav">
        <ul>
            <li><a href="index.php"><?= t('nav_home') ?></a></li>
            <li><a href="#">Ubicanos</a></li>
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
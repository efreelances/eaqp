<?php
// 1. Cargar autenticación y verificar sesión
require_once 'auth.php';
require_login();

// 2. Manejar cambio de idioma
if (isset($_GET['lang'])) {
    $_SESSION['admin_lang'] = in_array($_GET['lang'], ['es', 'en']) ? $_GET['lang'] : 'es';
    header('Location: dashboard.php');
    exit;
}

// 3. Obtener idioma activo
$lang = admin_lang();

// 4. Cargar tema activo (para variables CSS del admin)
$theme = get_active_theme($pdo);

// 5. Calcular estadísticas
$stats = [
    'events'   => $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn(),
    'featured' => $pdo->query("SELECT COUNT(*) FROM events WHERE is_featured=1")->fetchColumn(),
    'bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'sliders'  => $pdo->query("SELECT COUNT(*) FROM sliders WHERE is_active=1")->fetchColumn(),
    'revenue'  => $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status='confirmed'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= at('dashboard') ?> | Elite Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }

body { 
    background: var(--admin-content-bg, #0a0a0a); 
    color:#fff; 
    display:flex; 
    min-height:100vh; 
}

/* ============================================
SIDEBAR
============================================ */
.sidebar { 
    width:250px; 
    background: var(--admin-sidebar-bg, #1a1a1a); 
    border-right:2px solid var(--admin-accent, #d4af37); 
    padding:20px 0; 
    position:fixed; 
    height:100vh; 
    overflow-y:auto; 
    transition: transform 0.3s ease;
    z-index: 1000;
}

.sidebar h2 { 
    color: var(--admin-accent, #d4af37); 
    text-align:center; 
    padding:20px; 
    border-bottom:1px solid #2d2d2d; 
    margin-bottom:20px; 
}

.sidebar h2 span { color:#fff; }

.sidebar a { 
    display:block; 
    padding:15px 25px; 
    color: var(--admin-sidebar-text, #ccc); 
    text-decoration:none; 
    border-left:3px solid transparent; 
    transition:all 0.3s; 
}

.sidebar a:hover, .sidebar a.active { 
    background:#2d2d2d; 
    color: var(--admin-sidebar-active, #d4af37); 
    border-left-color: var(--admin-sidebar-active, #d4af37); 
}

.sidebar a i { margin-right:10px; width:20px; }

/* ============================================
MAIN CONTENT
============================================ */
.main-content { 
    margin-left:250px; 
    flex:1; 
    padding:30px; 
    transition: margin-left 0.3s ease;
}

.topbar { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    margin-bottom:30px; 
    padding-bottom:20px; 
    border-bottom:1px solid #2d2d2d; 
    flex-wrap:wrap; 
    gap:15px; 
}

.topbar h1 { 
    color: var(--admin-accent, #d4af37); 
    font-size:28px; 
}

.user-info { 
    color:#ccc; 
    display:flex; 
    align-items:center; 
    gap:15px; 
    flex-wrap:wrap; 
}

.user-info a { 
    color: var(--admin-accent, #d4af37); 
    text-decoration:none; 
}

.lang-switch-admin { display:flex; gap:5px; }

.lang-switch-admin a { 
    padding:6px 12px; 
    background:#2d2d2d; 
    color:#ccc; 
    border-radius:20px; 
    font-size:12px; 
    text-decoration:none; 
    transition: all 0.3s;
}

.lang-switch-admin a:hover { border-color: var(--admin-accent, #d4af37); }

.lang-switch-admin a.active { 
    background: var(--admin-accent, #d4af37); 
    color:#0a0a0a; 
}

/* ============================================
STATS GRID
============================================ */
.stats-grid { 
    display:grid; 
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); 
    gap:20px; 
    margin-bottom:30px; 
}

.stat-card { 
    background: var(--admin-card-bg, #1a1a1a); 
    padding:25px; 
    border-radius:10px; 
    border:1px solid #2d2d2d; 
    border-left:4px solid var(--admin-accent, #d4af37); 
    position: relative;
}

.stat-card h3 { 
    color:#ccc; 
    font-size:14px; 
    text-transform:uppercase; 
    margin-bottom:10px; 
}

.stat-card .value { 
    color: var(--admin-accent, #d4af37); 
    font-size:32px; 
    font-weight:700; 
}

.stat-card i { 
    position: absolute;
    top: 20px;
    right: 20px;
    font-size:30px; 
    color:#2d2d2d; 
}

/* ============================================
QUICK ACTIONS
============================================ */
.quick-actions { 
    display:grid; 
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); 
    gap:15px; 
}

.action-btn { 
    background:#2d2d2d; 
    padding:20px; 
    border-radius:10px; 
    text-align:center; 
    color:#fff; 
    text-decoration:none; 
    transition:all 0.3s; 
    border:1px solid #2d2d2d; 
}

.action-btn:hover { 
    background: var(--admin-accent, #d4af37); 
    color:#0a0a0a; 
    border-color: var(--admin-accent, #d4af37); 
}

.action-btn i { font-size:28px; display:block; margin-bottom:10px; }

/* ============================================
MOBILE MENU TOGGLE
============================================ */
.mobile-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1001;
    background: var(--admin-accent, #d4af37);
    color: #0a0a0a;
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    z-index: 999;
}

/* ============================================
RESPONSIVE - TABLET (768px)
============================================ */
@media (max-width: 768px) {
    .mobile-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .sidebar {
        transform: translateX(-100%);
        box-shadow: 2px 0 10px rgba(0,0,0,0.5);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
        padding: 20px;
        padding-top: 80px;
    }
    
    .overlay.active {
        display: block;
    }
    
    .topbar h1 {
        font-size: 22px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-card .value {
        font-size: 28px;
    }
    
    .quick-actions {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
    }
    
    .action-btn {
        padding: 15px;
    }
    
    .action-btn i {
        font-size: 24px;
    }
}

/* ============================================
RESPONSIVE - MOBILE (480px)
============================================ */
@media (max-width: 480px) {
    .main-content {
        padding: 15px;
        padding-top: 70px;
    }
    
    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .user-info {
        width: 100%;
        justify-content: space-between;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .stat-card {
        padding: 18px;
    }
    
    .stat-card .value {
        font-size: 26px;
    }
    
    .quick-actions {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .action-btn {
        padding: 15px 10px;
        font-size: 13px;
    }
    
    .action-btn i {
        font-size: 22px;
        margin-bottom: 8px;
    }
}
</style>
</head>
<body>

<!-- Botón de menú móvil -->
<button class="mobile-toggle" id="mobileToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay para cerrar sidebar -->
<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="sidebar">
    <h2>ELITE<span> ADMIN</span></h2>
    <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <?= at('dashboard') ?></a>
    <a href="themes.php"><i class="fas fa-palette"></i> <?= at('themes') ?></a>
    <a href="events.php"><i class="fas fa-calendar-alt"></i> <?= at('events') ?></a>
    <a href="sliders.php"><i class="fas fa-images"></i> <?= at('sliders') ?></a>
    <a href="bookings.php"><i class="fas fa-ticket-alt"></i> <?= at('bookings') ?></a>
    <a href="settings.php"><i class="fas fa-cog"></i> <?= at('settings') ?></a>
    <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <?= at('view_site') ?></a>
</aside>

<div class="main-content">
    <div class="topbar">
        <h1><?= at('overview') ?></h1>
        <div class="user-info">
            <div class="lang-switch-admin">
                <a href="?lang=es" class="<?= $lang === 'es' ? 'active' : '' ?>">ES</a>
                <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
            </div>
            <span><?= at('welcome') ?>, <strong><?= htmlspecialchars($_SESSION['admin_username']) ?></strong></span>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?= at('logout') ?></a>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-calendar"></i>
            <h3><?= at('total_events') ?></h3>
            <div class="value"><?= $stats['events'] ?></div>
        </div>
        <div class="stat-card">
            <i class="fas fa-star"></i>
            <h3><?= at('featured') ?></h3>
            <div class="value"><?= $stats['featured'] ?></div>
        </div>
        <div class="stat-card">
            <i class="fas fa-images"></i>
            <h3><?= at('active_sliders') ?></h3>
            <div class="value"><?= $stats['sliders'] ?></div>
        </div>
        <div class="stat-card">
            <i class="fas fa-dollar-sign"></i>
            <h3><?= at('revenue') ?></h3>
            <div class="value"><?= format_currency($pdo, $stats['revenue']) ?></div>
        </div>
    </div>
    
    <h2 style="color: var(--admin-accent, #d4af37); margin-bottom:20px;"><?= at('quick_actions') ?></h2>
    <div class="quick-actions">
        <a href="themes.php" class="action-btn">
            <i class="fas fa-palette"></i>
            <?= at('change_theme') ?>
        </a>
        <a href="events.php?action=add" class="action-btn">
            <i class="fas fa-plus"></i>
            <?= at('new_event') ?>
        </a>
        <a href="sliders.php" class="action-btn">
            <i class="fas fa-images"></i>
            <?= at('manage_sliders') ?>
        </a>
        <a href="settings.php" class="action-btn">
            <i class="fas fa-cog"></i>
            <?= at('site_settings') ?>
        </a>
    </div>
</div>

<script>
// Toggle del sidebar en móviles
const mobileToggle = document.getElementById('mobileToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

if (mobileToggle && sidebar && overlay) {
    mobileToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    });
    
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });
}
</script>

</body>
</html>
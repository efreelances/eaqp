<?php
require_once 'auth.php';
require_login();
$lang = admin_lang();
$message = '';

// ============================================
// GUARDAR CONFIGURACIÓN GENERAL
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // Whitelist de claves permitidas para evitar inyección de settings maliciosos
    $allowedKeys = [
        'site_name', 'site_tagline', 'logo_text', 'logo_highlight',
        'contact_email', 'contact_phone', 'contact_address',
        'currency', 'currency_symbol'
    ];
    foreach ($_POST as $key => $value) {
        if (in_array($key, $allowedKeys)) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?");
            $stmt->execute([$value, $key]);
        }
    }
    $message = at('saved_success');
}

// ============================================
// AGREGAR RED SOCIAL
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_social'])) {
    $name = $_POST['social_name'];
    $icon = $_POST['social_icon'];
    $url = $_POST['social_url'];
    $sort = (int)($_POST['social_order'] ?? 0);
    $pdo->prepare("INSERT INTO social_media (name, icon, url, sort_order) VALUES (?, ?, ?, ?)")
        ->execute([$name, $icon, $url, $sort]);
    $message = at('created_success');
}

// ============================================
// TOGGLE / ELIMINAR RED SOCIAL
// ============================================
if (isset($_GET['toggle_social'])) {
    $id = (int)$_GET['toggle_social'];
    $pdo->prepare("UPDATE social_media SET is_active = NOT is_active WHERE id=?")->execute([$id]);
    $message = at('updated_success');
}

if (isset($_GET['delete_social'])) {
    $id = (int)$_GET['delete_social'];
    $pdo->prepare("DELETE FROM social_media WHERE id=?")->execute([$id]);
    $message = at('deleted_success');
}

// ============================================
// OBTENER DATOS
// ============================================
$settings = $pdo->query("SELECT * FROM settings")->fetchAll();
$settingsMap = [];
foreach ($settings as $s) $settingsMap[$s['setting_key']] = $s['setting_value'];
$socials = $pdo->query("SELECT * FROM social_media ORDER BY sort_order ASC")->fetchAll();

$currency = $settingsMap['currency'] ?? 'PEN';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<title><?= at('settings') ?> | Elite Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:var(--admin-content-bg,#0a0a0a);color:#fff;display:flex;min-height:100vh;}
.sidebar{width:250px;background:var(--admin-sidebar-bg,#1a1a1a);border-right:2px solid var(--admin-accent,#d4af37);padding:20px 0;position:fixed;height:100vh;}
.sidebar h2{color:var(--admin-accent,#d4af37);text-align:center;padding:20px;border-bottom:1px solid #2d2d2d;margin-bottom:20px;}
.sidebar h2 span{color:#fff;}
.sidebar a{display:block;padding:15px 25px;color:var(--admin-sidebar-text,#ccc);text-decoration:none;border-left:3px solid transparent;transition:all 0.3s;}
.sidebar a:hover,.sidebar a.active{background:#2d2d2d;color:var(--admin-sidebar-active,#d4af37);border-left-color:var(--admin-sidebar-active,#d4af37);}
.sidebar a i{margin-right:10px;width:20px;}
.main-content{margin-left:250px;flex:1;padding:30px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #2d2d2d;}
.topbar h1{color:var(--admin-accent,#d4af37);}
.user-info a{color:var(--admin-accent,#d4af37);margin-left:15px;text-decoration:none;}
.message{background:var(--admin-accent,#d4af37);color:#0a0a0a;padding:12px;border-radius:8px;margin-bottom:20px;font-weight:600;}
.btn{padding:10px 20px;border-radius:6px;border:none;cursor:pointer;font-weight:600;text-decoration:none;display:inline-block;}
.btn-gold{background:var(--admin-accent,#d4af37);color:#0a0a0a;}
.btn-danger{background:#662222;color:#ff6b6b;}
.btn-gray{background:#2d2d2d;color:#fff;}
.form-card{background:var(--admin-card-bg,#1a1a1a);padding:30px;border-radius:10px;border:1px solid #2d2d2d;margin-bottom:20px;}
.form-card h2{color:var(--admin-accent,#d4af37);margin-bottom:20px;font-size:20px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group label{color:#ccc;font-size:13px;}
.form-group input, .form-group select{padding:10px;background:#0a0a0a;border:1px solid #2d2d2d;color:#fff;border-radius:6px;}
.social-card{background:var(--admin-card-bg,#1a1a1a);padding:15px 20px;border-radius:8px;border:1px solid #2d2d2d;margin-bottom:10px;display:flex;align-items:center;gap:15px;}
.social-card .icon-preview{width:40px;height:40px;background:#2d2d2d;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--admin-accent,#d4af37);font-size:18px;}
.social-card .info{flex:1;}
.social-card .info h4{color:#fff;font-size:14px;margin-bottom:3px;}
.social-card .info p{color:#888;font-size:12px;word-break:break-all;}
.social-card .actions{display:flex;gap:8px;align-items:center;}
.icon-hint{background:#2d2d2d;padding:15px;border-radius:8px;margin-top:15px;font-size:12px;color:#ccc;}
.icon-hint code{background:#0a0a0a;padding:2px 8px;border-radius:4px;color:var(--admin-accent,#d4af37);}
.currency-preview{margin-top:10px;padding:10px;background:#0a0a0a;border-radius:6px;color:var(--admin-accent,#d4af37);font-size:18px;font-weight:700;}
.badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-on{background:#2d6b2d;color:#9fff9f;}
.badge-off{background:#6b2d2d;color:#ff9f9f;}
</style>
</head>
<body>
<aside class="sidebar">
    <h2>ELITE<span> ADMIN</span></h2>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <?= at('dashboard') ?></a>
    <a href="themes.php"><i class="fas fa-palette"></i> <?= at('themes') ?></a>
    <a href="events.php"><i class="fas fa-calendar-alt"></i> <?= at('events') ?></a>
    <a href="sliders.php"><i class="fas fa-images"></i> <?= at('sliders') ?></a>
    <a href="bookings.php"><i class="fas fa-ticket-alt"></i> <?= at('bookings') ?></a>
    <a href="settings.php" class="active"><i class="fas fa-cog"></i> <?= at('settings') ?></a>
    <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <?= at('view_site') ?></a>
</aside>
<div class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-cog"></i> <?= at('settings') ?></h1>
        <div class="user-info">
            <a href="dashboard.php">← <?= at('back') ?></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?= at('logout') ?></a>
        </div>
    </div>
    
    <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    
    <!-- ============================================
    FORMULARIO DE CONFIGURACIÓN GENERAL
    ============================================= -->
    <form method="POST">
        <!-- Branding -->
        <div class="form-card">
            <h2><i class="fas fa-tag"></i> <?= at('branding') ?></h2>
            <div class="form-row">
                <div class="form-group"><label><?= at('site_name') ?></label><input type="text" name="site_name" value="<?= htmlspecialchars($settingsMap['site_name'] ?? '') ?>"></div>
                <div class="form-group"><label><?= at('tagline') ?></label><input type="text" name="site_tagline" value="<?= htmlspecialchars($settingsMap['site_tagline'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label><?= at('logo_text') ?></label><input type="text" name="logo_text" value="<?= htmlspecialchars($settingsMap['logo_text'] ?? '') ?>"></div>
                <div class="form-group"><label><?= at('logo_highlight') ?></label><input type="text" name="logo_highlight" value="<?= htmlspecialchars($settingsMap['logo_highlight'] ?? '') ?>"></div>
            </div>
        </div>
        
        <!-- Contact -->
        <div class="form-card">
            <h2><i class="fas fa-address-book"></i> <?= at('contact_info') ?></h2>
            <div class="form-row">
                <div class="form-group"><label><?= at('email') ?></label><input type="email" name="contact_email" value="<?= htmlspecialchars($settingsMap['contact_email'] ?? '') ?>"></div>
                <div class="form-group"><label><?= at('phone') ?></label><input type="text" name="contact_phone" value="<?= htmlspecialchars($settingsMap['contact_phone'] ?? '') ?>"></div>
            </div>
            <div class="form-group" style="margin-bottom:20px;"><label><?= at('address') ?></label><input type="text" name="contact_address" value="<?= htmlspecialchars($settingsMap['contact_address'] ?? '') ?>"></div>
        </div>
        
        <!-- Currency -->
        <div class="form-card">
            <h2><i class="fas fa-coins"></i> <?= at('currency') ?></h2>
            <div class="form-row">
                <div class="form-group">
                    <label><?= at('currency') ?></label>
                    <select name="currency" id="currencySelect" onchange="updateCurrencyPreview()">
                        <option value="PEN" <?= $currency === 'PEN' ? 'selected' : '' ?>>🇵🇪 PEN - Peruvian Sol</option>
                        <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>🇺🇸 USD - US Dollar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?= at('currency_symbol') ?></label>
                    <input type="text" name="currency_symbol" id="currencySymbol" value="<?= htmlspecialchars($settingsMap['currency_symbol'] ?? 'S/') ?>" maxlength="5">
                </div>
            </div>
            <div class="currency-preview" id="currencyPreview">
                <?= htmlspecialchars($settingsMap['currency_symbol'] ?? 'S/') ?> 150.00
            </div>
        </div>
        
        <button type="submit" name="save_settings" class="btn btn-gold"><i class="fas fa-save"></i> <?= at('save') ?> Settings</button>
    </form>
    
    <!-- ============================================
    REDES SOCIALES DINÁMICAS
    ============================================= -->
    <div class="form-card" style="margin-top:30px;">
        <h2><i class="fas fa-share-alt"></i> <?= at('social_media') ?></h2>
        
        <!-- Formulario para agregar red social -->
        <form method="POST" style="margin-bottom:20px;">
            <div class="form-row">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="social_name" placeholder="e.g. TikTok" required>
                </div>
                <div class="form-group">
                    <label><?= at('icon') ?> (Font Awesome class)</label>
                    <input type="text" name="social_icon" placeholder="e.g. fab fa-tiktok" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= at('url') ?></label>
                    <input type="url" name="social_url" placeholder="https://..." required>
                </div>
                <div class="form-group">
                    <label><?= at('sort_order') ?></label>
                    <input type="number" name="social_order" value="0">
                </div>
            </div>
            <button type="submit" name="add_social" class="btn btn-gold"><i class="fas fa-plus"></i> <?= at('add_social') ?></button>
        </form>
        
        <div class="icon-hint">
            <strong>Popular icons:</strong><br>
            <code>fab fa-facebook-f</code> • 
            <code>fab fa-instagram</code> • 
            <code>fab fa-tiktok</code> • 
            <code>fab fa-youtube</code> • 
            <code>fab fa-twitter</code> • 
            <code>fab fa-whatsapp</code> • 
            <code>fab fa-linkedin</code>
        </div>
        
        <h3 style="color:var(--admin-accent,#d4af37);margin:25px 0 15px;"><?= at('current_socials') ?></h3>
        <?php if (empty($socials)): ?>
            <p style="color:#888;text-align:center;padding:20px;">No social networks added yet.</p>
        <?php else: ?>
            <?php foreach ($socials as $s): ?>
            <div class="social-card">
                <div class="icon-preview"><i class="<?= htmlspecialchars($s['icon']) ?>"></i></div>
                <div class="info">
                    <h4><?= htmlspecialchars($s['name']) ?></h4>
                    <p><?= htmlspecialchars($s['url']) ?></p>
                </div>
                <div class="actions">
                    <span class="badge <?= $s['is_active']?'badge-on':'badge-off' ?>">
                        <?= $s['is_active'] ? at('active') : at('inactive') ?>
                    </span>
                    <a href="?toggle_social=<?= $s['id'] ?>" class="btn btn-gray" style="padding:6px 12px;font-size:12px;">
                        <i class="fas fa-toggle-<?= $s['is_active']?'on':'off' ?>"></i>
                    </a>
                    <a href="?delete_social=<?= $s['id'] ?>" class="btn btn-danger" style="padding:6px 12px;font-size:12px;" onclick="return confirm('<?= at('confirm_delete') ?>')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Preview de moneda en tiempo real
function updateCurrencyPreview() {
    const currency = document.getElementById('currencySelect').value;
    const symbolInput = document.getElementById('currencySymbol');
    const preview = document.getElementById('currencyPreview');
    
    if (currency === 'USD') {
        symbolInput.value = '$';
    } else {
        symbolInput.value = 'S/';
    }
    preview.textContent = symbolInput.value + ' 150.00';
}
document.getElementById('currencySymbol').addEventListener('input', function() {
    document.getElementById('currencyPreview').textContent = this.value + ' 150.00';
});
</script>
</body>
</html>
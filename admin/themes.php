<?php
require_once 'auth.php';
require_login();
$lang = admin_lang();
$message = '';

// ============================================
// HANDLE ACTIONS
// ============================================
// Activar tema
if (isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];
    $pdo->prepare("UPDATE themes SET is_active = 0")->execute();
    $pdo->prepare("UPDATE themes SET is_active = 1 WHERE id = ?")->execute([$id]);
    $message = at('saved_success');
}

// Eliminar tema
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM themes WHERE id = ? AND is_active = 0")->execute([$id]);
    $message = at('deleted_success');
}

// Crear nuevo tema
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_theme'])) {
    $name = $_POST['name'];
    $pdo->prepare("INSERT INTO themes (name) VALUES (?)")->execute([$name]);
    $message = at('created_success');
}

$themes = $pdo->query("SELECT * FROM themes ORDER BY is_active DESC, name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<title><?= at('themes') ?> | Elite Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body { 
    background: var(--admin-content-bg, #0a0a0a); 
    color:#fff; 
    display:flex; 
    min-height:100vh; 
}
.sidebar { 
    width:250px; 
    background: var(--admin-sidebar-bg, #1a1a1a); 
    border-right:2px solid var(--admin-accent, #d4af37); 
    padding:20px 0; 
    position:fixed; 
    height:100vh; 
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
    transition: all 0.3s;
}
.sidebar a:hover, .sidebar a.active { 
    background:#2d2d2d; 
    color: var(--admin-sidebar-active, #d4af37); 
    border-left-color: var(--admin-sidebar-active, #d4af37); 
}
.sidebar a i { margin-right:10px; width:20px; }
.main-content { margin-left:250px; flex:1; padding:30px; }
.topbar { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    margin-bottom:30px; 
    padding-bottom:20px; 
    border-bottom:1px solid #2d2d2d; 
}
.topbar h1 { color: var(--admin-accent, #d4af37); }
.user-info a { color: var(--admin-accent, #d4af37); margin-left:15px; text-decoration:none; }
.message { 
    background: var(--admin-accent, #d4af37); 
    color:#0a0a0a; 
    padding:12px; 
    border-radius:8px; 
    margin-bottom:20px; 
    font-weight:600; 
}
.create-form { 
    background: var(--admin-card-bg, #1a1a1a); 
    padding:20px; 
    border-radius:10px; 
    margin-bottom:30px; 
    border:1px solid #2d2d2d; 
    display:flex; 
    gap:15px; 
    align-items:end; 
}
.create-form input { 
    flex:1; 
    padding:12px; 
    background:#0a0a0a; 
    border:1px solid #2d2d2d; 
    color:#fff; 
    border-radius:8px; 
}
.create-form button { 
    padding:12px 25px; 
    background: var(--admin-accent, #d4af37); 
    color:#0a0a0a; 
    border:none; 
    border-radius:8px; 
    font-weight:700; 
    cursor:pointer; 
    text-transform:uppercase; 
}
.themes-grid { 
    display:grid; 
    grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); 
    gap:20px; 
}
.theme-card { 
    background: var(--admin-card-bg, #1a1a1a); 
    border-radius:10px; 
    overflow:hidden; 
    border:1px solid #2d2d2d; 
    transition:all 0.3s; 
}
.theme-card:hover { border-color: var(--admin-accent, #d4af37); }
.theme-card.active { 
    border:2px solid var(--admin-accent, #d4af37); 
    box-shadow:0 0 20px rgba(212,175,55,0.3); 
}
.theme-preview { height:120px; position:relative; display:flex; }
.theme-preview .block { flex:1; }
.theme-badge { 
    position:absolute; 
    top:10px; 
    right:10px; 
    background: var(--admin-accent, #d4af37); 
    color:#0a0a0a; 
    padding:4px 12px; 
    border-radius:20px; 
    font-size:11px; 
    font-weight:700; 
    text-transform:uppercase; 
}
.theme-info { padding:20px; }
.theme-info h3 { color:#fff; margin-bottom:15px; font-size:18px; }
.color-row { display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px; }
.color-row .label { color:#ccc; }
.color-row .swatch { 
    display:inline-block; 
    width:20px; 
    height:20px; 
    border-radius:4px; 
    vertical-align:middle; 
    margin-left:8px; 
    border:1px solid #2d2d2d; 
}
.theme-actions { 
    display:flex; 
    gap:10px; 
    margin-top:15px; 
    padding-top:15px; 
    border-top:1px solid #2d2d2d; 
}
.theme-actions a, .theme-actions button { 
    flex:1; 
    padding:8px; 
    border-radius:6px; 
    text-align:center; 
    text-decoration:none; 
    font-size:13px; 
    font-weight:600; 
    border:none; 
    cursor:pointer; 
}
.btn-activate { background: var(--admin-accent, #d4af37); color:#0a0a0a; }
.btn-edit { background:#2d2d2d; color:#fff; }
.btn-delete { background:#442222; color:#ff6b6b; }
.btn-activate:hover { background:#b5952f; }
.btn-edit:hover { background:#3d3d3d; }
.btn-delete:hover { background:#662222; }

/* Modal editor */
.modal { 
    display:none; 
    position:fixed; 
    inset:0; 
    background:rgba(0,0,0,0.85); 
    z-index:9999; 
    align-items:center; 
    justify-content:center; 
    padding:20px; 
}
.modal.active { display:flex; }
.modal-content { 
    background: var(--admin-card-bg, #1a1a1a); 
    border-radius:12px; 
    padding:30px; 
    max-width:800px; 
    width:100%; 
    max-height:90vh; 
    overflow-y:auto; 
    border:2px solid var(--admin-accent, #d4af37); 
}
.modal-content h2 { color: var(--admin-accent, #d4af37); margin-bottom:20px; }
.modal-section-title { 
    color:#fff; 
    font-size:16px; 
    margin:25px 0 15px; 
    padding-bottom:8px; 
    border-bottom:1px solid #2d2d2d; 
}
.color-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:15px; }
.color-field { display:flex; flex-direction:column; gap:6px; }
.color-field label { color:#ccc; font-size:13px; }
.color-field .input-wrap { display:flex; gap:10px; align-items:center; }
.color-field input[type="color"] { 
    width:50px; 
    height:40px; 
    border:none; 
    background:transparent; 
    cursor:pointer; 
    border-radius:6px; 
}
.color-field input[type="text"] { 
    flex:1; 
    padding:10px; 
    background:#0a0a0a; 
    border:1px solid #2d2d2d; 
    color:#fff; 
    border-radius:6px; 
    font-family:monospace; 
}
.modal-actions { display:flex; gap:10px; margin-top:25px; justify-content:flex-end; }
.modal-actions button { padding:10px 25px; border-radius:6px; border:none; cursor:pointer; font-weight:600; }
.btn-save { background: var(--admin-accent, #d4af37); color:#0a0a0a; }
.btn-cancel { background:#2d2d2d; color:#fff; }
</style>
</head>
<body>
<aside class="sidebar">
    <h2>ELITE<span> ADMIN</span></h2>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <?= at('dashboard') ?></a>
    <a href="themes.php" class="active"><i class="fas fa-palette"></i> <?= at('themes') ?></a>
    <a href="events.php"><i class="fas fa-calendar-alt"></i> <?= at('events') ?></a>
    <a href="sliders.php"><i class="fas fa-images"></i> <?= at('sliders') ?></a>
    <a href="bookings.php"><i class="fas fa-ticket-alt"></i> <?= at('bookings') ?></a>
    <a href="settings.php"><i class="fas fa-cog"></i> <?= at('settings') ?></a>
    <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <?= at('view_site') ?></a>
</aside>
<div class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-palette"></i> <?= at('themes') ?></h1>
        <div class="user-info">
            <a href="dashboard.php">← <?= at('back') ?></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?= at('logout') ?></a>
        </div>
    </div>
    
    <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    
    <form method="POST" class="create-form">
        <div style="flex:1;">
            <label style="color:#ccc;font-size:13px;display:block;margin-bottom:6px;">New Theme Name</label>
            <input type="text" name="name" placeholder="e.g. Sunset Orange" required>
        </div>
        <button type="submit" name="create_theme"><i class="fas fa-plus"></i> <?= at('create') ?></button>
    </form>
    
    <div class="themes-grid">
        <?php foreach ($themes as $theme): ?>
        <div class="theme-card <?= $theme['is_active'] ? 'active' : '' ?>">
            <div class="theme-preview" style="background:<?= $theme['color_black'] ?>">
                <div class="block" style="background:<?= $theme['color_dark_gray'] ?>"></div>
                <div class="block" style="background:<?= $theme['color_gray'] ?>"></div>
                <div class="block" style="background:<?= $theme['color_gold'] ?>"></div>
                <?php if ($theme['is_active']): ?><div class="theme-badge">✓ <?= at('active') ?></div><?php endif; ?>
            </div>
            <div class="theme-info">
                <h3><?= htmlspecialchars($theme['name']) ?></h3>
                <div class="color-row"><span class="label">Black</span><span class="swatch" style="background:<?= $theme['color_black'] ?>"></span></div>
                <div class="color-row"><span class="label">Dark Gray</span><span class="swatch" style="background:<?= $theme['color_dark_gray'] ?>"></span></div>
                <div class="color-row"><span class="label">Gray</span><span class="swatch" style="background:<?= $theme['color_gray'] ?>"></span></div>
                <div class="color-row"><span class="label">Gold</span><span class="swatch" style="background:<?= $theme['color_gold'] ?>"></span></div>
                <div class="color-row"><span class="label">Gold Hover</span><span class="swatch" style="background:<?= $theme['color_gold_hover'] ?>"></span></div>
                <div class="color-row"><span class="label">White</span><span class="swatch" style="background:<?= $theme['color_white'] ?>"></span></div>
                <div class="color-row"><span class="label">Light Gray</span><span class="swatch" style="background:<?= $theme['color_light_gray'] ?>"></span></div>
                <div class="theme-actions">
                    <?php if (!$theme['is_active']): ?>
                    <a href="?activate=<?= $theme['id'] ?>" class="btn-activate"><?= at('activate') ?></a>
                    <?php endif; ?>
                    <button class="btn-edit" onclick='openEditor(<?= htmlspecialchars(json_encode($theme), ENT_QUOTES, 'UTF-8') ?>)'>
                        <i class="fas fa-edit"></i> <?= at('edit') ?>
                    </button>
                    <?php if (!$theme['is_active']): ?>
                    <a href="?delete=<?= $theme['id'] ?>" class="btn-delete" onclick="return confirm('<?= at('confirm_delete') ?>')"><i class="fas fa-trash"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL EDITOR -->
<div class="modal" id="editorModal">
    <div class="modal-content">
        <h2><i class="fas fa-palette"></i> <?= at('edit') ?> Theme</h2>
        <form id="themeForm" method="POST" action="save_theme.php">
            <input type="hidden" name="theme_id" id="theme_id">
            
            <h3 class="modal-section-title">🎨 <?= at('website_colors') ?></h3>
            <div class="color-grid">
                <?php
                $webFields = [
                    'color_black' => 'Background (Black)',
                    'color_dark_gray' => 'Dark Gray',
                    'color_gray' => 'Gray',
                    'color_gold' => 'Primary (Gold)',
                    'color_gold_hover' => 'Primary Hover',
                    'color_white' => 'Text (White)',
                    'color_light_gray' => 'Secondary Text'
                ];
                foreach ($webFields as $key => $label): ?>
                <div class="color-field">
                    <label><?= $label ?></label>
                    <div class="input-wrap">
                        <input type="color" name="<?= $key ?>" id="<?= $key ?>_picker">
                        <input type="text" name="<?= $key ?>_hex" id="<?= $key ?>_hex" maxlength="7">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <h3 class="modal-section-title">🖥️ <?= at('admin_colors') ?></h3>
            <div class="color-grid">
                <?php
                $adminFields = [
                    'admin_sidebar_bg' => at('sidebar_bg'),
                    'admin_sidebar_text' => at('sidebar_text'),
                    'admin_sidebar_active' => at('sidebar_active'),
                    'admin_content_bg' => at('content_bg'),
                    'admin_card_bg' => at('card_bg'),
                    'admin_accent' => at('accent_color'),
                ];
                foreach ($adminFields as $key => $label): ?>
                <div class="color-field">
                    <label><?= $label ?></label>
                    <div class="input-wrap">
                        <input type="color" name="<?= $key ?>" id="<?= $key ?>_picker">
                        <input type="text" name="<?= $key ?>_hex" id="<?= $key ?>_hex" maxlength="7">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeEditor()"><?= at('cancel') ?></button>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> <?= at('save') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
// Lista completa de campos (Web + Admin)
const allFields = [
    'color_black','color_dark_gray','color_gray','color_gold','color_gold_hover','color_white','color_light_gray',
    'admin_sidebar_bg','admin_sidebar_text','admin_sidebar_active','admin_content_bg','admin_card_bg','admin_accent'
];

function openEditor(theme) {
    document.getElementById('theme_id').value = theme.id;
    allFields.forEach(f => {
        const val = theme[f] || '#000000';
        const picker = document.getElementById(f+'_picker');
        const hex = document.getElementById(f+'_hex');
        if (picker) picker.value = val;
        if (hex) hex.value = val;
    });
    document.getElementById('editorModal').classList.add('active');
}

function closeEditor() {
    document.getElementById('editorModal').classList.remove('active');
}

// Sincronizar color picker <-> input hex
allFields.forEach(f => {
    const picker = document.getElementById(f+'_picker');
    const hex = document.getElementById(f+'_hex');
    if (picker && hex) {
        picker.addEventListener('input', e => hex.value = e.target.value);
        hex.addEventListener('input', e => {
            if (/^#[0-9A-F]{6}$/i.test(e.target.value)) picker.value = e.target.value;
        });
    }
});
</script>
</body>
</html>
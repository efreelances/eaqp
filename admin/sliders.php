<?php
require_once 'auth.php';
require_once 'translate_helper.php';
require_login();
$lang = admin_lang();
$message = '';

// 🎨 Obtener todos los temas disponibles para el selector
$themes = $pdo->query("SELECT id, name, color_gold FROM themes ORDER BY name ASC")->fetchAll();

// ============================================
// HANDLE ACTIONS
// ============================================
// Eliminar slider
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM sliders WHERE id=?")->execute([(int)$_GET['delete']]);
    $message = at('deleted_success');
}

// Toggle activar/desactivar
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE sliders SET is_active = NOT is_active WHERE id=?")->execute([$id]);
    $message = at('updated_success');
}

// Cargar slider para editar
$editSlider = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM sliders WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editSlider = $stmt->fetch();
}

// ============================================
// SAVE (CREATE OR UPDATE)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'],
        'subtitle' => $_POST['subtitle'],
        'button_text' => $_POST['button_text'],
        'button_link' => $_POST['button_link'],
        'sort_order' => (int)$_POST['sort_order'],
        'display_seconds' => max(1, (int)($_POST['display_seconds'] ?? 5)),
        'theme_id' => !empty($_POST['theme_id']) ? (int)$_POST['theme_id'] : 1, // 🎨 NUEVO
    ];
    
    // 🤖 TRADUCCIÓN AUTOMÁTICA AL INGLÉS
    $data['title_en'] = auto_translate($data['title'], 'en');
    $data['subtitle_en'] = auto_translate($data['subtitle'], 'en');
    $data['button_text_en'] = auto_translate($data['button_text'], 'en');
    
    // Upload de imagen
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'slider_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $data['image_url'] = 'uploads/' . $filename;
        }
    }
    
    if (!empty($_POST['id'])) {
        // UPDATE
        $sql = "UPDATE sliders SET title=:title, title_en=:title_en, subtitle=:subtitle, 
                subtitle_en=:subtitle_en, button_text=:button_text, button_text_en=:button_text_en,
                button_link=:button_link, sort_order=:sort_order, display_seconds=:display_seconds, theme_id=:theme_id";
        if (isset($data['image_url'])) $sql .= ", image_url=:image_url";
        $sql .= " WHERE id=:id";
        $data['id'] = $_POST['id'];
        $pdo->prepare($sql)->execute($data);
        $message = at('updated_success') . ' (Auto-translated)';
    } else {
        // INSERT
        if (!isset($data['image_url'])) $data['image_url'] = 'uploads/default.jpg';
        $pdo->prepare("INSERT INTO sliders (title, title_en, subtitle, subtitle_en, image_url, 
                      button_text, button_text_en, button_link, sort_order, display_seconds, theme_id) 
                      VALUES (:title, :title_en, :subtitle, :subtitle_en, :image_url, 
                      :button_text, :button_text_en, :button_link, :sort_order, :display_seconds, :theme_id)")->execute($data);
        $message = at('created_success') . ' (Auto-translated)';
    }
    $editSlider = null;
}

// Obtener todos los sliders
$sliders = $pdo->query("SELECT * FROM sliders ORDER BY sort_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<title><?= at('sliders') ?> | Elite Admin</title>
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
.btn-gray{background:#2d2d2d;color:#fff;}
.btn-danger{background:#662222;color:#ff6b6b;}
.btn-toggle-on{background:#2d6b2d;color:#9fff9f;}
.btn-toggle-off{background:#6b2d2d;color:#ff9f9f;}
.form-card{background:var(--admin-card-bg,#1a1a1a);padding:30px;border-radius:10px;border:1px solid #2d2d2d;margin-bottom:30px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group label{color:#ccc;font-size:13px;}
.form-group input, .form-group select{padding:10px;background:#0a0a0a;border:1px solid #2d2d2d;color:#fff;border-radius:6px;}
.slider-card{background:var(--admin-card-bg,#1a1a1a);border-radius:10px;overflow:hidden;border:1px solid #2d2d2d;margin-bottom:15px;display:flex;}
.slider-card img{width:200px;height:120px;object-fit:cover;}
.slider-info{flex:1;padding:20px;}
.slider-info h3{color:#fff;margin-bottom:8px;}
.slider-info p{color:#ccc;font-size:14px;margin-bottom:5px;}
.slider-info .meta{display:flex;gap:15px;margin-top:8px;font-size:12px;color:#888;flex-wrap:wrap;}
.slider-info .meta span{background:#2d2d2d;padding:4px 10px;border-radius:12px;}
.slider-actions{display:flex;gap:10px;padding:20px;align-items:center;flex-wrap:wrap;}
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
    <a href="sliders.php" class="active"><i class="fas fa-images"></i> <?= at('sliders') ?></a>
    <a href="bookings.php"><i class="fas fa-ticket-alt"></i> <?= at('bookings') ?></a>
    <a href="settings.php"><i class="fas fa-cog"></i> <?= at('settings') ?></a>
    <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <?= at('view_site') ?></a>
</aside>
<div class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-images"></i> <?= at('sliders') ?></h1>
        <div class="user-info">
            <a href="dashboard.php">← <?= at('back') ?></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?= at('logout') ?></a>
        </div>
    </div>
    <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    
    <!-- FORMULARIO: Agregar / Editar -->
    <div class="form-card">
        <h2 style="color:var(--admin-accent,#d4af37);margin-bottom:20px;">
            <i class="fas fa-<?= $editSlider ? 'edit' : 'plus' ?>"></i> 
            <?= $editSlider ? at('edit') . ' Slider' : at('add_slider') ?>
        </h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $editSlider['id'] ?? '' ?>">
            <div class="form-row">
                <div class="form-group">
                    <label><?= at('title') ?></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($editSlider['title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label><?= at('subtitle') ?></label>
                    <input type="text" name="subtitle" value="<?= htmlspecialchars($editSlider['subtitle'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= at('button_text') ?></label>
                    <input type="text" name="button_text" value="<?= htmlspecialchars($editSlider['button_text'] ?? 'Explore Events') ?>">
                </div>
                <div class="form-group">
                    <label><?= at('button_link') ?></label>
                    <input type="text" name="button_link" value="<?= htmlspecialchars($editSlider['button_link'] ?? '#featured') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><?= at('order') ?></label>
                    <input type="number" name="sort_order" value="<?= $editSlider['sort_order'] ?? 0 ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> <?= at('duration') ?></label>
                    <input type="number" name="display_seconds" min="1" max="60" value="<?= $editSlider['display_seconds'] ?? 5 ?>">
                    <small style="color:#888;font-size:11px;">(1-60 seconds)</small>
                </div>
            </div>
            
            <!-- 🎨 NUEVO: Selector de Tema -->
            <div class="form-group" style="margin-bottom:20px;">
                <label><i class="fas fa-palette"></i> Theme Colors</label>
                <select name="theme_id" style="width:100%;padding:10px;background:#0a0a0a;border:1px solid #2d2d2d;color:#fff;border-radius:6px;">
                    <option value="1">-- Default Theme --</option>
                    <?php foreach ($themes as $theme): ?>
                    <option value="<?= $theme['id'] ?>" <?= ($editSlider['theme_id'] ?? 1) == $theme['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($theme['name']) ?> (<?= $theme['color_gold'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#888;font-size:11px;">Choose which color theme to use for this slider</small>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label><?= at('image') ?> (1920x600 recommended)</label>
                <input type="file" name="image" accept="image/*">
                <?php if ($editSlider && !empty($editSlider['image_url'])): ?>
                    <small style="color:#888;">Current: <?= htmlspecialchars($editSlider['image_url']) ?></small>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> <?= at('save') ?></button>
            <?php if ($editSlider): ?>
                <a href="sliders.php" class="btn btn-gray"><?= at('cancel') ?></a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- LISTA DE SLIDERS ACTUALES -->
    <h2 style="color:var(--admin-accent,#d4af37);margin-bottom:20px;"><?= at('current_sliders') ?></h2>
    <?php foreach ($sliders as $s): ?>
    <div class="slider-card">
        <img src="../<?= htmlspecialchars($s['image_url']) ?>" alt="">
        <div class="slider-info">
            <h3><?= htmlspecialchars($s['title']) ?></h3>
            <p><?= htmlspecialchars($s['subtitle']) ?></p>
            <div class="meta">
                <span><i class="fas fa-sort"></i> <?= at('order') ?>: <?= $s['sort_order'] ?></span>
                <span><i class="fas fa-clock"></i> <?= $s['display_seconds'] ?? 5 ?>s</span>
                <span><i class="fas fa-link"></i> <?= htmlspecialchars($s['button_text']) ?></span>
            </div>
        </div>
        <div class="slider-actions">
            <span class="badge <?= $s['is_active']?'badge-on':'badge-off' ?>">
                <?= $s['is_active'] ? at('active') : at('inactive') ?>
            </span>
            <a href="?toggle=<?= $s['id'] ?>" class="btn <?= $s['is_active']?'btn-toggle-on':'btn-toggle-off' ?>" style="padding:6px 12px;font-size:12px;">
                <i class="fas fa-<?= $s['is_active']?'toggle-on':'toggle-off' ?>"></i> <?= at('toggle') ?>
            </a>
            <a href="?edit=<?= $s['id'] ?>" class="btn btn-gray" style="padding:6px 12px;font-size:12px;">
                <i class="fas fa-edit"></i> <?= at('edit') ?>
            </a>
            <a href="?delete=<?= $s['id'] ?>" class="btn btn-danger" style="padding:6px 12px;font-size:12px;" onclick="return confirm('<?= at('confirm_delete') ?>')">
                <i class="fas fa-trash"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>
<?php
require_once 'auth.php';
require_once 'translate_helper.php'; 
require_login();
$lang = admin_lang();
$message = '';
$action = $_GET['action'] ?? 'list';

// ============================================
// HANDLE ACTIONS
// ============================================
if (isset($_GET['delete'])) {
    $eventId = (int)$_GET['delete'];
    // Eliminar sliders asociados primero (aunque ON DELETE CASCADE lo hace, es buena práctica)
    $pdo->prepare("DELETE FROM event_sliders WHERE event_id=?")->execute([$eventId]);
    $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$eventId]);
    $message = at('deleted_success');
}

if (isset($_GET['toggle_featured'])) {
    $id = (int)$_GET['toggle_featured'];
    $pdo->prepare("UPDATE events SET is_featured = NOT is_featured WHERE id=?")->execute([$id]);
    $message = at('updated_success');
}

// ============================================
// SAVE (CREATE OR UPDATE)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'event_date' => $_POST['event_date'],
        'price' => $_POST['price'],
        'location' => $_POST['location'],
        'capacity' => $_POST['capacity'],
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    
    $data['title_en'] = auto_translate($data['title'], 'en');
    $data['description_en'] = auto_translate($data['description'], 'en');
    $data['location_en'] = auto_translate($data['location'], 'en');
    
    // 1. Imagen principal del evento
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $filename = 'event_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $data['image_url'] = 'uploads/' . $filename;
        }
    }
    
    $isUpdate = !empty($_POST['id']);
    
    if ($isUpdate) {
        $sql = "UPDATE events SET title=:title, title_en=:title_en, description=:description, 
                description_en=:description_en, event_date=:event_date, price=:price, 
                location=:location, location_en=:location_en, capacity=:capacity, 
                is_featured=:is_featured, is_active=:is_active";
        if (isset($data['image_url'])) $sql .= ", image_url=:image_url";
        $sql .= " WHERE id=:id";
        $data['id'] = $_POST['id'];
        $pdo->prepare($sql)->execute($data);
        $eventId = $data['id'];
        $message = at('updated_success') . ' (Auto-translated)';
    } else {
        if (!isset($data['image_url'])) $data['image_url'] = 'uploads/default.jpg';
        $pdo->prepare("INSERT INTO events (title, title_en, description, description_en, event_date, 
                      price, location, location_en, capacity, is_featured, is_active, image_url)
                      VALUES (:title, :title_en, :description, :description_en, :event_date, 
                      :price, :location, :location_en, :capacity, :is_featured, :is_active, :image_url)")->execute($data);
        $eventId = $pdo->lastInsertId();
        $message = at('created_success') . ' (Auto-translated)';
    }

    // 2. PROCESAR SLIDERS DEL EVENTO (1 a 3 imágenes)
// Eliminamos SOLO los sliders que el usuario quiere reemplazar (los que tienen nuevo archivo)
for ($i = 1; $i <= 3; $i++) {
    $fileKey = "slider_image_{$i}";
    
    // Si hay un nuevo archivo en esta posición, eliminamos el anterior (si existe)
    if (!empty($_FILES[$fileKey]['name'])) {
        $pdo->prepare("DELETE FROM event_sliders WHERE event_id = ? AND sort_order = ?")
            ->execute([$eventId, $i]);
    }
}

// Ahora insertamos/actualizamos las imágenes
for ($i = 1; $i <= 3; $i++) {
    $fileKey = "slider_image_{$i}";
    $themeKey = "slider_theme_{$i}";
    
    // Verificar si ya existe un slider en esta posición
    $existing = $pdo->prepare("SELECT id FROM event_sliders WHERE event_id = ? AND sort_order = ?");
    $existing->execute([$eventId, $i]);
    $existingSlide = $existing->fetch();
    
    if (!empty($_FILES[$fileKey]['name'])) {
        // NUEVO ARCHIVO SUBIDO
        $uploadDir = '../uploads/';
        $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $filename = 'event_slide_' . $eventId . '_' . $i . '_' . time() . '.' . $ext;
            
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $uploadDir . $filename)) {
                $imageUrl = 'uploads/' . $filename;
                $themeId = (int)($_POST[$themeKey] ?? 1);
                
                if ($existingSlide) {
                    // Actualizar existente
                    $pdo->prepare("UPDATE event_sliders SET image_url = ?, theme_id = ? WHERE id = ?")
                        ->execute([$imageUrl, $themeId, $existingSlide['id']]);
                } else {
                    // Insertar nuevo
                    $pdo->prepare("INSERT INTO event_sliders (event_id, image_url, theme_id, sort_order) VALUES (?, ?, ?, ?)")
                        ->execute([$eventId, $imageUrl, $themeId, $i]);
                }
            }
        }
    }
    // Si NO hay nuevo archivo pero existe uno guardado, lo mantenemos (no hacemos nada)
}
    
    $action = 'list';
}

// Cargar evento para editar
$event = null;
$event_sliders = [];
$themes = $pdo->query("SELECT id, name FROM themes ORDER BY id ASC")->fetchAll();

if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $event = $stmt->fetch();
    
    // Cargar sliders existentes de este evento
    $stmtSlides = $pdo->prepare("SELECT * FROM event_sliders WHERE event_id = ? ORDER BY sort_order ASC");
    $stmtSlides->execute([$event['id']]);
    $event_sliders = $stmtSlides->fetchAll();
}

$events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<title><?= at('events') ?> | Elite Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ... (Mantén todo tu CSS actual aquí, no lo he modificado) ... */
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
.btn-star{background:#d4af37;color:#0a0a0a;}
.btn-star-off{background:#2d2d2d;color:#888;}
.form-card{background:var(--admin-card-bg,#1a1a1a);padding:30px;border-radius:10px;border:1px solid #2d2d2d;margin-bottom:30px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group label{color:#ccc;font-size:13px;}
.form-group input,.form-group textarea,.form-group select{padding:10px;background:#0a0a0a;border:1px solid #2d2d2d;color:#fff;border-radius:6px;font-family:inherit;}
.form-group textarea{min-height:100px;resize:vertical;}
.checkbox-row{display:flex;gap:30px;margin-bottom:20px;flex-wrap:wrap;}
.checkbox-row label{color:#ccc;display:flex;align-items:center;gap:8px;cursor:pointer;}
.checkbox-row input[type="checkbox"]{width:18px;height:18px;accent-color:var(--admin-accent,#d4af37);}
.featured-check{background:#2d2d2d;padding:10px 15px;border-radius:8px;border:1px solid var(--admin-accent,#d4af37);}
table{width:100%;background:var(--admin-card-bg,#1a1a1a);border-radius:10px;overflow:hidden;border:1px solid #2d2d2d;}
th{background:#2d2d2d;color:var(--admin-accent,#d4af37);padding:15px;text-align:left;font-size:13px;text-transform:uppercase;}
td{padding:15px;border-top:1px solid #2d2d2d;color:#ccc;font-size:14px;}
tr:hover{background:#222;}
.badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;}
.badge-featured{background:var(--admin-accent,#d4af37);color:#0a0a0a;}
.badge-regular{background:#2d2d2d;color:#ccc;}
.badge-active{background:#2d6b2d;color:#9fff9f;}
.badge-inactive{background:#6b2d2d;color:#ff9f9f;}

/* Nuevo estilo para sliders en admin */
.slider-upload-box {
    background: #0a0a0a;
    border: 1px dashed #2d2d2d;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.slider-upload-box h4 { color: var(--admin-accent, #d4af37); margin-bottom: 10px; font-size: 14px; }
.slider-preview { width: 100%; max-width: 200px; height: 100px; object-fit: cover; border-radius: 6px; margin-bottom: 10px; border: 1px solid #2d2d2d; }
</style>
</head>
<body>
<aside class="sidebar">
    <h2>ELITE<span> ADMIN</span></h2>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <?= at('dashboard') ?></a>
    <a href="themes.php"><i class="fas fa-palette"></i> <?= at('themes') ?></a>
    <a href="events.php" class="active"><i class="fas fa-calendar-alt"></i> <?= at('events') ?></a>
    <a href="sliders.php"><i class="fas fa-images"></i> <?= at('sliders') ?></a>
    <a href="bookings.php"><i class="fas fa-ticket-alt"></i> <?= at('bookings') ?></a>
    <a href="settings.php"><i class="fas fa-cog"></i> <?= at('settings') ?></a>
    <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <?= at('view_site') ?></a>
</aside>

<div class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-calendar-alt"></i> <?= at('events') ?></h1>
        <div class="user-info">
            <a href="dashboard.php">← <?= at('back') ?></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?= at('logout') ?></a>
        </div>
    </div>
    
    <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    
    <?php if ($action === 'edit' || $action === 'add'): ?>
    <div class="form-card">
        <h2 style="color:var(--admin-accent,#d4af37);margin-bottom:20px;">
            <?= $event ? at('edit') . ' Event' : at('new_event') ?>
        </h2>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($event): ?><input type="hidden" name="id" value="<?= $event['id'] ?>"><?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label><?= at('title') ?></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($event['title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label><?= at('event_date') ?></label>
                    <input type="datetime-local" name="event_date" value="<?= $event['event_date'] ?? '' ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><?= at('price') ?></label>
                    <input type="number" step="0.01" name="price" value="<?= $event['price'] ?? '0.00' ?>" required>
                </div>
                <div class="form-group">
                    <label><?= at('location') ?></label>
                    <input type="text" name="location" value="<?= htmlspecialchars($event['location'] ?? 'Elite Venue, Arequipa') ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><?= at('capacity') ?></label>
                    <input type="number" name="capacity" value="<?= $event['capacity'] ?? 0 ?>">
                </div>
                <div class="form-group">
                    <label><?= at('image') ?> (Principal)</label>
                    <input type="file" name="image" accept="image/*">
                    <?php if ($event && !empty($event['image_url'])): ?>
                        <img src="../<?= htmlspecialchars($event['image_url']) ?>" class="slider-preview" style="margin-top:10px;">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom:20px;">
                <label><?= at('description') ?></label>
                <textarea name="description"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
            </div>
            
            <!-- ============================================ -->
            <!-- NUEVA SECCIÓN: SLIDERS DEL EVENTO (1 a 3) -->
            <!-- ============================================ -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #2d2d2d;">
                <h3 style="color: var(--admin-accent, #d4af37); margin-bottom: 15px;">
                    <i class="fas fa-images"></i> Slider del Evento (Opcional, máx. 3 imágenes)
                </h3>
                <p style="color: #888; font-size: 13px; margin-bottom: 20px;">
                    Sube hasta 3 imágenes. Cada una puede tener un tema de color diferente que se activará cuando se muestre en el slider.
                </p>
                
                <?php for ($i = 1; $i <= 3; $i++): 
                    // Buscar si ya existe un slider para esta posición (sort_order = $i)
                    $existingSlide = null;
                    foreach ($event_sliders as $es) {
                        if ($es['sort_order'] == $i) { $existingSlide = $es; break; }
                    }
                ?>
                <div class="slider-upload-box">
                    <h4>Imagen <?= $i ?></h4>
                    <div class="form-row" style="margin-bottom: 0;">
                        <div class="form-group">
                            <label>Archivo de imagen</label>
                            <input type="file" name="slider_image_<?= $i ?>" accept="image/*">
                            <?php if ($existingSlide): ?>
                                <img src="../<?= htmlspecialchars($existingSlide['image_url']) ?>" class="slider-preview" style="margin-top:10px;">
                                <small style="color: #888;">Actual. Sube otro para reemplazar.</small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Tema de colores para esta imagen</label>
                            <select name="slider_theme_<?= $i ?>">
                                <?php foreach ($themes as $theme): ?>
                                    <option value="<?= $theme['id'] ?>" <?= ($existingSlide && $existingSlide['theme_id'] == $theme['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($theme['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <!-- ============================================ -->
            
            <div class="checkbox-row" style="margin-top: 20px;">
                <label class="featured-check">
                    <input type="checkbox" name="is_featured" <?= ($event['is_featured'] ?? 0) ? 'checked' : '' ?>>
                    <i class="fas fa-star" style="color:var(--admin-accent,#d4af37);"></i>
                    <strong>⭐ <?= at('feature_this_event') ?></strong>
                </label>
                <label>
                    <input type="checkbox" name="is_active" <?= ($event['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <?= at('active') ?>
                </label>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> <?= at('save') ?></button>
                <a href="events.php" class="btn btn-gray"><?= at('cancel') ?></a>
            </div>
        </form>
    </div>
    
    <?php else: ?>
    <!-- LISTA DE EVENTOS (Sin cambios respecto a tu código original) -->
    <div style="margin-bottom:20px;">
        <a href="?action=add" class="btn btn-gold"><i class="fas fa-plus"></i> <?= at('new_event') ?></a>
    </div>
    <table>
        <thead>
            <tr>
                <th><?= at('image') ?></th>
                <th><?= at('title') ?></th>
                <th><?= at('date') ?></th>
                <th><?= at('price') ?></th>
                <th>Type</th>
                <th><?= at('status') ?></th>
                <th><?= at('actions') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($events as $e): ?>
        <tr>
            <td><img src="../<?= htmlspecialchars($e['image_url']) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;"></td>
            <td><?= htmlspecialchars($e['title']) ?></td>
            <td><?= date('M d, Y H:i', strtotime($e['event_date'])) ?></td>
            <td><?= format_currency($pdo, $e['price']) ?></td>
            <td>
                <a href="?toggle_featured=<?= $e['id'] ?>" class="btn <?= $e['is_featured']?'btn-star':'btn-star-off' ?>" style="padding:6px 12px;font-size:12px;">
                    <i class="fas fa-star"></i> <?= $e['is_featured'] ? at('featured') : 'Regular' ?>
                </a>
            </td>
            <td><span class="badge <?= $e['is_active']?'badge-active':'badge-inactive' ?>"><?= $e['is_active'] ? at('active') : at('inactive') ?></span></td>
            <td>
                <a href="?action=edit&id=<?= $e['id'] ?>" class="btn btn-gray" style="padding:6px 12px;font-size:12px;"><i class="fas fa-edit"></i></a>
                <a href="?delete=<?= $e['id'] ?>" class="btn btn-danger" style="padding:6px 12px;font-size:12px;" onclick="return confirm('<?= at('confirm_delete') ?>')"><i class="fas fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
<?php
require_once 'auth.php';
require_once 'translate_helper.php'; // Agregar esta línea 
require_login();
$lang = admin_lang();
$message = '';
$action = $_GET['action'] ?? 'list';

// ============================================
// HANDLE ACTIONS
// ============================================
// Eliminar evento
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM events WHERE id=?")->execute([(int)$_GET['delete']]);
    $message = at('deleted_success');
}

// Toggle destacado rápido
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
    
    //  TRADUCCIÓN AUTOMÁTICA AL INGLÉS
    $data['title_en'] = auto_translate($data['title'], 'en');
    $data['description_en'] = auto_translate($data['description'], 'en');
    $data['location_en'] = auto_translate($data['location'], 'en');
    
    // Upload de imagen
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'event_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
            $data['image_url'] = 'uploads/' . $filename;
        }
    }
    
    if (!empty($_POST['id'])) {
        // UPDATE
        $sql = "UPDATE events SET title=:title, title_en=:title_en, description=:description, 
                description_en=:description_en, event_date=:event_date, price=:price, 
                location=:location, location_en=:location_en, capacity=:capacity, 
                is_featured=:is_featured, is_active=:is_active";
        if (isset($data['image_url'])) $sql .= ", image_url=:image_url";
        $sql .= " WHERE id=:id";
        $data['id'] = $_POST['id'];
        $pdo->prepare($sql)->execute($data);
        $message = at('updated_success') . ' (Auto-translated to English)';
    } else {
        // INSERT
        if (!isset($data['image_url'])) $data['image_url'] = 'uploads/default.jpg';
        $pdo->prepare("INSERT INTO events (title, title_en, description, description_en, event_date, 
                      price, location, location_en, capacity, is_featured, is_active, image_url)
                      VALUES (:title, :title_en, :description, :description_en, :event_date, 
                      :price, :location, :location_en, :capacity, :is_featured, :is_active, :image_url)")->execute($data);
        $message = at('created_success') . ' (Auto-translated to English)';
    }
    $action = 'list';
}

// Cargar evento para editar
$event = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $event = $stmt->fetch();
}

// Obtener todos los eventos
$events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<title><?= at('events') ?> | Elite Admin</title>
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
    <!-- FORMULARIO: Crear / Editar Evento -->
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
                    <label><?= at('image') ?></label>
                    <input type="file" name="image" accept="image/*">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label><?= at('description') ?></label>
                <textarea name="description"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
            </div>
            
            <!-- ⭐ CHECKBOX DESTACAR EVENTO -->
            <div class="checkbox-row">
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
            
            <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> <?= at('save') ?></button>
            <a href="events.php" class="btn btn-gray"><?= at('cancel') ?></a>
        </form>
    </div>
    
    <?php else: ?>
    <!-- LISTA DE EVENTOS -->
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
            <td>
                <img src="../<?= htmlspecialchars($e['image_url']) ?>" 
                     style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
            </td>
            <td><?= htmlspecialchars($e['title']) ?></td>
            <td><?= date('M d, Y H:i', strtotime($e['event_date'])) ?></td>
            <td><?= format_currency($pdo, $e['price']) ?></td>
            <td>
                <!-- ⭐ Toggle rápido de destacado -->
                <a href="?toggle_featured=<?= $e['id'] ?>" 
                   class="btn <?= $e['is_featured']?'btn-star':'btn-star-off' ?>" 
                   style="padding:6px 12px;font-size:12px;" 
                   title="<?= at('feature_this_event') ?>">
                    <i class="fas fa-star"></i> 
                    <?= $e['is_featured'] ? at('featured') : 'Regular' ?>
                </a>
            </td>
            <td>
                <span class="badge <?= $e['is_active']?'badge-active':'badge-inactive' ?>">
                    <?= $e['is_active'] ? at('active') : at('inactive') ?>
                </span>
            </td>
            <td>
                <a href="?action=edit&id=<?= $e['id'] ?>" 
                   class="btn btn-gray" 
                   style="padding:6px 12px;font-size:12px;">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="?delete=<?= $e['id'] ?>" 
                   class="btn btn-danger" 
                   style="padding:6px 12px;font-size:12px;" 
                   onclick="return confirm('<?= at('confirm_delete') ?>')">
                    <i class="fas fa-trash"></i>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
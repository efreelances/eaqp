<?php
require_once 'auth.php';
require_login();
$lang = admin_lang();
$message = '';

// ============================================
// HANDLE ACTIONS
// ============================================
// Eliminar reserva
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([(int)$_GET['delete']]);
    $message = at('deleted_success');
}

// Cambiar estado de reserva
if (isset($_GET['status']) && isset($_GET['id'])) {
    $newStatus = $_GET['status'];
    $allowed = ['pending', 'confirmed', 'cancelled'];
    if (in_array($newStatus, $allowed)) {
        $pdo->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$newStatus, (int)$_GET['id']]);
        $message = at('updated_success');
    }
}

// ============================================
// FILTERS & SEARCH
// ============================================
$filterStatus = $_GET['filter'] ?? 'all';
$filterEvent = $_GET['event'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT b.*, e.title as event_title, e.event_date 
        FROM bookings b 
        LEFT JOIN events e ON b.event_id = e.id 
        WHERE 1=1";
$params = [];

if ($filterStatus !== 'all') {
    $sql .= " AND b.status = ?";
    $params[] = $filterStatus;
}
if ($filterEvent !== 'all') {
    $sql .= " AND b.event_id = ?";
    $params[] = (int)$filterEvent;
}
if ($search) {
    $sql .= " AND (b.customer_name LIKE ? OR b.customer_email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY b.booking_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// ============================================
// STATS
// ============================================
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$confirmedBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status='confirmed'")->fetchColumn();

// Eventos para el filtro
$events = $pdo->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<title><?= at('bookings') ?> | Elite Admin</title>
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
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px;}
.stat-card{background:var(--admin-card-bg,#1a1a1a);padding:20px;border-radius:10px;border:1px solid #2d2d2d;border-left:4px solid var(--admin-accent,#d4af37);}
.stat-card h3{color:#ccc;font-size:12px;text-transform:uppercase;margin-bottom:8px;}
.stat-card .value{color:var(--admin-accent,#d4af37);font-size:28px;font-weight:700;}
.stat-card i{float:right;font-size:26px;color:#2d2d2d;}
.filters{background:var(--admin-card-bg,#1a1a1a);padding:20px;border-radius:10px;border:1px solid #2d2d2d;margin-bottom:20px;display:flex;gap:15px;flex-wrap:wrap;align-items:end;}
.filters .form-group{flex:1;min-width:150px;}
.filters label{color:#ccc;font-size:12px;display:block;margin-bottom:5px;}
.filters input,.filters select{width:100%;padding:8px;background:#0a0a0a;border:1px solid #2d2d2d;color:#fff;border-radius:6px;}
.btn{padding:8px 16px;border-radius:6px;border:none;cursor:pointer;font-weight:600;text-decoration:none;display:inline-block;font-size:13px;}
.btn-gold{background:var(--admin-accent,#d4af37);color:#0a0a0a;}
.btn-gray{background:#2d2d2d;color:#fff;}
.btn-danger{background:#662222;color:#ff6b6b;}
table{width:100%;background:var(--admin-card-bg,#1a1a1a);border-radius:10px;overflow:hidden;border:1px solid #2d2d2d;}
th{background:#2d2d2d;color:var(--admin-accent,#d4af37);padding:12px;text-align:left;font-size:12px;text-transform:uppercase;}
td{padding:12px;border-top:1px solid #2d2d2d;color:#ccc;font-size:13px;}
tr:hover{background:#222;}
.status-dropdown{background:#2d2d2d;color:#fff;border:1px solid #2d2d2d;padding:6px 10px;border-radius:6px;font-size:12px;cursor:pointer;}
.empty{text-align:center;padding:40px;color:#888;}
</style>
</head>
<body>
<aside class="sidebar">
    <h2>ELITE<span> ADMIN</span></h2>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <?= at('dashboard') ?></a>
    <a href="themes.php"><i class="fas fa-palette"></i> <?= at('themes') ?></a>
    <a href="events.php"><i class="fas fa-calendar-alt"></i> <?= at('events') ?></a>
    <a href="sliders.php"><i class="fas fa-images"></i> <?= at('sliders') ?></a>
    <a href="bookings.php" class="active"><i class="fas fa-ticket-alt"></i> <?= at('bookings') ?></a>
    <a href="settings.php"><i class="fas fa-cog"></i> <?= at('settings') ?></a>
    <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <?= at('view_site') ?></a>
</aside>
<div class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-ticket-alt"></i> <?= at('bookings') ?></h1>
        <div class="user-info">
            <a href="dashboard.php">← <?= at('back') ?></a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?= at('logout') ?></a>
        </div>
    </div>
    
    <?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    
    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card"><i class="fas fa-ticket-alt"></i><h3><?= at('total_bookings') ?></h3><div class="value"><?= $totalBookings ?></div></div>
        <div class="stat-card"><i class="fas fa-check-circle"></i><h3><?= at('confirmed') ?></h3><div class="value"><?= $confirmedBookings ?></div></div>
        <div class="stat-card"><i class="fas fa-clock"></i><h3><?= at('pending') ?></h3><div class="value"><?= $pendingBookings ?></div></div>
        <div class="stat-card"><i class="fas fa-dollar-sign"></i><h3><?= at('revenue') ?></h3><div class="value"><?= format_currency($pdo, $totalRevenue) ?></div></div>
    </div>
    
    <!-- FILTERS -->
    <form method="GET" class="filters">
        <div class="form-group">
            <label><?= at('search') ?></label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name or email...">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="filter">
                <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>><?= at('all') ?></option>
                <option value="confirmed" <?= $filterStatus === 'confirmed' ? 'selected' : '' ?>><?= at('confirmed') ?></option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>><?= at('pending') ?></option>
                <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>><?= at('cancelled') ?></option>
            </select>
        </div>
        <div class="form-group">
            <label>Event</label>
            <select name="event">
                <option value="all"><?= at('all_events') ?></option>
                <?php foreach ($events as $e): ?>
                <option value="<?= $e['id'] ?>" <?= $filterEvent == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-gold"><i class="fas fa-filter"></i> <?= at('filter') ?></button>
        <a href="bookings.php" class="btn btn-gray"><i class="fas fa-times"></i> <?= at('clear') ?></a>
    </form>
    
    <!-- TABLE -->
    <?php if (empty($bookings)): ?>
        <div class="empty">
            <i class="fas fa-inbox" style="font-size:48px;color:#2d2d2d;margin-bottom:15px;"></i>
            <p><?= at('no_bookings') ?></p>
        </div>
    <?php else: ?>
    <table>
        <thead><tr>
            <th>#</th>
            <th><?= at('customer') ?></th>
            <th><?= at('event') ?></th>
            <th><?= at('tickets') ?></th>
            <th><?= at('total') ?></th>
            <th><?= at('date') ?></th>
            <th><?= at('status') ?></th>
            <th><?= at('actions') ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
        <tr>
            <td>#<?= $b['id'] ?></td>
            <td>
                <strong style="color:#fff;"><?= htmlspecialchars($b['customer_name']) ?></strong><br>
                <small><?= htmlspecialchars($b['customer_email']) ?></small><br>
                <small><?= htmlspecialchars($b['customer_phone']) ?></small>
            </td>
            <td>
                <strong><?= htmlspecialchars($b['event_title'] ?? 'N/A') ?></strong><br>
                <small style="color:#888;"><?= $b['event_date'] ? date('M d, Y H:i', strtotime($b['event_date'])) : '' ?></small>
            </td>
            <td><strong><?= $b['tickets_quantity'] ?></strong></td>
            <td><strong style="color:var(--admin-accent,#d4af37);"><?= format_currency($pdo, $b['total_price']) ?></strong></td>
            <td><?= date('M d, Y', strtotime($b['booking_date'])) ?></td>
            <td>
                <!-- Cambio de estado rápido -->
                <form method="GET" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <select name="status" class="status-dropdown" onchange="this.form.submit()">
                        <option value="confirmed" <?= $b['status'] === 'confirmed' ? 'selected' : '' ?>>✓ <?= at('confirmed') ?></option>
                        <option value="pending" <?= $b['status'] === 'pending' ? 'selected' : '' ?>>⏳ <?= at('pending') ?></option>
                        <option value="cancelled" <?= $b['status'] === 'cancelled' ? 'selected' : '' ?>>✗ <?= at('cancelled') ?></option>
                    </select>
                </form>
            </td>
            <td>
                <a href="?delete=<?= $b['id'] ?>" class="btn btn-danger" onclick="return confirm('<?= at('confirm_delete') ?>')">
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
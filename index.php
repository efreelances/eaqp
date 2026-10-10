<?php
require_once 'config.php';

// ============================================
// HANDLE LANGUAGE CHANGE
// ============================================
if (isset($_GET['lang'])) {
    set_lang($_GET['lang']);
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirect);
    exit;
}

// 🌐 OBTENER EL IDIOMA ACTIVO
$current_lang = get_current_lang();

// ============================================
// 1. FETCH SLIDERS FROM DB
// ============================================
$stmt_sliders = $pdo->query("SELECT * FROM sliders WHERE is_active = 1 ORDER BY sort_order ASC");
$sliders = $stmt_sliders->fetchAll(PDO::FETCH_ASSOC);

// 🤖 APLICAR TRADUCCIÓN AUTOMÁTICA A SLIDERS (Si el idioma es inglés)
if ($current_lang === 'en') {
    foreach ($sliders as &$slide) {
        $slide['title'] = !empty($slide['title_en']) ? $slide['title_en'] : $slide['title'];
        $slide['subtitle'] = !empty($slide['subtitle_en']) ? $slide['subtitle_en'] : $slide['subtitle'];
        $slide['button_text'] = !empty($slide['button_text_en']) ? $slide['button_text_en'] : $slide['button_text'];
    }
}


// 🎨 Cargar todos los temas disponibles
$all_themes = $pdo->query("SELECT * FROM themes ORDER BY id ASC")->fetchAll();

// Crear un array asociativo para acceso rápido
$themes_by_id = [];
foreach ($all_themes as $theme) {
    $themes_by_id[$theme['id']] = $theme;
}


// ============================================
// 2. FETCH 8 FEATURED EVENTS
// ============================================
$stmt_featured = $pdo->query("SELECT * FROM events WHERE is_featured = 1 AND is_active = 1 ORDER BY event_date ASC LIMIT 8");
$featured_events = $stmt_featured->fetchAll(PDO::FETCH_ASSOC);


// 🤖 APLICAR TRADUCCIÓN AUTOMÁTICA A EVENTOS DESTACADOS (Si el idioma es inglés)
if ($current_lang === 'en') {
    foreach ($featured_events as &$event) {
        $event['title'] = !empty($event['title_en']) ? $event['title_en'] : $event['title'];
        $event['description'] = !empty($event['description_en']) ? $event['description_en'] : $event['description'];
        $event['location'] = !empty($event['location_en']) ? $event['location_en'] : $event['location'];
    }
}

// ============================================
// 3. FETCH 5 UPCOMING EVENTS
// ============================================
$stmt_upcoming = $pdo->query("SELECT * FROM events WHERE is_featured = 0 AND is_active = 1 ORDER BY event_date ASC LIMIT 5");
$upcoming_events = $stmt_upcoming->fetchAll(PDO::FETCH_ASSOC);

// 🤖 APLICAR TRADUCCIÓN AUTOMÁTICA A PRÓXIMOS EVENTOS (Si el idioma es inglés)
if ($current_lang === 'en') {
    foreach ($upcoming_events as &$event) {
        $event['title'] = !empty($event['title_en']) ? $event['title_en'] : $event['title'];
        $event['description'] = !empty($event['description_en']) ? $event['description_en'] : $event['description'];
        $event['location'] = !empty($event['location_en']) ? $event['location_en'] : $event['location'];
    }
}

include 'header.php';
?>
<main>
<!-- ============================================
SLIDER SECTION (dynamic from DB + Theme Colors)
============================================ -->
<section id="home" class="slider-section">
<div class="slider-container">
<?php if (!empty($sliders)): ?>
    <?php foreach ($sliders as $i => $slide): 
        // 🎨 Obtener el tema asociado a este slider
        $slide_theme = null;
        if (!empty($slide['theme_id']) && isset($themes_by_id[$slide['theme_id']])) {
            $slide_theme = $themes_by_id[$slide['theme_id']];
        }
    ?>
    <div class="slide <?= $i === 0 ? 'active' : '' ?>" 
         data-duration="<?= (int)($slide['display_seconds'] ?? 5) ?>"
         data-theme-id="<?= $slide['theme_id'] ?? 1 ?>"
         style="background-image: url('<?= htmlspecialchars($slide['image_url']) ?>');
                --slide-gold: <?= $slide_theme['color_gold'] ?? '#d4af37' ?>;
                --slide-gold-hover: <?= $slide_theme['color_gold_hover'] ?? '#b5952f' ?>;
                --slide-white: <?= $slide_theme['color_white'] ?? '#ffffff' ?>;
                --slide-black: <?= $slide_theme['color_black'] ?? '#0a0a0a' ?>;
                --slide-light-gray: <?= $slide_theme['color_light_gray'] ?? '#cccccc' ?>;">
        <div class="slide-content">
            <h2><?= htmlspecialchars($slide['title']) ?></h2>
            <p><?= htmlspecialchars($slide['subtitle']) ?></p>
            <a href="<?= htmlspecialchars($slide['button_link']) ?>" class="btn-gold"><?= htmlspecialchars($slide['button_text']) ?></a>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="slide active" data-duration="5" data-theme-id="1" 
         style="background-image: url('uploads/slider1.jpg');
                --slide-gold: #d4af37;
                --slide-gold-hover: #b5952f;
                --slide-white: #ffffff;
                --slide-black: #0a0a0a;
                --slide-light-gray: #cccccc;">
        <div class="slide-content">
            <h2>Experience the Extraordinary</h2>
            <p>Exclusive events in the heart of Arequipa</p>
            <a href="#featured" class="btn-gold">Explore Events</a>
        </div>
    </div>
<?php endif; ?>
<button class="slider-btn prev-btn"><i class="fas fa-chevron-left"></i></button>
<button class="slider-btn next-btn"><i class="fas fa-chevron-right"></i></button>
<div class="slider-dots"></div>
</div>
</section>

<!-- ============================================
FEATURED EVENTS
============================================ -->
<section id="featured" class="section-padding">
    <div class="container" id="proximos">
        <h2 class="section-title"><span><?= t('featured_events') ?></span></h2>
        <div class="events-grid">
            <?php foreach ($featured_events as $event): ?>
            <div class="event-card">
                <div class="event-image">
                    <a href="events.php?id=<?= $event['id'] ?>">
                        <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                    </a>

                    <span class="event-price"><?php echo format_currency($pdo, $event['price']); ?></span>
                </div>
                <div class="event-details">
                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p class="event-date"><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($event['event_date'])); ?></p>
                    <p class="event-desc"><?php echo htmlspecialchars(substr($event['description'], 0, 80)); ?>...</p>
                    <a href="events.php?id=<?php echo $event['id']; ?>" class="btn-outline"><?= t('view_details') ?></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($featured_events) >= 8): ?>
        <div class="text-center" style="margin-top: 40px;">
            <button class="btn-gold" id="loadMoreFeatured"><?= t('view_more') ?></button>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================
UPCOMING EVENTS
============================================ -->
<section id="upcoming" class="section-padding bg-darker">
    <div class="container">
        <h2 class="section-title"><?= t('upcoming_events') ?></h2>
        <div class="upcoming-list">
            <?php foreach ($upcoming_events as $event): ?>
            <div class="upcoming-item">
                <div class="upcoming-date">
                    <span class="day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                    <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                </div>
                <div class="upcoming-info">
                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location'] ?? 'Elite Venue, Arequipa'); ?></p>
                </div>
                <div class="upcoming-action">
                    <span class="bold"><?php echo format_currency($pdo, $event['price']); ?></span>
                    <button class="btn-gold-sm"><?= t('get_tickets') ?></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
</main>
<?php include 'footer.php'; ?>
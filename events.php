<?php
require_once 'config.php';

// 1. Obtener el ID del evento desde la URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Consultar el evento en la base de datos
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND is_active = 1");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no se encuentra el evento, redirigir al inicio
if (!$event) {
    header("Location: index.php");
    exit;
}

// 3. Aplicar traducción si el idioma es inglés
$current_lang = get_current_lang();
if ($current_lang === 'en') {
    $event['title'] = !empty($event['title_en']) ? $event['title_en'] : $event['title'];
    $event['description'] = !empty($event['description_en']) ? $event['description_en'] : $event['description'];
    $event['location'] = !empty($event['location_en']) ? $event['location_en'] : $event['location'];
}

//  Cargar todos los temas disponibles
$all_themes = $pdo->query("SELECT * FROM themes ORDER BY id ASC")->fetchAll();
$themes_by_id = [];
foreach ($all_themes as $theme) {
    $themes_by_id[$theme['id']] = $theme;
}

// ============================================
// FETCH SLIDERS FROM DB
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

// 4. Incluir el header
include 'header.php';
?>




<!-- ============================================
     HERO DEL EVENTO - Diseño Premium
     ============================================ -->
<section class="event-hero-premium" style="background-image: linear-gradient(135deg, rgba(10,10,10,0.95) 0%, rgba(26,26,26,0.9) 100%), url('<?= htmlspecialchars($event['image_url'] ?: 'default.jpg') ?>');">
    <div class="container">
        <div class="event-hero-content">
            <div class="event-hero-badge"><?= date('d M, Y', strtotime($event['event_date'])) ?></div>
            <h1 class="event-hero-title"><?= htmlspecialchars($event['title']) ?></h1>
            <?php if (!empty($event['lineup'])): ?>
            <p class="event-hero-lineup"><?= htmlspecialchars($event['lineup']) ?></p>
            <?php endif; ?>
            
            <div class="event-hero-meta">
                <div class="hero-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="meta-text">
                        <span class="meta-label">Lugar</span>
                        <span class="meta-value"><?= htmlspecialchars($event['location']) ?></span>
                    </div>
                </div>
                
                <div class="hero-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="meta-text">
                        <span class="meta-label">Fecha</span>
                        <span class="meta-value"><?= date('d \d\e F \d\e Y', strtotime($event['event_date'])) ?></span>
                    </div>
                </div>
                
                <div class="hero-meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="meta-text">
                        <span class="meta-label">Hora</span>
                        <span class="meta-value"><?= date('h:i A', strtotime($event['event_date'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>




<!-- ============================================
     SLIDER SECTION (Dinámico por Evento + Temas)
     ============================================ -->
<section id="home" class="slider-section">
<div class="slider-container">
<?php
// 1. Obtener sliders específicos de este evento con sus colores de tema
$stmt_slides = $pdo->prepare("
    SELECT es.image_url, es.sort_order, 
           t.color_gold, t.color_gold_hover, t.color_white, t.color_black, t.color_light_gray
    FROM event_sliders es
    LEFT JOIN themes t ON es.theme_id = t.id
    WHERE es.event_id = ?
    ORDER BY es.sort_order ASC
");
$stmt_slides->execute([$event_id]);
$event_slides = $stmt_slides->fetchAll(PDO::FETCH_ASSOC);

// 2. Fallback: si no hay sliders, usar imagen principal del evento
if (empty($event_slides)) {
    $event_slides = [[
        'image_url' => $event['image_url'] ?: 'uploads/default.jpg',
        'sort_order' => 0,
        'color_gold' => '#d4af37',
        'color_gold_hover' => '#b5952f',
        'color_white' => '#ffffff',
        'color_black' => '#0a0a0a',
        'color_light_gray' => '#cccccc'
    ]];
}
?>

<?php foreach ($event_slides as $i => $slide): ?>
    <div class="slide <?= $i === 0 ? 'active' : '' ?>" 
         data-duration="5"
         style="background-image: url('<?= htmlspecialchars($slide['image_url']) ?>');
                --slide-gold: <?= htmlspecialchars($slide['color_gold']) ?>;
                --slide-gold-hover: <?= htmlspecialchars($slide['color_gold_hover']) ?>;
                --slide-white: <?= htmlspecialchars($slide['color_white']) ?>;
                --slide-black: <?= htmlspecialchars($slide['color_black']) ?>;
                --slide-light-gray: <?= htmlspecialchars($slide['color_light_gray']) ?>;">
        <div class="slide-content">
            <!--<a href="#tickets" class="btn-gold"><?= t('select') ?> Entradas</a>-->
        </div>
    </div>
<?php endforeach; ?>

<button class="slider-btn prev-btn"><i class="fas fa-chevron-left"></i></button>
<button class="slider-btn next-btn"><i class="fas fa-chevron-right"></i></button>
<div class="slider-dots"></div>
</div>
</section>



<!-- ============================================
     CONTENIDO PRINCIPAL - Layout 2 Columnas
     ============================================ -->
<section class="event-main-content">
    <div class="container">
        <div class="event-grid-layout">
            
            <!-- COLUMNA IZQUIERDA: Info del Evento -->
            <div class="event-info-section">
                <div class="content-card">
                    <h2 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        <?= t('about_event') ?>
                    </h2>
                    <div class="event-description-content">
                        <?= nl2br(htmlspecialchars($event['description'])) ?>
                    </div>
                </div>
                
                <div class="content-card location-card">
                    <h2 class="card-title">
                        <i class="fas fa-map-marked-alt"></i>
                        <?= t('location') ?>
                    </h2>
                    <div class="location-info">
                        <p class="location-name">
                            <i class="fas fa-location-dot"></i>
                            <?= htmlspecialchars($event['location']) ?>
                        </p>
                        <a href="https://maps.google.com/?q=<?= urlencode($event['location']) ?>" 
                           target="_blank" 
                           class="btn-map">
                            <i class="fas fa-external-link-alt"></i>
                            <?= t('view_map') ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: Tickets (Sticky) -->
            <div class="event-tickets-section">
                <div class="tickets-card-premium">
                    <div class="tickets-header">
                        <i class="fas fa-ticket-alt"></i>
                        <h2><?= t('ticket_prices') ?></h2>
                    </div>
                    
                    <div class="tickets-body">
                        <div class="ticket-row">
                            <div class="ticket-details">
                                <span class="ticket-type">Zona General</span>
                                <span class="ticket-availability">
                                    <i class="fas fa-fire"></i> Quedan 800
                                </span>
                            </div>
                            <div class="ticket-purchase">
                                <span class="ticket-cost">S/ 20.00</span>
                                <button class="btn-select"><?= t('select') ?></button>
                            </div>
                        </div>
                        
                        <div class="ticket-row featured">
                            <div class="ticket-details">
                                <span class="ticket-type">Mesa Superior</span>
                                <span class="ticket-availability">
                                    <i class="fas fa-fire"></i> Quedan 44
                                </span>
                            </div>
                            <div class="ticket-purchase">
                                <span class="ticket-cost">S/ 300.00</span>
                                <button class="btn-select"><?= t('select') ?></button>
                            </div>
                        </div>
                        
                        <div class="ticket-row">
                            <div class="ticket-details">
                                <span class="ticket-type">Box VIP</span>
                                <span class="ticket-availability">
                                    <i class="fas fa-fire"></i> Quedan 3
                                </span>
                            </div>
                            <div class="ticket-purchase">
                                <span class="ticket-cost">S/ 700.00</span>
                                <button class="btn-select"><?= t('select') ?></button>
                            </div>
                        </div>
                        
                        <div class="ticket-row">
                            <div class="ticket-details">
                                <span class="ticket-type">Box Ultis</span>
                                <span class="ticket-availability">
                                    <i class="fas fa-crown"></i> Quedan 1
                                </span>
                            </div>
                            <div class="ticket-purchase">
                                <span class="ticket-cost">S/ 1800.00</span>
                                <button class="btn-select"><?= t('select') ?></button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tickets-footer">
                        <i class="fas fa-info-circle"></i>
                        <?= t('sale_starts') ?> <?= date('d/m/Y h:i A', strtotime($event['event_date'] . ' -4 hours')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     PRÓXIMOS EVENTOS
     ============================================ -->
<section class="upcoming-events-section section-padding">
    <div class="container">
        <h2 class="section-title"><span><?= t('upcoming_events') ?></span></h2>
        <div class="events-grid">
            <?php
            $stmt_upcoming = $pdo->prepare("SELECT * FROM events WHERE is_active = 1 AND id != ? ORDER BY event_date ASC LIMIT 3");
            $stmt_upcoming->execute([$event_id]);
            $upcoming_events = $stmt_upcoming->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($upcoming_events as $up_event):
                $up_title = ($current_lang === 'en' && !empty($up_event['title_en'])) ? $up_event['title_en'] : $up_event['title'];
                $up_desc = ($current_lang === 'en' && !empty($up_event['description_en'])) ? $up_event['description_en'] : $up_event['description'];
            ?>
            <div class="event-card">
                <div class="event-image">
                    <a href="events.php?id=<?= $up_event['id'] ?>">
                        <img src="<?= htmlspecialchars($up_event['image_url'] ?: 'default.jpg') ?>" alt="<?= htmlspecialchars($up_title) ?>">
                    </a>
                    <span class="event-price"><?= format_currency($pdo, $up_event['price']) ?></span>
                </div>
                <div class="event-details">
                    <h3><?= htmlspecialchars($up_title) ?></h3>
                    <p class="event-date"><i class="far fa-calendar-alt"></i> <?= date('M d, Y', strtotime($up_event['event_date'])) ?></p>
                    <p class="event-desc"><?= htmlspecialchars(substr($up_desc, 0, 80)) ?>...</p>
                    <a href="events.php?id=<?= $up_event['id'] ?>" class="btn-outline"><?= t('view_details') ?></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
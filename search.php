<?php
require_once 'config.php';

// Obtener el término de búsqueda
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// 4. Incluir el header
include 'header.php';
?>

<!-- Forzar fondo negro en la página de búsqueda -->
<style>
body, .search-section {
    background-color: #0a0a0a !important;
}
</style>

<!-- ============================================
     SECCIÓN DE BÚSQUEDA
     ============================================ -->
<section class="search-section section-padding">
    <div class="container">
        <h1 class="section-title">
            <span><?= t('search_results') ?></span>
        </h1>
        
        <?php if (!empty($search_query)): ?>
            <p style="text-align: center; color: var(--color-light-gray); margin-bottom: 40px; font-size: 18px;">
                <?= t('searching_for') ?>: <strong style="color: var(--color-gold);">"<?= htmlspecialchars($search_query) ?>"</strong>
            </p>
            
            <?php
            // Buscar eventos que coincidan con el término
            $stmt = $pdo->prepare("
                SELECT * FROM events 
                WHERE is_active = 1 
                AND (title LIKE :query 
                     OR description LIKE :query 
                     OR location LIKE :query)
                ORDER BY event_date DESC
            ");
            $stmt->execute(['query' => '%' . $search_query . '%']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) > 0):
            ?>
                <div class="events-grid">
                    <?php foreach ($results as $event): 
                        $event_title = ($current_lang === 'en' && !empty($event['title_en'])) ? $event['title_en'] : $event['title'];
                        $event_desc = ($current_lang === 'en' && !empty($event['description_en'])) ? $event['description_en'] : $event['description'];
                    ?>
                    <div class="event-card">
                        <div class="event-image">
                            <img src="<?= htmlspecialchars($event['image_url'] ?: 'default.jpg') ?>" alt="<?= htmlspecialchars($event_title) ?>">
                            <span class="event-price"><?= format_currency($pdo, $event['price']) ?></span>
                        </div>
                        <div class="event-details">
                            <h3><?= htmlspecialchars($event_title) ?></h3>
                            <p class="event-date"><i class="far fa-calendar-alt"></i> <?= date('M d, Y', strtotime($event['event_date'])) ?></p>
                            <p class="event-desc"><?= htmlspecialchars(substr($event_desc, 0, 80)) ?>...</p>
                            <a href="events.php?id=<?= $event['id'] ?>" class="btn-outline"><?= t('view_details') ?></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-search" style="font-size: 64px; color: var(--color-gray); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--color-white); margin-bottom: 15px;"><?= t('no_results') ?></h3>
                    <p style="color: var(--color-light-gray);"><?= t('try_different_search') ?></p>
                    <a href="index.php" class="btn-gold" style="margin-top: 30px;"><?= t('back_home') ?></a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-search" style="font-size: 64px; color: var(--color-gray); margin-bottom: 20px;"></i>
                <h3 style="color: var(--color-white); margin-bottom: 15px;"><?= t('enter_search_term') ?></h3>
                <p style="color: var(--color-light-gray);"><?= t('use_search_bar') ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'footer.php'; ?>
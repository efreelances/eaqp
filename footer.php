<?php
// ============================================
// LOAD SETTINGS AND DYNAMIC DATA
// ============================================
$site_name      = get_setting($pdo, 'site_name', 'ELITE AREQUIPA');
$logo_text      = get_setting($pdo, 'logo_text', 'ELITE');
$logo_highlight = get_setting($pdo, 'logo_highlight', 'AREQUIPA');
$tagline        = get_setting($pdo, 'site_tagline', t('footer_tagline'));
$contact_email  = get_setting($pdo, 'contact_email', 'info@elitearequipa.com');
$contact_phone  = get_setting($pdo, 'contact_phone', '+51 999 999 999');
$contact_address = get_setting($pdo, 'contact_address', 'Arequipa, Peru');

// 🌐 Redes sociales dinámicas desde la tabla social_media
$social_media = get_social_media($pdo);
?>
<footer class="main-footer" id="contact">
<div class="container footer-container">
    <!-- SECCIÓN 1: Logo y redes sociales -->
    <div class="footer-section">
        <img src="logo-e.png" width="30" alt="Logo">    
        <!--<h3><?php echo htmlspecialchars($logo_text); ?><span><?php echo htmlspecialchars($logo_highlight); ?></span></h3>-->
        <p><?php echo htmlspecialchars($tagline); ?></p>
        
        <?php if (!empty($social_media)): ?>
        <div class="footer-social">
            <?php foreach ($social_media as $social): ?>
            <a href="<?php echo htmlspecialchars($social['url']); ?>" 
               target="_blank" 
               class="social-icon" 
               title="<?php echo htmlspecialchars($social['name']); ?>">
                <i class="<?php echo htmlspecialchars($social['icon']); ?>"></i>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- SECCIÓN 2: Enlaces rápidos -->
    <div class="footer-section">
        <h4><?= t('quick_links') ?></h4>
        <ul>
            <li><a href="#home"><i class="fas fa-chevron-right"></i> <?= t('home') ?></a></li>
            <li><a href="#featured"><i class="fas fa-chevron-right"></i> <?= t('featured') ?></a></li>
            <li><a href="#upcoming"><i class="fas fa-chevron-right"></i> <?= t('upcoming') ?></a></li>
        </ul>
    </div>
    
    <!-- SECCIÓN 3: Contacto -->
    <div class="footer-section">
        <h4><?= t('contact_us') ?></h4>
        <p><i class="fas fa-envelope"></i> 
           <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>">
               <?php echo htmlspecialchars($contact_email); ?>
           </a>
        </p>
        <p><i class="fas fa-phone"></i> 
           <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $contact_phone); ?>">
               <?php echo htmlspecialchars($contact_phone); ?>
           </a>
        </p>
        <p><i class="fas fa-map-marker-alt"></i> 
           <?php echo htmlspecialchars($contact_address); ?>
        </p>
    </div>
</div>

<!-- Barra inferior -->
<div class="container">
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. 
           <?php echo t('all_rights'); ?>
        </p>
    </div>
</div>
</footer>

<!-- Botón Scroll to Top -->
<button class="scroll-top" id="scrollTopBtn" aria-label="Scroll to top">
    <i class="fas fa-arrow-up"></i>
</button>



<!-- ============================================
BOTÓN FLOTANTE DE CHATBOT
============================================ -->
<a href="https://elitearequipa.com/chat/" target="_blank" rel="noopener noreferrer" class="chatbot-btn" title="Chat - Asistencia Virtual">
    <img src="logo-copa.png" width="20">
</a>


<!-- Vinculación del script -->
<script src="script.js"></script>
</body>
</html>
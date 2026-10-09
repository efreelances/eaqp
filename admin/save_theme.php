<?php
require_once 'auth.php';
require_login();

// ============================================
// PROCESAR ACTUALIZACIÓN DE TEMA
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme_id'])) {
    $id = (int)$_POST['theme_id'];
    
    // Campos de colores del sitio web
    $webFields = [
        'color_black',
        'color_dark_gray',
        'color_gray',
        'color_gold',
        'color_gold_hover',
        'color_white',
        'color_light_gray'
    ];
    
    // Campos de colores del panel admin
    $adminFields = [
        'admin_sidebar_bg',
        'admin_sidebar_text',
        'admin_sidebar_active',
        'admin_content_bg',
        'admin_card_bg',
        'admin_accent'
    ];
    
    // Unir todos los campos
    $allFields = array_merge($webFields, $adminFields);
    
    // Validar y limpiar cada color
    $data = [];
    foreach ($allFields as $f) {
        // Aceptar tanto el campo del color picker como el hex input
        $hex = $_POST[$f.'_hex'] ?? $_POST[$f] ?? '#000000';
        
        // Validar formato hex
        if (!preg_match('/^#[0-9A-F]{6}$/i', $hex)) {
            $hex = '#000000';
        }
        
        $data[$f] = strtoupper($hex);
    }
    
    // Preparar SQL con todos los campos
    $sql = "UPDATE themes SET 
        color_black=:color_black, 
        color_dark_gray=:color_dark_gray,
        color_gray=:color_gray, 
        color_gold=:color_gold,
        color_gold_hover=:color_gold_hover, 
        color_white=:color_white,
        color_light_gray=:color_light_gray,
        admin_sidebar_bg=:admin_sidebar_bg, 
        admin_sidebar_text=:admin_sidebar_text,
        admin_sidebar_active=:admin_sidebar_active, 
        admin_content_bg=:admin_content_bg,
        admin_card_bg=:admin_card_bg, 
        admin_accent=:admin_accent
        WHERE id=:id";
    
    $data['id'] = $id;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    
    header('Location: themes.php?saved=1');
    exit;
}

// Si no es POST o no hay theme_id, redirigir
header('Location: themes.php');
exit;
?>
<?php
// ✅ Iniciar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

// ============================================
// AUTENTICACIÓN
// ============================================
function require_login() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: index.php');
        exit;
    }
}

function is_logged_in() {
    return isset($_SESSION['admin_id']);
}

// ============================================
// SISTEMA DE IDIOMA DEL ADMIN
// ============================================
function admin_lang() {
    return $_SESSION['admin_lang'] ?? 'es';
}

function at($key) {
    static $translations = null;
    static $current_lang = null;
    $lang = admin_lang();
    if ($translations === null || $current_lang !== $lang) {
        $file = __DIR__ . '/lang/' . $lang . '.php';
        if (file_exists($file)) {
            $translations = require $file;
        } else {
            $translations = require __DIR__ . '/lang/es.php';
        }
        $current_lang = $lang;
    }
    return $translations[$key] ?? $key;
}
?>
<?php
// ✅ Iniciar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

// ============================================
// HANDLE LANGUAGE CHANGE
// ============================================
if (isset($_GET['lang'])) {
    $_SESSION['admin_lang'] = in_array($_GET['lang'], ['es', 'en']) ? $_GET['lang'] : 'es';
    header('Location: index.php');
    exit;
}

// Load admin translations
require_once 'auth.php';
$lang = admin_lang();

// Translations for login page
$translations = [
    'es' => [
        'title' => 'Iniciar Sesión',
        'username' => 'Usuario',
        'password' => 'Contraseña',
        'login' => 'Ingresar',
        'invalid' => 'Credenciales inválidas',
    ],
    'en' => [
        'title' => 'Login',
        'username' => 'Username',
        'password' => 'Password',
        'login' => 'Login',
        'invalid' => 'Invalid credentials',
    ],
];
$t = $translations[$lang] ?? $translations['en'];

// ============================================
// LOGIN LOGIC
// ============================================
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = $t['invalid'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $t['title'] ?> | Elite Admin 2026</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }

body { 
    background: linear-gradient(135deg, #0a0a0a, #1a1a1a); 
    min-height: 100vh; 
    display: flex; 
    align-items: center; 
    justify-content: center;
    padding: 20px; /* ✅ NUEVO: Evita que la caja toque los bordes en móviles */
}

.login-box { 
    background: #1a1a1a; 
    padding: 40px; 
    border-radius: 15px; 
    width: 100%; /* ✅ NUEVO: Ocupa el ancho disponible */
    max-width: 400px; /* ✅ NUEVO: Pero nunca supera los 400px en escritorio */
    border: 2px solid #d4af37; 
    box-shadow: 0 10px 40px rgba(212, 175, 55, 0.2); 
}

.login-box h1 { 
    color: #d4af37; 
    text-align: center; 
    margin-bottom: 30px; 
    font-size: 28px; 
}

.login-box h1 span { color: #fff; }

.form-group { margin-bottom: 20px; }

.form-group label { 
    color: #ccc; 
    display: block; 
    margin-bottom: 8px; 
    font-size: 14px; 
}

.form-group input { 
    width: 100%; 
    padding: 12px; 
    background: #0a0a0a; 
    border: 1px solid #2d2d2d; 
    border-radius: 8px; 
    color: #fff; 
    font-size: 14px; 
    transition: border-color 0.3s ease;
}

.form-group input:focus { 
    outline: none; 
    border-color: #d4af37; 
}

.btn-login { 
    width: 100%; 
    padding: 12px; 
    background: #d4af37; 
    color: #0a0a0a; 
    border: none; 
    border-radius: 8px; 
    font-weight: 700; 
    cursor: pointer; 
    text-transform: uppercase; 
    font-size: 14px; 
    transition: background 0.3s ease;
}

.btn-login:hover { background: #b5952f; }

.error { 
    background: #ff4444; 
    color: #fff; 
    padding: 10px; 
    border-radius: 5px; 
    margin-bottom: 15px; 
    text-align: center; 
    font-size: 14px; 
}

.lang-switch { 
    text-align: center; 
    margin-top: 20px; 
    display: flex; 
    justify-content: center; 
    gap: 10px; 
}

.lang-switch a { 
    color: #d4af37; 
    text-decoration: none; 
    padding: 8px 16px; 
    border: 1px solid #2d2d2d; 
    border-radius: 20px; 
    font-size: 13px; 
    transition: all 0.3s;
    flex: 1; /* ✅ NUEVO: Hace que los botones de idioma sean del mismo tamaño */
    text-align: center;
}

.lang-switch a:hover { 
    border-color: #d4af37; 
}

.lang-switch a.active { 
    background: #d4af37; 
    color: #0a0a0a; 
    border-color: #d4af37;
}

/* ============================================
RESPONSIVE DESIGN (Móviles)
============================================ */
@media (max-width: 480px) {
    .login-box {
        padding: 30px 20px; /* Reduce el padding interno en pantallas muy pequeñas */
    }
    
    .login-box h1 {
        font-size: 24px;
        margin-bottom: 20px;
    }
    
    .form-group input {
        padding: 14px; /* Área de toque más grande para dedos en móvil */
        font-size: 16px; /* Evita que iOS haga zoom al escribir */
    }
    
    .btn-login {
        padding: 14px;
        font-size: 15px;
    }
    
    .lang-switch {
        flex-direction: column; /* Apila los botones de idioma verticalmente en móviles muy pequeños */
        gap: 8px;
    }
}
</style>
</head>
<body>
<div class="login-box">
    <h1><img src="../elite-dorado.png" width="100"> </h1>
    
    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label><?= $t['username'] ?></label>
            <input type="text" name="username" required autofocus autocomplete="username">
        </div>
        <div class="form-group">
            <label><?= $t['password'] ?></label>
            <input type="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-login"><?= $t['login'] ?></button>
    </form>
    <div class="lang-switch">
        <a href="../index.php">Volver a la pagina</a>
    <!--<a href="?lang=es" class="<?= $lang === 'es' ? 'active' : '' ?>">🇪🇸 </a>
        <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">🇺🇸 </a>-->
    </div>
</div>
</body>
</html>
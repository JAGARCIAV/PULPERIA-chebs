<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['user'])) {
    $rol = $_SESSION['user']['rol'] ?? '';
    if ($rol === 'admin') {
        header("Location: /PULPERIA-CHEBS/index.php");
        exit;
    }
    header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php");
    exit;
}

$error = $_GET['err'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login | Pulpería Chebs</title>
    <style>
        body{
            display:flex; justify-content:center; align-items:center;
            height:100vh; background:#f4f4f4; font-family:Arial,sans-serif;
        }
        .login-box{
            background:#fff; padding:25px; width:320px;
            border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,.1);
        }
        h2{ text-align:center; margin-bottom:20px; }
        input{ width:100%; padding:10px; margin-bottom:12px; }
        button{
            width:100%; padding:10px; background:#2c7be5;
            color:#fff; border:none; cursor:pointer; font-size:16px;
        }
        .error{
            background:#ffd6d6; color:#900; padding:8px;
            margin-bottom:10px; text-align:center; border-radius:4px;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Iniciar Sesión</h2>

    <?php if ($error): ?>
        <div class="error">Usuario o contraseña incorrectos</div>
    <?php endif; ?>

    <form method="POST" action="/PULPERIA-CHEBS/controladores/login.php">
        <input type="text" name="usuario" placeholder="Usuario" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Entrar</button>
    </form>
</div>

</body>
</html>

<?php 
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . "/../../config/auth.php";
require_login();

$rol = $_SESSION['user']['rol'] ?? '';
$esAdmin = ($rol === 'admin');

$cssPathFs = __DIR__ . "/../../dist/output.css";
$cssVer = file_exists($cssPathFs) ? filemtime($cssPathFs) : time();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Pulpería Chebs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="icon" type="image/png" href="/PULPERIA-CHEBS/public/img/logo.png">

  <!-- ✅ Tailwind LOCAL (sin internet) + anti-caché -->
  <link rel="stylesheet" href="/PULPERIA-CHEBS/dist/output.css?v=<?= $cssVer ?>">

  <!-- ✅ Fallback mínimo SOLO para el logo (por si Tailwind no carga) -->
  <style>
    .chebs-logo-fallback{
      width:32px !important;
      height:32px !important;
      object-fit:contain !important;
      display:block !important;
    }
  </style>
</head>

<body class="min-h-screen bg-chebs-soft text-chebs-black overflow-x-hidden">

<header class="fixed top-0 left-0 w-full z-50">
  <div class="bg-white border-b border-chebs-line shadow-soft">
    <div class="max-w-[1440px] mx-auto px-6">
      <div class="h-[72px] flex items-center justify-between gap-6">

        <!-- ✅ LOGO (tamaño fijo SI O SI) -->
        <div class="flex items-center gap-3 min-w-[120px]">
          <img
            src="/PULPERIA-CHEBS/public/img/logo.png"
            alt="Chebs"
            class="chebs-logo-fallback h-[32px] w-[32px] rounded-xl bg-white object-contain"
            style="width:32px;height:32px;object-fit:contain;"
          >

          <div class="leading-tight">
            <div class="font-semibold text-sm">Pulpería Chebs</div>
            <div class="text-xs text-gray-500">
              <?= $esAdmin ? 'Administrador' : 'Empleado' ?>
            </div>
          </div>
        </div>

        <!-- MENÚ -->
        <nav class="flex items-center gap-2 text-sm font-semibold">

          <a href="/PULPERIA-CHEBS/index.php"
             class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
            Inicio
          </a>

          <?php if ($esAdmin): ?>
            <a href="/PULPERIA-CHEBS/vistas/productos/listar.php"
               class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
              Productos
            </a>

            <a href="/PULPERIA-CHEBS/vistas/lotes/listar.php"
               class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
              Lotes
            </a>

<a href="/PULPERIA-CHEBS/vistas/notificacion/notificacion.php"
   id="nav_notif"
   class="relative px-4 py-2 rounded-xl hover:bg-chebs-soft transition inline-flex items-center gap-2">

  <span class="relative inline-flex items-center justify-center">
    
    <span id="nav_notif_badge"
          class="hidden absolute -top-2 -right-2 min-w-[20px] h-[20px] px-1
                 rounded-full bg-red-600 text-white text-[12px] font-black
                 flex items-center justify-center leading-none">
      0
    </span>
  </span>

  <span>Notificaciones 🔔</span>
</a>
            <a href="/PULPERIA-CHEBS/vistas/movimientos/historial.php"
               class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
              Historial Inv.
            </a>

            <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php"
               class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
              Historial Ventas
            </a>

            <a href="/PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php"
               class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
              Usuarios
            </a>
          <?php endif; ?>

          <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
             class="ml-2 px-5 py-2 rounded-xl bg-chebs-green text-white
                    hover:bg-chebs-greenDark transition shadow-soft">
            Caja
          </a>

          <a href="/PULPERIA-CHEBS/controladores/logout.php"
             class="ml-1 px-5 py-2 rounded-xl
                    bg-red-600 text-white font-extrabold
                    hover:bg-red-700 hover:shadow-lg
                    transition duration-200">
            Salir
          </a>

        </nav>

      </div>
    </div>
  </div>
</header>

<main class="pt-[88px] px-6 pb-6 w-full">
  <?php
    $ruta = $_SERVER['REQUEST_URI'] ?? '';
    $esCaja = (strpos($ruta, '/vistas/ventas/venta.php') !== false);
  ?>
  <div class="<?= $esCaja ? 'w-full max-w-none' : 'max-w-[1440px]' ?> mx-auto">
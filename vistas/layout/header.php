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

/* ✅ Ruta actual (solo PATH, sin ?query) */
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

/* ✅ Helper para saber si estoy en esa pestaña */
function esRuta($rutaActual, $needle){
  return (strpos($rutaActual, $needle) !== false);
}

/* ✅ Activos */
$actInicio   = ($ruta === '/PULPERIA-CHEBS/' || $ruta === '/PULPERIA-CHEBS/index.php');
$actProd     = esRuta($ruta, '/vistas/productos/');
$actLotes    = esRuta($ruta, '/vistas/lotes/');
$actNotif    = esRuta($ruta, '/vistas/notificacion/');
$actMov      = esRuta($ruta, '/vistas/movimientos/');
$actHistV    = esRuta($ruta, '/vistas/ventas/historial.php');
$actUsuarios = esRuta($ruta, '/vistas/perfiles/');
$actCaja     = esRuta($ruta, '/vistas/ventas/venta.php');

/* ✅ Clases base */
$clsLink = "px-4 py-2 rounded-xl hover:bg-chebs-soft transition";

/* ✅ Activo con parpadeo (para todo MENOS Caja) */
$clsActiveBlink = "bg-chebs-soft ring-2 ring-chebs-green shadow-soft chebs-blink";

/* ✅ Activo fijo (para Caja) */
$clsActiveCaja = "bg-chebs-green text-white ring-2 ring-chebs-greenDark shadow-soft";

/* ✅ Main ancho completo en Caja */
$esCaja = $actCaja;
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

  <style>
    /* ✅ Fallback mínimo SOLO para el logo (por si Tailwind no carga) */
    .chebs-logo-fallback{
      width:32px !important;
      height:32px !important;
      object-fit:contain !important;
      display:block !important;
    }

    /* ✅ EFECTO “pestaña activa” (parpadeo suave) */
/* 🔥 EFECTO ACTIVO FUERTE CHEBS */
@keyframes chebsPulseStrong {
  0% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(34,197,94,0.7);
  }
  50% {
    transform: scale(1.08);
    box-shadow: 0 0 18px 6px rgba(34,197,94,0.6);
  }
  100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(34,197,94,0.7);
  }
}



.chebs-blink {
  animation: chebsPulseStrong 1s ease-in-out infinite;
  background: linear-gradient(90deg, #dcfce7, #bbf7d0);
  color: #065f46 !important;
  font-weight: 800;
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
             class="<?= $clsLink . ($actInicio ? " $clsActiveBlink" : "") ?>">
            Inicio
          </a>

          <?php if ($esAdmin): ?>
            <a href="/PULPERIA-CHEBS/vistas/productos/listar.php"
               class="<?= $clsLink . ($actProd ? " $clsActiveBlink" : "") ?>">
              Productos
            </a>

            <a href="/PULPERIA-CHEBS/vistas/lotes/listar.php"
               class="<?= $clsLink . ($actLotes ? " $clsActiveBlink" : "") ?>">
              Lotes
            </a>

            <a href="/PULPERIA-CHEBS/vistas/notificacion/notificacion.php"
               id="nav_notif"
               class="relative px-4 py-2 rounded-xl hover:bg-chebs-soft transition inline-flex items-center gap-2 <?= $actNotif ? $clsActiveBlink : "" ?>">

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
               class="<?= $clsLink . ($actMov ? " $clsActiveBlink" : "") ?>">
              Historial Inv.
            </a>

            <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php"
               class="<?= $clsLink . ($actHistV ? " $clsActiveBlink" : "") ?>">
              Historial Ventas
            </a>

            <a href="/PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php"
               class="<?= $clsLink . ($actUsuarios ? " $clsActiveBlink" : "") ?>">
              Usuarios
            </a>
          <?php endif; ?>

          <!-- ✅ Caja: si está activa, NO parpadea -->
          <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
             class="ml-2 px-5 py-2 rounded-xl hover:bg-chebs-greenDark transition shadow-soft
                    <?= $actCaja ? $clsActiveCaja : 'bg-chebs-green text-white' ?>">
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
  <div class="<?= $esCaja ? 'w-full max-w-none' : 'max-w-[1440px]' ?> mx-auto">
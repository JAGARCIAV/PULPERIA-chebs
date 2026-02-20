<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . "/../../config/auth.php";
require_login();

$rol = $_SESSION['user']['rol'] ?? '';
$esAdmin = ($rol === 'admin');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Pulpería Chebs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="icon" type="image/png" href="/PULPERIA-CHEBS/public/img/logo.png">

<link rel="stylesheet" href="/PULPERIA-CHEBS/dist/output.css">  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            chebs: {
              green: '#4E7A2B',
              greenDark: '#3b631d',
              black: '#111111',
              soft: '#c7d8b9',
              line: '#E5E7EB'
            }
          },
          boxShadow: {
            soft: '0 10px 30px rgba(0,0,0,.08)'
          }
        }
      }
    }
  </script>
</head>

<body class="min-h-screen bg-chebs-soft text-chebs-black">

<header class="fixed top-0 left-0 w-full z-50">
  <div class="bg-white border-b border-chebs-line shadow-soft">
    <div class="max-w-[1440px] mx-auto px-6">
      <div class="h-[72px] flex items-center justify-between gap-6">

        <!-- Logo -->
        <div class="flex items-center gap-3 min-w-[220px]">
          <img src="/PULPERIA-CHEBS/public/img/logo.png"
               class="h-10 w-10 rounded-xl object-contain bg-white"
               alt="Chebs">

          <div class="leading-tight">
            <div class="font-semibold text-sm">Pulpería Chebs</div>
            <div class="text-xs text-gray-500">
              <?= $esAdmin ? 'Administrador' : 'Empleado' ?>
            </div>
          </div>
        </div>

        <!-- MENÚ -->
        <nav class="flex items-center gap-2 text-sm font-semibold">

          <!-- ✅ Inicio (admin y empleado) -->
          <a href="/PULPERIA-CHEBS/index.php"
             class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
            Inicio
          </a>

          <?php if ($esAdmin): ?>
            <!-- 🔒 SOLO ADMIN -->
            <a href="/PULPERIA-CHEBS/vistas/productos/listar.php"
               class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
              Productos
            </a>

            <a href="/PULPERIA-CHEBS/vistas/lotes/listar.php"
               class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
              Lotes
            </a>

            <a href="/PULPERIA-CHEBS/vistas/notificacion/notificacion.php"
               class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
              🔔 Notificaciones
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

          <!-- ✅ Caja (admin y empleado) -->
          <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
             class="ml-2 px-5 py-2 rounded-xl bg-chebs-green text-white
                    hover:bg-chebs-greenDark transition shadow-soft">
            Caja
          </a>

          <!-- ✅ Salir (admin y empleado) -->
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

<main class="pt-[88px] px-6 pb-6">
  <?php
    $ruta = $_SERVER['REQUEST_URI'] ?? '';
    $esCaja = (strpos($ruta, '/vistas/ventas/venta.php') !== false);
  ?>
  <div class="<?= $esCaja ? 'w-full max-w-none' : 'max-w-[1440px]' ?> mx-auto">
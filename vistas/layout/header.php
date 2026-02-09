<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . "/../../config/auth.php";
require_login();

$rol = $_SESSION['user']['rol'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Pulpería Chebs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="icon" type="image/png" href="/PULPERIA-CHEBS/public/img/logo.png">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
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
              <?= $rol === 'admin' ? 'Administrador' : 'Empleado' ?>
            </div>
          </div>
        </div>

        <!-- Menú -->
        <nav class="flex items-center gap-2 text-sm font-semibold">

          <!-- Visible para TODOS -->
          <a href="/PULPERIA-CHEBS/index.php"
             class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
            Inicio
          </a>

          <a href="/PULPERIA-CHEBS/vistas/productos/listar.php"
             class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
            Productos
          </a>

          <a href="/PULPERIA-CHEBS/vistas/lotes/listar.php"
             class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
            Lotes
          </a>

          <a href="/PULPERIA-CHEBS/vistas/movimientos/historial.php"
             class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
            Historial Inv.
          </a>

          <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php"
             class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
            Historial Ventas
          </a>

          <!-- SOLO ADMIN -->
          <?php if ($rol === 'admin'): ?>
            <a href="/PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php"
               class="px-4 py-2 rounded-xl hover:bg-chebs-soft transition">
              Usuarios
            </a>
          <?php endif; ?>

          <!-- Caja (siempre visible) -->
          <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
             class="ml-2 px-5 py-2 rounded-xl bg-chebs-green text-white
                    hover:bg-chebs-greenDark transition shadow-soft">
            Caja
          </a>

          <!-- Salir -->
          <a href="/PULPERIA-CHEBS/controladores/logout.php"
             class="ml-1 px-5 py-2 rounded-xl border border-chebs-line
                    hover:bg-gray-50 transition">
            Salir
          </a>

        </nav>

      </div>
    </div>
  </div>
</header>

<main class="pt-[88px] px-6 pb-6">
  <div class="max-w-[1440px] mx-auto">

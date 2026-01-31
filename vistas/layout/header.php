<?php
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

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="/PULPERIA-CHEBS/public/img/logo.png">

  <!-- Tailwind (SE CARGA UNA SOLA VEZ AQUÍ) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            chebs: {
              green: '#4E7A2B',
              greenDark: '#35541D',
              black: '#111111',
              soft: '#E9F0E3',
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

<!-- NAVBAR -->
<header class="px-4 pt-4">
  <div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-2xl shadow-soft border border-chebs-line px-5 py-3
                flex items-center justify-between gap-4">

      <!-- Logo + nombre -->
      <div class="flex items-center gap-3">
        <img src="/PULPERIA-CHEBS/public/img/logo.png"
             class="h-10 w-10 rounded-xl object-contain bg-white"
             alt="Chebs">

        <div class="leading-tight">
          <div class="font-semibold text-sm">Pulpería Chebs</div>
          <div class="text-xs text-gray-500">
            <?= $rol === 'admin' ? 'Administrador' : 'Caja' ?>
          </div>
        </div>
      </div>

      <!-- Menú -->
      <nav class="flex items-center gap-2 text-sm font-semibold">

        <?php if ($rol === 'admin'): ?>
          <a href="/PULPERIA-CHEBS/index.php"
             class="px-3 py-2 rounded-xl hover:bg-chebs-soft transition">
            Inicio
          </a>

          <a href="/PULPERIA-CHEBS/vistas/productos/listar.php"
             class="px-3 py-2 rounded-xl hover:bg-chebs-soft transition">
            Productos
          </a>

          <a href="/PULPERIA-CHEBS/vistas/lotes/listar.php"
             class="px-3 py-2 rounded-xl hover:bg-chebs-soft transition">
            Lotes
          </a>

          <a href="/PULPERIA-CHEBS/vistas/movimientos/historial.php"
             class="px-3 py-2 rounded-xl hover:bg-chebs-soft transition">
            Historial Inv.
          </a>

          <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php"
             class="px-3 py-2 rounded-xl hover:bg-chebs-soft transition">
            Historial Ventas
          </a>
        <?php endif; ?>

        <!-- Caja / Ventas destacado -->
        <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
           class="px-4 py-2 rounded-xl bg-chebs-green text-white
                  hover:bg-chebs-greenDark transition">
          Caja
        </a>

        <!-- Salir -->
        <a href="/PULPERIA-CHEBS/controladores/logout.php"
           class="px-4 py-2 rounded-xl border border-chebs-line
                  hover:bg-gray-50 transition">
          Salir
        </a>

      </nav>
    </div>
  </div>
</header>

<!-- CONTENEDOR GENERAL DE CADA PÁGINA -->
<main class="px-4 py-6">
  <div class="max-w-6xl mx-auto">

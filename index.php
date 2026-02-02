<?php
require_once __DIR__ . "/config/auth.php";
require_role(['admin']);

if (session_status() === PHP_SESSION_NONE) session_start();
$nombre = $_SESSION['user']['nombre'] ?? ($_SESSION['user']['usuario'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pulpería Chebs | Admin</title>
</head>

<body class="bg-gray-50">

<?php
// ✅ usa tu header común (navbar chebs)
include __DIR__ . "/vistas/layout/header.php";
?>

<div class="max-w-6xl mx-auto px-4 py-8">

  <!-- Encabezado -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
      <h1 class="text-3xl font-black text-chebs-black">Panel de Administración</h1>
      <p class="text-sm text-gray-600 mt-1">
        Bienvenido, <span class="font-semibold"><?= htmlspecialchars($nombre) ?></span>.
        Administra tu tienda desde aquí.
      </p>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
         class="inline-flex items-center justify-center px-6 py-3 rounded-2xl bg-chebs-green text-white font-black
                hover:bg-chebs-greenDark transition shadow-soft">
        Ir Caja
      </a>

      <a href="/PULPERIA-CHEBS/controladores/logout.php"
         class="inline-flex items-center justify-center px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black
                hover:bg-chebs-soft transition">
        Cerrar sesión
      </a>
    </div>
  </div>

  <!-- Accesos rápidos -->
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

    <!-- Ventas -->
    <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
       class="group bg-white border border-chebs-line rounded-3xl shadow-soft p-6 hover:bg-chebs-soft/40 transition">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-black text-chebs-black group-hover:text-chebs-green transition">Caja</h3>
          <p class="text-sm text-gray-600 mt-1">Registrar ventas, abrir/cerrar turno y retiros.</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-chebs-soft border border-chebs-line flex items-center justify-center font-black text-chebs-green">
          $
        </div>
      </div>
      <div class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-chebs-green">
        Entrar <span class="transition group-hover:translate-x-1">→</span>
      </div>
    </a>

    <!-- Productos -->
    <a href="/PULPERIA-CHEBS/vistas/productos/listar.php"
       class="group bg-white border border-chebs-line rounded-3xl shadow-soft p-6 hover:bg-chebs-soft/40 transition">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-black text-chebs-black group-hover:text-chebs-green transition">Productos</h3>
          <p class="text-sm text-gray-600 mt-1">Crear, editar y administrar precios.</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-chebs-soft border border-chebs-line flex items-center justify-center font-black text-chebs-green">
          P
        </div>
      </div>
      <div class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-chebs-green">
        Gestionar <span class="transition group-hover:translate-x-1">→</span>
      </div>
    </a>

    <!-- Lotes -->
    <a href="/PULPERIA-CHEBS/vistas/lotes/listar.php"
       class="group bg-white border border-chebs-line rounded-3xl shadow-soft p-6 hover:bg-chebs-soft/40 transition">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-black text-chebs-black group-hover:text-chebs-green transition">Lotes</h3>
          <p class="text-sm text-gray-600 mt-1">Entradas, stock por lote y control.</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-chebs-soft border border-chebs-line flex items-center justify-center font-black text-chebs-green">
          L
        </div>
      </div>
      <div class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-chebs-green">
        Ver lotes <span class="transition group-hover:translate-x-1">→</span>
      </div>
    </a>

    <!-- Historial inventario -->
    <a href="/PULPERIA-CHEBS/vistas/movimientos/historial.php"
       class="group bg-white border border-chebs-line rounded-3xl shadow-soft p-6 hover:bg-chebs-soft/40 transition">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-black text-chebs-black group-hover:text-chebs-green transition">Inventario</h3>
          <p class="text-sm text-gray-600 mt-1">Historial de movimientos y stock.</p>
        </div>
        <div class="w-10 h-10 rounded-2xl bg-chebs-soft border border-chebs-line flex items-center justify-center font-black text-chebs-green">
          I
        </div>
      </div>
      <div class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-chebs-green">
        Ver historial <span class="transition group-hover:translate-x-1">→</span>
      </div>
    </a>

  </div>

  <!-- Segunda fila -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">

    <!-- Historial de ventas -->
    <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php"
       class="group bg-white border border-chebs-line rounded-3xl shadow-soft p-6 hover:bg-chebs-soft/40 transition">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h3 class="text-lg font-black text-chebs-black group-hover:text-chebs-green transition">Historial de Ventas</h3>
          <p class="text-sm text-gray-600 mt-1">Revisa ventas anteriores y reportes.</p>
        </div>
        <div class="text-chebs-green text-xl font-black transition group-hover:translate-x-1">→</div>
      </div>
    </a>

    <!-- Tips / info -->
    <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-6">
      <h3 class="text-lg font-black text-chebs-black">Tips rápidos</h3>
      <ul class="mt-3 space-y-2 text-sm text-gray-700">
        <li class="flex gap-2"><span class="font-black text-chebs-green">•</span> Ctrl + Enter en ventas para confirmar rápido.</li>
        <li class="flex gap-2"><span class="font-black text-chebs-green">•</span> Usa “Abrir turno” antes de vender.</li>
        <li class="flex gap-2"><span class="font-black text-chebs-green">•</span> Recuerda: stock considera solo lotes activos.</li>
      </ul>
    </div>

  </div>

</div>

<?php include __DIR__ . "/vistas/layout/footer.php"; ?>
</body>
</html>

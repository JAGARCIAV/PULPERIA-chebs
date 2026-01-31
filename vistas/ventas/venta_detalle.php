<?php
require_once "../../config/conexion.php";
require_once "../../modelos/venta_modelo.php";
include "../layout/header.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  echo "<div class='text-red-600 font-bold'>ID inválido</div>";
  include "../layout/footer.php";
  exit;
}

$detalle = obtenerDetalleVenta($conexion, $id);

// ✅ cargar todo a array para poder totalizar
$items = [];
$totalVenta = 0;

while ($d = $detalle->fetch_assoc()) {
  $items[] = $d;
  $totalVenta += (float)$d['subtotal'];
}
?>

<div class="max-w-5xl mx-auto px-4 py-8">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-black text-chebs-black">
        Detalle de Venta #<?= $id ?>
      </h1>
      <p class="text-sm text-gray-600">
        Productos incluidos en esta venta.
      </p>
    </div>

    <a href="historial.php"
       class="inline-flex items-center justify-center px-5 py-3 rounded-2xl border border-chebs-line bg-white
              hover:bg-chebs-soft font-bold transition whitespace-nowrap">
      ← Volver a historial
    </a>
  </div>

  <!-- Card tabla -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-100">
          <tr class="text-left text-chebs-black">
            <th class="px-4 py-3 font-black">Producto</th>
            <th class="px-4 py-3 font-black">Tipo</th>
            <th class="px-4 py-3 font-black">Cantidad</th>
            <th class="px-4 py-3 font-black">Precio</th>
            <th class="px-4 py-3 font-black">Subtotal</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-chebs-line">

        <?php if (empty($items)): ?>
          <tr>
            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
              No hay detalle para esta venta.
            </td>
          </tr>
        <?php endif; ?>

        <?php foreach ($items as $d): ?>
          <tr class="hover:bg-chebs-soft/40 transition">

            <!-- Producto -->
            <td class="px-4 py-3 font-semibold text-chebs-black">
              <?= htmlspecialchars($d['nombre']) ?>
            </td>

            <!-- Tipo -->
            <td class="px-4 py-3">
              <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black
                           bg-chebs-soft border border-chebs-line">
                <?= htmlspecialchars($d['tipo_venta']) ?>
              </span>
            </td>

            <!-- Cantidad -->
            <td class="px-4 py-3">
              <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black
                           bg-gray-100 border border-gray-200">
                <?= (int)$d['cantidad'] ?>
              </span>
            </td>

            <!-- Precio -->
            <td class="px-4 py-3">
              Bs <?= number_format((float)$d['precio_unitario'], 2) ?>
            </td>

            <!-- Subtotal -->
            <td class="px-4 py-3 font-black text-chebs-black">
              Bs <?= number_format((float)$d['subtotal'], 2) ?>
            </td>

          </tr>
        <?php endforeach; ?>

        </tbody>
      </table>
    </div>

  </div>

  <!-- Total -->
  <div class="mt-6 flex justify-end">
    <div class="bg-white border border-chebs-line rounded-3xl shadow-soft px-6 py-5 text-right">
      <div class="text-sm text-gray-600">Total de la venta</div>
      <div class="text-2xl font-black text-chebs-black">
        Bs <?= number_format($totalVenta, 2) ?>
      </div>
    </div>
  </div>

</div>

<?php include "../layout/footer.php"; ?>

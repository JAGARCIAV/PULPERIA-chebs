<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once "../../config/conexion.php";
require_once "../../modelos/lote_modelo.php";
include "../layout/header.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$lote = obtenerLotePorId($conexion, $id);
?>

<div class="max-w-3xl mx-auto px-4 py-10">

  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <!-- Header -->
    <div class="px-8 py-6 border-b border-chebs-line">
      <h1 class="text-2xl font-black text-chebs-black">Editar lote</h1>
      <p class="text-sm text-gray-600 mt-1">
        Ajusta la cantidad física y/o fecha de vencimiento.
      </p>
    </div>

    <?php if (!$lote) { ?>
      <div class="px-8 py-8">
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-700 font-semibold">
          ❌ Lote no encontrado.
        </div>

        <a href="listar.php"
           class="mt-5 inline-flex items-center justify-center px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
          ← Volver a lotes
        </a>
      </div>

    <?php } else { ?>

    <!-- Form -->
    <form action="../../controladores/lote_editar_controlador.php" method="POST" class="px-8 py-8 space-y-6">

      <input type="hidden" name="lote_id" value="<?= (int)$lote['id'] ?>">

      <!-- Info -->
      <div class="grid sm:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-chebs-line bg-chebs-soft/60 p-4">
          <div class="text-xs text-gray-500">Lote ID</div>
          <div class="text-lg font-black text-chebs-black">#<?= (int)$lote['id'] ?></div>
        </div>

        <div class="rounded-2xl border border-chebs-line bg-chebs-soft/60 p-4">
          <div class="text-xs text-gray-500">Producto ID</div>
          <div class="text-lg font-black text-chebs-black"><?= (int)$lote['producto_id'] ?></div>
        </div>
      </div>

      <!-- Fecha vencimiento -->
      <div>
        <label class="block text-sm font-bold mb-2 text-chebs-black">Fecha vencimiento</label>
        <input type="date"
               name="fecha_vencimiento"
               value="<?= htmlspecialchars($lote['fecha_vencimiento'] ?? '') ?>"
               class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                      focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
        <p class="text-xs text-gray-500 mt-1">Opcional si tu producto no vence.</p>
      </div>

      <!-- Cantidad actual + nueva -->
      <div class="grid sm:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-bold mb-2 text-chebs-black">Cantidad actual</label>
          <div class="w-full px-4 py-3 rounded-2xl border border-chebs-line bg-gray-50 font-black text-chebs-black">
            <?= (int)$lote['cantidad_unidades'] ?>
          </div>
        </div>

        <div>
          <label class="block text-sm font-bold mb-2 text-chebs-black">Nueva cantidad física</label>
          <input type="number"
                 name="cantidad_unidades"
                 value="<?= (int)$lote['cantidad_unidades'] ?>"
                 min="0"
                 class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                        focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
          <p class="text-xs text-gray-500 mt-1">Si cambias la cantidad, elige un motivo.</p>
        </div>
      </div>

      <!-- Motivo -->
      <div>
        <label class="block text-sm font-bold mb-2 text-chebs-black">Motivo ajuste (si cambia cantidad)</label>
        <select name="motivo"
                class="w-full px-4 py-3 rounded-2xl border border-chebs-line bg-white
                       focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
          <option value="">-- Seleccionar si hay cambio --</option>
          <option>Conteo físico</option>
          <option>Producto dañado</option>
          <option>Producto vencido</option>
          <option>Error de registro</option>
        </select>
      </div>

      <!-- Botones -->
      <div class="flex flex-col sm:flex-row gap-4 pt-2">
        <button type="submit"
                class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-2xl
                       bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft">
          💾 Guardar cambios
        </button>

        <a href="listar.php"
           class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-2xl
                  border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
          ← Volver a lotes
        </a>
      </div>

    </form>

    <?php } ?>

  </div>

</div>

<?php include "../layout/footer.php"; ?>

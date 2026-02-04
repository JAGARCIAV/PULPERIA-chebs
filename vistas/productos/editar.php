<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";
include "../layout/header.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { ?>
  <div class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white border border-red-200 rounded-3xl shadow-soft p-6 text-red-700 font-semibold">
      ❌ ID inválido
    </div>
    <a href="listar.php"
       class="mt-4 inline-flex px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
      ← Volver
    </a>
  </div>
<?php
  include "../layout/footer.php";
  exit;
}

$producto = obtenerProductoPorIds($conexion, $id);
if (!$producto) { ?>
  <div class="max-w-3xl mx-auto px-4 py-10">
    <div class="bg-white border border-red-200 rounded-3xl shadow-soft p-6 text-red-700 font-semibold">
      ❌ Producto no encontrado
    </div>
    <a href="listar.php"
       class="mt-4 inline-flex px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
      ← Volver
    </a>
  </div>
<?php
  include "../layout/footer.php";
  exit;
}
?>

<div class="max-w-3xl mx-auto px-4 py-10">

  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <!-- Header -->
    <div class="px-8 py-6 border-b border-chebs-line">
      <h1 class="text-2xl font-black text-chebs-black">Editar producto</h1>
      <p class="text-sm text-gray-600 mt-1">
        Actualiza nombre, precios y estado.
      </p>
    </div>

    <!-- Form -->
    <form action="../../controladores/producto_actualizar.php"
          method="POST"
          class="px-8 py-8 space-y-6">

      <input type="hidden" name="id" value="<?= (int)$producto['id'] ?>">

      <!-- Nombre -->
      <div>
        <label class="block text-sm font-bold mb-2 text-chebs-black">Nombre</label>
        <input type="text"
               name="nombre"
               value="<?= htmlspecialchars($producto['nombre']) ?>"
               required
               class="w-full px-4 py-3 rounded-2xl 
       bg-pink-50 border-2 border-pink-300 
       outline-none focus:ring-4 focus:ring-pink-200 
       focus:border-pink-500">
      </div>

      <!-- Precios -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold mb-2 text-chebs-black">Precio unidad</label>
          <input type="number"
                 step="0.01"
                 name="precio_unidad"
                 value="<?= htmlspecialchars($producto['precio_unidad']) ?>"
                 required
                    class="w-full px-4 py-3 rounded-2xl 
       bg-pink-50 border-2 border-pink-300 
       outline-none focus:ring-4 focus:ring-pink-200 
       focus:border-pink-500">
        </div>

        <div>
          <label class="block text-sm font-bold mb-2 text-chebs-black">Precio paquete</label>
          <input type="number"
                 step="0.01"
                 name="precio_paquete"
                 value="<?= htmlspecialchars($producto['precio_paquete']) ?>"
                 class="w-full px-4 py-3 rounded-2xl 
       bg-pink-50 border-2 border-pink-300 
       outline-none focus:ring-4 focus:ring-pink-200 
       focus:border-pink-500">
          <p class="mt-2 text-xs text-gray-500">
            Si no vendes por paquete, puedes dejarlo en 0 o vacío.
          </p>
        </div>
      </div>

      <!-- Estado -->
      <div>
        <label class="block text-sm font-bold mb-2 text-chebs-black">Estado</label>
        <select name="activo"
                class="w-full px-4 py-3 rounded-2xl 
       bg-pink-50 border-2 border-pink-300 
       outline-none focus:ring-4 focus:ring-pink-200 
       focus:border-pink-500">
          <option value="1" <?= !empty($producto['activo']) ? 'selected' : '' ?>>Activo</option>
          <option value="0" <?= empty($producto['activo']) ? 'selected' : '' ?>>Desactivado</option>
        </select>

        <div class="mt-3 rounded-2xl border border-chebs-line bg-chebs-soft/60 px-4 py-3 text-sm text-gray-700">
          <span class="font-bold">Tip:</span> Si desactivas un producto, no debería aparecer para vender.
        </div>
      </div>

      <!-- Botones -->
      <div class="flex flex-col sm:flex-row gap-3 pt-2">
        <button type="submit"
                class="flex-1 px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft">
          💾 Guardar cambios
        </button>

        <a href="listar.php"
           class="flex-1 px-6 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition text-center">
          ← Volver
        </a>
      </div>

    </form>

  </div>

</div>

<?php include "../layout/footer.php"; ?>

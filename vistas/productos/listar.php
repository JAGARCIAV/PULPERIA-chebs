<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";

include "../layout/header.php";

$productos = obtenerProductos($conexion);
?>

<div class="max-w-7xl mx-auto px-4 py-8">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-black text-chebs-black">Productos</h1>
      <p class="text-sm text-gray-600">Gestiona precios y revisa stock actual.</p>
    </div>

    <a href="crear.php"
       class="inline-flex items-center justify-center px-6 py-3 rounded-2xl bg-chebs-green text-white font-black
              hover:bg-chebs-greenDark transition shadow-soft">
      ➕ Nuevo producto
    </a>
  </div>

  <!-- Buscador -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-4 mb-6">
    <input id="buscador"
           type="text"
           placeholder="Buscar producto por nombre..."
           class="w-full px-4 py-3 rounded-2xl border border-chebs-line focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
  </div>

  <!-- Tabla -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-100">
          <tr class="text-left text-chebs-black">
            <th class="px-4 py-3 font-black">ID</th>
            <th class="px-4 py-3 font-black">Nombre</th>
            <th class="px-4 py-3 font-black">Precio Unidad</th>
            <th class="px-4 py-3 font-black">Precio Paquete</th>
            <th class="px-4 py-3 font-black">Stock</th>
            <th class="px-4 py-3 font-black text-right">Acciones</th>
          </tr>
        </thead>

        <tbody id="tabla_body">

        <?php while($p = $productos->fetch_assoc()) { 
          $stock = obtenerStockTotal($conexion, $p['id']);
        ?>
          <tr class="border-t border-chebs-line hover:bg-chebs-soft/40 transition">
            <td class="px-4 py-3"><?= (int)$p['id'] ?></td>

            <td class="px-4 py-3 font-semibold">
              <?= htmlspecialchars($p['nombre']) ?>
            </td>

            <td class="px-4 py-3">
              Bs <?= number_format((float)$p['precio_unidad'], 2) ?>
            </td>

            <td class="px-4 py-3">
              Bs <?= number_format((float)$p['precio_paquete'], 2) ?>
            </td>

            <td class="px-4 py-3">
              <?php if ($stock > 0): ?>
                <span class="inline-flex px-3 py-1 rounded-xl text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                  <?= (int)$stock ?> en stock
                </span>
              <?php else: ?>
                <span class="inline-flex px-3 py-1 rounded-xl text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                  Sin stock
                </span>
              <?php endif; ?>
            </td>

            <td class="px-4 py-3 text-right">
              <a href="editar.php?id=<?= (int)$p['id'] ?>"
                 class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-chebs-line bg-white
                        hover:bg-chebs-soft font-bold text-sm transition">
                ✏️ Editar
              </a>
            </td>
          </tr>
        <?php } ?>

        </tbody>
      </table>
    </div>

  </div>

</div>

<!-- Buscador JS -->
<script>
const buscador = document.getElementById("buscador");
const filas = document.querySelectorAll("#tabla_body tr");

buscador.addEventListener("input", () => {
  const q = buscador.value.toLowerCase();

  filas.forEach(tr => {
    const nombre = tr.children[1].innerText.toLowerCase();
    tr.style.display = nombre.includes(q) ? "" : "none";
  });
});
</script>

<?php include "../layout/footer.php"; ?>

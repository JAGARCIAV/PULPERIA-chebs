<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin','empleado']);

require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";

include "../layout/header.php";

$productos = obtenerProductos($conexion);

// ✅ base URL para construir src correcto
$base = '/PULPERIA-CHEBS/';
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
  <div class="bg-pink-50 border-2 border-pink-50 
       rounded-3xl shadow-soft p-4 mb-6 
       focus:outline-none focus:ring-4 focus:ring-pink-200 
       focus:border-pink-500">
    <input id="buscador"
           type="text"
           placeholder="Buscar producto por nombre..."
           class="w-full px-4 py-3 rounded-2xl 
       bg-pink-50 border-2 border-pink-300 
       outline-none focus:ring-4 focus:ring-pink-200 
       focus:border-pink-500">
  </div>


<!-- Tabla -->
<div class="bg-white border border-teal-100 rounded-3xl shadow-soft overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-teal-50">
        <tr class="text-left text-gray-800">
          <th class="px-4 py-3 font-black text-teal-700">ID</th>
          <th class="px-4 py-3 font-black text-teal-700">Imagen</th>
          <th class="px-4 py-3 font-black text-teal-700">Nombre</th>
          <th class="px-4 py-3 font-black text-teal-700">Precio Unidad</th>
          <th class="px-4 py-3 font-black text-teal-700">Precio Paquete</th>
          <th class="px-4 py-3 font-black text-teal-700">Stock</th>
          <th class="px-4 py-3 font-black text-right text-teal-700">Acciones</th>
        </tr>
      </thead>

      <tbody id="tabla_body">
      <?php while($p = $productos->fetch_assoc()) {
        $stock = obtenerStockTotal($conexion, $p['id']);

        // ✅ construimos URL de imagen
        $img_db = trim((string)($p['imagen'] ?? ''));
        $img_url = '';
        if ($img_db !== '') {
          $img_url = ($img_db[0] === '/')
            ? $img_db
            : $base . ltrim($img_db, './');
        }
      ?>
        <tr class="border-t border-teal-100 hover:bg-teal-50 transition">
          <td class="px-4 py-3"><?= (int)$p['id'] ?></td>

          <!-- ✅ IMAGEN -->
          <td class="px-4 py-3">
            <div class="w-14 h-14 rounded-2xl border border-teal-100 bg-white overflow-hidden flex items-center justify-center">
              <?php if($img_url !== ''){ ?>
                <img src="<?= htmlspecialchars($img_url) ?>"
                     alt=""
                     loading="lazy"
                     class="w-full h-full object-cover">
              <?php } else { ?>
                <span class="text-2xl">🧃</span>
              <?php } ?>
            </div>
          </td>

          <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($p['nombre']) ?></td>
          <td class="px-4 py-3">Bs <?= number_format((float)$p['precio_unidad'], 2) ?></td>
          <td class="px-4 py-3">Bs <?= number_format((float)$p['precio_paquete'], 2) ?></td>
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
               class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-teal-100 bg-white hover:bg-teal-50 font-bold text-sm transition">
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
    // ✅ Nombre ahora está en la columna 2 (porque agregamos Imagen)
    const nombre = tr.children[2].innerText.toLowerCase();
    tr.style.display = nombre.includes(q) ? "" : "none";
  });
});
</script>

<?php include "../layout/footer.php"; ?>

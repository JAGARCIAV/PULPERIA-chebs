<?php 
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once "../../config/conexion.php";
require_once "../../modelos/producto_modelo.php";

include "../layout/header.php";

$productos = obtenerProductosConStock($conexion);
// ✅ base URL para construir src correcto
$base = '/PULPERIA-CHEBS/';

/* ======================================================
   ✅ Detectar nombres duplicados (sin tocar DB)
   - contamos nombres normalizados (trim + lowercase)
   ====================================================== */
$rows = [];
$conteoNombre = [];

if ($productos) {
  while($r = $productos->fetch_assoc()){
    $rows[] = $r;
    $key = mb_strtolower(trim((string)($r['nombre'] ?? '')));
    if ($key !== '') $conteoNombre[$key] = ($conteoNombre[$key] ?? 0) + 1;
  }
}
?>

<div class="max-w-7xl mx-auto px-4 py-8">

  <!-- ✅ Mensaje OK -->
  <?php if(isset($_GET['ok']) && ($_GET['ok'] == '1')): ?>
    <div class="p-4 mb-6 rounded-2xl bg-green-100 border border-green-200 text-green-800 font-bold">
      ✅ <?= htmlspecialchars((string)($_GET['msg'] ?? 'Acción realizada')) ?>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-black text-chebs-black">Productos</h1>
      <p class="text-sm text-gray-600">
        Gestiona precios y revisa stock actual.
        <span class="ml-2 inline-flex px-3 py-1 rounded-xl text-xs font-black bg-yellow-100 text-yellow-800 border border-yellow-200">
          Tip: los nombres duplicados salen en amarillo
        </span>
      </p>
    </div>

    <a href="crear.php"
       class="inline-flex items-center justify-center px-6 py-3 rounded-2xl bg-chebs-green text-white font-black
              hover:bg-chebs-greenDark transition shadow-soft">
      ➕ Nuevo producto
    </a>
  </div>

  <!-- Buscador -->
  <div class="bg-pink-50 border-2 border-pink-50 
       rounded-3xl shadow-soft p-4 mb-6">
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
          
          <!-- 🔽 ORDENAR POR ID -->
          <th onclick="ordenarTabla(0, 'number')" 
              class="px-4 py-3 font-black text-teal-700 cursor-pointer select-none">
            ID ⬍
          </th>

          <th class="px-4 py-3 font-black text-teal-700">
            Imagen
          </th>

          <!-- 🔽 ORDENAR POR NOMBRE -->
          <th onclick="ordenarTabla(2, 'string')" 
              class="px-4 py-3 font-black text-teal-700 cursor-pointer select-none">
            Nombre ⬍
          </th>

          <th class="px-4 py-3 font-black text-teal-700">Precio Unidad</th>
          <th class="px-4 py-3 font-black text-teal-700">Precio Paquete</th>
          <th class="px-4 py-3 font-black text-teal-700">Stock</th>
          <th class="px-4 py-3 font-black text-right text-teal-700">Acciones</th>
        </tr>
      </thead>

      <tbody id="tabla_body">
      <?php foreach($rows as $p) {

$stock = (int)($p['stock_total'] ?? 0);
        $img_db = trim((string)($p['imagen'] ?? ''));
        $img_url = '';
        if ($img_db !== '') {
          $img_url = ($img_db[0] === '/')
            ? $img_db
            : $base . ltrim($img_db, './');
        }

        $nombreKey = mb_strtolower(trim((string)($p['nombre'] ?? '')));
        $esDuplicado = ($nombreKey !== '' && ($conteoNombre[$nombreKey] ?? 0) >= 2);

        // ✅ Si es duplicado: resaltar fila con amarillo suave
        $trClass = $esDuplicado
          ? "border-t border-yellow-200 bg-yellow-50 hover:bg-yellow-100 transition"
          : "border-t border-teal-100 hover:bg-teal-50 transition";
      ?>
        <tr class="<?= $trClass ?>">
          <td class="px-4 py-3"><?= (int)$p['id'] ?></td>

          <!-- Imagen -->
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

          <td class="px-4 py-3 font-semibold">
            <div class="flex items-center gap-2">
              <span><?= htmlspecialchars($p['nombre']) ?></span>

              <?php if($esDuplicado): ?>
                <span class="inline-flex px-3 py-1 rounded-xl text-xs font-black bg-yellow-200 text-yellow-900 border border-yellow-300">
                  Duplicado
                </span>
              <?php endif; ?>
            </div>
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
            <div class="inline-flex gap-2">
              <a href="editar.php?id=<?= (int)$p['id'] ?>"
                 class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-teal-100 bg-white hover:bg-teal-50 font-bold text-sm transition">
                ✏️ Editar
              </a>

              <a href="eliminar.php?id=<?= (int)$p['id'] ?>"
                 class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700 font-black text-sm transition shadow-soft">
                🗑 Eliminar
              </a>
            </div>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<!-- 🔍 Buscador optimizado (debounce) -->
<script>
const buscador = document.getElementById("buscador");
let t = null;

buscador.addEventListener("input", () => {
  clearTimeout(t);

  t = setTimeout(() => {
    const q = buscador.value.trim().toLowerCase();
    const filas = document.querySelectorAll("#tabla_body tr");

    for (const tr of filas) {
      const nombre = (tr.children[2].textContent || '').toLowerCase();
      tr.style.display = nombre.includes(q) ? "" : "none";
    }
  }, 150); // espera 150ms antes de ejecutar
});
</script>

<!-- 🔽 ORDENADOR -->
<script>
let ordenActual = {};

function ordenarTabla(columna, tipo) {
  const tbody = document.getElementById("tabla_body");
  const filas = Array.from(tbody.querySelectorAll("tr"));

  if (!ordenActual[columna]) {
    ordenActual[columna] = "desc";
  } else {
    ordenActual[columna] = ordenActual[columna] === "asc" ? "desc" : "asc";
  }

  filas.sort((a, b) => {
    let A = a.children[columna].innerText.trim();
    let B = b.children[columna].innerText.trim();

    if (tipo === "number") {
      A = parseFloat(A);
      B = parseFloat(B);
    } else {
      A = A.toLowerCase();
      B = B.toLowerCase();
    }

    if (ordenActual[columna] === "asc") {
      return A > B ? 1 : -1;
    } else {
      return A < B ? 1 : -1;
    }
  });

  tbody.innerHTML = "";
  filas.forEach(fila => tbody.appendChild(fila));
}
</script>

<?php include "../layout/footer.php"; ?>
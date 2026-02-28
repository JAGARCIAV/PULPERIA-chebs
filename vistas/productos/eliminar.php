<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../../config/conexion.php";

include "../layout/header.php";

if (session_status() === PHP_SESSION_NONE) session_start();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header("Location: /PULPERIA-CHEBS/vistas/productos/listar.php?err=id");
  exit;
}

$stmt = $conexion->prepare("SELECT id, nombre, imagen FROM productos WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$p) {
  header("Location: /PULPERIA-CHEBS/vistas/productos/listar.php?err=noexiste");
  exit;
}

// ✅ base URL como usas en listar.php
$base = '/PULPERIA-CHEBS/';

$img_db = trim((string)($p['imagen'] ?? ''));
$img_url = '';
if ($img_db !== '') {
  $img_url = ($img_db[0] === '/')
    ? $img_db
    : $base . ltrim($img_db, './');
}
?>

<div class="max-w-3xl mx-auto px-4 py-8">
  <div class="rounded-3xl border bg-white shadow-soft overflow-hidden">

    <div class="px-6 py-5 border-b border-chebs-line bg-chebs-soft/30">
      <h1 class="text-2xl md:text-3xl font-black text-chebs-black">Eliminar producto</h1>
      <p class="text-sm text-gray-600 mt-1">
        Se intentará eliminar el producto y sus lotes.
        Si no se puede por ventas/relaciones, se desactivará para no romper el sistema.
      </p>
    </div>

    <div class="p-6">
      <?php if(isset($_GET['err']) && $_GET['err'] !== ''): ?>
        <div class="p-4 mb-4 rounded-2xl bg-red-100 border border-red-300 text-red-800 font-bold">
          ⚠ <?= htmlspecialchars((string)$_GET['err']) ?>
        </div>
      <?php endif; ?>

      <div class="flex items-start gap-4">
        <div class="w-20 h-20 rounded-2xl border border-chebs-line bg-white overflow-hidden flex items-center justify-center">
          <?php if($img_url !== ''){ ?>
            <img src="<?= htmlspecialchars($img_url) ?>" alt="" class="w-full h-full object-cover">
          <?php } else { ?>
            <span class="text-3xl">🧃</span>
          <?php } ?>
        </div>

        <div class="flex-1">
          <div class="text-sm text-gray-600">Producto</div>
          <div class="text-xl font-black text-chebs-black">
            #<?= (int)$p['id'] ?> — <?= htmlspecialchars($p['nombre']) ?>
          </div>

          <div class="mt-4 rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-800">
            <b>Advertencia:</b> esto intentará eliminar también los <b>lotes</b> del producto.
            <br>
            Si ya fue usado en ventas, el sistema hará <b>desactivación</b> automática.
          </div>
        </div>
      </div>

      <form class="mt-6" method="POST" action="/PULPERIA-CHEBS/controladores/producto_eliminar.php">
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

        <label class="block text-sm font-bold mb-2 text-chebs-black">
          Escribe <span class="font-black">ELIMINAR</span> para confirmar:
        </label>

        <input
          name="confirm"
          required
          autocomplete="off"
          placeholder="ELIMINAR"
          class="w-full px-4 py-3 rounded-2xl bg-pink-50 border-2 border-pink-300
                 outline-none focus:ring-4 focus:ring-pink-200 focus:border-pink-500"
        >

        <div class="flex flex-col sm:flex-row gap-3 mt-6">
          <a href="/PULPERIA-CHEBS/vistas/productos/listar.php"
             class="flex-1 inline-flex items-center justify-center px-5 py-3 rounded-2xl
                    border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
            Cancelar
          </a>

          <button type="submit"
                  class="flex-1 inline-flex items-center justify-center px-5 py-3 rounded-2xl
                         bg-red-600 text-white font-black hover:bg-red-700 transition shadow-soft">
            🗑 Eliminar ahora
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<?php include "../layout/footer.php"; ?>
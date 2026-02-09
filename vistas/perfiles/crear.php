<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../../config/conexion.php";
include __DIR__ . "/../layout/header.php";

$err = "";
$ok  = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre  = trim($_POST['nombre'] ?? '');
  $usuario = trim($_POST['usuario'] ?? '');
  $rol     = $_POST['rol'] ?? 'empleado';
  $activo  = isset($_POST['activo']) ? 1 : 0;
  $pass1   = $_POST['password'] ?? '';
  $pass2   = $_POST['password2'] ?? '';

  if ($nombre === '' || $usuario === '') {
    $err = "Nombre y usuario son obligatorios.";
  } elseif (!in_array($rol, ['admin','empleado'], true)) {
    $err = "Rol inválido.";
  } elseif (strlen($usuario) < 3) {
    $err = "El usuario debe tener al menos 3 caracteres.";
  } elseif ($pass1 === '' || $pass2 === '') {
    $err = "La contraseña es obligatoria.";
  } elseif ($pass1 !== $pass2) {
    $err = "Las contraseñas no coinciden.";
  } elseif (strlen($pass1) < 4) {
    $err = "La contraseña debe tener al menos 4 caracteres.";
  } else {
    // ✅ usuario único
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario=? LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existe) {
      $err = "Ese usuario ya existe. Usa otro.";
    } else {
      $hash = password_hash($pass1, PASSWORD_DEFAULT);

      $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, password_hash, rol, activo) VALUES (?,?,?,?,?)");
      $stmt->bind_param("ssssi", $nombre, $usuario, $hash, $rol, $activo);

      if ($stmt->execute()) {
        $ok = "✅ Usuario creado correctamente.";
      } else {
        $err = "❌ Error al crear usuario: " . $conexion->error;
      }
      $stmt->close();
    }
  }
}
?>

<div class="max-w-3xl mx-auto px-4 py-8">

  <div class="flex items-end justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-black text-chebs-black">Crear usuario</h1>
      <p class="text-sm text-gray-600 mt-1">Solo Admin puede crear y administrar usuarios.</p>
    </div>

    <a href="/PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php"
       class="px-5 py-3 rounded-2xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
      ← Volver
    </a>
  </div>

  <?php if($err): ?>
    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-black text-red-700">
      <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <?php if($ok): ?>
    <div class="mb-4 rounded-2xl border border-chebs-line bg-chebs-soft/60 px-4 py-3 text-sm font-black text-chebs-green">
      <?= htmlspecialchars($ok) ?>
    </div>
  <?php endif; ?>

  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">
    <div class="px-6 py-5 border-b border-chebs-line bg-chebs-soft/60">
      <h2 class="text-lg font-black text-chebs-black">Datos del usuario</h2>
    </div>

    <form method="POST" class="px-6 py-6 space-y-5">

      <div>
        <label class="block text-sm font-black text-pink-600 mb-2">Nombre</label>
        <input type="text" name="nombre" required
               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
               class="w-full h-[52px] rounded-2xl bg-white border-2 border-pink-300 px-4 text-gray-800 outline-none
                      focus:ring-4 focus:ring-pink-200 focus:border-pink-500 font-semibold">
      </div>

      <div>
        <label class="block text-sm font-black text-pink-600 mb-2">Usuario (login)</label>
        <input type="text" name="usuario" required
               value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
               class="w-full h-[52px] rounded-2xl bg-white border-2 border-pink-300 px-4 text-gray-800 outline-none
                      focus:ring-4 focus:ring-pink-200 focus:border-pink-500 font-semibold">
        <div class="text-xs text-gray-500 mt-2">Ej: personal_1 (sin espacios).</div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-black text-pink-600 mb-2">Rol</label>
          <select name="rol"
                  class="w-full h-[52px] rounded-2xl bg-white border-2 border-pink-300 px-4 text-gray-800 outline-none
                         focus:ring-4 focus:ring-pink-200 focus:border-pink-500 font-semibold">
            <option value="empleado" <?= (($_POST['rol'] ?? 'empleado')==='empleado')?'selected':'' ?>>Personal</option>
            <option value="admin" <?= (($_POST['rol'] ?? '')==='admin')?'selected':'' ?>>Admin</option>
          </select>
        </div>

        <div class="flex items-center gap-3 pt-8">
          <input type="checkbox" id="activo" name="activo" value="1" <?= isset($_POST['activo']) ? 'checked' : 'checked' ?>
                 class="w-5 h-5 accent-pink-500">
          <label for="activo" class="text-sm font-black text-chebs-black">Activo</label>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-black text-pink-600 mb-2">Contraseña</label>
          <input type="password" name="password" required
                 class="w-full h-[52px] rounded-2xl bg-pink-50 border-2 border-pink-300 px-4 text-gray-800 outline-none
                        focus:ring-4 focus:ring-pink-200 focus:border-pink-500 font-semibold">
        </div>
        <div>
          <label class="block text-sm font-black text-pink-600 mb-2">Repetir contraseña</label>
          <input type="password" name="password2" required
                 class="w-full h-[52px] rounded-2xl bg-pink-50 border-2 border-pink-300 px-4 text-gray-800 outline-none
                        focus:ring-4 focus:ring-pink-200 focus:border-pink-500 font-semibold">
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 sm:justify-end pt-2">
        <button type="submit"
                class="px-7 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft">
          Crear usuario
        </button>
      </div>

    </form>
  </div>
</div>

<?php include __DIR__ . "/../layout/footer.php"; ?>

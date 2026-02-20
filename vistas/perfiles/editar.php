<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../../config/conexion.php";

function nombreRol($rol){
  return match($rol){
    'admin' => 'ADMIN',
    'empleado' => 'PERSONAL',
    default => strtoupper($rol),
  };
}

$err = "";

/* =========================
   1) Cargar usuario por ID
   ========================= */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  header("Location: /PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php?err=id");
  exit;
}

$stmt = $conexion->prepare("SELECT id, nombre, usuario, rol, activo, creado_en FROM usuarios WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u) {
  header("Location: /PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php?err=noexiste");
  exit;
}

/* =========================
   2) Guardar cambios (POST)
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre  = trim($_POST['nombre'] ?? '');
  $usuario = trim($_POST['usuario'] ?? '');
  $rol     = $_POST['rol'] ?? 'empleado';
  $activo  = isset($_POST['activo']) ? 1 : 0;

  // contraseña opcional
  $pass1   = $_POST['password'] ?? '';
  $pass2   = $_POST['password2'] ?? '';

  if ($nombre === '' || $usuario === '') {
    $err = "Nombre y usuario son obligatorios.";
  } elseif (!in_array($rol, ['admin','empleado'], true)) {
    $err = "Rol inválido.";
  } elseif (strlen($usuario) < 3) {
    $err = "El usuario debe tener al menos 3 caracteres.";
  } else {

    // ✅ usuario único (excepto el mismo)
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario=? AND id<>? LIMIT 1");
    $stmt->bind_param("si", $usuario, $id);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existe) {
      $err = "Ese usuario ya existe. Usa otro.";
    } else {

      // ✅ Validar contraseña SOLO si escribió algo
      $cambiarPass = false;
      if ($pass1 !== '' || $pass2 !== '') {
        if ($pass1 === '' || $pass2 === '') {
          $err = "Si vas a cambiar contraseña, llena ambos campos.";
        } elseif ($pass1 !== $pass2) {
          $err = "Las contraseñas no coinciden.";
        } elseif (strlen($pass1) < 4) {
          $err = "La contraseña debe tener al menos 4 caracteres.";
        } else {
          $cambiarPass = true;
        }
      }

      if ($err === "") {

        if ($cambiarPass) {
          $hash = password_hash($pass1, PASSWORD_DEFAULT);

          $stmt = $conexion->prepare("
            UPDATE usuarios
            SET nombre=?, usuario=?, rol=?, activo=?, password_hash=?
            WHERE id=?
          ");
          $stmt->bind_param("sssisi", $nombre, $usuario, $rol, $activo, $hash, $id);

        } else {
          $stmt = $conexion->prepare("
            UPDATE usuarios
            SET nombre=?, usuario=?, rol=?, activo=?
            WHERE id=?
          ");
          $stmt->bind_param("sssii", $nombre, $usuario, $rol, $activo, $id);
        }

        if ($stmt->execute()) {
          $stmt->close();

          header("Location: /PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php?ok=edit");
          exit;

        } else {
          $err = "❌ Error al actualizar: " . $conexion->error;
        }

        $stmt->close();
      }
    }
  }

  // si hubo error, refrescamos $u con lo que escribió (para que no se pierda)
  $u['nombre'] = $nombre;
  $u['usuario'] = $usuario;
  $u['rol'] = $rol;
  $u['activo'] = $activo;
}

/* =========================
   3) Recién ahora imprimimos HTML
   ========================= */
include __DIR__ . "/../layout/header.php";
?>

<div class="max-w-3xl mx-auto px-4 py-8">

  <div class="flex items-end justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-black text-chebs-black">Editar usuario</h1>
      <p class="text-sm text-gray-600 mt-1">
        Editando: <span class="font-black"><?= htmlspecialchars($u['nombre']) ?></span>
        <span class="text-gray-400">(@<?= htmlspecialchars($u['usuario']) ?>)</span>
      </p>
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

  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden">
    <div class="px-6 py-5 border-b border-chebs-line bg-chebs-soft/60 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-black text-chebs-black">Datos del usuario</h2>
        <div class="text-xs text-gray-500 mt-1">
          ID #<?= (int)$u['id'] ?> · Creado: <?= htmlspecialchars($u['creado_en'] ?? '-') ?>
        </div>
      </div>
      <span class="px-3 py-1 rounded-full text-xs font-black <?= ($u['rol']==='admin') ? 'bg-pink-100 text-pink-700 border border-pink-200' : 'bg-blue-50 text-blue-700 border border-blue-100' ?>">
      <?= nombreRol($u['rol']) ?>
      </span>
    </div>

    <form method="POST" class="px-6 py-6 space-y-5">

      <div>
        <label class="block text-sm font-black text-pink-600 mb-2">Nombre</label>
        <input type="text" name="nombre" required
               value="<?= htmlspecialchars($u['nombre'] ?? '') ?>"
               class="w-full h-[52px] rounded-2xl bg-white border-2 border-pink-300 px-4 text-gray-800 outline-none
                      focus:ring-4 focus:ring-pink-200 focus:border-pink-500 font-semibold">
      </div>

      <div>
        <label class="block text-sm font-black text-pink-600 mb-2">Usuario (login)</label>
        <input type="text" name="usuario" required
               value="<?= htmlspecialchars($u['usuario'] ?? '') ?>"
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
            <option value="empleado" <?= (($u['rol'] ?? 'empleado')==='empleado')?'selected':'' ?>>Personal</option>
            <option value="admin" <?= (($u['rol'] ?? '')==='admin')?'selected':'' ?>>Admin</option>
          </select>
        </div>

        <div class="flex items-center gap-3 pt-8">
          <input type="checkbox" id="activo" name="activo" value="1" <?= ((int)($u['activo'] ?? 0)===1) ? 'checked' : '' ?>
                 class="w-5 h-5 accent-pink-500">
          <label for="activo" class="text-sm font-black text-chebs-black">Activo</label>
        </div>
      </div>

      <div class="rounded-2xl border border-chebs-line bg-pink-50/60 p-4">
        <div class="font-black text-chebs-black">Cambiar contraseña (opcional)</div>
        <div class="text-xs text-gray-600 mt-1">
          Si no quieres cambiarla, deja ambos campos vacíos.
        </div>

        <!-- ✅ CAMPOS CON OJITO -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div class="relative">
            <label class="block text-sm font-black text-pink-600 mb-2">Nueva contraseña</label>
            <input type="password" name="password" id="password"
                   class="w-full h-[52px] rounded-2xl bg-white border-2 border-pink-300 px-4 pr-12 text-gray-800 outline-none
                          focus:ring-4 focus:ring-pink-200 focus:border-pink-500 font-semibold">

            <button type="button" onclick="togglePassword('password')"
                    class="absolute right-4 top-[42px] text-gray-500 hover:text-pink-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.5 4-5 7-9 7s-7.5-3-9-7c1.5-4 5-7 9-7s7.5 3 9 7z"/>
              </svg>
            </button>
          </div>

          <div class="relative">
            <label class="block text-sm font-black text-pink-600 mb-2">Repetir nueva contraseña</label>
            <input type="password" name="password2" id="password2"
                   class="w-full h-[52px] rounded-2xl bg-white border-2 border-pink-300 px-4 pr-12 text-gray-800 outline-none
                          focus:ring-4 focus:ring-pink-200 focus:border-pink-500 font-semibold">

            <button type="button" onclick="togglePassword('password2')"
                    class="absolute right-4 top-[42px] text-gray-500 hover:text-pink-600">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.5 4-5 7-9 7s-7.5-3-9-7c1.5-4 5-7 9-7s7.5 3 9 7z"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 sm:justify-end pt-2">
        <button type="submit"
                class="px-7 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft">
          Guardar cambios
        </button>
      </div>

    </form>
  </div>
</div>

<!-- ✅ SCRIPT: antes de incluir el footer -->
<script>
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  input.type = (input.type === "password") ? "text" : "password";
}
</script>

<?php include __DIR__ . "/../layout/footer.php"; ?>

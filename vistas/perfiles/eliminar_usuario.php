<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

require_once __DIR__ . "/../../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php");
  exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  header("Location: /PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php?err=idelim");
  exit;
}

// ✅ Seguridad: evitar borrar al admin logueado
$yo = (int)($_SESSION['user']['id'] ?? 0);
if ($id === $yo) {
  header("Location: /PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php?err=noborrarself");
  exit;
}

// ✅ OJO: como tienes turnos/ventas ligados al usuario, borrar físico puede fallar.
// Mejor: desactivar (activo=0). Eso es "eliminar" sin romper integridad.
$stmt = $conexion->prepare("UPDATE usuarios SET activo=0 WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  $stmt->close();
  header("Location: /PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php?ok=elim");
  exit;
}

$stmt->close();
header("Location: /PULPERIA-CHEBS/vistas/perfiles/perfiles_usuarios.php?err=elimfail");
exit;

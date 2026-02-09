<?php 
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']); // ✅ SOLO ADMIN

require_once __DIR__ . "/../../config/conexion.php";
include __DIR__ . "/../layout/header.php";

function nombreRol($rol){
  return match($rol){
    'admin' => 'ADMIN',
    'empleado' => 'PERSONAL',
    default => strtoupper($rol),
  };
}


date_default_timezone_set('America/La_Paz');

$hoy = date('Y-m-d');
$mesInicio = date('Y-m-01');
$mesFin    = date('Y-m-t');

function money($n){ return number_format((float)$n, 2); }

/* =========================
   1) USUARIOS ACTIVOS (EMPLEADOS + ADMIN)
   ========================= */
$usuarios = [];
$qU = $conexion->query("SELECT id, nombre, usuario, rol, activo FROM usuarios WHERE activo=1 ORDER BY rol DESC, nombre ASC");
while($u = $qU->fetch_assoc()){
  $usuarios[(int)$u['id']] = [
    'id' => (int)$u['id'],
    'nombre' => $u['nombre'],
    'usuario' => $u['usuario'],
    'rol' => $u['rol'],
    'vendido_hoy' => 0,
    'vendido_mes' => 0,
    'ganancia_hoy' => 0,
    'ganancia_mes' => 0,
    'turnos_hoy' => [],
  ];
}

if (empty($usuarios)) {
  echo '<div class="max-w-6xl mx-auto px-4 py-10">
          <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-6">
            <h1 class="text-2xl font-black text-chebs-black">Perfiles</h1>
            <p class="text-gray-600 mt-2">No hay usuarios activos para mostrar.</p>
          </div>
        </div>';
  include __DIR__ . "/../layout/footer.php";
  exit;
}

$ids = array_keys($usuarios);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

/* =========================
   2) VENDIDO (HOY / MES) por usuario (admin+empleado)
   ========================= */
$sqlVendido = "
SELECT
  t.usuario_id,
  SUM(CASE WHEN DATE(v.fecha)=? THEN v.total ELSE 0 END) AS vendido_hoy,
  SUM(CASE WHEN DATE(v.fecha) BETWEEN ? AND ? THEN v.total ELSE 0 END) AS vendido_mes
FROM turnos t
LEFT JOIN ventas v ON v.turno_id = t.id
WHERE t.usuario_id IN ($placeholders)
GROUP BY t.usuario_id
";
$stmt = $conexion->prepare($sqlVendido);
$params = array_merge([$hoy, $mesInicio, $mesFin], $ids);
$stmt->bind_param("sss".$types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while($r = $res->fetch_assoc()){
  $uid = (int)$r['usuario_id'];
  if(isset($usuarios[$uid])){
    $usuarios[$uid]['vendido_hoy'] = (float)$r['vendido_hoy'];
    $usuarios[$uid]['vendido_mes'] = (float)$r['vendido_mes'];
  }
}
$stmt->close();

/* =========================
   3) GANANCIA (HOY / MES) por usuario (admin+empleado)
   ========================= */
$sqlGanancia = "
SELECT
  t.usuario_id,

  SUM(
    CASE
      WHEN DATE(v.fecha)=?
      THEN
        CASE
          WHEN d.tipo_venta='unidad'
            AND p.costo_unidad IS NOT NULL AND p.costo_unidad > 0
            THEN (d.precio_unitario - p.costo_unidad) * d.cantidad

          WHEN d.tipo_venta='paquete'
            AND pp.id IS NOT NULL
            THEN (
              d.precio_unitario
              - COALESCE(NULLIF(pp.costo,0), (p.costo_unidad * pp.unidades))
            ) * d.cantidad

          ELSE 0
        END
      ELSE 0
    END
  ) AS ganancia_hoy,

  SUM(
    CASE
      WHEN DATE(v.fecha) BETWEEN ? AND ?
      THEN
        CASE
          WHEN d.tipo_venta='unidad'
            AND p.costo_unidad IS NOT NULL AND p.costo_unidad > 0
            THEN (d.precio_unitario - p.costo_unidad) * d.cantidad

          WHEN d.tipo_venta='paquete'
            AND pp.id IS NOT NULL
            THEN (
              d.precio_unitario
              - COALESCE(NULLIF(pp.costo,0), (p.costo_unidad * pp.unidades))
            ) * d.cantidad

          ELSE 0
        END
      ELSE 0
    END
  ) AS ganancia_mes

FROM turnos t
JOIN ventas v ON v.turno_id = t.id
JOIN detalle_venta d ON d.venta_id = v.id
JOIN productos p ON p.id = d.producto_id
LEFT JOIN producto_presentaciones pp ON pp.id = d.presentacion_id
WHERE t.usuario_id IN ($placeholders)
GROUP BY t.usuario_id
";
$stmt = $conexion->prepare($sqlGanancia);
$params = array_merge([$hoy, $mesInicio, $mesFin], $ids);
$stmt->bind_param("sss".$types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while($r = $res->fetch_assoc()){
  $uid = (int)$r['usuario_id'];
  if(isset($usuarios[$uid])){
    $usuarios[$uid]['ganancia_hoy'] = (float)$r['ganancia_hoy'];
    $usuarios[$uid]['ganancia_mes'] = (float)$r['ganancia_mes'];
  }
}
$stmt->close();

/* =========================
   4) TURNOS DE HOY (por usuario)
   ========================= */
$sqlTurnosHoy = "
SELECT id, usuario_id, abierto_en, cerrado_en, estado, efectivo_inicial_contado, efectivo_esperado, total_ventas, total_retiros, diferencia
FROM turnos
WHERE usuario_id IN ($placeholders) AND fecha = ?
ORDER BY id DESC
";
$stmt = $conexion->prepare($sqlTurnosHoy);
$params = array_merge($ids, [$hoy]);
$stmt->bind_param($types."s", ...$params);
$stmt->execute();
$res = $stmt->get_result();
while($t = $res->fetch_assoc()){
  $uid = (int)$t['usuario_id'];
  if(isset($usuarios[$uid])){
    $usuarios[$uid]['turnos_hoy'][] = $t;
  }
}
$stmt->close();

/* =========================
   5) LISTA COMPLETA DE USUARIOS (para tabla)
   ========================= */
$listaUsuarios = [];
$qAll = $conexion->query("SELECT id, nombre, usuario, rol, activo, creado_en FROM usuarios ORDER BY rol DESC, activo DESC, nombre ASC");
while($u = $qAll->fetch_assoc()){
  $listaUsuarios[] = $u;
}
?>

<style>
  .card-pro{
    background: rgba(255,255,255,.98);
    border: 2px solid #E5E7EB;
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,.07);
    transition: .18s ease;
  }
  .card-pro:hover{
    transform: translateY(-3px);
    box-shadow: 0 18px 55px rgba(0,0,0,.12);
    border-color: rgba(236,72,153,.45);
  }
  .badge-ok{ background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
  .badge-warn{ background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
  .badge-admin{ background:#ffe4f1; color:#9d174d; border:1px solid #fbcfe8; }
  .badge-emp{ background:#e0f2fe; color:#075985; border:1px solid #bae6fd; }
</style>

<div class="max-w-7xl mx-auto px-4 py-8">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-3xl font-black text-chebs-black">Perfiles de usuarios</h1>
      <p class="text-sm text-gray-600 mt-1">
        HOY se reinicia solo cada día. MENSUAL se reinicia solo al cambiar de mes.
      </p>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <a href="/PULPERIA-CHEBS/vistas/perfiles/crear.php"
         class="px-6 py-3 rounded-2xl bg-chebs-green text-white font-black hover:bg-chebs-greenDark transition shadow-soft text-center">
        + Crear usuario
      </a>

      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl border border-chebs-line bg-white font-black">
        <span class="text-gray-500 text-xs">Fecha:</span>
        <span class="text-chebs-black"><?= htmlspecialchars($hoy) ?></span>
        <span class="text-gray-300">·</span>
        <span class="text-gray-500 text-xs">Mes:</span>
        <span class="text-chebs-black"><?= htmlspecialchars(date('Y-m')) ?></span>
      </div>
    </div>
  </div>

  <!-- ✅ TABLA USUARIOS CREADOS -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-chebs-line bg-chebs-soft/60 flex items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-black text-chebs-black">Usuarios creados</h2>
        <p class="text-xs text-gray-600">Admin puede crear, editar, activar o desactivar usuarios.</p>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left text-chebs-black">
            <th class="px-5 py-3 font-black">Nombre</th>
            <th class="px-5 py-3 font-black">Usuario</th>
            <th class="px-5 py-3 font-black">Rol</th>
            <th class="px-5 py-3 font-black">Estado</th>
            <th class="px-5 py-3 font-black">Creado</th>
            <th class="px-5 py-3 font-black text-right">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-chebs-line">
          <?php foreach($listaUsuarios as $u): ?>
            <tr class="hover:bg-pink-50/40 transition">
              <td class="px-5 py-3 font-semibold"><?= htmlspecialchars($u['nombre']) ?></td>
              <td class="px-5 py-3 text-gray-700">@<?= htmlspecialchars($u['usuario']) ?></td>
              <td class="px-5 py-3">
                <span class="px-3 py-1 rounded-full text-xs font-black <?= ($u['rol']==='admin')?'badge-admin':'badge-emp' ?>">
                <?= nombreRol($u['rol']) ?>
                </span>
              </td>
              <td class="px-5 py-3">
                <?php if((int)$u['activo']===1): ?>
                  <span class="px-3 py-1 rounded-full text-xs font-black badge-ok">ACTIVO</span>
                <?php else: ?>
                  <span class="px-3 py-1 rounded-full text-xs font-black badge-warn">INACTIVO</span>
                <?php endif; ?>
              </td>
              <td class="px-5 py-3 text-xs text-gray-500"><?= htmlspecialchars($u['creado_en']) ?></td>
              <td class="px-5 py-3 text-right">
                <a href="/PULPERIA-CHEBS/vistas/perfiles/editar.php?id=<?= (int)$u['id'] ?>"
                   class="inline-flex px-4 py-2 rounded-xl border border-chebs-line bg-white font-black hover:bg-chebs-soft transition">
                  Editar
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if(empty($listaUsuarios)): ?>
            <tr><td colspan="6" class="px-5 py-6 text-gray-600">No hay usuarios.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ✅ CARDS POR USUARIO (ADMIN + EMPLEADOS) -->
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach($usuarios as $e): ?>
      <?php
        $turnoAbierto = false;
        foreach($e['turnos_hoy'] as $tt){ if(($tt['estado'] ?? '') === 'abierto'){ $turnoAbierto=true; break; } }
        $hoyV = (float)$e['vendido_hoy'];
        $mesV = (float)$e['vendido_mes'];
        $hoyG = (float)$e['ganancia_hoy'];
        $mesG = (float)$e['ganancia_mes'];

        $badgeRol = ($e['rol']==='admin') ? 'badge-admin' : 'badge-emp';
        $rolTxt = nombreRol($e['rol']);

?>

      <!-- ✅ CARD -->
      <div class="card-pro overflow-hidden relative flex flex-col min-h-[520px]">

        <!-- Header card -->
        <div class="px-5 py-4 border-b border-chebs-line bg-chebs-soft/60 flex items-center justify-between gap-3">
          <div>
            <div class="flex items-center gap-2">
              <div class="text-xs font-black text-gray-500">USUARIO</div>
              <span class="px-3 py-1 rounded-full text-xs font-black <?= $badgeRol ?>"><?= $rolTxt ?></span>
            </div>
            <div class="text-xl font-black text-chebs-black"><?= htmlspecialchars($e['nombre']) ?></div>
            <div class="text-xs text-gray-500">@<?= htmlspecialchars($e['usuario']) ?></div>
          </div>

          <span class="px-3 py-1 rounded-full text-xs font-black <?= $turnoAbierto ? 'badge-ok' : 'badge-warn' ?>">
            <?= $turnoAbierto ? 'Turno abierto' : 'Sin turno abierto' ?>
          </span>
        </div>

        <!-- Body -->
        <div class="px-5 py-5 space-y-4">

          <!-- HOY -->
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-chebs-line p-4 bg-white hover:bg-pink-50/60 transition">
              <div class="text-xs text-gray-500 font-black">Vendido hoy</div>
              <div class="text-2xl font-black text-chebs-black tabular-nums">Bs <?= money($hoyV) ?></div>
            </div>

            <div class="rounded-2xl border border-chebs-line p-4 bg-white hover:bg-pink-50/60 transition">
              <div class="text-xs text-gray-500 font-black">Ganancia hoy</div>
              <div class="text-2xl font-black tabular-nums <?= ($hoyG>=0?'text-chebs-green':'text-red-600') ?>">
                Bs <?= money($hoyG) ?>
              </div>
            </div>
          </div>

          <!-- MES -->
          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-chebs-line p-4 bg-white hover:bg-chebs-soft/40 transition">
              <div class="text-xs text-gray-500 font-black">Vendido mes</div>
              <div class="text-2xl font-black text-chebs-black tabular-nums">Bs <?= money($mesV) ?></div>
              <div class="text-[11px] text-gray-500 mt-1">Se reinicia al cambiar de mes</div>
            </div>

            <div class="rounded-2xl border border-chebs-line p-4 bg-white hover:bg-chebs-soft/40 transition">
              <div class="text-xs text-gray-500 font-black">Ganancia mes</div>
              <div class="text-2xl font-black tabular-nums <?= ($mesG>=0?'text-chebs-green':'text-red-600') ?>">
                Bs <?= money($mesG) ?>
              </div>
              <div class="text-[11px] text-gray-500 mt-1">Solo calcula cuando hay costo</div>
            </div>
          </div>

          <!-- TURNOS HOY -->
          <div class="rounded-2xl border border-chebs-line overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-chebs-line flex items-center justify-between">
              <div class="font-black text-chebs-black text-sm">Turnos de hoy</div>
              <div class="text-xs text-gray-500"><?= count($e['turnos_hoy']) ?> turno(s)</div>
            </div>

            <!-- ✅ CAMBIO: dejamos espacio abajo para que NO lo tape el footer -->
            <div class="max-h-40 overflow-auto chebs-scroll bg-white pb-3">
              <?php if(empty($e['turnos_hoy'])): ?>
                <div class="px-4 py-3 text-sm text-gray-600">Sin turnos hoy.</div>
              <?php else: ?>
                <?php foreach($e['turnos_hoy'] as $t): ?>
                  <div class="px-4 py-3 border-b border-chebs-line last:border-b-0">
                    <div class="flex items-center justify-between gap-2">
                      <div class="font-black text-chebs-black text-sm">
                        Turno #<?= (int)$t['id'] ?>
                        <span class="ml-2 text-xs font-black <?= ($t['estado']==='abierto'?'text-chebs-green':'text-gray-600') ?>">
                          <?= strtoupper($t['estado']) ?>
                        </span>
                      </div>
                      <div class="text-xs text-gray-500 tabular-nums">
                        <?= htmlspecialchars(date('H:i', strtotime($t['abierto_en']))) ?>
                        →
                        <?= $t['cerrado_en'] ? htmlspecialchars(date('H:i', strtotime($t['cerrado_en']))) : '—' ?>
                      </div>
                    </div>

                    <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                      <div class="rounded-xl bg-chebs-soft/40 border border-chebs-line px-2 py-2">
                        <div class="text-gray-500 font-black">Ventas</div>
                        <div class="font-black tabular-nums">Bs <?= money($t['total_ventas'] ?? 0) ?></div>
                      </div>
                      <div class="rounded-xl bg-chebs-soft/40 border border-chebs-line px-2 py-2">
                        <div class="text-gray-500 font-black">Retiros</div>
                        <div class="font-black tabular-nums">Bs <?= money($t['total_retiros'] ?? 0) ?></div>
                      </div>
                      <div class="rounded-xl bg-chebs-soft/40 border border-chebs-line px-2 py-2">
                        <div class="text-gray-500 font-black">Diferencia</div>
                        <div class="font-black tabular-nums <?= ((float)($t['diferencia'] ?? 0) >= 0) ? 'text-chebs-green' : 'text-red-600' ?>">
                          Bs <?= money($t['diferencia'] ?? 0) ?>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

        </div><!-- /Body -->

        <!-- ✅ CAMBIO PRINCIPAL: Footer pegado abajo (izq eliminar / der editar) -->
        <div class="mt-auto sticky bottom-0 border-t border-chebs-line bg-white/95 backdrop-blur px-5 py-4">
          <div class="flex items-center justify-between gap-3">

            <!-- Eliminar -->
            <form action="/PULPERIA-CHEBS/vistas/perfiles/eliminar_usuario.php" method="POST"
                  onsubmit="return confirm('¿Seguro que deseas eliminar este usuario? (Se desactivará)');">
              <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
              <button type="submit"
                      class="px-4 py-2 rounded-xl bg-red-50 text-red-700 border border-red-200 font-black hover:bg-red-100 transition">
                Eliminar
              </button>
            </form>

            <!-- Editar -->
            <a href="/PULPERIA-CHEBS/vistas/perfiles/editar.php?id=<?= (int)$e['id'] ?>"
               class="px-5 py-2 rounded-xl bg-white border border-chebs-line font-black hover:bg-chebs-soft transition">
              Editar usuario
            </a>

          </div>
        </div>

      </div><!-- /Card -->

    <?php endforeach; ?>
  </div>

</div>

<?php include __DIR__ . "/../layout/footer.php"; ?>

<?php
require_once __DIR__ . "/config/auth.php";
require_role(['admin']);

if (session_status() === PHP_SESSION_NONE) session_start();
$nombre = $_SESSION['user']['nombre'] ?? ($_SESSION['user']['usuario'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pulpería Chebs | Admin</title>

  <!-- ✅ Fondo vivo + animaciones suaves -->
  <style>
    :root{
      --chebs-green:#4E7A2B;
      --chebs-greenDark:#3b631d;
      --chebs-soft:#c7d8b9;
      --chebs-line:#E5E7EB;
      --chebs-black:#111111;

      --pink:#ec4899;      /* pink-500 */
      --pink2:#f472b6;     /* pink-400 */
      --pink-soft:#fdf2f8; /* pink-50 */
      --glow: 0 18px 55px rgba(0,0,0,.12);
      --glowPink: 0 18px 55px rgba(236,72,153,.22);
      --glowGreen: 0 18px 55px rgba(78,122,43,.22);
    }

    /* Fondo con vida (pero limpio) */
    body{
      background:
        radial-gradient(900px 400px at 20% 5%, rgba(236,72,153,.18), transparent 60%),
        radial-gradient(900px 400px at 80% 0%, rgba(78,122,43,.20), transparent 60%),
        radial-gradient(900px 500px at 50% 110%, rgba(199,216,185,.55), transparent 60%),
        #f7faf7;
    }

    /* Tarjetas con “luz” por color */
    .chebs-card{
      position:relative;
      border-radius: 24px;
      border: 2px solid var(--chebs-line);
      background: rgba(255,255,255,.92);
      box-shadow: 0 10px 30px rgba(0,0,0,.07);
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
      overflow:hidden;
      will-change: transform;
    }

    /* brillo superior suave */
    .chebs-card::before{
      content:"";
      position:absolute;
      inset:-2px;
      background: radial-gradient(600px 220px at 25% 0%, rgba(255,255,255,.55), transparent 60%);
      pointer-events:none;
      opacity:.9;
    }

    /* “luz” del color (halo) */
    .chebs-card::after{
      content:"";
      position:absolute;
      width: 420px;
      height: 220px;
      left:-120px;
      top:-120px;
      background: radial-gradient(circle, rgba(78,122,43,.20), transparent 60%);
      filter: blur(2px);
      pointer-events:none;
      opacity:.65;
      transition: opacity .18s ease;
    }

    .chebs-card:hover{
      transform: translateY(-4px);
      box-shadow: var(--glow);
      border-color: rgba(17,17,17,.10);
      background: rgba(255,255,255,.98);
    }
    .chebs-card:hover::after{ opacity: 1; }

    /* Variantes por color */
    .card-pink{ border-color: rgba(236,72,153,.55); }
    .card-pink::after{ background: radial-gradient(circle, rgba(236,72,153,.22), transparent 62%); }
    .card-pink:hover{ box-shadow: var(--glowPink); border-color: rgba(236,72,153,.75); }

    .card-green{ border-color: rgba(78,122,43,.45); }
    .card-green::after{ background: radial-gradient(circle, rgba(78,122,43,.22), transparent 62%); }
    .card-green:hover{ box-shadow: var(--glowGreen); border-color: rgba(78,122,43,.65); }

    .card-soft{ border-color: rgba(199,216,185,.85); }
    .card-soft::after{ background: radial-gradient(circle, rgba(199,216,185,.65), transparent 62%); }

    /* Iconos con borde vivo */
    .chebs-icon{
      width: 44px; height: 44px;
      border-radius: 16px;
      display:flex; align-items:center; justify-content:center;
      font-weight: 900;
      border: 2px solid rgba(236,72,153,.35);
      background: linear-gradient(180deg, rgba(253,242,248,.95), rgba(255,255,255,.95));
      color: var(--pink);
      box-shadow: 0 10px 25px rgba(236,72,153,.10);
      transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .chebs-card:hover .chebs-icon{
      transform: translateY(-1px) scale(1.02);
      border-color: rgba(236,72,153,.55);
      box-shadow: 0 16px 30px rgba(236,72,153,.16);
    }

    .chebs-pill{
      border: 2px solid rgba(78,122,43,.25);
      background: rgba(78,122,43,.10);
      color: var(--chebs-greenDark);
      font-weight: 900;
      border-radius: 999px;
      padding: 10px 14px;
      display:inline-flex;
      align-items:center;
      gap:8px;
    }

    /* Botones más vivos */
    .btn-chebs{
      border-radius: 18px;
      padding: 12px 18px;
      font-weight: 900;
      transition: transform .18s ease, filter .18s ease, box-shadow .18s ease;
      box-shadow: 0 16px 35px rgba(78,122,43,.18);
    }
    .btn-chebs:hover{ transform: translateY(-2px); filter: saturate(1.08); }

    .btn-green{
      background: linear-gradient(180deg, #5b8b33 0%, #4E7A2B 55%, #3b631d 100%);
      color:#fff;
      border: 1px solid rgba(17,17,17,.06);
    }
    .btn-green:hover{
      box-shadow: 0 22px 45px rgba(78,122,43,.25);
    }

    .btn-white{
      background: rgba(255,255,255,.95);
      border: 2px solid rgba(236,72,153,.28);
      color: var(--chebs-black);
      box-shadow: 0 14px 35px rgba(236,72,153,.12);
    }
    .btn-white:hover{
      box-shadow: 0 22px 45px rgba(236,72,153,.18);
    }

    /* Titulares más pro */
    .title{
      letter-spacing: -0.02em;
      text-shadow: 0 1px 0 rgba(255,255,255,.7);
    }

    /* Para que no se vea “apagado” */
    .muted{ color: rgba(17,17,17,.68); }
    .link-green{ color: var(--chebs-green); font-weight: 900; }
    .link-green:hover{ color: var(--chebs-greenDark); }

  </style>
</head>

<body class="text-chebs-black">

<?php include __DIR__ . "/vistas/layout/header.php"; ?>

<div class="max-w-6xl mx-auto px-4 py-8">

  <!-- Encabezado -->
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
      <h1 class="text-3xl md:text-4xl font-black text-chebs-black title">Panel de Administración</h1>
      <p class="text-sm mt-2 muted">
        Bienvenido, <span class="font-black text-chebs-black"><?= htmlspecialchars($nombre) ?></span>.
        Administra tu tienda desde aquí.
      </p>

      <div class="mt-4 inline-flex items-center chebs-pill">
        <span class="inline-flex w-7 h-7 rounded-full bg-white border border-chebs-line items-center justify-center">🛡️</span>
        Modo Admin activo
      </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
      <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php"
         class="btn-chebs btn-green inline-flex items-center justify-center gap-2">
        <span class="inline-flex w-6 h-6 rounded-lg bg-white/15 items-center justify-center">💳</span>
        Ir Caja
      </a>

      <a href="/PULPERIA-CHEBS/controladores/logout.php"
         class="btn-chebs btn-white inline-flex items-center justify-center gap-2">
        <span class="inline-flex w-6 h-6 rounded-lg bg-pink-50 items-center justify-center">⎋</span>
        Cerrar sesión
      </a>
    </div>
  </div>

  <!-- Accesos rápidos -->
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

    <!-- Caja (verde) -->
    <a href="/PULPERIA-CHEBS/vistas/ventas/venta.php" class="chebs-card card-green p-6 group">
      <div class="relative z-10 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-black text-chebs-black group-hover:text-chebs-greenDark transition">Caja</h3>
          <p class="text-sm muted mt-1">Registrar ventas, abrir/cerrar turno y retiros.</p>
        </div>
        <div class="chebs-icon" style="border-color: rgba(78,122,43,.35); color: var(--chebs-green);">
          $
        </div>
      </div>
      <div class="relative z-10 mt-4 inline-flex items-center gap-2 text-sm font-black link-green">
        Entrar <span class="transition group-hover:translate-x-1">→</span>
      </div>
    </a>

    <!-- Productos (rosado) -->
    <a href="/PULPERIA-CHEBS/vistas/productos/listar.php" class="chebs-card card-pink p-6 group">
      <div class="relative z-10 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-black text-chebs-black group-hover:text-pink-600 transition">Productos</h3>
          <p class="text-sm muted mt-1">Crear, editar y administrar precios.</p>
        </div>
        <div class="chebs-icon">P</div>
      </div>
      <div class="relative z-10 mt-4 inline-flex items-center gap-2 text-sm font-black text-pink-600">
        Gestionar <span class="transition group-hover:translate-x-1">→</span>
      </div>
    </a>

    <!-- Lotes (verde) -->
    <a href="/PULPERIA-CHEBS/vistas/lotes/listar.php" class="chebs-card card-green p-6 group">
      <div class="relative z-10 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-black text-chebs-black group-hover:text-chebs-greenDark transition">Lotes</h3>
          <p class="text-sm muted mt-1">Entradas, stock por lote y control.</p>
        </div>
        <div class="chebs-icon" style="border-color: rgba(78,122,43,.35); color: var(--chebs-green); background: linear-gradient(180deg, rgba(78,122,43,.10), rgba(255,255,255,.95));">
          L
        </div>
      </div>
      <div class="relative z-10 mt-4 inline-flex items-center gap-2 text-sm font-black link-green">
        Ver lotes <span class="transition group-hover:translate-x-1">→</span>
      </div>
    </a>

    <!-- Inventario (rosado + verde suave) -->
    <a href="/PULPERIA-CHEBS/vistas/movimientos/historial.php" class="chebs-card card-soft p-6 group" style="border-color: rgba(236,72,153,.35);">
      <div class="relative z-10 flex items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-black text-chebs-black group-hover:text-pink-600 transition">Inventario</h3>
          <p class="text-sm muted mt-1">Historial de movimientos y stock.</p>
        </div>
        <div class="chebs-icon">I</div>
      </div>
      <div class="relative z-10 mt-4 inline-flex items-center gap-2 text-sm font-black text-pink-600">
        Ver historial <span class="transition group-hover:translate-x-1">→</span>
      </div>
    </a>

  </div>

  <!-- Segunda fila -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">

    <!-- Historial de ventas (rosado) -->
    <a href="/PULPERIA-CHEBS/vistas/ventas/historial.php" class="chebs-card card-pink p-6 group">
      <div class="relative z-10 flex items-center justify-between gap-3">
        <div>
          <h3 class="text-lg font-black text-chebs-black group-hover:text-pink-600 transition">Historial de Ventas</h3>
          <p class="text-sm muted mt-1">Revisa ventas anteriores y reportes.</p>
        </div>
        <div class="text-pink-600 text-2xl font-black transition group-hover:translate-x-1">→</div>
      </div>
    </a>

    <!-- Tips (verde) -->
    <div class="chebs-card card-green p-6">
      <div class="relative z-10">
        <h3 class="text-lg font-black text-chebs-black">Tips rápidos</h3>
        <ul class="mt-3 space-y-2 text-sm muted">
          <li class="flex gap-2">
            <span class="font-black text-chebs-green">•</span> Ctrl + Enter en ventas para confirmar rápido.
          </li>
          <li class="flex gap-2">
            <span class="font-black text-chebs-green">•</span> Usa “Abrir turno” antes de vender.
          </li>
          <li class="flex gap-2">
            <span class="font-black text-chebs-green">•</span> Recuerda: stock considera solo lotes activos.
          </li>
        </ul>
      </div>
    </div>

  </div>

</div>

<?php include __DIR__ . "/vistas/layout/footer.php"; ?>
</body>
</html>

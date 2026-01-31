<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['user'])) {
    $rol = $_SESSION['user']['rol'] ?? '';
    if ($rol === 'admin') {
        header("Location: /PULPERIA-CHEBS/index.php");
        exit;
    }
    header("Location: /PULPERIA-CHEBS/vistas/ventas/venta.php");
    exit;
}

$error = $_GET['err'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login | Pulpería Chebs</title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="/PULPERIA-CHEBS/public/img/logo.png">

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            chebs: {
              green: '#4E7A2B',
              greenDark: '#35541D',
              greenSoft: '#EAF2E2',
              cream: '#FBFBF7',
              pink: '#F2C7D8',
              ink: '#1F1F1F',
            }
          },
          borderRadius: { xl2: '1.25rem' },
          boxShadow: { soft: '0 10px 25px rgba(0,0,0,.12)' }
        }
      }
    }
  </script>
</head>

<body class="min-h-screen bg-chebs-greenSoft text-chebs-ink">
  <div class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-chebs-cream shadow-soft rounded-xl2 border border-chebs-green/20 overflow-hidden">

      <!-- Top banner -->
      <div class="p-6 bg-chebs-green text-white">
        <div class="flex items-center gap-5">

          <!-- Logo -->
          <div class="h-24 w-24 rounded-2xl bg-white flex items-center justify-center
                      shadow-lg border border-black/10 overflow-hidden">
            <img
              src="/PULPERIA-CHEBS/public/img/logo.png"
              alt="Chebs"
              class="h-20 w-20 object-contain">
          </div>

          <!-- Title -->
          <div>
            <div class="text-4xl font-extrabold leading-tight">
              <span class="text-white">PULPERIA</span>
              <span class="text-black"> chebs</span>
            </div>
            <div class="text-sm text-white/80">
              Iniciar sesión
            </div>
          </div>

        </div>
      </div>

      <!-- Body -->
      <div class="p-5">
        <?php if ($error): ?>
          <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
            ❌ Usuario o contraseña incorrectos
          </div>
        <?php else: ?>
          <div class="mb-4 p-3 rounded-xl bg-chebs-green/5 border border-chebs-green/20 text-sm text-black/70">
            Ingresa con tu usuario y contraseña para continuar.
          </div>
        <?php endif; ?>

        <form method="POST" action="/PULPERIA-CHEBS/controladores/login.php" class="space-y-3">
          <div>
            <label class="text-sm font-semibold">Usuario</label>
            <input
              type="text"
              name="usuario"
              placeholder="Ej: admin"
              required
              autofocus
              class="mt-1 w-full px-3 py-2 rounded-xl border border-black/10 bg-white
                     focus:outline-none focus:ring-2 focus:ring-chebs-green/20 focus:border-chebs-green"
            >
          </div>

          <div>
            <label class="text-sm font-semibold">Contraseña</label>
            <input
              type="password"
              name="password"
              placeholder="••••••••"
              required
              class="mt-1 w-full px-3 py-2 rounded-xl border border-black/10 bg-white
                     focus:outline-none focus:ring-2 focus:ring-chebs-green/20 focus:border-chebs-green"
            >
          </div>

          <button
            type="submit"
            class="w-full mt-2 px-4 py-3 rounded-xl bg-chebs-green text-white font-semibold
                   hover:bg-chebs-greenDark active:scale-[0.99] transition"
          >
            Entrar
          </button>
        </form>

        <div class="mt-4 text-center text-xs text-black/50">
          © <?= date('Y') ?> Pulpería Chebs
        </div>
      </div>

    </div>

  </div>
</body>
</html>

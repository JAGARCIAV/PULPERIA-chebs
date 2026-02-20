<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['user'])) {
    header("Location: /PULPERIA-CHEBS/index.php");
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

            <!-- ✅ Contenedor para el input + ojo -->
            <div class="relative mt-1">
              <input
                id="password"
                type="password"
                name="password"
                placeholder="••••••••"
                required
                class="w-full px-3 py-2 pr-11 rounded-xl border border-black/10 bg-white
                       focus:outline-none focus:ring-2 focus:ring-chebs-green/20 focus:border-chebs-green"
              >

              <!-- ✅ Botón ojo -->
              <button
                type="button"
                id="togglePassword"
                class="absolute inset-y-0 right-2 flex items-center justify-center
                       w-9 rounded-lg text-black/60 hover:text-black
                       focus:outline-none focus:ring-2 focus:ring-chebs-green/20"
                aria-label="Mostrar u ocultar contraseña"
              >
                <!-- Icono ojo (ver) -->
                <svg id="iconEye" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>

                <!-- Icono ojo tachado (ocultar) -->
                <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M10.58 10.58A2 2 0 0 0 12 15a2 2 0 0 0 1.42-.58"></path>
                  <path d="M9.88 9.88A3 3 0 0 1 12 9a3 3 0 0 1 3 3c0 .82-.33 1.57-.88 2.12"></path>
                  <path d="M2 12s3-7 10-7c2.1 0 3.9.64 5.4 1.55"></path>
                  <path d="M22 12s-3 7-10 7c-2.1 0-3.9-.64-5.4-1.55"></path>
                  <line x1="2" y1="2" x2="22" y2="22"></line>
                </svg>
              </button>
            </div>
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

  <!-- ✅ Script mostrar/ocultar contraseña -->
  <script>
    const passInput = document.getElementById('password');
    const btnToggle = document.getElementById('togglePassword');
    const iconEye = document.getElementById('iconEye');
    const iconEyeOff = document.getElementById('iconEyeOff');

    btnToggle.addEventListener('click', () => {
      const isPassword = passInput.type === 'password';
      passInput.type = isPassword ? 'text' : 'password';

      // Cambia iconos
      iconEye.classList.toggle('hidden', isPassword);
      iconEyeOff.classList.toggle('hidden', !isPassword);

      // (Opcional) Mantener foco
      passInput.focus();
    });
  </script>
</body>
</html>
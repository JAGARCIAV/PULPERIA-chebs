<body class="min-h-screen flex flex-col">

<main class="flex-1">
  <!-- Todo tu contenido aquí -->
</main>

<footer class="text-center text-xs text-gray-500 py-6 mt-auto">
  © <?= date('Y') ?> Pulpería Chebs · Sistema de ventas
</footer>

<?php
// ✅ contador global de notificaciones en el header (sin popups)
include __DIR__ . "/../notificacion/notificacion_badge.php";
?>

</body>
</html>
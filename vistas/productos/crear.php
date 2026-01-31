<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

include "../layout/header.php";
?>

<div class="max-w-3xl mx-auto px-4 py-10">

  <!-- Card -->
  <div class="bg-white border border-chebs-line rounded-3xl shadow-soft p-8">

    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-black text-chebs-black">Crear producto</h1>
      <p class="text-sm text-gray-600 mt-1">
        Registra un nuevo producto con sus precios de venta.
      </p>
    </div>

    <!-- Form -->
    <form action="../../controladores/producto_controlador.php"
          method="POST"
          class="space-y-6">

      <!-- Nombre -->
      <div>
        <label class="block text-sm font-bold mb-2">Nombre</label>
        <input type="text"
               name="nombre"
               required
               class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                      focus:outline-none focus:ring-2 focus:ring-chebs-green/40"
               placeholder="Ej: Arroz 1kg">
      </div>

      <!-- Descripción -->
      <div>
        <label class="block text-sm font-bold mb-2">Descripción</label>
        <textarea name="descripcion"
                  rows="3"
                  class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                         focus:outline-none focus:ring-2 focus:ring-chebs-green/40"
                  placeholder="Opcional"></textarea>
      </div>

      <!-- Precios grid -->
      <div class="grid md:grid-cols-2 gap-6">

        <!-- Precio unidad -->
        <div>
          <label class="block text-sm font-bold mb-2">Precio por unidad</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold">Bs</span>
            <input type="number"
                   step="0.01"
                   name="precio_unidad"
                   required
                   class="w-full pl-10 pr-4 py-3 rounded-2xl border border-chebs-line
                          focus:outline-none focus:ring-2 focus:ring-chebs-green/40"
                   placeholder="0.00">
          </div>
        </div>

        <!-- Precio paquete -->
        <div>
          <label class="block text-sm font-bold mb-2">Precio por paquete</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold">Bs</span>
            <input type="number"
                   step="0.01"
                   name="precio_paquete"
                   class="w-full pl-10 pr-4 py-3 rounded-2xl border border-chebs-line
                          focus:outline-none focus:ring-2 focus:ring-chebs-green/40"
                   placeholder="Opcional">
          </div>
        </div>

      </div>

      <!-- Unidades por paquete -->
      <div>
        <label class="block text-sm font-bold mb-2">Unidades por paquete</label>
        <input type="number"
               name="unidades_paquete"
               value="1"
               min="1"
               class="w-full px-4 py-3 rounded-2xl border border-chebs-line
                      focus:outline-none focus:ring-2 focus:ring-chebs-green/40">
        <p class="text-xs text-gray-500 mt-1">
          Cuántas unidades trae un paquete completo.
        </p>
      </div>

      <!-- Botones -->
      <div class="flex flex-col sm:flex-row gap-4 pt-4">

        <button type="submit"
                class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-2xl
                       bg-chebs-green text-white font-black
                       hover:bg-chebs-greenDark transition shadow-soft">
          💾 Guardar producto
        </button>

        <a href="listar.php"
           class="flex-1 inline-flex items-center justify-center px-6 py-3 rounded-2xl
                  border border-chebs-line bg-white font-black
                  hover:bg-chebs-soft transition">
          ← Volver a lista
        </a>

      </div>

    </form>

  </div>

</div>

<?php include "../layout/footer.php"; ?>

<?php
require_once __DIR__ . "/../../config/auth.php";
require_role(['admin']);

include "../layout/header.php";
?>

<?php if(isset($_GET['creado']) && isset($_GET['id'])): ?>
<div id="modalLote" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
  <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-soft">

    <h2 class="text-xl font-black mb-3">Producto guardado 🎉</h2>
    <p class="text-gray-600 mb-6">
      ¿Quieres crear el lote inicial para este producto?
    </p>

    <div class="flex gap-4">
      <a href="../lotes/registrar_lote.php?producto_id=<?= (int)$_GET['id'] ?>"
         class="flex-1 text-center px-4 py-3 rounded-2xl bg-chebs-green text-white font-black">
        ➕ Crear lote
      </a>

      <a href="listar.php"
         class="flex-1 text-center px-4 py-3 rounded-2xl border border-chebs-line font-black">
        Solo guardar
      </a>
    </div>

  </div>
</div>
<?php endif; ?>


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
      class="space-y-4">

  <!-- Nombre (DESTACADO) -->
  <div>
    <label class="block text-sm font-bold  mb-1">Nombre del producto</label>
    <input type="text"
           name="nombre"
           required
           class="w-full rounded-xl bg-pink-50 border-2 border-pink-300
                  px-3 py-2 text-gray-800 
                  outline-none focus:ring-4 focus:ring-pink-200
                  focus:border-pink-500"
           placeholder="Ej: Arroz 1kg">
  </div>

  <!-- PRECIOS -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <!-- Precio unidad (DESTACADO) -->
    <div>
      <label class="block text-sm font-bold  mb-1">Precio unidad</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold">Bs</span>
        <input type="number"
               step="0.01"
               name="precio_unidad"
               required
               class="w-full pl-9 pr-3 py-2 rounded-xl
                      bg-pink-50 border-2 border-pink-300
                      outline-none focus:ring-4 focus:ring-pink-200
                      focus:border-pink-500"
               placeholder="0.00">
      </div>
    </div>

    <!-- Precio paquete -->
    <div>
      <label class="block text-sm font-bold mb-1">Precio del paquete</label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold">Bs</span>
        <input type="number"
               step="0.01"
               name="precio_paquete"
               class="w-full pl-9 pr-3 py-2 rounded-xl 
        bg-orange-50 border-2 border-orange-300 
        outline-none focus:ring-4 focus:ring-orange-200 
        focus:border-orange-500"

               placeholder="Opcional">
      </div>
    </div>

  </div>

  <!-- Unidades por paquete -->
  <div>
    <label class="block text-sm font-bold mb-1">Unidades por paquete</label>
    <input type="number"
           name="unidades_paquete"
           class="w-full px-3 py-2 rounded-xl 
       bg-orange-50 border-2 border-orange-300 
       outline-none focus:ring-4 focus:ring-orange-200 
       focus:border-orange-500"placeholder="Opcional">
  </div>

  <!-- BOTONES -->
  <div class="flex flex-col sm:flex-row gap-3 pt-2">

    <button type="submit"
            class="flex-1 inline-flex items-center justify-center px-5 py-2 rounded-xl
                   bg-chebs-green text-white font-black
                   hover:bg-chebs-greenDark transition shadow-soft">
      💾 Guardar
    </button>

    <a href="listar.php"
       class="flex-1 inline-flex items-center justify-center px-5 py-2 rounded-xl
              border border-chebs-line bg-white font-black
              hover:bg-chebs-soft transition">
      ← Volver
    </a>

  </div>

</form>


  </div>

</div>

<?php include "../layout/footer.php"; ?>

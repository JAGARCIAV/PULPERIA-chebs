<?php
// 1. INCLUIR CONEXIÓN Y MODELOS
include_once "../../config/conexion.php";
include_once "../../modelos/venta_modelo.php";
include "../layout/header.php";
include_once "../../modelos/producto_modelo.php";

// 2. OBTENER DATOS
$id_venta = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_venta === 0) {
    die("ID de venta no válido");
}

// Obtener los detalles de la venta actual
$detalles = corregirVenta($conexion, $id_venta);

// Obtener todos los productos para el input de "cambiar producto"
$productosDisponibles = obtenerProductos($conexion); 
// Guardamos productos en un array para usarlos fácil en el datalist
$listaProductos = [];
while($p = $productosDisponibles->fetch_assoc()){
    $listaProductos[] = $p;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corregir Venta #<?= $id_venta ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .text-chebs-green { color: #16a34a; }
        .bg-chebs-green { background-color: #16a34a; }
    </style>
</head>
<body class="bg-gray-50 p-6">

<div class="max-w-4xl mx-auto">
    
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-gray-800">Corregir Venta #<?= $id_venta ?></h1>
            <p class="text-sm text-gray-500">Edita cantidades, elimina items o cambia productos.</p>
        </div>
        <a href="javascript:history.back()" class="text-gray-500 hover:text-gray-700 underline">Volver</a>
    </div>

    <form action="../../controladores/corregir_venta.php" method="POST" class="bg-white rounded-3xl shadow-lg border border-gray-200 overflow-hidden">
        <input type="hidden" name="venta_id" value="<?= $id_venta ?>">

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-600 uppercase font-bold text-xs">
                    <tr>
                        <th class="p-4">Producto Original</th>
                        <th class="p-4 w-1/3">Cambiar por (Opcional)</th>
                        <th class="p-4 w-24">Cantidad</th>
                        <th class="p-4 text-center">Eliminar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while($d = $detalles->fetch_assoc()) { ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4">
                                <div class="font-bold text-gray-800"><?= htmlspecialchars($d['nombre']) ?></div>
                                <div class="text-xs text-gray-500">
                                    Precio: Bs <?= number_format($d['precio_unitario'], 2) ?>
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-gray-200 text-gray-600 text-[10px]"><?= $d['tipo_venta'] ?></span>
                                </div>
                            </td>

                            <td class="p-4">
                                <input 
                                    list="lista-productos" 
                                    name="nuevo_producto[<?= $d['id'] ?>]" 
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 outline-none text-sm"
                                    placeholder="Buscar otro producto..."
                                >
                                <p class="text-[10px] text-gray-400 mt-1">Déjalo vacío para mantener el original</p>
                            </td>

                            <td class="p-4">
                                <input 
                                    type="number" 
                                    name="cantidad[<?= $d['id'] ?>]" 
                                    value="<?= (int)$d['cantidad'] ?>" 
                                    min="1" 
                                    class="w-full font-bold text-center border border-gray-300 rounded-lg px-2 py-2 focus:ring-2 focus:ring-green-500 outline-none"
                                >
                            </td>

                            <td class="p-4 text-center">
                                <label class="cursor-pointer flex items-center justify-center group">
                                    <input type="checkbox" name="eliminar[]" value="<?= $d['id'] ?>" class="peer sr-only">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-400 peer-checked:bg-red-100 peer-checked:text-red-600 flex items-center justify-center transition-all group-hover:bg-red-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </div>
                                </label>
                                <div class="text-[10px] text-gray-400 mt-1">Borrar</div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <a href="javascript:history.back()" class="px-5 py-2.5 rounded-xl border border-gray-300 font-bold text-gray-600 hover:bg-white transition-colors">
                Cancelar
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-chebs-green text-white font-bold shadow-lg shadow-green-200 hover:shadow-green-300 hover:scale-[1.02] transition-all">
                Guardar Cambios
            </button>
        </div>

    </form>
</div>

<datalist id="lista-productos">
    <?php foreach($listaProductos as $prod) { ?>
        <option value="<?= htmlspecialchars($prod['id']) ?> - <?= htmlspecialchars($prod['nombre']) ?>">
            Bs <?= number_format($prod['precio'], 2) ?>
        </option>
    <?php } ?>
</datalist>

</body>
</html>
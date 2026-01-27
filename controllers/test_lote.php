<?php
require_once "../model/lote/Lote.php";

$producto_id = 1;      // cambia por un producto real
$unidades = 5;         // unidades a descontar

$resultado = Lote::descontarStock($producto_id, $unidades);

if ($resultado) {
    echo "✅ Stock descontado correctamente";
} else {
    echo "❌ No hay stock suficiente";
}

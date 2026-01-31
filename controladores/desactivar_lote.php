<?php
require_once "../config/conexion.php";
require_once "../modelos/lote_modelo.php";

$lote_id = $_GET['id'];

desactivarLotes($conexion, $lote_id);

header("Location: ../vistas/lotes/listar.php");
?>

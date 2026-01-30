<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$_SESSION = [];
session_destroy();

header("Location: /PULPERIA-CHEBS/vistas/login.php");
exit;

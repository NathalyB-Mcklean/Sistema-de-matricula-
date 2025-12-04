<?php
// Datos de conexión
$sql_host = "localhost";
$sql_name = "matricula";
$sql_user = "matricula";
$sql_pass = "matricula123";

// Crear la conexión - CORREGIR: usar $conexion en lugar de $conn
$conexion = new mysqli($sql_host, $sql_user, $sql_pass, $sql_name);

// Verificar la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

?>
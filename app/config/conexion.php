<?php
// app/config/conexion.php - VERSIÓN SIMPLE QUE FUNCIONA CON TU CÓDIGO

// Configuración
$host = "localhost";
$user = "matricula";      // Tu usuario
$pass = "matricula123";   // Tu contraseña
$dbname = "matricula";    // Tu base de datos

// Crear conexión (usa $conexion, no una clase)
$conexion = new mysqli($host, $user, $pass, $dbname);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión a la base de datos. Verifica:<br>
         1. ¿La base de datos 'matricula' existe?<br>
         2. ¿El usuario 'matricula' tiene acceso?<br>
         3. ¿La contraseña 'matricula123' es correcta?<br>
         Error: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

?>
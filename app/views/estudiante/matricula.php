<?php
require_once("../database/conexion.php");
require_once("../validaciones/validaciones.php");

try {
    // Datos del administrador 
    $nombre = "Samuel";
    $apellido = "De Luque";
    $correo = "vegetta777@utp.ac.pa";
    $password = "Admin777";  
    $rol = "admin";

    // ------ VALIDACIONES -------
    validarNoVacio($nombre, "nombre");
    validarNoVacio($apellido, "apellido");
    validarCorreoUTP($correo);
    validarPassword($password);

    // Generar hash de la contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Verificar si ya existe
    $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        throw new Exception("Ya existe una cuenta con ese correo institucional.");
    }

    // Insertar administrador
    $insert = $conexion->prepare("
        INSERT INTO usuario (nombre, apellido, correo, password, rol)
        VALUES (?, ?, ?, ?, ?)
    ");

    $insert->bind_param("sssss", $nombre, $apellido, $correo, $passwordHash, $rol);

    if ($insert->execute()) {
        echo "<strong>Administrador creado exitosamente</strong><br><br>";
        echo "Nombre: $nombre $apellido<br>";
        echo "Correo: $correo<br>";
        echo "Rol: $rol<br>";
        echo "Contraseña: $password<br>";
        echo "Hash generado: $passwordHash<br><br>";
    } else {
        throw new Exception("Error al registrar administrador en la base de datos.");
    }

} catch (Exception $e) {
    echo "<strong>Error:</strong> " . $e->getMessage();
}
?>
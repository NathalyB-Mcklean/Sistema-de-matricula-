<?php

// Validar correo institucional
function validarCorreoUTP($correo) {
    if (!preg_match("/@utp\.ac\.pa$/", $correo)) {
        throw new Exception("El correo debe ser institucional (@utp.ac.pa).");
    }
}

// Validar campos vacíos
function validarNoVacio($campo, $nombreCampo) {
    if (empty(trim($campo))) {
        throw new Exception("El campo $nombreCampo no puede estar vacío.");
    }
}

// Validar que las contraseñas coincidan
function validarCoincidenciaPassword($pass1, $pass2) {
    if ($pass1 !== $pass2) {
        throw new Exception("Las contraseñas no coinciden.");
    }
}

// Validar fortaleza de contraseña
function validarPassword($password) {
    if (strlen($password) < 8) {
        throw new Exception("La contraseña debe tener al menos 8 caracteres.");
    }
}

// Función para login (faltante)
function validarLogin($correo, $password) {
    $errores = [];
    
    if (empty(trim($correo))) {
        $errores[] = "El correo es requerido";
    }
    
    if (empty(trim($password))) {
        $errores[] = "La contraseña es requerida";
    }
    
    return $errores;
}

// Campos vacíos (para arrays)
function camposVacios($datos) {
    foreach ($datos as $d) {
        if (empty(trim($d))) {
            return true;
        }
    }
    return false;
}

// Añadir mensajes de error al arreglo
function agregarError(&$errores, $mensaje) {
    $errores[] = $mensaje;
}
?>
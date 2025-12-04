<?php
// app/utils/validaciones.php

function validarNoVacio($campo, $nombreCampo) {
    if (empty(trim($campo))) {
        throw new Exception("El campo '$nombreCampo' no puede estar vacío.");
    }
}

function validarCorreoUTP($correo) {
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Correo electrónico inválido.");
    }
    
    if (!preg_match("/@utp\.ac\.pa$/", $correo)) {
        throw new Exception("El correo debe ser institucional (@utp.ac.pa).");
    }
}

function validarPassword($password) {
    if (strlen($password) < 8) {
        throw new Exception("La contraseña debe tener al menos 8 caracteres.");
    }
}

function validarCoincidenciaPassword($pass1, $pass2) {
    if ($pass1 !== $pass2) {
        throw new Exception("Las contraseñas no coinciden.");
    }
}

// Funciones para login
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

function camposVacios($datos) {
    foreach ($datos as $d) {
        if (empty(trim($d))) {
            return true;
        }
    }
    return false;
}

function agregarError(&$errores, $mensaje) {
    $errores[] = $mensaje;
}
?>
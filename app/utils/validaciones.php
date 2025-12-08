<?php
// app/utils/validaciones.php - VERSIÓN COMPATIBLE (funciones + clase)

// ==============================================
// FUNCIONES ORIGINALES (para compatibilidad)
// ==============================================

// Función para validar que no esté vacío
function validarNoVacio($valor, $campo) {
    if (trim($valor) === '') {
        throw new Exception("El campo $campo es obligatorio.");
    }
    return trim($valor);
}

// Función para validar correo UTP
function validarCorreoUTP($correo) {
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("El correo electrónico no es válido.");
    }
    if (!preg_match('/@utp\.ac\.pa$/', $correo)) {
        throw new Exception("El correo debe ser institucional (@utp.ac.pa).");
    }
    return $correo;
}

// Función para validar contraseña segura
function validarPassword($password) {
    if (strlen($password) < 8) {
        throw new Exception("La contraseña debe tener al menos 8 caracteres.");
    }
    if (!preg_match('/[A-Z]/', $password)) {
        throw new Exception("La contraseña debe tener al menos una letra mayúscula.");
    }
    if (!preg_match('/[a-z]/', $password)) {
        throw new Exception("La contraseña debe tener al menos una letra minúscula.");
    }
    if (!preg_match('/[0-9]/', $password)) {
        throw new Exception("La contraseña debe tener al menos un número.");
    }
    return $password;
}

// Función para validar coincidencia de contraseñas
function validarCoincidenciaPassword($password, $password2) {
    if ($password !== $password2) {
        throw new Exception("Las contraseñas no coinciden.");
    }
}

// ==============================================
// CLASE PARA NUEVOS DESARROLLOS (métodos estáticos)
// ==============================================

class Validaciones {
    
    // Validar que no esté vacío (método estático)
    public static function noVacio($valor, $campo) {
        if (trim($valor) === '') {
            throw new Exception("El campo $campo es obligatorio.");
        }
        return trim($valor);
    }
    
    // Validar correo UTP (método estático)
    public static function correoUTP($correo) {
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El correo electrónico no es válido.");
        }
        if (!preg_match('/@utp\.ac\.pa$/', $correo)) {
            throw new Exception("El correo debe ser institucional (@utp.ac.pa).");
        }
        return $correo;
    }
    
    // Validar contraseña segura (método estático)
    public static function passwordSegura($password) {
        if (strlen($password) < 8) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres.");
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new Exception("La contraseña debe tener al menos una letra mayúscula.");
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new Exception("La contraseña debe tener al menos una letra minúscula.");
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new Exception("La contraseña debe tener al menos un número.");
        }
        return $password;
    }
    
    // Validar coincidencia de contraseñas (método estático)
    public static function coincidenciaPassword($password, $password2) {
        if ($password !== $password2) {
            throw new Exception("Las contraseñas no coinciden.");
        }
    }
    
    // Sanitizar texto (nueva funcionalidad)
    public static function sanitizarTexto($texto) {
        return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
    }
    
    // Sanitizar número (nueva funcionalidad)
    public static function sanitizarNumero($numero) {
        return filter_var($numero, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    // Sanitizar entero (nueva funcionalidad)
    public static function sanitizarEntero($entero) {
        return filter_var($entero, FILTER_SANITIZE_NUMBER_INT);
    }
    
    // Validar rango numérico (nueva funcionalidad)
    public static function validarRango($valor, $min, $max, $campo) {
        $valor = self::sanitizarNumero($valor);
        if ($valor < $min || $valor > $max) {
            throw new Exception("El campo $campo debe estar entre $min y $max.");
        }
        return $valor;
    }
    
    // Validar fecha (nueva funcionalidad)
    public static function validarFecha($fecha, $formato = 'Y-m-d') {
        $d = DateTime::createFromFormat($formato, $fecha);
        if (!$d || $d->format($formato) !== $fecha) {
            throw new Exception("La fecha no es válida. Use el formato $formato.");
        }
        return $fecha;
    }
    
    // Validar que fecha de fin sea mayor que fecha de inicio (nueva funcionalidad)
    public static function validarRangoFechas($inicio, $fin) {
        if (strtotime($fin) <= strtotime($inicio)) {
            throw new Exception("La fecha de fin debe ser posterior a la fecha de inicio.");
        }
    }
    
    // Validar teléfono (nueva funcionalidad)
    public static function validarTelefono($telefono) {
        if (!preg_match('/^[0-9\-\s\(\)]{7,20}$/', $telefono)) {
            throw new Exception("El teléfono no tiene un formato válido.");
        }
        return $telefono;
    }
    
    // Validar cédula (nueva funcionalidad)
    public static function validarCedula($cedula) {
        if (!preg_match('/^[0-9\-]{8,15}$/', $cedula)) {
            throw new Exception("La cédula debe tener entre 8 y 15 caracteres (números y guiones).");
        }
        return $cedula;
    }
    
    // Validar que un valor esté en una lista (nueva funcionalidad)
    public static function validarEnLista($valor, $lista, $campo) {
        if (!in_array($valor, $lista)) {
            throw new Exception("El valor para $campo no es válido.");
        }
        return $valor;
    }
    
    // Validar URL (nueva funcionalidad)
    public static function validarURL($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("La URL no es válida.");
        }
        return $url;
    }

public static function validarNoVacio($valor, $campo) {
    return self::noVacio($valor, $campo);
}

public static function validarCorreoUTP($correo) {
    return self::correoUTP($correo);
}

public static function validarPassword($password) {
    return self::passwordSegura($password);
}

public static function validarCoincidenciaPassword($password, $password2) {
    return self::coincidenciaPassword($password, $password2);
}
}

?>
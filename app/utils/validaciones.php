<?php
// app/utils/validaciones.php - VERSIÓN COMPLETA CON TODAS LAS VALIDACIONES

// ==============================================
// FUNCIONES ORIGINALES (para compatibilidad)
// ==============================================

function validarNoVacio($valor, $campo) {
    if (trim($valor) === '') {
        throw new Exception("El campo $campo es obligatorio.");
    }
    return trim($valor);
}

function validarCorreoUTP($correo) {
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("El correo electrónico no es válido.");
    }
    if (!preg_match('/@utp\.ac\.pa$/', $correo)) {
        throw new Exception("El correo debe ser institucional (@utp.ac.pa).");
    }
    return $correo;
}

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

function validarCoincidenciaPassword($password, $password2) {
    if ($password !== $password2) {
        throw new Exception("Las contraseñas no coinciden.");
    }
}

// ==============================================
// FUNCIONES NUEVAS PARA REGISTRO
// ==============================================

function validarFormatoCedula($cedula) {
    if (empty($cedula)) {
        return ''; // La cédula es opcional
    }
    
    if (!preg_match('/^[0-9]{1}-[0-9]{3}-[0-9]{3}$/', $cedula)) {
        throw new Exception("La cédula debe tener el formato: 8-XXX-XXX (ej: 8-123-456)");
    }
    return $cedula;
}

function validarFormatoTelefono($telefono) {
    if (empty($telefono)) {
        return ''; // El teléfono es opcional
    }
    
    if (!preg_match('/^[0-9]{4}-[0-9]{4}$/', $telefono)) {
        throw new Exception("El teléfono debe tener el formato: XXXX-XXXX (ej: 6000-1234)");
    }
    return $telefono;
}

function validarUnicidadCorreo($correo, $conexion) {
    $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        throw new Exception("Ya existe una cuenta con ese correo institucional.");
    }
}

function validarUnicidadCedula($cedula, $conexion) {
    if (empty($cedula)) {
        return;
    }
    
    $stmt = $conexion->prepare("SELECT id_estudiante FROM estudiantes WHERE cedula = ?");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        throw new Exception("Ya existe un estudiante registrado con esa cédula.");
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
    
    // Validar teléfono (nueva funcionalidad) - versión mejorada
    public static function validarTelefono($telefono) {
        if (!preg_match('/^[0-9]{4}-[0-9]{4}$/', $telefono)) {
            throw new Exception("El teléfono debe tener formato: XXXX-XXXX (ej: 6000-1234)");
        }
        return $telefono;
    }
    
    // Validar cédula (nueva funcionalidad) - versión mejorada
    public static function validarCedula($cedula) {
        if (!preg_match('/^[0-9]{1}-[0-9]{3}-[0-9]{3}$/', $cedula)) {
            throw new Exception("La cédula debe tener formato: 8-XXX-XXX (ej: 8-123-456)");
        }
        return $cedula;
    }
    
    // Validar formato opcional de cédula (puede estar vacío)
    public static function validarCedulaOpcional($cedula) {
        if (empty(trim($cedula))) {
            return '';
        }
        return self::validarCedula($cedula);
    }
    
    // Validar formato opcional de teléfono (puede estar vacío)
    public static function validarTelefonoOpcional($telefono) {
        if (empty(trim($telefono))) {
            return '';
        }
        return self::validarTelefono($telefono);
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
    
    // Alias para compatibilidad con código existente
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
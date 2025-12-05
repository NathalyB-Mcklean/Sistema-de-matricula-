<?php
// app/utils/validaciones.php
// SISTEMA COMPLETO DE VALIDACIONES - MATRÍCULA UTP

// ============================================
// VALIDACIONES DE USUARIO Y AUTENTICACIÓN
// ============================================

function validarNoVacio($campo, $nombreCampo) {
    if (empty(trim($campo))) {
        throw new Exception("El campo '$nombreCampo' no puede estar vacío.");
    }
}

function validarCorreo($correo) {
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Correo electrónico inválido.");
    }
}

function validarCorreoUTP($correo) {
    validarCorreo($correo);
    
    if (!preg_match("/@utp\.ac\.pa$/", $correo)) {
        throw new Exception("El correo debe ser institucional (@utp.ac.pa).");
    }
}

function validarPassword($password, $fuerte = true) {
    // Validación básica de longitud
    if (strlen($password) < 8) {
        throw new Exception("La contraseña debe tener al menos 8 caracteres.");
    }
    
    // Validación fuerte (activada por defecto)
    if ($fuerte) {
        $errores = [];
        
        // Verificar mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            $errores[] = "al menos una letra mayúscula";
        }
        
        // Verificar minúscula
        if (!preg_match('/[a-z]/', $password)) {
            $errores[] = "al menos una letra minúscula";
        }
        
        // Verificar número
        if (!preg_match('/[0-9]/', $password)) {
            $errores[] = "al menos un número";
        }
        
        // Verificar carácter especial
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errores[] = "al menos un carácter especial (!@#$%^&*)";
        }
        
        // Si hay errores, lanzar excepción con todos los mensajes
        if (!empty($errores)) {
            $mensaje = "La contraseña debe contener: " . implode(", ", $errores);
            throw new Exception($mensaje);
        }
    }
}

function validarCoincidenciaPassword($pass1, $pass2) {
    if ($pass1 !== $pass2) {
        throw new Exception("Las contraseñas no coinciden.");
    }
}

function validarNombre($nombre) {
    if (strlen(trim($nombre)) < 2) {
        throw new Exception("El nombre debe tener al menos 2 caracteres.");
    }
    
    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
        throw new Exception("El nombre solo puede contener letras y espacios.");
    }
}

function validarApellido($apellido) {
    if (strlen(trim($apellido)) < 2) {
        throw new Exception("El apellido debe tener al menos 2 caracteres.");
    }
    
    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellido)) {
        throw new Exception("El apellido solo puede contener letras y espacios.");
    }
}

// ============================================
// VALIDACIONES DE ESTUDIANTE
// ============================================

function validarCedula($cedula) {
    if (!preg_match('/^\d{8,10}$/', $cedula)) {
        throw new Exception("La cédula debe contener entre 8 y 10 dígitos.");
    }
}

function validarTelefono($telefono) {
    // Validación para teléfonos panameños
    if (!preg_match('/^[+]?[\d\s\-\(\)]{7,15}$/', $telefono)) {
        throw new Exception("El teléfono debe ser válido (ej: +507 1234-5678).");
    }
}

function validarFechaNacimiento($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) {
        throw new Exception("La fecha de nacimiento debe tener el formato YYYY-MM-DD.");
    }
    
    // Verificar que sea mayor de 16 años
    $hoy = new DateTime();
    $edad = $hoy->diff($d)->y;
    
    if ($edad < 16) {
        throw new Exception("Debes tener al menos 16 años para registrarte.");
    }
    
    // Verificar que no sea fecha futura
    if (strtotime($fecha) > time()) {
        throw new Exception("La fecha de nacimiento no puede ser futura.");
    }
}

function validarCarrera($carrera) {
    $carrerasPermitidas = [
        'Ingeniería en Sistemas',
        'Ingeniería Civil',
        'Ingeniería Eléctrica',
        'Ingeniería Mecánica',
        'Ingeniería Industrial',
        'Administración de Empresas',
        'Contabilidad',
        'Marketing'
    ];
    
    if (!in_array($carrera, $carrerasPermitidas)) {
        throw new Exception("Carrera no válida.");
    }
}

// ============================================
// VALIDACIONES DE MATRÍCULA
// ============================================

function validarMateriasSeleccionadas($materias) {
    if (!is_array($materias) || count($materias) === 0) {
        throw new Exception("Debes seleccionar al menos una materia.");
    }
    
    if (count($materias) > 5) {
        throw new Exception("No puedes matricular más de 5 materias por semestre.");
    }
}

function validarCreditos($creditos) {
    if ($creditos < 12) {
        throw new Exception("Debes matricular al menos 12 créditos.");
    }
    
    if ($creditos > 18) {
        throw new Exception("No puedes matricular más de 18 créditos.");
    }
}

function validarCupoMateria($inscritos, $cupoMaximo) {
    if ($inscritos >= $cupoMaximo) {
        throw new Exception("No hay cupo disponible en esta materia.");
    }
}

function validarHorario($horarios) {
    // Verificar que no haya conflictos de horario
    $horas = [];
    
    foreach ($horarios as $horario) {
        $partes = explode('-', $horario);
        if (count($partes) !== 2) {
            throw new Exception("Formato de horario inválido.");
        }
        
        $inicio = strtotime($partes[0]);
        $fin = strtotime($partes[1]);
        
        if ($inicio === false || $fin === false || $inicio >= $fin) {
            throw new Exception("Horario inválido: $horario");
        }
        
        // Verificar solapamiento
        foreach ($horas as $rangoExistente) {
            if (!($fin <= $rangoExistente['inicio'] || $inicio >= $rangoExistente['fin'])) {
                throw new Exception("Conflicto de horario detectado.");
            }
        }
        
        $horas[] = ['inicio' => $inicio, 'fin' => $fin];
    }
}

// ============================================
// VALIDACIONES DE PAGOS
// ============================================

function validarMonto($monto) {
    if (!is_numeric($monto) || $monto <= 0) {
        throw new Exception("El monto debe ser un número positivo.");
    }
    
    if ($monto > 10000) {
        throw new Exception("El monto no puede exceder $10,000.");
    }
}

function validarMetodoPago($metodo) {
    $metodosPermitidos = ['tarjeta', 'transferencia', 'efectivo'];
    
    if (!in_array($metodo, $metodosPermitidos)) {
        throw new Exception("Método de pago no válido.");
    }
}

function validarNumeroTarjeta($numero) {
    // Validación básica de tarjeta (16 dígitos)
    $numero = preg_replace('/\s+/', '', $numero);
    
    if (!preg_match('/^\d{16}$/', $numero)) {
        throw new Exception("Número de tarjeta inválido (debe tener 16 dígitos).");
    }
    
    // Algoritmo de Luhn para validar tarjeta
    $sum = 0;
    $alt = false;
    
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $n = intval($numero[$i]);
        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n = ($n % 10) + 1;
            }
        }
        $sum += $n;
        $alt = !$alt;
    }
    
    if ($sum % 10 != 0) {
        throw new Exception("Número de tarjeta inválido.");
    }
}

function validarCVV($cvv) {
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        throw new Exception("CVV inválido (debe tener 3 o 4 dígitos).");
    }
}

function validarFechaExpiracion($mes, $ano) {
    if (!preg_match('/^\d{2}$/', $mes) || $mes < 1 || $mes > 12) {
        throw new Exception("Mes de expiración inválido.");
    }
    
    if (!preg_match('/^\d{4}$/', $ano)) {
        throw new Exception("Año de expiración inválido.");
    }
    
    $hoy = new DateTime();
    $expiracion = DateTime::createFromFormat('Y-m', $ano . '-' . $mes);
    
    if ($expiracion < $hoy) {
        throw new Exception("La tarjeta ha expirado.");
    }
}

// ============================================
// VALIDACIONES DE DOCENTES
// ============================================

function validarTitulo($titulo) {
    $titulosPermitidos = [
        'Licenciado',
        'Ingeniero',
        'Magister',
        'Doctor',
        'Profesor'
    ];
    
    if (!in_array($titulo, $titulosPermitidos)) {
        throw new Exception("Título académico no válido.");
    }
}

function validarEspecialidad($especialidad) {
    if (strlen(trim($especialidad)) < 3) {
        throw new Exception("La especialidad debe tener al menos 3 caracteres.");
    }
}

// ============================================
// VALIDACIONES DE MATERIAS
// ============================================

function validarCodigoMateria($codigo) {
    // Formato: IFS-100, MAT-101, etc.
    if (!preg_match('/^[A-Z]{3}-\d{3}$/', $codigo)) {
        throw new Exception("Código de materia inválido (formato: XXX-999).");
    }
}

function validarNombreMateria($nombre) {
    if (strlen(trim($nombre)) < 5) {
        throw new Exception("El nombre de la materia debe tener al menos 5 caracteres.");
    }
}

function validarCreditosMateria($creditos) {
    if (!is_numeric($creditos) || $creditos < 1 || $creditos > 5) {
        throw new Exception("Los créditos deben estar entre 1 y 5.");
    }
}

// ============================================
// VALIDACIONES DE HORARIOS Y AULAS
// ============================================

function validarAula($aula) {
    // Formato: A-101, B-205, LAB-101
    if (!preg_match('/^[A-Z]{1,3}-\d{3}$/', $aula)) {
        throw new Exception("Aula inválida (formato: X-999 o XXX-999).");
    }
}

function validarDiaSemana($dia) {
    $diasPermitidos = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    
    if (!in_array($dia, $diasPermitidos)) {
        throw new Exception("Día de la semana no válido.");
    }
}

// ============================================
// VALIDACIONES DE CALIFICACIONES
// ============================================

function validarNota($nota) {
    if (!is_numeric($nota) || $nota < 0 || $nota > 100) {
        throw new Exception("La nota debe estar entre 0 y 100.");
    }
}

function validarPorcentaje($porcentaje) {
    if (!is_numeric($porcentaje) || $porcentaje < 0 || $porcentaje > 100) {
        throw new Exception("El porcentaje debe estar entre 0 y 100.");
    }
}

// ============================================
// VALIDACIONES DE ARCHIVOS
// ============================================

function validarArchivo($archivo, $maxSize = 5242880, $tiposPermitidos = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']) {
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error al subir el archivo.");
    }
    
    if ($archivo['size'] > $maxSize) {
        $maxSizeMB = $maxSize / (1024 * 1024);
        throw new Exception("El archivo no puede ser mayor de {$maxSizeMB}MB.");
    }
    
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $tiposPermitidos)) {
        throw new Exception("Tipo de archivo no permitido. Use: " . implode(', ', $tiposPermitidos));
    }
}

// ============================================
// VALIDACIONES DE DIRECCIÓN
// ============================================

function validarDireccion($direccion) {
    if (strlen(trim($direccion)) < 10) {
        throw new Exception("La dirección debe tener al menos 10 caracteres.");
    }
}

function validarCiudad($ciudad) {
    $ciudadesPermitidas = [
        'Panamá',
        'Colón',
        'David',
        'Santiago',
        'Chitré',
        'La Chorrera',
        'Aguadulce'
    ];
    
    if (!in_array($ciudad, $ciudadesPermitidas)) {
        throw new Exception("Ciudad no válida.");
    }
}

// ============================================
// FUNCIONES AUXILIARES DE VALIDACIÓN
// ============================================

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

function calcularFortalezaPassword($password) {
    $puntaje = 0;
    $feedback = [];
    
    if (strlen($password) >= 8) $puntaje += 1;
    else $feedback[] = "Longitud mínima 8 caracteres";
    
    if (preg_match('/[A-Z]/', $password)) $puntaje += 1;
    else $feedback[] = "Agregar una letra mayúscula";
    
    if (preg_match('/[a-z]/', $password)) $puntaje += 1;
    else $feedback[] = "Agregar una letra minúscula";
    
    if (preg_match('/[0-9]/', $password)) $puntaje += 1;
    else $feedback[] = "Agregar un número";
    
    if (preg_match('/[^A-Za-z0-9]/', $password)) $puntaje += 1;
    else $feedback[] = "Agregar un carácter especial (!@#$%^&*)";
    
    if ($puntaje <= 2) {
        $nivel = 'débil';
        $color = '#f44336';
    } elseif ($puntaje <= 3) {
        $nivel = 'media';
        $color = '#ff9800';
    } else {
        $nivel = 'fuerte';
        $color = '#2d8659';
    }
    
    return [
        'puntaje' => $puntaje,
        'maximo' => 5,
        'nivel' => $nivel,
        'color' => $color,
        'feedback' => $feedback
    ];
}

function limpiarEntrada($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    return $dato;
}

// ============================================
// VALIDACIONES DE FECHAS Y PERÍODOS
// ============================================

function validarFecha($fecha, $formato = 'Y-m-d') {
    $d = DateTime::createFromFormat($formato, $fecha);
    if (!$d || $d->format($formato) !== $fecha) {
        throw new Exception("La fecha debe tener el formato $formato.");
    }
    return $d;
}

function validarFechaFutura($fecha, $formato = 'Y-m-d') {
    $d = validarFecha($fecha, $formato);
    $hoy = new DateTime();
    
    if ($d < $hoy) {
        throw new Exception("La fecha debe ser futura.");
    }
}

function validarFechaPasada($fecha, $formato = 'Y-m-d') {
    $d = validarFecha($fecha, $formato);
    $hoy = new DateTime();
    
    if ($d > $hoy) {
        throw new Exception("La fecha no puede ser futura.");
    }
}

function validarPeriodoAcademico($periodo) {
    // Formato: 2025-1 (año-semestre)
    if (!preg_match('/^\d{4}-[12]$/', $periodo)) {
        throw new Exception("Período académico inválido (formato: YYYY-S).");
    }
}

// ============================================
// VALIDACIONES DE NÚMEROS Y CANTIDADES
// ============================================

function validarNumero($numero, $min = null, $max = null) {
    if (!is_numeric($numero)) {
        throw new Exception("Debe ser un número válido.");
    }
    
    if ($min !== null && $numero < $min) {
        throw new Exception("El número no puede ser menor que $min.");
    }
    
    if ($max !== null && $numero > $max) {
        throw new Exception("El número no puede ser mayor que $max.");
    }
}

function validarTexto($texto, $min = 1, $max = 1000) {
    $longitud = strlen(trim($texto));
    
    if ($longitud < $min) {
        throw new Exception("El texto debe tener al menos $min caracteres.");
    }
    
    if ($longitud > $max) {
        throw new Exception("El texto no puede tener más de $max caracteres.");
    }
}

function validarSeleccion($valor, $opcionesValidas) {
    if (!in_array($valor, $opcionesValidas)) {
        throw new Exception("Selección inválida.");
    }
}
?>
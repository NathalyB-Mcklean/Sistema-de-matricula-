<?php
// config/constants.php - Constantes globales de la aplicación

// Constantes de aplicación
define('APP_NAME', 'Sistema de Matrícula UTP');
define('APP_VERSION', '1.0.0');
define('APP_YEAR', date('Y'));

// Constantes de roles (coinciden con ENUM en BD)
define('ROL_ADMIN', 'admin');
define('ROL_ESTUDIANTE', 'estudiante');
// NOTA: No hay ROL_DOCENTE porque docente es atributo

// Constantes de estados
define('ESTADO_ACTIVO', 'activo');
define('ESTADO_INACTIVO', 'inactivo');
define('ESTADO_PENDIENTE', 'pendiente');
define('ESTADO_APROBADO', 'aprobado');
define('ESTADO_CANCELADO', 'cancelado');

// Constantes de matrícula
define('MATRICULA_MAX_MATERIAS', 5); // Máximo de materias por semestre
define('MATRICULA_COSTO_BASE', 25.00); // Costo base de matrícula
define('MATRICULA_FECHA_INICIO', '2024-01-15'); // Inicio del período
define('MATRICULA_FECHA_FIN', '2024-01-30'); // Fin del período

// Constantes académicas
define('CREDITOS_MAXIMOS', 18); // Máximo de créditos por semestre
define('CREDITOS_MINIMOS', 12); // Mínimo de créditos por semestre
define('NOTA_MINIMA', 71); // Nota mínima para aprobar
define('ASISTENCIA_MINIMA', 70); // % mínimo de asistencia

// Constantes de archivos
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_DIR', 'uploads/');

// Constantes de seguridad
define('SESSION_TIMEOUT', 1800); // 30 minutos en segundos
define('MAX_LOGIN_ATTEMPTS', 5); // Intentos máximos de login
define('PASSWORD_MIN_LENGTH', 8); // Longitud mínima de contraseña

// Constantes de correo
define('EMAIL_FROM', 'no-reply@utp.ac.pa');
define('EMAIL_FROM_NAME', 'Sistema de Matrícula UTP');
define('EMAIL_SMTP_HOST', 'smtp.utp.ac.pa');
define('EMAIL_SMTP_PORT', 587);

// Constantes de pagos
define('MONEDA', 'USD');
define('IVA_PORCENTAJE', 7); // 7% de ITBMS
define('DESCUENTO_BECA', 25); // 25% de descuento por beca

// Constantes de días/horarios
define('DIAS_SEMANA', ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']);
define('HORARIOS_DISPONIBLES', [
    '07:00-09:00',
    '09:00-11:00', 
    '11:00-13:00',
    '14:00-16:00',
    '16:00-18:00',
    '18:00-20:00'
]);

// Constantes de encuesta
define('ENCUESTA_OPCIONES', [
    'Excelente' => 5,
    'Conforme' => 4,
    'Regular' => 3,
    'Inconforme' => 2,
    'No respondida' => 1
]);

// Constantes de auditoría
define('ACCION_CREAR', 'CREAR');
define('ACCION_ACTUALIZAR', 'ACTUALIZAR');
define('ACCION_ELIMINAR', 'ELIMINAR');
define('ACCION_LOGIN', 'INICIAR_SESION');
define('ACCION_LOGOUT', 'CERRAR_SESION');

// Mensajes constantes
define('MSG_EXITO', 'Operación realizada con éxito');
define('MSG_ERROR', 'Ocurrió un error. Intente nuevamente');
define('MSG_NO_AUTORIZADO', 'No tiene permisos para realizar esta acción');
define('MSG_NO_ENCONTRADO', 'Recurso no encontrado');
define('MSG_SESION_EXPIRADA', 'Su sesión ha expirado');

// URLs importantes
define('URL_BASE', 'http://localhost/matricula/');
define('URL_PUBLIC', URL_BASE . 'public/');
define('URL_ASSETS', URL_PUBLIC . 'assets/');
define('URL_CSS', URL_ASSETS . 'css/');
define('URL_JS', URL_ASSETS . 'js/');
define('URL_IMG', URL_ASSETS . 'img/');

// Rutas físicas
define('DIR_BASE', dirname(dirname(__FILE__)) . '/');
define('DIR_APP', DIR_BASE . 'app/');
define('DIR_PUBLIC', DIR_BASE . 'public/');
define('DIR_UPLOADS', DIR_PUBLIC . 'uploads/');
?>
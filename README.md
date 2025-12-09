# Sistema de Matrícula Académica

![Tecnologías](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white)
![Tecnologías](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)
![Tecnologías](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?logo=javascript&logoColor=black)
![Tecnologías](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)
![Tecnologías](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![Estado](https://img.shields.io/badge/Estado-Funcional-brightgreen)

## 📋 Descripción General

Sistema web completo para la gestión del proceso de matrícula académica en instituciones de educación superior. La plataforma permite a los estudiantes realizar su inscripción de manera autónoma y a los administradores gestionar toda la oferta académica de forma centralizada.

## ✨ Características Principales

### 👨‍🎓 Para Estudiantes
- **Registro e inicio de sesión seguro** con correo institucional
- **Consulta de oferta académica** filtrada por carrera y período
- **Matrícula en línea** con detección automática de conflictos de horario
- **Visualización de horario personal** semanal
- **Encuesta de satisfacción** para retroalimentación del sistema

### 👨‍💼 Para Administradores
- **Gestión completa de usuarios** (estudiantes y administradores)
- **Configuración de carreras** y planes de estudio
- **Catálogo de docentes** con información profesional
- **Gestión de materias**, horarios y grupos
- **Control de períodos académicos** y habilitación de matrícula
- **Sistema de reportes** y estadísticas detalladas
- **Auditoría completa** de todas las acciones del sistema

## 🏗️ Arquitectura Técnica

### Stack Tecnológico
- **Backend**: PHP nativo (sin frameworks)
- **Frontend**: HTML5, CSS3, JavaScript vanilla
- **Base de datos**: MySQL 8.0+
- **Servidor**: Apache/nginx compatible

### Estructura del Proyecto
```
Sistema-de-matricula-/
│
├── app/
│   ├── config/          # Configuración y conexión a BD
│   ├── utils/           # Utilidades y validaciones
│   └── views/           # Vistas PHP organizadas por rol
│       ├── admin/       # Panel administrativo
│       └── auth/        # Autenticación
│
├── estudiante/          # Interfaz estudiantil completa
│   ├── css/
│   ├── partials/
│   └── módulos funcionales
│
└── public/
    └── assets/          # Recursos estáticos
```

## 🗄️ Base de Datos

### Esquema Principal
El sistema utiliza 13 tablas interconectadas, normalizadas hasta la 3NF:

1. **usuarios** - Autenticación y roles
2. **estudiantes** - Información académica
3. **docentes** - Catálogo de profesores
4. **carreras** - Programas académicos
5. **materias** - Catálogo de asignaturas
6. **horarios** - Bloques de tiempo
7. **grupos_horarios_materia** - Asociación materias-horarios
8. **periodos_academicos** - Ciclos académicos
9. **grupos_periodo** - Activación de grupos por período
10. **matriculas** - Registro de inscripciones
11. **plan_estudios** - Secuencia curricular
12. **encuestas** - Retroalimentación de usuarios
13. **auditoria** - Trazabilidad de acciones

### Características de la BD
- **Normalización**: Hasta Tercera Forma Normal (3NF)
- **Integridad referencial**: Claves foráneas y restricciones
- **Transacciones**: Para operaciones críticas como matrícula
- **Backup**: Recomendación de respaldos automáticos

## 🚀 Instalación y Configuración

### Requisitos Previos
- PHP 8.0 o superior
- MySQL 8.0 o MariaDB 10.4+
- Servidor web (Apache/nginx)
- Composer (opcional, para posibles dependencias)

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/tu-usuario/sistema-matricula.git
cd sistema-matricula
```

2. **Configurar base de datos**
```sql
-- Importar el esquema desde el archivo SQL
mysql -u usuario -p nombre_base_datos < database/schema.sql
```

3. **Configurar conexión a BD**
Editar `app/config/conexion.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'matricula');
define('DB_PASS', 'matricula123');
define('DB_NAME', 'matricula');
```

4. **Configurar servidor web**
```apache
# Ejemplo para Apache
DocumentRoot "/var/www/html/sistema-matricula"
<Directory "/var/www/html/sistema-matricula">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```



5. **Acceder al sistema**
- URL: `http://localhost/sistema-matricula`
- Credenciales iniciales: Verificar archivo `INSTALL.md`

## 🔧 Módulos Principales

### 🔐 Autenticación y Sesiones
- Sistema dual de validación (cliente y servidor)
- Roles diferenciados (estudiante/administrador)
- Registro automático con asignación de carrera
- Sesiones seguras con tiempo de expiración

### 📊 Panel Administrativo
- Dashboard con métricas en tiempo real
- Gestión completa de entidades (CRUD)
- Sistema de auditoría con filtros avanzados
- Exportación de reportes a CSV

### 🎓 Proceso de Matrícula
- Consulta de materias disponibles
- Validación multinivel de horarios
- Control de cupos en tiempo real
- Transacciones atómicas para integridad

### 📝 Sistema de Encuestas
- Evaluación de satisfacción post-matrícula
- Análisis de retroalimentación cualitativa
- Estadísticas de percepción del servicio

## 🛡️ Seguridad

### Medidas Implementadas
- **Autenticación**: Contraseñas hasheadas con `password_hash()`
- **Protección SQL**: Consultas preparadas con MySQLi
- **Validación**: Triple capa (cliente, servidor, BD)
- **XSS Prevention**: Escape de salida con `htmlspecialchars()`
- **Control de acceso**: Verificación de roles en cada módulo
- **Auditoría**: Registro de todas las acciones críticas

### Prácticas Recomendadas
- Usar HTTPS en producción
- Implementar límites de intentos de login
- Actualizar regularmente las credenciales de BD
- Monitorear el archivo de auditoría

## 📈 Rendimiento y Escalabilidad

### Optimizaciones Actuales
- Consultas optimizadas con índices apropiados
- Uso de transacciones para operaciones agrupadas
- Caché implícito en consultas frecuentes
- Estructura modular para fácil mantenimiento

### Consideraciones para Escala
- Implementar caché de consultas (Redis/Memcached)
- Separar servidor de BD en instancia dedicada
- Considerar balanceo de carga para múltiples servidores web
- Implementar sistema de colas para operaciones pesadas

## 🤝 Contribución

### Desarrollo Local
1. Fork del repositorio
2. Crear rama de características
```bash
git checkout -b feature/nueva-funcionalidad
```
3. Realizar cambios y pruebas
4. Commit siguiendo convenciones
```bash
git commit -m "feat: añade validación de prerrequisitos"
```
5. Push y Pull Request

### Convenciones de Código
- **PHP**: PSR-12 coding standard
- **JavaScript**: ES6+ con funciones descriptivas
- **CSS**: BEM methodology para componentes
- **Comentarios**: Documentar funciones complejas
- **Commits**: Conventional commits

```


## 👥 Autores

ABREGO, ABDIEL
BONILLA, 
FÉLIX, EIMY 
GREEN, AMANDA 


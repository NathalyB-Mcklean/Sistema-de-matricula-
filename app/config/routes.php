<?php
// config/routes.php - Mapeo de URLs a controladores

return [
    // Públicas
    '' => ['controller' => 'PublicController', 'action' => 'index'],
    'login' => ['controller' => 'AuthController', 'action' => 'login'],
    'registro' => ['controller' => 'AuthController', 'action' => 'registro'],
    'encuesta' => ['controller' => 'PublicController', 'action' => 'encuesta'],
    
    // Admin
    'admin' => ['controller' => 'AdminController', 'action' => 'dashboard'],
    'admin/usuarios' => ['controller' => 'AdminController', 'action' => 'usuarios'],
    'admin/materias' => ['controller' => 'AdminController', 'action' => 'materias'],
    
    // Estudiante
    'estudiante' => ['controller' => 'EstudianteController', 'action' => 'dashboard'],
    'estudiante/matricula' => ['controller' => 'MatriculaController', 'action' => 'index'],
    
    // API (si fuera necesario)
    'api/materias' => ['controller' => 'ApiController', 'action' => 'materias'],
];
?>
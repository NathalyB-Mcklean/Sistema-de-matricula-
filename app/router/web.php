<?php
// app/routes/web.php

session_start();

// Definir rutas
$request = $_SERVER['REQUEST_URI'];
$base_path = '/Sistema-de-matricula-/app';

// Quitar el base_path si existe
$request = str_replace($base_path, '', $request);

// Limpiar parámetros GET
$request = strtok($request, '?');

// Rutas principales
switch ($request) {
    case '/admin/dashboard':
        require_once '../views/admin/dashboard.php';
        break;
        
    case '/admin/estudiantes':
        $controller = new EstudianteController();
        $controller->index();
        break;
        
    case '/admin/estudiantes/create':
        $controller = new EstudianteController();
        $controller->create();
        break;
        
    case '/admin/estudiantes/store':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $controller = new EstudianteController();
            $controller->store();
        }
        break;
        
    case '/admin/estudiantes/edit':
        if (isset($_GET['id'])) {
            $controller = new EstudianteController();
            $controller->edit($_GET['id']);
        }
        break;
        
    case '/admin/estudiantes/update':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $controller = new EstudianteController();
            $controller->update($_POST['id']);
        }
        break;
        
    case '/admin/estudiantes/destroy':
        if (isset($_GET['id'])) {
            $controller = new EstudianteController();
            $controller->destroy($_GET['id']);
        }
        break;
        
    // Rutas de autenticación
    case '/auth/login':
        $controller = new AuthController();
        $controller->login();
        break;
        
    case '/auth/logout':
        $controller = new AuthController();
        $controller->logout();
        break;
        
    default:
        http_response_code(404);
        echo "Página no encontrada";
        break;
}
?>
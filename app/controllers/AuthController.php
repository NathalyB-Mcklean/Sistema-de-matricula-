<?php
// app/controllers/AuthController.php

class AuthController {
    
    public function login() {
        // Si ya está logueado, redirigir
        if (isset($_SESSION['user_id'])) {
            $role = $_SESSION['user_role'];
            header('Location: ../../public/index.php?page=' . $role);
            exit();
        }
        
        // Incluir la vista
        require_once __DIR__ . '/../views/auth/login.php';
    }
    
    public function registro() {
        require_once __DIR__ . '/../views/auth/registro.php';
    }
    
    public function logout() {
        session_destroy();
        header('Location: ../../public/index.php?page=login');
        exit();
    }
}
?>
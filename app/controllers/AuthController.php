<?php
// app/controllers/AuthController.php
session_start();
require_once '../models/UsuarioModel.php';
require_once '../utils/validaciones.php';

class AuthController {
    private $model;
    
    public function __construct() {
        $this->model = new UsuarioModel();
    }
    
    // LOGIN
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $errores = validarLogin($_POST['correo'] ?? '', $_POST['password'] ?? '');
                
                if (!empty($errores)) {
                    throw new Exception(implode(', ', $errores));
                }
                
                $usuario = $this->model->authenticate(
                    limpiarEntrada($_POST['correo']),
                    $_POST['password']
                );
                
                if ($usuario) {
                    $_SESSION['user_id'] = $usuario['id_usuario'];
                    $_SESSION['user_name'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
                    $_SESSION['user_role'] = $usuario['rol'];
                    $_SESSION['user_email'] = $usuario['correo'];
                    
                    // Redirigir según rol
                    if ($usuario['rol'] == 'admin') {
                        header('Location: /admin/dashboard');
                    } else {
                        header('Location: /estudiante/dashboard');
                    }
                } else {
                    throw new Exception('Credenciales incorrectas');
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /auth/login');
            }
            exit();
        }
    }
    
    // LOGOUT
    public function logout() {
        session_destroy();
        header('Location: /auth/login');
        exit();
    }
}
<?php
// app/controllers/CarreraController.php
session_start();
require_once '../models/CarreraModel.php';
require_once '../utils/validaciones.php';

class CarreraController {
    private $model;
    
    public function __construct() {
        $this->model = new CarreraModel();
    }
    
    // CREAR CARRERA
    public function store() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                validarNoVacio($_POST['codigo'], 'Código');
                validarNoVacio($_POST['nombre'], 'Nombre');
                validarNumero($_POST['duracion_semestres'], 1, 20);
                validarNumero($_POST['creditos_totales'], 1, 300);
                validarTexto($_POST['descripcion'] ?? '', 0, 500);
                
                $data = [
                    'codigo' => limpiarEntrada($_POST['codigo']),
                    'nombre' => limpiarEntrada($_POST['nombre']),
                    'descripcion' => limpiarEntrada($_POST['descripcion'] ?? ''),
                    'duracion_semestres' => intval($_POST['duracion_semestres']),
                    'creditos_totales' => intval($_POST['creditos_totales']),
                    'estado' => limpiarEntrada($_POST['estado'] ?? 'activa')
                ];
                
                if ($this->model->create($data)) {
                    $_SESSION['success'] = 'Carrera creada exitosamente';
                } else {
                    throw new Exception('Error al crear la carrera');
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
            
            header('Location: /admin/carreras');
            exit();
        }
    }
    
    // ELIMINAR CARRERA (CON VALIDACIÓN)
    public function destroy($id) {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        try {
            // Verificar si tiene materias asociadas
            if ($this->model->hasMaterias($id)) {
                throw new Exception('No se puede eliminar la carrera porque tiene materias asociadas');
            }
            
            // Verificar si tiene estudiantes
            if ($this->model->hasEstudiantes($id)) {
                throw new Exception('No se puede eliminar la carrera porque tiene estudiantes inscritos');
            }
            
            if ($this->model->delete($id)) {
                $_SESSION['success'] = 'Carrera eliminada exitosamente';
            } else {
                throw new Exception('Error al eliminar la carrera');
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /admin/carreras');
        exit();
    }
}
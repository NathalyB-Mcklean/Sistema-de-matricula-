<?php
// app/controllers/MatriculaController.php
session_start();
require_once '../models/MatriculaModel.php';
require_once '../models/EstudianteModel.php';
require_once '../models/GrupoModel.php';
require_once '../utils/validaciones.php';

class MatriculaController {
    private $model;
    private $estudianteModel;
    private $grupoModel;
    
    public function __construct() {
        $this->model = new MatriculaModel();
        $this->estudianteModel = new EstudianteModel();
        $this->grupoModel = new GrupoModel();
    }
    
    // PROCESAR MATRÍCULA COMPLETA
    public function store() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Validaciones
                validarNoVacio($_POST['id_estudiante'], 'Estudiante');
                validarNoVacio($_POST['id_periodo'], 'Período');
                validarNoVacio($_POST['id_grupo'], 'Grupo');
                
                $data = [
                    'id_estudiante' => intval($_POST['id_estudiante']),
                    'id_periodo' => intval($_POST['id_periodo']),
                    'id_grupo' => intval($_POST['id_grupo']),
                    'fecha' => date('Y-m-d H:i:s'),
                    'estado' => 'activa'
                ];
                
                // Verificar cupo disponible
                if (!$this->grupoModel->tieneCupo($data['id_grupo'])) {
                    throw new Exception('No hay cupo disponible en este grupo');
                }
                
                // Verificar que no esté ya matriculado en este grupo
                if ($this->model->yaMatriculado($data['id_estudiante'], $data['id_grupo'])) {
                    throw new Exception('El estudiante ya está matriculado en este grupo');
                }
                
                // Crear matrícula
                if ($this->model->create($data)) {
                    // Actualizar cupo del grupo
                    $this->grupoModel->incrementarInscritos($data['id_grupo']);
                    
                    $_SESSION['success'] = 'Matrícula realizada exitosamente';
                    header('Location: /admin/matriculas?estudiante=' . $data['id_estudiante']);
                } else {
                    throw new Exception('Error al realizar la matrícula');
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/matriculas'));
            }
            exit();
        }
    }
    
    // ELIMINAR MATRÍCULA
    public function destroy($id) {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        try {
            $matricula = $this->model->getById($id);
            
            if (!$matricula) {
                throw new Exception('Matrícula no encontrada');
            }
            
            if ($this->model->delete($id)) {
                // Disminuir cupo del grupo
                $this->grupoModel->decrementarInscritos($matricula['id_grupo']);
                
                $_SESSION['success'] = 'Matrícula eliminada exitosamente';
            } else {
                throw new Exception('Error al eliminar la matrícula');
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: ' . ($_GET['redirect'] ?? '/admin/matriculas'));
        exit();
    }
}
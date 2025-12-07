<?php
// app/controllers/PeriodoController.php
session_start();
require_once '../models/PeriodoModel.php';
require_once '../utils/validaciones.php';

class PeriodoController {
    private $model;
    
    public function __construct() {
        $this->model = new PeriodoModel();
    }
    
    // ACTIVAR PERÍODO (SOLO UNO ACTIVO A LA VEZ)
    public function activate($id) {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        try {
            // Desactivar todos primero
            $this->model->deactivateAll();
            
            // Activar el seleccionado
            if ($this->model->activate($id)) {
                $_SESSION['success'] = 'Período activado exitosamente';
            } else {
                throw new Exception('Error al activar el período');
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /admin/periodos');
        exit();
    }
    
    // ELIMINAR PERÍODO (CON VALIDACIÓN)
    public function destroy($id) {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        try {
            // Verificar si tiene matrículas
            if ($this->model->hasMatriculas($id)) {
                // Solo marcar como inactivo
                $this->model->updateEstado($id, 'inactivo');
                $_SESSION['success'] = 'Período marcado como inactivo (tiene matrículas)';
            } else {
                // Eliminar completamente
                if ($this->model->delete($id)) {
                    $_SESSION['success'] = 'Período eliminado exitosamente';
                } else {
                    throw new Exception('Error al eliminar el período');
                }
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /admin/periodos');
        exit();
    }
}
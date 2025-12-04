<?php
namespace App\Controllers;

use Utils\Session;
use App\Models\UsuarioModel;
use App\Models\MateriaModel;
use App\Models\CarreraModel;

class AdminController {
    
    private $usuarioModel;
    private $materiaModel;
    private $carreraModel;
    
    public function __construct() {
        Session::requireRole('admin');
        $this->usuarioModel = new UsuarioModel();
        $this->materiaModel = new MateriaModel();
        $this->carreraModel = new CarreraModel();
    }
    
    public function dashboard() {
        $data = [
            'totalUsuarios' => count($this->usuarioModel->listar()),
            'totalMaterias' => count($this->materiaModel->listar()),
            'totalCarreras' => count($this->carreraModel->listar())
        ];
        $this->view('admin/dashboard', $data);
    }
    
    public function materias() {
        $materias = $this->materiaModel->listar();
        $carreras = $this->carreraModel->listar();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $datos = [
                    'nombre' => $_POST['nombre'],
                    'descripcion' => $_POST['descripcion'],
                    'costo' => $_POST['costo'],
                    'docente' => $_POST['docente'],  // Solo nombre
                    'id_carrera' => $_POST['id_carrera']
                ];
                
                if (isset($_POST['id_materia'])) {
                    // Actualizar
                    $this->materiaModel->actualizar($_POST['id_materia'], $datos);
                    Session::setFlash('success', 'Materia actualizada');
                } else {
                    // Crear
                    $this->materiaModel->crear($datos);
                    Session::setFlash('success', 'Materia creada');
                }
                
                header('Location: ?page=admin&action=materias');
                exit();
                
            } catch (\Exception $e) {
                Session::setFlash('error', $e->getMessage());
            }
        }
        
        $this->view('admin/materias', [
            'materias' => $materias,
            'carreras' => $carreras
        ]);
    }
    
    public function usuarios() {
        $usuarios = $this->usuarioModel->listar();
        $this->view('admin/usuarios', ['usuarios' => $usuarios]);
    }
    
    private function view($view, $data = []) {
        extract($data);
        require __DIR__ . "/../views/$view.php";
    }
}
?>
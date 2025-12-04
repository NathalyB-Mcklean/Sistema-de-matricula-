<?php
require_once("../database/conexion.php");
require_once("../validaciones/validaciones.php");

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {

        // Obtener valores
        $nombre     = trim($_POST["nombre"]);
        $apellido   = trim($_POST["apellido"]);
        $correo     = trim($_POST["correo"]);
        $password   = trim($_POST["password"]);
        $password2  = trim($_POST["password2"]);
        $rol        = "estudiante";   // ROL FIJO como estudiante

        // ------ VALIDACIONES -------
        validarNoVacio($nombre, "nombre");
        validarNoVacio($apellido, "apellido");
        validarCorreoUTP($correo);
        validarPassword($password);
        validarCoincidenciaPassword($password, $password2);

        // ------- ENCRIPTAR CONTRASEÑA -------
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // ------- VERIFICAR SI YA EXISTE -------
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            throw new Exception("Ya existe una cuenta con ese correo institucional.");
        }

        // ------- INSERTAR USUARIO --------
        $insert = $conexion->prepare("
            INSERT INTO usuario (nombre, apellido, correo, password, rol)
            VALUES (?, ?, ?, ?, ?)
        ");

        $insert->bind_param("sssss", $nombre, $apellido, $correo, $passwordHash, $rol);

        if ($insert->execute()) {
            $mensaje = "<p style='color:green; font-weight:bold;'>Usuario registrado exitosamente.</p>";
        } else {
            throw new Exception("Error al registrar usuario. Intenta más tarde.");
        }

    } catch (Exception $e) {
        $mensaje = "<p style='color:red; font-weight:bold;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
</head>
<body>

<h2>Registro de Usuario</h2>

<form method="POST" action="">

    <label>Nombre:</label><br>
    <input type="text" name="nombre" placeholder="Nombre" required><br><br>

    <label>Apellido:</label><br>
    <input type="text" name="apellido" placeholder="Apellido" required><br><br>

    <label>Correo institucional:</label><br>
    <input type="email" name="correo" placeholder="correo@utp.ac.pa" required><br><br>

    <label>Contraseña:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Repetir contraseña:</label><br>
    <input type="password" name="password2" required><br><br>

    <button type="submit">Registrarse</button>
</form>

<?php echo $mensaje; ?>

</body>
</html>
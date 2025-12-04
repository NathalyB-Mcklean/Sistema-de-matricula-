<?php
session_start();
require_once("../database/conexion.php");
require_once("../validaciones/validaciones.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $correo = $_POST["correo"] ?? "";
    $password = $_POST["password"] ?? "";

    // Validación mínima
    $errores = validarLogin($correo, $password);

    if (count($errores) > 0) {
        $_SESSION["error"] = implode("<br>", $errores);
        header("Location: login.php");
        exit();
    }

    try {
        // Buscar usuario por correo - CORREGIR: 'usuario' en lugar de 'usuarios'
        $sql = "SELECT * FROM usuario WHERE correo = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 0) {
            $_SESSION["error"] = "Correo o contraseña incorrectos.";
            header("Location: login.php");
            exit();
        }

        $usuario = $resultado->fetch_assoc();

        // Verificar hash
        if (!password_verify($password, $usuario["password"])) {
            $_SESSION["error"] = "Correo o contraseña incorrectos.";
            header("Location: login.php");
            exit();
        }

        // Crear sesión
        $_SESSION["usuario_id"] = $usuario["id_usuario"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["rol"] = $usuario["rol"];

        // Redirigir dependiendo del rol
        if ($usuario["rol"] === "admin") {
            header("Location: panel_admin.php");
        } else {
            header("Location: panel_estudiante.php");
        }
        exit();

    } catch (Exception $e) {
        $_SESSION["error"] = "Error interno: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Iniciar Sesión</title>
</head>
<body>

<h2>Iniciar Sesión</h2>

<?php
if (isset($_SESSION["error"])) {
    echo "<p style='color:red'>" . $_SESSION["error"] . "</p>";
    unset($_SESSION["error"]);
}
?>

<form action="login.php" method="POST">
    <label>Correo institucional:</label><br>
    <input type="email" name="correo" placeholder="correo@utp.ac.pa" required><br><br>

    <label>Contraseña:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Ingresar</button>
</form>

</body>
</html>
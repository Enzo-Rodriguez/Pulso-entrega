<?php
header('Content-Type: application/json');
require_once 'conexion.php';
session_start();

$usuario = $_POST['usuario'] ?? '';
$contrasenia = $_POST['contrasenia'] ?? '';

$stmt = $con->prepare("SELECT id_usuario, id_empleado, nombre_usuario, nombre_completo, email, rol, password_hash FROM usuarios_sistema WHERE nombre_usuario = ?");
$stmt->bind_param('s', $usuario);
$stmt->execute();

$resultado = $stmt->get_result();


if ($resultado->num_rows === 0) {
    echo json_encode(['error' => 'El usuario no existe']);
    exit;
}

$fila = $resultado->fetch_assoc();

if (password_verify($contrasenia, $fila['password_hash'])) {

    $_SESSION['id_empleado'] = $fila['id_empleado'];
    $_SESSION['usuario']     = $fila['nombre_usuario'];
    $_SESSION['nombre']      = $fila['nombre_completo'];
    $_SESSION['email']       = $fila['email'];
    $_SESSION['rol']         = $fila['rol'];
    $_SESSION['id_usuario']  = $fila['id_usuario'];
    

    unset($fila['password_hash']);

    echo json_encode([
        'exito' => true,
        'usuario' => $fila
    ]);
} else {
    echo json_encode(['error' => 'Contraseña incorrecta']);
}


$stmt->close();
$con->close();
?>
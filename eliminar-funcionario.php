<?php

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$idFuncionario = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($idFuncionario === false || $idFuncionario === null || $idFuncionario < 1) {
    http_response_code(400);
    die("La ID del funcionario no es válida.");
}

// Buscar la persona asociada al funcionario
$consulta = $conexion->prepare(
    "SELECT id_persona
     FROM funcionario
     WHERE id_funcionario = ?"
);

$consulta->bind_param("i", $idFuncionario);
$consulta->execute();

$resultado = $consulta->get_result();
$funcionario = $resultado->fetch_assoc();

$consulta->close();

if (!$funcionario) {
    $conexion->close();
    http_response_code(404);
    die("El funcionario no existe.");
}

$idPersona = $funcionario["id_persona"];

$conexion->begin_transaction();

try {

    // Primero eliminar el funcionario
    $eliminarFuncionario = $conexion->prepare(
        "DELETE FROM funcionario WHERE id_funcionario = ?"
    );

    $eliminarFuncionario->bind_param("i", $idFuncionario);
    $eliminarFuncionario->execute();
    $eliminarFuncionario->close();

    // Después eliminar la persona asociada
    $eliminarPersona = $conexion->prepare(
        "DELETE FROM persona WHERE id_persona = ?"
    );

    $eliminarPersona->bind_param("i", $idPersona);
    $eliminarPersona->execute();
    $eliminarPersona->close();

    // Confirmar cambios
    $conexion->commit();

    $conexion->close();

    header("Location: funcionario.php");
    exit;

} catch (Exception $e) {

    // Si algo falla deshacer los cambios
    $conexion->rollback();

    $conexion->close();

    http_response_code(500);
    die("No se pudo eliminar el funcionario.");
}
?>
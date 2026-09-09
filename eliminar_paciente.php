<?php

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$idPaciente = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($idPaciente === false || $idPaciente === null || $idPaciente < 1) {
    http_response_code(400);
    die("La ID del paciente no es válida.");
}

// Buscar la persona asociada al paciente
$consulta = $conexion->prepare(
    "SELECT id_persona
     FROM paciente
     WHERE id_paciente = ?"
);

$consulta->bind_param("i", $idPaciente);
$consulta->execute();

$resultado = $consulta->get_result();
$paciente = $resultado->fetch_assoc();

$consulta->close();

if (!$paciente) {
    $conexion->close();
    http_response_code(404);
    die("El paciente no existe.");
}

$idPersona = $paciente["id_persona"];

$conexion->begin_transaction();

try {

    // Primero eliminar el paciente
    $eliminarPaciente = $conexion->prepare(
        "DELETE FROM paciente WHERE id_paciente = ?"
    );

    $eliminarPaciente->bind_param("i", $idPaciente);
    $eliminarPaciente->execute();
    $eliminarPaciente->close();

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

    header("Location: pacientes.php");
    exit;

} catch (Exception $e) {

    // Si algo falla deshacer los cambios
    $conexion->rollback();

    $conexion->close();

    http_response_code(500);
    die("No se pudo eliminar el paciente.");
}
?>
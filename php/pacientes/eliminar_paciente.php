<?php

require_once "../conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$idPaciente = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($idPaciente === false || $idPaciente === null || $idPaciente < 1) {
    http_response_code(400);
    die("La ID del paciente no es válida.");
}

$resultado = $conexion->query(
    "SELECT id_persona FROM paciente WHERE id_paciente = $idPaciente"
);
$paciente = $resultado->fetch_assoc();

if (!$paciente) {
    $conexion->close();
    http_response_code(404);
    die("El paciente no existe.");
}

$idPersona = $paciente["id_persona"];

$conexion->begin_transaction();

try {
    // Se borra primero paciente porque depende de persona.
    $conexion->query("DELETE FROM paciente WHERE id_paciente = $idPaciente");
    $conexion->query("DELETE FROM persona WHERE id_persona = $idPersona");
    $conexion->commit();
    $conexion->close();
    header("Location: pacientes.php");
    exit;
} catch (Exception $e) {
    $conexion->rollback();
    $conexion->close();
    http_response_code(500);
    die("No se pudo eliminar el paciente.");
}
?>

<?php

require_once "conexion.php";

// La eliminación solo se acepta desde el formulario POST de la ficha.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$idPaciente = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($idPaciente === false || $idPaciente === null || $idPaciente < 1) {
    http_response_code(400);
    die("El identificador del paciente no es válido.");
}

$conexion->query(
    "DELETE FROM paciente WHERE id_paciente = $idPaciente"
);

// Por ahora el proyecto supone que id_paciente e id_persona tienen el mismo valor.
$conexion->query(
    "DELETE FROM persona WHERE id_persona = $idPaciente"
);

$conexion->close();
header("Location: pacientes.php");
exit;
?>
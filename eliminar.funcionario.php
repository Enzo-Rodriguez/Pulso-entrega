<?php

require_once "conexion.php";

// La eliminación solo se acepta desde el formulario POST de la ficha.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$idFuncionario = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($idFuncionario === false || $idFuncionario === null || $idFuncionario < 1) {
    http_response_code(400);
    die("El identificador del funcionario no es válido.");
}

$conexion->query(
    "DELETE FROM funcionario WHERE id_funcionario = $idFuncionario"
);

// Por ahora el proyecto supone que id_funcionario e id_persona tienen el mismo valor.
$conexion->query(
    "DELETE FROM persona WHERE id_persona = $idFuncionario"
);

$conexion->close();
header("Location: funcionarios.php");
exit;
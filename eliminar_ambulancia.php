<?php

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$matricula = trim($_POST["matricula"] ?? "");

if ($matricula === "") {
    http_response_code(400);
    die("La matrícula no es válida.");
}

try {
    $conexion->query(
        "DELETE FROM ambulancia_equipamiento WHERE matricula = '$matricula'"
    );
    $conexion->query(
        "DELETE FROM ambulancia WHERE matricula = '$matricula'"
    );

    header("Location: ambulancia.php");
    exit;
} catch (mysqli_sql_exception $error) {
    die("No se puede eliminar la ambulancia porque está siendo utilizada.");
}

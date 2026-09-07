<?php

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$equipamiento = trim($_POST["equipamiento"] ?? "");

if ($equipamiento === "") {
    http_response_code(400);
    die("El equipamiento no es válido.");
}

try {
    $conexion->query(
        "DELETE FROM equipamiento WHERE id_equipamiento = '$equipamiento'"
    );
    $conexion->query(
        "DELETE FROM equipamientos WHERE id_equipamiento = '$equipamiento'"
    );

    header("Location: equipamientos.php");
    exit;
} catch (mysqli_sql_exception $error) {
    die("No se puede eliminar el equipamiento porque está siendo utilizado.");
}

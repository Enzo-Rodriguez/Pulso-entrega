<?php

require_once "../conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$equipamiento = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($equipamiento === false || $equipamiento === null || $equipamiento < 1) {
    http_response_code(400);
    die("El identificador del equipamiento no es válido.");
}

$resultado = $conexion->query(
    "SELECT id_equipamiento FROM equipamiento WHERE id_equipamiento = $equipamiento"
);

if ($resultado->num_rows === 0) {
    http_response_code(404);
    die("El equipamiento no existe.");
}

try {
    $conexion->query(
        "DELETE FROM equipamiento WHERE id_equipamiento = $equipamiento"
    );
} catch (mysqli_sql_exception $error) {
    http_response_code(500);
    die("No se pudo eliminar el equipamiento.");
}

$conexion->close();

header("Location: equipamientos.php?eliminacion=exitosa");
exit;
?>

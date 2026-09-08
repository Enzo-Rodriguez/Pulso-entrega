<?php

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

$equipamiento = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if ($equipamiento === false || $equipamiento === null || $equipamiento < 1) {
    http_response_code(400);
    die("El identificador del equipamiento no es válido.");
}

try {

    $buscarEquipamiento = $conexion->prepare(
        "SELECT id_equipamiento FROM equipamiento WHERE id_equipamiento = ?"
    );

    $buscarEquipamiento->bind_param("i", $equipamiento);
    $buscarEquipamiento->execute();
    $buscarEquipamiento->store_result();

    if ($buscarEquipamiento->num_rows === 0) {
        http_response_code(404);
        die("El equipamiento no existe.");
    }

    $buscarEquipamiento->close();

    $eliminarEquipamiento = $conexion->prepare(
        "DELETE FROM equipamiento WHERE id_equipamiento = ?"
    );

    $eliminarEquipamiento->bind_param("i", $equipamiento);
    $eliminarEquipamiento->execute();
    $eliminarEquipamiento->close();

} catch (Exception $e) {
    http_response_code(500);
    die("Error al eliminar el equipamiento: " . $e->getMessage());
}

$conexion->close();

header("Location: equipamientos.php?eliminacion=exitosa");
exit;
?>

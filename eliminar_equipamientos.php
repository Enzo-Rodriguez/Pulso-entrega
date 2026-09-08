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
    // Busca la ruta antes de eliminar el registro para poder borrar también el archivo.
    $buscarEquipamiento = $conexion->prepare(
        "SELECT equipamientos FROM equipamiento WHERE id_equipamiento = ?"
    );
    $buscarEquipamiento->bind_param("i", $idEquipamiento);
    $buscarEquipamiento->execute();
    $buscarEquipamiento->bind_result($rutaArchivo);
    $equipamientoExiste = $buscarEquipamiento->fetch();
    $buscarEquipamiento->close();

    if (!$equipamientoExiste) {
        http_response_code(404);
        die("El equipamiento no existe.");
    }

    $eliminarEquipamiento = $conexion->prepare(
        "DELETE FROM equipamiento WHERE id_equipamiento = ?"
    );
    $eliminarEquipamiento->bind_param("i", $idEquipamiento);
    $eliminarEquipamiento->execute();
    $eliminarEquipamiento->close();

}

$conexion->close();

header("Location: equipamientos.php?eliminacion=exitosa");
exit;
?>

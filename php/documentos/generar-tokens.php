<?php
require_once "../conexion.php";

$consulta = $conexion->query(
    "SELECT id_documento
     FROM documento
     WHERE token_descarga IS NULL"
);

$actualizar = $conexion->prepare(
    "UPDATE documento
     SET token_descarga = ?
     WHERE id_documento = ?"
);

while ($fila = $consulta->fetch_assoc()) {
    $token = bin2hex(random_bytes(32));
    $id = (int) $fila["id_documento"];

    $actualizar->bind_param("si", $token, $id);
    $actualizar->execute();
}

$actualizar->close();
$consulta->free();

echo "Tokens generados correctamente.";
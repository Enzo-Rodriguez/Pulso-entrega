<?php

header("Content-Type: application/json");

require_once "php/conexion.php";

session_start();

$ci = trim($_POST["ci"] ?? "");
$contrasenia = $_POST["contrasenia"] ?? "";

if ($ci === "" || $contrasenia === "") {
    echo json_encode([
        "error" => "Debe ingresar la cédula y la contraseña",
    ]);
    exit();
}

$stmt = $conexion->prepare(
    "SELECT
        persona.id_persona,
        funcionario.id_funcionario,
        persona.nombres,
        persona.apellidos,
        persona.email,
        persona.contrasenia,
        funcionario.activo
    FROM persona
    INNER JOIN funcionario
        ON persona.id_persona = funcionario.id_persona
    WHERE persona.ci = ?",
);

$stmt->bind_param("s", $ci);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo json_encode([
        "error" => "La cédula no está registrada",
    ]);
    exit();
}

$fila = $resultado->fetch_assoc();

if (!$fila["activo"]) {
    echo json_encode([
        "error" => "El funcionario está inactivo",
    ]);
    exit();
}

if ($fila["contrasenia"] === null) {
    $contraseniaHash = password_hash($contrasenia, PASSWORD_DEFAULT);

    $actualizar = $conexion->prepare(
        "UPDATE persona
         SET contrasenia = ?
         WHERE id_persona = ?",
    );

    $actualizar->bind_param("si", $contraseniaHash, $fila["id_persona"]);

    if (!$actualizar->execute()) {
        echo json_encode([
            "error" => "No se pudo guardar la contraseña",
        ]);
        exit();
    }

    $actualizar->close();

    $_SESSION["id_persona"] = $fila["id_persona"];
    $_SESSION["id_funcionario"] = $fila["id_funcionario"];
    $_SESSION["ci"] = $ci;
    $_SESSION["nombre"] = $fila["nombres"];
    $_SESSION["apellidos"] = $fila["apellidos"];
    $_SESSION["email"] = $fila["email"];

    echo json_encode([
        "exito" => true,
    ]);

    exit();
}

if (password_verify($contrasenia, $fila["contrasenia"])) {
    $_SESSION["id_persona"] = $fila["id_persona"];
    $_SESSION["id_funcionario"] = $fila["id_funcionario"];
    $_SESSION["ci"] = $ci;
    $_SESSION["nombre"] = $fila["nombres"];
    $_SESSION["apellidos"] = $fila["apellidos"];
    $_SESSION["email"] = $fila["email"];

    echo json_encode([
        "exito" => true,
    ]);
} else {
    echo json_encode([
        "error" => "Contraseña incorrecta",
    ]);
}

$stmt->close();
$conexion->close();

?>

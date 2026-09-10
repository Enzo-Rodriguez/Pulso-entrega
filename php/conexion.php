<?php

// Datos que PHP necesita para encontrar la base de datos local de XAMPP.
$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "pulso_entrega";

// Hace que MySQL lance excepciones; permite controlar los errores con try/catch.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Abre la conexión y configura UTF-8 para guardar correctamente tildes y eñes.
    $conexion = new mysqli($servidor, $usuario, $contrasena, $base_datos);
    $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $error) {
    // Evita mostrar datos técnicos de la base si la conexión falla.
    http_response_code(500);
    die("No se pudo conectar con la base de datos.");
}

?>

<?php

require_once "conexion.php";

do {
    echo "\nMENÚ DE BASE DE DATOS\n";
    echo "1. Ver pacientes\n";
    echo "2. Ver ambulancias\n";
    echo "3. Agregar equipamiento\n";
    echo "4. Salir\n";
    echo "Elegí una opción: ";

    $opcion = trim(fgets(STDIN));

    if ($opcion == 1) {
        $resultado = $conexion->query("SELECT * FROM paciente");

        while ($paciente = $resultado->fetch_assoc()) {
            echo $paciente["id_paciente"] . " - "
                . $paciente["estado"] . " - "
                . $paciente["patologia"] . "\n";
        }
    }

    if ($opcion == 2) {
        $resultado = $conexion->query("SELECT * FROM ambulancia");

        while ($ambulancia = $resultado->fetch_assoc()) {
            echo $ambulancia["matricula"] . " - "
                . $ambulancia["marca"] . " "
                . $ambulancia["modelo"] . "\n";
        }
    }

    if ($opcion == 3) {
        echo "Nombre del equipamiento: ";
        $nombre = trim(fgets(STDIN));

        $conexion->query("INSERT INTO equipamiento (nombre) VALUES ('$nombre')");
        echo "Equipamiento agregado.\n";
    }
} while ($opcion != 4);

echo "Programa finalizado.\n";
$conexion->close();

?>

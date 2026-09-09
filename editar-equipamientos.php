<?php

require_once "conexion.php";

$idEquipamiento = (int) ($_GET["id_equipamiento"] ?? 0);

if ($idEquipamiento <= 0) {
    http_response_code(400);
    die("El equipamiento no es válido.");
}

$resultado = $conexion->query(
    "SELECT id_equipamiento, nombre, descripcion
     FROM equipamiento
     WHERE id_equipamiento = $idEquipamiento"
);

$equipamiento = $resultado->fetch_assoc();

if ($equipamiento === null) {
    http_response_code(404);
    die("Equipamiento no encontrado.");
}

$mensajeError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");

    if ($nombre === "" || $descripcion === "") {

        $mensajeError = "Complete correctamente todos los campos.";

    } else {

        $nombreSeguro = $conexion->real_escape_string($nombre);
        $descripcionSegura = $conexion->real_escape_string($descripcion);

        $exito = $conexion->query(
            "UPDATE equipamiento
             SET nombre = '$nombreSeguro',
                 descripcion = '$descripcionSegura'
             WHERE id_equipamiento = $idEquipamiento"
        );

        if ($exito) {
            header("Location: equipamientos.php");
            exit;
        }

        $mensajeError = "No se pudieron guardar los cambios.";

        // Refleja en pantalla lo que el usuario intentó guardar, no lo viejo de la BD
        $equipamiento["nombre"] = $nombre;
        $equipamiento["descripcion"] = $descripcion;
    }
}

function escapar($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Editar equipamiento</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon"
          type="image/png"
          href="recursos/logo-pulsoA.png">

</head>

<body>

<header class="barra-superior">
    <a class="marca" href="panel.html">
        <img src="recursos/logo-pulsoA.png" alt="">
        <span>PULSO</span>
    </a>

    <nav class="navegacion" aria-label="Páginas principales">

        <a href="panel.html">Panel</a>
        <a href="pacientes.php">Pacientes</a>
        <a href="funcionario.php">Funcionarios</a>
        <a href="documentos.php">Documentos</a>
        <a href="ambulancia.php">Ambulancias</a>
        <a class="enlace-activo" href="equipamientos.php">
            Equipamientos
        </a>
        <a href="index.html">Inicio</a>
    </nav>
</header>


<main class="pagina">
    <section class="encabezado-pagina">

        <div>
            <p class="subtitulo">Gestión de equipamientos</p>
            <h1>Editar equipamiento</h1>
            <p>
                Actualice los datos del equipamiento
                <?= escapar($equipamiento["id_equipamiento"]) ?>.
            </p>

        </div>

    </section>


    <section class="tarjeta">

        <?php if ($mensajeError !== ""): ?>
            <p class="estado estado-critico" role="alert">
                <?= escapar($mensajeError) ?>
            </p>

        <?php endif; ?>
        <form
            class="formulario formulario-dos-columnas"
            action="editar-equipamientos.php?id_equipamiento=<?= urlencode($equipamiento["id_equipamiento"]) ?>"
            method="post"
        >

            <label for="id_equipamiento">
                ID del equipamiento
                <input
                    id="id_equipamiento"
                    type="text"
                    value="<?= escapar($equipamiento["id_equipamiento"]) ?>"
                    readonly
                >
            </label>
            <label for="nombre">
                Nombre
                <input
                    id="nombre"
                    type="text"
                    name="nombre"
                    value="<?= escapar($equipamiento["nombre"]) ?>"
                    maxlength="100"
                    required
                >
            </label>

            <label for="descripcion">
                Descripción

                <input
                    id="descripcion"
                    type="text"
                    name="descripcion"
                    value="<?= escapar($equipamiento["descripcion"]) ?>"
                    maxlength="255"
                    required
                >

            </label>
            <div class="acciones campo-completo">      
            <a
                    class="boton boton-secundario"
                    href="equipamientos.php"
                >
                    Cancelar
                </a>
                <button class="boton" type="submit">
                    Guardar cambios
                </button>
            </div>
        </form>
    </section>
</main>
</body>
</html>
<?php
require_once "conexion.php";
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = $_POST["titulo"];

}
    
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" type="image/png" href="recursos/logo-pulsoA.png">
    <title>PULSO - Cargar Documento</title>
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
            <a class="enlace-activo" href="documentos.html">Documentos</a>
            <a href="ambulancia.php">Ambulancias</a>
            <a href="index.html">Inicio</a>
        </nav>
    </header>
    <main class="pagina">
        <section class="encabezado-pagina">
            <div>
                <p class="subtitulo">Registro asistencial</p>
                <h1>Cargar documento</h1>
                <p>Seleccione el documento que desea cargar.</p>
            </div>
        </section>
        <section class="tarjeta">

            <form class="formulario" action="documentos.php" method="POST" enctype="multipart/form-data">
                <label for="nombre_archivo">Ingrese el nombre del documento:</label>
                <input type="text" id="nombre_archivo" name="nombre" required>
                <label for="documento">Seleccione el archivo:</label>
                <input type="file" id="documento" name="documento" required>
                <br><br>
                <input class="boton" type="submit" id="boton-cargar" value="Cargar Documento">
            </form>
        </section>
    </main>
</body>

</html>
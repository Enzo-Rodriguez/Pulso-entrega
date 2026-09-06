<?php

require_once "conexion.php";

$matricula = trim($_GET["matricula"] ?? "");

if ($matricula === "") {
    http_response_code(400);
    die("La matrícula no es válida.");
}

$resultado = $conexion->query(
    "SELECT
        ambulancia.matricula,
        ambulancia.marca,
        ambulancia.modelo,
        ambulancia.capacidad,
        ambulancia.activa,
        estado_ambulancia.nombre AS estado
     FROM ambulancia
     INNER JOIN estado_ambulancia
        ON estado_ambulancia.id_estado_ambulancia = ambulancia.id_estado_ambulancia
     WHERE ambulancia.matricula = '$matricula'"
);
$ambulancia = $resultado->fetch_assoc();

if ($ambulancia === null) {
    http_response_code(404);
    die("Ambulancia no encontrada.");
}

function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

$estadoVisible = (int) $ambulancia["activa"] === 1
    ? $ambulancia["estado"]
    : "Inactiva";

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Ficha de ambulancia</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" type="image/png" href="recursos/logo-pulsoA.png">
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
        <a href="documentos.html">Documentos</a>
        <a class="enlace-activo" href="ambulancia.php">Ambulancias</a>
        <a href="index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Ficha de la unidad</p>
          <h1><?= escapar($ambulancia["matricula"]) ?></h1>
          <p>Información registrada en el sistema PULSO.</p>
        </div>
        <div class="acciones">
          <a class="boton boton-secundario" href="ambulancia.php">Volver</a>
          <a class="boton" href="editar-ambulancia.php?matricula=<?= urlencode($ambulancia["matricula"]) ?>">
            Editar ambulancia
          </a>
          <form action="eliminar_ambulancia.php" method="post" onsubmit="return confirm('¿Eliminar esta ambulancia?');">
            <input type="hidden" name="matricula" value="<?= escapar($ambulancia["matricula"]) ?>">
            <button class="boton_rojo" type="submit">Eliminar ambulancia</button>
          </form>
        </div>
      </section>

      <section class="tarjeta">
        <dl class="detalle-ambulancia">
          <div>
            <dt>Matrícula</dt>
            <dd><?= escapar($ambulancia["matricula"]) ?></dd>
          </div>
          <div>
            <dt>Estado</dt>
            <dd><?= escapar($estadoVisible) ?></dd>
          </div>
          <div>
            <dt>Marca</dt>
            <dd><?= escapar($ambulancia["marca"]) ?></dd>
          </div>
          <div>
            <dt>Modelo</dt>
            <dd><?= escapar($ambulancia["modelo"]) ?></dd>
          </div>
          <div>
            <dt>Capacidad</dt>
            <dd><?= escapar($ambulancia["capacidad"]) ?></dd>
          </div>
        </dl>
      </section>
    </main>
  </body>
</html>

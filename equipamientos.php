<?php

require_once "conexion.php";

$resultado = $conexion->query(
    "SELECT
        equipamiento.id_equipamiento,
        equipamiento.nombre,
        equipamiento.descripcion,
        equipamiento.activo
     FROM equipamiento
     ORDER BY equipamiento.nombre"
);

$equipamientos = $resultado->fetch_all(MYSQLI_ASSOC);

function llamar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Equipamientos</title>
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
        <a href="funcionario.php">Funcionarios</a>
        <a href="documentos.php">Documentos</a>
        <a href="ambulancia.php">Ambulancias</a>
        <a class="enlace-activo" href="equipamientos.php">Equipamientos</a>
        <a href="index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Gestión de recursos</p>
          <h1>Equipamientos</h1>
          <p>Equipos registrados en el catálogo del sistema PULSO.</p>
        </div>
        <div>
          <a class="boton" href="nuevo-equipamientos.php">Nuevo equipamiento</a>
        </div>
      </section>

      <section class="tarjeta">
        <p class="cantidad-resultados">
          <?= count($equipamientos) ?> <?= count($equipamientos) === 1 ? "resultado" : "resultados" ?>
        </p>

        <div class="contenedor-tabla">
          <table class="tabla">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($equipamientos) === 0): ?>
                <tr>
                  <td colspan="3">No hay equipamientos registrados.</td>
                </tr>
              <?php endif; ?>

              <?php foreach ($equipamientos as $equipamiento): ?>
                <?php
                  $estadoVisible = (int) $equipamiento["activo"] === 1 ? "Disponible" : "Inactivo";
                  $claseEstado = (int) $equipamiento["activo"] === 1 ? "estado-disponible" : "estado-inactivo";
                ?>
                <tr>
                  <td><strong><?= llamar($equipamiento["nombre"]) ?></strong></td>
                  <td><?= llamar($equipamiento["descripcion"]) ?></td>
                  <td><span class="estado
                   <?= llamar($claseEstado) ?>"><?= llamar($estadoVisible) ?></span></td>
                </tr>
              <?php endforeach; ?>
              
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </body>
</html>




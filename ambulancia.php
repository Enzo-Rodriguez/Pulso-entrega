<?php

require_once "conexion.php";

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
     ORDER BY ambulancia.matricula"
);

$ambulancias = $resultado->fetch_all(MYSQLI_ASSOC);

function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

$clasesEstados = [
    "Disponible" => "estado-estable",
    "En servicio" => "estado-observacion",
    "Mantenimiento" => "estado-critico",
];

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Ambulancias</title>
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
        <a class="enlace-activo" href="ambulancia.php">Ambulancias</a>
        <a href="index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Gestión de unidades</p>
          <h1>Ambulancias</h1>
          <p>Unidades registradas en el sistema PULSO.</p>
        </div>
        <div class="acciones">
          <a class="boton" href="nueva-ambulancia.php">Nueva ambulancia</a>
        </div>
      </section>

      <section class="tarjeta">
        <p class="cantidad-resultados">
          <?= count($ambulancias) ?> <?= count($ambulancias) === 1 ? "resultado" : "resultados" ?>
        </p>

        <div class="contenedor-tabla">
          <table class="tabla">
            <thead>
              <tr>
                <th>Matrícula</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Capacidad</th>
                <th>Estado</th>
                <th>Ficha</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($ambulancias) === 0): ?>
                <tr>
                  <td colspan="6">No hay ambulancias registradas.</td>
                </tr>
              <?php endif; ?>

              <?php foreach ($ambulancias as $ambulancia): ?>
                <?php
                  $estadoVisible = (int) $ambulancia["activa"] === 1
                      ? $ambulancia["estado"]
                      : "Inactiva";
                  $claseEstado = $ambulancia["activa"]
                      ? ($clasesEstados[$ambulancia["estado"]] ?? "estado-inactivo")
                      : "estado-inactivo";
                ?>
                <tr>
                  <td><strong><?= escapar($ambulancia["matricula"]) ?></strong></td>
                  <td><?= escapar($ambulancia["marca"]) ?></td>
                  <td><?= escapar($ambulancia["modelo"]) ?></td>
                  <td><?= escapar($ambulancia["capacidad"]) ?></td>
                  <td><span class="estado <?= escapar($claseEstado) ?>"><?= escapar($estadoVisible) ?></span></td>
                  <td>
                    <a href="ficha-ambulancia.php?matricula=<?= urlencode($ambulancia["matricula"]) ?>">Ver ficha</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </body>
</html>

<?php

require_once "conexion.php";

$resultado = $conexion->query(
    "SELECT
        equipamiento.id_equipamiento,
        equipamiento.nombre,
        equipamiento.descripcion
     FROM equipamiento
     ORDER BY equipamiento.nombre"
);

$equipamientos = $resultado->fetch_all(MYSQLI_ASSOC);
$altaExitosa = ($_GET["alta"] ?? "") === "exitosa";

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
        <?php if ($altaExitosa): ?>
          <p class="estado estado-publicado" role="status">Equipamiento registrado correctamente.</p>
        <?php endif; ?>

        <p class="cantidad-resultados">
          <?= count($equipamientos) ?> <?= count($equipamientos) === 1 ? "resultado" : "resultados" ?>
        </p>

        <div class="contenedor-tabla">
          <table class="tabla">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($equipamientos) === 0): ?>
                <tr>
                  <td colspan="3">No hay equipamientos registrados.</td>
                </tr>
              <?php endif; ?>

              <?php foreach ($equipamientos as $equipamiento): ?>
                <tr>
                  <td><strong><?= llamar($equipamiento["nombre"]) ?></strong></td>
                  <td><?= llamar($equipamiento["descripcion"]) ?></td>
                  <td>
                    <div class="acciones acciones-tabla">
                      <a class="boton boton-accion" href="editar-equipamientos.php?id_equipamiento=<?= llamar($equipamiento["id_equipamiento"]) ?>">
                        Actualizar
                      </a>
                      <form action="eliminar_equipamientos.php" method="post" onsubmit="return confirm('¿Eliminar este equipamiento?');">
                        <input type="hidden" name="id" value="<?= llamar($equipamiento["id_equipamiento"]) ?>">
                        <button class="boton_rojo boton-accion" type="submit" title="Eliminar equipamiento" aria-label="Eliminar equipamiento">
                          <img class="icono-boton" src="recursos/eliminar.png" alt="">
                        </button>
                      </form>
                    </div>
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




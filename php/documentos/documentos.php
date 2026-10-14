<?php

require_once "../conexion.php";

// Obtiene los documentos registrados en el sistema.
$resultadoDocumentos = $conexion->query(
  "SELECT
        documento.id_documento,
        documento.titulo,
        CONCAT_WS(' ', persona.nombres, persona.apellidos) AS paciente,
        DATE_FORMAT(documento.fecha_carga, '%d/%m/%Y %H:%i') AS fecha_carga
     FROM documento
     LEFT JOIN paciente ON paciente.id_paciente = documento.id_paciente
     LEFT JOIN persona ON persona.id_persona = paciente.id_persona
     ORDER BY documento.id_documento DESC"
);

$documentos = $resultadoDocumentos->fetch_all(MYSQLI_ASSOC);
$cargaExitosa = ($_GET["carga"] ?? "") === "exitosa";
$eliminacionExitosa = ($_GET["eliminacion"] ?? "") === "exitosa";
$actualizacionExitosa = ($_GET["actualizacion"] ?? "") === "exitosa";

function escapar($contenido): string
{
  return htmlspecialchars((string) ($contenido ?? ""), ENT_QUOTES, "UTF-8");
}

?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PULSO - Documentos</title>
  <link rel="stylesheet" href="../../css/estilos.css">
  <link rel="icon" type="image/png" href="../../recursos/logo-pulsoA.png">
</head>

<body>
  <header class="barra-superior">
    <a class="marca" href="../../panel.html">
      <img src="../../recursos/logo-pulsoA.png" alt="">
      <span>PULSO</span>
    </a>
    <nav class="navegacion" aria-label="Páginas principales">
      <a href="../../panel.html">Panel</a>
      <a href="../pacientes/pacientes.php">Pacientes</a>
      <a href="../funcionarios/funcionario.php">Funcionarios</a>
      <a class="enlace-activo" href="../documentos/documentos.php">Documentos</a>
      <a href="../ambulancias/ambulancia.php">Ambulancias</a>
      <a href="../equipamientos/equipamientos.php">Equipamientos</a>
      <a href="../traslados/traslados.html">Traslados</a>
      <a href="../../index.html">Inicio</a>
    </nav>
  </header>

  <main class="pagina">
    <section class="encabezado-pagina">
      <div>
        <p class="subtitulo">Documentación digital</p>
        <h1>Documentos</h1>
        <p>Documentos registrados en el sistema PULSO.</p>
      </div>
      <div class="acciones">
        <a class="boton" href="cargar-documento.php">Cargar documento</a>
      </div>
    </section>

    <section class="tarjeta">
      <?php if ($cargaExitosa): ?>
        <p class="estado estado-publicado" role="status">Documento cargado correctamente.</p>
      <?php endif; ?>

      <?php if ($eliminacionExitosa): ?>
        <p class="estado estado-publicado" role="status">Documento eliminado correctamente.</p>
      <?php endif; ?>

      <?php if ($actualizacionExitosa): ?>
        <p class="estado estado-publicado" role="status">Documento actualizado correctamente.</p>
      <?php endif; ?>

      <p class="cantidad-resultados">
        <?= count($documentos) ?> <?= count($documentos) === 1 ? "documento" : "documentos" ?>
      </p>

      <div class="contenedor-tabla">
        <table class="tabla">
          <thead>
            <tr>
              <th>Título</th>
              <th>Paciente</th>
              <th>Fecha de carga</th>
              <th>Código QR</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($documentos) === 0): ?>
              <tr>
                <td colspan="5">No hay documentos registrados.</td>
              </tr>
            <?php endif; ?>

            <?php foreach ($documentos as $documento): ?>
              <tr>
                <td><strong><?= escapar($documento["titulo"]) ?></strong></td>
                <td><?= escapar($documento["paciente"] ?: "Sin asignar") ?></td>
                <td><?= escapar($documento["fecha_carga"]) ?></td>
                <td>
                  <a href="ver-qr.php?id=<?= (int) $documento["id_documento"] ?>">
                    Ver código QR
                  </a>
                </td>
                <td>
                  <div class="acciones acciones-tabla">
                    <a class="boton boton-accion" href="editar-documento.php?id=<?= escapar($documento["id_documento"]) ?>">
                      Actualizar
                    </a>
                    <form action="eliminar-documento.php" method="post" onsubmit="return confirm('¿Eliminar este documento?');">
                      <input type="hidden" name="id" value="<?= escapar($documento["id_documento"]) ?>">
                      <button class="boton-rojo boton-accion" type="submit" title="Eliminar documento" aria-label="Eliminar documento">
                        <img class="icono-boton" src="../../recursos/eliminar.png" alt="">
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

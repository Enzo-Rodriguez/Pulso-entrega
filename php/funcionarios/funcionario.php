<?php

require_once "../conexion.php";

$resultado = $conexion->query(
  "SELECT
        funcionario.id_funcionario,
        funcionario.activo,
        persona.ci,
        persona.nombres,
        persona.apellidos,
        persona.telefono,
        persona.email
     FROM funcionario
     INNER JOIN persona ON persona.id_persona = funcionario.id_persona
     ORDER BY funcionario.id_funcionario DESC"
);
$funcionarios = $resultado->fetch_all(MYSQLI_ASSOC);

function escapar($valor)
{
  return htmlspecialchars((string) ($valor ?? ""), ENT_QUOTES, "UTF-8");
}

?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PULSO - Funcionarios</title>
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
      <a class="enlace-activo" href="../funcionarios/funcionario.php">Funcionarios</a>
      <a href="../documentos/documentos.php">Documentos</a>
      <a href="../ambulancias/ambulancia.php">Ambulancias</a>
      <a href="../equipamientos/equipamientos.php">Equipamientos</a>
      <a href="../../index.html">Inicio</a>
    </nav>
  </header>

  <main class="pagina">
    <section class="encabezado-pagina">
      <div>
        <p class="subtitulo">Registro de personal</p>
        <h1>Funcionarios</h1>
        <p>Personal registrado en el sistema PULSO.</p>
      </div>
      <div class="acciones">
        <a class="boton" href="nuevo-funcionario.php">Nuevo funcionario</a>
      </div>
    </section>

    <section class="tarjeta">
      <p class="cantidad-resultados">
        <?= count($funcionarios) ?> <?= count($funcionarios) === 1 ? "resultado" : "resultados" ?>
      </p>

      <div class="contenedor-tabla">
        <table class="tabla">
          <thead>
            <tr>
              <th>Funcionario</th>
              <th>Cédula</th>
              <th>Correo</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($funcionarios) === 0): ?>
              <tr>
                <td colspan="5">No hay funcionarios registrados.</td>
              </tr>
            <?php endif; ?>

            <?php foreach ($funcionarios as $funcionario): ?>
              <?php
              $esActivo = (int) $funcionario["activo"] === 1;
              $estadoTexto = $esActivo ? "Activo" : "Inactivo";
              $estadoClase = $esActivo ? "estado-estable" : "estado-inactivo";
              ?>
              <tr>
                <td>
                  <strong><?= escapar($funcionario["nombres"] . " " . $funcionario["apellidos"]) ?></strong>
                  <?php if (!empty($funcionario["telefono"])): ?>
                    <small><?= escapar($funcionario["telefono"]) ?></small>
                  <?php endif; ?>
                </td>
                <td><?= escapar($funcionario["ci"]) ?></td>
                <td><?= escapar($funcionario["email"] ?: "Sin especificar") ?></td>
                <td><span class="estado <?= $estadoClase ?>"><?= $estadoTexto ?></span></td>
                <td>
                  <a class="boton boton-secundario"
                    href="editar-funcionario.php?id=<?= $funcionario['id_funcionario'] ?>">
                    Editar
                  </a>

                  <form action="eliminar-funcionario.php" method="POST" style="display: inline;">
                    <input type="hidden" name="id" value="<?= $funcionario['id_funcionario'] ?>">

                    <button class="boton-rojo boton-accion" type="submit" title="Eliminar funcionario"
                      aria-label="Eliminar funcionario"
                      onclick="return confirm('¿Está seguro de que desea eliminar este funcionario?')">

                      <img class="icono-boton" src="../../recursos/eliminar.png" alt="">
                    </button>
                  </form>
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

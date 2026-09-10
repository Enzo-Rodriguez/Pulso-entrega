<?php

require_once "../conexion.php";


$resultado = $conexion->query(
    "SELECT
        paciente.id_paciente,
        DATE_FORMAT(paciente.fecha_registro, '%d/%m/%Y %H:%i') AS fecha_registro,
        paciente.estado,
        paciente.patologia,
        paciente.activo,
        persona.ci,
        persona.nombres,
        persona.apellidos,
        persona.telefono
     FROM paciente
     INNER JOIN persona ON persona.id_persona = paciente.id_persona
     ORDER BY paciente.id_paciente DESC"
);

$pacientes = $resultado->fetch_all(MYSQLI_ASSOC);


function escapar($valor)
{
    return htmlspecialchars($valor ?? "", ENT_QUOTES, "UTF-8");
}

$estados = [
    "estable" => ["Estable", "estado-estable"],
    "en_revision" => ["En observación", "estado-observacion"],
    "critico" => ["Crítico", "estado-critico"],
    "alta" => ["Alta", "estado-alta"],
];

?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PULSO - Pacientes</title>
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
        <a class="enlace-activo" href="../pacientes/pacientes.php">Pacientes</a>
        <a href="../funcionarios/funcionario.php">Funcionarios</a>
        <a href="../documentos/documentos.php">Documentos</a>
        <a href="../ambulancias/ambulancia.php">Ambulancias</a>
        <a href="../equipamientos/equipamientos.php">Equipamientos</a>
        <a href="../../index.html">Inicio</a>
      </nav>
    </header>

    <main class="pagina">
      <section class="encabezado-pagina">
        <div>
          <p class="subtitulo">Registro asistencial</p>
          <h1>Pacientes</h1>
          <p>Personas registradas en el sistema PULSO.</p>
        </div>
        <div class="acciones">
          <a class="boton" href="nuevo-paciente.php">Nuevo paciente</a>
        </div>
      </section>

      <section class="tarjeta">
        <p class="cantidad-resultados">
          <?= count($pacientes) ?> <?= count($pacientes) === 1 ? "resultado" : "resultados" ?>
        </p>

        <div class="contenedor-tabla">
          <table class="tabla">
            <thead>
              <tr>
                <th>Paciente</th>
                <th>Cédula</th>
                <th>Patología</th>
                <th>Estado</th>
                <th>Fecha de registro</th>
                <th>Ficha</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($pacientes) === 0): ?>
                <tr>
                  <td colspan="6">No hay pacientes registrados.</td>
                </tr>
              <?php endif; ?>

              <?php foreach ($pacientes as $paciente): ?>
                <?php
                  $estadoPaciente = (int) $paciente["activo"] === 1
                      ? ($estados[$paciente["estado"]] ?? ["Sin especificar", "estado-inactivo"])
                      : ["Inactivo", "estado-inactivo"];
                ?>
                <tr>
                  <td>
                    <strong><?= escapar($paciente["nombres"] . " " . $paciente["apellidos"]) ?></strong>
                    <?php if ($paciente["telefono"] !== null && $paciente["telefono"] !== ""): ?>
                      <small><?= escapar($paciente["telefono"]) ?></small>
                    <?php endif; ?>
                  </td>
                  <td><?= escapar($paciente["ci"]) ?></td>
                  <td><?= escapar($paciente["patologia"] ?: "Sin especificar") ?></td>
                  <td><span class="estado <?= escapar($estadoPaciente[1]) ?>"><?= escapar($estadoPaciente[0]) ?></span></td>
                  <td><?= escapar($paciente["fecha_registro"]) ?></td>
                  <td><a href="ficha-paciente.php?id=<?php echo $paciente['id_paciente']; ?>">Ver ficha</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>

  </body>
</html>

<?php

require_once "conexion.php";


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
        <a class="enlace-activo" href="pacientes.php">Pacientes</a>
        <a href="documentos.php">Documentos</a>
        <a href="ambulancia.php">Ambulancias</a>
        <a href="equipamientos.php">Equipamientos</a>
        <a href="index.html">Inicio</a>
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

      <section class="tarjeta" id="listado-pacientes">
        <form id="filtros-pacientes" class="filtros" action="pacientes.php">
          <input id="busqueda-paciente" name="busqueda" type="search" placeholder="Buscar por nombre, cédula o patología">
          <select id="filtro-estado" name="estado" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <option>Estable</option>
            <option>En observación</option>
            <option>Crítico</option>
            <option>Alta</option>
            <option>Inactivo</option>
          </select>
          <button class="boton" type="submit">Buscar</button>
        </form>

        <p class="cantidad-resultados">
          <span id="contador"><?= count($pacientes) ?></span>
          <span id="etiqueta-resultados"><?= count($pacientes) === 1 ? "resultado" : "resultados" ?></span>
        </p>

        <div class="contenedor-tabla">
          <table class="tabla" id="tabla-pacientes">
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
                  <td colspan="5">No hay pacientes registrados.</td>
                </tr>
              <?php endif; ?>

              <!-- Repite una fila por cada paciente obtenido de la base de datos. -->
              <?php foreach ($pacientes as $paciente): ?>
                <?php
                  // activo permite mostrar una baja lógica sin borrar al paciente de la base.
                  $estadoPaciente = (int) $paciente["activo"] === 1
                      ? ($estados[$paciente["estado"]] ?? ["Sin especificar", "estado-inactivo"])
                      : ["Inactivo", "estado-inactivo"];
                ?>
                <tr data-estado="<?= escapar($estadoPaciente[0]) ?>">
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

    <script>
      // Guarda referencias a los controles y a las filas que se van a filtrar.
      const formularioFiltros = document.getElementById("filtros-pacientes");
      const busquedaPaciente = document.getElementById("busqueda-paciente");
      const filtroEstado = document.getElementById("filtro-estado");
      const filas = document.querySelectorAll("#tabla-pacientes tbody tr[data-estado]");
      const contador = document.getElementById("contador");
      const etiquetaResultados = document.getElementById("etiqueta-resultados");

      // Ignora mayúsculas y tildes para que la búsqueda sea más tolerante.
      function normalizar(texto) {
        return texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
      }

      // Oculta las filas que no coinciden y actualiza el contador visible.
      function actualizarListado() {
        const texto = normalizar(busquedaPaciente.value.trim());
        const estado = filtroEstado.value;
        let visibles = 0;

        filas.forEach((fila) => {
          const coincideTexto = texto === "" || normalizar(fila.textContent).includes(texto);
          const coincideEstado = estado === "" || fila.dataset.estado === estado;
          fila.hidden = !coincideTexto || !coincideEstado;
          if (!fila.hidden) visibles += 1;
        });

        contador.textContent = visibles;
        etiquetaResultados.textContent = visibles === 1 ? "resultado" : "resultados";
      }

      // Filtra en esta misma página; preventDefault evita una recarga innecesaria.
      formularioFiltros.addEventListener("submit", (evento) => {
        evento.preventDefault();
        actualizarListado();
      });
      busquedaPaciente.addEventListener("input", actualizarListado);
      filtroEstado.addEventListener("change", actualizarListado);
    </script>
  </body>
</html>

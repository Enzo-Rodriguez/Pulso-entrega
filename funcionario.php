<?php

require_once "conexion.php";

$resultado = $conexion->query(
    "SELECT
        funcionario.id_funcionario,
        funcionario.id_tipo_funcionario,
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
$funcionarios = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];

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
        <a class="enlace-activo" href="funcionario.php">Funcionarios</a>
        <a href="documentos.php">Documentos</a>
        <a href="ambulancia.php">Ambulancias</a>
        <a href="equipamientos.php">Equipamientos</a>
        <a href="index.html">Inicio</a>
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

      <section class="tarjeta" id="listado-funcionarios">
        <form id="filtros-funcionarios" class="filtros" action="funcionario.php">
          <input id="busqueda-funcionario" name="busqueda" type="search" placeholder="Buscar por nombre, cédula o teléfono">
          <select id="filtro-estado" name="estado" aria-label="Filtrar por estado laboral">
            <option value="">Todos los estados</option>
            <option value="Activo">Activo</option>
            <option value="Inactivo">Inactivo</option>
          </select>
          <button class="boton" type="submit">Buscar</button>
        </form>

        <p class="cantidad-resultados">
          <span id="contador"><?= count($funcionarios) ?></span>
          <span id="etiqueta-resultados"><?= count($funcionarios) === 1 ? "resultado" : "resultados" ?></span>
        </p>

        <div class="contenedor-tabla">
          <table class="tabla" id="tabla-funcionarios">
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
                <tr data-estado="<?= $estadoTexto ?>">
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
                    <a class="boton boton-secundario" href="modificarfuncionario.php?id=<?= $funcionario['id_funcionario'] ?>">Editar</a>
                    <button class="boton_rojo boton-accion" type="submit" title="Eliminar documento" aria-label="Eliminar documento">
                          <img class="icono-boton" src="recursos/eliminar.png" alt="">
                        </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <script>
      const formularioFiltros = document.getElementById("filtros-funcionarios");
      const busquedaFuncionario = document.getElementById("busqueda-funcionario");
      const filtroEstado = document.getElementById("filtro-estado");
      const filas = document.querySelectorAll("#tabla-funcionarios tbody tr[data-estado]");
      const contador = document.getElementById("contador");
      const etiquetaResultados = document.getElementById("etiqueta-resultados");

      function normalizar(texto) {
        return texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
      }

      function actualizarListado() {
        const texto = normalizar(busquedaFuncionario.value.trim());
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

      formularioFiltros.addEventListener("submit", (evento) => {
        evento.preventDefault();
        actualizarListado();
      });
      busquedaFuncionario.addEventListener("input", actualizarListado);
      filtroEstado.addEventListener("change", actualizarListado);
    </script>
  </body>
</html>

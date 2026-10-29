const panelAdministrativo = document.getElementById('panelAdministrativo');
const consultasPanel = document.getElementById('consultasPanel');
const botonActualizarPanel = document.getElementById('botonActualizarPanel');
const listaActivosPanel = document.getElementById('listaActivosPanel');
const mensajePanel = document.getElementById('mensajePanel');
const camposResumenPanel = ['totalProgramados', 'totalActivos', 'totalAmbulancias', 'totalConductores'];
const estadosActivosPanel = ['En curso', 'En destino', 'En regreso', 'En regreso por cancelación'];
let sesionPanel = null;
let consultandoPanel = false;

function mostrarFilaPanel(texto) {
  const fila = document.createElement('tr');
  const celda = document.createElement('td');
  celda.colSpan = 5;
  celda.className = 'fila-vacia-panel';
  celda.textContent = texto;
  fila.append(celda);
  listaActivosPanel.replaceChildren(fila);
}

function limpiarResumenPanel(texto) {
  camposResumenPanel.forEach(campo => { document.getElementById(campo).textContent = '—'; });
  document.getElementById('detalleActivos').textContent = texto;
  document.getElementById('actualizacionPanel').textContent = '';
  mostrarFilaPanel(texto);
}

function limpiarSesionPanel() {
  sesionPanel = null;
  panelAdministrativo.hidden = true;
  consultasPanel.hidden = true;
  botonActualizarPanel.hidden = true;
  mensajePanel.hidden = true;
  limpiarResumenPanel('Sesión no disponible.');
}

function mostrarResumenPanel(datos) {
  if (!Array.isArray(datos.traslados) || !Array.isArray(datos.opciones?.ambulancias) || !Array.isArray(datos.opciones?.funcionarios)) {
    throw new Error('No se recibieron los datos necesarios para el panel.');
  }
  const activos = datos.traslados.filter(traslado => estadosActivosPanel.includes(traslado.estado));
  const resumen = [
    datos.traslados.filter(traslado => traslado.estado === 'Programado').length,
    activos.length,
    datos.opciones.ambulancias.filter(ambulancia => ambulancia.id_traslado_ocupado === null).length,
    datos.opciones.funcionarios.filter(funcionario => funcionario.tipo === 'Conductor' && funcionario.id_traslado_ocupado === null).length,
  ];
  camposResumenPanel.forEach((campo, indice) => { document.getElementById(campo).textContent = resumen[indice]; });
  const visibles = activos.slice(0, 5);
  document.getElementById('detalleActivos').textContent = activos.length
    ? 'Mostrando ' + visibles.length + ' de ' + activos.length + ' traslados activos.'
    : 'No hay traslados activos en este momento.';
  listaActivosPanel.replaceChildren();
  visibles.forEach(function (traslado) {
    const fila = document.createElement('tr');
    ['origen', 'destino', 'nombre_conductor', 'matricula', 'estado'].forEach(function (campo) {
      const celda = document.createElement('td');
      if (campo === 'estado') {
        const estado = document.createElement('span');
        const clase = traslado.estado === 'En regreso por cancelación' ? 'estado-critico'
          : traslado.estado === 'En destino' ? 'estado-observacion' : 'estado-alta';
        estado.className = 'estado ' + clase;
        estado.textContent = traslado.estado;
        celda.append(estado);
      } else {
        celda.textContent = traslado[campo] || 'Sin dato';
      }
      fila.append(celda);
    });
    listaActivosPanel.append(fila);
  });
  if (!activos.length) mostrarFilaPanel('No hay traslados activos en este momento.');
  document.getElementById('actualizacionPanel').textContent = 'Última actualización: ' +
    new Date().toLocaleTimeString('es-UY', { hour: '2-digit', minute: '2-digit' });
}

async function cargarPanel() {
  if (!sesionPanel || sesionPanel.funcionario.tipo !== 'Administrativo' || consultandoPanel) return;
  const sesionConsultada = sesionPanel;
  const controlador = new AbortController();
  const tiempoLimite = setTimeout(() => controlador.abort(), 12000);
  consultandoPanel = true;
  botonActualizarPanel.disabled = true;
  document.querySelector('.resumen-panel').setAttribute('aria-busy', 'true');
  mensajePanel.hidden = true;
  try {
    // Reutiliza las mismas reglas de disponibilidad del módulo de Traslados.
    const respuesta = await fetch('php/traslados/traslados.php', { cache: 'no-store', signal: controlador.signal });
    const datos = await respuesta.json();
    if (sesionPanel !== sesionConsultada) return;
    if ([401, 403].includes(respuesta.status)) {
      limpiarSesionPanel();
      window.manejarAccesoDenegado(respuesta, datos);
      return;
    }
    if (!respuesta.ok) throw new Error(datos.error || 'No se pudo actualizar el panel.');
    mostrarResumenPanel(datos);
  } catch (error) {
    if (sesionPanel !== sesionConsultada) return;
    limpiarResumenPanel('No se pudo cargar el resumen.');
    mensajePanel.textContent = error.name === 'AbortError' ? 'La consulta demoró demasiado. Volvé a intentar.' : error.message;
    mensajePanel.className = 'estado estado-critico mensaje-panel';
    mensajePanel.hidden = false;
  } finally {
    clearTimeout(tiempoLimite);
    consultandoPanel = false;
    botonActualizarPanel.disabled = !sesionPanel;
    document.querySelector('.resumen-panel').setAttribute('aria-busy', 'false');
  }
}

botonActualizarPanel.addEventListener('click', cargarPanel);
window.addEventListener('sesion-cerrada', limpiarSesionPanel);
document.addEventListener('visibilitychange', () => { if (!document.hidden) cargarPanel(); });
window.sesionLista.then(function (sesion) {
  sesionPanel = sesion;
  const administrativo = sesion.funcionario.tipo === 'Administrativo';
  panelAdministrativo.hidden = !administrativo;
  consultasPanel.hidden = administrativo;
  botonActualizarPanel.hidden = !administrativo;
  return cargarPanel();
}).catch(function () { if (sesionPanel) limpiarSesionPanel(); });

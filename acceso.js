// La pantalla usa la sesión para navegar; PHP vuelve a comprobar cada permiso.
const raizPulso = new URL('.', document.currentScript.src);
const moduloPulso = document.currentScript.dataset.modulo;
document.documentElement.style.visibility = 'hidden';

window.irAlAcceso = function (destino = 'index.html') {
  const enlace = new URL(destino, raizPulso);
  if (enlace.origin !== location.origin || !enlace.pathname.startsWith(raizPulso.pathname)) {
    enlace.href = new URL('index.html', raizPulso).href;
  }
  document.documentElement.style.visibility = 'hidden';
  location.replace(enlace.href);
};

window.manejarAccesoDenegado = function (respuesta, resultado) {
  if (![401, 403].includes(respuesta.status)) return false;
  window.irAlAcceso(resultado.destino || 'index.html');
  return true;
};

function moduloDelEnlace(enlace) {
  const ruta = new URL(enlace.href, raizPulso).pathname;
  if (ruta.endsWith('/panel.html')) return 'panel';
  if (ruta.endsWith('/chofer.html')) return 'chofer';
  return ['pacientes', 'documentos', 'funcionarios', 'ambulancias', 'equipamientos', 'traslados']
    .find(modulo => ruta.includes('/php/' + modulo + '/'));
}

window.sesionLista = (async function () {
  try {
    const respuesta = await fetch(new URL('php/sesion.php', raizPulso), { cache: 'no-store' });
    const sesion = await respuesta.json();
    if (window.manejarAccesoDenegado(respuesta, sesion)) throw new Error(sesion.error || 'Acceso no permitido.');
    if (!respuesta.ok) throw new Error(sesion.error || 'No se pudo consultar la sesión.');
    if (!sesion.modulos.includes(moduloPulso)) {
      window.irAlAcceso(sesion.destino);
      throw new Error('Tu usuario no tiene acceso a esta pantalla.');
    }
    document.querySelectorAll('.navegacion a, .rejilla a').forEach(enlace => {
      const modulo = moduloDelEnlace(enlace);
      if (modulo && !sesion.modulos.includes(modulo)) enlace.remove();
    });
    const nombre = document.querySelector('[data-usuario]');
    if (nombre) nombre.textContent = sesion.funcionario.nombre + ' ' + sesion.funcionario.apellidos + ' · ' + sesion.funcionario.tipo;
    const botonSalir = document.createElement('button');
    botonSalir.type = 'button';
    botonSalir.className = 'boton boton-secundario';
    botonSalir.textContent = 'Cerrar sesión';
    botonSalir.addEventListener('click', async function () {
      botonSalir.disabled = true;
      try {
        const datos = new FormData();
        datos.set('accion', 'cerrar');
        datos.set('csrf', sesion.csrf);
        const cierre = await fetch(new URL('php/sesion.php', raizPulso), { method: 'POST', body: datos });
        const resultado = await cierre.json();
        if (window.manejarAccesoDenegado(cierre, resultado)) return;
        if (!cierre.ok) throw new Error(resultado.error || 'No se pudo cerrar la sesión.');
        window.dispatchEvent(new Event('sesion-cerrada'));
        window.irAlAcceso(resultado.destino);
      } catch (error) {
        alert(error.message);
        botonSalir.disabled = false;
      }
    });
    (document.querySelector('.navegacion') || document.querySelector('header')).append(botonSalir);
    document.documentElement.style.visibility = '';
    return sesion;
  } catch (error) {
    if (document.documentElement.style.visibility === 'hidden') {
      // Nunca se muestra una pantalla privada si falla la comprobación de sesión.
      const aviso = document.createElement('p');
      const archivoLocal = location.protocol === 'file:';
      aviso.textContent = archivoLocal
        ? 'Este proyecto necesita PHP y MySQL. Abrilo desde la dirección local del servidor, no haciendo doble clic en el archivo HTML.'
        : 'No se pudo comprobar el acceso. Volvé al inicio para ingresar.';
      const volver = document.createElement('a');
      volver.href = new URL('index.html', raizPulso);
      volver.textContent = 'Ir al inicio';
      document.body.replaceChildren(...(archivoLocal ? [aviso] : [aviso, volver]));
      document.documentElement.style.visibility = '';
    }
    throw error;
  }
})();
// Las pantallas PHP no necesitan consumir la promesa, pero sí la validación.
window.sesionLista.catch(() => {});

// Evita recuperar una página privada de la caché al volver tras cerrar sesión.
window.addEventListener('pageshow', evento => { if (evento.persisted) location.reload(); });

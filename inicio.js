const formulario = document.querySelector('#ingreso');

formulario.addEventListener('submit', async function (evento) {
  evento.preventDefault();
  const boton = formulario.querySelector('button[type="submit"]');
  boton.disabled = true;
  try {
    const respuesta = await fetch('inicio.php', { method: 'POST', body: new FormData(formulario) });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.exito) throw new Error(resultado.error || 'No se pudo ingresar.');
    const destino = new URL(resultado.destino, location.href);
    if (destino.origin !== location.origin) throw new Error('El destino de ingreso no es válido.');
    location.replace(destino.href);
  } catch (error) {
    alert(error.message);
  } finally {
    boton.disabled = false;
  }
});

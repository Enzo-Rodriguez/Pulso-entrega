const formulario = document.querySelector('#inicio');

formulario.addEventListener('submit', async (e) => {
  e.preventDefault();

  const datosI = new FormData(formulario);

  const respuesta = await fetch('/HR_Clinica/php/inicio.php', {
    method: 'POST',
    body: datosI
  });

  const objetoJSON = await respuesta.json();

  if (objetoJSON.error) {
    alert(objetoJSON.error);
  } else if (objetoJSON.exito) {
    window.location.href = './paginas/dashboard.html';
  }
});
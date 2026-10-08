alert("JavaScript funciona");
const formulario = document.querySelector('#formulario-ingreso');

formulario.addEventListener('submit', async(e) => {

e.preventDefault();

const datosI = new FormData(formulario);

const respuesta = await fetch('inicio.php', {
    method: 'POST',
    body: datosI
});

const objetoJSON = await respuesta.json();

if (objetoJSON.error) {

    alert(objetoJSON.error);

} else if (objetoJSON.exito) {

    window.location.href = 'panel.html';

}

});
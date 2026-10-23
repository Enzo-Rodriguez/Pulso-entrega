const parametrosDocumento = new URLSearchParams(window.location.search);
const tokenDocumento = parametrosDocumento.get("token");
const estadoDocumento = document.getElementById("estadoDocumento");
const contenidoDocumento = document.getElementById("contenidoDocumento");
const encuestaDocumento = document.getElementById("encuestaDocumento");
const visorDocumento = document.getElementById("visorDocumento");
const botonReintentar = document.getElementById("reintentarDocumento");
let consultaDocumento = null;

function mostrarErrorDocumento(mensaje, permitirReintento = false) {
  contenidoDocumento.hidden = true;
  encuestaDocumento.hidden = true;
  visorDocumento.replaceChildren();
  estadoDocumento.textContent = mensaje;
  estadoDocumento.classList.add("estado-documento-error");
  estadoDocumento.hidden = false;
  botonReintentar.hidden = !permitirReintento;
}

function tamanoDocumento(bytes) {
  if (bytes < 1024) return bytes + " bytes";
  const megabytes = bytes >= 1024 * 1024;
  return (bytes / (megabytes ? 1024 * 1024 : 1024)).toLocaleString("es-UY", { maximumFractionDigits: 1 }) + (megabytes ? " MB" : " KB");
}

async function cargarDocumento() {
  if (!tokenDocumento || !/^[a-f0-9]{64}$/.test(tokenDocumento)) {
    mostrarErrorDocumento("Este enlace no es válido. Volvé a escanear el código QR o consultá con el personal del hospital.");
    return;
  }

  consultaDocumento?.abort();
  const controlador = new AbortController();
  consultaDocumento = controlador;
  const tiempoLimite = setTimeout(() => controlador.abort(), 15000);
  estadoDocumento.textContent = "Buscando tu documento…";
  estadoDocumento.classList.remove("estado-documento-error");
  estadoDocumento.hidden = false;
  botonReintentar.hidden = true;
  contenidoDocumento.hidden = true;
  encuestaDocumento.hidden = true;
  visorDocumento.replaceChildren();

  try {
    const consulta = new URLSearchParams({ token: tokenDocumento, informacion: "1" });
    const respuesta = await fetch("php/documentos/descargar-documento.php?" + consulta,
      { cache: "no-store", signal: controlador.signal });
    const datos = await respuesta.json();
    if (consultaDocumento !== controlador) return;
    if (!respuesta.ok) {
      mostrarErrorDocumento(datos.error || "No se pudo abrir el documento.", respuesta.status >= 500);
      return;
    }

    const documento = datos.documento;
    if (!documento || typeof documento.titulo !== "string") throw new Error("Respuesta no válida.");
    const urlArchivo = new URL(documento.url_ver, window.location.href);
    const urlDescarga = new URL(documento.url_descarga, window.location.href);
    if (urlArchivo.origin !== location.origin || urlDescarga.origin !== location.origin) throw new Error("Enlace no válido.");

    document.getElementById("tituloDocumento").textContent = documento.titulo;
    document.title = documento.titulo + " · PULSO";
    document.getElementById("datosDocumento").textContent = documento.extension.toUpperCase() + " · " + tamanoDocumento(documento.tamano_bytes);
    document.getElementById("abrirDocumento").href = urlArchivo.href;
    document.getElementById("descargarDocumento").href = urlDescarga.href;
    const avisoVisor = document.getElementById("avisoVisor");

    if (["jpg", "jpeg", "png"].includes(documento.extension)) {
      const imagen = document.createElement("img");
      imagen.alt = documento.titulo;
      imagen.src = urlArchivo.href;
      imagen.addEventListener("error", () => {
        if (imagen.isConnected) mostrarErrorDocumento("No se pudo cargar la imagen. Intentá nuevamente.", true);
      });
      visorDocumento.append(imagen);
      avisoVisor.textContent = "Podés ampliar la imagen abriéndola en otra pestaña o descargarla para consultarla después.";
    } else if (documento.extension === "txt") {
      const texto = await fetch(urlArchivo, { cache: "no-store", signal: controlador.signal });
      if (!texto.ok) throw new Error("No se pudo leer el archivo.");
      const contenido = await texto.text();
      if (consultaDocumento !== controlador) return;
      const parrafo = document.createElement("pre");
      parrafo.textContent = contenido;
      visorDocumento.append(parrafo);
      avisoVisor.textContent = "También podés abrir el texto en otra pestaña o descargarlo.";
    } else if (documento.extension === "pdf") {
      const visor = document.createElement("iframe");
      visor.title = "Documento: " + documento.titulo;
      visor.src = urlArchivo.href + "#view=FitH&navpanes=0";
      visor.referrerPolicy = "no-referrer";
      visorDocumento.append(visor);
      avisoVisor.textContent = "Si el documento no aparece dentro de la página, tocá «Abrir documento» o «Descargar». Algunos celulares lo abren en otra aplicación.";
    } else {
      const aviso = document.createElement("p");
      aviso.className = "aviso-formato-documento";
      aviso.textContent = "Este archivo de Word se consulta con una aplicación compatible. Tocá «Abrir documento» o «Descargar» para verlo.";
      visorDocumento.append(aviso);
      avisoVisor.textContent = "El archivo se abre desde este servidor; no se envía a visores externos.";
    }

    contenidoDocumento.hidden = false;
    encuestaDocumento.hidden = false;
    estadoDocumento.hidden = true;
  } catch (error) {
    if (consultaDocumento !== controlador) return;
    mostrarErrorDocumento(error.name === "AbortError"
      ? "La conexión demoró demasiado. Revisá tu conexión y volvé a intentar."
      : "No se pudo cargar el documento. Revisá tu conexión y volvé a intentar.", true);
  } finally {
    clearTimeout(tiempoLimite);
    if (consultaDocumento === controlador) consultaDocumento = null;
  }
}

botonReintentar.addEventListener("click", cargarDocumento);
cargarDocumento();

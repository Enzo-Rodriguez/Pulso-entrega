# PULSO — Pasos para probar con XAMPP y MySQL

Este documento registra la preparación realizada el 4 de septiembre de 2026 y explica cómo repetirla en otra computadora.

## 1. Preparación realizada

### Paso 1: comprobar XAMPP

Se verificó que los dos servicios necesarios estuvieran activos:

- **Apache**, que ejecuta los archivos PHP y muestra las páginas en el navegador.
- **MySQL**, que guarda y consulta la información.

En esta computadora se encontraron ejecutándose `httpd.exe` y `mysqld.exe` dentro de `C:\xampp`.

### Paso 2: revisar la base existente

Ya existía una base llamada `pulso`, pero correspondía a otra prueba y no contenía la tabla `persona` que utiliza el PHP actual.

Para no eliminar ni modificar ese trabajo, se creó una base independiente llamada:

```text
pulso_entrega
```

### Paso 3: preparar un SQL importable

Se creó el archivo `pulso_base_de_datos.sql`.

El archivo realiza estas tareas:

1. Crea la base `pulso_entrega` si todavía no existe.
2. Selecciona esa base con `USE pulso_entrega`.
3. Crea las tablas en el orden necesario para respetar las claves foráneas.
4. Carga los catálogos mínimos de funcionarios, documentos, ambulancias y traslados.
5. Crea una ambulancia y tres equipos de demostración.
6. Relaciona la ambulancia con esos equipos mediante `ambulancia_equipamiento`.

Las bases antiguas `(1)` y `(2)` se mantienen como material de comparación. El archivo que debe utilizarse para ejecutar esta entrega es:

```text
pulso_base_de_datos.sql
```

### Paso 4: configurar la conexión de PHP

En `conexion.php` se configuraron estos datos:

```text
Servidor: localhost
Usuario: root
Contraseña: vacía
Base de datos: pulso_entrega
```

Estos valores corresponden a la configuración local predeterminada de XAMPP utilizada por el equipo.

### Paso 5: importar la base

La base fue importada con el cliente de MySQL incluido en XAMPP.

El comando ejecutado fue:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 --execute="SOURCE C:/Users/53845744/Desktop/PULSO/Pulso-entrega/pulso_base_de_datos.sql"
```

Después de la importación se comprobaron 14 tablas y los datos mínimos de los catálogos.

### Paso 6: publicar el proyecto en Apache

Apache normalmente muestra los proyectos que están dentro de `C:\xampp\htdocs`.

Para no mantener dos copias diferentes del proyecto, se creó un vínculo entre la carpeta de Apache y el repositorio real:

```powershell
New-Item -ItemType Junction `
  -Path "C:\xampp\htdocs\pulso-entrega" `
  -Target "C:\Users\53845744\Desktop\PULSO\Pulso-entrega"
```

Esto significa que se continúa trabajando en la carpeta del Escritorio, pero Apache puede verla como si estuviera dentro de `htdocs`.

### Paso 7: corregir las consultas PHP

Antes de probar se corrigieron los siguientes problemas:

- el listado no consultaba `id_paciente`, aunque después lo utilizaba para crear el enlace;
- la tabla mostraba seis columnas, pero el encabezado solo declaraba cinco;
- la ficha intentaba utilizar campos que no habían sido seleccionados;
- algunos enlaces todavía apuntaban a `pacientes.html`;
- el enlace de edición usaba un nombre de archivo inexistente;
- el editor mezclaba las variables `$idPaciente` y `$id_paciente`;
- el formulario de edición enviaba los datos a un archivo con un nombre incorrecto;
- la edición no utilizaba una transacción para actualizar `persona` y `paciente` juntas;
- los datos mostrados en la ficha y el formulario no estaban escapados de manera consistente.

### Paso 8: comprobar la sintaxis

Se revisaron todos los archivos PHP con el ejecutable incluido en XAMPP:

```powershell
Get-ChildItem -File *.php | ForEach-Object {
  & "C:\xampp\php\php.exe" -l $_.FullName
}
```

Los cinco archivos PHP terminaron sin errores de sintaxis.

### Paso 9: realizar una prueba funcional

Se comprobó lo siguiente:

1. La página de inicio respondió con HTTP 200.
2. El panel respondió con HTTP 200.
3. El listado de pacientes respondió con HTTP 200.
4. El formulario de alta respondió con HTTP 200.
5. Se registró un paciente desde `nuevo-paciente.php`.
6. El paciente apareció en MySQL y en el listado.
7. La ficha individual abrió correctamente.
8. El formulario de edición mostró los datos guardados.
9. Se modificaron apellido, teléfono, correo, estado, dirección y patología.
10. Los cambios quedaron guardados después de recargar.
11. Una cédula duplicada fue rechazada sin crear una segunda persona.
12. Una ficha con un identificador inexistente respondió con HTTP 404.

El registro que quedó disponible para revisar es:

```text
Nombre: Paciente Prueba Editada
Cédula: TEST-20260904
Estado: En revisión
```

## 2. Cómo abrir el proyecto ahora

1. Abrir el panel de XAMPP.
2. Confirmar que Apache esté en estado `Running`.
3. Confirmar que MySQL esté en estado `Running`.
4. Abrir un navegador.
5. Ingresar esta dirección:

```text
http://localhost/pulso-entrega/
```

Para entrar directamente al listado:

```text
http://localhost/pulso-entrega/pacientes.php
```

No se deben abrir los archivos PHP haciendo doble clic. Siempre hay que utilizar una dirección que comience con `http://localhost/` para que Apache ejecute PHP.

## 3. Cómo instalarlo en otra computadora

### Opción A: mediante phpMyAdmin

1. Instalar y abrir XAMPP.
2. Iniciar Apache y MySQL.
3. Copiar la carpeta del proyecto dentro de:

```text
C:\xampp\htdocs\pulso-entrega
```

4. Abrir:

```text
http://localhost/phpmyadmin/
```

5. Entrar en la pestaña **Importar**.
6. Seleccionar `pulso_base_de_datos.sql`.
7. Mantener el formato SQL.
8. Presionar **Importar**.
9. Confirmar que aparezca la base `pulso_entrega`.
10. Abrir `http://localhost/pulso-entrega/`.

No es necesario crear la base manualmente, porque el propio archivo SQL lo hace.

### Opción B: mediante la terminal

Con XAMPP instalado en `C:\xampp`, abrir PowerShell dentro de la carpeta del proyecto y ejecutar:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root --default-character-set=utf8mb4 --execute="SOURCE $((Get-Location).Path.Replace('\', '/'))/pulso_base_de_datos.sql"
```

Después se copia la carpeta a `htdocs` o se crea un vínculo como el explicado anteriormente.

## 4. Recorrido manual recomendado

1. Abrir `pacientes.php`.
2. Confirmar que aparezca `Paciente Prueba Editada`.
3. Presionar **Nuevo paciente**.
4. Completar los campos obligatorios.
5. Registrar el paciente.
6. Confirmar que vuelva al listado.
7. Buscarlo por nombre o cédula.
8. Presionar **Ver ficha**.
9. Confirmar que la ficha muestre sus datos.
10. Presionar **Editar paciente**.
11. Cambiar algún dato.
12. Presionar **Guardar cambios**.
13. Confirmar que vuelva a la ficha con el dato actualizado.
14. Volver al listado y comprobar que el cambio también aparezca allí.

## 5. Consultas útiles para comprobar MySQL

Desde phpMyAdmin se puede seleccionar `pulso_entrega`, abrir la pestaña SQL y ejecutar:

```sql
SELECT
    pa.id_paciente,
    pe.nombres,
    pe.apellidos,
    pe.ci,
    pa.estado,
    pa.activo
FROM paciente AS pa
INNER JOIN persona AS pe ON pe.id_persona = pa.id_persona;
```

Esta consulta permite ver cómo un `JOIN` reúne los datos personales con los datos del paciente.

Para comprobar las tablas disponibles:

```sql
SHOW TABLES;
```

## 6. Errores frecuentes

### “No se pudo conectar con la base de datos”

Comprobar:

- que MySQL esté iniciado;
- que exista la base `pulso_entrega`;
- que `conexion.php` use el mismo nombre;
- que el usuario sea `root`;
- que la contraseña esté vacía solamente si XAMPP conserva su configuración predeterminada.

### El navegador muestra el código PHP

El archivo se abrió directamente. Hay que entrar mediante `http://localhost/pulso-entrega/`.

### Apache no encuentra el proyecto

Comprobar que exista:

```text
C:\xampp\htdocs\pulso-entrega
```

En esta computadora esa ruta es un vínculo hacia el repositorio del Escritorio.

### La base conserva datos de una prueba anterior

El SQL utiliza `CREATE DATABASE IF NOT EXISTS` y `CREATE TABLE IF NOT EXISTS`, por lo que no elimina información existente.

Si el equipo decide reiniciar la prueba, primero debe hacer una copia de seguridad. Después puede eliminar únicamente la base `pulso_entrega` desde phpMyAdmin y volver a importar `pulso_base_de_datos.sql`. No se debe eliminar la base `pulso`, porque corresponde a otro trabajo de normalización.

## 7. Estado actual

Funcionan con PHP y MySQL:

- conexión con `pulso_entrega`;
- alta de pacientes;
- listado de pacientes;
- búsqueda y filtrado en el navegador;
- ficha individual;
- modificación de persona y paciente mediante una transacción;
- control de cédula duplicada;
- control de identificadores inválidos o inexistentes.

Todavía falta implementar la baja lógica desde la interfaz para completar el ABM de pacientes.

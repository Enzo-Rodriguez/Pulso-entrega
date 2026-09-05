# Guía breve del código de pacientes

Esta guía explica la parte funcional del proyecto. No describe etiquetas HTML ni reglas CSS evidentes, porque para la defensa importa más entender cómo viajan los datos entre el formulario, PHP y MySQL.

## 1. Conexión: `conexion.php`

| Código o concepto | Explicación técnica | Explicación sencilla | ¿Se puede borrar? |
|---|---|---|---|
| Variables de servidor, usuario, contraseña y base | Son los parámetros usados por `mysqli` para abrir la conexión. | Le dicen a PHP dónde está MySQL y a qué base debe entrar. | No, aunque podrían escribirse directamente dentro de `new mysqli`; quedaría menos claro. |
| `mysqli_report(...)` | Configura MySQLi para convertir los errores en excepciones. | Hace que PHP avise de los problemas de una forma que podemos controlar. | Técnicamente sí, pero el `try/catch` dejaría de controlar algunos errores. Conviene mantenerlo. |
| `new mysqli(...)` | Crea la conexión con MySQL. | Abre la comunicación entre la página y la base. | No. |
| `set_charset("utf8mb4")` | Define la codificación de la conexión. | Evita que tildes, eñes y otros caracteres se guarden mal. | Conviene mantenerlo. |
| `try/catch` | Captura una `mysqli_sql_exception` si falla la conexión. | En vez de romper la página con un error técnico, muestra un mensaje entendible. | Se puede reducir, pero no es recomendable. |
| `http_response_code(500)` | Informa que ocurrió un error interno del servidor. | Le dice al navegador que la página falló. | Opcional para que se vea la página, pero correcto técnicamente. |

## 2. Alta: `nuevo-paciente.php`

| Código o concepto | Explicación técnica | Explicación sencilla | ¿Se puede borrar? |
|---|---|---|---|
| `$_SERVER["REQUEST_METHOD"] === "POST"` | Comprueba que la petición proviene del envío del formulario. | Solo intenta guardar cuando se presiona “Registrar paciente”. | No. Sin esto intentaría guardar también al abrir la página. |
| `require_once "conexion.php"` | Carga una sola vez el archivo que crea `$conexion`. | Conecta el formulario con la base. | No. |
| `$_POST["campo"] ?? ""` | Obtiene un valor enviado y usa texto vacío si no existe. | Lee cada casillero sin generar un error si falta alguno. | Conviene mantenerlo. |
| `trim(...)` | Elimina espacios al comienzo y al final. | Evita guardar nombres como `"  Ana  "`. | Opcional, pero útil y simple. |
| `$sexosValidos` y `$estadosValidos` | Funcionan como listas permitidas para validar los datos. | Impiden recibir valores inventados o modificados desde fuera del formulario. | Se podrían reemplazar por condiciones más largas; así está más claro. |
| Validación con `if` | Comprueba los campos obligatorios y los valores permitidos. | Evita guardar un paciente sin nombre, cédula o estado válido. | No. El atributo HTML `required` no reemplaza la validación de PHP. |
| Convertir la fecha vacía en `null` | Envía un valor SQL nulo cuando la fecha opcional no fue completada. | Le dice a MySQL “no conocemos esta fecha” en vez de mandarle una fecha vacía. | Es necesario mientras la fecha sea opcional. |
| `$fechaNacimientoParaSQL` | Escribe `NULL` cuando no existe una fecha y coloca comillas cuando sí existe. | Prepara la fecha para que MySQL pueda entenderla. | Necesario con la consulta directa actual. |
| `$conexion->query(...)` | Envía directamente una instrucción SQL a MySQL. | Ejecuta el texto que guarda, consulta, modifica o elimina datos. | No. Es la forma elegida para mantener esta entrega sencilla. |
| `$conexion->insert_id` | Recupera el identificador autogenerado de la persona. | Obtiene el número interno de la persona recién creada para relacionarla con paciente. | No; es la unión entre ambas tablas. |
| Segundo `INSERT` | Guarda el id de persona, el estado y la patología en `paciente`. | Completa la parte médica del registro. | No mientras persona y paciente sean tablas separadas. |
| `activo` ausente en el `INSERT` | MySQL usa automáticamente `DEFAULT TRUE`. | Todo paciente nuevo comienza activo sin agregar código PHP. | Está bien que no aparezca en PHP. |
| `fecha_registro` ausente en el `INSERT` | MySQL completa la columna mediante `DEFAULT CURRENT_TIMESTAMP`. | La fecha se pone sola al guardar. | Está bien que no aparezca en PHP. Agregarla sería código innecesario. |
| `header("Location: pacientes.php")` y `exit` | Redirigen después del alta y detienen el script. | Vuelve al listado y evita registrar de nuevo al actualizar el navegador. | El alta funcionaría sin redirección, pero conviene mantenerlos. |
| Código de error `1062` | Detecta una restricción `UNIQUE` duplicada. | Permite avisar específicamente que la cédula ya existe. | Opcional; sin él solo habría un mensaje genérico. |

### Por qué ahora no aparece `bind_param`

Para esta entrega se eligieron consultas directas con `query()` porque son más cortas y fáciles de presentar. Por eso ya no hacen falta variables llamadas `$stmt`, signos `?`, `bind_param()` ni letras de tipos. Esta decisión prioriza la sencillez del trabajo académico y no sería la recomendada para un sistema real expuesto a usuarios externos.

## 3. Listado: `pacientes.php`

| Código o concepto | Explicación técnica | Explicación sencilla | ¿Se puede borrar? |
|---|---|---|---|
| Consulta `SELECT` | Solicita a MySQL las columnas que necesita la tabla HTML. | Trae los pacientes para mostrarlos. | No. |
| `DATE_FORMAT(...)` | Convierte la fecha al formato `día/mes/año hora:minuto`. | Hace que la fecha sea fácil de leer. | Se puede borrar y mostrar la fecha original de MySQL, pero se vería como `2026-09-03 19:46:14`. |
| `INNER JOIN persona` | Relaciona `paciente.id_persona` con `persona.id_persona`. | Junta los datos personales con los datos médicos. | No con la base normalizada actual. |
| `ORDER BY ... DESC` | Ordena desde el id más nuevo al más antiguo. | Muestra primero los últimos pacientes registrados. | Opcional. |
| `fetch_all(MYSQLI_ASSOC)` | Convierte el resultado en un arreglo asociativo. | Deja los pacientes listos para recorrerlos con PHP. | No, salvo que se reescriba todo el recorrido de otra manera. |
| `escapar(...)` | Aplica `htmlspecialchars` antes de imprimir datos de la base. | Evita que un dato guardado pueda convertirse en código dentro de la página. | No conviene borrarlo. |
| Arreglo `$estados` | Traduce el valor interno a una etiqueta y una clase CSS. | Decide qué texto y color corresponde a cada estado. | Se puede simplificar si se acepta mostrar directamente `en_revision` y perder los colores. |
| Comprobación de `activo` | Permite representar una baja lógica como “Inactivo”. | Un paciente puede dejar de estar activo sin borrarlo. | Hoy podría quitarse, pero será útil para completar la baja del ABM. Mantener. |
| `foreach ($pacientes as $paciente)` | Repite la fila HTML por cada elemento del arreglo. | Dibuja un renglón por paciente. | No. |
| Mensaje “No hay pacientes” | Cubre el caso de un resultado vacío. | Evita mostrar una tabla en blanco sin explicación. | Opcional. |

## 4. Búsqueda del listado: JavaScript dentro de `pacientes.php`

| Código o concepto | Explicación técnica | Explicación sencilla | ¿Se puede borrar? |
|---|---|---|---|
| Referencias obtenidas con `getElementById` y `querySelectorAll` | Permiten que JavaScript manipule el formulario, las filas y el contador. | Identifican las partes de la página que se van a filtrar. | Solo son necesarias si se conserva la búsqueda. |
| `normalizar(...)` | Quita diacríticos y convierte el texto a minúsculas. | Hace que buscar `critico` también encuentre `Crítico`. | Se puede reducir a `toLowerCase()`, pero la búsqueda con tildes sería peor. |
| `actualizarListado()` | Compara texto y estado, cambia `hidden` y cuenta coincidencias. | Oculta lo que no coincide con la búsqueda. | Todo el filtro es opcional; el alta y el listado PHP siguen funcionando sin él. |
| `preventDefault()` | Impide el envío normal del formulario. | Evita recargar la página al buscar. | Necesario para este filtro en JavaScript. |
| `addEventListener(...)` | Ejecuta el filtro al enviar, escribir o cambiar el estado. | Hace que la búsqueda reaccione a lo que hace el usuario. | Solo es necesario si se conserva el filtro. |

## 5. Base de datos

| Código o concepto | Explicación técnica | Explicación sencilla | ¿Se puede borrar? |
|---|---|---|---|
| Tablas `persona` y `paciente` separadas | Evitan mezclar datos generales con datos propios del rol paciente. | Una persona guarda nombre y cédula; paciente guarda su información asistencial. | No conviene unirlas porque perderíamos normalización. |
| `PRIMARY KEY` | Identifica de forma única cada fila. | Es el número interno irrepetible de cada registro. | No. |
| `FOREIGN KEY (id_persona)` | Garantiza que todo paciente apunte a una persona existente. | No permite crear un paciente “sin dueño”. | No conviene borrarla. |
| `UNIQUE` en `persona.ci` | Impide repetir una cédula. | Una misma persona no puede registrarse dos veces. | No conviene borrarlo. |
| `DEFAULT CURRENT_TIMESTAMP` | Asigna la fecha y hora del servidor al insertar. | MySQL coloca automáticamente cuándo se creó el paciente. | No si queremos mostrar la fecha de registro sin agregar lógica PHP. |
| `estado` | Guarda la situación clínica actual. | Indica si está estable, en revisión o crítico. | Necesario para la funcionalidad actual. |
| `patologia` | Guarda el motivo o patología, y admite `NULL`. | Puede quedar sin completar si todavía no se conoce. | Se puede borrar solo si deciden no registrar información médica. |
| `activo` | Permite hacer una baja lógica. | Oculta o marca como inactivo sin eliminar definitivamente. | Conviene mantenerlo para el ABM. |

## 6. Qué podríamos reducir sin romper el alta

Las reducciones razonables, desde la más segura hasta la que más funcionalidad quita, son:

1. Quitar el caso especial del error `1062` y dejar un único mensaje de error.
2. Quitar `ORDER BY` si no importa el orden del listado.
3. Quitar el formulario de búsqueda y todo el bloque JavaScript. El alta y el listado seguirán funcionando, pero ya no se podrá filtrar.

No conviene quitar la validación de PHP, `insert_id`, `escapar()` ni la relación entre persona y paciente porque cumplen funciones visibles en el proyecto actual.

## 7. Archivos SQL

El esquema principal de esta versión es `pulso_base_de_datos.sql`, que crea `pulso_entrega`. Los archivos numerados son versiones anteriores; mantener los tres puede causar confusión durante la defensa. Para la entrega final conviene dejar uno solo cuando el equipo confirme que contiene las tablas necesarias para los cinco ABM.

## 8. Explicación corta para la defensa

> El formulario envía los datos por POST. PHP valida los campos e inserta primero la persona con una consulta directa. Después recupera su id autogenerado y lo usa para insertar el paciente. El listado usa un JOIN para reunir los datos de las dos tablas y escapa cada valor antes de mostrarlo. MySQL genera automáticamente la fecha de registro y el estado activo inicial.

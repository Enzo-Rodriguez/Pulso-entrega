<?php

// Comprueba y conserva solamente la línea del recorrido y sus medidas.
function normalizarRutaCalculada(mixed $ruta, mixed $distancia, mixed $duracion): array
{
    if (!is_numeric($distancia) || !is_numeric($duracion)) {
        throw new RuntimeException("El servicio de recorridos devolvió medidas inválidas.", 502);
    }
    $distancia = (float) $distancia;
    $duracion = (float) $duracion;
    if (
        !is_finite($distancia) || !is_finite($duracion) ||
        $distancia < 0 || $duracion < 0 || ceil($duracion) >= PHP_INT_MAX
    ) {
        throw new RuntimeException("El servicio de recorridos devolvió medidas inválidas.", 502);
    }
    if (
        !is_array($ruta) || ($ruta["type"] ?? null) !== "LineString" ||
        !is_array($ruta["coordinates"] ?? null) || !array_is_list($ruta["coordinates"]) ||
        count($ruta["coordinates"]) < 2 || count($ruta["coordinates"]) > 100000
    ) {
        throw new RuntimeException("El servicio de recorridos devolvió una ruta inválida.", 502);
    }

    $coordenadas = [];
    foreach ($ruta["coordinates"] as $punto) {
        if (
            !is_array($punto) || !array_is_list($punto) || count($punto) !== 2 ||
            !is_numeric($punto[0]) || !is_numeric($punto[1])
        ) {
            throw new RuntimeException("El servicio de recorridos devolvió una ruta inválida.", 502);
        }
        $longitud = (float) $punto[0];
        $latitud = (float) $punto[1];
        if (!is_finite($longitud) || !is_finite($latitud) || abs($longitud) > 180 || abs($latitud) > 90) {
            throw new RuntimeException("El servicio de recorridos devolvió una ruta inválida.", 502);
        }
        $coordenadas[] = [$longitud, $latitud];
    }

    return [
        "ruta" => ["type" => "LineString", "coordinates" => $coordenadas],
        "distancia_metros" => $distancia,
        "duracion_segundos" => (int) ceil($duracion),
    ];
}

// OSRM recibe longitud,latitud; el GeoJSON mantiene ese mismo orden.
function calcularRutaTraslado(float $origenLatitud, float $origenLongitud, float $destinoLatitud, float $destinoLongitud): array
{
    if (
        !is_finite($origenLatitud) || !is_finite($origenLongitud) ||
        !is_finite($destinoLatitud) || !is_finite($destinoLongitud) ||
        abs($origenLatitud) > 90 || abs($destinoLatitud) > 90 ||
        abs($origenLongitud) > 180 || abs($destinoLongitud) > 180
    ) {
        throw new RuntimeException("Las ubicaciones del recorrido no son válidas.", 400);
    }
    if (!function_exists("curl_init")) {
        throw new RuntimeException("El servidor necesita cURL para calcular recorridos.", 503);
    }

    $puntos = number_format($origenLongitud, 7, ".", "") . "," . number_format($origenLatitud, 7, ".", "") . ";" .
        number_format($destinoLongitud, 7, ".", "") . "," . number_format($destinoLatitud, 7, ".", "");
    $parametros = http_build_query([
        "overview" => "full",
        "geometries" => "geojson",
        "steps" => "false",
        "alternatives" => "false",
        "radiuses" => "1000;1000",
    ], "", "&", PHP_QUERY_RFC3986);
    $consultaRuta = curl_init("https://router.project-osrm.org/route/v1/driving/" . $puntos . "?" . $parametros);
    $contenidoRuta = "";
    $respuestaDemasiadoGrande = false;
    curl_setopt_array($consultaRuta, [
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => "PULSO/1.0 (academic routing project)",
        CURLOPT_HTTPHEADER => ["Accept: application/json"],
        CURLOPT_WRITEFUNCTION => function ($consulta, string $fragmento) use (&$contenidoRuta, &$respuestaDemasiadoGrande): int {
            if (strlen($contenidoRuta) + strlen($fragmento) > 5 * 1024 * 1024) {
                $respuestaDemasiadoGrande = true;
                return 0;
            }
            $contenidoRuta .= $fragmento;
            return strlen($fragmento);
        },
    ]);
    $respuestaRuta = curl_exec($consultaRuta);
    $errorConexion = curl_errno($consultaRuta);
    $codigoRuta = curl_getinfo($consultaRuta, CURLINFO_HTTP_CODE);
    curl_close($consultaRuta);

    if ($respuestaDemasiadoGrande) {
        throw new RuntimeException("El recorrido recibido es demasiado grande.", 502);
    }
    if ($errorConexion === CURLE_OPERATION_TIMEDOUT) {
        throw new RuntimeException("El cálculo del recorrido demoró demasiado. Probá de nuevo.", 504);
    }
    if ($respuestaRuta === false || $errorConexion !== 0) {
        throw new RuntimeException("No se pudo conectar con el servicio de recorridos.", 502);
    }
    if ($codigoRuta === 429) {
        throw new RuntimeException("Se alcanzó el límite de recorridos. Esperá y probá de nuevo.", 429);
    }
    if ($codigoRuta !== 200) {
        // OSRM puede responder 400 cuando no hay una calle próxima al punto.
        if ($codigoRuta === 400) {
            $errorRuta = json_decode($contenidoRuta, true);
            if (is_array($errorRuta) && in_array($errorRuta["code"] ?? null, ["NoRoute", "NoSegment"], true)) {
                throw new RuntimeException("No se encontró un recorrido por calles entre esos puntos. Revisá el origen y el destino.", 422);
            }
        }
        throw new RuntimeException("El servicio de recorridos no está disponible. Probá de nuevo.", 502);
    }

    $datosRuta = json_decode($contenidoRuta, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($datosRuta)) {
        throw new RuntimeException("El servicio de recorridos devolvió una respuesta inválida.", 502);
    }
    if (in_array($datosRuta["code"] ?? null, ["NoRoute", "NoSegment"], true)) {
        throw new RuntimeException("No se encontró un recorrido por calles entre esos puntos. Revisá el origen y el destino.", 422);
    }
    if (($datosRuta["code"] ?? null) !== "Ok" || !is_array($datosRuta["routes"][0] ?? null)) {
        throw new RuntimeException("El servicio de recorridos no pudo completar el cálculo.", 502);
    }

    $recorrido = $datosRuta["routes"][0];
    return normalizarRutaCalculada($recorrido["geometry"] ?? null, $recorrido["distance"] ?? null, $recorrido["duration"] ?? null);
}

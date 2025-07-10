<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->map(['POST', 'OPTIONS'], '/registrarInstancia', function (Request $request, Response $response, $args) {
    // Configuración inicial
    define('DATA_FILE', __DIR__ . '/../instancias.json');
    $max_instances = 1000; // Límite máximo de instancias registradas

    // Habilitar CORS completamente abierto
    $response = $response
        ->withHeader("Access-Control-Allow-Origin", "*")
        ->withHeader("Access-Control-Allow-Methods", "POST, OPTIONS")
        ->withHeader("Access-Control-Allow-Headers", "Content-Type");

    // Manejar preflight requests para CORS
    if ($request->getMethod() === 'OPTIONS') {
        return $response->withStatus(200);
    }

    // Leer y validar datos de entrada
    $rawBody = $request->getBody()->getContents();

    // DEBUG extra: loguear siempre el cuerpo recibido para depuración de errores 500
    error_log("registrarInstancia.php RAW BODY: " . $rawBody);

    $input = json_decode($rawBody, true);

    // DEBUG: Si el JSON es inválido, mostrar el cuerpo recibido para depuración
    if (json_last_error() !== JSON_ERROR_NONE || !$input) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Datos JSON inválidos',
            'debug_body' => $rawBody,
            'json_last_error' => json_last_error_msg()
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Validar campos requeridos
    $required_fields = ['url', 'api_key'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => "Campo requerido faltante: $field"
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    // Sanitizar URL
    $url = filter_var($input['url'], FILTER_SANITIZE_URL);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'URL no válida'
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Sanitizar API key (32 caracteres alfanuméricos)
    $api_key = preg_replace('/[^a-zA-Z0-9]/', '', $input['api_key']);
    if (strlen($api_key) !== 32) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'API key no válida (debe tener 32 caracteres alfanuméricos)'
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // Sanitizar endpoints si existen
    $endpoints = [];
    if (isset($input['endpoints']) && is_array($input['endpoints'])) {
        foreach ($input['endpoints'] as $type => $endpoint_url) {
            $clean_url = filter_var($endpoint_url, FILTER_SANITIZE_URL);
            if (filter_var($clean_url, FILTER_VALIDATE_URL)) {
                $endpoints[$type] = $clean_url;
            }
        }
    }

    // Cargar o inicializar datos existentes
    $instancias = [];
    if (file_exists(DATA_FILE)) {
        $instancias = json_decode(file_get_contents(DATA_FILE), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $instancias = [];
        }
    }

    // Verificar si la instancia ya existe
    $found = false;
    foreach ($instancias as &$instancia) {
        if ($instancia['url'] === $url) {
            // Verificar API key para actualización
            if ($instancia['api_key'] !== $api_key) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'API key no coincide para esta instancia'
                ]));
                return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
            }

            // Actualizar datos existentes
            $instancia['endpoints'] = $endpoints;
            $instancia['ultima_confirmacion'] = date('Y-m-d H:i:s');
            $instancia['activa'] = true;
            $found = true;
            break;
        }
    }

    // Si no existe, agregar nueva instancia (si no hemos alcanzado el límite)
    if (!$found) {
        if (count($instancias) >= $max_instances) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Límite de instancias alcanzado'
            ]));
            return $response->withStatus(507)->withHeader('Content-Type', 'application/json');
        }

        $instancias[] = [
            'url' => $url,
            'api_key' => $api_key,
            'endpoints' => $endpoints,
            'fecha_registro' => date('Y-m-d H:i:s'),
            'ultima_confirmacion' => date('Y-m-d H:i:s'),
            'activa' => true
        ];
    }

    // Guardar datos
    if (file_put_contents(DATA_FILE, json_encode($instancias, JSON_PRETTY_PRINT)) === false) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Error al guardar datos'
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }

    // Respuesta exitosa
    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => $found ? 'Instancia actualizada' : 'Instancia registrada'
    ]));
    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
});

// Función para limpiar instancias inactivas (ejecutar ocasionalmente)
function limpiarInstanciasInactivas() {
    $data_file = __DIR__ . '/../instancias.json';
    if (!file_exists($data_file)) return;

    $instancias = json_decode(file_get_contents($data_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) return;

    $limite = date('Y-m-d H:i:s', strtotime('-30 days'));

    $instancias = array_filter($instancias, function($instancia) use ($limite) {
        // Mantener solo instancias activas confirmadas en los últimos 30 días
        return $instancia['activa'] &&
               isset($instancia['ultima_confirmacion']) &&
               strtotime($instancia['ultima_confirmacion']) >= strtotime($limite);
    });

    file_put_contents($data_file, json_encode(array_values($instancias), JSON_PRETTY_PRINT));
}

// Ejecutar limpieza (10% de probabilidad en cada request)
if (rand(1, 10) === 1) {
    limpiarInstanciasInactivas();
}

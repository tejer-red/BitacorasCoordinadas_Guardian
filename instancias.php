<?php
header('Content-Type: application/json');

// Configuración inicial
define('DATA_FILE', __DIR__ . '/instancias.json');
$max_instances = 1000; // Límite máximo de instancias registradas

// Habilitar CORS completamente abierto
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejar preflight requests para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Permitir GET para consulta pública de instancias registradas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists(DATA_FILE)) {
        $instancias = json_decode(file_get_contents(DATA_FILE), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Filtrar datos sensibles antes de mostrar
            $instancias_publicas = array_map(function($instancia) {
                return [
                    'url' => $instancia['url'],
                    'endpoints' => $instancia['endpoints'] ?? [],
                    'ultima_confirmacion' => $instancia['ultima_confirmacion'] ?? null
                ];
            }, $instancias);
            
            http_response_code(200);
            echo json_encode($instancias_publicas, JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al leer datos existentes']);
        }
    } else {
        http_response_code(200);
        echo json_encode([]);
    }
    exit;
}

// Solo permitir método POST para registro
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Leer y validar datos de entrada
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE || !$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos JSON inválidos']);
    exit;
}

// Validar campos requeridos
$required_fields = ['url', 'api_key'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Campo requerido faltante: $field"]);
        exit;
    }
}

// Sanitizar URL
$url = filter_var($input['url'], FILTER_SANITIZE_URL);
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL no válida']);
    exit;
}

// Sanitizar API key (32 caracteres alfanuméricos)
$api_key = preg_replace('/[^a-zA-Z0-9]/', '', $input['api_key']);
if (strlen($api_key) !== 32) {
    http_response_code(400);
    echo json_encode(['error' => 'API key no válida (debe tener 32 caracteres alfanuméricos)']);
    exit;
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
            http_response_code(403);
            echo json_encode(['error' => 'API key no coincide para esta instancia']);
            exit;
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
        http_response_code(507);
        echo json_encode(['error' => 'Límite de instancias alcanzado']);
        exit;
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
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar datos']);
    exit;
}

// Respuesta exitosa
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => $found ? 'Instancia actualizada' : 'Instancia registrada',
    'total_instancias' => count($instancias),
    'endpoints_registrados' => array_keys($endpoints)
]);

// Función para limpiar instancias inactivas (ejecutar ocasionalmente)
function limpiarInstanciasInactivas() {
    if (!file_exists(DATA_FILE)) return;
    
    $instancias = json_decode(file_get_contents(DATA_FILE), true);
    if (json_last_error() !== JSON_ERROR_NONE) return;
    
    $limite = date('Y-m-d H:i:s', strtotime('-30 days'));
    
    $instancias = array_filter($instancias, function($instancia) use ($limite) {
        // Mantener solo instancias activas confirmadas en los últimos 30 días
        return $instancia['activa'] && 
               isset($instancia['ultima_confirmacion']) && 
               strtotime($instancia['ultima_confirmacion']) >= strtotime($limite);
    });
    
    file_put_contents(DATA_FILE, json_encode(array_values($instancias), JSON_PRETTY_PRINT));
}

// Ejecutar limpieza (10% de probabilidad en cada request)
if (rand(1, 10) === 1) {
    limpiarInstanciasInactivas();
}


<?php
require __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Incluir rutas externas
require __DIR__ . '/routes/validarNodo.php';
require __DIR__ . '/routes/registrarInstancia.php';
require __DIR__ . '/routes/exportar.php';

// Página de inicio (HTML)
$app->get('/', function (Request $request, Response $response, $args) use ($app) {
    // Obtener rutas registradas
    $routes = [];
    foreach ($app->getRouteCollector()->getRoutes() as $route) {
        $pattern = $route->getPattern();
        $methods = implode(', ', $route->getMethods());
        $routes[] = "<li><code>{$methods}</code> <a href=\"{$pattern}\">{$pattern}</a></li>";
    }
    $routes_html = implode("\n", $routes);

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Posts</title>
</head>
<body>
  <h1>API CACHE - INDEX</h1>
    <p>Pagina de inicio, en desarrollo endpoints</p>
    <ul>
    {$routes_html}
    </ul>
</body>
</html>
HTML;
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// Ruta para servir archivos estáticos de /output con CORS habilitado
$app->get('/output/{params:.*}', function (Request $request, Response $response, $args) {
    $file = __DIR__ . '/output/' . $args['params'];
    if (!file_exists($file) || !is_file($file)) {
        return $response->withStatus(404)->write('Archivo no encontrado');
    }
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $mime = 'text/plain';
    if ($ext === 'json') $mime = 'application/json';
    if ($ext === 'html') $mime = 'text/html';
    if ($ext === 'jpg' || $ext === 'jpeg') $mime = 'image/jpeg';
    if ($ext === 'png') $mime = 'image/png';
    if ($ext === 'gif') $mime = 'image/gif';
    $stream = fopen($file, 'rb');
    $body = new \Slim\Psr7\Stream($stream);
    return $response
        ->withHeader('Content-Type', $mime)
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withBody($body);
});

$app->run();
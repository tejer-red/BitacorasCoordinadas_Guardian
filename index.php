<?php
require __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Incluir rutas externas
require __DIR__ . '/routes/validarNodo.php';
require __DIR__ . '/routes/registrarInstancia.php';

// Página de inicio (HTML)
$app->get('/', function (Request $request, Response $response, $args) {
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
</body>
</html>
HTML;
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->run();
<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Endpoint de prueba de conexión renombrado a /validarNodo
$app->get('/validarNodo', function (Request $request, Response $response, $args) {
    $host = $request->getUri()->getHost();
    $data = [
        'message' => 'Esta instancia del concentrador es valida y está activa.',
        'domain' => $host,
        'valid' => true,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});

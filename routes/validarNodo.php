<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Endpoint de prueba de conexión renombrado a /validarNodo
$app->get('/validarNodo', function (Request $request, Response $response, $args) {
    $data = ['mensaje' => 'endpoint de test valido'];
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});

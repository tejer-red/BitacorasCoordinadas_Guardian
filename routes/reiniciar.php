<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Función recursiva para borrar archivos y carpetas (solo rutas relativas)
function borrarTodo($dir, &$deleted = [], &$errors = [], $base = null) {
    if ($base === null) $base = realpath($dir);
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        $relPath = ltrim(str_replace($base, '', realpath($path)), '/\\');
        if (is_dir($path)) {
            borrarTodo($path, $deleted, $errors, $base);
            if (@rmdir($path)) {
                $deleted[] = $relPath;
            } else {
                $errors[] = $relPath;
            }
        } else {
            if (@unlink($path)) {
                $deleted[] = $relPath;
            } else {
                $errors[] = $relPath;
            }
        }
    }
}

$app->map(['POST', 'GET'], '/reiniciar', function (Request $request, Response $response) {
    $outputDir = __DIR__ . '/../output';
    $deleted = [];
    $errors = [];

    if (is_dir($outputDir)) {
        borrarTodo($outputDir, $deleted, $errors);
    }

    $result = [
        'deleted' => $deleted,
        'errors' => $errors,
        'status' => empty($errors) ? 'ok' : 'partial'
    ];

    $response->getBody()->write(json_encode($result));
    return $response->withHeader('Content-Type', 'application/json');
});

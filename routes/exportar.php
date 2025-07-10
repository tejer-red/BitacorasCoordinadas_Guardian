<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app->get('/exportar', function (Request $request, Response $response, $args) {
    // Si no se ha enviado el formulario, muestra la advertencia y el botón
    if (!isset($_GET['iniciar'])) {
        $instancias_url = __DIR__ . '/../instancias.json';
        $instancias = [];
        if (file_exists($instancias_url)) {
            $instancias = json_decode(file_get_contents($instancias_url), true);
        }
        $total = is_array($instancias) ? count($instancias) : 0;

        // Construir lista de nombres o URLs de instancias
        $lista_instancias = '';
        if ($total > 0) {
            $lista_instancias .= "<ul>";
            foreach ($instancias as $inst) {
                $nombre = isset($inst['nombre']) && $inst['nombre'] ? $inst['nombre'] : $inst['url'];
                $lista_instancias .= "<li>" . htmlspecialchars($nombre) . "</li>";
            }
            $lista_instancias .= "</ul>";
        }

        $html = "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Exportar Bitácoras</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .aviso { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 15px; margin-bottom: 20px; }
        .boton { background: #007bff; color: #fff; border: none; padding: 10px 20px; font-size: 1.1em; border-radius: 4px; cursor: pointer; }
        .boton:hover { background: #0056b3; }
        .instancias-list { margin: 10px 0 0 0; }
    </style>
</head>
<body>
    <h1>Exportar Bitácoras</h1>
    <div class='aviso'>
        <strong>Advertencia:</strong> El proceso de exportación puede ser tardado.<br>
        Se detectaron <strong>{$total}</strong> instancias en <code>instancias.json</code>.<br>
        <div class='instancias-list'>
            <strong>Instancias:</strong>
            {$lista_instancias}
        </div>
        Por favor, no cierres esta ventana hasta que el proceso termine.
    </div>
    <form method='get' action=''>
        <input type='hidden' name='iniciar' value='1'>
        <button type='submit' class='boton'>Iniciar exportación</button>
    </form>
</body>
</html>";
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    // Deshabilita la compresión de salida si está activa
    ignore_user_abort(false); // Detener proceso si el usuario cierra el navegador
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', 1);
    }
    @ini_set('zlib.output_compression', 0);
    @ini_set('output_buffering', 'off');
    @ini_set('implicit_flush', 1);
    while (ob_get_level() > 0) { ob_end_flush(); }
    ob_implicit_flush(1);

    // Envía headers y abre el body lo antes posible
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Proceso de Exportación</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .log { margin-bottom: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9; }
        .detalles { margin-top: 20px; padding: 10px; border: 1px solid #ccc; background: #f1f1f1; }
        .success { color: green; }
        .info { color: blue; }
        .warning { color: orange; }
        img { max-width: 300px; height: auto; margin: 5px 0; }
        .gallery { display: flex; flex-wrap: wrap; gap: 10px; }
        .gallery img { max-width: calc(25% - 10px); height: auto; flex: 1 1 calc(25% - 10px); }
    </style>
</head>
<body>
<h1>Proceso de Exportación</h1>
<div class='log'>
";
    flush(); if (ob_get_level() > 0) { ob_flush(); }

    require_once __DIR__ . '/../lib/fetcher.php';
    require_once __DIR__ . '/../lib/exporter.php';
    require_once __DIR__ . '/../lib/debugger.php';

    $instancias_url = __DIR__ . '/../instancias.json';
    $instancias = json_decode(file_get_contents($instancias_url), true);

    $general_report = [];

    // Redefinir las funciones para imprimir y flush en cada paso relevante
    function process_instance($instancia, &$general_report) {
        print_r($instancia);
        if (!$instancia['activa']) {
            debug_warning("Instancia inactiva: <strong>{$instancia['url']}</strong>");
            flush(); if (ob_get_level() > 0) { ob_flush(); }
            return;
        }
        $base = parse_url($instancia['url'], PHP_URL_HOST);
        debug_info("🌍 Procesando instancia: <strong>{$base}</strong>");
        flush(); if (ob_get_level() > 0) { ob_flush(); }
        foreach ($instancia['endpoints'] as $tipo => $endpoint) {
            process_endpoint($base, $tipo, $endpoint, $instancia['url'], $general_report);
        }
    }

    function process_endpoint($base, $tipo, $endpoint, $base_url, &$general_report) {
        debug_info("📥 Tipo: <strong>{$tipo}</strong>");
        debug_info("🔍 Tipo de endpoint: " . (is_string($endpoint) ? "string" : (is_array($endpoint) ? "array" : "otro tipo")));
        if (is_string($endpoint)) {
            $endpoint .= "?per_page=100";
        } elseif (is_array($endpoint)) {
            foreach ($endpoint as &$url) {
                if (is_string($url)) {
                    $url .= "?per_page=100";
                }
            }
        }
        debug_info("🔗 Endpoint: <strong>{$endpoint}</strong>");
        flush(); if (ob_get_level() > 0) { ob_flush(); }
        $posts = fetch_posts($endpoint);
        if (empty($posts)) {
            debug_warning("No se encontraron posts para el endpoint: <strong>{$endpoint}</strong>");
            flush(); if (ob_get_level() > 0) { ob_flush(); }
            return;
        }
        $all_posts = [];
        foreach ($posts as $post) {
            process_post($base, $tipo, $post, $base_url, $all_posts, $general_report);
        }
        generate_post_list_html($base, $tipo, $all_posts);
        $listing_url = "/output/{$base}/{$tipo}/index.html";
        debug_success("✅ Accede al listado de posts aquí: <a href='{$listing_url}'>{$listing_url}</a>");
        flush(); if (ob_get_level() > 0) { ob_flush(); }
    }

    function process_post($base, $tipo, $post, $base_url, &$all_posts, &$general_report) {
        $id = $post['id'];
        // Ruta donde se guarda el JSON del post
        $json_path = __DIR__ . "/../output/{$base}/{$tipo}/{$id}.json";
        if (file_exists($json_path)) {
            // Si existe, cargar datos del JSON y validar que sea un array
            $saved = json_decode(file_get_contents($json_path), true);
            if (is_array($saved)) {
                debug_info("🔄 Post ID {$id} ya exportado, usando cache.");
                flush(); if (ob_get_level() > 0) { ob_flush(); }
                $all_posts[] = [
                    'id' => $saved['id'],
                    'title' => is_array($saved['title']) ? $saved['title']['rendered'] : $saved['title'],
                    'meta' => $saved['meta'],
                    'slug' => $saved['slug'],
                    'date' => $saved['date'],
                ];
                $general_report[] = [
                    'instance' => $base,
                    'type' => $tipo,
                    'id' => $saved['id'],
                    'title' => is_array($saved['title']) ? $saved['title']['rendered'] : $saved['title'],
                    'meta' => $saved['meta'],
                    'slug' => $saved['slug'],
                    'date' => $saved['date'],
                    'media_url' => $saved['media_url'] ?? null,
                    'taxonomies' => $saved['taxonomies'] ?? [],
                ];
                return;
            } else {
                debug_warning("⚠️ El JSON cacheado para el post ID {$id} está corrupto o no es un array. Se ignorará el cache y se volverá a descargar.");
                // Opcional: unlink($json_path);
            }
        }

        $meta_url = "{$base_url}";
        $meta = fetch_meta($meta_url);
        $post['meta'] = $meta;
        $taxonomies = [];
        $taxonomy_links = extract_taxonomy_links($post);
        foreach ($taxonomy_links as $tax_name => $tax_url) {
            debug_info("🔍 Taxonomía encontrada: <strong>{$tax_name}</strong> -> <a href='{$tax_url}'>{$tax_url}</a>");
            flush(); if (ob_get_level() > 0) { ob_flush(); }
            $tax_data = fetch_terms($tax_url);
            foreach ($tax_data as $term) {
                $taxonomies[] = [
                    'id' => $term['id'],
                    'name' => $term['name'],
                    'slug' => $term['slug'],
                    'taxonomy' => $term['taxonomy']
                ];
            }
        }
        $media_url = null;
        if (isset($post['featured_media']) && $post['featured_media']) {
            $media_url = fetch_media_url($base_url, $post['featured_media']);
            $post['media_url'] = $media_url;
            debug_success("✅ Imagen destacada: <img class='featured' src='{$media_url}' alt='Imagen destacada'>");
            flush(); if (ob_get_level() > 0) { ob_flush(); }
        }
        $post_type = $tipo;
        $post_id = $post['id'];
        $custom_meta = fetch_custom_meta($base_url, $post_type, $post_id);
        if (isset($post['meta']['gallery_urls'])) {
            $custom_meta['gallery_urls'] = $post['meta']['gallery_urls'];
        }
        $post['meta'] = array_merge($post['meta'], $custom_meta);
        if (isset($post['meta']['galeria'])) {
            debug_info("🔍 Verificando galería de medios para el post ID: <strong>{$id}</strong>");
            flush(); if (ob_get_level() > 0) { ob_flush(); }
            if (is_array($post['meta']['galeria'])) {
                debug_info("🔍 Galería encontrada:");
                $gallery_urls = [];
                foreach ($post['meta']['galeria'] as $gallery_id) {
                    $gallery_url = fetch_media_url($base_url, $gallery_id);
                    if ($gallery_url) {
                        $gallery_urls[] = $gallery_url;
                    } else {
                        debug_warning("⚠️ No se encontró URL para ID: {$gallery_id}");
                    }
                    flush(); if (ob_get_level() > 0) { ob_flush(); }
                }
                debug_gallery($gallery_urls);
                $post['meta']['gallery_urls'] = $gallery_urls;
            } else {
                debug_warning("⚠️ El campo 'galeria' no es un array para el post ID: <strong>{$id}</strong>");
                flush(); if (ob_get_level() > 0) { ob_flush(); }
            }
        } else {
            debug_info("ℹ️ El campo 'galeria' no está presente en los meta datos para el post ID: <strong>{$id}</strong> (esto es opcional).");
            flush(); if (ob_get_level() > 0) { ob_flush(); }
        }
        debug_post_details($post, $taxonomy_links, $post['media_url'] ?? null, $post['meta']['gallery_urls'] ?? []);
        flush(); if (ob_get_level() > 0) { ob_flush(); }
        $all_posts[] = [
            'id' => $id,
            'title' => $post['title']['rendered'],
            'meta' => $post['meta'],
            'slug' => $post['slug'],
            'date' => $post['date'],
        ];
        $general_report[] = [
            'instance' => $base,
            'type' => $tipo,
            'id' => $id,
            'title' => $post['title']['rendered'],
            'meta' => $post['meta'],
            'slug' => $post['slug'],
            'date' => $post['date'],
            'media_url' => $media_url,
            'taxonomies' => $taxonomies,
        ];
        save_post_json($base, $tipo, $post, $taxonomies);
        generate_post_html($base, $tipo, $post);
        flush(); if (ob_get_level() > 0) { ob_flush(); }
    }

    foreach ($instancias as $instancia) {
        process_instance($instancia, $general_report);
    }
    generate_general_report($general_report);
    generate_general_json($general_report);

    debug_success("✅ Reporte general generado en <a href='/output/general_report.html'>/output/general_report.html</a>");
    debug_success("✅ Archivos JSON generados correctamente.");
    debug_end_html();
    echo "</div></body></html>";
    flush(); if (ob_get_level() > 0) { ob_flush(); }

    // No uses $response->getBody()->write(), ya que ya se imprimió todo
    return $response->withHeader('Content-Type', 'text/html');
});

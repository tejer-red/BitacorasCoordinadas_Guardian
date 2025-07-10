<?php

require_once 'debugger.php';

function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return "{$protocol}://{$host}{$script_dir}";
}

function generate_post_html($base, $tipo, $post) {
    $base_url = get_base_url();
    $export_header = debug_export_header($base, $tipo);

    // Corrige el título para soportar array o string
    $title = is_array($post['title']) && isset($post['title']['rendered']) ? $post['title']['rendered'] : $post['title'];

    $html_content = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title}</title>
</head>
<body>
    {$export_header}
    <h1>{$title}</h1>
    <p><strong>Fecha:</strong> {$post['date']}</p>
    <p><strong>Slug:</strong> {$post['slug']}</p>
    <h2>Meta Datos</h2>
    <ul>";

    // Include meta data
    foreach ($post['meta'] as $key => $value) {
        if (is_array($value)) {
            $value = implode(', ', array_map('strval', $value));
        }
        $html_content .= "<li><strong>{$key}:</strong> {$value}</li>";
    }

    $html_content .= "</ul>
    <h2>Taxonomías</h2>
    <ul>";

    // Include taxonomies
    if (!empty($post['taxonomies'])) {
        foreach ($post['taxonomies'] as $taxonomy) {
            $html_content .= "<li><strong>{$taxonomy['taxonomy']}:</strong> {$taxonomy['name']} (Slug: {$taxonomy['slug']}, ID: {$taxonomy['id']})</li>";
        }
    } else {
        $html_content .= "<li>No hay taxonomías disponibles.</li>";
    }

    $html_content .= "</ul>
    <h2>Imágenes</h2>
    <p><strong>Imagen destacada:</strong></p>
    " . (isset($post['media_url']) ? "<img src='{$post['media_url']}' alt='Imagen destacada' style='max-width: 100%;'>" : "<p>No disponible</p>") . "
    <h3>Galería</h3>
    " . (isset($post['meta']['gallery_urls']) ? implode('', array_map(fn($url) => "<img src='{$url}' alt='Galería' style='max-width: 100%; margin: 5px;'>", $post['meta']['gallery_urls'])) : "<p>No disponible</p>") . "
</body>
</html>
";

    $html_path = "output/{$base}/{$tipo}/{$post['id']}.html";
    ensure_directory_exists(dirname($html_path));
    file_put_contents($html_path, $html_content);
    echo "  ✅ HTML generado en <a href='{$base_url}/{$html_path}'>{$base_url}/{$html_path}</a>\n";
}

function generate_post_list_html($base, $tipo, $all_posts) {
    $base_url = get_base_url();
    $export_header = debug_export_header($base, $tipo);

    $list_html = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Listado de Posts</title>
</head>
<body>
    {$export_header}
    <h1>Listado de Posts</h1>
";

    foreach ($all_posts as $post) {
        $title = is_array($post['title']) && isset($post['title']['rendered']) ? $post['title']['rendered'] : $post['title'];
        $post_url = "{$base_url}/output/{$base}/{$tipo}/{$post['id']}.html";
        $list_html .= "
        <div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>
            <h2><a href='{$post_url}'>{$title}</a></h2>
            <p><strong>Fecha:</strong> {$post['date']}</p>
            <p><strong>Slug:</strong> {$post['slug']}</p>
            <h3>Meta Datos</h3>
            <p><strong>Latitud:</strong> " . ($post['meta']['latitud'][0] ?? 'N/A') . "</p>
            <p><strong>Longitud:</strong> " . ($post['meta']['longitud'][0] ?? 'N/A') . "</p>
        </div>
    ";
    }

    $list_html .= "
</body>
</html>
";

    $list_path = "output/{$base}/{$tipo}/index.html";
    ensure_directory_exists(dirname($list_path));
    file_put_contents($list_path, $list_html);
    echo "  ✅ Listado HTML generado en <a href='{$base_url}/{$list_path}'>{$base_url}/{$list_path}</a>\n";
}

function generate_general_report($general_report) {
    $base_url = get_base_url();
    $export_header = debug_export_header('General', 'Reporte');

    $report_html = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Reporte General de Posts</title>
</head>
<body>
    {$export_header}
    <h1>Reporte General de Posts</h1>
";

    $current_instance = '';
    $current_type = '';

    foreach ($general_report as $post) {
        if ($post['instance'] !== $current_instance) {
            $current_instance = $post['instance'];
            $report_html .= "<h2>Instancia: {$current_instance}</h2>";
        }

        if ($post['type'] !== $current_type) {
            $current_type = $post['type'];
            $report_html .= "<h3>Tipo: {$current_type}</h3>";
        }

        $report_html .= "
        <div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>
            <h4>{$post['title']}</h4>
            <p><strong>ID:</strong> {$post['id']}</p>
            <p><strong>Fecha:</strong> {$post['date']}</p>
            <p><strong>Slug:</strong> {$post['slug']}</p>
            <h5>Meta Datos</h5>
            <p><strong>Latitud:</strong> " . ($post['meta']['latitud'][0] ?? 'N/A') . "</p>
            <p><strong>Longitud:</strong> " . ($post['meta']['longitud'][0] ?? 'N/A') . "</p>
        </div>
        ";
    }

    $report_html .= "
</body>
</html>
";

    $report_path = "output/general_report.html";
    ensure_directory_exists(dirname($report_path));
    file_put_contents($report_path, $report_html);
    echo "  ✅ Reporte general generado en <a href='{$base_url}/{$report_path}'>{$base_url}/{$report_path}</a>\n";
}

function save_post_json($base, $tipo, $post, $taxonomies) {
    $json_path = "output/{$base}/{$tipo}/{$post['id']}.json";
    ensure_directory_exists(dirname($json_path));

    // Add taxonomies to the post data
    $post['taxonomies'] = $taxonomies;

    // Save the post data as JSON
    file_put_contents($json_path, json_encode($post, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "  ✅ JSON generado para el post ID {$post['id']} en {$json_path}\n";
}

function generate_general_json($general_report) {
    $general_data = [];
    $host_data = [];

    foreach ($general_report as $post) {
        $entry = [
            'host' => $post['instance'],
            'type' => $post['type'],
            'id' => $post['id'],
            'title' => $post['title'],
            'image' => $post['meta']['gallery_urls'][0] ?? $post['media_url'] ?? null,
        ];

        // Add to general data
        $general_data[] = $entry;

        // Add to host-specific data
        if (!isset($host_data[$post['instance']])) {
            $host_data[$post['instance']] = [];
        }
        $host_data[$post['instance']][] = $entry;

        // Save individual post JSON
        save_post_json($post['instance'], $post['type'], $post, $post['taxonomies'] ?? []);
    }

    // Save general.json
    $general_path = "output/general.json";
    ensure_directory_exists(dirname($general_path));
    file_put_contents($general_path, json_encode($general_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "  ✅ Archivo general.json generado en {$general_path}\n";

    // Save per-host JSON files
    foreach ($host_data as $host => $data) {
        $host_path = "output/{$host}/index.json";
        ensure_directory_exists(dirname($host_path));
        file_put_contents($host_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "  ✅ Archivo index.json generado para host {$host} en {$host_path}\n";
    }
}

function ensure_directory_exists($path) {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

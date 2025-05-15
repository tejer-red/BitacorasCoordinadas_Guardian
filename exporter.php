<?php

function generate_post_html($base, $tipo, $post) {
    $html_content = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$post['title']['rendered']}</title>
</head>
<body>
    <h1>{$post['title']['rendered']}</h1>
    <p><strong>Fecha:</strong> {$post['date']}</p>
    <p><strong>Slug:</strong> {$post['slug']}</p>
    <h2>Contenido</h2>
    <div>{$post['content']['rendered']}</div>
    <h2>Meta Datos</h2>
    <ul>
        <li><strong>Latitud:</strong> " . ($post['meta']['latitud'][0] ?? 'N/A') . "</li>
        <li><strong>Longitud:</strong> " . ($post['meta']['longitud'][0] ?? 'N/A') . "</li>
    </ul>
    <h2>Imágenes</h2>
    <p><strong>Imagen destacada:</strong></p>
    " . (isset($post['media_url']) ? "<img src='{$post['media_url']}' alt='Imagen destacada' style='max-width: 100%;'>" : "<p>No disponible</p>") . "
    <h3>Galería</h3>
    " . (isset($post['meta']['gallery_urls']) ? implode('', array_map(fn($url) => "<img src='{$url}' alt='Galería' style='max-width: 100%; margin: 5px;'>", $post['meta']['gallery_urls'])) : "<p>No disponible</p>") . "
</body>
</html>
";

    $html_path = "output/{$base}/{$tipo}/{$post['id']}.html";
    file_put_contents($html_path, $html_content);
    echo "  ✅ HTML generado en {$html_path}\n";
}

function generate_post_list_html($base, $tipo, $all_posts) {
    $list_html = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Listado de Posts</title>
</head>
<body>
    <h1>Listado de Posts</h1>
";

    foreach ($all_posts as $post) {
        $list_html .= "
        <div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>
            <h2><a href='output/{$base}/{$tipo}/{$post['id']}.html'>{$post['title']}</a></h2>
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
    file_put_contents($list_path, $list_html);
    echo "  ✅ Listado HTML generado en {$list_path}\n";
}

function generate_general_report($general_report) {
    $report_html = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Reporte General de Posts</title>
</head>
<body>
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
    file_put_contents($report_path, $report_html);
    echo "  ✅ Reporte general generado en {$report_path}\n";
}

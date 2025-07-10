<?php

if (!function_exists('debug_start_html')) {
    function debug_start_html($title) {
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title}</title>
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
<h1>{$title}</h1>
<div class='log'>";
    }

    function debug_end_html() {
        echo "</div>
</body>
</html>";
    }

    function debug_info($message) {
        echo "<p class='info'>{$message}</p>";
    }

    function debug_success($message) {
        echo "<p class='success'>{$message}</p>";
    }

    function debug_warning($message) {
        echo "<p class='warning'>{$message}</p>";
    }

    function debug_gallery($images) {
        echo "<div class='gallery'>";
        foreach ($images as $image) {
            echo "<img src='{$image}' alt='Galería'>";
        }
        echo "</div>";
    }

    function debug_post_details($post, $taxonomies, $featured_image, $gallery_images) {
        echo "<div class='detalles'>";
        echo "<h3>Detalles del Post</h3>";

        // Display basic post details
        echo "<p><strong>ID:</strong> {$post['id']}</p>";
        $title = is_array($post['title']) && isset($post['title']['rendered']) ? $post['title']['rendered'] : $post['title'];
        echo "<p><strong>Título:</strong> {$title}</p>";

        // Corrige acceso a content
        $content = '';
        if (isset($post['content'])) {
            if (is_array($post['content']) && isset($post['content']['rendered'])) {
                $content = $post['content']['rendered'];
            } elseif (is_string($post['content'])) {
                $content = $post['content'];
            }
        }
        echo "<p><strong>Contenido:</strong> {$content}</p>";

        echo "<p><strong>Fecha:</strong> {$post['date']}</p>";
        echo "<p><strong>Slug:</strong> {$post['slug']}</p>";
        echo "<p><strong>Tipo:</strong> {$post['type']}</p>";

        // Display metadata
        if (!empty($post['meta'])) {
            echo "<h4>Meta Datos</h4><ul>";
            foreach ($post['meta'] as $key => $value) {
                $value = is_array($value) ? implode(', ', $value) : $value;
                echo "<li><strong>{$key}:</strong> {$value}</li>";
            }
            echo "</ul>";
        }

        // Display taxonomies
        if (!empty($post['taxonomies'])) {
            echo "<h4>Taxonomías</h4><ul>";
            foreach ($post['taxonomies'] as $taxonomy) {
                echo "<li><strong>{$taxonomy['taxonomy']}:</strong> {$taxonomy['name']} (Slug: {$taxonomy['slug']}, ID: {$taxonomy['id']})</li>";
            }
            echo "</ul>";
        }

        // Display featured image
        if ($featured_image) {
            echo "<p><strong>Imagen destacada:</strong></p>";
            echo "<img src='{$featured_image}' alt='Imagen destacada'>";
        }

        // Display gallery images
        if (!empty($gallery_images)) {
            echo "<p><strong>Galería:</strong></p>";
            debug_gallery($gallery_images);
        }

        echo "</div>";
    }

    function debug_export_header($instance, $type) {
        return "
        <div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; background: #f9f9f9;'>
            <h2>Exportación</h2>
            <p><strong>Instancia:</strong> {$instance}</p>
            <p><strong>Tipo:</strong> {$type}</p>
            <p><strong>Generado por:</strong> " . __FUNCTION__ . "()</p>
        </div>
        ";
    }
}
?>

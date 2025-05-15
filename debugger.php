<?php

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
    echo "<p><strong>Título:</strong> {$post['title']['rendered']}</p>";
    echo "<p><strong>Contenido:</strong> {$post['content']['rendered']}</p>";
    echo "<p><strong>Fecha:</strong> {$post['date']}</p>";
    echo "<p><strong>Slug:</strong> {$post['slug']}</p>";
    echo "<p><strong>Tipo:</strong> {$post['type']}</p>";

    // Display taxonomies
    echo "<p><strong>Taxonomías:</strong></p><ul>";
    foreach ($taxonomies as $tax_name => $tax_url) {
        echo "<li><strong>{$tax_name}:</strong> <a href='{$tax_url}'>{$tax_url}</a></li>";
    }
    echo "</ul>";

    // Display metadata
    if (!empty($post['meta'])) {
        echo "<h4>Meta Datos</h4><ul>";
        foreach ($post['meta'] as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            echo "<li><strong>{$key}:</strong> {$value}</li>";
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

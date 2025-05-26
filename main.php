<?php

// Set the content type to HTML
header('Content-Type: text/html');

require_once 'fetcher.php';
require_once 'exporter.php';
require_once 'debugger.php';

$instancias_url = './instancias.json';
$instancias = json_decode(file_get_contents($instancias_url), true);

$general_report = []; // Store all posts for the general report

debug_start_html("Proceso de Exportación");

function process_instance($instancia, &$general_report) {
    if (!$instancia['activa']) {
        debug_warning("Instancia inactiva: <strong>{$instancia['url']}</strong>");
        return;
    }

    $base = parse_url($instancia['url'], PHP_URL_HOST);
    debug_info("🌍 Procesando instancia: <strong>{$base}</strong>");

    foreach ($instancia['endpoints'] as $tipo => $endpoint) {
        process_endpoint($base, $tipo, $endpoint, $instancia['url'], $general_report);
    }
}

function process_endpoint($base, $tipo, $endpoint, $base_url, &$general_report) {
    debug_info("📥 Tipo: <strong>{$tipo}</strong>");
    $posts = fetch_posts($endpoint);

    if (empty($posts)) {
        debug_warning("No se encontraron posts para el endpoint: <strong>{$endpoint}</strong>");
        return;
    }

    $all_posts = [];
    foreach ($posts as $post) {
        process_post($base, $tipo, $post, $base_url, $all_posts, $general_report);
    }

    // Generate a complete HTML listing of all posts
    generate_post_list_html($base, $tipo, $all_posts);

    // Display the link to the main listing page
    $listing_url = "/output/{$base}/{$tipo}/index.html";
    debug_success("✅ Accede al listado de posts aquí: <a href='{$listing_url}'>{$listing_url}</a>");
}

function process_post($base, $tipo, $post, $base_url, &$all_posts, &$general_report) {
    $id = $post['id'];
    debug_info("🔍 Procesando post ID: <strong>{$id}</strong>");

    $meta_url = "{$base_url}";
    $meta = fetch_meta($meta_url);

    $post['meta'] = $meta;

    // Detect taxonomies and fetch their details
    $taxonomies = [];
    $taxonomy_links = extract_taxonomy_links($post);
    foreach ($taxonomy_links as $tax_name => $tax_url) {
        debug_info("🔍 Taxonomía encontrada: <strong>{$tax_name}</strong> -> <a href='{$tax_url}'>{$tax_url}</a>");
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

    // Retrieve media URL
    $media_url = null;
    if (isset($post['featured_media']) && $post['featured_media']) {
        $media_url = fetch_media_url($base_url, $post['featured_media']);
        $post['media_url'] = $media_url;
        debug_success("✅ Imagen destacada: <img class='featured' src='{$media_url}' alt='Imagen destacada'>");
    }

    // Merge custom meta
    $post_type = $tipo;
    $post_id = $post['id'];
    $custom_meta = fetch_custom_meta($base_url, $post_type, $post_id);

    if (isset($post['meta']['gallery_urls'])) {
        $custom_meta['gallery_urls'] = $post['meta']['gallery_urls'];
    }

    $post['meta'] = array_merge($post['meta'], $custom_meta);

    // Process gallery
    if (isset($post['meta']['galeria'])) {
        debug_info("🔍 Verificando galería de medios para el post ID: <strong>{$id}</strong>");
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
            }
            debug_gallery($gallery_urls);
            $post['meta']['gallery_urls'] = $gallery_urls;
        } else {
            debug_warning("⚠️ El campo 'galeria' no es un array para el post ID: <strong>{$id}</strong>");
        }
    } else {
        debug_info("ℹ️ El campo 'galeria' no está presente en los meta datos para el post ID: <strong>{$id}</strong> (esto es opcional).");
    }

    // Display all fields of the post
    debug_post_details($post, $taxonomy_links, $post['media_url'] ?? null, $post['meta']['gallery_urls'] ?? []);

    // Add the post to the list for the complete HTML
    $all_posts[] = [
        'id' => $id,
        'title' => $post['title']['rendered'],
        'meta' => $post['meta'],
        'slug' => $post['slug'],
        'date' => $post['date'],
    ];

    // Add the post to the general report
    $general_report[] = [
        'instance' => $base,
        'type' => $tipo,
        'id' => $id,
        'title' => $post['title']['rendered'],
        'meta' => $post['meta'],
        'slug' => $post['slug'],
        'date' => $post['date'],
        'media_url' => $media_url,
        'taxonomies' => $taxonomies, // Include taxonomy details here
    ];

    // Save JSON representation of the post
    save_post_json($base, $tipo, $post, $taxonomies);

    // Generate HTML representation of the post
    generate_post_html($base, $tipo, $post);
}

// Process each instance
foreach ($instancias as $instancia) {
    process_instance($instancia, $general_report);
}

// Generate the general report
generate_general_report($general_report);

// Generate the general JSON and per-host JSON files
generate_general_json($general_report);

debug_success("✅ Reporte general generado en <a href='/output/general_report.html'>/output/general_report.html</a>");
debug_success("✅ Archivos JSON generados correctamente.");
debug_end_html();

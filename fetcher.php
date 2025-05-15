<?php

function fetch_posts($url) {
    $json = file_get_contents($url);
    return json_decode($json, true) ?: [];
}

function fetch_meta($url) {
    $json = @file_get_contents($url);
    return json_decode($json, true) ?: [];
}

function fetch_terms($url) {
    $json = @file_get_contents($url);
    return json_decode($json, true) ?: [];
}

function extract_taxonomy_links($post) {
    $result = [];
    if (!isset($post['_links']['wp:term'])) return $result;

    foreach ($post['_links']['wp:term'] as $tax) {
        $name = $tax['taxonomy'];
        $url = $tax['href'];
        $result[$name] = $url;
    }

    return $result;
}

function fetch_media_url($base_url, $media_id) {
    $media_url = "{$base_url}/wp-json/wp/v2/media/{$media_id}";
    echo "  🔍 Buscando media URL: {$media_url}\n";

    $json = @file_get_contents($media_url);

    if ($json === false) {
        echo "  ⚠️ Error al obtener datos para ID: {$media_id}\n";
        return null;
    }

    $media_data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "  ⚠️ Error al decodificar JSON para ID: {$media_id}: " . json_last_error_msg() . "\n";
        return null;
    }

    if (!isset($media_data['source_url'])) {
        echo "  ⚠️ No se encontró 'source_url' en la respuesta para ID: {$media_id}\n";
        return null;
    }

    echo "  ✅ ID: {$media_id} -> URL: {$media_data['source_url']}\n";
    return $media_data['source_url'];
}

function save_json($path, $data) {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function merge_terms_by_id(array $existing, array $new): array {
    $by_id = [];
    foreach ($existing as $term) {
        $by_id[$term['id']] = $term;
    }
    foreach ($new as $term) {
        $by_id[$term['id']] = $term; // sobrescribe si ya existe
    }
    // Orden opcional por nombre
    usort($by_id, fn($a, $b) => strcmp($a['name'], $b['name']));
    return array_values($by_id);
}

function fetch_custom_meta($base_url, $post_type, $post_id) {
    $post_type =  substr($post_type, 0, -1);
    $meta_url = "{$base_url}/wp-json/personalizado/v1/info/{$post_type}/{$post_id}";
    echo "  🔍 Buscando meta: {$meta_url}\n";
    $json = file_get_contents($meta_url);
    echo "  🔍 Meta: {$json}\n";
    return $json ? json_decode($json, true) : [];
}
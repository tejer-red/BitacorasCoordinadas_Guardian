<?php

require_once 'fetcher.php';

$instancias_url = './instancias.json';
$instancias = json_decode(file_get_contents($instancias_url), true);

foreach ($instancias as $instancia) {
    if (!$instancia['activa']) continue;

    $base = parse_url($instancia['url'], PHP_URL_HOST);
    echo "\n🌍 Procesando instancia: {$base}\n";

    foreach ($instancia['endpoints'] as $tipo => $endpoint) {
        echo "📥 Tipo: $tipo\n";
        $posts = fetch_posts($endpoint);

        foreach ($posts as $post) {
            $id = $post['id'];
            $meta_url = "{$instancia['url']}";
            $meta = fetch_meta($meta_url);

            $post['meta'] = $meta;

            // ⬇️ Detectar taxonomías
            $taxonomias = extract_taxonomy_links($post);
            foreach ($taxonomias as $tax_name => $tax_url) {
                $tax_path = "output/{$base}/taxonomias/{$tax_name}.json";
                $existing_terms = file_exists($tax_path) ? json_decode(file_get_contents($tax_path), true) : [];
                $terms = fetch_terms($tax_url);
                // Fusionar y evitar duplicados por ID
                $merged_terms = merge_terms_by_id($existing_terms, $terms);
                save_json($tax_path, $merged_terms);
            }

            $path = "output/{$base}/{$tipo}/{$id}.json";
            $post_type = $tipo; // ya definido en el loop
            $post_id = $post['id'];
            $custom_meta = fetch_custom_meta($meta_url, $post_type, $post_id);
            $post['meta'] = $custom_meta;
            $post_path = "output/{$base}/{$tipo}/{$id}.json";
            print_r($custom_meta);
            save_json($post_path, $post);
            echo "  ✅ Post {$id} guardado\n";
        }
    }
}

echo "\n🏁 ¡Todas las instancias fueron procesadas!\n";

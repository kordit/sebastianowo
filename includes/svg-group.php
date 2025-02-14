<?php
add_action('init', function () {
    if (!is_admin() || !function_exists('acf_add_local_field_group')) {
        return;
    }

    // Pobierz wszystkie zarejestrowane post types (bez systemowych)
    $custom_post_types = get_post_types(['_builtin' => false]);

    foreach ($custom_post_types as $cpt) {
        $posts = get_posts([
            'post_type'      => $cpt,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ]);

        foreach ($posts as $post) {
            $post_id = $post->ID;
            $post_title = sanitize_title($post->post_title); // Użycie tytułu jako klucza

            $svg_url = get_field('svg', $post_id);
            if (!$svg_url) {
                continue; // Pomijamy, jeśli brak SVG
            }

            if (is_numeric($svg_url)) {
                $svg_url = wp_get_attachment_url($svg_url);
            } else {
                $svg_url = $svg_url;
            }


            // Oblicz liczbę ścieżek w SVG
            $path_count = count_svg_paths($svg_url);
            if ($path_count === 0) {
                continue;
            }

            for ($i = 0; $i < $path_count; $i++) {
                acf_add_local_field_group([
                    'key'    => "group_{$post_title}_svg_paths_{$i}",
                    'name'    => "group_{$post_title}_svg_paths_{$i}",
                    'title'  => "Path {$i}",
                    'fields' => [
                        [
                            'key'   => "field_{$post_title}_svg_path_{$i}_nazwa",
                            'label' => 'Nazwa',
                            'name'  => "{$post_title}_svg_path_{$i}_nazwa",
                            'type'  => 'text',
                            'default_value' => "Wartość {$i}",
                        ],
                        [
                            'key'   => "field_{$post_title}_svg_path_{$i}_liczba",
                            'label' => 'Liczba',
                            'name'  => "{$post_title}_svg_path_{$i}_liczba",
                            'type'  => 'number',
                        ],
                        [
                            'key'   => "field_{$post_title}_svg_path_{$i}_cyfrowe",
                            'label' => 'Cyfrowe',
                            'name'  => "{$post_title}_svg_path_{$i}_cyfrowe",
                            'type'  => 'number',
                        ],
                    ],
                    'location' => [
                        [
                            [
                                'param'    => 'post',
                                'operator' => '==',
                                'value'    => $post_id,
                            ],
                        ],
                    ],
                    'position' => 'side', // Dodanie do sidebara
                    'style'    => 'default',
                    'menu_order' => 99
                ]);
            }
        }
    }
});

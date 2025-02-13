<?php
$post_id = get_the_ID();
$instance = get_query_var('instance_name');
$post_type = get_post_type();
$post_name = get_post()->post_name;
$scene_id = get_query_var('scene_id', $post_id);
$get_scenes = get_field('scenes');

$i = 1;

$post_title = sanitize_title(get_the_title($post_id));

if ($scene_id == $post_id) {
    $get_scenes = $get_scenes[0];
    $background = $get_scenes['tlo'];
    $svg_url = $get_scenes['maska'];
    $path_count = count_svg_paths($svg_url);

    if ($svg_url) {
        $selected_paths = [];
        for ($i = 0; $i < $path_count; $i++) {
            $select = get_field("field_{$post_title}_scene_0_svg_path_{$i}_select", $post_id);
            $path_id = get_field("field_{$post_title}_scene_0_svg_path_{$i}_id", $post_id);
            $npc     = get_field("field_{$post_title}_scene_0_svg_path_{$i}_npc", $post_id);
            $name    = get_field("field_{$post_title}_scene_0_svg_path_{$i}_name", $post_id);
            if (!empty($select) || !empty($path_id) || !empty($npc)) {
                $selected_paths[] = [
                    'select' => $select,
                    'target' => get_site_url() . '/' . $post_type . '/' . $post_name . '/' . $path_id,
                    'npc'    => $npc ?: NULL,
                    'title'  => $name ?: 'brak tytułu',
                ];
            }
        }
    }
} else {
    // Szukamy sceny, której pole id_sceny odpowiada wartości $scene_id (ciąg znaków)
    $found_scene = null;
    $found_index = null;
    foreach ($get_scenes as $index => $scene) {
        if ($scene['id_sceny'] === $scene_id) {
            $found_scene = $scene;
            $found_index = $index;
            break;
        }
    }
    if ($found_scene) {
        $background = $found_scene['tlo'];
        $svg_url = $found_scene['maska'];
        $path_count = count_svg_paths($svg_url);
        if ($svg_url) {
            $selected_paths = [];
            for ($i = 0; $i < $path_count; $i++) {
                $select = get_field("field_{$post_title}_scene_{$found_index}_svg_path_{$i}_select", $post_id);
                $path_id = get_field("field_{$post_title}_scene_{$found_index}_svg_path_{$i}_id", $post_id);
                $npc     = get_field("field_{$post_title}_scene_{$found_index}_svg_path_{$i}_npc", $post_id);
                $name    = get_field("field_{$post_title}_scene_{$found_index}_svg_path_{$i}_name", $post_id);
                if (!empty($select) || !empty($path_id) || !empty($npc)) {
                    $selected_paths[] = [
                        'select' => $select,
                        'target' => get_site_url() . '/' . $post_type . '/' . $post_name . '/' . $path_id,
                        'npc'    => $npc ?: NULL,
                        'title'  => $name ?: 'brak tytułu',
                    ];
                }
            }
        }
    } else {
        // Możesz obsłużyć przypadek, gdy scena o danym id_sceny nie zostanie znaleziona.
    }
}



get_header();
?>


<?php if ($instance != 'kreator'): ?>
    <aside>
        <?php get_sidebar();
        ?>
    </aside>
<?php endif; ?>
<main class="game-content">
    <div class="container-world">
        <?= wp_get_attachment_image($background, 'full'); ?>
        <?php
        echo et_svg_with_data($svg_url, $selected_paths);
        ?>
    </div>
    <div id="what-place" class="btn">Co to za miejsce?</div>
    <div class="game-content--inner">
        <button id="loadNpc" data-npc-id="115">Wczytaj NPC</button>

        <?php
        if ($instance) {
            include(THEME_SRC . '/templates/' . $instance . '/template.php');
        } elseif (is_author()) {
            include(THEME_SRC . '/templates/author/config.php');
        } elseif (is_archive()) {
            $single_src = get_post()->post_type;
            include(THEME_SRC . '/templates/' . $single_src . '/main/template.php');
        } elseif (is_single()) {
            $single_src = get_post()->post_type;
            // include(THEME_SRC . '/templates/' . $single_src . '/single/template.php');
            // include(THEME_SRC . '/templates/global/main-single-popup.php');
        } else {
        }
        ?>
    </div>
</main>
<?php

?>

<?php get_footer(); ?>
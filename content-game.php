<?php
$instance = get_query_var('instance_name');


get_header();
if ($instance != 'kreator'): ?>
    <?php get_sidebar();
    ?>
<?php endif; ?>
<main class="game-content">
    <div class="container-world">
        <?php scene_generator(); ?>
    </div>
    <div class="game-content--inner">
        <?php
        if (is_single()) {

            $post = get_post();
            echo '<a class="walk btn btn-green" href="' . esc_url(get_permalink($post)) . '?spacer=true">Idź na spacer</a>';
        }

        $request_uri = trim($_SERVER['REQUEST_URI'], '/'); // Usuwamy początkowe i końcowe "/"
        $slash_count = substr_count($request_uri, '/');
        $post_id = get_the_ID();

        if (isset($post_id)) {
            $scene = get_query_var('scene_id', $post_id);
        } else {
            $scene = '';
        }


        if ($instance) {
            include(THEME_SRC . '/page-templates/' . $instance . '/template.php');
        } elseif (is_author()) {
            include(THEME_SRC . '/page-templates/author/config.php');
        } elseif (is_archive()) {
            $single_src = get_post()->post_type;
            include(THEME_SRC . '/page-templates/' . $single_src . '/main/template.php');
        } elseif (is_single()) {
            $single_src = get_post()->post_type;
            include(THEME_SRC . '/page-templates/' . $single_src . '/single/template.php');
        } else {
        }
        ?>
    </div>
</main>
<?php

?>

<?php get_footer(); ?>
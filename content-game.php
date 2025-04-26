<?php
$instance = get_query_var('instance_name');


get_header();
if ($instance != 'kreator'): ?>
    <?php get_sidebar(); ?>
<?php endif; ?>
<main class="game-content">
    <div class="container-world">
        <?php scene_generator(); ?>
    </div>
    <div class="game-content--inner">

        <?php
        // et_r(get_fields(76));

        $user_missions = get_fields('user_' . get_current_user_id());
        et_r($user_missions);

        $request_uri = trim($_SERVER['REQUEST_URI'], '/'); // Usuwamy początkowe i końcowe "/"
        $slash_count = substr_count($request_uri, '/');
        $post_id = get_the_ID();

        if (isset($post_id)) {
            $scene = get_query_var('scene_id', $post_id);
        } else {
            $scene = '';
        }
        if ($slash_count === 1 || $scene == 'spacer') {
        ?>

        <?php
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
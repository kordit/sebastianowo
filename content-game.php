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

        $current_user_id = get_current_user_id();
        $meta = get_user_meta($current_user_id);
        if (is_single() && get_the_ID() !== 24) {
            $post = get_post();
            echo '<a class="walk btn btn-green" href="' . esc_url(get_permalink($post)) . '?spacer=true">Poszwędaj się</a>';
        }


        $request_uri = trim($_SERVER['REQUEST_URI'], '/'); // Usuwamy początkowe i końcowe "/"
        $slash_count = substr_count($request_uri, '/');
        $post_id = get_the_ID();

        if (isset($post_id)) {
            $scene = get_query_var('scene_id', $post_id);
        } else {
            $scene = '';
        }
        $npc_id = 449;
        $user_id = get_current_user_id();


        $user_relation = get_field('backpack', 'user_' . $user_id) ?? 0;
        et_r($user_relation);
        $user_has_met = get_fields('user_' . $user_id);
        et_r($user_has_met);

        // et_r(get_fields(449)['dialogs']);


        // if ($instance) {
        //     include(THEME_SRC . '/page-templates/' . $instance . '/template.php');
        // } elseif (is_author()) {
        //     include(THEME_SRC . '/page-templates/author/config.php');
        // } elseif (is_archive()) {
        //     $single_src = get_post()->post_type;
        //     include(THEME_SRC . '/page-templates/' . $single_src . '/main/template.php');
        // } elseif (is_single()) {
        //     $single_src = get_post()->post_type;
        //     include(THEME_SRC . '/page-templates/' . $single_src . '/single/template.php');
        // } else {
        // }
        ?>
    </div>

</main>
<?php

?>

<?php get_footer(); ?>
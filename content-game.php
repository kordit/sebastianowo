<?php
$instance = get_query_var('instance_name');
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
        <?php scene_generator(); ?>
    </div>
    <?php if ($instance != 'kreator'): ?>
        <div id="what-place" class="btn">Co to za miejsce?</div>
    <?php endif; ?>
    <div class="game-content--inner">
        <!-- <button id="loadNpc" data-npc-id="115">Wczytaj NPC</button> -->
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
            include(THEME_SRC . '/templates/' . $single_src . '/single/template.php');
        } else {
        }
        ?>
    </div>
</main>
<?php

?>

<?php get_footer(); ?>
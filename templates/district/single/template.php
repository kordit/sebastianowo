<?php
$acf_fields = get_fields();
$post_id = get_the_ID();
$current_user_id = get_current_user_id();
$villagers = get_field('the_villagers', $post_id) ?: [];
$villagers = get_field('the_villagers', $post_id);
$is_villager = is_array($villagers) && in_array($current_user_id, array_column($villagers, 'ID'));
$leader = get_field('leader', $post_id);
if ($leader) {
    if ($leader['ID'] == $current_user_id) {
        $is_leader = true;
    } else {
        $is_leader = false;
    }
}
?>

<h1><?php the_title(); ?></h1>

<?php
if ($is_villager) {
    include 'resident-layout.php';
} else {
    include 'nonresident-layout.php';
}
?>
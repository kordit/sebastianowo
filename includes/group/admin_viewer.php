<?php
// function add_monster_columns($columns)
// {
//     $columns['life'] = __('Życie');
//     $columns['defence'] = __('Obrona');
//     $columns['attack'] = __('Atak');
//     $columns['speed'] = __('Szybkość');
//     $columns['instance_from_exp'] = __('Od ilu expa');
//     $columns['instance_to_exp'] = __('Do ilu expa');
//     $columns['gold_from_exp'] = __('Od ilu golda');
//     $columns['gold_to_exp'] = __('Do ilu golda');
//     $columns['dexterity'] = __('Zręczność');
//     $columns['monster_category'] = __('Lokacja w jakiej występuje');
//     return $columns;
// }
// add_filter('manage_edit-monster_columns', 'add_monster_columns');

// function custom_monster_column($column, $post_id)
// {
//     switch ($column) {
//         case 'life':
//             echo get_post_meta($post_id, 'life', true);
//             break;
//         case 'defence':
//             echo get_post_meta($post_id, 'defence', true);
//             break;
//         case 'attack':
//             echo get_post_meta($post_id, 'attack', true);
//             break;
//         case 'speed':
//             echo get_post_meta($post_id, 'speed', true);
//             break;
//         case 'instance_from_exp':
//             echo get_post_meta($post_id, 'instance_from_exp', true);
//             break;
//         case 'instance_to_exp':
//             echo get_post_meta($post_id, 'instance_to_exp', true);
//             break;
//         case 'gold_from_exp':
//             echo get_post_meta($post_id, 'gold_from_exp', true);
//             break;
//         case 'gold_to_exp':
//             echo get_post_meta($post_id, 'gold_to_exp', true);
//             break;
//         case 'dexterity':
//             echo get_post_meta($post_id, 'dexterity', true);
//             break;
//         case 'monster_category': // Obsługa kolumny taksonomii
//             $terms = get_the_terms($post_id, 'monster-category');
//             if (!empty($terms) && !is_wp_error($terms)) {
//                 $term_names = wp_list_pluck($terms, 'name');
//                 echo implode(', ', $term_names);
//             } else {
//                 echo __('Brak lokacji');
//             }
//             break;
//     }
// }
// add_action('manage_monster_posts_custom_column', 'custom_monster_column', 10, 2);

// function set_monster_sortable_columns($columns)
// {
//     $columns['life'] = 'life';
//     $columns['defence'] = 'defence';
//     $columns['attack'] = 'attack';
//     $columns['speed'] = 'speed';
//     $columns['instance_from_exp'] = __('Od ilu expa');
//     $columns['instance_to_exp'] = __('Do ilu expa');
//     $columns['gold_from_exp'] = __('Od ilu golda');
//     $columns['gold_to_exp'] = __('Do ilu golda');
//     $columns['Lokacja'] = __('Lokacja');
//     $columns['monster_category'] = 'monster-category';
//     $columns['dexterity'] = 'dexterity';
//     return $columns;
// }
// add_filter('manage_edit-monster_sortable_columns', 'set_monster_sortable_columns');

// function custom_monster_orderby($query)
// {
//     if (!is_admin()) {
//         return;
//     }

//     $orderby = $query->get('orderby');

//     if ('life' == $orderby) {
//         $query->set('meta_key', 'life');
//         $query->set('orderby', 'meta_value_num');
//     }
//     if ('defence' == $orderby) {
//         $query->set('meta_key', 'defence');
//         $query->set('orderby', 'meta_value_num');
//     }
//     if ('attack' == $orderby) {
//         $query->set('meta_key', 'attack');
//         $query->set('orderby', 'meta_value_num');
//     }
//     if ('speed' == $orderby) {
//         $query->set('meta_key', 'speed');
//         $query->set('orderby', 'meta_value_num');
//     }
//     if ('instance_from_exp' == $orderby) {
//         $query->set('meta_key', 'instance_from_exp');
//         $query->set('orderby', 'meta_value_num');
//     }
//     if ('instance_to_exp' == $orderby) {
//         $query->set('meta_key', 'instance_to_exp');
//         $query->set('orderby', 'meta_value_num');
//     }
//     if ('gold_from_exp' == $orderby) {
//         $query->set('meta_key', 'gold_from_exp');
//         $query->set('orderby', 'meta_value_num');
//     }
//     if ('gold_to_exp' == $orderby) {
//         $query->set('meta_key', 'gold_to_exp');
//         $query->set('orderby', 'meta_value_num');
//     }
//     if ('monster-category' == $orderby) { // Sortowanie po taksonomii
//         $query->set('orderby', 'taxonomy');
//         $query->set('taxonomy', 'monster-category');
//     }
//     if ('dexterity' == $orderby) { // Sortowanie po taksonomii
//         $query->set('orderby', 'taxonomy');
//         $query->set('taxonomy', 'dexterity');
//     }
// }
// add_action('pre_get_posts', 'custom_monster_orderby');

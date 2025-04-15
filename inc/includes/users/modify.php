<?php
function modify_user_roles()
{
    global $wp_roles;
    foreach ($wp_roles->roles as $role_name => $role_info) {
        if ($role_name !== 'administrator') {
            remove_role($role_name);
        }
    }

    add_role(
        'gracz',
        __('Gracz'),
        array(
            'read' => true,

        )
    );
}
add_action('init', 'modify_user_roles');


add_filter('manage_users_columns', function ($columns) {
    $new = array();
    $new['cb']         = $columns['cb'];
    $new['username']   = $columns['username'];
    $new['name']       = $columns['name'];
    $new['email']      = $columns['email'];
    $new['nick']       = 'Nick';
    $new['user_class'] = 'Klasa postaci';
    $new['bag']        = 'Plecak';
    $new['stats']      = 'Statystyki';
    $new['my_group']   = 'Moja grupa';
    $new['role']       = $columns['role'];
    $new['posts']      = $columns['posts'];
    return $new;
});

add_action('manage_users_custom_column', function ($value, $column_name, $user_id) {
    if ($column_name === 'nick') {
        return get_field('nick', 'user_' . $user_id);
    }
    if ($column_name === 'user_class') {
        $field = get_field('user_class', 'user_' . $user_id);
        return is_array($field) && isset($field['label']) ? $field['label'] : $field;
    }
    if ($column_name === 'bag') {
        $bag = get_field('bag', 'user_' . $user_id);
        if ($bag && is_array($bag)) {
            $output = '';
            foreach ($bag as $key => $val) {
                $output .= $key . ': ' . $val . '<br>';
            }
            return $output;
        }
    }
    if ($column_name === 'stats') {
        $stats = get_field('stats', 'user_' . $user_id);
        if ($stats && is_array($stats)) {
            $output = '';
            foreach ($stats as $key => $val) {
                $output .= $key . ': ' . $val . '<br>';
            }
            return $output;
        }
    }
    if ($column_name === 'my_group') {
        $group = get_field('my_group', 'user_' . $user_id);
        if ($group && is_object($group)) return get_the_title($group->ID);
    }
    return $value;
}, 10, 3);

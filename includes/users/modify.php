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

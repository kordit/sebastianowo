<?php
add_action('user_register', function ($user_id) {
    update_field('avatar', 86, 'user_' . $user_id);
    update_field('village', 73, 'user_' . $user_id);
});

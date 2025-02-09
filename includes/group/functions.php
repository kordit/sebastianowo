<?php
function add_custom_rewrite_rule()
{
    add_rewrite_rule(
        '^wioska/([^/]+)/([^/]+)/?$',
        'index.php?post_type=wioska&name=$matches[1]&tab=$matches[2]',
        'top'
    );
}
add_action('init', 'add_custom_rewrite_rule');

function add_tab_query_var($vars)
{
    $vars[] = 'tab';
    return $vars;
}
add_filter('query_vars', 'add_tab_query_var');

$current_tab = get_query_var('tab');

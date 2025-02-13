<?php
// Main WordPress index file
get_header();
if (!is_user_logged_in()) {
    require_once('login.php');
} else {
    require_once('content-game.php');
}
get_footer();

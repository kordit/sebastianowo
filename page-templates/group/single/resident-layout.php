<?php
$npc_id = get_field('npc')[0]->ID;
$current_user_id = get_current_user_id();
get_npc($npc_id);

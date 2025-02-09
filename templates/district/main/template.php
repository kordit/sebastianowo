<?php include('config.php'); ?>
<div class="town-wrapper">
    <?= wp_get_attachment_image(7, 'full'); ?>
    <?php
    echo et_svg_with_data(SVG . 'map-2.svg', $selected_tereny);
    ?>
</div>
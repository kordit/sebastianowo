<?php
$fields = get_fields(get_the_ID());
$man_image = get_field('group_man') ?: 87;
?>
<div class="teren-info">
    <a href="/tereny" class="btn back">wróć do rzutu miasta</a>
</div>


<?php
if (!empty($fields['siedziba_grupy'])) : ?>


<?php else:
?>



<?php endif; ?>
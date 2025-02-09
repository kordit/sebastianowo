<?php $fields = get_fields(get_the_ID()); ?>
<div class="teren-info">
    <a href="/tereny" class="btn back">wróć do rzutu miasta</a>
    <div class="polaroid">
        <?= wp_get_attachment_image(get_field('teren_zdjecie'), 'full'); ?>
    </div>
    <h1><?php the_title(); ?></h1>
    <p class="description">
        <?php the_field('teren_opis'); ?>
    </p>
</div>


<?php
if (!empty($fields['siedziba_grupy'])) : ?>


<?php else:
?>
    <form id="create-village-form">
        <label for="village-title" class="bold">
            Czy masz na tyle odwagi, żeby na terenie "<?php the_title(); ?>" założyć siedzibę swojej grupy?
        </label>
        <input type="text" id="village-title" name="village-title" required>
        <!-- Ukryty input z ID terenu (dla CPT "tereny") -->
        <input type="hidden" id="teren-id" value="<?php echo get_the_ID(); ?>">
        <button type="submit">Załóż grupę (koszt 200 złota)</button>
    </form>


<?php endif; ?>
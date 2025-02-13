<?php
add_action('acf/include_fields', function () {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_6793e69170911',
        'title' => 'User',
        'fields' => array(
            array(
                'key' => 'field_6794b4c86bae4',
                'label' => 'Avatar',
                'name' => 'avatar',
                'aria-label' => '',
                'type' => 'image',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'return_format' => 'array',
                'library' => 'all',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => '',
                'mime_types' => '',
                'preview_size' => 'medium',
            ),
            array(
                'key' => 'field_679a251fed9a5',
                'label' => 'Kreator ukończony?',
                'name' => 'creator_end',
                'aria-label' => '',
                'type' => 'true_false',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'message' => '',
                'default_value' => 0,
                'ui' => 0,
                'ui_on_text' => '',
                'ui_off_text' => '',
            ),
            array(
                'key' => 'field_679a2b8sfnick',
                'label' => 'Nick',
                'name' => 'nick',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
            ),
            array(
                'key' => 'field_679a2b8ab83a2',
                'label' => 'Podpis',
                'name' => 'story',
                'aria-label' => '',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 0,
                'delay' => 0,
            ),
            array(
                'key' => 'field_679a2d06adef9',
                'label' => 'Klasa postaci',
                'name' => 'user_class',
                'aria-label' => '',
                'type' => 'select',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'choices' => array(
                    'zadymiarz' => 'Zadymiarz',
                    'zlodziej' => 'Złodziej',
                    'ogarniacz' => 'Ogarniacz',
                ),
                'default_value' => false,
                'return_format' => 'array',
                'multiple' => 0,
                'allow_null' => 1,
                'ui' => 0,
                'ajax' => 0,
                'placeholder' => '',
            ),
            array(
                'key' => 'field_6793e69183c31',
                'label' => 'plecak',
                'name' => 'bag',
                'aria-label' => '',
                'type' => 'group',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_6793e6a783c33',
                        'label' => 'Złote',
                        'name' => 'gold',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e69b83c32',
                        'label' => 'papierosy',
                        'name' => 'papierosy',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6bc83c36',
                        'label' => 'piwo',
                        'name' => 'piwo',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6c283c37',
                        'label' => 'bimber',
                        'name' => 'bimber',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6c283c37a1',
                        'label' => 'marihuana',
                        'name' => 'marihuana',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6c283c37a2',
                        'label' => 'grzyby',
                        'name' => 'grzyby',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6c283c37a2',
                        'label' => 'klej',
                        'name' => 'klej',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6c283c37a2',
                        'label' => 'klej',
                        'name' => 'klej',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                ),
            ),
            array(
                'key' => 'field_6793e6cb83c38',
                'label' => 'Statystyki',
                'name' => 'stats',
                'aria-label' => '',
                'type' => 'group',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_6793e6cb83c39',
                        'label' => 'Życie',
                        'name' => 'life',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6cb83c3a',
                        'label' => 'Maksymalne życie',
                        'name' => 'max_life',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6cb83c3b',
                        'label' => 'Energia',
                        'name' => 'energy',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '0',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                    array(
                        'key' => 'field_6793e6cb83c3b_max',
                        'label' => 'Energia',
                        'name' => 'max_energy',
                        'aria-label' => '',
                        'type' => 'number',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '100',
                        'min' => 0,
                        'max' => '',
                        'placeholder' => '',
                        'step' => '',
                        'prepend' => '',
                        'append' => '',
                    ),
                ),
            ),
            array(
                'key' => 'field_6794b04d1bd06',
                'label' => 'Moja grupa',
                'name' => 'my_group',
                'aria-label' => '',
                'type' => 'post_object',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'post_type' => array(
                    0 => 'group',
                ),
                'post_status' => '',
                'taxonomy' => '',
                'return_format' => 'object',
                'multiple' => 0,
                'allow_null' => 1,
                'bidirectional' => 0,
                'ui' => 1,
                'bidirectional_target' => array(),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'user_role',
                    'operator' => '==',
                    'value' => 'all',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ));
});

<?php

function add_gravity_forms_acf_field()
{
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group(array(
            'key' => 'group_gravity_forms',
            'title' => 'Select Gravity Form',
            'fields' => array(
                array(
                    'key' => 'field_gravity_form',
                    'label' => 'Gravity Forms',
                    'name' => 'gravity_form',
                    'type' => 'select',
                    'choices' => array(), // We'll populate this dynamically.
                    'allow_null' => 1,
                    'ui' => 1,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'page_template',
                        'operator' => '==',
                        'value' => 'contact-us.php', // Change this as needed.
                    ),
                ),
                array(
                    array(
                        'param' => 'page_template',
                        'operator' => '==',
                        'value' => 'form.php', // Change this as needed.
                    )),
            ),
        ));
    }
}

add_action('acf/init', 'add_gravity_forms_acf_field');
function populate_gravity_forms_acf_field($field)
{
    if (class_exists('GFAPI')) {
        $forms = \GFAPI::get_forms();
        if (!empty($forms)) {
            foreach ($forms as $form) {
                $field['choices'][$form['id']] = $form['title'];
            }
        }
    }
    return $field;
}

add_filter('acf/load_field/key=field_gravity_form', 'populate_gravity_forms_acf_field');

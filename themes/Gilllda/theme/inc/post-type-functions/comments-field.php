<?php
function mu_remove_website_field_comment_form( $fields ) {
    if( isset( $fields['url'] ) ) {
        unset( $fields['url'] );
    }
    return $fields;
}
add_action( 'comment_form_default_fields', 'mu_remove_website_field_comment_form' );

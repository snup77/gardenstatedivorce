<?php

add_action( 'init', function() {
    register_taxonomy( 'attorney_location', 'attorney', [
        'label'        => 'Location',
        'hierarchical' => true,
        'public'       => false,
        'show_ui'      => true,
        'show_in_rest' => true,
        'rewrite'      => false,
    ] );
} );
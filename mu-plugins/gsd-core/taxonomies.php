<?php

add_action( 'init', function() {

	register_taxonomy( 'county', [ 'attorney', 'office' ], [
		'label'        => 'County',
		'public'       => false,
        'show_ui'      => true,
		'hierarchical' => true,
		'rewrite'      => [ 'slug' => 'county' ],
		'show_in_rest' => true,
	] );

	register_taxonomy( 'city', [ 'attorney', 'office' ], [
		'label'        => 'City',
		'public'       => false,
        'show_ui'      => true,
		'hierarchical' => true,
		'rewrite'      => [ 'slug' => 'city' ],
		'show_in_rest' => true,
	] );

} );
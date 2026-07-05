<?php

add_action( 'init', function() {

	register_post_type( 'attorney', [
		'label'        => 'Attorneys',
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => [ 'slug' => 'divorce-attorneys' ],
		'menu_icon'    => 'dashicons-businessperson',
		'supports'     => [ 'title', 'thumbnail' ],
		'show_in_rest' => true,
	] );

	register_post_type( 'firm', [
		'label'        => 'Firms',
        'public'       => false,
        'show_ui'      => true,
		'has_archive'  => true,
		'rewrite'      => [ 'slug' => 'firms' ],
		'menu_icon'    => 'dashicons-building',
		'supports'     => [ 'title' ],
		'show_in_rest' => true,
	] );

	register_post_type( 'office', [
		'label'        => 'Offices',
		'public'       => false, // no standalone office pages for v1
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-location',
		'supports'     => [ 'title' ],
		'show_in_rest' => true,
	] );

} );
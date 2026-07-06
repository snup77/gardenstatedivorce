<?php
/**
 * Theme functions.
 */

// Theme setup: title tag, featured images, standard html5 markup.
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );
} );

// Enqueue theme stylesheet, fonts, and scripts.
add_action( 'wp_enqueue_scripts', function () {
	$version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'gsd-fonts',
		'https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,400;6..72,500;6..72,600&display=swap',
		[],
		null
	);

	wp_enqueue_style( 'gsd-style', get_stylesheet_uri(), [], $version );
	wp_enqueue_script( 'gsd-menu', get_theme_file_uri( '/assets/js/menu.js' ), [], $version, true );
} );

// Disable the block editor for all post types.
add_filter( 'use_block_editor_for_post', '__return_false', 100 );

// Disable block-editor styles/assets from loading on the front end.
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
}, 100 );

// Remove block-editor-only widgets screen in favor of the classic widgets UI.
remove_theme_support( 'widgets-block-editor' );

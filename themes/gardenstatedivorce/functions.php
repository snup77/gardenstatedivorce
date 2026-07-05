<?php
/**
 * Theme functions.
 */

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

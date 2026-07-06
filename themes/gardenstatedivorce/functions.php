<?php
/**
 * Theme functions.
 */

// Theme setup: title tag, featured images, standard html5 markup.
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ] );

	register_nav_menus([
        'primary' => __('Primary Menu', 'garden-state-divorce'),
    ]);
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

/**
 * Renders Primary Menu items as bare <a> tags (no <li>/<ul>) so they drop
 * straight into the existing flex nav markup. Pass a $cta_class to style
 * whichever item is tagged with the "cta" CSS class (set via the menu
 * item's "CSS Classes" field in Appearance > Menus) as a button; omit it
 * (as the footer does) to render every item as a plain link regardless of
 * that tag.
 */
class GSD_Nav_Walker extends Walker_Nav_Menu {

	private $link_class;
	private $cta_class;

	public function __construct( $link_class = '', $cta_class = '' ) {
		$this->link_class = $link_class;
		$this->cta_class  = $cta_class;
	}

	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$is_cta = $this->cta_class && in_array( 'cta', $item->classes, true );
		$class  = $is_cta ? $this->cta_class : $this->link_class;

		if ( in_array( 'current-menu-item', $item->classes, true ) ) {
			$class = trim( $class . ' is-active' );
		}

		$output .= sprintf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( $item->url ),
			esc_attr( $class ),
			esc_html( $item->title )
		);
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
}

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

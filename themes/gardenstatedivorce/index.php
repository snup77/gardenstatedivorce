<?php get_header(); ?>

<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		the_title( '<h1>', '</h1>' );
		the_content();
	endwhile;
else :
	esc_html_e( 'Nothing found.', 'gardenstatedivorce' );
endif;
?>

<?php get_footer(); ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site-canvas">
  <div class="site-shell">

    <!-- ============================== header ============================== -->
    <header class="site-header">
      <?php if ( has_custom_logo() ) : ?>
        <?php the_custom_logo(); ?>
      <?php else : ?>
        <a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
          <span class="brand__mark">G</span>
          <span class="brand__name"><?php bloginfo( 'name' ); ?></span>
        </a>
      <?php endif; ?>
      <nav class="site-menu">
        <?php
        wp_nav_menu( [
          'theme_location' => 'primary',
          'container'      => false,
          'items_wrap'     => '%3$s',
          'walker'         => new GSD_Nav_Walker( 'nav-link', 'nav-cta' ),
          'fallback_cb'    => false,
        ] );
        ?>
      </nav>
      <button class="nav-toggle" type="button" data-menu-toggle aria-label="Toggle menu">
        <svg class="icon-open" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M3 12h18"></path><path d="M3 18h18"></path></svg>
        <svg class="icon-close" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
      </button>
    </header>

    <main id="main-content">

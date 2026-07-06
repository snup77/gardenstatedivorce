  </main>

    <!-- ============================== footer ============================== -->
    <footer class="site-footer">
      <span class="brand brand--sm">
        <span class="brand__mark">G</span>
        <span class="brand__name"><?php bloginfo( 'name' ); ?></span>
      </span>
      <nav class="site-footer__links">
        <?php
        wp_nav_menu( [
          'theme_location' => 'primary',
          'container'      => false,
          'items_wrap'     => '%3$s',
          'walker'         => new GSD_Nav_Walker( '' ),
          'fallback_cb'    => false,
        ] );
        ?>
      </nav>
      <div class="site-footer__copy">&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></div>
    </footer>

  </div>
</div>
<?php wp_footer(); ?>
</body>
</html>

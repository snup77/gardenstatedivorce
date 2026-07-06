  </main>

    <!-- ============================== footer ============================== -->
    <footer class="site-footer">
      <span class="brand brand--sm">
        <span class="brand__mark">G</span>
        <span class="brand__name"><?php bloginfo( 'name' ); ?></span>
      </span>
      <nav class="site-footer__links">
        <a href="#">About</a>
        <a href="#">For attorneys</a>
        <a href="#">Privacy</a>
        <a href="talk-to-us.html">Contact</a>
      </nav>
      <div class="site-footer__copy">&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></div>
    </footer>

  </div>
</div>
<?php wp_footer(); ?>
</body>
</html>

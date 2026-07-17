<?php get_header(); ?>

<!-- =============================== hero =============================== -->
    <section class="page-hero">
      <h1 class="page-hero__title">Find a New Jersey divorce attorney with confidence.</h1>
      <p class="page-hero__lede">Garden State Divorce is an independent directory of New Jersey divorce attorneys, bringing together professional certifications, peer recognition, experience, and other objective credentials so you can make a more informed decision.</p>
    </section>

    <!-- ============================== toolbar ============================= -->
    <div class="toolbar">
      <div class="toolbar__count"><strong><?php echo esc_html( wp_count_posts( 'attorney' )->publish ); ?> attorneys</strong></div>
    </div>

    <!-- ============================ attorney list ========================= -->
    <?php if ( have_posts() ) : ?>
      <?php while ( have_posts() ) : the_post(); ?>
        <?php
        $firm           = get_field( 'firm' );
        $office         = get_field( 'primary_office' );
        $headshot       = get_field( 'headshot' );
        $license_year   = get_field( 'license_year' );
        $years_licensed = $license_year ? ( (int) current_time( 'Y' ) - (int) $license_year ) : null;
        $city           = $office ? get_field( 'city', $office->ID ) : '';
        $state          = $office ? get_field( 'state', $office->ID ) : '';
        $firm_url       = $firm ? get_field( 'website_url', $firm->ID ) : '';

        $meta_parts = [];
        if ( $city ) {
          $meta_parts[] = esc_html( $city . ( $state ? ', ' . $state : '' ) );
        }
        if ( null !== $years_licensed ) {
          $meta_parts[] = 'Licensed for ' . esc_html( $years_licensed ) . ' years';
        }

        $creds = [];
        if ( get_field( 'nj_matrimonial_cert' ) ) {
          $creds[] = 'Certified Matrimonial Attorney';
        }
        if ( ! empty( get_field( 'super_lawyers' )['listed'] ) ) {
          $creds[] = 'Super Lawyers';
        }
        if ( get_field( 'av_preeminent' ) ) {
          $creds[] = 'AV Preeminent';
        }
        if ( ! empty( get_field( 'chambers' )['listed'] ) ) {
          $creds[] = 'Chambers High Net Worth';
        }
        ?>
        <article class="attorney-row">
          <div class="attorney-row__photo<?php echo $headshot ? '' : ' placeholder'; ?>">
            <?php if ( $headshot ) : ?>
              <img src="<?php echo esc_url( $headshot['url'] ); ?>" alt="<?php echo esc_attr( $headshot['alt'] ?: get_the_title() ); ?>">
            <?php else : ?>
              <span class="placeholder__label">headshot</span>
            <?php endif; ?>
          </div>
          <div>
            <div class="attorney-row__name"><?php the_title(); ?></div>
            <?php if ( $firm ) : ?>
              <div class="attorney-row__firm"><?php echo esc_html( get_the_title( $firm->ID ) ); ?></div>
            <?php endif; ?>
            <?php if ( $meta_parts ) : ?>
              <div class="attorney-row__meta"><?php echo implode( ' &middot; ', $meta_parts ); ?></div>
            <?php endif; ?>
            <?php if ( $creds ) : ?>
              <div class="attorney-row__creds">
                <?php foreach ( $creds as $cred ) : ?>
                  <div class="attorney-row__cred"><svg class="star" width="14" height="14" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg><?php echo esc_html( $cred ); ?></div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="attorney-row__actions">
            <a class="btn btn--primary btn--sm" href="<?php the_permalink(); ?>">View Profile</a>
            <?php if ( $firm_url ) : ?>
              <a class="attorney-row__website" href="<?php echo esc_url( $firm_url ); ?>" target="_blank" rel="noopener">
                <svg class="icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6"></path><path d="M20 4 11 13"></path><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"></path></svg>
                Visit website
              </a>
            <?php endif; ?>
          </div>
        </article>
      <?php endwhile; ?>
    <?php endif; ?>

    <!-- ============================ pagination ============================ -->
    <?php
    $pagination_links = paginate_links( [
      'total'     => $wp_query->max_num_pages,
      'current'   => max( 1, get_query_var( 'paged' ) ),
      'prev_text' => '&larr;',
      'next_text' => '&rarr;',
      'type'      => 'array',
    ] );
    ?>
    <?php if ( $pagination_links ) : ?>
      <nav class="pagination">
        <?php foreach ( $pagination_links as $link ) : ?>
          <?php echo str_replace( 'page-numbers current', 'page-numbers is-current', $link ); ?>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

<?php get_footer(); ?>

<?php get_header(); ?>

<?php $gsd_attorney_count = wp_count_posts( 'attorney' )->publish; ?>

<!-- =============================== hero =============================== -->
    <section class="hero">
      <div>
        <span class="badge">New Jersey Divorce Attorneys</span>
        <h1 class="hero__title">A divorce is hard enough. Finding the right attorney shouldn't be.</h1>
        <p class="hero__lede">Garden State Divorce is an independent directory of New Jersey divorce attorneys, highlighting professional certifications, peer recognition, and other objective credentials to help you choose with confidence.</p>
        <div class="hero__actions">
          <a class="btn btn--primary" href="divorce-attorneys/">Find an Attorney</a>
        </div>
        <p class="hero__note"><?php echo esc_html( $gsd_attorney_count ); ?> divorce attorneys across New Jersey.</p>
      </div>

      <!-- trust panel -->
      <aside class="trust-panel">
        <div class="trust-panel__title">We highlight</div>
        <div class="trust-panel__list">
          <div class="recognition">
            <svg class="star" width="16" height="16" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
            <div class="recognition__label">Certified Matrimonial Attorney</div>
            <div class="recognition__detail">New Jersey Supreme Court certification recognizing experienced family law specialists.</div>
          </div>
          <div class="recognition">
            <svg class="star" width="16" height="16" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
            <div class="recognition__label">Super Lawyers</div>
            <div class="recognition__detail">Independent peer recognition for outstanding attorneys.</div>
          </div>
          <div class="recognition">
            <svg class="star" width="16" height="16" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
            <div class="recognition__label">AV Preeminent</div>
            <div class="recognition__detail">Martindale-Hubbell's highest rating for ability and ethics.</div>
          </div>
          <div class="recognition">
            <svg class="star" width="16" height="16" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
            <div class="recognition__label">Chambers High Net Worth</div>
            <div class="recognition__detail">Independent recognition for complex, high-asset family law matters.</div>
          </div>
        </div>
      </aside>
    </section>

    <!-- =============================== why =============================== -->
    <section class="section" id="why">
      <div class="kicker">Why Garden State Divorce</div>
      <h2 class="section__title">Finding the right attorney starts with better information.</h2>
      <div class="grid-3">
        <div>
          <div class="pillar__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path></svg>
          </div>
          <div class="pillar__title">Professional certifications</div>
          <p class="pillar__body">We identify attorneys who have earned New Jersey Supreme Court certification as Matrimonial Attorneys and clearly highlight that distinction.</p>
        </div>
        <div>
          <div class="pillar__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"></circle><path d="M8.2 13.9 7 22l5-3 5 3-1.2-8.1"></path></svg>
          </div>
          <div class="pillar__title">Objective recognition</div>
          <p class="pillar__body">We highlight respected credentials like Super Lawyers, AV Preeminent, and Chambers so they're easy to compare.</p>
        </div>
        <div>
          <div class="pillar__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.5V19l4 2v-8.5L22 3z"></path></svg>
          </div>
          <div class="pillar__title">Quality over quantity</div>
          <p class="pillar__body">We focus on meaningful attorney profiles rather than overwhelming lists, helping you compare lawyers more efficiently.</p>
        </div>
      </div>
    </section>

    <!-- ============================ the roster ============================ -->
    <section class="section">
      <div class="section__head">
        <div>
          <div class="kicker">Featured Attorneys</div>
          <h2 class="section__title">Compare New Jersey Divorce Attorneys</h2>
        </div>
        <a class="link-arrow" href="divorce-attorneys/">View all <?php echo esc_html( $gsd_attorney_count ); ?> attorneys
          <svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M13 6l6 6-6 6"></path></svg>
        </a>
      </div>

      <div class="roster">
        <?php
        $gsd_roster_query = new WP_Query( [
          'post_type'      => 'attorney',
          'post_status'    => 'publish',
          'posts_per_page' => 4,
          'orderby'        => 'rand',
        ] );
        ?>
        <?php while ( $gsd_roster_query->have_posts() ) : $gsd_roster_query->the_post(); ?>
          <?php
          $firm     = get_field( 'firm' );
          $office   = get_field( 'primary_office' );
          $headshot = get_field( 'headshot' );
          $city     = $office ? get_field( 'city', $office->ID ) : '';
          $state    = $office ? get_field( 'state', $office->ID ) : '';

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
          // Certified Matrimonial Attorney is always checked first above, so
          // it's already first when present; just cap the list to 2 total.
          $creds = array_slice( $creds, 0, 2 );
          ?>
          <!-- attorney card -->
          <article class="attorney-card">
            <div class="attorney-card__photo<?php echo $headshot ? '' : ' placeholder'; ?>">
              <?php if ( $headshot ) : ?>
                <img src="<?php echo esc_url( $headshot['url'] ); ?>" alt="<?php echo esc_attr( $headshot['alt'] ?: get_the_title() ); ?>">
              <?php else : ?>
                <span class="placeholder__label">headshot</span>
              <?php endif; ?>
            </div>
            <div class="attorney-card__body">
              <div class="attorney-card__info">
                <div class="attorney-card__name"><?php the_title(); ?></div>
                <?php if ( $firm ) : ?>
                  <div class="attorney-card__firm"><?php echo esc_html( get_the_title( $firm->ID ) ); ?></div>
                <?php endif; ?>
                <?php if ( $city ) : ?>
                  <div class="attorney-card__city"><?php echo esc_html( $city . ( $state ? ', ' . $state : '' ) ); ?></div>
                <?php endif; ?>
                <?php if ( $creds ) : ?>
                  <div class="attorney-card__creds">
                    <?php foreach ( $creds as $cred ) : ?>
                      <div class="attorney-card__cred"><svg class="star" width="14" height="14" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg><?php echo esc_html( $cred ); ?></div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
              <a class="btn btn--primary btn--sm" href="<?php the_permalink(); ?>">View Profile</a>
            </div>
          </article>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </section>

    <!-- ============================= cta band ============================= -->
    <section class="cta-band">
      <div>
        <div class="cta-band__title">Ready to talk to someone who can help?</div>
        <p class="cta-band__body">Browse our directory of New Jersey divorce attorneys and contact the lawyers who best match your needs.</p>
      </div>
      <a class="btn btn--onDark" href="/divorce-attorneys/">Find an Attorney</a>
    </section>

    <?php get_footer(); ?>
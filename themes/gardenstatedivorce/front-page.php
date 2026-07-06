<?php get_header(); ?>

<?php $gsd_attorney_count = wp_count_posts( 'attorney' )->publish; ?>

<!-- =============================== hero =============================== -->
    <section class="hero">
      <div>
        <span class="badge">A curated roster &middot; New Jersey</span>
        <h1 class="hero__title">A divorce is hard enough. Finding the right attorney shouldn't be.</h1>
        <p class="hero__lede">Garden State Divorce is a curated roster of New Jersey's most recognized divorce attorneys &mdash; every one board-certified and vetted for reputation, so you can choose with confidence.</p>
        <div class="hero__actions">
          <a class="btn btn--primary" href="divorce-attorneys/">Find an Attorney</a>
        </div>
        <p class="hero__note"><?php echo esc_html( $gsd_attorney_count ); ?> divorce attorneys across New Jersey.</p>
      </div>

      <!-- trust panel -->
      <aside class="trust-panel">
        <div class="trust-panel__title">Every listing is</div>
        <div class="trust-panel__list">
          <div class="recognition">
            <svg class="star" width="16" height="16" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
            <div class="recognition__body">
              <div class="recognition__label">Certified Matrimonial Attorney</div>
              <div class="recognition__detail">A NJ Supreme Court certification held by a small fraction of the bar.</div>
            </div>
          </div>
          <div class="recognition">
            <svg class="star" width="16" height="16" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
            <div class="recognition__body">
              <div class="recognition__label">Super Lawyers</div>
              <div class="recognition__detail">Peer-nominated recognition for the top attorneys in family law.</div>
            </div>
          </div>
          <div class="recognition">
            <svg class="star" width="16" height="16" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
            <div class="recognition__body">
              <div class="recognition__label">AV Preeminent</div>
              <div class="recognition__detail">Martindale-Hubbell's highest rating for ability and ethics.</div>
            </div>
          </div>
          <div class="recognition">
            <svg class="star" width="16" height="16" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
            <div class="recognition__body">
              <div class="recognition__label">Chambers High Net Worth</div>
              <div class="recognition__detail">Independent ranking for complex, high-asset divorces.</div>
            </div>
          </div>
        </div>
      </aside>
    </section>

    <!-- =============================== why =============================== -->
    <section class="section" id="why">
      <div class="kicker">Why Garden State Divorce</div>
      <h2 class="section__title">A shortlist you can actually trust &mdash; not another phone book.</h2>
      <div class="grid-3">
        <div>
          <div class="pillar__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path></svg>
          </div>
          <div class="pillar__title">Every attorney is board-certified</div>
          <p class="pillar__body">New Jersey certifies only a small share of its lawyers as Matrimonial Attorneys. Every listing here holds that certification.</p>
        </div>
        <div>
          <div class="pillar__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"></circle><path d="M8.2 13.9 7 22l5-3 5 3-1.2-8.1"></path></svg>
          </div>
          <div class="pillar__title">Vetted for reputation</div>
          <p class="pillar__body">We track Super Lawyers, AV Preeminent, and Chambers ratings so you can see professional recognition at a glance.</p>
        </div>
        <div>
          <div class="pillar__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.5V19l4 2v-8.5L22 3z"></path></svg>
          </div>
          <div class="pillar__title">A shortlist, not a phone book</div>
          <p class="pillar__body">No ads and no pay-to-play. Just a curated roster of attorneys you can reach out to directly.</p>
        </div>
      </div>
    </section>

    <!-- ============================ the roster ============================ -->
    <section class="section">
      <div class="section__head">
        <div>
          <div class="kicker">The roster</div>
          <h2 class="section__title">Attorneys earning the top recognitions.</h2>
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
          'posts_per_page' => 3,
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
        <p class="cta-band__body">Browse the full roster of certified New Jersey divorce attorneys and reach out directly &mdash; no middleman, no referral fees.</p>
      </div>
      <a class="btn btn--onDark" href="/divorce-attorneys/">Find an Attorney</a>
    </section>

    <?php get_footer(); ?>
<?php get_header(); ?>

<?php
while ( have_posts() ) : the_post();

$attorney_id = get_the_ID();
$first_name  = explode( ' ', get_the_title() )[0];

$firm   = get_field( 'firm' );
$office = get_field( 'primary_office' );

$headshot       = get_field( 'headshot' );
$email          = get_field( 'email_address' );
$phone          = get_field( 'phone_number' );
$website_url    = get_field( 'website_url' );
$license_year   = get_field( 'license_year' );
$years_licensed = $license_year ? ( (int) current_time( 'Y' ) - (int) $license_year ) : null;
$verified_date  = get_field( 'date_last_verified' );
$biography      = get_field( 'biography' );

$nj_cert       = get_field( 'nj_matrimonial_cert' );
$aaml          = get_field( 'aaml_fellowship' );
$av_preeminent = get_field( 'av_preeminent' );
$super_lawyers = get_field( 'super_lawyers' );
$best_lawyers  = get_field( 'best_lawyers' );
$chambers      = get_field( 'chambers' );
$avvo          = get_field( 'avvo' );
$other_dirs    = get_field( 'other_directories' );

$education    = get_field( 'education' );
$associations = get_field( 'associations' );

$office_city   = $office ? get_field( 'city', $office->ID ) : '';
$office_state  = $office ? get_field( 'state', $office->ID ) : '';
$office_street = $office ? get_field( 'street', $office->ID ) : '';
$office_suite  = $office ? get_field( 'suite', $office->ID ) : '';
$office_zip    = $office ? get_field( 'zip', $office->ID ) : '';
$office_phone  = $office ? get_field( 'phone', $office->ID ) : '';
$office_gbp    = $office ? get_field( 'gbp', $office->ID ) : null;

$counties = [];
foreach ( wp_get_post_terms( $attorney_id, 'attorney_location' ) as $term ) {
	if ( 0 === $term->parent ) {
		$counties[] = $term->name;
	}
}

$degree_labels = [
	'JD'  => 'J.D.',
	'LLM' => 'LL.M.',
	'BA'  => 'B.A.',
	'BS'  => 'B.S.',
	'MA'  => 'M.A.',
];
?>

<!-- ============================ breadcrumb ============================ -->
    <div class="breadcrumb">
      <a href="<?php echo esc_url( get_post_type_archive_link( 'attorney' ) ); ?>">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
        All attorneys
      </a>
    </div>

    <!-- =============================== hero =============================== -->
    <section class="profile-hero">
      <div class="profile-hero__photo<?php echo $headshot ? '' : ' placeholder'; ?>">
        <?php if ( $headshot ) : ?>
          <img src="<?php echo esc_url( $headshot['url'] ); ?>" alt="<?php echo esc_attr( $headshot['alt'] ?: get_the_title() ); ?>">
        <?php else : ?>
          <span class="placeholder__label">headshot</span>
        <?php endif; ?>
      </div>
      <div>
        <?php if ( $firm ) : ?>
          <div class="profile-hero__firm"><?php echo esc_html( get_the_title( $firm->ID ) ); ?></div>
        <?php endif; ?>
        <h1 class="profile-hero__name"><?php the_title(); ?></h1>
        <div class="profile-hero__meta">
          <?php
          $meta_parts = [];
          if ( $office_city ) {
            $meta_parts[] = esc_html( $office_city . ( $office_state ? ', ' . $office_state : '' ) );
          }
          if ( $counties ) {
            $meta_parts[] = 'Serving ' . esc_html( implode( ' &amp; ', $counties ) ) . ' County';
          }
          if ( null !== $years_licensed ) {
            $meta_parts[] = 'Licensed for ' . esc_html( $years_licensed ) . ' years';
          }
          echo implode( ' &nbsp;&middot;&nbsp; ', $meta_parts );
          ?>
        </div>

        <div class="profile-hero__recognitions">
          <?php if ( $nj_cert ) : ?>
            <div class="recognition">
              <svg class="star" width="15" height="15" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
              <div class="recognition__label">NJ Supreme Court Certified Matrimonial Attorney</div>
              <div class="recognition__detail">Fewer than 160 statewide</div>
            </div>
          <?php endif; ?>
          <?php if ( $aaml ) : ?>
            <div class="recognition">
              <svg class="star" width="15" height="15" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
              <div class="recognition__label">Fellow, American Academy of Matrimonial Lawyers</div>
              <div class="recognition__detail">~45 in New Jersey</div>
            </div>
          <?php endif; ?>
          <?php if ( ! empty( $super_lawyers['listed'] ) ) : ?>
            <div class="recognition">
              <svg class="star" width="15" height="15" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
              <div class="recognition__label">Super Lawyers</div>
              <?php if ( ! empty( $super_lawyers['years'] ) ) : ?>
                <div class="recognition__detail"><?php echo esc_html( str_replace( '-', '–', $super_lawyers['years'] ) ); ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ( ! empty( $best_lawyers['listed'] ) ) : ?>
            <div class="recognition">
              <svg class="star" width="15" height="15" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
              <div class="recognition__label">Best Lawyers in America</div>
              <?php if ( ! empty( $best_lawyers['start_year'] ) ) : ?>
                <div class="recognition__detail">Since <?php echo esc_html( $best_lawyers['start_year'] ); ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ( $av_preeminent ) : ?>
            <div class="recognition">
              <svg class="star" width="15" height="15" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
              <div class="recognition__label">AV Preeminent</div>
              <div class="recognition__detail">Martindale-Hubbell</div>
            </div>
          <?php endif; ?>
          <?php if ( ! empty( $chambers['listed'] ) ) : ?>
            <div class="recognition">
              <svg class="star" width="15" height="15" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
              <div class="recognition__label">Chambers High Net Worth</div>
              <?php if ( ! empty( $chambers['tier'] ) ) : ?>
                <div class="recognition__detail"><?php echo esc_html( $chambers['tier'] ); ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="profile-hero__actions">
          <?php if ( $phone ) : ?>
            <a class="btn btn--primary btn--sm" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
              <svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
              <?php echo esc_html( $phone ); ?>
            </a>
          <?php endif; ?>
          <?php if ( $email ) : ?>
            <a class="btn btn--ghost btn--sm" href="mailto:<?php echo esc_attr( $email ); ?>">
              <svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 5L2 7"></path></svg>
              Email
            </a>
          <?php endif; ?>
          <?php if ( $website_url ) : ?>
            <a class="link-arrow link-arrow--muted" href="<?php echo esc_url( $website_url ); ?>" target="_blank" rel="noopener">
              <svg class="icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6"></path><path d="M20 4 11 13"></path><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"></path></svg>
              Visit website
            </a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- =========================== main + sidebar ========================= -->
    <div class="profile-body">

      <!-- main column -->
      <main class="profile-main">
        <h2 class="profile-main__heading">About <?php echo esc_html( $first_name ); ?></h2>
        <div class="prose">
          <?php echo $biography; ?>
        </div>

        <?php if ( $associations ) : ?>
          <h2 class="profile-main__heading mt">Professional associations</h2>
          <div class="assoc">
            <?php foreach ( $associations as $assoc ) : ?>
              <div class="assoc__item">
                <div><div class="assoc__name"><?php echo esc_html( $assoc['association'] ); ?></div><div class="assoc__role"><?php echo esc_html( $assoc['role'] ); ?></div></div>
                <?php if ( ! empty( $assoc['start_year'] ) ) : ?>
                  <div class="assoc__years"><?php echo esc_html( $assoc['start_year'] ); ?>&ndash;<?php echo esc_html( ! empty( $assoc['end_year'] ) ? $assoc['end_year'] : 'Present' ); ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </main>

      <!-- sidebar -->
      <aside class="profile-sidebar">
        <!-- office card -->
        <?php if ( $office ) : ?>
          <div class="card card--tinted">
            <div class="card__title">Office</div>
            <?php if ( $firm ) : ?>
              <div class="office__firm"><?php echo esc_html( get_the_title( $firm->ID ) ); ?></div>
            <?php endif; ?>
            <div class="office__addr">
              <?php
              $addr_parts = array_filter( [ $office_street, $office_suite, $office_city, trim( $office_state . ' ' . $office_zip ) ] );
              echo esc_html( implode( ', ', $addr_parts ) );
              ?>
            </div>
            <?php if ( ! empty( $office_gbp['url'] ) ) : ?>
              <a class="office__maplink" href="<?php echo esc_url( $office_gbp['url'] ); ?>" target="_blank" rel="noopener">
                View on Google Maps
                <svg class="icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6"></path><path d="M20 4 11 13"></path><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"></path></svg>
              </a>
            <?php endif; ?>
            <?php if ( $office_phone ) : ?>
              <div class="office__contact">
                <svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2E6F40" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <?php echo esc_html( $office_phone ); ?>
              </div>
            <?php endif; ?>
            <?php if ( $email ) : ?>
              <div class="office__contact office__contact--email">
                <svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2E6F40" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 5L2 7"></path></svg>
                <?php echo esc_html( $email ); ?>
              </div>
            <?php endif; ?>
            <?php if ( ! empty( $office_gbp['rating'] ) || ! empty( $avvo['rating'] ) ) : ?>
              <div class="office__ratings">
                <?php if ( ! empty( $office_gbp['rating'] ) ) : ?>
                  <div>
                    <div class="rating__row">
                      <svg class="star" width="15" height="15" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
                      <span class="rating__score"><?php echo esc_html( $office_gbp['rating'] ); ?></span>
                      <span class="rating__meta">Google &middot; <?php echo esc_html( $office_gbp['review_number'] ); ?> reviews</span>
                    </div>
                    <div class="rating__kind">Firm rating</div>
                  </div>
                <?php endif; ?>
                <?php if ( ! empty( $avvo['rating'] ) ) : ?>
                  <div>
                    <div class="rating__row">
                      <svg class="star" width="15" height="15" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26 6.85.6-5.2 4.52 1.56 6.7L12 17.27 5.89 20.58l1.56-6.7-5.2-4.52 6.85-.6z"></path></svg>
                      <span class="rating__score"><?php echo esc_html( $avvo['stars'] ); ?></span>
                      <span class="rating__meta">Avvo &middot; <?php echo esc_html( $avvo['review_number'] ); ?> review<?php echo 1 == $avvo['review_number'] ? '' : 's'; ?> &middot; Rating: <?php echo esc_html( number_format( (float) $avvo['rating'], 1 ) ); ?></span>
                    </div>
                    <div class="rating__kind">Attorney rating</div>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- education card -->
        <?php if ( $education ) : ?>
          <div class="card">
            <div class="card__title">Education</div>
            <div class="edu">
              <?php foreach ( $education as $edu ) : ?>
                <div>
                  <div class="edu__school"><?php echo esc_html( $edu['school'] ); ?></div>
                  <div class="edu__line"><?php echo esc_html( $degree_labels[ $edu['degree'] ] ?? $edu['degree'] ); ?>, <?php echo esc_html( $edu['graduation_year'] ); ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- third-party directory links -->
        <?php
        $dirlinks = [];
        if ( ! empty( $avvo['url'] ) ) {
          $dirlinks[] = [ 'label' => 'Avvo', 'url' => $avvo['url'] ];
        }
        if ( ! empty( $other_dirs['findlaw_url'] ) ) {
          $dirlinks[] = [ 'label' => 'FindLaw', 'url' => $other_dirs['findlaw_url'] ];
        }
        if ( ! empty( $other_dirs['martindale_url'] ) ) {
          $dirlinks[] = [ 'label' => 'Martindale', 'url' => $other_dirs['martindale_url'] ];
        }
        if ( ! empty( $other_dirs['justia_url'] ) ) {
          $dirlinks[] = [ 'label' => 'Justia', 'url' => $other_dirs['justia_url'] ];
        }
        ?>
        <?php if ( $dirlinks ) : ?>
          <div class="card">
            <div class="card__title">Find <?php echo esc_html( $first_name ); ?> on</div>
            <div class="dirlinks">
              <?php foreach ( $dirlinks as $link ) : ?>
                <a class="dirlinks__item" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener">
                  <span><?php echo esc_html( $link['label'] ); ?></span>
                  <svg class="icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2E6F40" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6"></path><path d="M20 4 11 13"></path><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"></path></svg>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ( $verified_date ) : ?>
          <div class="verified">Verified <?php echo esc_html( date_i18n( 'F j, Y', strtotime( $verified_date ) ) ); ?></div>
        <?php endif; ?>
      </aside>
    </div>

<?php endwhile; ?>

<?php get_footer(); ?>

<?php
/**
 * WP-CLI command: imports Firms, Offices, Attorneys (with Education,
 * Associations, and attorney_location taxonomy terms) from the JSON file
 * produced by xlsx_to_json.py.
 *
 *     python3 wp-content/mu-plugins/gsd-core/import/xlsx_to_json.py \
 *         "/path/to/Data Fields.xlsx" \
 *         wp-content/mu-plugins/gsd-core/import/data/attorneys.json
 *
 *     wp gsd import-data --file=wp-content/mu-plugins/gsd-core/import/data/attorneys.json
 *
 * Idempotent: each imported post stores the spreadsheet's own id
 * (_gsd_firm_id / _gsd_office_id / _gsd_attorney_id) as post meta, so
 * re-running against an updated spreadsheet updates existing posts instead
 * of creating duplicates. County/city terms are matched by slug, with
 * county always the parent of city in the attorney_location taxonomy.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class GSD_Import_Command {

	private $firm_id_map   = [];
	private $office_id_map = [];
	private $term_cache    = [];

	/**
	 * Import attorneys, firms, offices, and location terms from a JSON data file.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Path to the JSON file produced by xlsx_to_json.py.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gsd import-data --file=wp-content/mu-plugins/gsd-core/import/data/attorneys.json
	 */
	public function import_data( $args, $assoc_args ) {
		$file = $assoc_args['file'] ?? '';

		if ( ! $file || ! file_exists( $file ) ) {
			WP_CLI::error( "File not found: {$file}" );
		}

		$data = json_decode( file_get_contents( $file ), true );

		if ( null === $data ) {
			WP_CLI::error( 'Failed to parse JSON: ' . json_last_error_msg() );
		}

		$this->import_firms( $data['firms'] ?? [] );
		$this->import_offices( $data['offices'] ?? [] );
		$this->import_attorneys(
			$data['attorneys'] ?? [],
			$data['education'] ?? [],
			$data['associations'] ?? [],
			$data['attorney_locations'] ?? []
		);

		WP_CLI::success( 'Import complete.' );
	}

	private function import_firms( $firms ) {
		foreach ( $firms as $firm ) {
			$post_id = $this->upsert_post( 'firm', '_gsd_firm_id', $firm['firm_id'], $firm['firm_name'] );

			if ( ! $post_id ) {
				continue;
			}

			update_field( 'website_url', $firm['website_url'] ?? '', $post_id );

			$this->firm_id_map[ $firm['firm_id'] ] = $post_id;
			WP_CLI::log( "Firm #{$firm['firm_id']} -> post #{$post_id} ({$firm['firm_name']})" );
		}
	}

	private function import_offices( $offices ) {
		foreach ( $offices as $office ) {
			$firm_post_id = $this->firm_id_map[ $office['firm_id'] ] ?? 0;

			if ( ! $firm_post_id ) {
				WP_CLI::warning( "Office #{$office['office_id']}: unknown firm_id {$office['firm_id']}, skipping." );
				continue;
			}

			$title   = get_the_title( $firm_post_id ) . ' - ' . $office['city'];
			$post_id = $this->upsert_post( 'office', '_gsd_office_id', $office['office_id'], $title );

			if ( ! $post_id ) {
				continue;
			}

			update_field( 'firm', $firm_post_id, $post_id );
			update_field( 'street', $office['street'] ?? '', $post_id );
			update_field( 'suite', $office['suite'] ?? '', $post_id );
			update_field( 'city', $office['city'] ?? '', $post_id );
			update_field( 'state', $office['state'] ?? '', $post_id );
			update_field( 'zip', $office['zip'] ?? '', $post_id );
			update_field( 'phone', $office['phone'] ?? '', $post_id );
			update_field( 'gbp', [
				'url'           => $office['gbp_url'] ?? '',
				'rating'        => $office['gbp_rating'] ?? '',
				'review_number' => $office['gbp_review_number'] ?? '',
			], $post_id );

			$this->office_id_map[ $office['office_id'] ] = $post_id;
			WP_CLI::log( "Office #{$office['office_id']} -> post #{$post_id} ({$office['city']})" );
		}
	}

	private function import_attorneys( $attorneys, $education, $associations, $locations ) {
		foreach ( $attorneys as $att ) {
			$name    = trim( "{$att['first_name']} {$att['last_name']}" );
			$post_id = $this->upsert_post( 'attorney', '_gsd_attorney_id', $att['attorney_id'], $name );

			if ( ! $post_id ) {
				continue;
			}

			update_field( 'email_address', $att['email_address'] ?? '', $post_id );
			update_field( 'phone_number', $att['phone_number'] ?? '', $post_id );
			update_field( 'license_year', $att['license_year'] ?? '', $post_id );
			update_field( 'date_last_verified', $att['date_last_verified'] ?? '', $post_id );
			update_field( 'website_url', $att['website_url'] ?? '', $post_id );
			update_field( 'biography', wpautop( $att['biography'] ?? '' ), $post_id );

			$this->maybe_sideload_headshot( $post_id, $att['headshot_url'] ?? '', $name );

			$office_post_id = $this->office_id_map[ $att['primary_office_id'] ] ?? 0;

			update_field( 'firm', $this->firm_id_map[ $att['firm_id'] ] ?? 0, $post_id );
			update_field( 'primary_office', $office_post_id, $post_id );

			$this->update_seo_meta( $post_id, $att, $office_post_id );

			update_field( 'nj_matrimonial_cert', (bool) ( $att['nj_matrimonial_cert'] ?? false ), $post_id );
			update_field( 'aaml_fellowship', (bool) ( $att['aaml_fellowship'] ?? false ), $post_id );
			update_field( 'av_preeminent', (bool) ( $att['av_preeminent'] ?? false ), $post_id );

			update_field( 'super_lawyers', [
				'listed' => (bool) ( $att['super_lawyers'] ?? false ),
				'years'  => $att['super_lawyers_years'] ?? '',
				'url'    => $att['super_lawyers_url'] ?? '',
			], $post_id );

			update_field( 'best_lawyers', [
				'listed'     => (bool) ( $att['best_lawyers'] ?? false ),
				'start_year' => $att['best_lawyers_start_year'] ?? '',
				'url'        => $att['best_lawyers_url'] ?? '',
			], $post_id );

			update_field( 'chambers', [
				'listed' => (bool) ( $att['chambers'] ?? false ),
				'tier'   => $att['chambers_tier'] ?? '',
				'url'    => $att['chambers_url'] ?? '',
			], $post_id );

			update_field( 'avvo', [
				'url'           => $att['avvo_url'] ?? '',
				'rating'        => $att['avvo_rating'] ?? '',
				'review_number' => $att['avvo_review_number'] ?? '',
				'stars'         => $att['avv_stars'] ?? '',
			], $post_id );

			update_field( 'other_directories', [
				'findlaw_url'    => $att['findlaw_url'] ?? '',
				'martindale_url' => $att['martindale_url'] ?? '',
				'justia_url'     => $att['justia_url'] ?? '',
			], $post_id );

			$edu_rows = array_values( array_filter( $education, fn( $e ) => $e['attorney_id'] === $att['attorney_id'] ) );
			update_field( 'education', array_map( fn( $e ) => [
				'school'          => $e['school'],
				'degree'          => $e['degree'],
				'graduation_year' => $e['graduation_year'],
			], $edu_rows ), $post_id );

			$assoc_rows = array_values( array_filter( $associations, fn( $a ) => $a['attorney_id'] === $att['attorney_id'] ) );
			update_field( 'associations', array_map( fn( $a ) => [
				'association' => $a['association'],
				'role'        => $a['role'],
				'start_year'  => $a['start_year'],
				'end_year'    => $a['end_year'],
			], $assoc_rows ), $post_id );

			$loc_rows = array_values( array_filter( $locations, fn( $l ) => $l['attorney_id'] === $att['attorney_id'] ) );
			$term_ids = [];

			foreach ( $loc_rows as $loc ) {
				$term_ids[] = $this->get_or_create_city_term( $loc['city_slug'], $loc['county_slug'] );

				if ( $loc['county_slug'] ) {
					$term_ids[] = $this->get_or_create_county_term( $loc['county_slug'] );
				}
			}

			wp_set_object_terms( $post_id, array_unique( array_filter( $term_ids ) ), 'attorney_location' );

			WP_CLI::log( "Attorney #{$att['attorney_id']} -> post #{$post_id} ({$name})" );
		}
	}

	private function upsert_post( $post_type, $meta_key, $source_id, $title ) {
		$existing_id = $this->find_by_source_id( $post_type, $meta_key, $source_id );

		$post_id = wp_insert_post( [
			'ID'          => $existing_id ?: 0,
			'post_type'   => $post_type,
			'post_title'  => $title,
			'post_status' => 'publish',
		], true );

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::warning( "{$post_type} \"{$title}\": " . $post_id->get_error_message() );
			return 0;
		}

		update_post_meta( $post_id, $meta_key, $source_id );

		return $post_id;
	}

	private function find_by_source_id( $post_type, $meta_key, $source_id ) {
		$existing = get_posts( [
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'meta_key'       => $meta_key,
			'meta_value'     => $source_id,
			'fields'         => 'ids',
			'posts_per_page' => 1,
		] );

		return $existing ? (int) $existing[0] : 0;
	}

	/**
	 * Sets the Yoast SEO title/meta description for an attorney post.
	 * Title format: "{first_name} {last_name} | {city}, NJ Divorce Attorney",
	 * where city comes from the linked primary office's `city` field.
	 */
	private function update_seo_meta( $post_id, $att, $office_post_id ) {
		$city = $office_post_id ? get_field( 'city', $office_post_id ) : '';

		if ( $city ) {
			$seo_title = "{$att['first_name']} {$att['last_name']} | {$city}, NJ Divorce Attorney";
			update_post_meta( $post_id, '_yoast_wpseo_title', $seo_title );
		} else {
			WP_CLI::warning( "Attorney #{$att['attorney_id']}: no primary office city found, skipping SEO title." );
		}

		if ( ! empty( $att['meta_description'] ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $att['meta_description'] );
		}
	}

	private function maybe_sideload_headshot( $post_id, $url, $desc ) {
		if ( ! $url || get_field( 'headshot', $post_id ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_sideload_image( $url, $post_id, $desc, 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			WP_CLI::warning( "Headshot sideload failed for post {$post_id}: " . $attachment_id->get_error_message() );
			return;
		}

		update_field( 'headshot', $attachment_id, $post_id );
		set_post_thumbnail( $post_id, $attachment_id );
	}

	private function get_or_create_county_term( $slug ) {
		$cache_key = "county:{$slug}";

		if ( isset( $this->term_cache[ $cache_key ] ) ) {
			return $this->term_cache[ $cache_key ];
		}

		$existing = term_exists( $slug, 'attorney_location' );

		if ( $existing ) {
			$term_id = (int) $existing['term_id'];
		} else {
			$term = wp_insert_term( $this->slug_to_name( $slug ), 'attorney_location', [ 'slug' => $slug ] );

			if ( is_wp_error( $term ) ) {
				WP_CLI::error( "Failed creating county term {$slug}: " . $term->get_error_message() );
			}

			$term_id = (int) $term['term_id'];
		}

		$this->term_cache[ $cache_key ] = $term_id;

		return $term_id;
	}

	private function get_or_create_city_term( $slug, $county_slug ) {
		if ( ! $slug ) {
			return 0;
		}

		$cache_key = "city:{$slug}";

		if ( isset( $this->term_cache[ $cache_key ] ) ) {
			return $this->term_cache[ $cache_key ];
		}

		$parent_id = $county_slug ? $this->get_or_create_county_term( $county_slug ) : 0;
		$existing  = term_exists( $slug, 'attorney_location' );

		if ( $existing ) {
			$term_id = (int) $existing['term_id'];
		} else {
			$term = wp_insert_term( $this->slug_to_name( $slug ), 'attorney_location', [
				'slug'   => $slug,
				'parent' => $parent_id,
			] );

			if ( is_wp_error( $term ) ) {
				WP_CLI::error( "Failed creating city term {$slug}: " . $term->get_error_message() );
			}

			$term_id = (int) $term['term_id'];
		}

		$this->term_cache[ $cache_key ] = $term_id;

		return $term_id;
	}

	private function slug_to_name( $slug ) {
		return ucwords( str_replace( '-', ' ', $slug ) );
	}
}

add_action( 'cli_init', function () {
	WP_CLI::add_command( 'gsd import-data', [ new GSD_Import_Command(), 'import_data' ] );
} );

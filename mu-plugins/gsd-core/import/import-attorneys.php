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
 *     wp gsd import-data --file=... --dry-run
 *
 * Full file, every run: this command always processes every firm, office,
 * and attorney in the file — there is no scope filtering or incremental
 * mode. What makes repeated full runs cheap is change detection, not a
 * smaller input: each record's importer-managed fields are hashed, and the
 * hash is stored in `_gsd_source_hash` post meta. A run that finds the same
 * hash already stored does nothing at all for that record (no post write, no
 * field write, no term write, no touching post_modified). Only genuinely new
 * or changed records cause any database writes.
 *
 * Validation runs before any writes: duplicate source IDs (two firms sharing
 * a firm_id, etc.) abort the entire run with nothing written. An attorney
 * whose firm_id/primary_office_id doesn't resolve to anything in the file
 * only warns — that one relationship field is left untouched (an existing
 * value is never overwritten with a blank/0; a new attorney is created with
 * that field left blank) while the rest of the attorney is still processed
 * normally. Offices with an unresolved firm_id are still skipped entirely,
 * as before.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class GSD_Import_Command {

	const DRY_RUN_PLACEHOLDER = 'dry-run-placeholder';

	private $firm_id_map   = [];
	private $office_id_map = [];
	private $term_cache    = [];

	private $is_dry_run = false;
	private $warnings   = [];
	private $report     = [
		'firm'     => [ 'create' => [], 'update' => [], 'unchanged' => 0 ],
		'office'   => [ 'create' => [], 'update' => [], 'unchanged' => 0 ],
		'attorney' => [ 'create' => [], 'update' => [], 'unchanged' => 0 ],
	];

	/**
	 * Import attorneys, firms, offices, and location terms from a JSON data file.
	 *
	 * ## OPTIONS
	 *
	 * --file=<path>
	 * : Path to the JSON file produced by xlsx_to_json.py.
	 *
	 * [--dry-run]
	 * : Report what would be created/updated/left unchanged without writing
	 * anything to the database.
	 *
	 * ## EXAMPLES
	 *
	 *     wp gsd import-data --file=wp-content/mu-plugins/gsd-core/import/data/attorneys.json
	 *     wp gsd import-data --file=wp-content/mu-plugins/gsd-core/import/data/attorneys.json --dry-run
	 */
	public function import_data( $args, $assoc_args ) {
		$file = $assoc_args['file'] ?? '';

		if ( ! $file || ! file_exists( $file ) ) {
			WP_CLI::error( "File not found: {$file}" );
		}

		$this->is_dry_run = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		$data = json_decode( file_get_contents( $file ), true );

		if ( null === $data ) {
			WP_CLI::error( 'Failed to parse JSON: ' . json_last_error_msg() );
		}

		// Hard stop, before anything else runs, if any entity type has a
		// duplicate source ID. This has happened before in data entry.
		$this->validate_no_duplicate_ids( $data );

		if ( $this->is_dry_run ) {
			WP_CLI::log( 'DRY RUN — no changes will be written.' );
		}

		$this->import_firms( $data['firms'] ?? [] );
		$this->import_offices( $data['offices'] ?? [], $data['attorney_locations'] ?? [] );
		$this->import_attorneys(
			$data['attorneys'] ?? [],
			$data['education'] ?? [],
			$data['associations'] ?? [],
			$data['attorney_locations'] ?? [],
			$data['offices'] ?? []
		);

		if ( $this->is_dry_run ) {
			$this->print_dry_run_report();
			WP_CLI::success( 'Dry run complete — no changes were made.' );
		} else {
			$this->print_summary();
			WP_CLI::success( 'Import complete.' );
		}
	}

	// ------------------------------------------------------------- Validation

	private function validate_no_duplicate_ids( $data ) {
		$checks = [
			'firms'     => [ 'firm_id', 'Firm', 'firm_name' ],
			'offices'   => [ 'office_id', 'Office', 'city' ],
			'attorneys' => [ 'attorney_id', 'Attorney', 'first_name' ],
		];

		$problems = [];

		foreach ( $checks as $data_key => $check ) {
			[ $id_key, $label, $name_key ] = $check;
			$seen = [];

			foreach ( $data[ $data_key ] ?? [] as $item ) {
				$id            = $item[ $id_key ] ?? null;
				$seen[ $id ][] = $item[ $name_key ] ?? '(unnamed)';
			}

			foreach ( $seen as $id => $names ) {
				if ( count( $names ) > 1 ) {
					$problems[] = "{$label} #{$id} appears " . count( $names ) . ' times (' . implode( ', ', $names ) . ')';
				}
			}
		}

		if ( $problems ) {
			WP_CLI::log( 'Duplicate source IDs found — aborting before any writes:' );

			foreach ( $problems as $problem ) {
				WP_CLI::log( "  - {$problem}" );
			}

			WP_CLI::error( 'Import aborted due to duplicate source IDs above. Fix the spreadsheet/JSON and try again. Nothing was written.' );
		}
	}

	// ------------------------------------------------------------------ Firms

	private function import_firms( $firms ) {
		foreach ( $firms as $firm ) {
			$firm_id = $firm['firm_id'];
			$title   = $firm['firm_name'];

			$new_data = [
				'firm_name'   => $firm['firm_name'] ?? null,
				'website_url' => $firm['website_url'] ?? null,
			];
			$new_hash = $this->hash_of( $new_data );

			$existing_id = $this->find_by_source_id( 'firm', '_gsd_firm_id', $firm_id );

			if ( $existing_id ) {
				$old_hash = get_post_meta( $existing_id, '_gsd_source_hash', true );

				if ( $old_hash === $new_hash ) {
					$this->firm_id_map[ $firm_id ] = $existing_id;
					$this->report_unchanged( 'firm' );
					continue;
				}

				if ( $this->is_dry_run ) {
					$current = [
						'firm_name'   => get_the_title( $existing_id ),
						'website_url' => get_field( 'website_url', $existing_id ),
					];
					$this->report_update( 'firm', $firm_id, $title, $this->diff_fields( $current, $new_data ) );
					$this->firm_id_map[ $firm_id ] = $existing_id;
					continue;
				}

				$post_id = $this->write_post( 'firm', $existing_id, $title );
			} else {
				if ( $this->is_dry_run ) {
					$this->report_create( 'firm', $firm_id, $title );
					$this->firm_id_map[ $firm_id ] = self::DRY_RUN_PLACEHOLDER;
					continue;
				}

				$post_id = $this->write_post( 'firm', 0, $title );
			}

			if ( ! $post_id ) {
				continue;
			}

			$existing_id ? $this->report_update( 'firm', $firm_id, $title ) : $this->report_create( 'firm', $firm_id, $title );

			update_post_meta( $post_id, '_gsd_firm_id', $firm_id );
			update_field( 'website_url', $firm['website_url'] ?? '', $post_id );
			update_post_meta( $post_id, '_gsd_source_hash', $new_hash );

			$this->firm_id_map[ $firm_id ] = $post_id;
			WP_CLI::log( "Firm #{$firm_id} -> post #{$post_id} ({$title})" );
		}
	}

	// ---------------------------------------------------------------- Offices

	private function import_offices( $offices, $locations ) {
		foreach ( $offices as $office ) {
			$office_id    = $office['office_id'];
			$firm_post_id = $this->firm_id_map[ $office['firm_id'] ] ?? null;

			if ( null === $firm_post_id ) {
				WP_CLI::warning( "Office #{$office_id}: unknown firm_id {$office['firm_id']}, skipping." );
				continue;
			}

			$firm_title = ( self::DRY_RUN_PLACEHOLDER === $firm_post_id )
				? '(new firm)'
				: get_the_title( $firm_post_id );
			$title = "{$firm_title} - {$office['city']}";

			$new_data = [
				'firm_id' => $office['firm_id'],
				'street'  => $office['street'] ?? null,
				'suite'   => $office['suite'] ?? null,
				'city'    => $office['city'] ?? null,
				'state'   => $office['state'] ?? null,
				'zip'     => $office['zip'] ?? null,
				'phone'   => $office['phone'] ?? null,
				'gbp'     => [
					'url'           => $office['gbp_url'] ?? null,
					'rating'        => $office['gbp_rating'] ?? null,
					'review_number' => $office['gbp_review_number'] ?? null,
				],
			];
			$new_hash = $this->hash_of( $new_data );

			$existing_id = $this->find_by_source_id( 'office', '_gsd_office_id', $office_id );

			if ( $existing_id ) {
				$old_hash = get_post_meta( $existing_id, '_gsd_source_hash', true );

				if ( $old_hash === $new_hash ) {
					$this->office_id_map[ $office_id ] = $existing_id;
					$this->report_unchanged( 'office' );
					continue;
				}

				if ( $this->is_dry_run ) {
					$this->report_update( 'office', $office_id, $title, $this->diff_fields( $this->current_office_fields( $existing_id ), $new_data ) );
					$this->office_id_map[ $office_id ] = $existing_id;
					continue;
				}

				$post_id = $this->write_post( 'office', $existing_id, $title );
			} else {
				if ( $this->is_dry_run ) {
					$this->report_create( 'office', $office_id, $title );
					$this->office_id_map[ $office_id ] = self::DRY_RUN_PLACEHOLDER;
					continue;
				}

				$post_id = $this->write_post( 'office', 0, $title );
			}

			if ( ! $post_id ) {
				continue;
			}

			$existing_id ? $this->report_update( 'office', $office_id, $title ) : $this->report_create( 'office', $office_id, $title );

			update_post_meta( $post_id, '_gsd_office_id', $office_id );
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
			update_post_meta( $post_id, '_gsd_source_hash', $new_hash );

			$this->office_id_map[ $office_id ] = $post_id;
			WP_CLI::log( "Office #{$office_id} -> post #{$post_id} ({$office['city']})" );
		}
	}

	private function current_office_fields( $post_id ) {
		$firm_field = get_field( 'firm', $post_id );
		$gbp        = get_field( 'gbp', $post_id );

		return [
			'firm_id' => $firm_field ? (int) get_post_meta( $firm_field->ID, '_gsd_firm_id', true ) : null,
			'street'  => get_field( 'street', $post_id ),
			'suite'   => get_field( 'suite', $post_id ),
			'city'    => get_field( 'city', $post_id ),
			'state'   => get_field( 'state', $post_id ),
			'zip'     => get_field( 'zip', $post_id ),
			'phone'   => get_field( 'phone', $post_id ),
			'gbp'     => [
				'url'           => $gbp['url'] ?? null,
				'rating'        => $gbp['rating'] ?? null,
				'review_number' => $gbp['review_number'] ?? null,
			],
		];
	}

	// -------------------------------------------------------------- Attorneys

	private function import_attorneys( $attorneys, $education, $associations, $locations, $offices ) {
		$offices_by_id = array_column( $offices, null, 'office_id' );

		foreach ( $attorneys as $att ) {
			$attorney_id = $att['attorney_id'];
			$name        = trim( "{$att['first_name']} {$att['last_name']}" );

			$edu_rows   = array_values( array_filter( $education, fn( $e ) => $e['attorney_id'] === $attorney_id ) );
			$assoc_rows = array_values( array_filter( $associations, fn( $a ) => $a['attorney_id'] === $attorney_id ) );
			$loc_rows   = array_values( array_filter( $locations, fn( $l ) => $l['attorney_id'] === $attorney_id ) );

			$office_city = $offices_by_id[ $att['primary_office_id'] ]['city'] ?? null;

			$new_data = $this->build_attorney_hash_data( $att, $edu_rows, $assoc_rows, $loc_rows, $office_city );
			$new_hash = $this->hash_of( $new_data );

			$existing_id = $this->find_by_source_id( 'attorney', '_gsd_attorney_id', $attorney_id );

			if ( $existing_id ) {
				$old_hash = get_post_meta( $existing_id, '_gsd_source_hash', true );

				if ( $old_hash === $new_hash ) {
					$this->report_unchanged( 'attorney' );
					continue;
				}

				if ( $this->is_dry_run ) {
					$this->report_update( 'attorney', $attorney_id, $name, $this->diff_fields( $this->current_attorney_fields( $existing_id ), $new_data ) );
					$this->check_attorney_relations( $att, $name, true );
					continue;
				}

				$post_id = $this->write_post( 'attorney', $existing_id, $name );
			} else {
				if ( $this->is_dry_run ) {
					$this->report_create( 'attorney', $attorney_id, $name );
					$this->check_attorney_relations( $att, $name, false );
					continue;
				}

				$post_id = $this->write_post( 'attorney', 0, $name );
			}

			if ( ! $post_id ) {
				continue;
			}

			$is_new = ! $existing_id;
			$is_new ? $this->report_create( 'attorney', $attorney_id, $name ) : $this->report_update( 'attorney', $attorney_id, $name );

			update_post_meta( $post_id, '_gsd_attorney_id', $attorney_id );

			update_field( 'email_address', $att['email_address'] ?? '', $post_id );
			update_field( 'phone_number', $att['phone_number'] ?? '', $post_id );
			update_field( 'license_year', $att['license_year'] ?? '', $post_id );
			update_field( 'date_last_verified', $att['date_last_verified'] ?? '', $post_id );
			update_field( 'website_url', $att['website_url'] ?? '', $post_id );
			update_field( 'biography', wpautop( $att['biography'] ?? '' ), $post_id );

			$this->maybe_sideload_headshot( $post_id, $att['headshot_url'] ?? '', $name );

			$firm_post_id   = $this->firm_id_map[ $att['firm_id'] ] ?? null;
			$office_post_id = $this->office_id_map[ $att['primary_office_id'] ] ?? null;

			if ( null === $firm_post_id ) {
				$this->warn_unresolved_relation( $name, $attorney_id, 'firm_id', $att['firm_id'], $is_new );
			} else {
				update_field( 'firm', $firm_post_id, $post_id );
			}

			if ( null === $office_post_id ) {
				$this->warn_unresolved_relation( $name, $attorney_id, 'primary_office_id', $att['primary_office_id'], $is_new );
			} else {
				update_field( 'primary_office', $office_post_id, $post_id );
			}

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

			update_field( 'education', array_map( fn( $e ) => [
				'school'          => $e['school'],
				'degree'          => $e['degree'],
				'graduation_year' => $e['graduation_year'],
			], $edu_rows ), $post_id );

			update_field( 'associations', array_map( fn( $a ) => [
				'association' => $a['association'],
				'role'        => $a['role'],
				'start_year'  => $a['start_year'],
				'end_year'    => $a['end_year'],
			], $assoc_rows ), $post_id );

			$term_ids = [];

			foreach ( $loc_rows as $loc ) {
				$term_ids[] = $this->get_or_create_city_term( $loc['city_slug'], $loc['county_slug'] );

				if ( $loc['county_slug'] ) {
					$term_ids[] = $this->get_or_create_county_term( $loc['county_slug'] );
				}
			}

			wp_set_object_terms( $post_id, array_unique( array_filter( $term_ids ) ), 'attorney_location' );

			update_post_meta( $post_id, '_gsd_source_hash', $new_hash );

			WP_CLI::log( "Attorney #{$attorney_id} -> post #{$post_id} ({$name})" );
		}
	}

	/**
	 * Dry-run-only: still surface unresolved firm/office references as
	 * warnings even though no writes happen, so the report matches what a
	 * real run would warn about.
	 */
	private function check_attorney_relations( $att, $name, $is_existing ) {
		if ( ! array_key_exists( $att['firm_id'], $this->firm_id_map ) ) {
			$this->warn_unresolved_relation( $name, $att['attorney_id'], 'firm_id', $att['firm_id'], ! $is_existing );
		}

		if ( ! array_key_exists( $att['primary_office_id'], $this->office_id_map ) ) {
			$this->warn_unresolved_relation( $name, $att['attorney_id'], 'primary_office_id', $att['primary_office_id'], ! $is_existing );
		}
	}

	private function warn_unresolved_relation( $name, $attorney_id, $field, $source_value, $is_new ) {
		$consequence = $is_new
			? 'leaving the field blank on the new post — it needs a valid reference'
			: 'leaving the existing relationship in WordPress untouched rather than overwriting it';

		$this->warn( "Attorney #{$attorney_id} ({$name}): {$field} {$source_value} does not resolve to any firm/office in this file; {$consequence}." );
	}

	private function build_attorney_hash_data( $att, $edu_rows, $assoc_rows, $loc_rows, $office_city ) {
		return [
			'first_name'          => $att['first_name'] ?? null,
			'last_name'           => $att['last_name'] ?? null,
			'email_address'       => $att['email_address'] ?? null,
			'phone_number'        => $att['phone_number'] ?? null,
			'license_year'        => $att['license_year'] ?? null,
			'date_last_verified'  => $att['date_last_verified'] ?? null,
			'website_url'         => $att['website_url'] ?? null,
			'biography'           => $att['biography'] ?? null,
			'firm_id'             => $att['firm_id'] ?? null,
			'primary_office_id'   => $att['primary_office_id'] ?? null,
			'office_city'         => $office_city,
			'meta_description'    => $att['meta_description'] ?? null,
			'nj_matrimonial_cert' => (bool) ( $att['nj_matrimonial_cert'] ?? false ),
			'aaml_fellowship'     => (bool) ( $att['aaml_fellowship'] ?? false ),
			'av_preeminent'       => (bool) ( $att['av_preeminent'] ?? false ),
			'super_lawyers'       => [
				'listed' => (bool) ( $att['super_lawyers'] ?? false ),
				'years'  => $att['super_lawyers_years'] ?? null,
				'url'    => $att['super_lawyers_url'] ?? null,
			],
			'best_lawyers'        => [
				'listed'     => (bool) ( $att['best_lawyers'] ?? false ),
				'start_year' => $att['best_lawyers_start_year'] ?? null,
				'url'        => $att['best_lawyers_url'] ?? null,
			],
			'chambers'            => [
				'listed' => (bool) ( $att['chambers'] ?? false ),
				'tier'   => $att['chambers_tier'] ?? null,
				'url'    => $att['chambers_url'] ?? null,
			],
			'avvo'                => [
				'url'           => $att['avvo_url'] ?? null,
				'rating'        => $att['avvo_rating'] ?? null,
				'review_number' => $att['avvo_review_number'] ?? null,
				'stars'         => $att['avv_stars'] ?? null,
			],
			'other_directories'   => [
				'findlaw_url'    => $att['findlaw_url'] ?? null,
				'martindale_url' => $att['martindale_url'] ?? null,
				'justia_url'     => $att['justia_url'] ?? null,
			],
			'education'           => array_map( fn( $e ) => [
				'school'          => $e['school'] ?? null,
				'degree'          => $e['degree'] ?? null,
				'graduation_year' => $e['graduation_year'] ?? null,
			], $edu_rows ),
			'associations'        => array_map( fn( $a ) => [
				'association' => $a['association'] ?? null,
				'role'        => $a['role'] ?? null,
				'start_year'  => $a['start_year'] ?? null,
				'end_year'    => $a['end_year'] ?? null,
			], $assoc_rows ),
			'locations'           => array_map( fn( $l ) => [
				'county_slug' => $l['county_slug'] ?? null,
				'city_slug'   => $l['city_slug'] ?? null,
			], $loc_rows ),
		];
	}

	private function current_attorney_fields( $post_id ) {
		$title      = get_the_title( $post_id );
		$name_parts = explode( ' ', $title, 2 );

		$office_field = get_field( 'primary_office', $post_id );
		$office_city  = $office_field ? get_field( 'city', $office_field->ID ) : null;
		$office_id    = $office_field ? (int) get_post_meta( $office_field->ID, '_gsd_office_id', true ) : null;

		$firm_field = get_field( 'firm', $post_id );
		$firm_id    = $firm_field ? (int) get_post_meta( $firm_field->ID, '_gsd_firm_id', true ) : null;

		$sl   = get_field( 'super_lawyers', $post_id );
		$bl   = get_field( 'best_lawyers', $post_id );
		$ch   = get_field( 'chambers', $post_id );
		$avvo = get_field( 'avvo', $post_id );
		$dirs = get_field( 'other_directories', $post_id );

		$education    = get_field( 'education', $post_id ) ?: [];
		$associations = get_field( 'associations', $post_id ) ?: [];

		$locations = [];

		foreach ( wp_get_post_terms( $post_id, 'attorney_location' ) as $term ) {
			if ( $term->parent ) {
				$parent               = get_term( $term->parent, 'attorney_location' );
				$locations[]           = [
					'county_slug' => $parent ? $parent->slug : null,
					'city_slug'   => $term->slug,
				];
			}
		}

		return [
			'first_name'          => $name_parts[0] ?? null,
			'last_name'           => $name_parts[1] ?? null,
			'email_address'       => get_field( 'email_address', $post_id ),
			'phone_number'        => get_field( 'phone_number', $post_id ),
			'license_year'        => get_field( 'license_year', $post_id ),
			'date_last_verified'  => get_field( 'date_last_verified', $post_id ),
			'website_url'         => get_field( 'website_url', $post_id ),
			'biography'           => get_field( 'biography', $post_id ),
			'firm_id'             => $firm_id,
			'primary_office_id'   => $office_id,
			'office_city'         => $office_city,
			'meta_description'    => get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ),
			'nj_matrimonial_cert' => (bool) get_field( 'nj_matrimonial_cert', $post_id ),
			'aaml_fellowship'     => (bool) get_field( 'aaml_fellowship', $post_id ),
			'av_preeminent'       => (bool) get_field( 'av_preeminent', $post_id ),
			'super_lawyers'       => [
				'listed' => (bool) ( $sl['listed'] ?? false ),
				'years'  => $sl['years'] ?? null,
				'url'    => $sl['url'] ?? null,
			],
			'best_lawyers'        => [
				'listed'     => (bool) ( $bl['listed'] ?? false ),
				'start_year' => $bl['start_year'] ?? null,
				'url'        => $bl['url'] ?? null,
			],
			'chambers'            => [
				'listed' => (bool) ( $ch['listed'] ?? false ),
				'tier'   => $ch['tier'] ?? null,
				'url'    => $ch['url'] ?? null,
			],
			'avvo'                => [
				'url'           => $avvo['url'] ?? null,
				'rating'        => $avvo['rating'] ?? null,
				'review_number' => $avvo['review_number'] ?? null,
				'stars'         => $avvo['stars'] ?? null,
			],
			'other_directories'   => [
				'findlaw_url'    => $dirs['findlaw_url'] ?? null,
				'martindale_url' => $dirs['martindale_url'] ?? null,
				'justia_url'     => $dirs['justia_url'] ?? null,
			],
			'education'           => array_map( fn( $e ) => [
				'school'          => $e['school'] ?? null,
				'degree'          => $e['degree'] ?? null,
				'graduation_year' => $e['graduation_year'] ?? null,
			], $education ),
			'associations'        => array_map( fn( $a ) => [
				'association' => $a['association'] ?? null,
				'role'        => $a['role'] ?? null,
				'start_year'  => $a['start_year'] ?? null,
				'end_year'    => $a['end_year'] ?? null,
			], $associations ),
			'locations'           => $locations,
		];
	}

	// -------------------------------------------------------------- Utilities

	private function write_post( $post_type, $existing_id, $title ) {
		$post_args = [
			'ID'          => $existing_id ?: 0,
			'post_type'   => $post_type,
			'post_title'  => $title,
			'post_status' => 'publish',
		];

		// Preserve the original post_date on updates. Without this,
		// wp_insert_post() resets it to "now" on every import run, which
		// makes WordPress core log a new _wp_old_date meta row each time
		// (its old-permalink-redirect tracking) — unbounded, pointless growth.
		if ( $existing_id ) {
			$existing_post              = get_post( $existing_id );
			$post_args['post_date']     = $existing_post->post_date;
			$post_args['post_date_gmt'] = $existing_post->post_date_gmt;
		}

		$post_id = wp_insert_post( $post_args, true );

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::warning( "{$post_type} \"{$title}\": " . $post_id->get_error_message() );
			return 0;
		}

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

		// media_sideload_image() only recognizes a file type via the URL's
		// own path extension. CDNs that serve images via query string
		// (LinkedIn's profile photo CDN, Google's cached thumbnails, etc.)
		// have no extension in the path and fail here even though the URL
		// is a perfectly real, live image — fall back to downloading the
		// bytes and sniffing the real content type instead of trusting the
		// URL's path.
		if ( is_wp_error( $attachment_id ) ) {
			$attachment_id = $this->sideload_by_content_sniffing( $url, $post_id, $desc );
		}

		if ( is_wp_error( $attachment_id ) ) {
			WP_CLI::warning( "Headshot sideload failed for post {$post_id}: " . $attachment_id->get_error_message() );
			return;
		}

		update_field( 'headshot', $attachment_id, $post_id );
		set_post_thumbnail( $post_id, $attachment_id );
	}

	private function sideload_by_content_sniffing( $url, $post_id, $desc ) {
		$tmp_file = download_url( $url );

		if ( is_wp_error( $tmp_file ) ) {
			return $tmp_file;
		}

		$mime_to_ext = [
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/gif'  => 'gif',
			'image/webp' => 'webp',
		];

		$mime = wp_get_image_mime( $tmp_file );
		$ext  = $mime_to_ext[ $mime ] ?? null;

		if ( ! $ext ) {
			@unlink( $tmp_file );
			return new WP_Error(
				'invalid_image',
				'Downloaded file is not a recognized image type (detected: ' . ( $mime ?: 'unknown' ) . ').'
			);
		}

		$file_array = [
			'name'     => sanitize_file_name( $desc ) . '-headshot.' . $ext,
			'tmp_name' => $tmp_file,
		];

		$attachment_id = media_handle_sideload( $file_array, $post_id, $desc );

		if ( file_exists( $tmp_file ) ) {
			@unlink( $tmp_file );
		}

		return $attachment_id;
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

			// Keep the parent in sync if the spreadsheet's county for this
			// city changed since the term was first created.
			$term = get_term( $term_id, 'attorney_location' );

			if ( $term && (int) $term->parent !== $parent_id ) {
				wp_update_term( $term_id, 'attorney_location', [ 'parent' => $parent_id ] );
			}
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

	// ---------------------------------------------------- Hashing/normalizing

	/**
	 * Recursively normalizes a value for stable hashing/comparison: trims
	 * strings, treats null/missing/empty-string as the same thing, coerces
	 * numeric values (int/float/numeric-string) to one canonical form so
	 * "10", 10, and 10.0 all hash identically, and sorts associative-array
	 * keys (never list/row order, which is meaningful for repeaters).
	 */
	private function normalize_for_hash( $value ) {
		if ( is_array( $value ) ) {
			$out = [];

			foreach ( $value as $k => $v ) {
				$out[ $k ] = $this->normalize_for_hash( $v );
			}

			if ( ! array_is_list( $value ) ) {
				ksort( $out );
			}

			return $out;
		}

		if ( null === $value ) {
			return '';
		}

		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return rtrim( rtrim( sprintf( '%.6F', (float) $value ), '0' ), '.' );
		}

		if ( is_string( $value ) ) {
			$trimmed = trim( $value );
			return '' === $trimmed ? '' : $trimmed;
		}

		return $value;
	}

	private function hash_of( $data ) {
		return hash( 'sha256', wp_json_encode( $this->normalize_for_hash( $data ) ) );
	}

	/**
	 * Dry-run only: top-level field-by-field diff between the current
	 * (already-normalized-on-read) values and the incoming normalized
	 * values, for human-readable reporting. Returns raw (non-normalized)
	 * old/new values so the printed report is meaningful, keyed by field.
	 */
	private function diff_fields( $old, $new ) {
		$diffs = [];
		$keys  = array_unique( array_merge( array_keys( $old ), array_keys( $new ) ) );

		foreach ( $keys as $key ) {
			$old_norm = $this->normalize_for_hash( $old[ $key ] ?? null );
			$new_norm = $this->normalize_for_hash( $new[ $key ] ?? null );

			if ( $old_norm !== $new_norm ) {
				$diffs[ $key ] = [
					'old' => $old[ $key ] ?? null,
					'new' => $new[ $key ] ?? null,
				];
			}
		}

		return $diffs;
	}

	// -------------------------------------------------------------- Reporting

	private function warn( $message ) {
		$this->warnings[] = $message;
		WP_CLI::warning( $message );
	}

	private function report_create( $type, $source_id, $title ) {
		$this->report[ $type ]['create'][] = [ 'id' => $source_id, 'title' => $title ];
	}

	private function report_update( $type, $source_id, $title, $diffs = [] ) {
		$this->report[ $type ]['update'][] = [ 'id' => $source_id, 'title' => $title, 'diffs' => $diffs ];
	}

	private function report_unchanged( $type ) {
		++$this->report[ $type ]['unchanged'];
	}

	private function format_value_for_report( $value ) {
		if ( is_array( $value ) ) {
			return wp_json_encode( $value );
		}

		if ( null === $value || '' === $value ) {
			return '(empty)';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		return (string) $value;
	}

	private function print_dry_run_report() {
		$labels = [ 'firm' => 'Firms', 'office' => 'Offices', 'attorney' => 'Attorneys' ];

		foreach ( $labels as $type => $label ) {
			$r = $this->report[ $type ];

			WP_CLI::log( '' );
			WP_CLI::log( "=== {$label} ===" );

			if ( $r['create'] ) {
				WP_CLI::log( 'Would create:' );
				foreach ( $r['create'] as $item ) {
					WP_CLI::log( "  #{$item['id']} {$item['title']}" );
				}
			}

			if ( $r['update'] ) {
				WP_CLI::log( 'Would update:' );
				foreach ( $r['update'] as $item ) {
					WP_CLI::log( "  #{$item['id']} {$item['title']}" );
					foreach ( $item['diffs'] as $field => $vals ) {
						$old = $this->format_value_for_report( $vals['old'] );
						$new = $this->format_value_for_report( $vals['new'] );
						WP_CLI::log( "      {$field}: {$old} -> {$new}" );
					}
				}
			}

			WP_CLI::log( "Unchanged: {$r['unchanged']}" );
		}

		if ( $this->warnings ) {
			WP_CLI::log( '' );
			WP_CLI::log( '=== Validation warnings ===' );
			foreach ( $this->warnings as $warning ) {
				WP_CLI::log( "  {$warning}" );
			}
		}

		WP_CLI::log( '' );
		WP_CLI::log( '=== Summary ===' );
		foreach ( $labels as $type => $label ) {
			$r = $this->report[ $type ];
			WP_CLI::log( sprintf(
				'%-10s %d to create, %d to update, %d unchanged',
				$label . ':',
				count( $r['create'] ),
				count( $r['update'] ),
				$r['unchanged']
			) );
		}
	}

	private function print_summary() {
		$labels = [ 'firm' => 'Firms', 'office' => 'Offices', 'attorney' => 'Attorneys' ];

		WP_CLI::log( '' );
		WP_CLI::log( '=== Summary ===' );
		foreach ( $labels as $type => $label ) {
			$r = $this->report[ $type ];
			WP_CLI::log( sprintf(
				'%-10s %d created, %d updated, %d unchanged (skipped)',
				$label . ':',
				count( $r['create'] ),
				count( $r['update'] ),
				$r['unchanged']
			) );
		}
	}
}

add_action( 'cli_init', function () {
	WP_CLI::add_command( 'gsd import-data', [ new GSD_Import_Command(), 'import_data' ] );
} );

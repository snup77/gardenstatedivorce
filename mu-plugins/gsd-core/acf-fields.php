<?php

add_action( 'acf/init', function() {

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
	return;
}

// -----------------------------------------------------------
// Attorney
// -----------------------------------------------------------
acf_add_local_field_group( [
	'key'    => 'group_gsd_attorney',
	'title'  => 'Attorney Details',
	'fields' => [

		[ 'key' => 'field_gsd_att_tab_basic', 'label' => 'Basic Info', 'type' => 'tab' ],
		[ 'key' => 'field_gsd_att_email', 'label' => 'Email Address', 'name' => 'email_address', 'type' => 'email' ],
		[ 'key' => 'field_gsd_att_phone', 'label' => 'Phone Number', 'name' => 'phone_number', 'type' => 'text' ],
		[ 'key' => 'field_gsd_att_headshot', 'label' => 'Headshot', 'name' => 'headshot', 'type' => 'image', 'return_format' => 'array' ],
		[ 'key' => 'field_gsd_att_license_year', 'label' => 'License Year', 'name' => 'license_year', 'type' => 'number' ],
		[ 'key' => 'field_gsd_att_bio', 'label' => 'Biography', 'name' => 'biography', 'type' => 'wysiwyg' ],
		[ 'key' => 'field_gsd_att_verified', 'label' => 'Date Last Verified', 'name' => 'date_last_verified', 'type' => 'date_picker', 'return_format' => 'Y-m-d' ],
		[ 'key' => 'field_gsd_att_website', 'label' => 'Personal/Firm Website URL', 'name' => 'website_url', 'type' => 'url' ],

		[ 'key' => 'field_gsd_att_tab_firm', 'label' => 'Firm & Office', 'type' => 'tab' ],
		[ 'key' => 'field_gsd_att_firm', 'label' => 'Firm', 'name' => 'firm', 'type' => 'post_object', 'post_type' => [ 'firm' ], 'return_format' => 'object' ],
		[ 'key' => 'field_gsd_att_primary_office', 'label' => 'Primary Office', 'name' => 'primary_office', 'type' => 'post_object', 'post_type' => [ 'office' ], 'return_format' => 'object' ],

		[ 'key' => 'field_gsd_att_tab_creds', 'label' => 'Credentials & Recognitions', 'type' => 'tab' ],
		[ 'key' => 'field_gsd_att_nj_cert', 'label' => 'NJ Matrimonial Certification', 'name' => 'nj_matrimonial_cert', 'type' => 'true_false' ],
		[ 'key' => 'field_gsd_att_aaml', 'label' => 'AAML Fellowship', 'name' => 'aaml_fellowship', 'type' => 'true_false' ],
		[ 'key' => 'field_gsd_att_av', 'label' => 'AV Preeminent', 'name' => 'av_preeminent', 'type' => 'true_false' ],
		[
			'key' => 'field_gsd_att_sl_group', 'label' => 'Super Lawyers', 'name' => 'super_lawyers', 'type' => 'group',
			'sub_fields' => [
				[ 'key' => 'field_gsd_sl_listed', 'label' => 'Listed', 'name' => 'listed', 'type' => 'true_false' ],
				[ 'key' => 'field_gsd_sl_years', 'label' => 'Years', 'name' => 'years', 'type' => 'text', 'instructions' => 'e.g. 2020-2026' ],
				[ 'key' => 'field_gsd_sl_url', 'label' => 'Profile URL', 'name' => 'url', 'type' => 'url' ],
			],
		],
		[
			'key' => 'field_gsd_att_bl_group', 'label' => 'Best Lawyers', 'name' => 'best_lawyers', 'type' => 'group',
			'sub_fields' => [
				[ 'key' => 'field_gsd_bl_listed', 'label' => 'Listed', 'name' => 'listed', 'type' => 'true_false' ],
				[ 'key' => 'field_gsd_bl_start_year', 'label' => 'Start Year', 'name' => 'start_year', 'type' => 'number' ],
				[ 'key' => 'field_gsd_bl_url', 'label' => 'Profile URL', 'name' => 'url', 'type' => 'url' ],
			],
		],
		[
			'key' => 'field_gsd_att_ch_group', 'label' => 'Chambers', 'name' => 'chambers', 'type' => 'group',
			'sub_fields' => [
				[ 'key' => 'field_gsd_ch_listed', 'label' => 'Listed', 'name' => 'listed', 'type' => 'true_false' ],
				[ 'key' => 'field_gsd_ch_tier', 'label' => 'Tier', 'name' => 'tier', 'type' => 'text' ],
				[ 'key' => 'field_gsd_ch_url', 'label' => 'Profile URL', 'name' => 'url', 'type' => 'url' ],
			],
		],
		[
			'key' => 'field_gsd_att_avvo_group', 'label' => 'Avvo', 'name' => 'avvo', 'type' => 'group',
			'sub_fields' => [
				[ 'key' => 'field_gsd_avvo_url', 'label' => 'Profile URL', 'name' => 'url', 'type' => 'url' ],
                [ 'key' => 'field_gsd_avvo_rating', 'label' => 'Rating', 'name' => 'rating', 'type' => 'number', 'step' => 0.1 ],
				[ 'key' => 'field_gsd_avvo_reviews', 'label' => 'Review Count', 'name' => 'review_number', 'type' => 'number' ],
				[ 'key' => 'field_gsd_avvo_stars', 'label' => 'Stars', 'name' => 'stars', 'type' => 'number', 'step' => 0.1 ],
			],
		],
		[
			'key' => 'field_gsd_att_other_directories', 'label' => 'Other Directory URLs', 'name' => 'other_directories', 'type' => 'group',
			'sub_fields' => [
				[ 'key' => 'field_gsd_findlaw_url', 'label' => 'FindLaw URL', 'name' => 'findlaw_url', 'type' => 'url' ],
				[ 'key' => 'field_gsd_martindale_url', 'label' => 'Martindale URL', 'name' => 'martindale_url', 'type' => 'url' ],
				[ 'key' => 'field_gsd_justia_url', 'label' => 'Justia URL', 'name' => 'justia_url', 'type' => 'url' ],
			],
		],

		[ 'key' => 'field_gsd_att_tab_education', 'label' => 'Education', 'type' => 'tab' ],
		[
			'key' => 'field_gsd_att_education', 'label' => 'Education', 'name' => 'education', 'type' => 'repeater', 'layout' => 'table',
			'sub_fields' => [
				[ 'key' => 'field_gsd_edu_school', 'label' => 'School', 'name' => 'school', 'type' => 'text' ],
				[ 'key' => 'field_gsd_edu_degree', 'label' => 'Degree', 'name' => 'degree', 'type' => 'text' ],
				[ 'key' => 'field_gsd_edu_grad_year', 'label' => 'Graduation Year', 'name' => 'graduation_year', 'type' => 'number' ],
			],
		],

		[ 'key' => 'field_gsd_att_tab_associations', 'label' => 'Associations', 'type' => 'tab' ],
		[
			'key' => 'field_gsd_att_associations', 'label' => 'Associations', 'name' => 'associations', 'type' => 'repeater', 'layout' => 'table',
			'sub_fields' => [
				[ 'key' => 'field_gsd_assoc_name', 'label' => 'Association', 'name' => 'association', 'type' => 'text' ],
				[ 'key' => 'field_gsd_assoc_role', 'label' => 'Role', 'name' => 'role', 'type' => 'text' ],
				[ 'key' => 'field_gsd_assoc_start', 'label' => 'Start Year', 'name' => 'start_year', 'type' => 'number' ],
				[ 'key' => 'field_gsd_assoc_end', 'label' => 'End Year', 'name' => 'end_year', 'type' => 'number' ],
			],
		],

	],
	'location' => [
		[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'attorney' ] ],
	],
] );

// -----------------------------------------------------------
// Firm
// -----------------------------------------------------------
acf_add_local_field_group( [
	'key'    => 'group_gsd_firm',
	'title'  => 'Firm Details',
	'fields' => [
		[ 'key' => 'field_gsd_firm_website', 'label' => 'Website URL', 'name' => 'website_url', 'type' => 'url' ],
	],
	'location' => [
		[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'firm' ] ],
	],
] );

// -----------------------------------------------------------
// Office
// -----------------------------------------------------------
acf_add_local_field_group( [
	'key'    => 'group_gsd_office',
	'title'  => 'Office Details',
	'fields' => [
		[ 'key' => 'field_gsd_office_firm', 'label' => 'Firm', 'name' => 'firm', 'type' => 'post_object', 'post_type' => [ 'firm' ], 'return_format' => 'object' ],
		[ 'key' => 'field_gsd_office_street', 'label' => 'Street', 'name' => 'street', 'type' => 'text' ],
		[ 'key' => 'field_gsd_office_suite', 'label' => 'Suite', 'name' => 'suite', 'type' => 'text' ],
		[ 'key' => 'field_gsd_office_city', 'label' => 'City', 'name' => 'city', 'type' => 'text' ],
		[ 'key' => 'field_gsd_office_state', 'label' => 'State', 'name' => 'state', 'type' => 'text', 'default_value' => 'NJ' ],
		[ 'key' => 'field_gsd_office_zip', 'label' => 'Zip', 'name' => 'zip', 'type' => 'text' ],
		[ 'key' => 'field_gsd_office_phone', 'label' => 'Phone', 'name' => 'phone', 'type' => 'text' ],
		[
			'key' => 'field_gsd_office_gbp_group', 'label' => 'Google Business Profile', 'name' => 'gbp', 'type' => 'group',
			'sub_fields' => [
				[ 'key' => 'field_gsd_gbp_url', 'label' => 'GBP URL', 'name' => 'url', 'type' => 'url' ],
                [ 'key' => 'field_gsd_gbp_rating', 'label' => 'Rating', 'name' => 'rating', 'type' => 'number', 'step' => 0.1 ],
				[ 'key' => 'field_gsd_gbp_reviews', 'label' => 'Review Count', 'name' => 'review_number', 'type' => 'number' ],
			],
		],
	],
	'location' => [
		[ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'office' ] ],
	],
] );

} );
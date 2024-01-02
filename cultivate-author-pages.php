<?php
/**
 * Plugin Name: Cultivate Author Pages
 * Plugin URI:  https://cultivatewp.com/our-plugins/cultivate-author-pages/
 * Description: Use the block editor to customize and curate your author pages. Note: this requires the Cultivate Category Pages plugin.
 * Author:      CultivateWP
 * Author URI:  https://cultivatewp.com/
 * Version:     1.0.0
 * Text Domain: cultivate-author-pages
 *
 * Cultivate Author Pages is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Cultivate Author Pages is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Cultivate Author Pages. If not, see <http://www.gnu.org/licenses/>.
 */

 namespace Cultivate\Author_Pages;

/**
 * Load ACF JSON
 */
function load_acf_json( $paths ) {
    $paths[] = plugin_dir_path( __FILE__ ) . '/acf-json';
    return $paths;
}
add_filter( 'acf/settings/load_json', __NAMESPACE__ . '\load_acf_json' );

/**
 * Author Landing Page
 */
function cwp_author_landing_page() {
	if ( ! is_author() || get_query_var( 'paged' ) ) {
		return;
	}

	$landing_id = get_user_meta( get_queried_object_id(), 'cwp_author_landing_page', true );
	if ( empty( $landing_id ) ) {
		return;
	}

	if ( function_exists( 'cultivate_category_pages' ) ) {
		cultivate_category_pages()->show( (int) $landing_id );
	}
}
add_action( 'tha_content_while_before', __NAMESPACE__ . '\cwp_author_landing_page', 20 );

/**
 * Author Archive Title
 */
add_filter(
    'cultivate_pro/landing/archive_title',
    function( $title ) {
        if ( is_author() ) {
            $title = 'All posts by ' . get_the_author_meta( 'first_name', get_queried_object_id() );
        }
        return $title;
    },
    30
);
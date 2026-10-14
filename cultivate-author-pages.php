<?php
/**
 * Plugin Name: Cultivate Author Pages
 * Plugin URI:  https://cultivatewp.com/our-plugins/cultivate-author-pages/
 * Description: Customize author archives with the block editor using Cultivate Category Pages, Cultivate Landing Pages, or Cultivate Pro.
 * Author:      CultivateWP
 * Author URI:  https://cultivatewp.com/
 * Version:     1.2.0
 * Requires PHP: 5.6.20
 * Update URI:  https://github.com/CultivateWP/Cultivate-Author-Pages/
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

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Register updates for dashboard, scheduled, CLI, and external management checks.
require_once __DIR__ . '/includes/updater/plugin-update-checker.php';
$update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/CultivateWP/Cultivate-Author-Pages/',
	__FILE__,
	'cultivate-author-pages'
);
$update_checker->setBranch( 'master' );
$update_checker->getVcsApi()->enableReleaseAssets( '/^cultivate-author-pages\.zip$/' );

/**
 * Load ACF JSON
 */
function load_acf_json( $paths ) {
    $paths[] = plugin_dir_path( __FILE__ ) . '/acf-json';
    return $paths;
}
add_filter( 'acf/settings/load_json', __NAMESPACE__ . '\load_acf_json' );

/**
 * Get the landing page assigned to the queried author.
 *
 * @return int Landing page ID, or zero when no valid page is assigned.
 */
function get_landing_page_id() {
	if ( ! is_author() ) {
		return 0;
	}

	$landing_id = (int) get_user_meta( get_queried_object_id(), 'cwp_author_landing_page', true );
	$landing_page = $landing_id > 0 ? get_post( $landing_id ) : null;
	if ( ! $landing_page || 'cultivate_landing' !== $landing_page->post_type || 'trash' === $landing_page->post_status ) {
		return 0;
	}

	return $landing_id;
}

/**
 * Add a frontend toolbar link for users who can edit the author landing page.
 *
 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
 */
function admin_bar_link_front( $wp_admin_bar ) {
	if ( is_admin() ) {
		return;
	}

	$landing_id = get_landing_page_id();
	if ( ! $landing_id || ! current_user_can( 'edit_post', $landing_id ) ) {
		return;
	}

	$edit_link = get_edit_post_link( $landing_id, 'raw' );
	if ( ! $edit_link ) {
		return;
	}

	$icon = file_get_contents( __DIR__ . '/assets/icons/cultivatewp-menu.svg' );
	$icon = str_replace( '<svg ', '<svg width="20" height="20" aria-hidden="true" focusable="false" ', $icon );
	$icon = '<span style="display: block; float: left; margin: 5px 5px 0 0;">' . $icon . '</span>';

	$wp_admin_bar->add_node( [
		'id'    => 'cultivate_author_pages',
		'title' => $icon . __( 'Edit Author Page', 'cultivate-author-pages' ),
		'href'  => $edit_link,
	] );
}
add_action( 'admin_bar_menu', __NAMESPACE__ . '\admin_bar_link_front', 90 );

/**
 * Author Landing Page
 */
function cwp_author_landing_page() {
	if ( ! is_author() || is_paged() ) {
		return;
	}

	$landing_id = get_landing_page_id();
	if ( empty( $landing_id ) ) {
		return;
	}

	if ( function_exists( 'cultivate_category_pages' ) ) {
		cultivate_category_pages()->show( (int) $landing_id );
	} elseif( function_exists( 'cultivate_landing_pages' ) ) {
		cultivate_landing_pages()->show( (int) $landing_id );
	} elseif( function_exists( 'cultivate_pro' ) ) {
		cultivate_pro()->landing_page->show( (int) $landing_id );
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
            $author_id = get_queried_object_id();
            $name = get_the_author_meta( 'first_name', $author_id );
            if ( empty( $name ) ) {
                $name = get_the_author_meta( 'display_name', $author_id );
            }
            /* translators: %s: Author's first name, or display name when no first name is set. */
            $title = sprintf( __( 'All posts by %s', 'cultivate-author-pages' ), $name );
        }
        return $title;
    },
    30
);

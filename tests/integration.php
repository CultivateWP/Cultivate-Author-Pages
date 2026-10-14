<?php
/**
 * Run against a local WordPress installation with the installed author plugin skipped:
 * wp --skip-plugins=cultivate-author-pages eval-file path/to/tests/integration.php
 *
 * Temporary users and posts are removed in the finally block.
 */

require_once dirname( __DIR__ ) . '/cultivate-author-pages.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';

$users = [];
$posts = [];
$original_user = get_current_user_id();
$original_query = $GLOBALS['wp_query'];
$check = function( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	echo 'PASS: ' . $message . PHP_EOL;
};
$node = function() {
	$bar = new WP_Admin_Bar();
	do_action( 'admin_bar_menu', $bar );
	return $bar->get_node( 'cultivate_author_pages' );
};

try {
	$check( post_type_exists( 'cultivate_landing' ), 'A landing page provider is active.' );
	$check( false !== has_filter( 'site_transient_update_plugins', [ $update_checker, 'injectUpdate' ] ), 'Updater registers WordPress update hooks in CLI requests.' );

	foreach ( [ 'administrator', 'subscriber' ] as $role ) {
		$id = wp_insert_user( [
			'user_login'   => 'author-pages-test-' . $role . '-' . wp_generate_password( 8, false ),
			'user_pass'    => wp_generate_password(),
			'role'         => $role,
			'display_name' => 'Toolbar Test Author',
		] );
		$check( ! is_wp_error( $id ), 'Create temporary ' . $role . '.' );
		$users[ $role ] = $id;
	}

	wp_set_current_user( $users['administrator'] );
	$landing_id = wp_insert_post( [
		'post_type'    => 'cultivate_landing',
		'post_status'  => 'publish',
		'post_title'   => 'Author toolbar integration fixture',
		'post_content' => '<p>Author fixture landing content.</p>',
	], true );
	$check( ! is_wp_error( $landing_id ), 'Create temporary landing page.' );
	$posts[] = $landing_id;
	$post_id = wp_insert_post( [
		'post_type'   => 'post',
		'post_status' => 'publish',
		'post_title'  => 'Author archive integration fixture',
		'post_author' => $users['subscriber'],
	], true );
	$check( ! is_wp_error( $post_id ), 'Create temporary author post.' );
	$posts[] = $post_id;
	update_user_meta( $users['subscriber'], 'cwp_author_landing_page', $landing_id );
	$query = function( $paged = 0 ) use ( $users ) {
		$GLOBALS['wp_query'] = new WP_Query( [ 'author' => $users['subscriber'], 'paged' => $paged ] );
	};
	$query();

	$link = $node();
	$check( $link && 'Edit Author Page' === trim( wp_strip_all_tags( $link->title ) ) && get_edit_post_link( $landing_id, 'raw' ) === $link->href, 'Editor receives a link to the assigned landing page.' );

	// Optional rendered toolbar preview for visual inspection; no authentication data is included.
	if ( ! empty( $args[0] ) ) {
		$bar = new WP_Admin_Bar();
		do_action( 'admin_bar_menu', $bar );
		ob_start();
		$bar->render();
		$markup = ob_get_clean();
		file_put_contents( $args[0], '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="' . esc_url( includes_url( 'css/dashicons.min.css' ) ) . '"><link rel="stylesheet" href="' . esc_url( includes_url( 'css/admin-bar.min.css' ) ) . '"></head><body>' . $markup . '</body></html>' );
	}

	foreach ( [ 0, $users['subscriber'] ] as $viewer ) {
		wp_set_current_user( $viewer );
		$check( ! $node(), 'Viewer without landing page edit permission has no link (user ' . $viewer . ').' );
	}
	wp_set_current_user( $users['administrator'] );

	$query( 2 );
	$check( (bool) $node(), 'Edit link remains available on paginated author archives.' );
	ob_start();
	Cultivate\Author_Pages\cwp_author_landing_page();
	$check( '' === ob_get_clean(), 'Paginated archives do not render landing content.' );

	$query( 1 );
	ob_start();
	Cultivate\Author_Pages\cwp_author_landing_page();
	$check( false !== strpos( ob_get_clean(), 'Author fixture landing content.' ), 'Explicit first-page query renders the assigned landing page.' );
	$check( 'All posts by Toolbar Test Author' === apply_filters( 'cultivate_pro/landing/archive_title', 'Original title' ), 'Archive heading falls back to display name.' );
	update_user_meta( $users['subscriber'], 'first_name', 'Sam' );
	$check( 'All posts by Sam' === apply_filters( 'cultivate_pro/landing/archive_title', 'Original title' ), 'Archive heading prefers first name when available.' );

	foreach ( [ '', PHP_INT_MAX, $post_id ] as $assignment ) {
		update_user_meta( $users['subscriber'], 'cwp_author_landing_page', $assignment );
		$check( ! $node(), 'No edit link for empty, missing, or non-landing assignment: ' . $assignment );
	}
	update_user_meta( $users['subscriber'], 'cwp_author_landing_page', $landing_id );
	wp_update_post( [ 'ID' => $landing_id, 'post_status' => 'trash' ] );
	$check( ! $node(), 'Trashed landing pages have no edit link.' );
	wp_update_post( [ 'ID' => $landing_id, 'post_status' => 'draft' ] );
	$check( (bool) $node(), 'Editors can open assigned draft landing pages.' );

	$GLOBALS['wp_query'] = new WP_Query( [ 'p' => $post_id ] );
	$check( ! $node(), 'Non-author requests have no author page link.' );
	$check( 'Original title' === apply_filters( 'cultivate_pro/landing/archive_title', 'Original title' ), 'Non-author archive titles remain unchanged.' );
} finally {
	wp_set_current_user( $original_user );
	$GLOBALS['wp_query'] = $original_query;
	wp_reset_postdata();
	foreach ( $posts as $id ) {
		wp_delete_post( $id, true );
	}
	foreach ( $users as $id ) {
		wp_delete_user( $id );
	}
}

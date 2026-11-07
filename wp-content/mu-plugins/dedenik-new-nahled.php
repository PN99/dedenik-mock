<?php
/**
 * Plugin Name: Dedeník – veřejný náhled /new
 * Description: Runtime settings for the anonymous preview using a SELECT-only database account.
 */
defined( 'ABSPATH' ) || exit;
if ( ! defined( 'DEDENIK_NEW_PREVIEW' ) || DEDENIK_NEW_PREVIEW !== true ) {
	return;
}

add_action( 'muplugins_loaded', static function () {
	global $wp_db_version;
	if ( ! $wp_db_version || (int) get_option( 'db_version' ) !== (int) $wp_db_version ) {
		http_response_code( 503 );
		header( 'Cache-Control: no-store' );
		header( 'Content-Type: text/plain; charset=UTF-8' );
		exit( 'Náhled momentálně není k dispozici.' );
	}
} );

foreach ( array( 'template', 'stylesheet' ) as $option ) {
	add_filter( 'pre_option_' . $option, static function () { return 'dedenik'; } );
}
add_filter( 'pre_option_active_plugins', '__return_empty_array' );
add_filter( 'pre_site_option_active_sitewide_plugins', '__return_empty_array' );
foreach ( array( 'theme_switched', 'blog_public', 'dedenik_ai_enabled' ) as $option ) {
	add_filter( 'pre_option_' . $option, '__return_zero' );
}

// Cookie and application-password validation callbacks may have their own side effects.
remove_all_filters( 'determine_current_user' );
add_filter( 'determine_current_user', '__return_zero', PHP_INT_MAX );
add_filter( 'send_auth_cookies', '__return_false' );
add_filter( 'comments_open', '__return_false' );
add_filter( 'pings_open', '__return_false' );
add_filter( 'comments_template_query_args', static function ( $args ) {
	unset( $args['include_unapproved'] );
	return $args;
} );
add_filter( 'post_password_required', static function ( $required, $post ) {
	return isset( $post->post_password ) && '' !== $post->post_password;
}, 10, 2 );

add_filter( 'pre_wp_mail', '__return_false' );
add_filter( 'pre_http_request', static function () {
	return new WP_Error( 'preview_offline', 'Odchozí požadavky jsou v náhledu vypnuté.' );
}, PHP_INT_MAX );
add_filter( 'pre_schedule_event', '__return_false' );
add_filter( 'pre_update_option', static function ( $new, $option, $old ) { return $old; }, PHP_INT_MAX, 3 );

// WordPress may call wp_mkdir_p() on 'path', even while serving a frontend request.
// Keep all proposed filesystem destinations under /new, and serve media from the live URL.
add_filter( 'upload_dir', static function ( $uploads ) {
	$subdir = preg_match( '~^/[0-9]{4}/[0-9]{2}$~D', $uploads['subdir'] ) ? $uploads['subdir'] : '';
	$uploads['subdir']  = $subdir;
	$uploads['basedir'] = WP_CONTENT_DIR . '/uploads';
	$uploads['path']    = $uploads['basedir'] . $subdir;
	$uploads['baseurl'] = 'https://www.dedenik.cz/wp-content/uploads';
	$uploads['url']     = $uploads['baseurl'] . $subdir;
	return $uploads;
} );

<?php
/** Short-lived deployment worker; never install permanently or expose without a token. */
if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || time() > __WORKER_EXPIRES__ ||
    ! hash_equals( '__WORKER_TOKEN_HASH__', hash( 'sha256', $_SERVER['HTTP_X_DEDENIK_INDEX_TOKEN'] ?? '' ) ) ) {
    http_response_code( 404 ); exit;
}
define( 'WP_USE_THEMES', false );
define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
require __DIR__ . '/wp-load.php';
header( 'Content-Type: application/json; charset=utf-8' );
header( 'Cache-Control: no-store' );
if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== 'svetdede.dedenik.cz' || DB_NAME === 'dedenikcz1' || ! function_exists( 'dedenik_index_install' ) ) {
    http_response_code( 409 ); echo '{"error":"Wrong target or index code unavailable"}'; exit;
}
global $wpdb;
$lock = $wpdb->get_var( "SELECT GET_LOCK('svetdede_search_index',0)" );
if ( '1' !== (string) $lock ) { http_response_code( 409 ); echo '{"error":"Index busy"}'; exit; }
try {
    $action = $_POST['action'] ?? '';
    if ( 'install' === $action ) {
        $result = dedenik_index_install();
        if ( ! is_wp_error( $result ) ) {
            update_option( 'dedenik_search_ready', false, false );
            $result = array( 'installed' => true );
        }
    } elseif ( 'batch' === $action && dedenik_index_installed() ) {
        $result = dedenik_index_batch( absint( $_POST['cursor'] ?? 0 ) );
    } elseif ( 'verify' === $action && dedenik_index_ready() ) {
        $tables = dedenik_index_tables();
        $expected = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} p WHERE " . dedenik_index_public_sql() );
        $indexed = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['documents']}" );
        $missing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$tables['documents']} d ON d.post_id=p.ID WHERE d.post_id IS NULL AND " . dedenik_index_public_sql() );
        $unsafe = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['documents']} d LEFT JOIN {$wpdb->posts} p ON p.ID=d.post_id WHERE p.ID IS NULL OR NOT (" . dedenik_index_public_sql() . ')' );
        $author = dedenik_index_author( 'vše od uživatele 814151' );
        $result = array( 'expected' => $expected, 'indexed' => $indexed, 'missing' => $missing, 'unsafe' => $unsafe,
            'credits' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['credits']}" ),
            'terms' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['terms']}" ),
            'author_814151_total' => $author['total'] ?? 0, 'author_814151_pages' => $author['pages'] ?? 0,
            'fulltext_matches' => count( dedenik_index_candidates( array( 'kočk', 'pes' ) ) ),
        );
        if ( ! $expected || $indexed !== $expected || $missing || $unsafe || ! $author || ! $result['fulltext_matches'] ) {
            $result = new WP_Error( 'verification', 'Index coverage verification failed.' );
        }
    } else { $result = new WP_Error( 'action', 'Unknown index action.' ); }
    if ( is_wp_error( $result ) ) { http_response_code( 500 ); echo wp_json_encode( array( 'error' => $result->get_error_message() ) ); }
    else { echo wp_json_encode( $result ); }
} finally { $wpdb->get_var( "SELECT RELEASE_LOCK('svetdede_search_index')" ); }

<?php
/** Public comment archives grouped by the displayed nickname, never private identity. */
defined( 'ABSPATH' ) || exit;

function dedenik_discussant_url( $name, $page = 1 ) {
    $args = array( 'diskutujici' => rawurlencode( dedenik_index_text( $name ) ) );
    if ( $page > 1 ) { $args['disk_strana'] = absint( $page ); }
    return add_query_arg( $args, home_url( '/' ) );
}
function dedenik_is_discussant() { return null !== get_query_var( 'diskutujici', null ); }
function dedenik_discussant_archive( $name, $page = 1 ) {
    $empty = array( 'status' => 404, 'name' => '', 'records' => array(), 'total' => 0, 'page' => 1, 'pages' => 0 );
    if ( ! is_string( $name ) || ! is_scalar( $page ) || mb_strlen( $name ) > 255 || ! preg_match( '/^[1-9][0-9]{0,5}$/D', (string) $page ) ) { return $empty; }
    $key = dedenik_index_key( dedenik_index_text( $name ) );
    if ( '' === $key ) { return $empty; }
    if ( ! dedenik_comments_ready() ) { $empty['status'] = 503; return $empty; }
    $found = dedenik_comments_search( '', array(), array( 'participant' => $key, 'discussion' => true ), (int) $page, 20 );
    if ( ! $found['total'] || (int) $page > $found['pages'] ) { return $empty; }
    $records = array(); $display = '';
    foreach ( $found['ids'] as $id ) {
        $record = dedenik_search_result( $id, '', true );
        // Recheck attribution too, in case a comment changed between the SQL read and rendering.
        if ( ! $record || dedenik_index_key( $record['author'] ) !== $key ) { continue; }
        $display = $display ?: $record['author'];
        $records[] = $record;
    }
    if ( ! $records ) { return $empty; }
    return array( 'status' => 200, 'name' => $display, 'records' => $records, 'total' => $found['total'], 'page' => $found['page'], 'pages' => $found['pages'] );
}
function dedenik_discussant_context() {
    static $context = null;
    if ( null === $context ) {
        $name = get_query_var( 'diskutujici', '' );
        if ( is_string( $name ) ) { $name = wp_unslash( $name ); }
        $page = get_query_var( 'disk_strana', '1' );
        $context = dedenik_discussant_archive( $name, $page );
    }
    return $context;
}
add_filter( 'query_vars', function ( $vars ) {
    $vars[] = 'diskutujici'; $vars[] = 'disk_strana'; return $vars;
} );
add_filter( 'redirect_canonical', function ( $url ) { return dedenik_is_discussant() ? false : $url; } );
add_filter( 'template_include', function ( $template ) {
    return dedenik_is_discussant() ? get_template_directory() . '/commenter-archive.php' : $template;
} );
add_filter( 'document_title_parts', function ( $parts ) {
    if ( dedenik_is_discussant() ) {
        $context = dedenik_discussant_context();
        $parts['title'] = $context['name'] ? 'Komentáře – ' . $context['name'] : 'Komentáře diskutujícího';
        unset( $parts['tagline'] );
    }
    return $parts;
} );
add_filter( 'get_comment_author_link', function ( $link, $author, $comment_id ) {
    if ( is_admin() || is_feed() || ! dedenik_comments_ready() ) { return $link; }
    $comment = get_comment( $comment_id );
    if ( ! dedenik_comments_public( $comment ) ) { return $link; }
    $name = dedenik_index_text( $comment->comment_author );
    if ( '' === $name ) { return $link; }
    return '<a class="commenter-archive-link" href="' . esc_url( dedenik_discussant_url( $name ) ) . '" aria-label="' . esc_attr( 'Zobrazit veřejné komentáře: ' . $name ) . '">' . esc_html( $name ) . '</a>';
}, 20, 3 );
add_action( 'wp_enqueue_scripts', function () {
    if ( is_singular() || dedenik_is_discussant() ) {
        wp_enqueue_style( 'dedenik-discussants', get_template_directory_uri() . '/assets/css/discussants.css', array( 'dedenik' ), wp_get_theme()->get( 'Version' ) );
    }
} );

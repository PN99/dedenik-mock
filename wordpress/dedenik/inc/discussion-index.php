<?php
/** Search only approved, public discussion. No emails, IPs or account identifiers. */
defined( 'ABSPATH' ) || exit;
function dedenik_comments_table() { global $wpdb; return $wpdb->prefix . 'dedenik_search_comments'; }
function dedenik_comments_installed() { return '1' === (string) get_option( 'dedenik_comments_schema', '' ); }
function dedenik_comments_ready() { return dedenik_comments_installed() && (bool) get_option( 'dedenik_comments_ready', false ); }
function dedenik_comments_public_sql() {
    return "c.comment_approved='1' AND c.comment_type IN ('','comment') AND " . dedenik_index_public_sql();
}
function dedenik_comments_public( $comment ) {
    return $comment && '1' === (string) $comment->comment_approved && in_array( $comment->comment_type, array( '', 'comment' ), true ) && dedenik_ai_public( get_post( $comment->comment_post_ID ) );
}
function dedenik_comments_install() {
    global $wpdb;
    $table = dedenik_comments_table();
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( "CREATE TABLE $table (
comment_id bigint(20) unsigned NOT NULL,
post_id bigint(20) unsigned NOT NULL,
parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
author_name varchar(255) NOT NULL,
author_key varchar(191) NOT NULL,
body longtext NOT NULL,
created_at datetime NOT NULL,
PRIMARY KEY  (comment_id),
KEY post_id (post_id),
KEY parent_id (parent_id),
KEY author_date (author_key,created_at),
KEY created_at (created_at),
FULLTEXT KEY comment_text (body)
) ENGINE=InnoDB $charset;" );
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table || ! $wpdb->get_var( "SHOW INDEX FROM $table WHERE Key_name='comment_text'" ) ) {
        return new WP_Error( 'comments_schema', 'Nepodařilo se vytvořit index diskusí.' );
    }
    update_option( 'dedenik_comments_schema', '1', false );
    return true;
}
/** One prepared bulk upsert per batch, selecting public fields only. */
function dedenik_comments_batch( $cursor = 0, $post_id = 0, $limit = 750 ) {
    global $wpdb;
    $table = dedenik_comments_table();
    $post_where = $post_id ? $wpdb->prepare( ' AND p.ID=%d', $post_id ) : '';
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT c.comment_ID,c.comment_post_ID,c.comment_parent,c.comment_author,c.comment_content,c.comment_date FROM {$wpdb->comments} c JOIN {$wpdb->posts} p ON p.ID=c.comment_post_ID WHERE c.comment_ID>%d AND " . dedenik_comments_public_sql() . "$post_where ORDER BY c.comment_ID LIMIT %d", absint( $cursor ), min( 1000, max( 1, (int) $limit ) ) ) );
    if ( $wpdb->last_error ) { return new WP_Error( 'comments_read', 'Čtení diskusí selhalo.' ); }
    $values = array(); $args = array();
    foreach ( $rows as $row ) {
        $name = mb_substr( dedenik_index_text( $row->comment_author ), 0, 255 );
        $values[] = '(%d,%d,%d,%s,%s,%s,%s)';
        array_push( $args, (int) $row->comment_ID, (int) $row->comment_post_ID, (int) $row->comment_parent, $name, dedenik_index_key( $name ), dedenik_index_text( $row->comment_content ), $row->comment_date );
        $cursor = (int) $row->comment_ID;
    }
    if ( $values ) {
        $sql = "INSERT INTO $table (comment_id,post_id,parent_id,author_name,author_key,body,created_at) VALUES " . implode( ',', $values ) . ' ON DUPLICATE KEY UPDATE post_id=VALUES(post_id),parent_id=VALUES(parent_id),author_name=VALUES(author_name),author_key=VALUES(author_key),body=VALUES(body),created_at=VALUES(created_at)';
        if ( false === $wpdb->query( $wpdb->prepare( $sql, ...$args ) ) ) { return new WP_Error( 'comments_write', 'Zápis diskusí selhal.' ); }
    } elseif ( ! $post_id ) {
        $sql = "DELETE i FROM $table i LEFT JOIN {$wpdb->comments} c ON c.comment_ID=i.comment_id LEFT JOIN {$wpdb->posts} p ON p.ID=c.comment_post_ID WHERE c.comment_ID IS NULL OR p.ID IS NULL OR NOT (" . dedenik_comments_public_sql() . ')';
        if ( false === $wpdb->query( $sql ) ) { return new WP_Error( 'comments_cleanup', 'Kontrola diskusí selhala.' ); }
        update_option( 'dedenik_comments_ready', true, false );
        dedenik_ai_invalidate();
    }
    return array( 'cursor' => $cursor, 'done' => ! $rows, 'processed' => count( $rows ) );
}
function dedenik_comments_changed( $comment_id ) {
    if ( ! dedenik_comments_installed() ) { return; }
    global $wpdb;
    $comment = get_comment( $comment_id );
    $table = dedenik_comments_table();
    if ( ! dedenik_comments_public( $comment ) ) { $wpdb->delete( $table, array( 'comment_id' => $comment_id ) ); }
    else {
        $name = mb_substr( dedenik_index_text( $comment->comment_author ), 0, 255 );
        $wpdb->replace( $table, array( 'comment_id' => $comment_id, 'post_id' => $comment->comment_post_ID, 'parent_id' => $comment->comment_parent,
            'author_name' => $name, 'author_key' => dedenik_index_key( $name ), 'body' => dedenik_index_text( $comment->comment_content ), 'created_at' => $comment->comment_date ) );
    }
    dedenik_ai_invalidate();
}
add_action( 'wp_insert_comment', 'dedenik_comments_changed' );
add_action( 'edit_comment', 'dedenik_comments_changed', 100 );
add_action( 'transition_comment_status', function ( $new, $old, $comment ) { dedenik_comments_changed( $comment->comment_ID ); }, 100, 3 );
add_action( 'deleted_comment', 'dedenik_comments_changed', 100 );
function dedenik_comments_post_changed( $post_id ) {
    if ( ! dedenik_comments_installed() || wp_is_post_revision( $post_id ) ) { return; }
    global $wpdb;
    $table = dedenik_comments_table();
    if ( ! dedenik_ai_public( get_post( $post_id ) ) ) { $wpdb->delete( $table, array( 'post_id' => $post_id ) ); }
    else {
        $expected = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_post_ID=%d AND comment_approved='1' AND comment_type IN ('','comment')", $post_id ) );
        $indexed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE post_id=%d", $post_id ) );
        if ( $expected !== $indexed ) {
            $cursor = 0;
            do {
                $batch = dedenik_comments_batch( $cursor, $post_id );
                if ( is_wp_error( $batch ) ) { break; }
                $cursor = $batch['cursor'];
            } while ( ! $batch['done'] );
        }
    }
    dedenik_ai_invalidate();
}
add_action( 'save_post', 'dedenik_comments_post_changed', 110 );
add_action( 'deleted_post', 'dedenik_comments_post_changed', 110 );
function dedenik_comments_intent( $query, $plan = array() ) {
    $key = dedenik_index_key( $query );
    $author = '';
    if ( preg_match( '/\b(?:mluvil[ai]?|psal[ai]?|rikal[ai]?|napsal[ai]?)\s+([\p{L}\p{N}_.-]+)/u', $key, $match ) ||
        preg_match( '/\b(?:komentare|komentar|prispevky|diskuse)\s+(?:od\s+)?([\p{L}\p{N}_.-]+)/u', $key, $match ) ) {
        if ( ! in_array( $match[1], array( 'o', 'pod', 'k', 'se', 'jsme', 'jsou' ), true ) ) { $author = $match[1]; }
    }
    if ( ! $author && ! empty( $plan['participant'] ) && is_string( $plan['participant'] ) ) {
        $candidate = dedenik_index_key( $plan['participant'] );
        if ( $candidate && preg_match( '/(?<![\p{L}\p{N}])' . preg_quote( $candidate, '/' ) . '(?![\p{L}\p{N}])/u', $key ) ) { $author = $candidate; }
    }
    $discussion = (bool) $author || true === ( $plan['discussion'] ?? false ) || (bool) preg_match( '/\b(?:diskus[ei]|komentar\w*|bavili|diskutovali|mluvil\w*|psal\w*|rikal\w*)\b/u', $key );
    $dates = array();
    foreach ( array( 'date_from', 'date_to' ) as $field ) {
        $date = $plan[ $field ] ?? '';
        if ( is_string( $date ) && preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) { $dates[ $field ] = $date; }
    }
    return array_merge( array( 'participant' => $author, 'discussion' => $discussion ), $dates );
}
function dedenik_comments_search( $query, $terms, $intent, $page = 1, $per_page = 12 ) {
    if ( ! dedenik_comments_ready() ) { return null; }
    global $wpdb;
    $table = dedenik_comments_table();
    $where = array( dedenik_comments_public_sql() ); $args = array();
    if ( $intent['participant'] ) { $where[] = 'i.author_key=%s'; $args[] = $intent['participant']; }
    foreach ( array( 'date_from' => '>=', 'date_to' => '<=' ) as $field => $operator ) {
        if ( isset( $intent[ $field ] ) ) { $where[] = "i.created_at $operator %s"; $args[] = $intent[ $field ] . ( 'date_to' === $field ? ' 23:59:59' : ' 00:00:00' ); }
    }
    $tokens = array();
    $skip = array( 'najdi','najdete','hledam','prosim','kdy','kde','jak','cem','cim','jsme','jsem','jsi','bavili','bavily','bavil','diskutovali','mluvila','mluvil','mluvili','psala','psal','napsala','napsal','rikala','rikal','diskuse','diskusi','komentare','komentar','prispevky','vse','vsechny','neco','uzivatele','autora','autorky','od' );
    foreach ( $terms as $term ) {
        preg_match_all( '/[\p{L}\p{N}]{3,60}/u', $term, $matches );
        foreach ( $matches[0] as $token ) {
            $normalized = dedenik_index_key( $token );
            if ( $normalized === $intent['participant'] || in_array( $normalized, $skip, true ) ) { continue; }
            // A bare kon* also matches konec, kontakt, koncert. Use horse inflections as whole words.
            if ( in_array( $normalized, array( 'kon','konich','koni','kone','konmi','kun','konim','konem' ), true ) ) {
                array_push( $tokens, 'kůň', 'koně', 'koni', 'koní', 'koním', 'koních', 'koňmi', 'koněm' );
                continue;
            }
            $tokens[] = $token . '*';
        }
    }
    $boolean = implode( ' ', array_slice( array_unique( $tokens ), 0, 30 ) );
    if ( $boolean ) { $where[] = 'MATCH(i.body) AGAINST (%s IN BOOLEAN MODE)'; $args[] = $boolean; }
    elseif ( ! $intent['participant'] && empty( $intent['date_from'] ) && empty( $intent['date_to'] ) ) { return array( 'ids' => array(), 'total' => 0, 'page' => 1, 'pages' => 0, 'mode' => 'discussion' ); }
    $join = "FROM $table i JOIN {$wpdb->comments} c ON c.comment_ID=i.comment_id JOIN {$wpdb->posts} p ON p.ID=c.comment_post_ID WHERE " . implode( ' AND ', $where );
    $total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) $join", ...$args ) : "SELECT COUNT(*) $join" );
    $per_page = min( 32, max( 1, (int) $per_page ) );
    $pages = (int) ceil( $total / $per_page );
    $page = min( max( 1, absint( $page ) ), max( 1, $pages ) );
    // Chronology makes discussion questions such as "when" inspectable and pageable.
    $parameters = array_merge( $args, array( $per_page, ( $page - 1 ) * $per_page ) );
    $ids = $wpdb->get_col( $wpdb->prepare( "SELECT i.comment_id $join ORDER BY i.created_at DESC,i.comment_id DESC LIMIT %d OFFSET %d", ...$parameters ) );
    return array( 'ids' => array_map( function ( $id ) { return -(int) $id; }, $ids ), 'total' => $total, 'page' => $page, 'pages' => $pages, 'mode' => 'discussion' );
}
function dedenik_comment_record( $comment, $query = '' ) {
    if ( ! dedenik_comments_public( $comment ) ) { return null; }
    $post = get_post( $comment->comment_post_ID );
    $text = dedenik_index_text( $comment->comment_content );
    $start = 0;
    preg_match_all( '/[\p{L}\p{N}]{3,60}/u', $query, $tokens );
    foreach ( $tokens[0] as $token ) {
        $pos = mb_stripos( $text, $token );
        if ( false !== $pos ) { $start = max( 0, $pos - 60 ); break; }
    }
    $excerpt = ( $start ? '…' : '' ) . wp_html_excerpt( mb_substr( $text, $start ), 900, '…' );
    $name = dedenik_index_text( $comment->comment_author );
    return array( 'id' => -(int) $comment->comment_ID, 'type' => 'comment', 'comment_id' => (int) $comment->comment_ID,
        'post_id' => (int) $post->ID, 'title' => $name . ' — diskuse: ' . dedenik_index_text( $post->post_title ),
        'author' => $name, 'date' => $comment->comment_date, 'excerpt' => $excerpt );
}
/** Validate every cached or model-supplied ID again before returning it. */
function dedenik_search_result( $id, $query = '', $with_url = false ) {
    if ( $id < 0 ) {
        $comment = get_comment( -$id );
        $record = dedenik_comment_record( $comment, $query );
        if ( $record && $with_url ) { $record['url'] = get_comment_link( $comment ); }
        return $record;
    }
    $post = get_post( $id );
    if ( ! dedenik_ai_public( $post ) ) { return null; }
    $record = dedenik_ai_record( $post );
    if ( $with_url ) { $record['url'] = get_permalink( $post ); }
    return $record;
}

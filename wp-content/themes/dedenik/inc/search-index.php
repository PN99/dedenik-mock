<?php
/** Public-content search index. Schema creation and initial rebuild are explicit operations. */
defined( 'ABSPATH' ) || exit;

function dedenik_index_tables() {
    global $wpdb;
    return array( 'documents' => $wpdb->prefix . 'dedenik_search_documents', 'credits' => $wpdb->prefix . 'dedenik_search_credits', 'terms' => $wpdb->prefix . 'dedenik_search_terms' );
}
function dedenik_index_installed() { return '1' === (string) get_option( 'dedenik_search_schema', '' ); }
function dedenik_index_ready() { return dedenik_index_installed() && (bool) get_option( 'dedenik_search_ready', false ); }
function dedenik_index_key( $name ) {
    return mb_substr( mb_strtolower( preg_replace( '/\s+/u', ' ', trim( remove_accents( $name ) ) ) ), 0, 191 );
}
function dedenik_index_text( $text ) {
    return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( strip_shortcodes( $text ), true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}
function dedenik_index_install() {
    global $wpdb;
    $tables = dedenik_index_tables();
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( "CREATE TABLE {$tables['documents']} (
post_id bigint(20) unsigned NOT NULL,
author_id bigint(20) unsigned NOT NULL DEFAULT 0,
title text NOT NULL,
body longtext NOT NULL,
author_names text NOT NULL,
taxonomy_names text NOT NULL,
published_at datetime NOT NULL,
indexed_at datetime NOT NULL,
PRIMARY KEY  (post_id),
KEY author_id (author_id),
KEY published_at (published_at),
FULLTEXT KEY search_text (title,body,author_names,taxonomy_names)
) ENGINE=InnoDB $charset;" );
    dbDelta( "CREATE TABLE {$tables['credits']} (
post_id bigint(20) unsigned NOT NULL,
name_key varchar(191) NOT NULL,
display_name varchar(255) NOT NULL,
source varchar(20) NOT NULL,
PRIMARY KEY  (post_id,name_key),
KEY name_key (name_key)
) ENGINE=InnoDB $charset;" );
    dbDelta( "CREATE TABLE {$tables['terms']} (
post_id bigint(20) unsigned NOT NULL,
term_taxonomy_id bigint(20) unsigned NOT NULL,
taxonomy varchar(32) NOT NULL,
name varchar(255) NOT NULL,
slug varchar(200) NOT NULL,
PRIMARY KEY  (post_id,term_taxonomy_id),
KEY term_taxonomy_id (term_taxonomy_id),
KEY taxonomy (taxonomy)
) ENGINE=InnoDB $charset;" );
    foreach ( $tables as $table ) {
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) {
            return new WP_Error( 'schema', 'Nepodařilo se vytvořit tabulky indexu.' );
        }
    }
    if ( ! $wpdb->get_var( "SHOW INDEX FROM {$tables['documents']} WHERE Key_name = 'search_text'" ) ) {
        return new WP_Error( 'schema', 'Chybí fulltextový index.' );
    }
    update_option( 'dedenik_search_schema', '1', false );
    return true;
}
/** Title attribution is a searchable credit, not a change to WordPress ownership. */
function dedenik_index_credits( $post ) {
    $credits = array();
    $user = get_userdata( $post->post_author );
    if ( $user && trim( $user->display_name ) ) { $credits[ dedenik_index_key( $user->display_name ) ] = array( $user->display_name, 'wordpress' ); }
    $title = dedenik_index_text( $post->post_title );
    if ( preg_match( '/[–—−-]\s*([^:]{1,100}):\s*/u', $title, $match ) ) {
        $name = trim( $match[1] );
        $key = dedenik_index_key( $name );
        if ( $key && ! isset( $credits[ $key ] ) ) { $credits[ $key ] = array( $name, 'title' ); }
    }
    foreach ( preg_split( '/\r?\n/', (string) get_post_meta( $post->ID, '_dedenik_search_authors', true ) ) as $name ) {
        $name = trim( sanitize_text_field( $name ) );
        if ( $name ) { $credits[ dedenik_index_key( $name ) ] = array( $name, 'editor' ); }
    }
    return $credits;
}
function dedenik_index_remove( $post_id ) {
    if ( ! dedenik_index_installed() ) { return; }
    global $wpdb;
    foreach ( dedenik_index_tables() as $table ) { $wpdb->delete( $table, array( 'post_id' => (int) $post_id ), array( '%d' ) ); }
}
function dedenik_index_post( $post_id ) {
    if ( ! dedenik_index_installed() || wp_is_post_revision( $post_id ) ) { return true; }
    global $wpdb;
    $post = get_post( $post_id );
    if ( ! dedenik_ai_public( $post ) ) { dedenik_index_remove( $post_id ); return true; }
    $tables = dedenik_index_tables();
    $credits = dedenik_index_credits( $post );
    $terms = wp_get_object_terms( $post_id, array( 'category', 'post_tag' ) );
    if ( is_wp_error( $terms ) ) { return $terms; }
    $wpdb->query( 'START TRANSACTION' );
    $ok = false !== $wpdb->replace( $tables['documents'], array(
        'post_id' => $post_id, 'author_id' => $post->post_author, 'title' => dedenik_index_text( $post->post_title ),
        'body' => dedenik_index_text( $post->post_content ), 'author_names' => implode( ' ', array_column( $credits, 0 ) ),
        'taxonomy_names' => implode( ' ', wp_list_pluck( $terms, 'name' ) ), 'published_at' => $post->post_date, 'indexed_at' => current_time( 'mysql', true ),
    ) );
    $ok = false !== $wpdb->delete( $tables['credits'], array( 'post_id' => $post_id ) ) && $ok;
    $ok = false !== $wpdb->delete( $tables['terms'], array( 'post_id' => $post_id ) ) && $ok;
    foreach ( $credits as $key => $credit ) {
        $ok = false !== $wpdb->insert( $tables['credits'], array( 'post_id' => $post_id, 'name_key' => $key, 'display_name' => $credit[0], 'source' => $credit[1] ) ) && $ok;
    }
    foreach ( $terms as $term ) {
        $ok = false !== $wpdb->insert( $tables['terms'], array( 'post_id' => $post_id, 'term_taxonomy_id' => $term->term_taxonomy_id, 'taxonomy' => $term->taxonomy, 'name' => $term->name, 'slug' => $term->slug ) ) && $ok;
    }
    $wpdb->query( $ok ? 'COMMIT' : 'ROLLBACK' );
    return $ok ? true : new WP_Error( 'index_write', 'Zápis do indexu selhal.' );
}
function dedenik_index_batch( $cursor = 0, $limit = 75 ) {
    global $wpdb;
    $ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE ID > %d AND post_type IN ('post','page') ORDER BY ID LIMIT %d", absint( $cursor ), min( 100, max( 1, absint( $limit ) ) ) ) );
    foreach ( $ids as $id ) {
        $result = dedenik_index_post( (int) $id );
        if ( is_wp_error( $result ) ) { return $result; }
        $cursor = (int) $id;
    }
    if ( ! $ids ) {
        $tables = dedenik_index_tables();
        // Purge stale rows after deletion or status changes during a rebuild.
        foreach ( $tables as $table ) {
            $wpdb->query( "DELETE i FROM $table i LEFT JOIN {$wpdb->posts} p ON p.ID=i.post_id WHERE p.ID IS NULL OR p.post_status <> 'publish' OR p.post_password <> '' OR p.post_type NOT IN ('post','page')" );
        }
        update_option( 'dedenik_search_ready', true, false );
        dedenik_ai_invalidate();
    }
    return array( 'cursor' => $cursor, 'done' => ! $ids );
}
/** Every query also checks current WordPress visibility; stale index rows cannot expose drafts. */
function dedenik_index_public_sql() {
    return "p.post_status = 'publish' AND p.post_password = '' AND p.post_type IN ('post','page')";
}
function dedenik_index_author( $query, $page = 1 ) {
    if ( ! dedenik_index_ready() ) { return null; }
    global $wpdb;
    $tables = dedenik_index_tables();
    $key = dedenik_index_key( $query );
    $names = array( $key );
    if ( preg_match( '/\bod\s+(?:(?:autora|autorky|uzivatele|uzivatelky)\s+)?(.+)$/u', $key, $match ) ) { $names[] = trim( $match[1], " \t\n\r\0\x0B.!?\"'“”„" ); }
    $marks = implode( ',', array_fill( 0, count( $names ), '%s' ) );
    $where = "FROM {$tables['credits']} c JOIN {$tables['documents']} d ON d.post_id=c.post_id JOIN {$wpdb->posts} p ON p.ID=c.post_id WHERE c.name_key IN ($marks) AND " . dedenik_index_public_sql();
    $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT c.post_id) $where", ...$names ) );
    if ( ! $total ) { return null; }
    $page = min( max( 1, absint( $page ) ), (int) ceil( $total / 12 ) );
    $args = array_merge( $names, array( ( $page - 1 ) * 12 ) );
    $ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT c.post_id,d.published_at $where ORDER BY d.published_at DESC,c.post_id DESC LIMIT 12 OFFSET %d", ...$args ) );
    return array( 'ids' => array_map( 'intval', $ids ), 'total' => $total, 'page' => $page, 'pages' => (int) ceil( $total / 12 ), 'mode' => 'author' );
}
function dedenik_index_candidates( $terms, $limit = 32 ) {
    if ( ! dedenik_index_ready() ) { return null; }
    global $wpdb;
    $tables = dedenik_index_tables();
    $tokens = array();
    foreach ( $terms as $term ) {
        preg_match_all( '/[\p{L}\p{N}]{3,60}/u', $term, $matches );
        foreach ( $matches[0] as $token ) {
            if ( in_array( dedenik_index_key( $token ), array( 'najdi', 'najdete', 'vyhledej', 'vyhledejte', 'hledam', 'hledat', 'prosim', 'neco', 'vse', 'vsechny', 'clanek', 'clanky', 'uzivatele', 'autora', 'autorky' ), true ) ) { continue; }
            $tokens[] = $token . '*';
        }
    }
    $boolean = implode( ' ', array_slice( array_unique( $tokens ), 0, 40 ) );
    if ( ! $boolean ) { return array(); }
    $match = 'MATCH(d.title,d.body,d.author_names,d.taxonomy_names) AGAINST (%s IN BOOLEAN MODE)';
    $sql = "SELECT d.post_id,$match AS relevance FROM {$tables['documents']} d JOIN {$wpdb->posts} p ON p.ID=d.post_id WHERE " . dedenik_index_public_sql() . " AND $match ORDER BY relevance DESC,d.published_at DESC,d.post_id DESC LIMIT %d";
    return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( $sql, $boolean, $boolean, min( 64, max( 1, (int) $limit ) ) ) ) );
}
function dedenik_index_changed( $post_id ) {
    dedenik_index_post( $post_id );
    dedenik_ai_invalidate();
}
add_action( 'save_post', 'dedenik_index_changed', 100 );
add_action( 'deleted_post', 'dedenik_index_remove' );
add_action( 'set_object_terms', function ( $id, $terms, $tt_ids, $taxonomy ) {
    if ( in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) { dedenik_index_changed( $id ); }
}, 100, 4 );
add_action( 'edited_term', function ( $term_id, $tt_id ) {
    if ( ! dedenik_index_installed() ) { return; }
    global $wpdb;
    foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=%d", $tt_id ) ) as $id ) { dedenik_index_changed( $id ); }
}, 100, 2 );
add_action( 'profile_update', function ( $user_id ) {
    if ( ! dedenik_index_installed() ) { return; }
    global $wpdb;
    foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author=%d AND post_type IN ('post','page')", $user_id ) ) as $id ) { dedenik_index_changed( $id ); }
}, 100 );
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
    add_action( $hook, function ( $meta_id, $post_id, $key ) {
        if ( '_dedenik_search_authors' === $key ) { dedenik_index_changed( $post_id ); }
    }, 100, 3 );
}
add_action( 'delete_term', function ( $term_id, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
    if ( in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
        foreach ( $object_ids as $id ) { dedenik_index_changed( $id ); }
    }
}, 100, 5 );
add_action( 'add_meta_boxes', function () {
    add_meta_box( 'dedenik-search-authors', 'Autoři pro hledání', function ( $post ) {
        wp_nonce_field( 'dedenik_search_authors', 'dedenik_search_authors_nonce' );
        echo '<p>Doplňující jména nebo přezdívky autorů, každé na samostatný řádek. Autor WordPressu a jméno v titulku před dvojtečkou se indexují automaticky.</p><textarea class="widefat" name="dedenik_search_authors" rows="3">' . esc_textarea( get_post_meta( $post->ID, '_dedenik_search_authors', true ) ) . '</textarea>';
    }, array( 'post', 'page' ) );
} );
add_action( 'save_post', function ( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || empty( $_POST['dedenik_search_authors_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dedenik_search_authors_nonce'] ) ), 'dedenik_search_authors' ) ) { return; }
    update_post_meta( $post_id, '_dedenik_search_authors', sanitize_textarea_field( wp_unslash( $_POST['dedenik_search_authors'] ?? '' ) ) );
}, 20 );

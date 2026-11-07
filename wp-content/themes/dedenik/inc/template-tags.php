<?php
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/world.php';
function dedenik_widget_area( $id ) {
    if ( is_active_sidebar( $id ) ) {
        echo '<div class="widget-area widget-area-' . esc_attr( $id ) . '">';
        dynamic_sidebar( $id );
        echo '</div>';
    }
}
function dedenik_menu_args( $location ) {
    $args = array( 'theme_location' => $location, 'container' => false, 'menu_class' => 'menu', 'fallback_cb' => false, 'depth' => 3 );
    // Read-only fallback to Frontier assignments; no rewriting theme mods.
    if ( ! has_nav_menu( $location ) && 'footer' !== $location ) {
        $old = get_option( 'theme_mods_frontier', array() );
        $id = isset( $old['nav_menu_locations'][ $location ] ) ? absint( $old['nav_menu_locations'][ $location ] ) : 0;
        if ( $id && wp_get_nav_menu_object( $id ) ) { $args['menu'] = $id; }
    }
    return $args;
}
function dedenik_posts_url() {
    $page = absint( get_option( 'page_for_posts' ) );
    return $page ? get_permalink( $page ) : home_url( '/' );
}
function dedenik_primary_category() {
    $categories = get_the_category();
    if ( ! $categories ) { return; }
    $primary = absint( get_post_meta( get_the_ID(), '_yoast_wpseo_primary_category', true ) );
    $category = $categories[0];
    foreach ( $categories as $candidate ) { if ( $candidate->term_id === $primary ) { $category = $candidate; break; } }
    $url = get_category_link( $category );
    if ( ! is_wp_error( $url ) ) { echo '<a class="kicker" href="' . esc_url( $url ) . '">' . esc_html( $category->name ) . '</a>'; }
}
function dedenik_metadata() {
    echo '<div class="post-meta">';
    echo '<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a>';
    echo '<time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date() ) . '</time>';
    if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
        echo '<a href="' . esc_url( get_comments_link() ) . '">';
        printf( esc_html__( 'Diskuse (%s)', 'dedenik' ), esc_html( number_format_i18n( get_comments_number() ) ) );
        echo '</a>';
    }
    echo '</div>';
}
function dedenik_card_image() {
    if ( has_post_thumbnail() && ! post_password_required() ) {
        the_post_thumbnail( 'medium_large', array( 'class' => 'card-image', 'alt' => '', 'loading' => 'lazy' ) );
    } else {
        echo '<div class="card-image card-placeholder" aria-hidden="true"><span>D</span></div>';
    }
}
function dedenik_chat_category() {
    $configured = absint( get_theme_mod( 'dedenik_chat_category', 0 ) );
    if ( $configured ) { $term = get_term( $configured, 'category' ); }
    else { $term = get_term_by( 'slug', 'chatnik', 'category' ); if ( ! $term ) { $term = get_term_by( 'name', 'Chatník', 'category' ); } }
    return ( $term && ! is_wp_error( $term ) ) ? $term : false;
}

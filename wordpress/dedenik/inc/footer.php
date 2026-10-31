<?php
defined( 'ABSPATH' ) || exit;

/** Only link to existing public pages in the automatic footer fallback. */
function dedenik_footer_page( $slugs ) {
    foreach ( $slugs as $slug ) {
        $page = get_page_by_path( $slug );
        if ( $page && 'publish' === $page->post_status && ! $page->post_password ) { return get_permalink( $page ); }
    }
    return '';
}

/** Decorative Spotify mark; the adjacent text supplies the accessible name. */
function dedenik_footer_spotify_icon() {
    return '<svg class="spotify-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M5.8 9.1c4.1-1.2 8.7-.9 12.4 1.2M6.6 12.4c3.4-1 7.2-.7 10.3 1M7.4 15.5c2.8-.8 5.8-.5 8.3.8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
}

/** Apply the same podcast label to an explicitly assigned footer menu. */
function dedenik_footer_is_podcast_item( $item, $args ) {
    if ( 'footer-world' !== ( $args->theme_location ?? '' ) ) { return false; }
    foreach ( dedenik_world_items() as $world_item ) {
        if ( 'podcast' === $world_item['key'] && untrailingslashit( $world_item['url'] ) === untrailingslashit( $item->url ) ) { return true; }
    }
    return false;
}
function dedenik_footer_menu_title( $title, $item, $args ) {
    return dedenik_footer_is_podcast_item( $item, $args ) ? dedenik_footer_spotify_icon() . '<span>' . esc_html__( 'Podcast', 'dedenik' ) . '</span>' : $title;
}
add_filter( 'nav_menu_item_title', 'dedenik_footer_menu_title', 10, 3 );
function dedenik_footer_menu_attributes( $attributes, $item, $args ) {
    if ( dedenik_footer_is_podcast_item( $item, $args ) ) { $attributes['class'] = trim( ( $attributes['class'] ?? '' ) . ' footer-podcast-link' ); }
    return $attributes;
}
add_filter( 'nav_menu_link_attributes', 'dedenik_footer_menu_attributes', 10, 3 );

function dedenik_footer_links( $location ) {
    if ( has_nav_menu( $location ) ) {
        wp_nav_menu( array( 'theme_location' => $location, 'container' => false, 'menu_class' => 'footer-links', 'fallback_cb' => false, 'depth' => 1 ) );
        return;
    }
    $links = array();
    if ( 'footer' === $location ) {
        $links = array(
            array( __( 'Články', 'dedenik' ), dedenik_posts_url() ),
            array( __( 'Témata', 'dedenik' ), home_url( '/#temata' ) ),
            array( __( 'Autoři', 'dedenik' ), home_url( '/#autori' ) ),
            array( __( 'Archiv', 'dedenik' ), home_url( '/#archiv' ) ),
        );
    } elseif ( 'footer-community' === $location ) {
        $chat = dedenik_chat_category();
        $links = array(
            array( __( 'Chatník', 'dedenik' ), $chat ? get_category_link( $chat ) : '' ),
            array( __( 'Jak se přidat', 'dedenik' ), dedenik_footer_page( array( 'jak-se-pridat', 'jak-napsat-clanek-na-zviretnik' ) ) ),
            array( __( 'Pravidla', 'dedenik' ), dedenik_footer_page( array( 'pravidla', 'pravidla-dedeniku' ) ) ),
            array( __( 'Kontakt', 'dedenik' ), dedenik_footer_page( array( 'kontakt', 'o-nas' ) ) ),
        );
    } elseif ( 'footer-world' === $location ) {
        foreach ( dedenik_world_items() as $item ) {
            $podcast = 'podcast' === $item['key'];
            $links[] = array( $podcast ? __( 'Podcast', 'dedenik' ) : $item['label'], $item['url'], $podcast );
        }
        if ( ! $links ) { $links[] = array( __( 'Knihy', 'dedenik' ), 'https://alzbetaadrak.cz/' ); }
        $links[] = array( __( 'Další projekty', 'dedenik' ), dedenik_footer_page( array( 'nase-projekty' ) ) );
    }
    echo '<ul class="footer-links">';
    foreach ( $links as $item ) {
        if ( ! $item[1] || is_wp_error( $item[1] ) ) { continue; }
        $podcast = ! empty( $item[2] );
        echo '<li><a' . ( $podcast ? ' class="footer-podcast-link"' : '' ) . ' href="' . esc_url( $item[1] ) . '">' . ( $podcast ? dedenik_footer_spotify_icon() . '<span>' : '' ) . esc_html( $item[0] ) . ( $podcast ? '</span>' : '' ) . '</a></li>';
    }
    echo '</ul>';
}

function dedenik_footer_year( $value ) { return max( 1900, min( absint( $value ), (int) wp_date( 'Y' ) ) ); }
function dedenik_footer_customize( $customizer ) {
    $customizer->add_section( 'dedenik_footer', array( 'title' => __( 'Dedeník – patička', 'dedenik' ), 'priority' => 37, 'description' => __( 'Odkazy ve sloupcích upravujte přes Vzhled → Menu a přiřaďte je pozicím Patička – Rychlé odkazy, Komunita, Svět Dede a Spodní odkazy.', 'dedenik' ) ) );
    foreach ( array( 'quick' => 'Rychlé odkazy', 'community' => 'Komunita', 'world' => 'Svět Dede' ) as $key => $label ) {
        $customizer->add_setting( 'dedenik_footer_' . $key . '_title', array( 'default' => $label, 'sanitize_callback' => 'sanitize_text_field' ) );
        $customizer->add_control( 'dedenik_footer_' . $key . '_title', array( 'label' => __( 'Nadpis sloupce', 'dedenik' ) . ' – ' . $label, 'section' => 'dedenik_footer', 'type' => 'text' ) );
    }
    $customizer->add_setting( 'dedenik_footer_description', array( 'default' => 'Deník o zvířatech, lidech a životě.', 'sanitize_callback' => 'sanitize_text_field' ) );
    $customizer->add_control( 'dedenik_footer_description', array( 'label' => __( 'Text pod logem', 'dedenik' ), 'section' => 'dedenik_footer', 'type' => 'textarea' ) );
    $customizer->add_setting( 'dedenik_footer_since', array( 'default' => 2010, 'sanitize_callback' => 'dedenik_footer_year' ) );
    $customizer->add_control( 'dedenik_footer_since', array( 'label' => __( 'Počáteční rok copyrightu', 'dedenik' ), 'section' => 'dedenik_footer', 'type' => 'number', 'input_attrs' => array( 'min' => 1900, 'max' => (int) wp_date( 'Y' ) ) ) );
    $customizer->add_setting( 'dedenik_footer_banner', array( 'default' => true, 'sanitize_callback' => 'wp_validate_boolean' ) );
    $customizer->add_control( 'dedenik_footer_banner', array( 'label' => __( 'Zobrazit pruh s krajinou a citátem', 'dedenik' ), 'section' => 'dedenik_footer', 'type' => 'checkbox' ) );
}
add_action( 'customize_register', 'dedenik_footer_customize' );

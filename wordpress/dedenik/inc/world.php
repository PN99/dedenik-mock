<?php
/** Editorial sections use native pages, posts and categories. */
defined( 'ABSPATH' ) || exit;

function dedenik_world_items() {
    $items = array();
    if ( ! get_theme_mod( 'dedenik_world_enabled', true ) ) { return $items; }
    $labels = array( 'podcast' => 'Podcasty', 'theatre' => 'Dedivadlo', 'books' => 'Knihy' );
    $order = explode( ',', get_theme_mod( 'dedenik_world_order', 'podcast,theatre,books' ) );
    if ( count( $order ) !== 3 || count( array_unique( $order ) ) !== 3 || array_diff( $order, array_keys( $labels ) ) ) { $order = array_keys( $labels ); }
    foreach ( $order as $key ) {
        $default_label = $labels[ $key ];
        $selected = absint( get_theme_mod( 'dedenik_world_' . $key, 0 ) );
        $page = $selected ? get_post( $selected ) : null;
        $public_page = $selected && $page && 'page' === $page->post_type && 'publish' === $page->post_status && ! $page->post_password;
        $default_urls = array( 'podcast' => 'https://open.spotify.com/show/4xeerT4CEz6vBbsb9YzGWM', 'books' => 'https://alzbetaadrak.cz/' );
        $external = get_theme_mod( 'dedenik_world_' . $key . '_url', '' );
        if ( ! $external && ! $selected ) { $external = $default_urls[ $key ] ?? ''; }
        $url = $external ? esc_url( $external, array( 'http', 'https' ) ) : ( $public_page ? get_permalink( $page ) : '' );
        if ( ! $url ) { continue; }
        $items[] = array(
            'key' => $key,
            'page' => $public_page ? $page : null,
            'url' => $url,
            'label' => get_theme_mod( 'dedenik_world_' . $key . '_label', $default_label ),
            'title' => get_theme_mod( 'dedenik_world_' . $key . '_title', '' ) ?: ( $public_page ? get_the_title( $page ) : $default_label ),
            'description' => get_theme_mod( 'dedenik_world_' . $key . '_description', '' ),
            'image' => absint( get_theme_mod( 'dedenik_world_' . $key . '_image', 0 ) ),
            'button' => get_theme_mod( 'dedenik_world_' . $key . '_button', 'podcast' === $key ? __( 'Poslechnout →', 'dedenik' ) : __( 'Objevit více →', 'dedenik' ) ),
        );
    }
    return $items;
}

/** Obtain only an explicit audio source or supported Spotify embed, never arbitrary HTML. */
function dedenik_episode_source( $content, $enclosure = '' ) {
    $find = function ( $blocks ) use ( &$find ) {
        foreach ( $blocks as $block ) {
            $url = isset( $block['attrs']['src'] ) ? $block['attrs']['src'] : '';
            if ( 'core/audio' === $block['blockName'] ) {
                // Older Audio blocks store src in their HTML rather than attributes.
                if ( ! $url && preg_match( '~\bsrc=["\x27]([^"\x27]+)~i', $block['innerHTML'] ?? '', $match ) ) {
                    $url = html_entity_decode( $match[1], ENT_QUOTES, 'UTF-8' );
                }
                $url = esc_url_raw( $url, array( 'http', 'https' ) );
                if ( $url ) { return array( 'type' => 'audio', 'url' => $url ); }
            }
            if ( 'core/embed' === $block['blockName'] ) {
                $url = $block['attrs']['url'] ?? '';
                if ( preg_match( '~^https://open\.spotify\.com/(?:intl-[a-z]{2}/)?(episode|show)/([A-Za-z0-9]+)(?:[/?#]|$)~', $url, $match ) ) {
                    return array( 'type' => 'spotify', 'url' => 'https://open.spotify.com/embed/' . $match[1] . '/' . $match[2] );
                }
            }
            $nested = $find( $block['innerBlocks'] ?? array() );
            if ( $nested ) { return $nested; }
        }
        return null;
    };
    $source = $find( parse_blocks( $content ) );
    if ( $source ) { return $source; }
    if ( preg_match( '~\[audio\b([^\]]*)\]~i', $content, $match ) ) {
        $attributes = shortcode_parse_atts( $match[1] );
        foreach ( array( 'src', 'mp3', 'm4a', 'ogg', 'wav' ) as $key ) {
            $url = esc_url_raw( $attributes[ $key ] ?? '', array( 'http', 'https' ) );
            if ( $url ) { return array( 'type' => 'audio', 'url' => $url ); }
        }
    }
    // Native podcast enclosure: URL, length, MIME type on separate lines.
    $parts = preg_split( '/\r?\n/', $enclosure );
    $url = esc_url_raw( $parts[0] ?? '', array( 'http', 'https' ) );
    return $url && isset( $parts[2] ) && 0 === strpos( trim( $parts[2] ), 'audio/' ) ? array( 'type' => 'audio', 'url' => $url ) : null;
}

function dedenik_episode_player( $source ) {
    if ( ! $source ) { return; }
    if ( 'spotify' === $source['type'] ) {
        printf( '<iframe class="podcast-player" title="%s" src="%s" height="232" loading="lazy" allow="encrypted-media; fullscreen; picture-in-picture"></iframe>', esc_attr__( 'Přehrávač podcastu Spotify', 'dedenik' ), esc_url( $source['url'] ) );
    } else {
        printf( '<audio class="podcast-player" controls preload="none" src="%s">%s <a href="%s">%s</a></audio>', esc_url( $source['url'] ), esc_html__( 'Váš prohlížeč nepodporuje přehrávání zvuku.', 'dedenik' ), esc_url( $source['url'] ), esc_html__( 'Otevřít zvukový soubor', 'dedenik' ) );
    }
}

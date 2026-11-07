<?php
defined( 'ABSPATH' ) || exit;

function dedenik_world_page_box() {
    add_meta_box( 'dedenik-world', __( 'Svět Dede – obsah rubriky', 'dedenik' ), 'dedenik_world_page_fields', 'page', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'dedenik_world_page_box' );

function dedenik_world_page_fields( $post ) {
    wp_nonce_field( 'dedenik_world_page', 'dedenik_world_nonce' );
    echo '<p>' . esc_html__( 'Pro tuto stránku vyberte šablonu „Svět Dede – rubrika“. Úvodní text, program představení, YouTube videa a další obsah upravujte přímo v editoru stránky. Nové díly přidávejte jako příspěvky do zvolené rubriky.', 'dedenik' ) . '</p>';
    echo '<p><label for="dedenik-world-kind">' . esc_html__( 'Typ stránky', 'dedenik' ) . '</label> <select id="dedenik-world-kind" name="dedenik_world_kind">';
    foreach ( array( 'general' => 'Svět Dede / jiná rubrika', 'podcast' => 'Podcasty', 'theatre' => 'Divadlo' ) as $value => $label ) {
        printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( get_post_meta( $post->ID, '_dedenik_world_kind', true ), $value, false ), esc_html( $label ) );
    }
    echo '</select></p><p><label for="dedenik-world-category">' . esc_html__( 'Rubrika příspěvků', 'dedenik' ) . '</label> ';
    wp_dropdown_categories( array( 'name' => 'dedenik_world_category', 'id' => 'dedenik-world-category', 'hide_empty' => false, 'hierarchical' => true, 'show_option_none' => __( 'Bez výpisu příspěvků', 'dedenik' ), 'option_none_value' => 0, 'selected' => absint( get_post_meta( $post->ID, '_dedenik_world_category', true ) ) ) );
    echo '</p>';
    foreach ( array( 'spotify' => 'Spotify', 'apple' => 'Apple Podcasts', 'youtube' => 'YouTube divadla' ) as $key => $label ) {
        printf( '<p><label for="dedenik-world-%1$s">%2$s</label><br><input class="widefat" type="url" id="dedenik-world-%1$s" name="dedenik_world_%1$s" value="%3$s" placeholder="https://"></p>', esc_attr( $key ), esc_html( $label ), esc_attr( get_post_meta( $post->ID, '_dedenik_world_' . $key, true ) ) );
    }
    echo '<p>' . esc_html__( 'Odkazy můžete doplnit později. Prázdná pole se na webu nezobrazí. Podcastový díl může obsahovat blok Zvuk, vložený odkaz Spotify nebo klasický audio shortcode. Nejnovější veřejný díl se objeví nahoře i s přehrávačem, pokud má podporovaný zdroj.', 'dedenik' ) . '</p>';
}

function dedenik_save_world_page( $post_id ) {
    if ( ! isset( $_POST['dedenik_world_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dedenik_world_nonce'] ) ), 'dedenik_world_page' ) ) { return; }
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
    $kind = sanitize_key( wp_unslash( $_POST['dedenik_world_kind'] ?? 'general' ) );
    update_post_meta( $post_id, '_dedenik_world_kind', in_array( $kind, array( 'general', 'podcast', 'theatre' ), true ) ? $kind : 'general' );
    $category = absint( $_POST['dedenik_world_category'] ?? 0 );
    update_post_meta( $post_id, '_dedenik_world_category', $category && term_exists( $category, 'category' ) ? $category : 0 );
    foreach ( array( 'spotify', 'apple', 'youtube' ) as $key ) {
        update_post_meta( $post_id, '_dedenik_world_' . $key, esc_url_raw( wp_unslash( $_POST['dedenik_world_' . $key ] ?? '' ), array( 'https', 'http' ) ) );
    }
}
add_action( 'save_post_page', 'dedenik_save_world_page' );

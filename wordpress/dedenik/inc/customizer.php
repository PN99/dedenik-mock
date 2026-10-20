<?php
defined( 'ABSPATH' ) || exit;
function dedenik_customize( $customizer ) {
    $customizer->add_section( 'dedenik_home', array( 'title' => __( 'Dedeník – úvodní stránka', 'dedenik' ), 'priority' => 35 ) );
    $choices = array( 0 => __( 'Automaticky podle rubriky Chatník', 'dedenik' ) );
    foreach ( get_categories( array( 'hide_empty' => false ) ) as $category ) { $choices[ $category->term_id ] = $category->name; }
    $customizer->add_setting( 'dedenik_chat_category', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $customizer->add_control( 'dedenik_chat_category', array( 'label' => __( 'Rubrika Chatníku', 'dedenik' ), 'section' => 'dedenik_home', 'type' => 'select', 'choices' => $choices ) );
    $customizer->add_setting( 'dedenik_hero_image', array( 'sanitize_callback' => 'absint' ) );
    $customizer->add_control( new WP_Customize_Media_Control( $customizer, 'dedenik_hero_image', array( 'label' => __( 'Výchozí obrázek úvodní sekce', 'dedenik' ), 'description' => __( 'Použije se, když nejnovější článek nemá úvodní obrázek.', 'dedenik' ), 'section' => 'dedenik_home', 'mime_type' => 'image' ) ) );
    $customizer->add_section( 'dedenik_world', array( 'title' => __( 'Dedeník – Svět Dede', 'dedenik' ), 'priority' => 36, 'description' => __( 'Podstránky a jejich obsah spravujte ve Stránkách. Pro seznam dílů a příspěvků použijte šablonu stránky „Svět Dede – rubrika“. Zde nastavíte karty na úvodní stránce.', 'dedenik' ) ) );
    $customizer->add_setting( 'dedenik_world_enabled', array( 'default' => true, 'sanitize_callback' => 'wp_validate_boolean' ) );
    $customizer->add_control( 'dedenik_world_enabled', array( 'label' => __( 'Zobrazovat sekci Svět Dede', 'dedenik' ), 'section' => 'dedenik_world', 'type' => 'checkbox' ) );
    $customizer->add_setting( 'dedenik_world_heading', array( 'default' => 'Ze Světa Dede', 'sanitize_callback' => 'sanitize_text_field' ) );
    $customizer->add_control( 'dedenik_world_heading', array( 'label' => __( 'Nadpis sekce', 'dedenik' ), 'section' => 'dedenik_world', 'type' => 'text' ) );
    $customizer->add_setting( 'dedenik_world_landing', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $customizer->add_control( 'dedenik_world_landing', array( 'label' => __( 'Hlavní stránka Světa Dede (volitelné)', 'dedenik' ), 'section' => 'dedenik_world', 'type' => 'dropdown-pages' ) );
    $orders = array( 'podcast,theatre,books' => 'Podcasty, Divadlo, Knihy', 'podcast,books,theatre' => 'Podcasty, Knihy, Divadlo', 'theatre,podcast,books' => 'Divadlo, Podcasty, Knihy', 'theatre,books,podcast' => 'Divadlo, Knihy, Podcasty', 'books,podcast,theatre' => 'Knihy, Podcasty, Divadlo', 'books,theatre,podcast' => 'Knihy, Divadlo, Podcasty' );
    $customizer->add_setting( 'dedenik_world_order', array( 'default' => 'podcast,theatre,books', 'sanitize_callback' => 'dedenik_world_order' ) );
    $customizer->add_control( 'dedenik_world_order', array( 'label' => __( 'Pořadí karet', 'dedenik' ), 'section' => 'dedenik_world', 'type' => 'select', 'choices' => $orders ) );
    foreach ( array( 'podcast' => 'Podcasty', 'theatre' => 'Divadlo', 'books' => 'Knihy' ) as $key => $label ) {
        $setting = 'dedenik_world_' . $key;
        $customizer->add_setting( $setting, array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $customizer->add_control( $setting, array( 'label' => $label . ' – ' . __( 'stránka', 'dedenik' ), 'section' => 'dedenik_world', 'type' => 'dropdown-pages', 'description' => __( 'Bez veřejné stránky nebo vyplněného odkazu se karta nezobrazí.', 'dedenik' ) ) );
        foreach ( array( 'label' => 'Označení karty', 'title' => 'Titulek karty', 'description' => 'Popis karty', 'button' => 'Text tlačítka', 'url' => 'Vlastní odkaz (má přednost před stránkou)' ) as $field => $field_label ) {
            $id = $setting . '_' . $field;
            $defaults = array( 'label' => $label, 'title' => '', 'description' => '', 'button' => 'Objevit více →', 'url' => '' );
            $customizer->add_setting( $id, array( 'default' => $defaults[ $field ], 'sanitize_callback' => 'url' === $field ? 'dedenik_world_url' : 'sanitize_text_field' ) );
            $customizer->add_control( $id, array( 'label' => $label . ' – ' . $field_label, 'section' => 'dedenik_world', 'type' => 'url' === $field ? 'url' : ( 'description' === $field ? 'textarea' : 'text' ), 'description' => 'books' === $key && 'url' === $field ? 'Pro knihy vložte https://alzbetaadrak.cz/' : '' ) );
        }
        $customizer->add_setting( $setting . '_image', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $customizer->add_control( new WP_Customize_Media_Control( $customizer, $setting . '_image', array( 'label' => $label . ' – obrázek karty', 'section' => 'dedenik_world', 'mime_type' => 'image', 'description' => __( 'Bez výběru se použije náhledový obrázek zvolené stránky.', 'dedenik' ) ) ) );
    }
}
add_action( 'customize_register', 'dedenik_customize' );
function dedenik_world_order( $value ) {
    $items = explode( ',', $value );
    return count( $items ) === 3 && count( array_unique( $items ) ) === 3 && ! array_diff( $items, array( 'podcast', 'theatre', 'books' ) ) ? $value : 'podcast,theatre,books';
}
function dedenik_world_url( $value ) { return esc_url_raw( $value, array( 'http', 'https' ) ); }

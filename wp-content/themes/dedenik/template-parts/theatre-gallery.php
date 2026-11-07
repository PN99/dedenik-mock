<?php
defined( 'ABSPATH' ) || exit;
?>
<section class="theatre-story" aria-label="<?php esc_attr_e( 'O divadelní hře Vánočnice', 'dedenik' ); ?>">
    <p><?php esc_html_e( 'Divadelní hra Vánočnice je autorská komedie ochotnického spolku Dedivadlo z Doubravice, která s humorem ukazuje, jak (ne)probíhá příprava na vánoční svátky a jak ji prožívají ženy.', 'dedenik' ); ?></p>
    <div class="theatre-gallery" aria-label="<?php esc_attr_e( 'Fotografie z Dedivadla', 'dedenik' ); ?>">
        <figure><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/dedivadlo-1.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Herci na jevišti při představení Dedivadla', 'dedenik' ); ?>" loading="lazy" decoding="async"></figure>
        <figure><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/dedivadlo-2.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Diváci tleskají po představení Dedivadla', 'dedenik' ); ?>" loading="lazy" decoding="async"></figure>
        <figure><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/dedivadlo-3.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Scéna z představení Dedivadla u stolu', 'dedenik' ); ?>" loading="lazy" decoding="async"></figure>
    </div>
    <?php
    $category = get_term_by( 'slug', 'dedivadlo', 'category' );
    $category_url = $category && ! is_wp_error( $category ) ? get_category_link( $category ) : '';
    if ( $category_url && ! is_wp_error( $category_url ) ) : ?>
        <p><a class="button" href="<?php echo esc_url( $category_url ); ?>"><?php esc_html_e( 'Příspěvky z rubriky Dedivadlo →', 'dedenik' ); ?></a></p>
    <?php endif; ?>
</section>

<?php defined( 'ABSPATH' ) || exit; ?>
<dialog class="theatre-dialog" id="dedivadlo-dialog" aria-labelledby="dedivadlo-dialog-title">
    <button class="theatre-dialog-close" type="button" aria-label="<?php esc_attr_e( 'Zavřít', 'dedenik' ); ?>" autofocus>×</button>
    <p class="kicker"><?php esc_html_e( 'Dedivadlo', 'dedenik' ); ?></p>
    <h2 id="dedivadlo-dialog-title"><?php esc_html_e( 'Program Dedivadla', 'dedenik' ); ?></h2>
    <div class="theatre-program-list theatre-program-detail">
        <?php foreach ( dedenik_theatre_program( true ) as $performance ) : ?>
            <p><strong><?php echo esc_html( $performance['date'] ); ?></strong> · <?php echo esc_html( $performance['title'] ); ?> · <?php echo esc_html( $performance['time'] ); ?> · <?php echo esc_html( $performance['location'] ); ?></p>
        <?php endforeach; ?>
    </div>
    <?php get_template_part( 'template-parts/theatre-gallery' ); ?>
</dialog>

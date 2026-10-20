<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap"><?php dedenik_widget_area( 'widgets_after_content' ); ?></div>
<div class="wrap"><?php dedenik_widget_area( 'widgets_body' ); ?></div>
</main>
<?php if ( get_theme_mod( 'dedenik_footer_banner', true ) ) : ?>
<div class="footer-quote"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/footer-landscape.webp' ); ?>" width="1024" height="72" loading="lazy" alt="<?php esc_attr_e( '„Zvířata, lidé, příroda – všechno je propojené.“ Dedeník od roku 2010.', 'dedenik' ); ?>"><p class="footer-quote-mobile"><?php esc_html_e( '„Zvířata, lidé, příroda – všechno je propojené.“', 'dedenik' ); ?></p></div>
<?php endif; ?>
<footer class="site-footer">
    <div class="wrap">
        <div class="footer-grid"><div class="footer-identity">
            <?php if ( has_custom_logo() ) { the_custom_logo(); } else { ?><a class="footer-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="205" height="58"></a><?php } ?>
            <p><?php echo esc_html( get_theme_mod( 'dedenik_footer_description', __( 'Deník o zvířatech, lidech a životě.', 'dedenik' ) ) ); ?></p>
        </div>
        <?php foreach ( array( 'quick' => array( 'Rychlé odkazy', 'footer' ), 'community' => array( 'Komunita', 'footer-community' ), 'world' => array( 'Svět Dede', 'footer-world' ) ) as $key => $column ) : ?>
            <nav class="footer-column" aria-labelledby="footer-<?php echo esc_attr( $key ); ?>-title"><h2 id="footer-<?php echo esc_attr( $key ); ?>-title"><?php echo esc_html( get_theme_mod( 'dedenik_footer_' . $key . '_title', $column[0] ) ); ?></h2><?php dedenik_footer_links( $column[1] ); ?></nav>
        <?php endforeach; ?>
        </div>
        <?php if ( is_active_sidebar( 'widgets_footer' ) ) : ?><div class="footer-widgets"><?php dedenik_widget_area( 'widgets_footer' ); ?></div><?php endif; ?>
        <div class="footer-bottom"><p class="copyright">© <?php $year = (int) wp_date( 'Y' ); $since = dedenik_footer_year( get_theme_mod( 'dedenik_footer_since', 2010 ) ); echo esc_html( ( $since < $year ? $since . '–' : '' ) . $year ); ?> <?php bloginfo( 'name' ); ?></p>
        <nav class="footer-legal" aria-label="<?php esc_attr_e( 'Právní informace a RSS', 'dedenik' ); ?>"><?php the_privacy_policy_link(); ?><?php if ( has_nav_menu( 'footer-legal' ) ) { wp_nav_menu( array( 'theme_location' => 'footer-legal', 'container' => false, 'menu_class' => 'footer-legal-links', 'depth' => 1, 'fallback_cb' => false ) ); } ?><a href="<?php echo esc_url( get_feed_link() ); ?>">RSS</a></nav></div>
    </div>
</footer>
<?php wp_footer(); ?>
</body></html>

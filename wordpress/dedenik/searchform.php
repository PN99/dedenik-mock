<?php defined( 'ABSPATH' ) || exit; ?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <label><span class="screen-reader-text"><?php esc_html_e( 'Hledat na webu', 'dedenik' ); ?></span><input type="search" name="s" value="<?php echo get_search_query(); ?>" placeholder="<?php esc_attr_e( 'Co hledáte?', 'dedenik' ); ?>"></label>
    <button type="submit" aria-label="<?php esc_attr_e( 'Vyhledat', 'dedenik' ); ?>"><span aria-hidden="true">⌕</span></button>
</form>

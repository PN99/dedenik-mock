<?php defined( 'ABSPATH' ) || exit;
$locations = get_nav_menu_locations();
$primary_args = dedenik_menu_args( 'frontier-menu-primary' );
$top_args = dedenik_menu_args( 'frontier-menu-top' );
$primary_id = absint( $primary_args['menu'] ?? ( $locations['frontier-menu-primary'] ?? 0 ) );
$top_id = absint( $top_args['menu'] ?? ( $locations['frontier-menu-top'] ?? 0 ) );
$distinct_top = $top_id && $top_id !== $primary_id;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Přeskočit na obsah', 'dedenik' ); ?></a>
<header class="site-header">
    <div class="wrap header-inner">
        <div class="brand">
        <?php if ( has_custom_logo() ) { the_custom_logo(); } else { ?>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><img class="logo" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="205" height="58"></a>
        <?php } ?>
        </div>
        <button class="menu-toggle" aria-expanded="false" aria-controls="<?php echo $distinct_top ? 'main-menu secondary-menu' : 'main-menu'; ?>" hidden><?php esc_html_e( 'Menu', 'dedenik' ); ?> <span aria-hidden="true">☰</span></button>
        <nav id="main-menu" class="primary-navigation" aria-label="<?php esc_attr_e( 'Hlavní navigace', 'dedenik' ); ?>">
            <?php $menu = wp_nav_menu( array_merge( $primary_args, array( 'echo' => false ) ) );
            if ( $menu ) { echo $menu; } else { ?>
                <ul class="menu"><li><a href="<?php echo esc_url( dedenik_posts_url() ); ?>"><?php esc_html_e( 'Články', 'dedenik' ); ?></a></li><?php wp_list_pages( array( 'title_li' => '', 'depth' => 1 ) ); ?></ul>
            <?php } ?>
        </nav>
        <?php get_search_form(); ?>
        <a class="button login" href="<?php echo esc_url( is_user_logged_in() ? admin_url( 'profile.php' ) : wp_login_url( home_url( '/' ) ) ); ?>"><?php echo is_user_logged_in() ? esc_html__( 'Můj účet', 'dedenik' ) : esc_html__( 'Přihlásit se', 'dedenik' ); ?></a>
    </div>
    <?php $top_menu = $distinct_top ? wp_nav_menu( array_merge( $top_args, array( 'echo' => false ) ) ) : ''; if ( $top_menu ) : ?>
        <nav id="secondary-menu" class="secondary-navigation wrap" aria-label="<?php esc_attr_e( 'Doplňková navigace', 'dedenik' ); ?>"><?php echo $top_menu; ?></nav>
    <?php endif; ?>
</header>
<div class="wrap"><?php dedenik_widget_area( 'widgets_header' ); dedenik_widget_area( 'widgets_below_menu' ); ?></div>
<main id="main" tabindex="-1">
<div class="wrap"><?php dedenik_widget_area( 'widgets_before_content' ); ?></div>

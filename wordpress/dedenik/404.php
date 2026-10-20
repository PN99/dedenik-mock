<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<section class="wrap section empty-state"><p class="kicker">404</p><h1><?php esc_html_e( 'Tento příběh jsme nenašli.', 'dedenik' ); ?></h1><p><?php esc_html_e( 'Zkuste hledání nebo se vraťte k nejnovějším článkům.', 'dedenik' ); ?></p><?php get_search_form(); ?><a class="button" href="<?php echo esc_url( dedenik_posts_url() ); ?>"><?php esc_html_e( 'Na články →', 'dedenik' ); ?></a></section>
<?php get_footer(); ?>

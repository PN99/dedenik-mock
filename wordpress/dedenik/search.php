<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<div class="wrap section"><header class="archive-header"><h1><?php printf( esc_html__( 'Výsledky hledání: %s', 'dedenik' ), esc_html( get_search_query( false ) ) ); ?></h1><?php get_search_form(); ?></header><?php get_template_part( 'template-parts/listing' ); get_sidebar(); ?></div>
<?php get_footer(); ?>

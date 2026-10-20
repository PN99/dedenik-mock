<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<div class="wrap section"><header class="archive-header"><?php the_archive_title( '<h1>', '</h1>' ); the_archive_description( '<div class="archive-description">', '</div>' ); ?></header><?php get_template_part( 'template-parts/listing' ); get_sidebar(); ?></div>
<?php get_footer(); ?>

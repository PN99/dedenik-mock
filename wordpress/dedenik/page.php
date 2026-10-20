<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<div class="wrap reading-layout"><div class="reading-column"><?php while ( have_posts() ) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'full-article' ); ?>><header class="article-header"><h1><?php the_title(); ?></h1></header>
<div class="entry-content"><?php the_content(); wp_link_pages( array( 'before' => '<nav class="page-links">', 'after' => '</nav>' ) ); ?></div><?php edit_post_link( __( 'Upravit stránku', 'dedenik' ) ); ?></article>
<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>
<?php endwhile; ?></div><?php get_sidebar(); ?></div>
<?php get_footer(); ?>

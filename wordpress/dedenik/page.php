<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<div class="wrap reading-layout"><div class="reading-column"><?php while ( have_posts() ) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'full-article' ); ?>><header class="article-header"><h1><?php the_title(); ?></h1></header>
<?php if ( is_page( 'dedivadlo' ) ) : ?>
<section class="world-posts theatre-program" aria-labelledby="theatre-program-title"><h2 id="theatre-program-title"><?php esc_html_e( 'Program Dedivadla', 'dedenik' ); ?></h2><p><strong><?php esc_html_e( '7. 11. · Vánočnice', 'dedenik' ); ?></strong> · <?php esc_html_e( '19.00 · Velichovky', 'dedenik' ); ?></p></section>
<?php edit_post_link( __( 'Upravit stránku', 'dedenik' ) ); ?>
<?php else : ?>
<div class="entry-content"><?php the_content(); wp_link_pages( array( 'before' => '<nav class="page-links">', 'after' => '</nav>' ) ); ?></div><?php edit_post_link( __( 'Upravit stránku', 'dedenik' ) ); ?>
<?php endif; ?>
</article>
<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>
<?php endwhile; ?></div><?php get_sidebar(); ?></div>
<?php get_footer(); ?>

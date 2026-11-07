<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( have_posts() ) : ?>
    <div class="cards"><?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/card' ); endwhile; ?></div>
    <?php the_posts_pagination( array( 'mid_size' => 2, 'prev_text' => __( '← Novější', 'dedenik' ), 'next_text' => __( 'Starší →', 'dedenik' ) ) ); ?>
<?php else : ?>
    <div class="empty-state"><h2><?php esc_html_e( 'Zatím tu nejsou žádné články.', 'dedenik' ); ?></h2><p><?php esc_html_e( 'Zkuste jinou rubriku nebo vyhledávání.', 'dedenik' ); ?></p><?php get_search_form(); ?></div>
<?php endif; ?>

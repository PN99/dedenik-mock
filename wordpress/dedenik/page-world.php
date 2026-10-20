<?php
/**
 * Template Name: Svět Dede – rubrika
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="wrap world-landing section">
<?php while ( have_posts() ) : the_post(); ?>
<article <?php post_class( 'world-intro' ); ?>><header class="article-header"><h1><?php the_title(); ?></h1></header>
<div class="entry-content"><?php the_content(); wp_link_pages(); ?></div></article>
<?php
if ( post_password_required() ) { continue; }
$page_id = get_the_ID();
$kind = get_post_meta( $page_id, '_dedenik_world_kind', true );
$category = absint( get_post_meta( $page_id, '_dedenik_world_category', true ) );
$links = array( 'spotify' => 'Spotify', 'apple' => 'Apple Podcasts', 'youtube' => 'YouTube' );
$public_links = array();
foreach ( $links as $key => $label ) {
    $url = esc_url( get_post_meta( $page_id, '_dedenik_world_' . $key, true ), array( 'https', 'http' ) );
    if ( $url ) { $public_links[] = '<a class="button" href="' . $url . '">' . esc_html( $label ) . '</a>'; }
}
if ( $public_links ) { echo '<nav class="world-platforms" aria-label="' . esc_attr__( 'Poslouchejte a sledujte', 'dedenik' ) . '">' . implode( '', $public_links ) . '</nav>'; }
$current = max( 1, absint( wp_unslash( $_GET['sd_page'] ?? 1 ) ) );
if ( $category && term_exists( $category, 'category' ) ) :
    $query = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false, 'cat' => $category, 'posts_per_page' => 12, 'paged' => $current, 'ignore_sticky_posts' => true ) );
    if ( 'podcast' === $kind && 1 === $current && $query->have_posts() ) :
        $latest = $query->posts[0];
        $source = dedenik_episode_source( $latest->post_content, get_post_meta( $latest->ID, 'enclosure', true ) );
        if ( $source ) : ?>
        <section class="latest-episode" aria-labelledby="latest-episode-title"><p class="kicker"><?php esc_html_e( 'Nejnovější epizoda', 'dedenik' ); ?></p><h2 id="latest-episode-title"><a href="<?php echo esc_url( get_permalink( $latest ) ); ?>"><?php echo esc_html( get_the_title( $latest ) ); ?></a></h2><?php dedenik_episode_player( $source ); ?></section>
        <?php endif;
    endif;
    ?>
    <section class="world-posts"><h2><?php echo 'podcast' === $kind ? esc_html__( 'Všechny epizody', 'dedenik' ) : esc_html__( 'Příspěvky z rubriky', 'dedenik' ); ?></h2>
    <?php if ( $query->have_posts() ) : ?>
        <div class="cards"><?php while ( $query->have_posts() ) { $query->the_post(); get_template_part( 'template-parts/card' ); } ?></div>
        <?php $pagination = paginate_links( array( 'base' => str_replace( '999999999', '%#%', add_query_arg( 'sd_page', 999999999, get_permalink( $page_id ) ) ), 'format' => '', 'current' => $current, 'total' => $query->max_num_pages, 'prev_text' => __( '← Novější', 'dedenik' ), 'next_text' => __( 'Starší →', 'dedenik' ) ) );
        if ( $pagination ) { echo '<nav class="pagination" aria-label="' . esc_attr__( 'Stránkování rubriky', 'dedenik' ) . '">' . $pagination . '</nav>'; }
        ?>
    <?php else : ?><p class="empty-state"><?php esc_html_e( 'V této rubrice zatím nejsou žádné veřejné příspěvky.', 'dedenik' ); ?></p><?php endif; ?>
    </section>
    <?php wp_reset_postdata(); ?>
<?php endif; ?>
<?php edit_post_link( __( 'Upravit stránku', 'dedenik' ), '<p>', '</p>', $page_id ); ?>
<?php endwhile; ?>
</div>
<?php get_footer(); ?>

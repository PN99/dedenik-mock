<?php
/** Search and archive are a separate editorial row, as in the visual mockup. */
defined( 'ABSPATH' ) || exit;
$archive_posts = get_posts( array(
    'post_type' => 'post', 'post_status' => 'publish', 'has_password' => false,
    'posts_per_page' => 1, 'ignore_sticky_posts' => true,
    'date_query' => array( array( 'before' => wp_date( 'Y-m-d', strtotime( '-1 year', current_time( 'timestamp' ) ) ) ) ),
) );
?>
<section class="section wrap search-archive-grid">
<div class="search-panel" id="hledani"><h2><?php esc_html_e( 'Chytré vyhledávání v Dedeníku', 'dedenik' ); ?></h2>
<p><?php esc_html_e( 'Hledejte příběhy podle tématu, rubriky nebo oblíbeného autora.', 'dedenik' ); ?></p>
<?php if ( dedenik_ai_ready() ) { dedenik_ai_form(); } else { get_search_form(); } ?>
</div>
<div class="home-archive" id="archiv"><h2><?php esc_html_e( 'Z archivu', 'dedenik' ); ?></h2>
<?php foreach ( $archive_posts as $archive_post ) : ?>
<a class="archive-feature" href="<?php echo esc_url( get_permalink( $archive_post ) ); ?>">
<?php if ( has_post_thumbnail( $archive_post ) ) { echo get_the_post_thumbnail( $archive_post, 'medium_large', array( 'class' => 'archive-picture', 'alt' => '', 'loading' => 'lazy' ) ); } else { ?>
<img class="archive-picture" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/archive.webp' ) ); ?>" width="400" height="105" alt="" loading="lazy">
<?php } ?>
<h3><?php echo esc_html( get_the_title( $archive_post ) ); ?></h3>
<span><?php esc_html_e( 'Přečíst z archivu →', 'dedenik' ); ?></span></a>
<?php endforeach; ?>
<details><summary><?php esc_html_e( 'Procházet archiv podle měsíce', 'dedenik' ); ?></summary><div class="archive-links"><?php wp_get_archives( array( 'type' => 'monthly', 'limit' => 6, 'format' => 'custom', 'before' => '<span>', 'after' => '</span>' ) ); ?></div></details>
</div></section>

<?php defined( 'ABSPATH' ) || exit; ?>
<section class="section wrap discover-grid" id="temata">
<div><h2><?php esc_html_e( 'Témata a rubriky', 'dedenik' ); ?></h2><div class="topics">
<?php foreach ( get_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 13 ) ) as $category ) :
    // Match real category slugs, so pictures stay attached when popularity changes.
    $picture = 'forest';
    if ( preg_match( '/psi|kock|zvir|kone/', $category->slug ) ) { $picture = 'dog'; }
    elseif ( preg_match( '/liter|kni|povid|pohad|recenz/', $category->slug ) ) { $picture = 'reading'; }
    elseif ( preg_match( '/foto|rozhled|cest|vylet|svet/', $category->slug ) ) { $picture = 'armenia'; }
?>
<a class="topic" href="<?php echo esc_url( get_category_link( $category ) ); ?>"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/' . $picture . '.webp' ) ); ?>" alt="" loading="lazy" width="200" height="120"><span><?php echo esc_html( $category->name ); ?></span></a>
<?php endforeach; ?>
</div></div>
<div class="discover-side">
<?php get_template_part( 'template-parts/authors' ); ?>
<div class="discover-tools"><h3><?php esc_html_e( 'Objevte Dedeník', 'dedenik' ); ?></h3>
<a href="<?php echo esc_url( dedenik_posts_url() . '#latest' ); ?>"><?php esc_html_e( 'Co číst dnes? →', 'dedenik' ); ?></a>
<a href="#archiv"><?php esc_html_e( 'Z archivu →', 'dedenik' ); ?></a>
<a href="#hledani"><?php esc_html_e( 'Vyhledávání v Dedeníku →', 'dedenik' ); ?></a>
</div></div></section>

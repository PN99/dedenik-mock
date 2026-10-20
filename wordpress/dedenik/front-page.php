<?php
/** Uses the existing main query and its pagination. */
defined( 'ABSPATH' ) || exit;
if ( ! is_home() ) { get_template_part( 'page' ); return; }
get_header();
if ( ! is_paged() ) { get_template_part( 'template-parts/hero' ); }
?>
<section class="section wrap" id="latest">
    <div class="section-heading"><h1><?php esc_html_e( 'Nejnovější články', 'dedenik' ); ?></h1></div>
    <nav class="category-tabs" aria-label="<?php esc_attr_e( 'Rubriky', 'dedenik' ); ?>"><a class="active" href="<?php echo esc_url( dedenik_posts_url() ); ?>" aria-current="page"><?php esc_html_e( 'Vše', 'dedenik' ); ?></a>
    <?php foreach ( get_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 8 ) ) as $category ) : ?><a href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a><?php endforeach; ?></nav>
    <?php get_template_part( 'template-parts/listing' ); ?>
</section>
<?php if ( ! is_paged() ) { get_template_part( 'template-parts/world' ); get_template_part( 'template-parts/discover' ); get_template_part( 'template-parts/authors' ); } ?>
<div class="wrap"><?php get_sidebar(); ?></div>
<?php get_footer(); ?>

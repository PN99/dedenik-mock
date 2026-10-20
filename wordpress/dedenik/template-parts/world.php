<?php
defined( 'ABSPATH' ) || exit;
$items = dedenik_world_items();
if ( ! $items ) { return; }
$landing_id = absint( get_theme_mod( 'dedenik_world_landing', 0 ) );
$landing = $landing_id ? get_post( $landing_id ) : null;
?>
<section class="world-section section"><div class="wrap"><div class="section-heading"><h2><?php echo esc_html( get_theme_mod( 'dedenik_world_heading', __( 'Ze Světa Dede', 'dedenik' ) ) ); ?></h2>
<?php if ( $landing && 'publish' === $landing->post_status && 'page' === $landing->post_type && ! $landing->post_password ) : ?><a href="<?php echo esc_url( get_permalink( $landing ) ); ?>"><?php esc_html_e( 'Celý Svět Dede →', 'dedenik' ); ?></a><?php endif; ?>
</div><div class="world-grid">
<?php foreach ( $items as $item ) : ?>
<article class="world-card world-card-<?php echo esc_attr( $item['key'] ); ?>">
<?php if ( $item['image'] ) { echo wp_get_attachment_image( $item['image'], 'medium', false, array( 'class' => 'world-image', 'alt' => '', 'loading' => 'lazy' ) ); }
elseif ( $item['page'] ) { echo get_the_post_thumbnail( $item['page'], 'medium', array( 'class' => 'world-image', 'alt' => '', 'loading' => 'lazy' ) ); } ?>
<div><span class="badge"><?php echo esc_html( $item['label'] ); ?></span><h3><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a></h3>
<?php if ( $item['description'] ) : ?><p><?php echo esc_html( $item['description'] ); ?></p><?php endif; ?>
<a class="text-link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['button'] ); ?></a></div></article>
<?php endforeach; ?>
</div></div></section>

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
<?php
$image = 'theatre' === $item['key'] ? '' : ( $item['image'] ? wp_get_attachment_image( $item['image'], 'medium', false, array( 'class' => 'world-image', 'alt' => '', 'loading' => 'lazy' ) ) : '' );
if ( 'podcast' === $item['key'] ) { $image = ''; }
if ( ! $image && ! in_array( $item['key'], array( 'podcast', 'theatre' ), true ) && $item['page'] ) { $image = get_the_post_thumbnail( $item['page'], 'medium', array( 'class' => 'world-image', 'alt' => '', 'loading' => 'lazy' ) ); }
if ( $image ) { echo $image; }
else { $pictures = array( 'podcast' => 'podcast.png', 'theatre' => 'dedivadlo-logo.jpg', 'books' => 'book.webp' ); $image_height = 'podcast' === $item['key'] ? 110 : 170; ?>
<img class="world-image" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/' . $pictures[ $item['key'] ] ) ); ?>" alt="<?php echo 'theatre' === $item['key'] ? esc_attr( 'Logo Dedivadla se čtyřmi členkami souboru' ) : ''; ?>" loading="lazy" width="110" height="<?php echo esc_attr( $image_height ); ?>">
<?php } ?>
<div><span class="badge"><?php echo esc_html( $item['label'] ); ?></span><?php if ( 'theatre' !== $item['key'] ) : ?><h3><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a></h3><?php endif; ?>
<?php if ( 'theatre' === $item['key'] ) : ?><div class="theatre-program-list" aria-label="<?php esc_attr_e( 'Program Dedivadla', 'dedenik' ); ?>"><?php foreach ( dedenik_theatre_program() as $performance ) : ?><p><strong><?php echo esc_html( $performance['date'] ); ?></strong> · <?php echo esc_html( $performance['title'] ); ?> · <?php echo esc_html( $performance['time'] ); ?> · <?php echo esc_html( $performance['location'] ); ?></p><?php endforeach; ?></div>
<?php elseif ( $item['description'] ) : ?><p><?php echo esc_html( $item['description'] ); ?></p><?php endif; ?>
<a class="button world-button" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['button'] ); ?></a></div></article>
<?php endforeach; ?>
</div></div></section>

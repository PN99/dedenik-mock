<?php defined( 'ABSPATH' ) || exit;
$authors = get_users( array( 'has_published_posts' => array( 'post' ), 'number' => 12, 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => array( 'ID', 'display_name' ) ) );
if ( ! $authors ) { return; }
?>
<section class="section wrap author-section" id="autori"><h2><?php esc_html_e( 'Autoři Dedeníku', 'dedenik' ); ?></h2><div class="authors">
<?php foreach ( $authors as $author ) : ?><a class="author" href="<?php echo esc_url( get_author_posts_url( $author->ID ) ); ?>"><span class="author-initial" aria-hidden="true"><?php echo esc_html( wp_html_excerpt( $author->display_name, 1, '' ) ); ?></span><span><?php echo esc_html( $author->display_name ); ?></span></a><?php endforeach; ?>
</div></section>

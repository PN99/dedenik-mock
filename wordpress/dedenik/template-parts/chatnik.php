<?php
defined( 'ABSPATH' ) || exit;
$category = dedenik_chat_category();
if ( ! $category ) { return; }
$chat = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'cat' => $category->term_id, 'posts_per_page' => 4, 'no_found_rows' => true, 'ignore_sticky_posts' => true ) );
if ( ! $chat->have_posts() ) { return; }
?>
<aside class="chatnik" aria-labelledby="chatnik-title"><h2 id="chatnik-title"><?php echo esc_html( $category->name ); ?></h2><p><?php esc_html_e( 'Místo pro společné povídání', 'dedenik' ); ?></p>
<?php while ( $chat->have_posts() ) : $chat->the_post(); ?><a class="chat-row" href="<?php the_permalink(); ?>"><span><?php the_title(); ?></span><?php if ( ! post_password_required() ) : ?><span class="chat-count"><?php echo esc_html( number_format_i18n( get_comments_number() ) ); ?><span class="screen-reader-text"> <?php esc_html_e( 'komentářů', 'dedenik' ); ?></span></span><?php endif; ?></a><?php endwhile; wp_reset_postdata(); ?>
<a class="text-link" href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php esc_html_e( 'Přejít do rubriky →', 'dedenik' ); ?></a></aside>

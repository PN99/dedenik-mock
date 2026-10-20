<?php
defined( 'ABSPATH' ) || exit;
if ( post_password_required() ) { return; }
?>
<section id="comments" class="comments-area" aria-labelledby="comments-title">
<h2 id="comments-title"><?php printf( esc_html__( 'Diskuse (%s)', 'dedenik' ), esc_html( number_format_i18n( get_comments_number() ) ) ); ?></h2>
<?php if ( have_comments() ) : ?>
    <?php the_comments_pagination( array( 'prev_text' => __( '← Předchozí', 'dedenik' ), 'next_text' => __( 'Další →', 'dedenik' ) ) ); ?>
    <ol class="comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 40 ) ); ?></ol>
    <?php the_comments_pagination( array( 'prev_text' => __( '← Předchozí', 'dedenik' ), 'next_text' => __( 'Další →', 'dedenik' ) ) ); ?>
<?php endif; ?>
<?php if ( ! comments_open() ) : ?><p class="comments-closed"><?php esc_html_e( 'Diskuse je uzavřená.', 'dedenik' ); ?></p><?php endif; ?>
<?php comment_form(); ?>
</section>

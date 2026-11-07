<?php defined( 'ABSPATH' ) || exit; ?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
    <a class="card-image-link" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php dedenik_card_image(); ?></a>
    <div class="card-body"><?php dedenik_primary_category(); ?>
        <h2 class="card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div class="card-excerpt"><?php if ( post_password_required() ) { esc_html_e( 'Tento obsah je chráněný heslem.', 'dedenik' ); } else { the_excerpt(); } ?></div>
        <?php dedenik_metadata(); ?>
    </div>
</article>

<?php defined( 'ABSPATH' ) || exit; ?>
<section class="section wrap discover-grid" id="temata">
<div><h2><?php esc_html_e( 'Témata a rubriky', 'dedenik' ); ?></h2><div class="topics">
<?php
// Fixed editorial selection and order from the approved category reference.
$topics = array(
    array( 'label' => __( 'BTW', 'dedenik' ), 'slug' => 'btw', 'image' => 'forest' ),
    array( 'label' => __( 'Člověčiny', 'dedenik' ), 'slug' => 'cloveciny', 'image' => 'forest' ),
    array( 'label' => __( 'Matyldoviny', 'dedenik' ), 'slug' => 'matyldoviny', 'image' => 'forest' ),
    array( 'label' => __( 'Mlsotník', 'dedenik' ), 'slug' => 'mlsotnik', 'image' => 'forest' ),
    array( 'label' => __( 'Rozcestník', 'dedenik' ), 'slug' => 'rozcestnik', 'image' => 'armenia' ),
    array( 'label' => __( 'Rozhledník', 'dedenik' ), 'slug' => 'rozhlednik', 'image' => 'armenia' ),
    array( 'label' => __( 'Chatník', 'dedenik' ), 'slug' => 'chatnik', 'image' => 'forest' ),
    array( 'label' => __( 'Fotočlánky', 'dedenik' ), 'slug' => 'fotoclanek', 'image' => '' ),
    array( 'label' => __( 'Povídky', 'dedenik' ), 'slug' => 'povidky', 'image' => 'reading' ),
    array( 'label' => __( 'Literární léto', 'dedenik' ), 'slug' => 'literarni-leto', 'image' => 'reading' ),
    array( 'label' => __( 'Kočky', 'dedenik' ), 'slug' => 'kocky', 'image' => 'dog' ),
    array( 'label' => __( 'Psi', 'dedenik' ), 'slug' => 'psi', 'image' => 'dog' ),
    array( 'label' => __( 'Koně', 'dedenik' ), 'slug' => 'kone', 'image' => '' ),
    array( 'label' => __( 'Netopýři', 'dedenik' ), 'slug' => 'netopyri', 'image' => '' ),
    array( 'label' => __( 'Akvaristika', 'dedenik' ), 'slug' => 'akvaristika', 'image' => '' ),
    array( 'label' => __( 'Dedivadlo', 'dedenik' ), 'slug' => 'dedivadlo', 'image' => '' ),
);
foreach ( $topics as $topic ) :
    $category = get_term_by( 'slug', $topic['slug'], 'category' );
    $url = $category && ! is_wp_error( $category ) ? get_category_link( $category ) : '';
    $available = $url && ! is_wp_error( $url );
    $tag = $available ? 'a' : 'span';
?>
<<?php echo $tag; ?> class="topic<?php echo $topic['image'] ? '' : ' topic-plain'; ?>"<?php if ( $available ) : ?> href="<?php echo esc_url( $url ); ?>"<?php else : ?> aria-disabled="true"<?php endif; ?>><?php if ( $topic['image'] ) : ?><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/' . $topic['image'] . '.webp' ) ); ?>" alt="" loading="lazy" width="200" height="120"><?php endif; ?><span><?php echo esc_html( $topic['label'] ); ?></span></<?php echo $tag; ?>>
<?php endforeach; ?>
</div></div>
<div class="discover-side">
<?php get_template_part( 'template-parts/authors' ); ?>
<div class="discover-tools"><h3><?php esc_html_e( 'Objevte Dedeník', 'dedenik' ); ?></h3>
<a href="<?php echo esc_url( dedenik_posts_url() . '#latest' ); ?>"><?php esc_html_e( 'Co číst dnes? →', 'dedenik' ); ?></a>
<a href="#archiv"><?php esc_html_e( 'Z archivu →', 'dedenik' ); ?></a>
<a href="#hledani"><?php esc_html_e( 'Vyhledávání v Dedeníku →', 'dedenik' ); ?></a>
</div></div></section>

<?php defined( 'ABSPATH' ) || exit; ?>
<section class="section wrap discover-grid" id="temata">
<div><h2><?php esc_html_e( 'Témata a rubriky', 'dedenik' ); ?></h2><div class="topics">
<?php
// Fixed editorial selection and order from the approved category reference.
$topics = array(
    array( 'label' => __( 'BTW', 'dedenik' ), 'slug' => 'btw', 'viewBox' => '15 17 372 221' ),
    array( 'label' => __( 'Člověčiny', 'dedenik' ), 'slug' => 'cloveciny', 'viewBox' => '412 17 372 221' ),
    array( 'label' => __( 'Matyldoviny', 'dedenik' ), 'slug' => 'matyldoviny', 'viewBox' => '809 17 372 221' ),
    array( 'label' => __( 'Mlsotník', 'dedenik' ), 'slug' => 'mlsotnik', 'viewBox' => '1206 17 372 221' ),
    array( 'label' => __( 'Rozcestník', 'dedenik' ), 'slug' => 'rozcestnik', 'viewBox' => '15 261 372 221' ),
    array( 'label' => __( 'Rozhledník', 'dedenik' ), 'slug' => 'rozhlednik', 'viewBox' => '412 261 372 221' ),
    array( 'label' => __( 'Chatník', 'dedenik' ), 'slug' => 'chatnik', 'viewBox' => '809 261 372 221' ),
    array( 'label' => __( 'Fotočlánky', 'dedenik' ), 'slug' => 'fotoclanek', 'viewBox' => '1206 261 372 221' ),
    array( 'label' => __( 'Povídky', 'dedenik' ), 'slug' => 'povidky', 'viewBox' => '15 507 372 221' ),
    array( 'label' => __( 'Literární léto', 'dedenik' ), 'slug' => 'literarni-leto', 'viewBox' => '412 507 372 221' ),
    array( 'label' => __( 'Kočky', 'dedenik' ), 'slug' => 'kocky', 'viewBox' => '809 507 372 221' ),
    array( 'label' => __( 'Psi', 'dedenik' ), 'slug' => 'psi', 'viewBox' => '1206 507 372 221' ),
    array( 'label' => __( 'Koně', 'dedenik' ), 'slug' => 'kone', 'viewBox' => '15 752 372 221' ),
    array( 'label' => __( 'Netopýři', 'dedenik' ), 'slug' => 'netopyri', 'viewBox' => '412 752 372 221' ),
    array( 'label' => __( 'Akvaristika', 'dedenik' ), 'slug' => 'akvaristika', 'viewBox' => '809 752 372 221' ),
    array( 'label' => __( 'Dedivadlo', 'dedenik' ), 'slug' => 'dedivadlo', 'viewBox' => '1206 752 372 221' ),
);
foreach ( $topics as $topic ) :
    $category = get_term_by( 'slug', $topic['slug'], 'category' );
    $url = $category && ! is_wp_error( $category ) ? get_category_link( $category ) : '';
    $available = $url && ! is_wp_error( $url );
    $tag = $available ? 'a' : 'span';
?>
<<?php echo $tag; ?> class="topic"<?php if ( $available ) : ?> href="<?php echo esc_url( $url ); ?>"<?php else : ?> aria-disabled="true"<?php endif; ?>><svg class="topic-image" viewBox="<?php echo esc_attr( $topic['viewBox'] ); ?>" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false"><image href="<?php echo esc_url( get_theme_file_uri( 'assets/images/topics-sheet.png' ) ); ?>" width="1588" height="991"/></svg><span><?php echo esc_html( $topic['label'] ); ?></span></<?php echo $tag; ?>>
<?php endforeach; ?>
</div></div>
<div class="discover-side">
<?php get_template_part( 'template-parts/authors' ); ?>
<div class="discover-tools"><h3><?php esc_html_e( 'Objevte Dedeník', 'dedenik' ); ?></h3>
<a href="<?php echo esc_url( dedenik_posts_url() . '#latest' ); ?>"><?php esc_html_e( 'Co číst dnes? →', 'dedenik' ); ?></a>
<a href="#archiv"><?php esc_html_e( 'Z archivu →', 'dedenik' ); ?></a>
<a href="#hledani"><?php esc_html_e( 'Vyhledávání v Dedeníku →', 'dedenik' ); ?></a>
</div></div></section>

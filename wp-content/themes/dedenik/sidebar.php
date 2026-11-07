<?php
defined( 'ABSPATH' ) || exit;
if ( ! is_active_sidebar( 'widgets_sidebar_left' ) && ! is_active_sidebar( 'widgets_sidebar_right' ) ) { return; }
?>
<aside class="community-sidebar" aria-label="<?php esc_attr_e( 'Komunita a další obsah', 'dedenik' ); ?>"><?php dedenik_widget_area( 'widgets_sidebar_right' ); dedenik_widget_area( 'widgets_sidebar_left' ); ?></aside>

<?php
/** Theme bootstrap. No activation migrations or database writes. */
defined( 'ABSPATH' ) || exit;
require get_template_directory() . '/inc/setup.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/world-admin.php';
require get_template_directory() . '/inc/footer.php';
require get_template_directory() . '/inc/ai-search.php';

<?php
// Offline behavior checks. These do not substitute for WordPress integration tests.
define('ABSPATH', __DIR__);
$mods = array(); $options = array(); $assigned = false; $protected = false; $thumbnail = true; $terms = array();
function absint($value) { return abs((int)$value); }
function esc_attr($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value) { return esc_attr($value); }
function esc_url($value) { return esc_attr($value); }
function __($value,$domain) { return $value; }
function esc_html__($value,$domain) { return $value; }
function has_nav_menu($location) { return $GLOBALS['assigned']; }
function get_option($key,$default=false) { return $GLOBALS['options'][$key] ?? $default; }
function get_theme_mod($key,$default=false) { return $GLOBALS['mods'][$key] ?? $default; }
function wp_get_nav_menu_object($id) { return $id === 7 ? (object)array('term_id'=>7) : false; }
function get_permalink($id) { return 'https://example.test/page/'.$id.'/'; }
function home_url($path) { return 'https://example.test'.$path; }
function get_theme_file_uri($path) { return 'https://example.test/wp-content/themes/dedenik/'.$path; }
function get_the_category() { return $GLOBALS['terms']; }
function get_post_meta($id,$key,$single) { return $GLOBALS['primary'] ?? 0; }
function get_the_ID() { return 42; }
function get_category_link($term) { return 'https://example.test/category/'.$term->term_id.'/'; }
function is_wp_error($value) { return $value instanceof Exception; }
function has_post_thumbnail() { return $GLOBALS['thumbnail']; }
function post_password_required() { return $GLOBALS['protected']; }
function the_post_thumbnail($size,$args) { echo '<img src="protected-source.jpg">'; }
function get_term($id,$taxonomy) { return $id===9 ? (object)array('term_id'=>9,'name'=>'Chatník') : new Exception('invalid'); }
function get_term_by($field,$value,$taxonomy) { return false; }
function get_post($id) { return $GLOBALS['fixture_pages'][$id] ?? null; }
require __DIR__.'/../dedenik/inc/template-tags.php';
$tests=0;
function check($condition,$message) { $GLOBALS['tests']++; if (!$condition) { throw new RuntimeException($message); } }
$options['theme_mods_frontier']=array('nav_menu_locations'=>array('frontier-menu-primary'=>7));
check(dedenik_menu_args('frontier-menu-primary')['menu']===7,'Frontier fallback preserves menu');
$assigned=true; check(!isset(dedenik_menu_args('frontier-menu-primary')['menu']),'New assignment must take precedence');
$assigned=false; check(!isset(dedenik_menu_args('footer')['menu']),'Footer must not borrow primary menu');
$options['theme_mods_frontier']['nav_menu_locations']['frontier-menu-primary']=999;
check(!isset(dedenik_menu_args('frontier-menu-primary')['menu']),'Deleted old menu must not be forced');
check(dedenik_posts_url()==='https://example.test/','Latest-posts homepage URL');
$options['page_for_posts']=123; check(dedenik_posts_url()==='https://example.test/page/123/','Configured posts-page URL');
$terms=array((object)array('term_id'=>1,'name'=>'Fallback'),(object)array('term_id'=>2,'name'=>'<script>'));
$primary=2; ob_start(); dedenik_primary_category(); $html=ob_get_clean();
check(strpos($html,'category/2/')!==false,'Primary category honored');
check(strpos($html,'<script>')===false && strpos($html,'&lt;script&gt;')!==false,'Category names escaped');
$primary=99; ob_start(); dedenik_primary_category(); $html=ob_get_clean(); check(strpos($html,'category/1/')!==false,'Invalid primary category falls back');
$protected=true; ob_start(); dedenik_card_image(); $html=ob_get_clean(); check(strpos($html,'protected-source')===false,'Password-protected card must not disclose thumbnail');
ob_start(); require __DIR__.'/../dedenik/comments.php'; $html=ob_get_clean(); check($html==='','Protected post must not render comments or form');
$protected=false; ob_start(); dedenik_card_image(); $html=ob_get_clean(); check(strpos($html,'protected-source')!==false,'Public thumbnail rendered');
$thumbnail=false; ob_start(); dedenik_card_image(); $html=ob_get_clean(); check(strpos($html,'card-placeholder')!==false,'Missing image has fallback');
$mods['dedenik_chat_category']=999; check(dedenik_chat_category()===false,'Invalid Chatnik category hides component');
$mods['dedenik_chat_category']=9; check(dedenik_chat_category()->term_id===9,'Configured Chatnik category used');
$fixture_pages=array(); ob_start(); require __DIR__.'/../dedenik/template-parts/world.php'; $html=ob_get_clean(); check(strpos($html,'https://alzbetaadrak.cz/')!==false,'Default books card links to Alzbeta a drak');
$mods['dedenik_world_books']=3; $fixture_pages[3]=(object)array('post_type'=>'page','post_status'=>'draft','post_password'=>'');
ob_start(); require __DIR__.'/../dedenik/template-parts/world.php'; $html=ob_get_clean(); check(strpos($html,'world-card-books')===false,'Draft promotional page must not leak');
$fixture_pages[3]->post_status='publish'; $fixture_pages[3]->post_password='fixture';
ob_start(); require __DIR__.'/../dedenik/template-parts/world.php'; $html=ob_get_clean(); check(strpos($html,'world-card-books')===false,'Protected promotional page must not leak');
check(strpos($html,'https://open.spotify.com/show/4xeerT4CEz6vBbsb9YzGWM')!==false,'Podcast card defaults to the supplied Spotify show');
$mods['dedenik_world_podcast_url']='https://example.test/custom-podcast';
$items=dedenik_world_items(); check($items[0]['url']==='https://example.test/custom-podcast','Configured podcast link takes priority');
echo 'Passed '.$tests." offline behavior checks\n";

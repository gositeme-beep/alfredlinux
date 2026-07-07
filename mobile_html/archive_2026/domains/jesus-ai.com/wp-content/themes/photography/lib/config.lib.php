<?php
//Setup theme constant and default data
$theme_obj = wp_get_theme('photography');

define("THEMENAME", $theme_obj['Name']);
if (!defined('THEMEDEMO'))
{
	define("THEMEDEMO", false);
}
if (!defined('ENVATOITEMID')) {
	define("ENVATOITEMID", 13304399);
}

define("THEMEDEMOIG", 'kinfolklifestyle');
define("DEMOGALLERYID", 'gallery-archive');
define("SHORTNAME", "pp");
define("SKINSHORTNAME", "ps");
define("THEMEVERSION", $theme_obj['Version']);
define("THEMEDEMOURL", $theme_obj['ThemeURI']);
define("THEMEDATEFORMAT", get_option('date_format'));
define("THEMETIMEFORMAT", get_option('time_format'));
define("BUILDERDOCURL", 'https://themes.themegoods.com/photography/doc/create-a-page-using-content-builder-2/');

define("THEMEGOODS_API", 'http://license.themegoods.com/manager/wp-json/envato');
define("THEMEGOODS_PURCHASE_URL", 'https://1.envato.market/Y3qaO');

//Get default WP uploads folder
$wp_upload_arr = wp_upload_dir();
define("THEMEUPLOAD", $wp_upload_arr['basedir']."/".strtolower(sanitize_title(THEMENAME))."/");
define("THEMEUPLOADURL", $wp_upload_arr['baseurl']."/".strtolower(sanitize_title(THEMENAME))."/");

if(!is_dir(THEMEUPLOAD))
{
	mkdir(THEMEUPLOAD);
}

//Define all google font usages in customizer
$photography_google_fonts = array('tg_body_font', 'tg_header_font', 'tg_menu_font', 'tg_sidemenu_font', 'tg_sidebar_title_font', 'tg_button_font');

global $photography_google_fonts;

//Set page gallery ID
function photography_set_page_gallery_id($new_value = '') {
	global $photograhy_page_gallery_id;
	$photograhy_page_gallery_id = $new_value;
}

//Get page gallery ID
function photography_get_page_gallery_id() {
	global $photograhy_page_gallery_id;
	return $photograhy_page_gallery_id;
}

//Get default WordPress file system variable
function photography_get_wp_filesystem() {
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	WP_Filesystem();
	global $wp_filesystem;
	return $wp_filesystem;
}
?>

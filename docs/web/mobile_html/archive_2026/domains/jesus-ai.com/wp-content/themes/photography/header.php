<?php
/**
 * The Header for the template.
 *
 * @package WordPress
 */
 
if (!isset( $content_width ) ) $content_width = 1170;

if(session_id() == '') {
	session_start();
}
 
global $photography_homepage_style;

$tg_menu_layout = photography_menu_layout();
?><!DOCTYPE html>
<html <?php language_attributes(); ?> <?php if(isset($photography_homepage_style) && !empty($photography_homepage_style)) { echo 'data-style="'.esc_attr($photography_homepage_style).'"'; } ?> data-menu="<?php echo esc_attr($tg_menu_layout); ?>">
<head>

<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

<?php
	//Fallback compatibility for favicon
	if(!function_exists( 'has_site_icon' ) || ! has_site_icon() ) 
	{
		/**
		*	Get favicon URL
		**/
		$tg_favicon = kirki_get_option('tg_favicon');
		
		if(!empty($tg_favicon))
		{
?>
			<link rel="shortcut icon" href="<?php echo esc_url($tg_favicon); ?>" />
<?php
		}
	}
?> 

<?php
	/* Always have wp_head() just before the closing </head>
	 * tag of your theme, or you will break many plugins, which
	 * generally use this hook to add elements to <head> such
	 * as styles, scripts, and meta tags.
	 */
	wp_head();
?>
</head>

<body <?php body_class(); ?>>

	<?php
		//Check if disable right click
		$tg_enable_right_click = kirki_get_option('tg_enable_right_click');
		
		//Check if disable image dragging
		$tg_enable_dragging = kirki_get_option('tg_enable_dragging');
		
		//Check if use AJAX search
		$tg_menu_search_instant = kirki_get_option('tg_menu_search_instant');
		
		//Check if sticky menu
		$tg_fixed_menu = kirki_get_option('tg_fixed_menu');
		
		//Check if sticky sidebar
		$tg_sidebar_sticky = kirki_get_option('tg_sidebar_sticky');
		
		//Check if display top bar
		$tg_topbar = kirki_get_option('tg_topbar');
		if(THEMEDEMO && isset($_GET['topbar']) && !empty($_GET['topbar']))
		{
			$tg_topbar = true;
		}
		
		//Check if add blur effect
		$tg_page_title_img_blur = kirki_get_option('tg_page_title_img_blur');

		//Check menu layout
		$tg_menu_layout = photography_menu_layout();
		
		//Check filterable link option
		$tg_portfolio_filterable_link = kirki_get_option('tg_portfolio_filterable_link');
		
		//Check image flow reflection option
		$tg_flow_enable_reflection = kirki_get_option('tg_flow_enable_reflection');
		
		//Get lightbox skin color
		$tg_lightbox_skin = kirki_get_option('tg_lightbox_skin');
		
		//Get lightbox thumbnails alignment
		$tg_lightbox_thumbnails = kirki_get_option('tg_lightbox_thumbnails');
		
		//Get lightbox overlay opacity
		$tg_lightbox_opacity = kirki_get_option('tg_lightbox_opacity');
		$tg_lightbox_opacity = $tg_lightbox_opacity/100;
		
		//Get lightbox setting
		$tg_lightbox_enable = kirki_get_option('tg_lightbox_enable');
		$tg_lightbox_plugin = kirki_get_option('tg_lightbox_plugin');
		$tg_lightbox_timer = kirki_get_option('tg_lightbox_timer');
		$tg_modulobox_thumbnails = kirki_get_option('tg_modulobox_thumbnails');
		
		//Get sticky menu color scheme
		$tg_fixed_menu_color = kirki_get_option('tg_fixed_menu_color');
	?>
	<input type="hidden" id="pp_menu_layout" name="pp_menu_layout" value="<?php echo esc_attr($tg_menu_layout); ?>"/>
	<input type="hidden" id="pp_enable_right_click" name="pp_enable_right_click" value="<?php echo esc_attr($tg_enable_right_click); ?>"/>
	<input type="hidden" id="pp_enable_dragging" name="pp_enable_dragging" value="<?php echo esc_attr($tg_enable_dragging); ?>"/>
	<input type="hidden" id="pp_image_path" name="pp_image_path" value="<?php echo get_template_directory_uri(); ?>/images/"/>
	<input type="hidden" id="pp_homepage_url" name="pp_homepage_url" value="<?php echo esc_url(home_url('/')); ?>"/>
	<input type="hidden" id="photography_ajax_search" name="photography_ajax_search" value="<?php echo esc_attr($tg_menu_search_instant); ?>"/>
	<input type="hidden" id="pp_fixed_menu" name="pp_fixed_menu" value="<?php echo esc_attr($tg_fixed_menu); ?>"/>
	<input type="hidden" id="tg_sidebar_sticky" name="tg_sidebar_sticky" value="<?php echo esc_attr($tg_sidebar_sticky); ?>"/>
	<input type="hidden" id="pp_topbar" name="pp_topbar" value="<?php echo esc_attr($tg_topbar); ?>"/>
	<input type="hidden" id="post_client_column" name="post_client_column" value="4"/>
	<input type="hidden" id="pp_back" name="pp_back" value="<?php esc_html_e('Back', 'photography' ); ?>"/>
	<input type="hidden" id="pp_page_title_img_blur" name="pp_page_title_img_blur" value="<?php echo esc_attr($tg_page_title_img_blur); ?>"/>
	<input type="hidden" id="tg_portfolio_filterable_link" name="tg_portfolio_filterable_link" value="<?php echo esc_attr($tg_portfolio_filterable_link); ?>"/>
	<input type="hidden" id="tg_flow_enable_reflection" name="tg_flow_enable_reflection" value="<?php echo esc_attr($tg_flow_enable_reflection); ?>"/>
	<input type="hidden" id="tg_lightbox_skin" name="tg_lightbox_skin" value="<?php echo esc_attr($tg_lightbox_skin); ?>"/>
	<input type="hidden" id="tg_lightbox_thumbnails" name="tg_lightbox_thumbnails" value="<?php echo esc_attr($tg_lightbox_thumbnails); ?>"/>
	<input type="hidden" id="tg_lightbox_opacity" name="tg_lightbox_opacity" value="<?php echo esc_attr($tg_lightbox_opacity); ?>"/>
	<input type="hidden" id="tg_lightbox_enable" name="tg_lightbox_enable" value="<?php echo esc_attr($tg_lightbox_enable); ?>"/>
	<input type="hidden" id="tg_lightbox_plugin" name="tg_lightbox_plugin" value="<?php echo esc_attr($tg_lightbox_plugin); ?>"/>
	<input type="hidden" id="tg_lightbox_timer" name="tg_lightbox_timer" value="<?php echo intval($tg_lightbox_timer*1000); ?>"/>
	<?php
		if($tg_lightbox_plugin == 'modulobox')
	    {
	?>
	<input type="hidden" id="tg_modulobox_thumbnails" name="tg_modulobox_thumbnails" value="<?php echo esc_attr($tg_modulobox_thumbnails); ?>"/>
	<input type="hidden" id="tg_modulobox_share_on_string" name="tg_modulobox_share_on_string" value="<?php esc_html_e('Share On', 'photography' ); ?>"/>
	<?php
		}
	?>
	<input type="hidden" id="tg_fixed_menu_color" name="tg_fixed_menu_color" value="<?php echo esc_attr($tg_fixed_menu_color); ?>"/>
	
	<?php
		$tg_live_builder = 0;
		if(isset($_GET['ppb_live']))
		{
			$tg_live_builder = 1;
		}
	?>
	<input type="hidden" id="tg_live_builder" name="tg_live_builder" value="<?php echo esc_attr($tg_live_builder); ?>"/>
	
	<?php
		//Check footer sidebar columns
		$tg_footer_sidebar = kirki_get_option('tg_footer_sidebar');
	?>
	<input type="hidden" id="pp_footer_style" name="pp_footer_style" value="<?php echo esc_attr($tg_footer_sidebar); ?>"/>
	
	<?php
		//Get main menu layout
		$tg_menu_layout = photography_menu_layout();
		
		switch($tg_menu_layout)
		{
			case 'centeralign':
			case 'hammenuside':
			case 'leftalign':
			case 'centeralogo':
			default:
				get_template_part("/templates/template-sidemenu");
			break;
			
			case 'hammenufull':
				get_template_part("/templates/template-fullmenu");
			break;
		}
	?>

	<!-- Begin template wrapper -->
	<?php
		global $photography_page_menu_transparent;
		
		//If single post
		if(is_singular('post')) {
			$tg_blog_feat_content = kirki_get_option('tg_blog_feat_content');
			
			if(empty($tg_blog_feat_content)) {
				$photography_page_menu_transparent = 0;
			}
		}
	?>
	<div id="wrapper" <?php if(!empty($photography_page_menu_transparent)) { ?>class="hasbg"<?php } ?>>
	
	<?php
		//Get main menu layout
		$tg_menu_layout = photography_menu_layout();
		
		switch($tg_menu_layout)
		{
			case 'centeralign':
			case 'hammenuside':
			case 'hammenufull':
			default:
				get_template_part("/templates/template-topmenu");
			break;
			
			case 'leftalign':
				get_template_part("/templates/template-topmenu-left");
			break;
			
			case 'centeralogo':
				get_template_part("/templates/template-topmenu-center-menus");
			break;
		}
	?>
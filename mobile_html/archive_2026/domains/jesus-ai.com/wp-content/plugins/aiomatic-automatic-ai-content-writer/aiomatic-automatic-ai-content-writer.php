<?php
/** 
Plugin Name: Aiomatic - Automatic AI Content Writer
Plugin URI: //1.envato.market/aiomatic
Description: This plugin will generate content for you, even in your sleep using AI
Author: CodeRevolution
Version: 1.1.8
Author URI: //coderevolution.ro
License: Commercial. For personal use only. Not to give away or resell.
Text Domain: aiomatic-automatic-ai-content-writer
*/
/*  
Copyright 2016 - 2023 CodeRevolution
*/
defined('ABSPATH') or die();
use Orhanerday\OpenAi\OpenAi;
require_once (dirname(__FILE__) . "/res/other/plugin-dash.php"); 
function aiomatic_get_version() {
    $plugin_data = get_file_data( __FILE__  , array('Version' => 'Version'), false);
    return $plugin_data['Version'];
}
$aiomatic_debug = false;
const AIOMATIC_MODELS = array('text-davinci-003', 'text-davinci-002', 'text-curie-001', 'text-babbage-001', 'text-ada-001', 'code-davinci-002', 'code-cushman-001');
const AIOMATIC_EDIT_MODELS = array('text-davinci-edit-001', 'code-davinci-edit-001');
const AIOMATIC_IS_DEBUG = false;
function aiomatic_load_textdomain() {
    require_once(dirname(__FILE__) . "/res/Embeddings.php");
    new Aiomatic_Embeddings();
    load_plugin_textdomain( 'aiomatic-automatic-ai-content-writer', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'aiomatic_load_textdomain' );

function aiomatic_get_random_user_agent() {
	$agents = array(
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36",
		"Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8",
		"Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36",
		"Mozilla/5.0 (Windows NT 10.0; WOW64; rv:55.0) Gecko/20100101 Firefox/55.0",
		"Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:55.0) Gecko/20100101 Firefox/55.0",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36",
		"Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko",
		"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:55.0) Gecko/20100101 Firefox/55.0",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:55.0) Gecko/20100101 Firefox/55.0",
		"Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:55.0) Gecko/20100101 Firefox/55.0",
		"Mozilla/5.0 (Windows NT 10.0; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36"
	);
	$rand   = rand( 0, count( $agents ) - 1 );
	return trim( $agents[ $rand ] );
}
function aiomatic_assign_var(&$target, $var, $root = false) {
	static $cnt = 0;
    $key = key($var);
    if(is_array($var[$key])) 
        aiomatic_assign_var($target[$key], $var[$key], false);
    else {
        if($key==0)
		{
			if($cnt == 0 && $root == true)
			{
				$target['_aiomaticr_nonce'] = $var[$key];
				$cnt++;
			}
			elseif($cnt == 1 && $root == true)
			{
				$target['_wp_http_referer'] = $var[$key];
				$cnt++;
			}
			else
			{
				$target[] = $var[$key];
			}
		}
        else
		{
            $target[$key] = $var[$key];
		}
    }   
}

$plugin = plugin_basename(__FILE__);
if(is_admin())
{
    require(dirname(__FILE__) . "/res/aiomatic-main.php");
    require(dirname(__FILE__) . "/res/aiomatic-rules-list.php");
    require(dirname(__FILE__) . "/res/aiomatic-single-list.php");
    require(dirname(__FILE__) . "/res/aiomatic-spinner-list.php");
    require(dirname(__FILE__) . "/res/aiomatic-playground.php");
    require(dirname(__FILE__) . "/res/aiomatic-shortcodes.php");
    require(dirname(__FILE__) . "/res/aiomatic-training.php");
    require(dirname(__FILE__) . "/res/aiomatic-embeddings.php");
    require(dirname(__FILE__) . "/res/aiomatic-limits-statistics.php");
    require(dirname(__FILE__) . "/res/aiomatic-logs.php");
    if($_SERVER["REQUEST_METHOD"]==="POST" && !empty($_POST["coderevolution_max_input_var_data"])) {
        $vars = explode("&", $_POST["coderevolution_max_input_var_data"]);
        $coderevolution_max_input_var_data = array();
        foreach($vars as $var) {
            parse_str($var, $variable);
            aiomatic_assign_var($_POST, $variable, true);
        }
        unset($_POST["coderevolution_max_input_var_data"]);
    }
    $plugin_slug = explode('/', $plugin);
    $plugin_slug = $plugin_slug[0];
    if(isset($_POST[$plugin_slug . '_register']) && isset($_POST[$plugin_slug. '_register_code']) && trim($_POST[$plugin_slug . '_register_code']) != '')
    {
        $uoptions = array();
        $uoptions['item_id'] = 38877369;
        $uoptions['item_name'] = 'AIomatic - Automatic AI Content Writer';
        $uoptions['created_at'] = '24.12.1974';
        $uoptions['buyer'] = 'Tom & Jerry';
        $uoptions['licence'] = 'extended';
        $uoptions['supported_until'] = '24.12.2038';
        update_option($plugin_slug . '_registration', $uoptions);
        update_option('coderevolution_settings_changed', 2);
    }
    require "update-checker/plugin-update-checker.php";
    $fwdu3dcarPUC = Puc_v4_Factory::buildUpdateChecker("https://wpinitiate.com/auto-update/?action=get_metadata&slug=aiomatic-automatic-ai-content-writer", __FILE__, "aiomatic-automatic-ai-content-writer");
}
function aiomatic_admin_enqueue_all()
{
    $reg_css_code = '.cr_auto_update{background-color:#fff8e5;margin:5px 20px 15px 20px;border-left:4px solid #fff;padding:12px 12px 12px 12px !important;border-left-color:#ffb900;}';
    wp_register_style( 'aiomatic-plugin-reg-style', false );
    wp_enqueue_style( 'aiomatic-plugin-reg-style' );
    wp_add_inline_style( 'aiomatic-plugin-reg-style', $reg_css_code );
}
function aiomatic_add_activation_link($links)
{
    $settings_link = '<a href="admin.php?page=aiomatic_admin_settings">' . esc_html__('Activate Plugin License', 'aiomatic-automatic-ai-content-writer') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

use \Eventviva\ImageResize;

add_action('admin_menu', 'aiomatic_register_my_custom_menu_page');
add_action('network_admin_menu', 'aiomatic_register_my_custom_menu_page');
function aiomatic_register_my_custom_menu_page()
{
    add_menu_page('Aiomatic Automatic AI Content Writer', 'Aiomatic Automatic AI Content Writer', 'manage_options', 'aiomatic_admin_settings', 'aiomatic_admin_settings', plugins_url('images/icon.png', __FILE__));
    $main = add_submenu_page('aiomatic_admin_settings', esc_html__("Main Settings", 'aiomatic-automatic-ai-content-writer'), esc_html__("Main Settings", 'aiomatic-automatic-ai-content-writer'), 'manage_options', 'aiomatic_admin_settings');
    add_action( 'load-' . $main, 'aiomatic_load_all_admin_js' );
    add_action( 'load-' . $main, 'aiomatic_load_main_admin_js' );
    add_action( 'load-' . $main, 'aiomatic_load_playground' );
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['aiomatic_enabled']) && $aiomatic_Main_Settings['aiomatic_enabled'] == 'on') {
        $single = add_submenu_page('aiomatic_admin_settings', esc_html__('Single AI Post Creator', 'aiomatic-automatic-ai-content-writer'), esc_html__('Single AI Post Creator', 'aiomatic-automatic-ai-content-writer'), 'manage_options', 'aiomatic_single_panel', 'aiomatic_single_panel');
        add_action( 'load-' . $single, 'aiomatic_load_admin_js' );
        add_action( 'load-' . $single, 'aiomatic_load_all_admin_js' );
        add_action( 'load-' . $single, 'aiomatic_load_single' );
        $spin = add_submenu_page('aiomatic_admin_settings', esc_html__('Bulk AI Post Creator', 'aiomatic-automatic-ai-content-writer'), esc_html__('Bulk AI Post Creator', 'aiomatic-automatic-ai-content-writer'), 'manage_options', 'aiomatic_items_panel', 'aiomatic_items_panel');
        add_action( 'load-' . $spin, 'aiomatic_load_admin_js' );
        add_action( 'load-' . $spin, 'aiomatic_load_all_admin_js' );
        $auto = add_submenu_page('aiomatic_admin_settings', esc_html__('AI Content Editor', 'aiomatic-automatic-ai-content-writer'), esc_html__('AI Content Editor', 'aiomatic-automatic-ai-content-writer'), 'manage_options', 'aiomatic_spinner_panel', 'aiomatic_spinner_panel');
        add_action( 'load-' . $auto, 'aiomatic_load_post_admin_js' );
        add_action( 'load-' . $auto, 'aiomatic_load_all_admin_js' );
        $shortcodes = add_submenu_page('aiomatic_admin_settings', esc_html__('Shortcodes', 'aiomatic-automatic-ai-content-writer'), esc_html__('Shortcodes', 'aiomatic-automatic-ai-content-writer'), 'manage_options', 'aiomatic_shortcodes_panel', 'aiomatic_shortcodes_panel');
        add_action( 'load-' . $shortcodes, 'aiomatic_load_all_admin_js' );
        add_action( 'load-' . $shortcodes, 'aiomatic_load_playground' );
        $embeddings = add_submenu_page('aiomatic_admin_settings', esc_html__('AI Embeddings', 'aiomatic-automatic-ai-content-writer'), esc_html__('AI Embeddings', 'aiomatic-automatic-ai-content-writer'), 'manage_options', 'aiomatic_embeddings_panel', 'aiomatic_embeddings_panel');
        add_action( 'load-' . $embeddings, 'aiomatic_load_all_admin_js' );
        add_action( 'load-' . $embeddings, 'aiomatic_load_playground' );
        add_action( 'load-' . $embeddings, 'aiomatic_load_embeddings' );
        $training = add_submenu_page('aiomatic_admin_settings', esc_html__('AI Model Training', 'aiomatic-automatic-ai-content-writer'), esc_html__('AI Model Training', 'aiomatic-automatic-ai-content-writer'), 'manage_options', 'aiomatic_openai_training', 'aiomatic_openai_training');
        add_action( 'load-' . $training, 'aiomatic_load_all_admin_js' );
        add_action( 'load-' . $training, 'aiomatic_load_playground' );
        add_action( 'load-' . $training, 'aiomatic_load_training' );
        $playground = add_submenu_page('aiomatic_admin_settings', esc_html__('AI Playground', 'aiomatic-automatic-ai-content-writer'), esc_html__('AI Playground', 'aiomatic-automatic-ai-content-writer'), 'manage_options', 'aiomatic_playground_panel', 'aiomatic_playground_panel');
        add_action( 'load-' . $playground, 'aiomatic_load_all_admin_js' );
        add_action( 'load-' . $playground, 'aiomatic_load_playground' );
        $openai_status = add_submenu_page('aiomatic_admin_settings', esc_html__('Limits & Statistics', 'aiomatic-automatic-ai-content-writer'), esc_html__('Limits & Statistics', 'aiomatic-automatic-ai-content-writer'), 'manage_options', 'aiomatic_openai_status', 'aiomatic_openai_status');
        add_action( 'load-' . $openai_status, 'aiomatic_load_all_admin_js' );
        add_action( 'load-' . $openai_status, 'aiomatic_load_playground' );
        $logs = add_submenu_page('aiomatic_admin_settings', esc_html__("Activity & Logging", 'aiomatic-automatic-ai-content-writer'), esc_html__("Activity & Logging", 'aiomatic-automatic-ai-content-writer'), 'manage_options', 'aiomatic_logs', 'aiomatic_logs');
        add_action( 'load-' . $logs, 'aiomatic_load_all_admin_js' );
    }
}
function aiomatic_load_post_admin_js(){
    add_action('admin_enqueue_scripts', 'aiomatic_admin_load_post_files');
}

function aiomatic_admin_load_post_files()
{
    wp_register_script('aiomatic-submitter-script', plugins_url('scripts/poster.js', __FILE__), false, '1.0.0');
    wp_enqueue_script('aiomatic-submitter-script');
}
function aiomatic_load_admin_js(){
    add_action('admin_enqueue_scripts', 'aiomatic_enqueue_admin_js');
}

function aiomatic_enqueue_admin_js(){
    wp_enqueue_script('aiomatic-footer-script', plugins_url('scripts/footer.js', __FILE__), array('jquery'), false, true);
    $cr_miv = ini_get('max_input_vars');
	if($cr_miv === null || $cr_miv === false || !is_numeric($cr_miv))
	{
        $cr_miv = '9999999';
    }
    $footer_conf_settings = array(
        'max_input_vars' => $cr_miv,
        'plugin_dir_url' => plugin_dir_url(__FILE__),
        'ajaxurl' => admin_url('admin-ajax.php')
    );
    wp_localize_script('aiomatic-footer-script', 'mycustomsettings', $footer_conf_settings);
    wp_register_style('aiomatic-rules-style', plugins_url('styles/aiomatic-rules.css', __FILE__), false, '1.0.0');
    wp_enqueue_style('aiomatic-rules-style');
}
function aiomatic_load_main_admin_js(){
    add_action('admin_enqueue_scripts', 'aiomatic_enqueue_main_admin_js');
}

function aiomatic_enqueue_main_admin_js(){
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    wp_enqueue_script('aiomatic-main-script', plugins_url('scripts/main.js', __FILE__), array('jquery'));
    if(!isset($aiomatic_Main_Settings['best_user']))
    {
        $best_user = '';
    }
    else
    {
        $best_user = $aiomatic_Main_Settings['best_user'];
    }
    if(!isset($aiomatic_Main_Settings['best_password']))
    {
        $best_password = '';
    }
    else
    {
        $best_password = $aiomatic_Main_Settings['best_password'];
    }
    $header_main_settings = array(
        'best_user' => $best_user,
        'best_password' => $best_password
    );
    wp_localize_script('aiomatic-main-script', 'mycustommainsettings', $header_main_settings);
}
function aiomatic_load_single(){
    add_action('admin_enqueue_scripts', 'aiomatic_admin_single');
    wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_style( 'wp-jquery-ui-dialog' );
    wp_enqueue_media();
    wp_enqueue_script( 'aiomatic-media-loader-js', plugins_url( 'scripts/media.js' , __FILE__ ), array('jquery'), '0.1' );
}
function aiomatic_admin_single()
{
    wp_register_script('aiomatic-single-script', plugins_url('scripts/single.js', __FILE__), false, '1.0.0');
    wp_enqueue_script('aiomatic-single-script');
    wp_localize_script('aiomatic-single-script', 'aiomatic_ajax_object', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('openai-single-nonce')
	));
}
function aiomatic_load_all_admin_js(){
    add_action('admin_enqueue_scripts', 'aiomatic_admin_load_files');
}
function aiomatic_load_playground(){
    add_action('admin_enqueue_scripts', 'aiomatic_admin_load_playground');
}
function aiomatic_load_embeddings(){
    add_action('admin_enqueue_scripts', 'aiomatic_admin_load_embeddings');
}
function aiomatic_load_training(){
    add_action('admin_enqueue_scripts', 'aiomatic_admin_load_training');
    add_action('admin_footer', 'aiomatic_admin_footer');
}
function aiomatic_add_rating_link($links)
{
    $settings_link = '<a href="//codecanyon.net/downloads" target="_blank" title="Rate">
            <i class="wdi-rate-stars"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></i></a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter("plugin_action_links_$plugin", 'aiomatic_add_support_link');
function aiomatic_add_support_link($links)
{
    $settings_link = '<a href="//coderevolution.ro/knowledge-base/" target="_blank">' . esc_html__('Support', 'aiomatic-automatic-ai-content-writer') . '</a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter("plugin_action_links_$plugin", 'aiomatic_add_settings_link');
add_filter("plugin_action_links_$plugin", 'aiomatic_add_rating_link');
function aiomatic_add_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=aiomatic_admin_settings">' . esc_html__('Settings', 'aiomatic-automatic-ai-content-writer') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

add_shortcode( 'aiomatic-display-posts', 'aiomatic_display_posts_shortcode' );
function aiomatic_display_posts_shortcode( $atts ) {
	$original_atts = $atts;
	$atts = shortcode_atts( array(
		'author'               => '',
		'category'             => '',
		'category_display'     => '',
		'category_label'       => 'Posted in: ',
		'content_class'        => 'content',
		'date_format'          => '(n/j/Y)',
		'date'                 => '',
		'date_column'          => 'post_date',
		'date_compare'         => '=',
		'date_query_before'    => '',
		'date_query_after'     => '',
		'date_query_column'    => '',
		'date_query_compare'   => '',
		'display_posts_off'    => false,
		'excerpt_length'       => false,
		'excerpt_more'         => false,
		'excerpt_more_link'    => false,
		'exclude_current'      => false,
		'id'                   => false,
		'ignore_sticky_posts'  => false,
		'image_size'           => false,
		'include_author'       => false,
		'include_content'      => false,
		'include_date'         => false,
		'include_excerpt'      => false,
		'include_link'         => true,
		'include_title'        => true,
		'meta_key'             => '',
		'meta_value'           => '',
		'no_posts_message'     => '',
		'offset'               => 0,
		'order'                => 'DESC',
		'orderby'              => 'date',
		'post_parent'          => false,
		'post_status'          => 'publish',
		'post_type'            => 'post',
		'posts_per_page'       => '10',
		'tag'                  => '',
		'tax_operator'         => 'IN',
		'tax_include_children' => true,
		'tax_term'             => false,
		'taxonomy'             => false,
		'time'                 => '',
		'title'                => '',
        'title_color'          => '#000000',
        'excerpt_color'        => '#000000',
        'link_to_source'       => '',
        'title_font_size'      => '100%',
        'excerpt_font_size'    => '100%',
        'read_more_text'       => '',
		'wrapper'              => 'ul',
		'wrapper_class'        => 'display-posts-listing',
		'wrapper_id'           => false,
        'ruleid'               => ''
	), $atts, 'display-posts' );
	if( $atts['display_posts_off'] )
		return;
	$author               = sanitize_text_field( $atts['author'] );
    $ruleid               = sanitize_text_field( $atts['ruleid'] );
	$category             = sanitize_text_field( $atts['category'] );
	$category_display     = 'true' == $atts['category_display'] ? 'category' : sanitize_text_field( $atts['category_display'] );
	$category_label       = sanitize_text_field( $atts['category_label'] );
	$content_class        = array_map( 'sanitize_html_class', ( explode( ' ', $atts['content_class'] ) ) );
	$date_format          = sanitize_text_field( $atts['date_format'] );
	$date                 = sanitize_text_field( $atts['date'] );
	$date_column          = sanitize_text_field( $atts['date_column'] );
	$date_compare         = sanitize_text_field( $atts['date_compare'] );
	$date_query_before    = sanitize_text_field( $atts['date_query_before'] );
	$date_query_after     = sanitize_text_field( $atts['date_query_after'] );
	$date_query_column    = sanitize_text_field( $atts['date_query_column'] );
	$date_query_compare   = sanitize_text_field( $atts['date_query_compare'] );
	$excerpt_length       = intval( $atts['excerpt_length'] );
	$excerpt_more         = sanitize_text_field( $atts['excerpt_more'] );
	$excerpt_more_link    = filter_var( $atts['excerpt_more_link'], FILTER_VALIDATE_BOOLEAN );
	$exclude_current      = filter_var( $atts['exclude_current'], FILTER_VALIDATE_BOOLEAN );
	$id                   = $atts['id'];
	$ignore_sticky_posts  = filter_var( $atts['ignore_sticky_posts'], FILTER_VALIDATE_BOOLEAN );
	$image_size           = sanitize_key( $atts['image_size'] );
	$include_title        = filter_var( $atts['include_title'], FILTER_VALIDATE_BOOLEAN );
	$include_author       = filter_var( $atts['include_author'], FILTER_VALIDATE_BOOLEAN );
	$include_content      = filter_var( $atts['include_content'], FILTER_VALIDATE_BOOLEAN );
	$include_date         = filter_var( $atts['include_date'], FILTER_VALIDATE_BOOLEAN );
	$include_excerpt      = filter_var( $atts['include_excerpt'], FILTER_VALIDATE_BOOLEAN );
	$include_link         = filter_var( $atts['include_link'], FILTER_VALIDATE_BOOLEAN );
	$meta_key             = sanitize_text_field( $atts['meta_key'] );
	$meta_value           = sanitize_text_field( $atts['meta_value'] );
	$no_posts_message     = sanitize_text_field( $atts['no_posts_message'] );
	$offset               = intval( $atts['offset'] );
	$order                = sanitize_key( $atts['order'] );
	$orderby              = sanitize_key( $atts['orderby'] );
	$post_parent          = $atts['post_parent'];
	$post_status          = $atts['post_status'];
	$post_type            = sanitize_text_field( $atts['post_type'] );
	$posts_per_page       = intval( $atts['posts_per_page'] );
	$tag                  = sanitize_text_field( $atts['tag'] );
	$tax_operator         = $atts['tax_operator'];
	$tax_include_children = filter_var( $atts['tax_include_children'], FILTER_VALIDATE_BOOLEAN );
	$tax_term             = sanitize_text_field( $atts['tax_term'] );
	$taxonomy             = sanitize_key( $atts['taxonomy'] );
	$time                 = sanitize_text_field( $atts['time'] );
	$shortcode_title      = sanitize_text_field( $atts['title'] );
    $title_color          = sanitize_text_field( $atts['title_color'] );
    $excerpt_color        = sanitize_text_field( $atts['excerpt_color'] );
    $link_to_source       = sanitize_text_field( $atts['link_to_source'] );
    $excerpt_font_size    = sanitize_text_field( $atts['excerpt_font_size'] );
    $title_font_size      = sanitize_text_field( $atts['title_font_size'] );
    $read_more_text       = sanitize_text_field( $atts['read_more_text'] );
	$wrapper              = sanitize_text_field( $atts['wrapper'] );
	$wrapper_class        = array_map( 'sanitize_html_class', ( explode( ' ', $atts['wrapper_class'] ) ) );
	if( !empty( $wrapper_class ) )
		$wrapper_class = ' class="' . implode( ' ', $wrapper_class ) . '"';
	$wrapper_id = sanitize_html_class( $atts['wrapper_id'] );
	if( !empty( $wrapper_id ) )
		$wrapper_id = ' id="' . esc_html($wrapper_id) . '"';
	$args = array(
		'category_name'       => $category,
		'order'               => $order,
		'orderby'             => $orderby,
		'post_type'           => explode( ',', $post_type ),
		'posts_per_page'      => $posts_per_page,
		'tag'                 => $tag,
	);
	if ( ! empty( $date ) || ! empty( $time ) || ! empty( $date_query_after ) || ! empty( $date_query_before ) ) {
		$initial_date_query = $date_query_top_lvl = array();
		$valid_date_columns = array(
			'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt',
			'comment_date', 'comment_date_gmt'
		);
		$valid_compare_ops = array( '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' );
		$dates = aiomatic_sanitize_date_time( $date );
		if ( ! empty( $dates ) ) {
			if ( is_string( $dates ) ) {
				$timestamp = strtotime( $dates );
				$dates = array(
					'year'   => date( 'Y', $timestamp ),
					'month'  => date( 'm', $timestamp ),
					'day'    => date( 'd', $timestamp ),
				);
			}
			foreach ( $dates as $arg => $segment ) {
				$initial_date_query[ $arg ] = $segment;
			}
		}
		$times = aiomatic_sanitize_date_time( $time, 'time' );
		if ( ! empty( $times ) ) {
			foreach ( $times as $arg => $segment ) {
				$initial_date_query[ $arg ] = $segment;
			}
		}
		$before = aiomatic_sanitize_date_time( $date_query_before, 'date', true );
		if ( ! empty( $before ) ) {
			$initial_date_query['before'] = $before;
		}
		$after = aiomatic_sanitize_date_time( $date_query_after, 'date', true );
		if ( ! empty( $after ) ) {
			$initial_date_query['after'] = $after;
		}
		if ( ! empty( $date_query_column ) && in_array( $date_query_column, $valid_date_columns ) ) {
			$initial_date_query['column'] = $date_query_column;
		}
		if ( ! empty( $date_query_compare ) && in_array( $date_query_compare, $valid_compare_ops ) ) {
			$initial_date_query['compare'] = $date_query_compare;
		}
		if ( ! empty( $date_column ) && in_array( $date_column, $valid_date_columns ) ) {
			$date_query_top_lvl['column'] = $date_column;
		}
		if ( ! empty( $date_compare ) && in_array( $date_compare, $valid_compare_ops ) ) {
			$date_query_top_lvl['compare'] = $date_compare;
		}
		if ( ! empty( $initial_date_query ) ) {
			$date_query_top_lvl[] = $initial_date_query;
		}
		$args['date_query'] = $date_query_top_lvl;
	}
    $args['meta_key'] = 'aiomatic_parent_rule';
    if($ruleid != '')
    {
        $args['meta_value'] = $ruleid;
    }
	if( $ignore_sticky_posts )
		$args['ignore_sticky_posts'] = true;
	 
	if( $id ) {
		$posts_in = array_map( 'intval', explode( ',', $id ) );
		$args['post__in'] = $posts_in;
	}
	if( is_singular() && $exclude_current )
		$args['post__not_in'] = array( get_the_ID() );
	if( !empty( $author ) ) {
		if( 'current' == $author && is_user_logged_in() )
			$args['author_name'] = wp_get_current_user()->user_login;
		elseif( 'current' == $author )
            $unrelevar = false;
			 
		else
			$args['author_name'] = $author;
	}
	if( !empty( $offset ) )
		$args['offset'] = $offset;
	$post_status = explode( ', ', $post_status );
	$validated = array();
	$available = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash', 'any' );
	foreach ( $post_status as $unvalidated )
		if ( in_array( $unvalidated, $available ) )
			$validated[] = $unvalidated;
	if( !empty( $validated ) )
		$args['post_status'] = $validated;
	if ( !empty( $taxonomy ) && !empty( $tax_term ) ) {
		if( 'current' == $tax_term ) {
			global $post;
			$terms = wp_get_post_terms(get_the_ID(), $taxonomy);
			$tax_term = array();
			foreach ($terms as $term) {
				$tax_term[] = $term->slug;
			}
		}else{
			$tax_term = explode( ', ', $tax_term );
		}
		if( !in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ) ) )
			$tax_operator = 'IN';
		$tax_args = array(
			'tax_query' => array(
				array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $tax_term,
					'operator'         => $tax_operator,
					'include_children' => $tax_include_children,
				)
			)
		);
		$count = 2;
		$more_tax_queries = false;
		while(
			isset( $original_atts['taxonomy_' . $count] ) && !empty( $original_atts['taxonomy_' . $count] ) &&
			isset( $original_atts['tax_' . esc_html($count) . '_term'] ) && !empty( $original_atts['tax_' . esc_html($count) . '_term'] )
		):
			$more_tax_queries = true;
			$taxonomy = sanitize_key( $original_atts['taxonomy_' . $count] );
	 		$terms = explode( ', ', sanitize_text_field( $original_atts['tax_' . esc_html($count) . '_term'] ) );
	 		$tax_operator = isset( $original_atts['tax_' . esc_html($count) . '_operator'] ) ? $original_atts['tax_' . esc_html($count) . '_operator'] : 'IN';
	 		$tax_operator = in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ) ) ? $tax_operator : 'IN';
	 		$tax_include_children = isset( $original_atts['tax_' . esc_html($count) . '_include_children'] ) ? filter_var( $atts['tax_' . esc_html($count) . '_include_children'], FILTER_VALIDATE_BOOLEAN ) : true;
	 		$tax_args['tax_query'][] = array(
	 			'taxonomy'         => $taxonomy,
	 			'field'            => 'slug',
	 			'terms'            => $terms,
	 			'operator'         => $tax_operator,
	 			'include_children' => $tax_include_children,
	 		);
			$count++;
		endwhile;
		if( $more_tax_queries ):
			$tax_relation = 'AND';
			if( isset( $original_atts['tax_relation'] ) && in_array( $original_atts['tax_relation'], array( 'AND', 'OR' ) ) )
				$tax_relation = $original_atts['tax_relation'];
			$args['tax_query']['relation'] = $tax_relation;
		endif;
		$args = array_merge_recursive( $args, $tax_args );
	}
	if( $post_parent !== false ) {
		if( 'current' == $post_parent ) {
			global $post;
			$post_parent = get_the_ID();
		}
		$args['post_parent'] = intval( $post_parent );
	}
	$wrapper_options = array( 'ul', 'ol', 'div' );
	if( ! in_array( $wrapper, $wrapper_options ) )
		$wrapper = 'ul';
	$inner_wrapper = 'div' == $wrapper ? 'div' : 'li';
	$listing = new WP_Query( apply_filters( 'display_posts_shortcode_args', $args, $original_atts ) );
	if ( ! $listing->have_posts() ) {
		return apply_filters( 'display_posts_shortcode_no_results', wpautop( $no_posts_message ) );
	}
	$inner = '';
    wp_suspend_cache_addition(true);
	while ( $listing->have_posts() ): $listing->the_post(); global $post;
		$image = $date = $author = $excerpt = $content = '';
		if ( $include_title && $include_link ) {
            if($link_to_source == 'yes')
            {
                $source_url = get_post_meta($post->ID, 'aiomatic_post_url', true);
                if($source_url != '')
                {
                    $title = '<a class="aiomatic_display_title" href="' . esc_url($source_url) . '"><span class="cr_display_span" >' . get_the_title() . '</span></a>';
                }
                else
                {
                    $title = '<a class="aiomatic_display_title" href="' . apply_filters( 'the_permalink', get_permalink() ) . '"><span class="cr_display_span" >' . get_the_title() . '</span></a>';
                }
            }
            else
            {
                $title = '<a class="aiomatic_display_title" href="' . apply_filters( 'the_permalink', get_permalink() ) . '"><span class="cr_display_span" >' . get_the_title() . '</span></a>';
            }
		} elseif( $include_title ) {
			$title = '<span class="aiomatic_display_title" class="cr_display_span">' . get_the_title() . '</span>';
		} else {
			$title = '';
		}
		if ( $image_size && has_post_thumbnail() && $include_link ) {
            if($link_to_source == 'yes')
            {
                $source_url = get_post_meta($post->ID, 'aiomatic_post_url', true);
                if($source_url != '')
                {
                    $image = '<a class="aiomatic_display_image" href="' . esc_url($source_url) . '">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</a> <br/>';
                }
                else
                {
                    $image = '<a class="aiomatic_display_image" href="' . get_permalink() . '">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</a> <br/>';
                }
            }
            else
            {
                $image = '<a class="aiomatic_display_image" href="' . get_permalink() . '">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</a> <br/>';
            }
		} elseif( $image_size && has_post_thumbnail() ) {
			$image = '<span class="aiomatic_display_image">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</span> <br/>';
		}
		if ( $include_date )
			$date = ' <span class="date">' . get_the_date( $date_format ) . '</span>';
		if( $include_author )
			$author = apply_filters( 'display_posts_shortcode_author', ' <span class="aiomatic_display_author">by ' . get_the_author() . '</span>', $original_atts );
		if ( $include_excerpt ) {
			if( $excerpt_length || $excerpt_more || $excerpt_more_link ) {
				$length = $excerpt_length ? $excerpt_length : apply_filters( 'excerpt_length', 55 );
				$more   = $excerpt_more ? $excerpt_more : apply_filters( 'excerpt_more', '' );
				$more   = $excerpt_more_link ? ' <a href="' . get_permalink() . '">' . esc_html($more) . '</a>' : ' ' . esc_html($more);
				if( has_excerpt() && apply_filters( 'display_posts_shortcode_full_manual_excerpt', false ) ) {
					$excerpt = $post->post_excerpt . $more;
				} elseif( has_excerpt() ) {
					$excerpt = wp_trim_words( strip_shortcodes( $post->post_excerpt ), $length, $more );
				} else {
					$excerpt = wp_trim_words( strip_shortcodes( $post->post_content ), $length, $more );
				}
			} else {
				$excerpt = get_the_excerpt();
			}
			$excerpt = ' <br/><br/> <span class="aiomatic_display_excerpt" class="cr_display_excerpt_adv">' . $excerpt . '</span>';
            if($read_more_text != '')
            {
                if($link_to_source == 'yes')
                {
                    $source_url = get_post_meta($post->ID, 'aiomatic_post_url', true);
                    if($source_url != '')
                    {
                        $excerpt .= '<br/><a href="' . esc_url($source_url) . '"><span class="aiomatic_display_excerpt" class="cr_display_excerpt_adv">' . esc_html($read_more_text) . '</span></a>';
                    }
                    else
                    {
                        $excerpt .= '<br/><a href="' . get_permalink() . '"><span class="aiomatic_display_excerpt" class="cr_display_excerpt_adv">' . esc_html($read_more_text) . '</span></a>';
                    }
                }
                else
                {
                    $excerpt .= '<br/><a href="' . get_permalink() . '"><span class="aiomatic_display_excerpt" class="cr_display_excerpt_adv">' . esc_html($read_more_text) . '</span></a>';
                }
            }
		}
		if( $include_content ) {
			add_filter( 'shortcode_atts_display-posts', 'aiomatic_display_posts_off', 10, 3 );
			$content = '<div class="' . implode( ' ', $content_class ) . '">' . apply_filters( 'the_content', get_the_content() ) . '</div>';
			remove_filter( 'shortcode_atts_display-posts', 'aiomatic_display_posts_off', 10, 3 );
		}
		$category_display_text = '';
		if( $category_display && is_object_in_taxonomy( get_post_type(), $category_display ) ) {
			$terms = get_the_terms( get_the_ID(), $category_display );
			$term_output = array();
			foreach( $terms as $term )
				$term_output[] = '<a href="' . get_term_link( $term, $category_display ) . '">' . esc_html($term->name) . '</a>';
			$category_display_text = ' <span class="category-display"><span class="category-display-label">' . esc_html($category_label) . '</span> ' . trim(implode( ', ', $term_output ), ', ') . '</span>';
			$category_display_text = apply_filters( 'display_posts_shortcode_category_display', $category_display_text );
		}
		$class = array( 'listing-item' );
		$class = array_map( 'sanitize_html_class', apply_filters( 'display_posts_shortcode_post_class', $class, $post, $listing, $original_atts ) );
		$output = '<br/><' . esc_html($inner_wrapper) . ' class="' . implode( ' ', $class ) . '">' . $image . $title . $date . $author . $category_display_text . $excerpt . $content . '</' . esc_html($inner_wrapper) . '><br/><br/><hr class="cr_hr_dot"/>';		$inner .= apply_filters( 'display_posts_shortcode_output', $output, $original_atts, $image, $title, $date, $excerpt, $inner_wrapper, $content, $class );
	endwhile; wp_reset_postdata();
    wp_suspend_cache_addition(false);
	$open = apply_filters( 'display_posts_shortcode_wrapper_open', '<' . $wrapper . $wrapper_class . $wrapper_id . '>', $original_atts );
	$close = apply_filters( 'display_posts_shortcode_wrapper_close', '</' . esc_html($wrapper) . '>', $original_atts );
	$return = $open;
	if( $shortcode_title ) {
		$title_tag = apply_filters( 'display_posts_shortcode_title_tag', 'h2', $original_atts );
		$return .= '<' . esc_html($title_tag) . ' class="display-posts-title">' . esc_html($shortcode_title) . '</' . esc_html($title_tag) . '>' . "\n";
	}
	$return .= $inner . $close;
    $reg_css_code = '.cr_hr_dot{border-top: dotted 1px;}.cr_display_span{font-size:' . esc_html($title_font_size) . ';color:' . esc_html($title_color) . ' !important;}.cr_display_excerpt_adv{font-size:' . esc_html($excerpt_font_size) . ';color:' . esc_html($excerpt_color) . ' !important;}';
    wp_register_style( 'aiomatic-display-style', false );
    wp_enqueue_style( 'aiomatic-display-style' );
    wp_add_inline_style( 'aiomatic-display-style', $reg_css_code );
	return $return;
}
function aiomatic_sanitize_date_time( $date_time, $type = 'date', $accepts_string = false ) {
	if ( empty( $date_time ) || ! in_array( $type, array( 'date', 'time' ) ) ) {
		return array();
	}
	$segments = array();
	if (
		true === $accepts_string
		&& ( false !== strpos( $date_time, ' ' ) || false === strpos( $date_time, '-' ) )
	) {
		if ( false !== $timestamp = strtotime( $date_time ) ) {
			return $date_time;
		}
	}
	$parts = array_map( 'absint', explode( 'date' == $type ? '-' : ':', $date_time ) );
	if ( 'date' == $type ) {
		$year = $month = $day = 1;
		if ( count( $parts ) >= 3 ) {
			list( $year, $month, $day ) = $parts;
			$year  = ( $year  >= 1 && $year  <= 9999 ) ? $year  : 1;
			$month = ( $month >= 1 && $month <= 12   ) ? $month : 1;
			$day   = ( $day   >= 1 && $day   <= 31   ) ? $day   : 1;
		}
		$segments = array(
			'year'  => $year,
			'month' => $month,
			'day'   => $day
		);
	} elseif ( 'time' == $type ) {
		$hour = $minute = $second = 0;
		switch( count( $parts ) ) {
			case 3 :
				list( $hour, $minute, $second ) = $parts;
				$hour   = ( $hour   >= 0 && $hour   <= 23 ) ? $hour   : 0;
				$minute = ( $minute >= 0 && $minute <= 60 ) ? $minute : 0;
				$second = ( $second >= 0 && $second <= 60 ) ? $second : 0;
				break;
			case 2 :
				list( $hour, $minute ) = $parts;
				$hour   = ( $hour   >= 0 && $hour   <= 23 ) ? $hour   : 0;
				$minute = ( $minute >= 0 && $minute <= 60 ) ? $minute : 0;
				break;
			default : break;
		}
		$segments = array(
			'hour'   => $hour,
			'minute' => $minute,
			'second' => $second
		);
	}

	return apply_filters( 'display_posts_shortcode_sanitized_segments', $segments, $date_time, $type );
}

function aiomatic_display_posts_off( $out, $pairs, $atts ) {
	$out['display_posts_off'] = apply_filters( 'display_posts_shortcode_inception_override', true );
	return $out;
}
add_shortcode( 'aiomatic-list-posts', 'aiomatic_list_posts' );
function aiomatic_list_posts( $atts ) {
    ob_start();
    extract( shortcode_atts( array (
        'type' => 'any',
        'order' => 'ASC',
        'orderby' => 'title',
        'posts' => 50,
        'posts_per_page' => 50,
        'category' => '',
        'ruleid' => ''
    ), $atts ) );
    $options = array(
        'post_type' => $type,
        'order' => $order,
        'orderby' => $orderby,
        'posts_per_page' => $posts,
        'category_name' => $category,
        'meta_key' => 'aiomatic_parent_rule',
        'meta_value' => $ruleid
    );
    $query = new WP_Query( $options );
    if ( $query->have_posts() ) { ?>
        <ul class="clothes-listing">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <li id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_the_title());?></a>
            </li>
            <?php endwhile;
            wp_reset_postdata(); ?>
        </ul>
    <?php $myvariable = ob_get_clean();
    return $myvariable;
    }
    return '';
}

add_filter('cron_schedules', 'aiomatic_add_cron_schedule');
function aiomatic_add_cron_schedule($schedules)
{
    $schedules['aiomatic_cron'] = array(
        'interval' => 3600,
        'display' => esc_html__('Aiomatic Cron', 'aiomatic-automatic-ai-content-writer')
    );
    $schedules['minutely'] = array(
        'interval' => 60,
        'display' => esc_html__('Once A Minute', 'aiomatic-automatic-ai-content-writer')
    );
    $schedules['weekly']        = array(
        'interval' => 604800,
        'display' => esc_html__('Once Weekly', 'aiomatic-automatic-ai-content-writer')
    );
    $schedules['monthly']       = array(
        'interval' => 2592000,
        'display' => esc_html__('Once Monthly', 'aiomatic-automatic-ai-content-writer')
    );
    return $schedules;
}
function aiomatic_auto_clear_log()
{
    global $wp_filesystem;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
       wp_filesystem($creds);
    }
    if ($wp_filesystem->exists(WP_CONTENT_DIR . '/aiomatic_info.log')) {
        $wp_filesystem->delete(WP_CONTENT_DIR . '/aiomatic_info.log');
    }
}

register_deactivation_hook(__FILE__, 'aiomatic_my_deactivation');
function aiomatic_my_deactivation()
{
    wp_clear_scheduled_hook('aiomaticaction');
    wp_clear_scheduled_hook('aiomaticactionclear');
    $running = array();
    update_option('aiomatic_running_list', $running, false);
}
add_action('aiomaticaction', 'aiomatic_cron');
add_action('aiomaticactionclear', 'aiomatic_auto_clear_log');


add_action('add_meta_boxes', 'aiomatic_add_meta_box');
function aiomatic_add_meta_box()
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['aiomatic_enabled']) && $aiomatic_Main_Settings['aiomatic_enabled'] === 'on') {
        foreach ( get_post_types( '', 'names' ) as $post_type ) {
            add_meta_box('aiomatic_meta_box_function_add', esc_html__('AIomatic AI Content Writer', 'aiomatic-automatic-ai-content-writer'), 'aiomatic_meta_box_function', $post_type, 'advanced', 'default', array('__back_compat_meta_box' => true));
        }
        
    }
}
add_action('wp_ajax_aiomatic_post_now', 'aiomatic_aiomatic_submit_post_callback');
function aiomatic_aiomatic_submit_post_callback()
{
    $run_id = $_POST['id'];
    $wp_post = get_post($run_id);
    if($wp_post != null)
    {
        aiomatic_do_post($wp_post, true);
    }
    die();
}

add_action('wp_ajax_aiomatic_delete_embedding', 'aiomatic_aiomatic_delete_embedding');
function aiomatic_aiomatic_delete_embedding()
{
    $aiomatic_result = array('status' => 'error', 'msg' => 'Something went wrong');
    check_ajax_referer('openai-ajax-nonce', 'nonce');
    if(!isset($_POST['embeddingid']))
    {
        $aiomatic_result['msg'] = 'Field missing: embeddingid';
    }
    else
    {
        $embeddingid = $_POST['embeddingid'];
        if($embeddingid != '' && is_numeric($embeddingid))
        {
            $wp_post = get_post($embeddingid);
            if($wp_post != null)
            {
                require_once(dirname(__FILE__) . "/res/Embeddings.php");
                $embdedding = new Aiomatic_Embeddings();
                $status = $embdedding->aiomatic_delete_embedding($embeddingid);
                $aiomatic_result = $status;
            }
            else
            {
                $aiomatic_result['msg'] = 'No post found with this ID: ' . $embeddingid;
            }
        }
        else
        {
            $aiomatic_result['msg'] = 'Blank embedding ID added';
        }
    }
    wp_send_json($aiomatic_result);
    die();
}

add_action('admin_enqueue_scripts', 'aiomatic_admin_do_post');
function aiomatic_admin_do_post()
{
    wp_enqueue_script('aiomatic-poster-script', plugins_url('scripts/postnow.js', __FILE__), array('jquery'), false, true);
}
function aiomatic_meta_box_function($post)
{
    wp_register_style('aiomatic-browser-style', plugins_url('styles/aiomatic-browser.css', __FILE__), false, '1.0.0');
    wp_enqueue_style('aiomatic-browser-style');
    $ech = '<div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle"><div class="bws_hidden_help_text cr_min_260px">' . esc_html__("Post will be edited respecting the configurations you made in the \'AI Content Editor\' plugin menu section.", 'aiomatic-automatic-ai-content-writer') . '</div></div>&nbsp;<span id="aiomatic_span">Manually Edit/Add AI Content: </span><br/><br/><form id="aiomatic_form"><input class="button button-primary button-large" type="button" name="aiomatic_submit_post" id="aiomatic_submit_post" value="' . esc_html__('Edit/Add AI Content!', 'aiomatic-automatic-ai-content-writer') . '" onclick="aiomatic_post_now(' . $post->ID . ');"/></form><br/><hr/>';
    echo $ech;
}
function aiomatic_wordai_spin_text($title, $content)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['best_user']) || $aiomatic_Main_Settings['best_user'] == '' || !isset($aiomatic_Main_Settings['best_password']) || $aiomatic_Main_Settings['best_password'] == '') {
        aiomatic_log_to_file('Please insert a valid "Wordai" user name and password.');
        return FALSE;
    }
    $titleSeparator   = '[19459000]';
    $quality = 'Readable';
    $html             = $title . ' ' . $titleSeparator . ' ' . $content;
    $email = $aiomatic_Main_Settings['best_user'];
    $pass = $aiomatic_Main_Settings['best_password'];
    $html = urlencode($html);
    $ch = curl_init('https://wai.wordai.com/api/rewrite');
    if($ch === false)
    {
        aiomatic_log_to_file('Failed to init curl in wordai spinning.');
        return FALSE;
    }
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_POST, 1);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, "input=$html&uniqueness=2&rewrite_num=1&return_rewrites=true&email=$email&key=$pass");
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $result = curl_exec($ch);
    if ($result === FALSE) {
        aiomatic_log_to_file('"Wordai" failed to exec curl after auth: ' . curl_error($ch));
        curl_close ($ch);
        return FALSE;
    }
    curl_close ($ch);
    $result = json_decode($result);
    if(!isset($result->rewrites))
    {
        aiomatic_log_to_file('"Wordai" unrecognized response: ' . print_r($result, true));
        return FALSE;
    }
    $result = explode($titleSeparator, $result->rewrites[0]);
    if (count($result) < 2) {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('"Wordai" failed to spin article - titleseparator not found.');
        }
        return FALSE;
    }
    return $result;
}
function aiomatic_chimprewriter_spin_text($title, $content)
{
    $titleSeparator = '[19459000]';
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['best_user']) || $aiomatic_Main_Settings['best_user'] == '' || !isset($aiomatic_Main_Settings['best_password']) || $aiomatic_Main_Settings['best_password'] == '') {
        aiomatic_log_to_file('Please insert a valid "ChimpRewriter" user email and password.');
        return FALSE;
    }
    $usr = $aiomatic_Main_Settings['best_user'];
    $pss = $aiomatic_Main_Settings['best_password'];
    $html = stripslashes($title). ' ' . $titleSeparator . ' ' . stripslashes($content);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT,10);
	curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.com/');
	curl_setopt($ch, CURLOPT_USERAGENT, aiomatic_get_random_user_agent());
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$curlurl="https://api.chimprewriter.com/ChimpRewrite";
	$curlpost="email=" . trim($usr) . "&apikey=" . trim($pss) . "&quality=4&text=" . urlencode($html) . "&aid=none&tagprotect=[|]&phrasequality=3&posmatch=3";
	curl_setopt($ch, CURLOPT_URL, $curlurl);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpost);
	$exec = curl_exec($ch);
    curl_close ($ch);
    if ($exec === FALSE) {
        aiomatic_log_to_file('"ChimpRewriter" failed to exec curl after auth.');
        return FALSE;
    }
	if(stristr($exec, '{'))
    {
		$json = json_decode($exec);
		if($json !== false && isset($json->status))
        {	
			if(isset($json->output) && trim($json->status) == 'success')
            {
				$result = explode($titleSeparator, $json->output);
                if (count($result) < 2) {
                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                        aiomatic_log_to_file('"ChimpRewriter" failed to spin article - titleseparator not found.');
                    }
                    return FALSE;
                }
                $spintax = new AIomatic_Spintax();
                $result[0] = $spintax->Parse(trim($result[0]));
                $result[1] = $spintax->Parse(trim($result[1]));
                return $result;
			}
            else
            {
				aiomatic_log_to_file('Invalid "ChimpRewriter" json response (output missing): ' . $exec);
                return FALSE;
			}
		}
        else
        {
			aiomatic_log_to_file('Invalid "ChimpRewriter" json response: ' . $exec);
            return FALSE;
		}
	}
    else
    {
		aiomatic_log_to_file('Invalid "ChimpRewriter" response: ' . $exec);
        return FALSE;
	}
    return FALSE;
}
function aiomatic_spinnerchief_spin_text($title, $content)
{
    $titleSeparator = '[19459000]';
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['best_user']) || $aiomatic_Main_Settings['best_user'] == '' || !isset($aiomatic_Main_Settings['best_password']) || $aiomatic_Main_Settings['best_password'] == '') {
        aiomatic_log_to_file('Please insert a valid "SpinnerChief" user email and password.');
        return FALSE;
    }
    $za_lang = '';
    if (isset($aiomatic_Main_Settings['spin_lang']) && $aiomatic_Main_Settings['spin_lang'] != '') 
    {
        $za_lang = trim($aiomatic_Main_Settings['spin_lang']);
    }
    $usr = $aiomatic_Main_Settings['best_user'];
    $pss = $aiomatic_Main_Settings['best_password'];
    $html = stripslashes($title). ' ' . $titleSeparator . ' ' . stripslashes($content);
    if(str_word_count($html) > 5000)
    {
        return FALSE;
    }
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.com/');
	curl_setopt($ch, CURLOPT_USERAGENT, aiomatic_get_random_user_agent());
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	$url = "http://api.spinnerchief.com:443/apikey=api2409357d02fa474d8&username=" . $usr . "&password=" . $pss . "&spinfreq=4&Wordscount=5&wordquality=0&tagprotect=[]&original=1&replacetype=0&chartype=1&convertbase=0";
	if($za_lang != '')
    {
        $url .= '&thesaurus=' . $za_lang . '&rule=' . $za_lang;
    }
    else
    {
        $url .= '&thesaurus=English';
    }
	$curlpost=  ( ( $html ) );
	//to fix issue with unicode characters where the API times out
	$curlpost = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $curlpost);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpost); 
 	$result = curl_exec($ch);
	curl_close ($ch);
    if ($result === FALSE) {
        aiomatic_log_to_file('"SpinnerChief" failed to exec curl after auth.');
        return FALSE;
    }
    $result = explode($titleSeparator, $result);
    if (count($result) < 2) {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('"SpinnerChief" failed to spin article - titleseparator not found: ' . print_r($result, true));
        }
        return FALSE;
    }
    $spintax = new AIomatic_Spintax();
    $result[0] = $spintax->Parse(trim($result[0]));
    $result[1] = $spintax->Parse(trim($result[1]));
    return $result;
}

function aiomatic_contentprofessor_spin_text($title, $content)
{
    $titleSeparator = '[19459000]';
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['best_user']) || $aiomatic_Main_Settings['best_user'] == '' || !isset($aiomatic_Main_Settings['best_password']) || $aiomatic_Main_Settings['best_password'] == '') {
        aiomatic_log_to_file('Please insert a valid "ContentProfessor" user email and password.');
        return FALSE;
    }
    $usr = $aiomatic_Main_Settings['best_user'];
    $pss = $aiomatic_Main_Settings['best_password'];
    $article = stripslashes($title). ' ' . $titleSeparator . ' ' . stripslashes($content);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT,10);
	curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.com/');
	curl_setopt($ch, CURLOPT_USERAGENT, aiomatic_get_random_user_agent());
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5); 
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $ctu = 'pro';
	$url = 'http://www.contentprofessor.com/member_pro/api/get_session?format=json&login='.trim($usr).'&password='.trim($pss);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
 	$exec = curl_exec($ch);
	if(!stristr($exec, '{'))
    {
        $ctu = 'free';
        $url = 'http://www.contentprofessor.com/member_free/api/get_session?format=json&login='.trim($usr).'&password='.trim($pss);
        curl_setopt($ch, CURLOPT_URL, $url);
        $exec = curl_exec($ch);	
	}
    if(!stristr($exec, '{'))
    {
        aiomatic_log_to_file('Invalid "ContentProfessor" response: ' . $exec);
        return FALSE;
    }
	$exec = json_decode($exec);
	if(!isset($exec->result) || !isset($exec->result->data->session))
    {
        $ctu = 'free';
		$url = 'http://www.contentprofessor.com/member_free/api/get_session?format=json&login='.trim($usr).'&password='.trim($pss);
        curl_setopt($ch, CURLOPT_URL, $url);
        $exec = curl_exec($ch);
        $exec = json_decode($exec);
    }        
	if(isset($exec->result) && isset($exec->result->data->session))
    {
		$session = $exec->result->data->session;
		$url = "http://www.contentprofessor.com/member_" . $ctu . "/api/include_synonyms?format=json&session=" . $session . "&language=en&limit=5&quality=ideal&synonym_set=global&min_words_count=1&max_words_count=7";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		$curlpost = array('text'=> $article);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpost);
		$exec = curl_exec($ch);
		if(stristr($exec, '{'))
        {
            $exec = json_decode($exec);
			if (isset($exec->result->data->text)) 
            {
				$article  = preg_replace('{<span class="word" id=".*?">(.*?)</span>}su', "$1", $exec->result->data->text);
                $article = explode($titleSeparator, $article);
                if (count($article) < 2) {
                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                        aiomatic_log_to_file('"SpinRewriter" failed to spin article - titleseparator (' . ' ' . $titleSeparator . ' ' . ') not found: ' . $article);
                    }
                    return FALSE;
                }
                $spintax = new AIomatic_Spintax();
                $article[0] = $spintax->Parse(trim($article[0]));
                $article[1] = $spintax->Parse(trim($article[1]));
                return $article;	
			}
            else
            {
                aiomatic_log_to_file('Incorect "ContentProfessor" json response: ' . print_r($exec, true));
                return FALSE;
			}
		}
        else
        {
            aiomatic_log_to_file('Incorect "ContentProfessor" call response: ' . print_r($exec, true));
            return FALSE;
		}
	}
    else
    {
		aiomatic_log_to_file('Incorect "ContentProfessor" login response: ' . print_r($exec, true));
        return FALSE;
	}
}
function aiomatic_spinrewriter_spin_text($title, $content)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['best_user']) || $aiomatic_Main_Settings['best_user'] == '' || !isset($aiomatic_Main_Settings['best_password']) || $aiomatic_Main_Settings['best_password'] == '') {
        aiomatic_log_to_file('Please insert a valid "SpinRewriter" user name and password.');
        return FALSE;
    }
    $titleSeparator = '(19459000)';
    $quality = '50';
    $html = $title . ' ' . $titleSeparator . ' ' . $content;
    $html = preg_replace('/\s+/', ' ', $html);
    $data = array();
    $data['email_address'] = $aiomatic_Main_Settings['best_user'];
    $data['api_key'] = $aiomatic_Main_Settings['best_password'];
    $data['action'] = "unique_variation";
    $data['auto_protected_terms'] = "true";					
    $data['confidence_level'] = "high";							
    $data['auto_sentences'] = "true";							
    $data['auto_paragraphs'] = "false";							
    $data['auto_new_paragraphs'] = "false";						
    $data['auto_sentence_trees'] = "false";						
    $data['use_only_synonyms'] = "true";						
    $data['reorder_paragraphs'] = "false";						
    $data['nested_spintax'] = "false";
    if(str_word_count($html) >= 2500)
    {
        $result = '';
        while($html != '' && $html != ' ')
        {
            $words = explode(" ", $html);
            $first30k = join(" ", array_slice($words, 0, 2500));
            $html = join(" ", array_slice($words, 2500));
            
            $data['text'] = $first30k;	
            $api_response = aiomatic_spinrewriter_api_post($data);
            if ($api_response === FALSE) {
                aiomatic_log_to_file('"SpinRewriter" failed to exec curl after auth.');
                return FALSE;
            }
            $api_response = json_decode($api_response);
            if(!isset($api_response->response) || !isset($api_response->status) || $api_response->status != 'OK')
            {
                if(isset($api_response->status) && $api_response->status == 'ERROR')
                {
                    if(isset($api_response->response) && $api_response->response == 'You can only submit entirely new text for analysis once every 7 seconds.')
                    {
                        $api_response = aiomatic_spinrewriter_api_post($data);
                        if ($api_response === FALSE) {
                            aiomatic_log_to_file('"SpinRewriter" failed to exec curl after auth (after resubmit).');
                            return FALSE;
                        }
                        $api_response = json_decode($api_response);
                        if(!isset($api_response->response) || !isset($api_response->status) || $api_response->status != 'OK')
                        {
                            aiomatic_log_to_file('"SpinRewriter" failed to wait and resubmit spinning: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                            return FALSE;
                        }
                    }
                    else
                    {
                        aiomatic_log_to_file('"SpinRewriter" error response: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                        return FALSE;
                    }
                }
                else
                {
                    aiomatic_log_to_file('"SpinRewriter" error response: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                    return FALSE;
                }
            }
            $spinned = $api_response->response;
            $result .= ' ' . $spinned;
            if($html != '' && $html != ' ')
            {
                sleep(7);
            }
        }
    }
    else
    {
        $data['text'] = $html;	
        $api_response = aiomatic_spinrewriter_api_post($data);
        if ($api_response === FALSE) {
            aiomatic_log_to_file('"SpinRewriter" failed to exec curl after auth.');
            return FALSE;
        }
        $api_response = json_decode($api_response);
        if(!isset($api_response->response) || !isset($api_response->status) || $api_response->status != 'OK')
        {
            if(isset($api_response->status) && $api_response->status == 'ERROR')
            {
                if(isset($api_response->response) && $api_response->response == 'You can only submit entirely new text for analysis once every 7 seconds.')
                {
                    $api_response = aiomatic_spinrewriter_api_post($data);
                    if ($api_response === FALSE) {
                        aiomatic_log_to_file('"SpinRewriter" failed to exec curl after auth (after resubmit).');
                        return FALSE;
                    }
                    $api_response = json_decode($api_response);
                    if(!isset($api_response->response) || !isset($api_response->status) || $api_response->status != 'OK')
                    {
                        aiomatic_log_to_file('"SpinRewriter" failed to wait and resubmit spinning: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                        return FALSE;
                    }
                }
                else
                {
                    aiomatic_log_to_file('"SpinRewriter" error response: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                    return FALSE;
                }
            }
            else
            {
                aiomatic_log_to_file('"SpinRewriter" error response: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                return FALSE;
            }
        }
        $result = $api_response->response;
    }
    $result = explode($titleSeparator, $result);
    if (count($result) < 2) {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('"SpinRewriter" failed to spin article - titleseparator not found: ' . $api_response->response);
        }
        return FALSE;
    }
    return $result;
}
function aiomatic_spinrewriter_api_post($data){
	$data_raw = "";
    
    $GLOBALS['wp_object_cache']->delete('crspinrewriter_spin_time', 'options');
    $spin_time = get_option('crspinrewriter_spin_time', false);
    if($spin_time !== false && is_numeric($spin_time))
    {
        $c_time = time();
        $spassed = $c_time - $spin_time;
        if($spassed < 10 && $spassed >= 0)
        {
            sleep(10 - $spassed);
        }
    }
    update_option('crspinrewriter_spin_time', time());
    
	foreach ($data as $key => $value){
		$data_raw = $data_raw . $key . "=" . urlencode($value) . "&";
	}
	$ch = curl_init();
    if($ch === false)
    {
        return false;
    }
	curl_setopt($ch, CURLOPT_URL, "http://www.spinrewriter.com/action/api");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_raw);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	$response = trim(curl_exec($ch));
	curl_close($ch);
	return $response;
}
function aiomatic_builtin_spin_text($title, $content)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    $titleSeparator         = '[19459000]';
    $text                   = $title . ' ' . $titleSeparator . ' ' . $content;
    if (isset($aiomatic_Main_Settings['exclude_words']) && $aiomatic_Main_Settings['exclude_words'] != '') {
        $excw = explode(',', $aiomatic_Main_Settings['exclude_words']);
        $excw = array_map('trim', $excw);
    }
    else
    {
        $excw = array();
    }
    try {
        $file=file(dirname(__FILE__)  .'/res/synonyms.dat');
		foreach($file as $line){
			$synonyms=explode('|',$line);
			foreach($synonyms as $word){
				if(trim($word) != ''){
                    $must_cont = false;
                    foreach($excw as $exw)
                    {
                        if(strstr($word, $exw) !== false)
                        {
                            $must_cont = true;
                            break;
                        }
                    }
                    if($must_cont == true)
                    {
                        continue;
                    }
                    $word=str_replace('/','\/',$word);
					if(preg_match('/\b'. $word .'\b/u', $text)) {
						$rand = array_rand($synonyms, 1);
						$text = preg_replace('/\b'.$word.'\b/u', trim($synonyms[$rand]), $text);
					}
                    $uword=ucfirst($word);
					if(preg_match('/\b'. $uword .'\b/u', $text)) {
						$rand = array_rand($synonyms, 1);
						$text = preg_replace('/\b'.$uword.'\b/u', ucfirst(trim($synonyms[$rand])), $text);
					}
				}
			}
		}
        $translated = $text;
    }
    catch (Exception $e) {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('Exception thrown in spinText ' . $e);
        }
        return false;
    }
    if (stristr($translated, $titleSeparator)) {
        $contents = explode($titleSeparator, $translated);
        $title    = $contents[0];
        $content  = $contents[1];
    } else {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('Failed to parse spinned content, separator not found');
        }
        return false;
    }
    return array(
        $title,
        $content
    );
}

function aiomatic_cron_schedule()
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['aiomatic_enabled']) && $aiomatic_Main_Settings['aiomatic_enabled'] === 'on') {
        if (!wp_next_scheduled('aiomaticaction')) {
            $rez = wp_schedule_event(time(), 'hourly', 'aiomaticaction');
            if ($rez === FALSE) {
                aiomatic_log_to_file('[Scheduler] Failed to schedule aiomaticaction to aiomatic_cron!');
            }
        }
        
        if (isset($aiomatic_Main_Settings['enable_logging']) && $aiomatic_Main_Settings['enable_logging'] === 'on' && isset($aiomatic_Main_Settings['auto_clear_logs']) && $aiomatic_Main_Settings['auto_clear_logs'] !== 'No') {
            if (!wp_next_scheduled('aiomaticactionclear')) {
                $rez = wp_schedule_event(time(), $aiomatic_Main_Settings['auto_clear_logs'], 'aiomaticactionclear');
                if ($rez === FALSE) {
                    aiomatic_log_to_file('[Scheduler] Failed to schedule aiomaticactionclear to ' . $aiomatic_Main_Settings['auto_clear_logs'] . '!');
                }
                add_option('aiomatic_schedule_time', $aiomatic_Main_Settings['auto_clear_logs']);
            } else {
                if (!get_option('aiomatic_schedule_time')) {
                    wp_clear_scheduled_hook('aiomaticactionclear');
                    $rez = wp_schedule_event(time(), $aiomatic_Main_Settings['auto_clear_logs'], 'aiomaticactionclear');
                    add_option('aiomatic_schedule_time', $aiomatic_Main_Settings['auto_clear_logs']);
                    if ($rez === FALSE) {
                        aiomatic_log_to_file('[Scheduler] Failed to schedule aiomaticactionclear to ' . $aiomatic_Main_Settings['auto_clear_logs'] . '!');
                    }
                } else {
                    $the_time = get_option('aiomatic_schedule_time');
                    if ($the_time != $aiomatic_Main_Settings['auto_clear_logs']) {
                        wp_clear_scheduled_hook('aiomaticactionclear');
                        delete_option('aiomatic_schedule_time');
                        $rez = wp_schedule_event(time(), $aiomatic_Main_Settings['auto_clear_logs'], 'aiomaticactionclear');
                        add_option('aiomatic_schedule_time', $aiomatic_Main_Settings['auto_clear_logs']);
                        if ($rez === FALSE) {
                            aiomatic_log_to_file('[Scheduler] Failed to schedule aiomaticactionclear to ' . $aiomatic_Main_Settings['auto_clear_logs'] . '!');
                        }
                    }
                }
            }
        } else {
            if (!wp_next_scheduled('aiomaticactionclear')) {
                delete_option('aiomatic_schedule_time');
            } else {
                wp_clear_scheduled_hook('aiomaticactionclear');
                delete_option('aiomatic_schedule_time');
            }
        }
    } else {
        if (wp_next_scheduled('aiomaticaction')) {
            wp_clear_scheduled_hook('aiomaticaction');
        }
        
        if (!wp_next_scheduled('aiomaticactionclear')) {
            delete_option('aiomatic_schedule_time');
        } else {
            wp_clear_scheduled_hook('aiomaticactionclear');
            delete_option('aiomatic_schedule_time');
        }
    }
}
function aiomatic_cron()
{
    $GLOBALS['wp_object_cache']->delete('aiomatic_rules_list', 'options');
    if (!get_option('aiomatic_rules_list')) {
        $rules = array();
    } else {
        $rules = get_option('aiomatic_rules_list');
    }
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['run_after']) && $aiomatic_Main_Settings['run_after'] != '' && isset($aiomatic_Main_Settings['run_before']) && $aiomatic_Main_Settings['run_before'] != '') 
    {
        $exit = true;
        $mytime = date("H:i");
        $min_time = $aiomatic_Main_Settings['run_after'];
        $max_time = $aiomatic_Main_Settings['run_before'];
        $date1 = DateTime::createFromFormat('H:i', $mytime);
        $date2 = DateTime::createFromFormat('H:i', $min_time);
        $date3 = DateTime::createFromFormat('H:i', $max_time);
        if ($date1 > $date2 && $date1 < $date3)
        {
            $exit = false;
        }
        if($exit == true)
        {
            return;
        }
    }
    if (!empty($rules)) {
        $cont = 0;
        foreach ($rules as $request => $bundle[]) {
            $bundle_values   = array_values($bundle);
            $myValues        = $bundle_values[$cont];
            $array_my_values = array_values($myValues);for($iji=0;$iji<count($array_my_values);++$iji){if(is_string($array_my_values[$iji])){$array_my_values[$iji]=stripslashes($array_my_values[$iji]);}}
            $schedule        = isset($array_my_values[0]) ? $array_my_values[0] : '24';
            $active          = isset($array_my_values[1]) ? $array_my_values[1] : '0';
            $last_run        = isset($array_my_values[2]) ? $array_my_values[2] : aiomatic_get_date_now();
            if ($active == '1') {
                $now                = aiomatic_get_date_now();
                $nextrun            = aiomatic_add_hour($last_run, $schedule);
                $aiomatic_hour_diff = (int) aiomatic_hour_diff($now, $nextrun);
                if ($aiomatic_hour_diff >= 0) {
                    aiomatic_run_rule($cont);
                }
            }
            $cont = $cont + 1;
        }
    }
    $running = array();
    update_option('aiomatic_running_list', $running);
}

function aiomatic_extractKeyWords($string, $count = 10)
{
    $stopwords = array();
    $string = trim(preg_replace('/\s\s+/iu', '\s', strtolower($string)));
    $string = wp_strip_all_tags($string);
    $matchWords   = array_filter(explode(' ', $string), function($item) use ($stopwords)
    {
        return !($item == '' || in_array($item, $stopwords) || strlen($item) <= 2 || (function_exists('ctype_alnum') && ctype_alnum(trim(str_replace(' ', '', $item))) === FALSE) || is_numeric($item));
    });
    $wordCountArr = array_count_values($matchWords);
    arsort($wordCountArr);
    return array_keys(array_slice($wordCountArr, 0, $count));
}

function aiomatic_log_to_file($str)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['enable_logging']) && $aiomatic_Main_Settings['enable_logging'] == 'on') {
        $d = date("j-M-Y H:i:s e", current_time( 'timestamp' ));
        error_log("[$d] " . $str . "<br/>\r\n", 3, WP_CONTENT_DIR . '/aiomatic_info.log');
    }
}
function aiomatic_delete_all_posts()
{
    $failed                 = false;
    $number                 = 0;
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    $post_list = array();
    $postsPerPage = 50000;
    $paged = 0;
    do
    {
        $postOffset = $paged * $postsPerPage;
        $query = array(
            'post_status' => array(
                'publish',
                'draft',
                'pending',
                'trash',
                'private',
                'future'
            ),
            'post_type' => array(
                'any'
            ),
            'numberposts' => $postsPerPage,
            'meta_key' => 'aiomatic_parent_rule',
            'fields' => 'ids',
            'offset'  => $postOffset
        );
        $got_me = get_posts($query);
        $post_list = array_merge($post_list, $got_me);
        $paged++;
    }while(!empty($got_me));
    wp_suspend_cache_addition(true);
    foreach ($post_list as $post) {
        $index = get_post_meta($post, 'aiomatic_parent_rule', true);
        if (isset($index) && $index !== '') {
            $args             = array(
                'post_parent' => $post
            );
            $post_attachments = get_children($args);
            if (isset($post_attachments) && !empty($post_attachments)) {
                foreach ($post_attachments as $attachment) {
                    wp_delete_attachment($attachment->ID, true);
                }
            }
            $res = wp_delete_post($post, true);
            if ($res === false) {
                $failed = true;
            } else {
                $number++;
            }
        }
    }
    wp_suspend_cache_addition(false);
    if ($failed === true) {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('[PostDelete] Failed to delete all posts!');
        }
    } else {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('[PostDelete] Successfuly deleted ' . esc_html($number) . ' posts!');
        }
    }
}
function aiomatic_replaceContentShortcodes($the_content, $img_attr, $rule_keywords)
{
    $matches = array();
    $i = 0;
    preg_match_all('~%regex\(\s*\"([^"]+?)\s*"\s*[,;]\s*\"([^"]*)\"\s*(?:[,;]\s*\"([^"]*?)\s*\")?(?:[,;]\s*\"([^"]*?)\s*\")?(?:[,;]\s*\"([^"]*?)\s*\")?\)%~si', $the_content, $matches);
    if (is_array($matches) && count($matches) && is_array($matches[0])) {
        for($i = 0; $i < count($matches[0]); $i++)
        {
            if (isset($matches[0][$i])) $fullmatch = $matches[0][$i];
            if (isset($matches[1][$i])) $search_in = aiomatic_replaceContentShortcodes($matches[1][$i], $img_attr, $rule_keywords);
            if (isset($matches[2][$i])) $matchpattern = $matches[2][$i];
            if (isset($matches[3][$i])) $element = $matches[3][$i];
            if (isset($matches[4][$i])) $delimeter = $matches[4][$i];if (isset($matches[5][$i])) $counter = $matches[5][$i];
            if (isset($matchpattern)) {
               if (preg_match('<^[\/#%+~[\]{}][\s\S]*[\/#%+~[\]{}]$>', $matchpattern, $z)) {
                  $ret = preg_match_all($matchpattern, $search_in, $submatches, PREG_PATTERN_ORDER);
               }
               else {
                  $ret = preg_match_all('~'.$matchpattern.'~si', $search_in, $submatches, PREG_PATTERN_ORDER);
               }
            }
            if (isset($submatches)) {
               if (is_array($submatches)) {
                  $empty_elements = array_keys($submatches[0], "");
                  foreach ($empty_elements as $e) {
                     unset($submatches[0][$e]);
                  }
                  $submatches[0] = array_unique($submatches[0]);
                  if (!is_numeric($element)) {
                     $element = 0;
                  }if (!is_numeric($counter)) {
                     $counter = 0;
                  }
                  if(isset($submatches[(int)($element)]))
                  {
                      $matched = $submatches[(int)($element)];
                  }
                  else
                  {
                      $matched = '';
                  }
                  $matched = array_unique((array)$matched);
                  if (empty($delimeter) || $delimeter == 'null') {
                     if (isset($matched[$counter])) $matched = $matched[$counter];
                  }
                  else {
                     $matched = implode($delimeter, $matched);
                  }
                  if (empty($matched)) {
                     $the_content = str_replace($fullmatch, '', $the_content);
                  } else {
                     $the_content = str_replace($fullmatch, $matched, $the_content);
                  }
               }
            }
        }
    }
    $pcxxx = explode('<!- template ->', $the_content);
    $the_content = $pcxxx[array_rand($pcxxx)];
    $the_content = str_replace('%%random_sentence%%', aiomatic_random_sentence_generator(), $the_content);
    $the_content = str_replace('%%random_sentence2%%', aiomatic_random_sentence_generator(false), $the_content);    
    $the_content = aiomatic_replaceSynergyShortcodes($the_content);
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['custom_html'])) {
        $the_content = str_replace('%%custom_html%%', $aiomatic_Main_Settings['custom_html'], $the_content);
    }
    if (isset($aiomatic_Main_Settings['custom_html2'])) {
        $the_content = str_replace('%%custom_html2%%', $aiomatic_Main_Settings['custom_html2'], $the_content);
    }
    $img_attr = str_replace('%%image_source_name%%', '', $img_attr);
    $img_attr = str_replace('%%image_source_url%%', '', $img_attr);
    $img_attr = str_replace('%%image_source_website%%', '', $img_attr);
    $the_content = str_replace('%%royalty_free_image_attribution%%', $img_attr, $the_content);
    $the_content = str_replace('%%keyword_search%%', $rule_keywords, $the_content);
    return $the_content;
}
function aiomatic_replaceTitleShortcodes($the_content)
{
    $matches = array();
    $i = 0;
    preg_match_all('~%regex\(\s*\"([^"]+?)\s*"\s*[,;]\s*\"([^"]*)\"\s*(?:[,;]\s*\"([^"]*?)\s*\")?(?:[,;]\s*\"([^"]*?)\s*\")?(?:[,;]\s*\"([^"]*?)\s*\")?\)%~si', $the_content, $matches);
    if (is_array($matches) && count($matches) && is_array($matches[0])) {
        for($i = 0; $i < count($matches[0]); $i++)
        {
            if (isset($matches[0][$i])) $fullmatch = $matches[0][$i];
            if (isset($matches[1][$i])) $search_in = aiomatic_replaceTitleShortcodes($matches[1][$i]);
            if (isset($matches[2][$i])) $matchpattern = $matches[2][$i];
            if (isset($matches[3][$i])) $element = $matches[3][$i];
            if (isset($matches[4][$i])) $delimeter = $matches[4][$i];if (isset($matches[5][$i])) $counter = $matches[5][$i];
            if (isset($matchpattern)) {
               if (preg_match('<^[\/#%+~[\]{}][\s\S]*[\/#%+~[\]{}]$>', $matchpattern, $z)) {
                  $ret = preg_match_all($matchpattern, $search_in, $submatches, PREG_PATTERN_ORDER);
               }
               else {
                  $ret = preg_match_all('~'.$matchpattern.'~si', $search_in, $submatches, PREG_PATTERN_ORDER);
               }
            }
            if (isset($submatches)) {
               if (is_array($submatches)) {
                  $empty_elements = array_keys($submatches[0], "");
                  foreach ($empty_elements as $e) {
                     unset($submatches[0][$e]);
                  }
                  $submatches[0] = array_unique($submatches[0]);
                  if (!is_numeric($element)) {
                     $element = 0;
                  }if (!is_numeric($counter)) {
                     $counter = 0;
                  }
                  if(isset($submatches[(int)($element)]))
                  {
                      $matched = $submatches[(int)($element)];
                  }
                  else
                  {
                      $matched = '';
                  }
                  $matched = array_unique((array)$matched);
                  if (empty($delimeter) || $delimeter == 'null') {
                     if (isset($matched[$counter])) $matched = $matched[$counter];
                  }
                  else {
                     $matched = implode($delimeter, $matched);
                  }
                  if (empty($matched)) {
                     $the_content = str_replace($fullmatch, '', $the_content);
                  } else {
                     $the_content = str_replace($fullmatch, $matched, $the_content);
                  }
               }
            }
        }
    }
    $pcxxx = explode('<!- template ->', $the_content);
    $the_content = $pcxxx[array_rand($pcxxx)];
    $the_content = str_replace('%%random_sentence%%', aiomatic_random_sentence_generator(), $the_content);
    $the_content = str_replace('%%random_sentence2%%', aiomatic_random_sentence_generator(false), $the_content);
    $the_content = aiomatic_replaceSynergyShortcodes($the_content);
    return $the_content;
}
add_action('wp_ajax_aiomatic_my_action', 'aiomatic_my_action_callback');
function aiomatic_my_action_callback()
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    $failed                 = false;
    $del_id                 = $_POST['id'];
    $how                    = $_POST['how'];
    if($how == 'duplicate')
    {
        $GLOBALS['wp_object_cache']->delete('aiomatic_rules_list', 'options');
        if (!get_option('aiomatic_rules_list')) {
            $rules = array();
        } else {
            $rules = get_option('aiomatic_rules_list');
        }
        if (!empty($rules)) {
            $found            = 0;
            $cont = 0;
            foreach ($rules as $request => $bundle[]) {
                if ($cont == $del_id) {
                    $copy_bundle = $rules[$request];
                    $rules[] = $copy_bundle;
                    $found   = 1;
                    break;
                }
                $cont = $cont + 1;
            }
            if($found == 0)
            {
                aiomatic_log_to_file('aiomatic_rules_list index not found: ' . $del_id);
                echo 'nochange';
                die();
            }
            else
            {
                update_option('aiomatic_rules_list', $rules, false);
                echo 'ok';
                die();
            }
        } else {
            aiomatic_log_to_file('aiomatic_rules_list empty!');
            echo 'nochange';
            die();
        }
        
    }
    $force_delete           = true;
    $number                 = 0;
    if ($how == 'trash') {
        $force_delete = false;
    }
    $post_list = array();
    $postsPerPage = 50000;
    $paged = 0;
    do
    {
        $postOffset = $paged * $postsPerPage;
        $query = array(
            'post_status' => array(
                'publish',
                'draft',
                'pending',
                'trash',
                'private',
                'future'
            ),
            'post_type' => array(
                'any'
            ),
            'numberposts' => $postsPerPage,
            'meta_key' => 'aiomatic_parent_rule',
            'fields' => 'ids',
            'offset'  => $postOffset
        );
        $got_me = get_posts($query);
        $post_list = array_merge($post_list, $got_me);
        $paged++;
    }while(!empty($got_me));
    wp_suspend_cache_addition(true);
    foreach ($post_list as $post) {
        $index = get_post_meta($post, 'aiomatic_parent_rule', true);
        if ($index == $del_id) {
            $args             = array(
                'post_parent' => $post
            );
            $post_attachments = get_children($args);
            if (isset($post_attachments) && !empty($post_attachments)) {
                foreach ($post_attachments as $attachment) {
                    wp_delete_attachment($attachment->ID, true);
                }
            }
            $res = wp_delete_post($post, $force_delete);
            if ($res === false) {
                $failed = true;
            } else {
                $number++;
            }
        }
    }
    wp_suspend_cache_addition(false);
    if ($failed === true) {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('[PostDelete] Failed to delete all posts for rule id: ' . esc_html($del_id) . '!');
        }
        echo 'failed';
    } else {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('[PostDelete] Successfuly deleted ' . esc_html($number) . ' posts for rule id: ' . esc_html($del_id) . '!');
        }
        if ($number == 0) {
            echo 'nochange';
        } else {
            echo 'ok';
        }
    }
    die();
}
add_action('wp_ajax_aiomatic_run_my_action', 'aiomatic_run_my_action_callback');
function aiomatic_run_my_action_callback()
{
    $run_id = $_POST['id'];
    echo aiomatic_run_rule($run_id, 0);
    die();
}

function aiomatic_clearFromList($param)
{
    $GLOBALS['wp_object_cache']->delete('aiomatic_running_list', 'options');
    $running = get_option('aiomatic_running_list');
    if($running !== false)
    {
        $key     = array_search($param, $running);
        if ($key !== FALSE) {
            unset($running[$key]);
            update_option('aiomatic_running_list', $running);
        }
    }
}

function aiomatic_get_web_page($url)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    $content = false;
    if (!isset($aiomatic_Main_Settings['proxy_url']) || $aiomatic_Main_Settings['proxy_url'] == '') 
    {
        $args = array(
        'timeout'     => 10,
        'redirection' => 10,
        'user-agent'  => 'Mozilla/5.0 (Windows NT x.y; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0',
        'blocking'    => true,
        'headers'     => array(),
        'cookies'     => array(),
        'body'        => null,
        'compress'    => false,
        'decompress'  => true,
        'sslverify'   => false,
        'stream'      => false,
        'filename'    => null
        );
        $ret_data            = wp_remote_get(html_entity_decode($url), $args);  
        $response_code       = wp_remote_retrieve_response_code( $ret_data );      
        if ( 200 != $response_code ) {
        } else {
            $content = wp_remote_retrieve_body( $ret_data );
        }
    }
    if($content === false)
    {
        if(function_exists('curl_version') && filter_var($url, FILTER_VALIDATE_URL))
        {
            $user_agent = 'Mozilla/5.0 (Windows NT x.y; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0';
            $options    = array(
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POST => false,
                CURLOPT_USERAGENT => $user_agent,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_AUTOREFERER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0
            );
            $ch         = curl_init($url);
            if ($ch === FALSE) {
                return FALSE;
            }
            if (isset($aiomatic_Main_Settings['proxy_url']) && $aiomatic_Main_Settings['proxy_url'] != '') {
                $prx = explode(',', $aiomatic_Main_Settings['proxy_url']);
                $randomness = array_rand($prx);
                $options[CURLOPT_PROXY] = trim($prx[$randomness]);
                if (isset($aiomatic_Main_Settings['proxy_auth']) && $aiomatic_Main_Settings['proxy_auth'] != '') 
                {
                    $prx_auth = explode(',', $aiomatic_Main_Settings['proxy_auth']);
                    if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
                    {
                        $options[CURLOPT_PROXYUSERPWD] = trim($prx_auth[$randomness]);
                    }
                }
            }
            curl_setopt_array($ch, $options);
            $content = curl_exec($ch);
            curl_close($ch);
        }
        else
        {
            $allowUrlFopen = preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'));
            if ($allowUrlFopen) {
                global $wp_filesystem;
                if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                    include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
                    wp_filesystem($creds);
                }
                return $wp_filesystem->get_contents($url);
            }
        }
    }
    return $content;
}

function aiomatic_get_web_page_api($url, $post_args = array())
{
    if(count($post_args) == 0)
    {
        $post_args = null;
    }
    $content = false;
    $args = array(
    'method'      => 'POST',
    'timeout'     => 999,
    'redirection' => 10,
    'user-agent'  => 'Mozilla/5.0 (Windows NT x.y; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0',
    'blocking'    => true,
    'headers'     => array(),
    'cookies'     => array(),
    'body'        => $post_args,
    'compress'    => false,
    'decompress'  => true,
    'sslverify'   => false,
    'stream'      => false,
    'filename'    => null
    );
    $ret_data            = wp_remote_post($url, $args);  
    $response_code       = wp_remote_retrieve_response_code( $ret_data );     
    if ( 200 != $response_code ) {
    } else {
        $content = wp_remote_retrieve_body( $ret_data );
    }
    if($content === false)
    {
        aiomatic_log_to_file('API response code is: ' . $response_code . ' - ' . $url . ' - ' . print_r($post_args, true));
    }
    return $content;
}


function aiomatic_get_web_page_post($url, $post_args = array(), $headers = array())
{
    if(is_array($post_args) && count($post_args) == 0)
    {
        $post_args = null;
    }
    $content = false;
    $args = array(
        'method'      => 'POST',
        'timeout'     => 999,
        'redirection' => 10,
        'user-agent'  => 'Mozilla/5.0 (Windows NT x.y; Win64; x64; rv:10.0) Gecko/20100101 Firefox/10.0',
        'blocking'    => true,
        'headers'     => $headers,
        'cookies'     => array(),
        'body'        => $post_args,
        'compress'    => false,
        'decompress'  => true,
        'sslverify'   => false,
        'stream'      => false,
        'filename'    => null
    );
    $ret_data            = wp_remote_post($url, $args);  
    $response_code       = wp_remote_retrieve_response_code( $ret_data );     
    if ( 200 != $response_code ) {
    } else {
        $content = wp_remote_retrieve_body( $ret_data );
    }
    if($content === false)
    {
        aiomatic_log_to_file('POST response code is: ' . $response_code . ' - ' . $url . ' - ' . print_r($post_args, true));
    }
    return $content;
}

function aiomatic_utf8_encode($str)
{
    if(function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding'))
    {
        $enc = mb_detect_encoding($str);
        if ($enc !== FALSE) {
            $str = mb_convert_encoding($str, 'UTF-8', $enc);
        } else {
            $str = mb_convert_encoding($str, 'UTF-8');
        }
    }
    return $str;
}

function aiomatic_generate_title($content)
{
    $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
    $content        = preg_replace($regexEmoticons, '', $content);
    $regexSymbols   = '/[\x{1F300}-\x{1F5FF}]/u';
    $content        = preg_replace($regexSymbols, '', $content);
    $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
    $content        = preg_replace($regexTransport, '', $content);
    $regexMisc      = '/[\x{2600}-\x{26FF}]/u';
    $content        = preg_replace($regexMisc, '', $content);
    $regexDingbats  = '/[\x{2700}-\x{27BF}]/u';
    $content        = preg_replace($regexDingbats, '', $content);
    $pattern        = "/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i";
    $replacement    = "";
    $content        = preg_replace($pattern, $replacement, $content);
    $return         = trim(trim(trim(wp_trim_words($content, 14)), '.'), ',');
    return $return;
}
function aiomatic_replaceSynergyShortcodes($the_content)
{
    $regex = '#%%([a-z0-9]+?)(?:_title)?_(\d+?)_(\d+?)%%#';
    $rezz = preg_match_all($regex, $the_content, $matches);
    if ($rezz === FALSE) {
        return $the_content;
    }
    if(isset($matches[1][0]))
    {
        $two_var_functions = array('pdfomatic');
        $three_var_functions = array('bhomatic', 'crawlomatic', 'dmomatic', 'ezinomatic', 'fbomatic', 'flickomatic', 'imguromatic', 'iui', 'instamatic', 'linkedinomatic', 'mediumomatic', 'pinterestomatic', 'echo', 'spinomatic', 'tumblomatic', 'wordpressomatic', 'wpcomomatic', 'youtubomatic', 'mastermind', 'businessomatic');
        $four_var_functions = array('contentomatic', 'quoramatic', 'newsomatic', 'aliomatic', 'amazomatic', 'blogspotomatic', 'bookomatic', 'careeromatic', 'cbomatic', 'cjomatic', 'craigomatic', 'ebayomatic', 'etsyomatic', 'rakutenomatic', 'learnomatic', 'eventomatic', 'gameomatic', 'gearomatic', 'giphyomatic', 'gplusomatic', 'hackeromatic', 'imageomatic', 'midas', 'movieomatic', 'nasaomatic', 'ocartomatic', 'okomatic', 'playomatic', 'recipeomatic', 'redditomatic', 'soundomatic', 'mp3omatic', 'ticketomatic', 'tmomatic', 'trendomatic', 'tuneomatic', 'twitchomatic', 'twitomatic', 'vimeomatic', 'viralomatic', 'vkomatic', 'walmartomatic', 'bestbuyomatic', 'wikiomatic', 'xlsxomatic', 'yelpomatic', 'yummomatic');
        for ($i = 0; $i < count($matches[1]); $i++)
        {
            $replace_me = false;
            if(in_array($matches[1][$i], $four_var_functions))
            {
                $za_function = $matches[1][$i] . '_run_rule';
                if(function_exists($za_function))
                {
                    $xreflection = new ReflectionFunction($za_function);
                    if($xreflection->getNumberOfParameters() >= 4)
                    {  
                        $rule_runner = $za_function($matches[3][$i], $matches[2][$i], 0, 1);
                        if($rule_runner != 'fail' && $rule_runner != 'nochange' && $rule_runner != 'ok' && $rule_runner !== false)
                        {
                            if(is_array($rule_runner))
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[0], $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[1], $the_content);
                            }
                            else
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner, $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', '', $the_content);
                            }
                            $replace_me = true;
                        }
                    }
                    $xreflection = null;
                    unset($xreflection);
                }
            }
            elseif(in_array($matches[1][$i], $three_var_functions))
            {
                $za_function = $matches[1][$i] . '_run_rule';
                if(function_exists($za_function))
                {
                    $xreflection = new ReflectionFunction($za_function);
                    if($xreflection->getNumberOfParameters() >= 3)
                    {
                        $rule_runner = $za_function($matches[3][$i], 0, 1);
                        if($rule_runner != 'fail' && $rule_runner != 'nochange' && $rule_runner != 'ok' && $rule_runner !== false)
                        {
                            if(is_array($rule_runner))
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[0], $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[1], $the_content);
                            }
                            else
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner, $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', '', $the_content);
                            }
                            $replace_me = true;
                        }
                    }
                    $xreflection = null;
                    unset($xreflection);
                }
            }
            elseif(in_array($matches[1][$i], $two_var_functions))
            {
                $za_function = $matches[1][$i] . '_run_rule';
                if(function_exists($za_function))
                {
                    $xreflection = new ReflectionFunction($za_function);
                    if($xreflection->getNumberOfParameters() >= 2)
                    {
                        $rule_runner = $za_function($matches[3][$i], 1);
                        if($rule_runner != 'fail' && $rule_runner != 'nochange' && $rule_runner != 'ok' && $rule_runner !== false)
                        {
                            if(is_array($rule_runner))
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[0], $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[1], $the_content);
                            }
                            else
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner, $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', '', $the_content);
                            }
                            $replace_me = true;
                        }
                    }
                    $xreflection = null;
                    unset($xreflection);
                }
            }
            if($replace_me == false)
            {
                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', '', $the_content);
                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', '', $the_content);
            }
        }
    }
    return $the_content;
}
function aiomatic_utf8ize($arr){
    if (is_array($arr)) {
        foreach ($arr as $k => $v) {
            $arr[$k] = aiomatic_utf8ize($v);
        }
    } else if (is_string ($arr)) {
        return utf8_encode($arr);
    }
    return $arr;
}
function safe_json_encode($value){
    $encoded = json_encode($value);
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            return $encoded;
        case JSON_ERROR_DEPTH:
            throw new Exception('Maximum stack depth exceeded');
        case JSON_ERROR_STATE_MISMATCH:
            throw new Exception('Underflow or the modes mismatch');
        case JSON_ERROR_CTRL_CHAR:
            throw new Exception('Unexpected control character found');
        case JSON_ERROR_SYNTAX:
            throw new Exception('Syntax error, malformed JSON');
        case JSON_ERROR_UTF8:
            $clean = aiomatic_utf8ize($value);
            return safe_json_encode($clean);
        default:
            throw new Exception('Unknown error in json encoding');
    }
}
function aiomatic_encode($text) 
{
    $bpe_tokens = array();
    if(empty($text))
    {
        return $bpe_tokens;
    }
    global $wp_filesystem;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') )
    {
        include_once(ABSPATH . 'wp-admin/includes/file.php');
        $creds = request_filesystem_credentials( site_url() );
        wp_filesystem($creds);
    }
    $raw_chars = $wp_filesystem->get_contents(dirname(__FILE__) . "/res/characters.json");
    $byte_encoder = json_decode($raw_chars, true);
    if(empty($byte_encoder))
    {
        aiomatic_log_to_file('Failed to load characters.json: ' . $raw_chars);
        return $bpe_tokens;
    }
    $rencoder = $wp_filesystem->get_contents(dirname(__FILE__) . "/res/encoder.json");
    $encoder = json_decode($rencoder, true);
    if(empty($encoder))
    {
        aiomatic_log_to_file('Failed to load encoder.json: ' . $rencoder);
        return $bpe_tokens;
    }

    $bpe_file = $wp_filesystem->get_contents(dirname(__FILE__) . "/res/vocab.bpe");
    if(empty($bpe_file))
    {
        aiomatic_log_to_file('Failed to load vocab.bpe');
        return $bpe_tokens;
    }
    $text = str_replace ("\r\n", "\n", $text);
    preg_match_all("#'s|'t|'re|'ve|'m|'ll|'d| ?\p{L}+| ?\p{N}+| ?[^\s\p{L}\p{N}]+|\s+(?!\S)|\s+#u", $text, $matches);
    if(!isset($matches[0]) || count($matches[0]) == 0)
    {
        aiomatic_log_to_file('Failed to match string: ' . $text);
        return $bpe_tokens;
    }
    $lines = preg_split('/\r\n|\r|\n/', $bpe_file);
    $bpe_merges = array();
    $bpe_merges_temp = array_slice($lines, 1, count($lines), true);
    foreach($bpe_merges_temp as $bmt)
    {
        $split_bmt = preg_split('#(\s+)#', $bmt);
        $split_bmt = array_filter($split_bmt, 'aiomatic_myFilter');
        if(count($split_bmt) > 0)
        {
            $bpe_merges[] = $split_bmt;
        }
    }
    $bpe_ranks = aiomatic_dictZip($bpe_merges, range(0, count($bpe_merges) - 1));
    
    $cache = array();
    foreach($matches[0] as $token)
    {
        $new_tokens = array();
        $chars = array();
        $token = utf8_encode($token);
        if(function_exists('mb_strlen'))
        {
            $len = mb_strlen($token, 'UTF-8');
            for ($i = 0; $i < $len; $i++) {
                $chars[] = mb_substr($token, $i, 1, 'UTF-8');
            }
        }
        else
        {
            $chars = str_split($token);
        }
        $result_word = '';
        foreach($chars as $char)
        {
            if(isset($byte_encoder[aiomatic_unichr($char)]))
            {
                $result_word .= $byte_encoder[aiomatic_unichr($char)];
            }
        }
        $new_tokens_bpe = aiomatic_bpe($result_word, $bpe_ranks, $cache);
        $new_tokens_bpe = explode(' ', $new_tokens_bpe);
        foreach($new_tokens_bpe as $x)
        {
            if(isset($encoder[$x]))
            {
                if(isset($new_tokens[$x]))
                {
                    $new_tokens[] = $encoder[$x];
                }
                else
                {
                    $new_tokens[$x] = $encoder[$x];
                }
            }
            else
            {
                if(isset($new_tokens[$x]))
                {
                    $new_tokens[] = $x;
                }
                else
                {
                    $new_tokens[$x] = $x;
                }
            }
        }
        foreach($new_tokens as $ninx => $nval)
        {
            if(isset($bpe_tokens[$ninx]))
            {
                $bpe_tokens[] = $nval;
            }
            else
            {
                $bpe_tokens[$ninx] = $nval;
            }
        }
    }
    return $bpe_tokens;
}

function aiomatic_myFilter($var)
{
    return ($var !== NULL && $var !== FALSE && $var !== '');
}

function aiomatic_unichr($c) 
{
    if (ord($c[0]) >=0 && ord($c[0]) <= 127)
    {
        return ord($c[0]);
    }
    if (ord($c[0]) >= 192 && ord($c[0]) <= 223)
    {
        return (ord($c[0])-192)*64 + (ord($c[1])-128);
    }
    if (ord($c[0]) >= 224 && ord($c[0]) <= 239)
    {
        return (ord($c[0])-224)*4096 + (ord($c[1])-128)*64 + (ord($c[2])-128);
    }
    if (ord($c[0]) >= 240 && ord($c[0]) <= 247)
    {
        return (ord($c[0])-240)*262144 + (ord($c[1])-128)*4096 + (ord($c[2])-128)*64 + (ord($c[3])-128);
    }
    if (ord($c[0]) >= 248 && ord($c[0]) <= 251)
    {
        return (ord($c[0])-248)*16777216 + (ord($c[1])-128)*262144 + (ord($c[2])-128)*4096 + (ord($c[3])-128)*64 + (ord($c[4])-128);
    }
    if (ord($c[0]) >= 252 && ord($c[0]) <= 253)
    {
        return (ord($c[0])-252)*1073741824 + (ord($c[1])-128)*16777216 + (ord($c[2])-128)*262144 + (ord($c[3])-128)*4096 + (ord($c[4])-128)*64 + (ord($c[5])-128);
    }
    if (ord($c[0]) >= 254 && ord($c[0]) <= 255)
    {
        return 0;
    }
    return 0;
}
function aiomatic_dictZip($x, $y)
{
    $result = array();
    $cnt = 0;
    foreach($x as $i)
    {
        if(isset($i[1]) && isset($i[0]))
        {
            $result[$i[0] . ',' . $i[1]] = $cnt;
            $cnt++;
        }
    }
    return $result;
}
function aiomatic_get_pairs($word) 
{
    $pairs = array();
    $prev_char = $word[0];
    for ($i = 1; $i < count($word); $i++) 
    {
        $char = $word[$i];
        $pairs[] = array($prev_char, $char);
        $prev_char = $char;
    }
    return $pairs;
}
function aiomatic_split($str, $len = 1) 
{
    $arr		= [];
    if(function_exists('mb_strlen'))
    {
        $length 	= mb_strlen($str, 'UTF-8');
    }
    else
    {
        $length 	= strlen($str);
    }

    for ($i = 0; $i < $length; $i += $len) 
    {
        if(function_exists('mb_substr'))
        {
            $arr[] = mb_substr($str, $i, $len, 'UTF-8');
        }
        else
        {
            $arr[] = substr($str, $i, $len);
        }
    }
    return $arr;

}
function aiomatic_bpe($token, $bpe_ranks, &$cache)
{
    if(array_key_exists($token, $cache))
    {
        return $cache[$token];
    }
    $word = aiomatic_split($token);
    $init_len = count($word);
    $pairs = aiomatic_get_pairs($word);
    if(!$pairs)
    {
        return $token;
    }
    while (true) 
    {
        $minPairs = array();
        
        foreach($pairs as $pair)
        {
            if(array_key_exists($pair[0] . ','. $pair[1], $bpe_ranks))
            {
                $rank = $bpe_ranks[$pair[0] . ','. $pair[1]];
                $minPairs[$rank] = $pair;
            }
            else
            { 
                $minPairs[10e10] = $pair;
            }
        }
        ksort($minPairs);
        if(!function_exists('array_key_first'))
        {
            function array_key_first(array $array) { foreach ($array as $key => $value) { return $key; } }
        }
        $min_key = array_key_first($minPairs);
        foreach($minPairs as $mpi => $mp)
        {
            if($mpi < $min_key)
            {
                $min_key = $mpi;
            }
        }
        $bigram = $minPairs[$min_key];
        if(!array_key_exists($bigram[0] . ',' . $bigram[1], $bpe_ranks))
        {
            break;
        }
        $first = $bigram[0];
        $second = $bigram[1];
        $new_word = array();
        $i = 0;
        while ($i < count($word)) 
        {
            $j = aiomatic_indexOf($word, $first, $i);
            if ($j === -1) 
            {
                $new_word = array_merge($new_word, array_slice($word, $i, null, true));
                break;
            }
            if($i > $j)
            {
                $slicer = array();
            }
            elseif($j == 0)
            {
                $slicer = array();
            }
            else
            {
                $slicer = array_slice($word, $i, $j - $i, true);
            }
            $new_word = array_merge($new_word, $slicer);
            if(count($new_word) > $init_len)
            {
                break;
            }
            $i = $j;
            if ($word[$i] === $first && $i < count($word) - 1 && $word[$i + 1] === $second) 
            {
                array_push($new_word, $first . $second);
                $i = $i + 2;
            }
            else
            {
                array_push($new_word, $word[$i]);
                $i = $i + 1;
            }
        }
        if($word == $new_word)
        {
            break;
        }
        $word = $new_word;
        if (count($word) === 1) 
        {
            break;
        }
        else
        {
            $pairs = aiomatic_get_pairs($word);
        }
    }
    $word = implode(' ', $word);
    $cache[$token] = $word;
    return $word;
}
function aiomatic_indexOf($arrax, $searchElement, $fromIndex)
{
    $index = 0;
    foreach($arrax as $index => $value)
    {
        if($index < $fromIndex)
        {
            $index++;
            continue;
        }
        if($value == $searchElement)
        {
            return $index;
        }
        $index++;
    }
    return -1;
}
class Aiomatic_keywords{ 
    public static $charset = 'UTF-8';
    public static $banned_words = array('adsbygoogle', 'able', 'about', 'above', 'act', 'add', 'afraid', 'after', 'again', 'against', 'age', 'ago', 'agree', 'all', 'almost', 'alone', 'along', 'already', 'also', 'although', 'always', 'am', 'amount', 'an', 'and', 'anger', 'angry', 'animal', 'another', 'answer', 'any', 'appear', 'apple', 'are', 'arrive', 'arm', 'arms', 'around', 'arrive', 'as', 'ask', 'at', 'attempt', 'aunt', 'away', 'back', 'bad', 'bag', 'bay', 'be', 'became', 'because', 'become', 'been', 'before', 'began', 'begin', 'behind', 'being', 'bell', 'belong', 'below', 'beside', 'best', 'better', 'between', 'beyond', 'big', 'body', 'bone', 'born', 'borrow', 'both', 'bottom', 'box', 'boy', 'break', 'bring', 'brought', 'bug', 'built', 'busy', 'but', 'buy', 'by', 'call', 'came', 'can', 'cause', 'choose', 'close', 'close', 'consider', 'come', 'consider', 'considerable', 'contain', 'continue', 'could', 'cry', 'cut', 'dare', 'dark', 'deal', 'dear', 'decide', 'deep', 'did', 'die', 'do', 'does', 'dog', 'done', 'doubt', 'down', 'during', 'each', 'ear', 'early', 'eat', 'effort', 'either', 'else', 'end', 'enjoy', 'enough', 'enter', 'even', 'ever', 'every', 'except', 'expect', 'explain', 'fail', 'fall', 'far', 'fat', 'favor', 'fear', 'feel', 'feet', 'fell', 'felt', 'few', 'fill', 'find', 'fit', 'fly', 'follow', 'for', 'forever', 'forget', 'from', 'front', 'gave', 'get', 'gives', 'goes', 'gone', 'good', 'got', 'gray', 'great', 'green', 'grew', 'grow', 'guess', 'had', 'half', 'hang', 'happen', 'has', 'hat', 'have', 'he', 'hear', 'heard', 'held', 'hello', 'help', 'her', 'here', 'hers', 'high', 'hill', 'him', 'his', 'hit', 'hold', 'hot', 'how', 'however', 'I', 'if', 'ill', 'in', 'indeed', 'instead', 'into', 'iron', 'is', 'it', 'its', 'just', 'keep', 'kept', 'knew', 'know', 'known', 'late', 'least', 'led', 'left', 'lend', 'less', 'let', 'like', 'likely', 'likr', 'lone', 'long', 'look', 'lot', 'make', 'many', 'may', 'me', 'mean', 'met', 'might', 'mile', 'mine', 'moon', 'more', 'most', 'move', 'much', 'must', 'my', 'near', 'nearly', 'necessary', 'neither', 'never', 'next', 'no', 'none', 'nor', 'not', 'note', 'nothing', 'now', 'number', 'of', 'off', 'often', 'oh', 'on', 'once', 'only', 'or', 'other', 'ought', 'our', 'out', 'please', 'prepare', 'probable', 'pull', 'pure', 'push', 'put', 'raise', 'ran', 'rather', 'reach', 'realize', 'reply', 'require', 'rest', 'run', 'said', 'same', 'sat', 'saw', 'say', 'see', 'seem', 'seen', 'self', 'sell', 'sent', 'separate', 'set', 'shall', 'she', 'should', 'side', 'sign', 'since', 'so', 'sold', 'some', 'soon', 'sorry', 'stay', 'step', 'stick', 'still', 'stood', 'such', 'sudden', 'suppose', 'take', 'taken', 'talk', 'tall', 'tell', 'ten', 'than', 'thank', 'that', 'the', 'their', 'them', 'then', 'there', 'therefore', 'these', 'they', 'this', 'those', 'though', 'through', 'till', 'to', 'today', 'told', 'tomorrow', 'too', 'took', 'tore', 'tought', 'toward', 'tried', 'tries', 'trust', 'try', 'turn', 'two', 'under', 'until', 'up', 'upon', 'us', 'use', 'usual', 'various', 'verb', 'very', 'visit', 'want', 'was', 'we', 'well', 'went', 'were', 'what', 'when', 'where', 'whether', 'which', 'while', 'white', 'who', 'whom', 'whose', 'why', 'will', 'with', 'within', 'without', 'would', 'yes', 'yet', 'you', 'young', 'your', 'br', 'img', 'p','lt', 'gt', 'quot', 'copy');
    public static $min_word_length = 4;
    
    public static function text($text, $length = 160)
    {
        return self::limit_chars(self::clean($text), $length,'',TRUE);
    } 

    public static function keywords($text, $max_keys = 3)
    {
        include (dirname(__FILE__) . "/res/diacritics.php");
        $wordcount = array_count_values(str_word_count(self::clean($text), 1, $diacritics));
        foreach ($wordcount as $key => $value) 
        {
            if ( (strlen($key)<= self::$min_word_length) OR in_array($key, self::$banned_words))
                unset($wordcount[$key]);
        }
        uasort($wordcount,array('self','cmp'));
        $wordcount = array_slice($wordcount,0, $max_keys);
        return implode(' ', array_keys($wordcount));
    } 

    private static function clean($text)
    { 
        $text = html_entity_decode($text,ENT_QUOTES,self::$charset);
        $text = strip_tags($text);
        $text = preg_replace('/\s\s+/', ' ', $text);
        $text = str_replace (array('\r\n', '\n', '+'), ',', $text);
        return trim($text); 
    } 

    private static function cmp($a, $b) 
    {
        if ($a == $b) return 0; 

        return ($a < $b) ? 1 : -1; 
    } 

    private static function limit_chars($str, $limit = 100, $end_char = NULL, $preserve_words = FALSE)
    {
        $end_char = ($end_char === NULL) ? '&#8230;' : $end_char;
        $limit = (int) $limit;
        if (trim($str) === '' OR strlen($str) <= $limit)
            return $str;
        if ($limit <= 0)
            return $end_char;
        if ($preserve_words === FALSE)
            return rtrim(substr($str, 0, $limit)).$end_char;
        if ( ! preg_match('/^.{0,'.$limit.'}\s/us', $str, $matches))
            return $end_char;
        return rtrim($matches[0]).((strlen($matches[0]) === strlen($str)) ? '' : $end_char);
    }
}
add_shortcode("aiomatic-image", "aiomatic_image");
function aiomatic_image($atts)
{
    $seed_expre = isset( $atts['seed_expre'] )? esc_attr($atts['seed_expre']) : '';
    $static_content = isset( $atts['static_content'] )? esc_attr($atts['static_content']) : '';
    $copy_locally = isset( $atts['copy_locally'] )? esc_attr($atts['copy_locally']) : '';
    $image_size = isset( $atts['image_size'] )? esc_attr($atts['image_size']) : '';
    $cache_seconds = isset( $atts['cache_seconds'] )? intval(esc_attr($atts['cache_seconds'])) : 2592000;
    global $post;
    if(empty($seed_expre))
    {
        $exc = get_the_excerpt();
        $exc = trim(strip_tags($exc));
        $cnt = get_the_content();
        $cnt = trim(strip_tags($cnt));
        $cnt = strip_shortcodes($cnt);
        if($cnt != false && !empty($cnt))
        {
            $seed_expre = substr($cnt, 0, 200);
        }
        elseif(!empty($exc) && $exc != false)
        {
            $seed_expre = $exc;
        }
        else
        {
            $seed_expre = get_the_title();
            $seed_expre = trim(strip_tags($seed_expre));
            if($seed_expre == '')
            {
                return '';
            }
        }
    }
    else
    {
        if(isset($post->ID))
        {
            $post_link = get_permalink($post->ID);
            $blog_title       = html_entity_decode(get_bloginfo('title'));
            $author_obj       = get_user_by('id', $post->post_author);
            $user_name        = $author_obj->user_nicename;
            $final_content = $post->post_content;
            $post_title    = $post->post_title;
            $featured_image   = '';
            wp_suspend_cache_addition(true);
            $metas = get_post_custom($post->ID);
            wp_suspend_cache_addition(false);
            if(is_array($metas))
            {
                $rez_meta = aiomatic_preg_grep_keys('#.+?_featured_ima?ge?#i', $metas);
            }
            else
            {
                $rez_meta = array();
            }
            if(count($rez_meta) > 0)
            {
                foreach($rez_meta as $rm)
                {
                    if(isset($rm[0]) && filter_var($rm[0], FILTER_VALIDATE_URL))
                    {
                        $featured_image = $rm[0];
                        break;
                    }
                }
            }
            if($featured_image == '')
            {
                $featured_image = aiomatic_generate_thumbmail($post->ID);;
            }
            if($featured_image == '' && $final_content != '')
            {
                $dom     = new DOMDocument();
                $internalErrors = libxml_use_internal_errors(true);
                $dom->loadHTML($final_content);
                libxml_use_internal_errors($internalErrors);
                $tags      = $dom->getElementsByTagName('img');
                foreach ($tags as $tag) {
                    $temp_get_img = $tag->getAttribute('src');
                    if ($temp_get_img != '') {
                        $temp_get_img = strtok($temp_get_img, '?');
                        $featured_image = rtrim($temp_get_img, '/');
                    }
                }
            }
            $post_cats = '';
            $post_categories = wp_get_post_categories( $post->ID );
            foreach($post_categories as $c){
                $cat = get_category( $c );
                $post_cats .= $cat->name . ',';
            }
            $post_cats = trim($post_cats, ',');
            if($post_cats != '')
            {
                $post_categories = explode(',', $post_cats);
            }
            else
            {
                $post_categories = array();
            }
            if(count($post_categories) == 0)
            {
                $terms = get_the_terms( $post->ID, 'product_cat' );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                    foreach ( $terms as $term ) {
                        $post_categories[] = $term->slug;
                    }
                    $post_cats = implode(',', $post_categories);
                }
                
            }
            $post_tagz = '';
            $post_tags = wp_get_post_tags( $post->ID );
            foreach($post_tags as $t){
                $post_tagz .= $t->name . ',';
            }
            $post_tagz = trim($post_tagz, ',');
            if($post_tagz != '')
            {
                $post_tags = explode(',', $post_tagz);
            }
            else
            {
                $post_tags = array();
            }
            if(count($post_tags) == 0)
            {
                $terms = get_the_terms( $post->ID, 'product_tag' );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                    foreach ( $terms as $term ) {
                        $post_tags[] = $term->slug;
                    }
                    $post_tagz = implode(',', $post_tags);
                }
                
            }
            $post_excerpt = $post->post_excerpt;
            $postID = $post->ID;
        }
        else
        {
            $post_link = '';
            $post_title = '';
            $blog_title = html_entity_decode(get_bloginfo('title'));
            $post_excerpt = '';
            $final_content = '';
            $user_name = '';
            $featured_image = '';
            $post_cats = '';
            $post_tagz = '';
            $postID = '';
        }
        $seed_expre = replaceAIPostShortcodes($seed_expre, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, '', '');
        if (filter_var($seed_expre, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($seed_expre, '.txt'))
        {
            $txt_content = aiomatic_get_web_page($seed_expre);
            if ($txt_content !== FALSE) 
            {
                $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                $txt_content = array_filter($txt_content);
                if(count($txt_content) > 0)
                {
                    $txt_content = $txt_content[array_rand($txt_content)];
                    if(trim($txt_content) != '') 
                    {
                        $seed_expre = $txt_content;
                        $seed_expre = replaceAIPostShortcodes($seed_expre, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, '', '');
                    }
                }
            }
        }
    }
    $md5v = md5($seed_expre . $image_size);
    
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['aiomatic_enabled']) && $aiomatic_Main_Settings['aiomatic_enabled'] == 'on') 
    {
        if(isset($post->ID) && $static_content == 'on')
        {
            $tranzi = false;
        }
        else
        {
            $tranzi = get_transient('aiomatic_image_transient' . $md5v);
        }
        if($tranzi === false)
        {
            if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') {
                aiomatic_log_to_file('You need to add an API key in plugin settings for this shortcode to work.');
                set_transient('aiomatic_image_transient' . $md5v, 'not_working', intval($cache_seconds/10));
                return '';
            }
            else
            {
                $appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
                $appids = array_filter($appids);
                $token = $appids[array_rand($appids)];
            }
            $tranzi = '';
            if(strlen($seed_expre) > 400)
            {
                $seed_expre = substr($seed_expre, 0, 400);
            }
            $aierror = '';
            $temp_get_imgs = aiomatic_generate_ai_image($token, 1, $seed_expre, $image_size, 'shortcodeImage', 0, $aierror);
            if($temp_get_imgs !== false)
            {
                foreach($temp_get_imgs as $tmpimg)
                {
                    $tranzi = $tmpimg;
                }
                if(!empty($tranzi))
                {
                    if($copy_locally == 'on')
                    {
                        $localpath = aiomatic_copy_image_locally($tranzi);
                        if($localpath !== false)
                        {
                            $tranzi = $localpath[0];
                        }
                    }
                    if(!isset($post->ID) || $static_content != 'on')
                    {
                        set_transient('aiomatic_image_transient' . $md5v, $tranzi, $cache_seconds);
                    }
                    else
                    {
                        preg_match_all('#\[aiomatic-image([^\]]*?)\]#i', $post->post_content, $zamatches);
                        if(isset($zamatches[0][0]) && $zamatches[0][0] != '')
                        {
                            $post->post_content = preg_replace('#\[aiomatic-image([^\]]*?)\]#i', '<img src="' . $tranzi . '">', $post->post_content);
                            remove_filter('content_save_pre', 'wp_filter_post_kses');
                            remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                            remove_filter('title_save_pre', 'wp_filter_kses');
                            wp_update_post($post);
                            add_filter('content_save_pre', 'wp_filter_post_kses');
                            add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                            add_filter('title_save_pre', 'wp_filter_kses');
                        }
                        else
                        {
                            set_transient('aiomatic_image_transient' . $md5v, $tranzi, $cache_seconds);
                        }
                    }
                }
            }
            else
            {
                aiomatic_log_to_file('Failed to create an image: ' . $aierror);
                set_transient('aiomatic_image_transient' . $md5v, 'not_working', intval($cache_seconds/10));
            }
        }
    }
    if(!empty($tranzi))
    {
        return '<img src="' . $tranzi . '">';
    }
    return '';
}

add_shortcode("aiomatic-stable-image", "aiomatic_stable_image");
function aiomatic_stable_image($atts)
{
    $seed_expre = isset( $atts['seed_expre'] )? esc_attr($atts['seed_expre']) : '';
    $static_content = isset( $atts['static_content'] )? esc_attr($atts['static_content']) : '';
    $copy_locally = isset( $atts['copy_locally'] )? esc_attr($atts['copy_locally']) : '';
    $image_size = isset( $atts['image_size'] )? esc_attr($atts['image_size']) : '';
    $cache_seconds = isset( $atts['cache_seconds'] )? intval(esc_attr($atts['cache_seconds'])) : 2592000;
    global $post;
    if(empty($seed_expre))
    {
        $exc = get_the_excerpt();
        $exc = trim(strip_tags($exc));
        $cnt = get_the_content();
        $cnt = trim(strip_tags($cnt));
        $cnt = strip_shortcodes($cnt);
        if($cnt != false && !empty($cnt))
        {
            $seed_expre = substr($cnt, 0, 200);
        }
        elseif(!empty($exc) && $exc != false)
        {
            $seed_expre = $exc;
        }
        else
        {
            $seed_expre = get_the_title();
            $seed_expre = trim(strip_tags($seed_expre));
            if($seed_expre == '')
            {
                return '';
            }
        }
    }
    else
    {
        if(isset($post->ID))
        {
            $post_link = get_permalink($post->ID);
            $blog_title       = html_entity_decode(get_bloginfo('title'));
            $author_obj       = get_user_by('id', $post->post_author);
            $user_name        = $author_obj->user_nicename;
            $final_content = $post->post_content;
            $post_title    = $post->post_title;
            $featured_image   = '';
            wp_suspend_cache_addition(true);
            $metas = get_post_custom($post->ID);
            wp_suspend_cache_addition(false);
            if(is_array($metas))
            {
                $rez_meta = aiomatic_preg_grep_keys('#.+?_featured_ima?ge?#i', $metas);
            }
            else
            {
                $rez_meta = array();
            }
            if(count($rez_meta) > 0)
            {
                foreach($rez_meta as $rm)
                {
                    if(isset($rm[0]) && filter_var($rm[0], FILTER_VALIDATE_URL))
                    {
                        $featured_image = $rm[0];
                        break;
                    }
                }
            }
            if($featured_image == '')
            {
                $featured_image = aiomatic_generate_thumbmail($post->ID);;
            }
            if($featured_image == '' && $final_content != '')
            {
                $dom     = new DOMDocument();
                $internalErrors = libxml_use_internal_errors(true);
                $dom->loadHTML($final_content);
                libxml_use_internal_errors($internalErrors);
                $tags      = $dom->getElementsByTagName('img');
                foreach ($tags as $tag) {
                    $temp_get_img = $tag->getAttribute('src');
                    if ($temp_get_img != '') {
                        $temp_get_img = strtok($temp_get_img, '?');
                        $featured_image = rtrim($temp_get_img, '/');
                    }
                }
            }
            $post_cats = '';
            $post_categories = wp_get_post_categories( $post->ID );
            foreach($post_categories as $c){
                $cat = get_category( $c );
                $post_cats .= $cat->name . ',';
            }
            $post_cats = trim($post_cats, ',');
            if($post_cats != '')
            {
                $post_categories = explode(',', $post_cats);
            }
            else
            {
                $post_categories = array();
            }
            if(count($post_categories) == 0)
            {
                $terms = get_the_terms( $post->ID, 'product_cat' );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                    foreach ( $terms as $term ) {
                        $post_categories[] = $term->slug;
                    }
                    $post_cats = implode(',', $post_categories);
                }
                
            }
            $post_tagz = '';
            $post_tags = wp_get_post_tags( $post->ID );
            foreach($post_tags as $t){
                $post_tagz .= $t->name . ',';
            }
            $post_tagz = trim($post_tagz, ',');
            if($post_tagz != '')
            {
                $post_tags = explode(',', $post_tagz);
            }
            else
            {
                $post_tags = array();
            }
            if(count($post_tags) == 0)
            {
                $terms = get_the_terms( $post->ID, 'product_tag' );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                    foreach ( $terms as $term ) {
                        $post_tags[] = $term->slug;
                    }
                    $post_tagz = implode(',', $post_tags);
                }
                
            }
            $post_excerpt = $post->post_excerpt;
            $postID = $post->ID;
        }
        else
        {
            $post_link = '';
            $post_title = '';
            $blog_title = html_entity_decode(get_bloginfo('title'));
            $post_excerpt = '';
            $final_content = '';
            $user_name = '';
            $featured_image = '';
            $post_cats = '';
            $post_tagz = '';
            $postID = '';
        }
        $seed_expre = replaceAIPostShortcodes($seed_expre, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, '', '');
        if (filter_var($seed_expre, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($seed_expre, '.txt'))
        {
            $txt_content = aiomatic_get_web_page($seed_expre);
            if ($txt_content !== FALSE) 
            {
                $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                $txt_content = array_filter($txt_content);
                if(count($txt_content) > 0)
                {
                    $txt_content = $txt_content[array_rand($txt_content)];
                    if(trim($txt_content) != '') 
                    {
                        $seed_expre = $txt_content;
                        $seed_expre = replaceAIPostShortcodes($seed_expre, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, '', '');
                    }
                }
            }
        }
    }
    $md5v = md5($seed_expre . $image_size);
    $local_now = false;
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['aiomatic_enabled']) && $aiomatic_Main_Settings['aiomatic_enabled'] == 'on') 
    {
        if(isset($post->ID) && $static_content == 'on')
        {
            $tranzi = false;
        }
        else
        {
            $tranzi = get_transient('aiomatic_stability_image_transient' . $md5v);
        }
        if($tranzi === false)
        {
            if (!isset($aiomatic_Main_Settings['stability_app_id']) || trim($aiomatic_Main_Settings['stability_app_id']) == '') {
                aiomatic_log_to_file('You need to add an API key in plugin settings for this shortcode to work.');
                set_transient('aiomatic_stability_image_transient' . $md5v, 'not_working', intval($cache_seconds/10));
                return '';
            }
            $tranzi = '';
            if(strlen($seed_expre) > 2000)
            {
                $seed_expre = substr($seed_expre, 0, 2000);
            }
            
            if($image_size == '256x256')
            {
                $width = '512';
                $height = '512';
            }
            elseif($image_size == '512x512')
            {
                $width = '512';
                $height = '512';
            }
            elseif($image_size == '1024x1024')
            {
                $width = '1024';
                $height = '1024';
            }
            else
            {
                $width = '512';
                $height = '512';
            }
            $get_img = aiomatic_generate_stability_image($seed_expre, $height, $width, 'shortcodeStableImage', 0, true);
            if($get_img !== false)
            {
                $tranzi = $get_img;
                if(!empty($tranzi))
                {
                    if($copy_locally == 'on')
                    {
                        $localpath = aiomatic_copy_image_locally('data:image/png;base64,' . $tranzi);
                        if($localpath !== false)
                        {
                            $tranzi = $localpath[0];
                            $local_now = true;
                        }
                    }
                    if(!isset($post->ID) || $static_content != 'on')
                    {
                        set_transient('aiomatic_stability_image_transient' . $md5v, $tranzi, $cache_seconds);
                    }
                    else
                    {
                        preg_match_all('#\[aiomatic-stable-image([^\]]*?)\]#i', $post->post_content, $zamatches);
                        if(isset($zamatches[0][0]) && $zamatches[0][0] != '')
                        {
                            if($local_now == true)
                            {
                                $post->post_content = preg_replace('#\[aiomatic-stable-image([^\]]*?)\]#i', '<img src="' . $tranzi . '">', $post->post_content);
                            }
                            else
                            {
                                $post->post_content = preg_replace('#\[aiomatic-stable-image([^\]]*?)\]#i', '<img src="data:image/png;base64,' . $tranzi . '">', $post->post_content);
                            }
                            remove_filter('content_save_pre', 'wp_filter_post_kses');
                            remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                            remove_filter('title_save_pre', 'wp_filter_kses');
                            wp_update_post($post);
                            add_filter('content_save_pre', 'wp_filter_post_kses');
                            add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                            add_filter('title_save_pre', 'wp_filter_kses');
                        }
                        else
                        {
                            set_transient('aiomatic_stability_image_transient' . $md5v, $tranzi, $cache_seconds);
                        }
                    }
                }
            }
            else
            {
                aiomatic_log_to_file('Failed to generate Stability.AI image.');
                $get_img = '';
            }
        }
    }
    if(!empty($tranzi))
    {
        if($local_now == true)
        {
            return '<img src="' . $tranzi . '">';
        }
        else
        {
            return '<img src="data:image/png;base64,' . $tranzi . '">';
        }
    }
    return '';
}
add_shortcode("aiomatic-article", "aiomatic_article");
function aiomatic_get_all_models()
{
    $all_models = get_option('aiomatic_custom_models', array());
    $all_models = array_merge(AIOMATIC_MODELS, $all_models);
    return $all_models;
}
function aiomatic_article($atts)
{
    $post_link = '';
    $post_title = '';
    $blog_title = html_entity_decode(get_bloginfo('title'));
    $post_excerpt = '';
    $final_content = '';
    $user_name = '';
    $featured_image = '';
    $post_cats = '';
    $post_tagz = '';
    $postID = '';
    $id = '';
    $added_img_list = array();
    $added_images = 0;
    $heading_results = array();
    $seed_expre = isset( $atts['seed_expre'] )? esc_attr($atts['seed_expre']) : '';
    $headings = isset( $atts['headings'] )? esc_attr($atts['headings']) : '';
    $images = isset( $atts['images'] )? esc_attr($atts['images']) : '';
    $videos = isset( $atts['videos'] )? esc_attr($atts['videos']) : '';
    $static_content = isset( $atts['static_content'] )? esc_attr($atts['static_content']) : '';
    $temperature = isset( $atts['temperature'] )? esc_attr($atts['temperature']) : '1';
    $top_p = isset( $atts['top_p'] )? esc_attr($atts['top_p']) : '1';
    $presence_penalty = isset( $atts['presence_penalty'] )? esc_attr($atts['presence_penalty']) : '0';
    $frequency_penalty = isset( $atts['frequency_penalty'] )? esc_attr($atts['frequency_penalty']) : '0';
    $min_char = isset( $atts['min_char'] )? esc_attr($atts['min_char']) : '';
    $max_tokens = isset( $atts['max_tokens'] )? esc_attr($atts['max_tokens']) : '2048';
    $max_seed_tokens = isset( $atts['max_seed_tokens'] )? esc_attr($atts['max_seed_tokens']) : '500';
    $max_continue_tokens = isset( $atts['max_continue_tokens'] )? esc_attr($atts['max_continue_tokens']) : '500';
    $model = isset( $atts['model'] )? esc_attr(trim($atts['model'])) : 'text-davinci-003';
    $cache_seconds = isset( $atts['cache_seconds'] )? intval(esc_attr($atts['cache_seconds'])) : 2592000;
    
    $all_models = aiomatic_get_all_models();
    if(!in_array($model, $all_models))
    {
        $model = 'text-davinci-003';
    }
    $max_tokens = intval($max_tokens);
    if($max_tokens <= 0)
    {
        $max_tokens = 2048;
    }
    if($max_tokens > 2048 && (!stristr($model, 'davinci') || strstr($model, ':ft-') === true))
    {
        $max_tokens = 2048;
    }
    $max_seed_tokens = intval($max_seed_tokens);
    $max_continue_tokens = intval($max_continue_tokens);
    global $post;
    if(empty($seed_expre))
    {
        $exc = get_the_excerpt();
        $exc = trim(strip_tags($exc));
        $cnt = get_the_content();
        $cnt = trim(strip_tags($cnt));
        $cnt = strip_shortcodes($cnt);
        if($cnt != false && !empty($cnt))
        {
            $id = $cnt;
        }
        elseif(!empty($exc) && $exc != false)
        {
            $id = $exc;
        }
        else
        {
            $id = get_the_title();
            $id = trim(strip_tags($id));
            if($id == '')
            {
                return '';
            }
        }
    }
    else
    {
        if(isset($post->ID))
        {
            $post_link = get_permalink($post->ID);
            $blog_title       = html_entity_decode(get_bloginfo('title'));
            $author_obj       = get_user_by('id', $post->post_author);
            $user_name        = $author_obj->user_nicename;
            $final_content = $post->post_content;
            $post_title    = $post->post_title;
            $featured_image   = '';
            wp_suspend_cache_addition(true);
            $metas = get_post_custom($post->ID);
            wp_suspend_cache_addition(false);
            if(is_array($metas))
            {
                $rez_meta = aiomatic_preg_grep_keys('#.+?_featured_ima?ge?#i', $metas);
            }
            else
            {
                $rez_meta = array();
            }
            if(count($rez_meta) > 0)
            {
                foreach($rez_meta as $rm)
                {
                    if(isset($rm[0]) && filter_var($rm[0], FILTER_VALIDATE_URL))
                    {
                        $featured_image = $rm[0];
                        break;
                    }
                }
            }
            if($featured_image == '')
            {
                $featured_image = aiomatic_generate_thumbmail($post->ID);;
            }
            if($featured_image == '' && $final_content != '')
            {
                $dom     = new DOMDocument();
                $internalErrors = libxml_use_internal_errors(true);
                $dom->loadHTML($final_content);
                libxml_use_internal_errors($internalErrors);
                $tags      = $dom->getElementsByTagName('img');
                foreach ($tags as $tag) {
                    $temp_get_img = $tag->getAttribute('src');
                    if ($temp_get_img != '') {
                        $temp_get_img = strtok($temp_get_img, '?');
                        $featured_image = rtrim($temp_get_img, '/');
                    }
                }
            }
            $post_cats = '';
            $post_categories = wp_get_post_categories( $post->ID );
            foreach($post_categories as $c){
                $cat = get_category( $c );
                $post_cats .= $cat->name . ',';
            }
            $post_cats = trim($post_cats, ',');
            if($post_cats != '')
            {
                $post_categories = explode(',', $post_cats);
            }
            else
            {
                $post_categories = array();
            }
            if(count($post_categories) == 0)
            {
                $terms = get_the_terms( $post->ID, 'product_cat' );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                    foreach ( $terms as $term ) {
                        $post_categories[] = $term->slug;
                    }
                    $post_cats = implode(',', $post_categories);
                }
                
            }
            $post_tagz = '';
            $post_tags = wp_get_post_tags( $post->ID );
            foreach($post_tags as $t){
                $post_tagz .= $t->name . ',';
            }
            $post_tagz = trim($post_tagz, ',');
            if($post_tagz != '')
            {
                $post_tags = explode(',', $post_tagz);
            }
            else
            {
                $post_tags = array();
            }
            if(count($post_tags) == 0)
            {
                $terms = get_the_terms( $post->ID, 'product_tag' );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                    foreach ( $terms as $term ) {
                        $post_tags[] = $term->slug;
                    }
                    $post_tagz = implode(',', $post_tags);
                }
                
            }
            $post_excerpt = $post->post_excerpt;
            $postID = $post->ID;
        }
        $seed_expre = replaceAIPostShortcodes($seed_expre, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, '', '');
        if (filter_var($seed_expre, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($seed_expre, '.txt'))
        {
            $txt_content = aiomatic_get_web_page($seed_expre);
            if ($txt_content !== FALSE) 
            {
                $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                $txt_content = array_filter($txt_content);
                if(count($txt_content) > 0)
                {
                    $txt_content = $txt_content[array_rand($txt_content)];
                    if(trim($txt_content) != '') 
                    {
                        $seed_expre = $txt_content;
                        $seed_expre = replaceAIPostShortcodes($seed_expre, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, '', '');
                    }
                }
            }
        }
        $id = $seed_expre;
    }
    $md5v = md5($id . $temperature . $top_p . $presence_penalty . $frequency_penalty . $min_char);
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['player_width']) && $aiomatic_Main_Settings['player_width'] !== '') {
        $width = esc_attr($aiomatic_Main_Settings['player_width']);
    }
    else
    {
        $width = 580;
    }
    if (isset($aiomatic_Main_Settings['player_height']) && $aiomatic_Main_Settings['player_height'] !== '') {
        $height = esc_attr($aiomatic_Main_Settings['player_height']);
    }
    else
    {
        $height = 380;
    }
    if($temperature == '')
    {
        $temperature = 1;
    }
    else
    {
        $temperature = floatval($temperature);
    }
    if($top_p == '')
    {
        $top_p = 1;
    }
    else
    {
        $top_p = floatval($top_p);
    }
    if($frequency_penalty == '')
    {
        $frequency_penalty = 0;
    }
    else
    {
        $frequency_penalty = floatval($frequency_penalty);
    }
    if($presence_penalty == '')
    {
        $presence_penalty = 0;
    }
    else
    {
        $presence_penalty = floatval($presence_penalty);
    }
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['aiomatic_enabled']) && $aiomatic_Main_Settings['aiomatic_enabled'] == 'on') {
        if(isset($post->ID) && $static_content == 'on')
        {
            $tranzi = false;
        }
        else
        {
            $tranzi = get_transient('aiomatic_article_transient' . $md5v);
        }
        $new_post_content = '';
        if($tranzi === false)
        {
            if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') {
                aiomatic_log_to_file('You need to add an API key in plugin settings for this shortcode to work.');
                set_transient('aiomatic_article_transient' . $md5v, 'not_working', intval($cache_seconds/10));
                return '';
            }
            else
            {
                $appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
                $appids = array_filter($appids);
                $token = $appids[array_rand($appids)];
            }
            
            $aicontent = $id;
            if(empty($aicontent))
            {
                return '';
            }
            if(strlen($aicontent) > $max_seed_tokens * 4)
            {
                $aicontent = substr($aicontent, 0, (0-($max_seed_tokens * 4)));
            }
            $aicontent = trim($aicontent);
            $last_char = substr($aicontent, -1);
            if(!ctype_punct($last_char))
            {
                $aicontent .= '.';
            }
            $query_token_count = count(aiomatic_encode($aicontent));
            $available_tokens = $max_tokens - $query_token_count;
            if($available_tokens <= 16)
            {
                $string_len = strlen($aicontent);
                $string_len = $string_len / 2;
                $string_len = intval(0 - $string_len);
                $aicontent = substr($aicontent, 0, $string_len);
                $aicontent = trim($aicontent);
                if(empty($aicontent))
                {
                    aiomatic_log_to_file('Empty API seed expression provided (after processing) ' . print_r($aicontent, true));
                    return '';
                }
                $query_token_count = count(aiomatic_encode($aicontent));
                $available_tokens = $max_tokens - $query_token_count;
            }
            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
            {
                if(aiomatic_is_aiomaticapi_key($token))
                {
                    $api_service = 'AiomaticAPI';
                }
                else
                {
                    $api_service = 'OpenAI';
                }
                aiomatic_log_to_file('Calling ' . $api_service . ' shortcode for text: ' . $aicontent);
            }
            $aierror = '';
            $finish_reason = '';
            $generated_text = aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'shortcodeContentArticle', 0, $finish_reason, $aierror);
            if($generated_text === false)
            {
                aiomatic_log_to_file($aierror);
                set_transient('aiomatic_article_transient' . $md5v, 'not_working', intval($cache_seconds/10));
                return '';
            }
            else
            {
                $new_post_content = ucfirst(trim(nl2br(trim($generated_text))));
            }
            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
            {
                aiomatic_log_to_file('Successfully got API result for shortcode.');
            }
            if($min_char == '')
            {
                $min_char = 0;
            }
            else
            {
                $min_char = intval($min_char);
            }
            $cnt = 1;
            $max_fails = 10;
            $failed_calls = 0;
            if(strlen($new_post_content) < $min_char)
            {
                if($headings != '' && is_numeric($headings))
                {
                    $heading_results = aiomatic_scrape_related_questions($id, $headings, $model, $temperature, $top_p, $presence_penalty, $frequency_penalty, $max_tokens);
                }
            }
            $image_query = '';
            $heading_val = '';
            $temp_post = '';
            $ai_retry = false;
            $ai_continue_title = $post_title;
            $img_attr = '';
            $query_words = '';
            while(strlen(strip_tags($new_post_content)) < $min_char)
            {
                $query_words = '';
                $just_set_fallback = false;
                $image_query = '';
                $heading_val = '';
                if(count($heading_results) > 0)
                {
                    $rand_heading = '';
                    $saverand = array_rand($heading_results);
                    $rand_heading = $heading_results[$saverand];
                    unset($heading_results[$saverand]);
                    if(isset($rand_heading['q']))
                    {
                        $rand_heading['q'] = preg_replace('#^\d+\.([\s\S]*)#i', '$1', $rand_heading['q']);
                        $heading_val = '<h2>' . $rand_heading['q'] . '</h2>' . '<span>' . $rand_heading['a'];
                        $image_query = $rand_heading['q'];
                    }
                }
                if($heading_val == '')
                { 
                    $temp_post = trim($new_post_content);
                }
                else
                {
                    $temp_post = trim($heading_val);
                }
                if(strlen($temp_post) > $max_continue_tokens * 4)
                {
                    $negative_contiue_tokens = 0 - ($max_continue_tokens * 4);
                    $newaicontent = substr($temp_post, $negative_contiue_tokens);
                }
                else
                {
                    $newaicontent = $temp_post;
                }
                $add_me_to_text = '';
                if($ai_retry == true)
                {
                    $just_set_fallback = true;
                    if (isset($aiomatic_Main_Settings['alternate_continue']) && $aiomatic_Main_Settings['alternate_continue'] == 'on')
                    {
                        $newaicontent = $newaicontent . ' ' . $ai_continue_title;
                    }
                    else
                    {
                        $aierror = '';
                        $finish_reason = '';
                        $generated_text = aiomatic_generate_text($token, $model, 'Write a People Also Asked question related to "' . $ai_continue_title . '"', 2048, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'shortcodeHeadingArticle', 0, $finish_reason, $aierror);
                        if($generated_text === false)
                        {
                            aiomatic_log_to_file('Similarity finding failed: ' . $aierror);
                            $newaicontent = $aicontent;
                        }
                        else
                        {
                            $newaicontent = ucfirst(trim(nl2br(trim($generated_text))));
                            if(empty($newaicontent))
                            {
                                $newaicontent = $aicontent;
                            }
                            else
                            {
                                $newaicontent = preg_replace('#^\d+\.([\s\S]*)#i', '$1', $newaicontent);
                                $add_me_to_text = '<h3>' . $newaicontent . '</h3> ';
                                $ai_continue_title = $newaicontent;
                            }
                        }
                    }
                }
                $ai_retry = false;
                $newaicontent = trim($newaicontent);
                $query_token_count = count(aiomatic_encode($newaicontent));
                $available_tokens = $max_tokens - $query_token_count;
                if($available_tokens <= 16)
                {
                    $string_len = strlen($newaicontent);
                    $string_len = $string_len / 2;
                    $string_len = intval(0 - $string_len);
                    $newaicontent = substr($newaicontent, 0, $string_len);
                    $newaicontent = trim($newaicontent);
                    if(empty($newaicontent))
                    {
                        aiomatic_log_to_file('Empty API seed expression provided (after processing) ' . print_r($newaicontent, true));
                        break;
                    }
                    $query_token_count = count(aiomatic_encode($newaicontent));
                    $available_tokens = $max_tokens - $query_token_count;
                }
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                    if(aiomatic_is_aiomaticapi_key($token))
                    {
                        $api_service = 'AiomaticAPI';
                    }
                    else
                    {
                        $api_service = 'OpenAI';
                    }
                    aiomatic_log_to_file('Calling ' . $api_service . ' again (' . $cnt . ') from shortcode, to meet minimum character limit: ' . $min_char . ' - current char count: ' . strlen(strip_tags($new_post_content)));
                }
                $aiwriter = '';
                $aierror = '';
                $finish_reason = '';
                $generated_text = aiomatic_generate_text($token, $model, $newaicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'shortcodeContentArticle', 0, $finish_reason, $aierror);
                if($generated_text === false)
                {
                    aiomatic_log_to_file($aierror);
                    break;
                }
                else
                {
                    $aiwriter = $add_me_to_text . ucfirst(trim(nl2br(trim($generated_text))));
                }
                if($aiwriter == '')
                {
                    $ai_retry = true;
                    if($just_set_fallback == true)
                    {
                        aiomatic_log_to_file('Ending execution, already retried once');
                        break;
                    }
                    continue;
                }
                $add_my_image = '';
                $temp_get_img = '';
                if($images != '' && is_numeric($images) && $images > $added_images)
                {
                    if($image_query == '')
                    {
                        $image_query = $temp_post;
                    }
                    if(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'textrazor')
                    {
                        if(isset($aiomatic_Main_Settings['textrazor_key']) && trim($aiomatic_Main_Settings['textrazor_key']) != '')
                        {
                            try
                            {
                                if(!class_exists('TextRazor'))
                                {
                                    require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                }
                                TextRazorSettings::setApiKey(trim($aiomatic_Main_Settings['textrazor_key']));
                                $textrazor = new TextRazor();
                                $textrazor->addExtractor('entities');
                                $response = $textrazor->analyze($image_query);
                                if (isset($response['response']['entities'])) 
                                {
                                    foreach ($response['response']['entities'] as $entity) 
                                    {
                                        $query_words = '';
                                        if(isset($entity['entityEnglishId']))
                                        {
                                            $query_words = $entity['entityEnglishId'];
                                        }
                                        else
                                        {
                                            $query_words = $entity['entityId'];
                                        }
                                        if($query_words != '')
                                        {
                                            $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 10, false);
                                            if(!empty($z_img))
                                            {
                                                $added_images++;
                                                $added_img_list[] = $z_img;
                                                $temp_get_img = $z_img;
                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                    aiomatic_log_to_file('Royalty Free Image Generated with help of TextRazor (kw: "' . $query_words . '"): ' . $z_img);
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            catch(Exception $e)
                            {
                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                    aiomatic_log_to_file('Failed to search for keywords using TextRazor (2): ' . $e->getMessage());
                                }
                            }
                        }
                    }
                    elseif(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'openai')
                    {
                        if(isset($aiomatic_Main_Settings['keyword_prompts']) && trim($aiomatic_Main_Settings['keyword_prompts']) != '')
                        {
                            if(isset($aiomatic_Main_Settings['keyword_model']) && $aiomatic_Main_Settings['keyword_model'] != '')
                            {
                                $kw_model = $aiomatic_Main_Settings['keyword_model'];
                            }
                            else
                            {
                                $kw_model = 'text-davinci-003';
                            }
                            $title_ai_command = trim($aiomatic_Main_Settings['keyword_prompts']);
                            $title_ai_command = preg_split('/\r\n|\r|\n/', $title_ai_command);
                            $title_ai_command = array_filter($title_ai_command);
                            if(count($title_ai_command) > 0)
                            {
                                $title_ai_command = $title_ai_command[array_rand($title_ai_command)];
                            }
                            else
                            {
                                $title_ai_command = '';
                            }
                            $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                            if(!empty($title_ai_command))
                            {
                                $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                            }
                            $title_ai_command = trim($title_ai_command);
                            if (filter_var($title_ai_command, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($title_ai_command, '.txt'))
                            {
                                $txt_content = aiomatic_get_web_page($title_ai_command);
                                if ($txt_content !== FALSE) 
                                {
                                    $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                    $txt_content = array_filter($txt_content);
                                    if(count($txt_content) > 0)
                                    {
                                        $txt_content = $txt_content[array_rand($txt_content)];
                                        if(trim($txt_content) != '') 
                                        {
                                            $title_ai_command = $txt_content;
                                            $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                            $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                        }
                                    }
                                }
                            }
                            if(empty($title_ai_command))
                            {
                                aiomatic_log_to_file('Empty API keyword extractor seed expression provided!');
                            }
                            else
                            {
                                $title_ai_command = 'Extract a comma separated list of relevant keywords from the text: ' . trim(strip_tags($post_title));
                                if(strlen($title_ai_command) > $max_seed_tokens * 4)
                                {
                                    $title_ai_command = substr($title_ai_command, 0, (0 - ($max_seed_tokens * 4)));
                                }
                                $title_ai_command = trim($title_ai_command);
                                if(empty($title_ai_command))
                                {
                                    aiomatic_log_to_file('Empty API title seed expression provided(1)! ' . print_r($title_ai_command, true));
                                }
                                else
                                {
                                    $query_token_count = count(aiomatic_encode($title_ai_command));
                                    $available_tokens = $max_tokens - $query_token_count;
                                    if($available_tokens <= 16)
                                    {
                                        $string_len = strlen($title_ai_command);
                                        $string_len = $string_len / 2;
                                        $string_len = intval(0 - $string_len);
                                        $title_ai_command = substr($title_ai_command, 0, $string_len);
                                        $title_ai_command = trim($title_ai_command);
                                        $query_token_count = count(aiomatic_encode($title_ai_command));
                                        $available_tokens = $max_tokens - $query_token_count;
                                    }
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                    {
                                        if(aiomatic_is_aiomaticapi_key($token))
                                        {
                                            $api_service = 'AiomaticAPI';
                                        }
                                        else
                                        {
                                            $api_service = 'OpenAI';
                                        }
                                        aiomatic_log_to_file('Calling ' . $api_service . ' for title text: ' . $title_ai_command);
                                    }
                                    $aierror = '';
                                    $finish_reason = '';
                                    $generated_text = aiomatic_generate_text($token, $kw_model, $title_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'shortcodeKeywordArticle', 0, $finish_reason, $aierror);
                                    if($generated_text === false)
                                    {
                                        aiomatic_log_to_file('Keyword generator error: ' . $aierror);
                                        $ai_title = '';
                                    }
                                    else
                                    {
                                        $ai_title = trim(trim(trim(trim($generated_text), '.'), ' “”‘’"\''));
                                        $ai_titles = explode(',', $ai_title);
                                        foreach($ai_titles as $query_words)
                                        {
                                            $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, trim($query_words), $img_attr, 10, false);
                                            if(!empty($z_img))
                                            {
                                                $added_images++;
                                                $added_img_list[] = $z_img;
                                                $temp_get_img = $z_img;
                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                    aiomatic_log_to_file('Royalty Free Image Generated with help of AI (kw: "' . $query_words . '"): ' . $z_img);
                                                }
                                                break;
                                            }
                                        }
                                    }
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                    {
                                        if(aiomatic_is_aiomaticapi_key($token))
                                        {
                                            $api_service = 'AiomaticAPI';
                                        }
                                        else
                                        {
                                            $api_service = 'OpenAI';
                                        }
                                        aiomatic_log_to_file('Successfully got API keyword result from ' . $api_service . ': ' . $ai_title);
                                    }
                                }
                            }
                        }
                    }
                    if(empty($temp_get_img))
                    {
                        $keyword_class = new Aiomatic_keywords();
                        $query_words = $keyword_class->keywords($image_query, 2);
                        $temp_img_attr = '';
                        $temp_get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $temp_img_attr, 10, false);
                        if($temp_get_img == '' || $temp_get_img === false)
                        {
                            $query_words = $keyword_class->keywords($image_query, 1);
                            $temp_get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $temp_img_attr, 20, false);
                            if($temp_get_img == '' || $temp_get_img === false)
                            {
                                $temp_get_img = '';
                            }
                            else
                            {
                                if(!in_array($temp_get_img, $added_img_list))
                                {
                                    $added_images++;
                                    $added_img_list[] = $temp_get_img;
                                }
                                else
                                {
                                    $temp_get_img = '';
                                }
                            }
                        }
                        else
                        {
                            if(!in_array($temp_get_img, $added_img_list))
                            {
                                $added_images++;
                                $added_img_list[] = $temp_get_img;
                            }
                            else
                            {
                                $temp_get_img = '';
                            }
                        }
                    }
                }
                if($temp_get_img != '')
                {
                    $add_my_image = '<img class="aiomatic_image_class" src="' . $temp_get_img . '" alt="' . $query_words . '"><br/>';
                }
                if($heading_val == '')
                {
                    if($add_my_image == '')
                    {
                        $add_my_image = ' ';
                    }
                    $new_post_content .= $add_my_image . trim(nl2br($aiwriter));
                }
                else
                {
                    $new_post_content .= $add_my_image . $heading_val . ' ' . trim(nl2br($aiwriter)) . '</span>';
                }
                sleep(1);
                $cnt++;
            }
            if (isset($aiomatic_Main_Settings['swear_filter']) && $aiomatic_Main_Settings['swear_filter'] == 'on') 
            {
                require_once(dirname(__FILE__) . "/res/swear.php");
                $new_post_content = aiomatic_filterwords($new_post_content);
            }
            if ($videos == 'on') 
            {
                if (isset($aiomatic_Main_Settings['yt_app_id']) && trim($aiomatic_Main_Settings['yt_app_id']) != '') {
                    $items = array();
                    $vid_id = '';
                    $za_app = explode(',', $aiomatic_Main_Settings['yt_app_id']);
                    $za_app = trim($za_app[array_rand($za_app)]);
                    $feed_uri = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&key=' . $za_app;
                    $feed_uri .= '&maxResults=10';
                    $image_query = $query_words;
                    if($image_query == '')
                    {
                        if($temp_post != '')
                        {
                            $image_query = $temp_post;
                        }
                        else
                        {
                            $image_query = $id;
                        }
                    }
                    $feed_uri .= '&q='.urlencode(trim(stripslashes(str_replace('&quot;', '"', $image_query))));
                    $ch  = curl_init();
                    if ($ch !== FALSE) {
                        if (isset($aiomatic_Main_Settings['proxy_url']) && $aiomatic_Main_Settings['proxy_url'] != '') {
                            curl_setopt($ch, CURLOPT_PROXY, $aiomatic_Main_Settings['proxy_url']);
                            if (isset($aiomatic_Main_Settings['proxy_auth']) && $aiomatic_Main_Settings['proxy_auth'] != '') {
                                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $aiomatic_Main_Settings['proxy_auth']);
                            }
                        }
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                        curl_setopt($ch, CURLOPT_HTTPGET, 1);
                        curl_setopt($ch, CURLOPT_REFERER, get_site_url());
                        curl_setopt($ch, CURLOPT_URL, $feed_uri);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        $exec = curl_exec($ch);
                        curl_close($ch);
                        if ($exec !== FALSE) {
                            $json  = json_decode($exec);
                            if(isset($json->items))
                            {
                                $items = $json->items;
                                if (count($items) == 0) 
                                {
                                    $feed_uri = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&key=' . $za_app;
                                    $feed_uri .= '&maxResults=10';
                                    $keyword_class = new Aiomatic_keywords();
                                    $image_query = $keyword_class->keywords($image_query, 2);
                                    $feed_uri .= '&q='.urlencode(trim(stripslashes(str_replace('&quot;', '"', $image_query))));
                                    $ch  = curl_init();
                                    if ($ch !== FALSE) {
                                        if (isset($aiomatic_Main_Settings['proxy_url']) && $aiomatic_Main_Settings['proxy_url'] != '') {
                                            curl_setopt($ch, CURLOPT_PROXY, $aiomatic_Main_Settings['proxy_url']);
                                            if (isset($aiomatic_Main_Settings['proxy_auth']) && $aiomatic_Main_Settings['proxy_auth'] != '') {
                                                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $aiomatic_Main_Settings['proxy_auth']);
                                            }
                                        }
                                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                                        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                                        curl_setopt($ch, CURLOPT_HTTPGET, 1);
                                        curl_setopt($ch, CURLOPT_REFERER, get_site_url());
                                        curl_setopt($ch, CURLOPT_URL, $feed_uri);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                        $exec = curl_exec($ch);
                                        curl_close($ch);
                                        if ($exec === FALSE) {
                                            $json  = json_decode($exec);
                                            if(isset($json->items))
                                            {
                                                $items = $json->items;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if(isset($items[0]->id->videoId))
                    {
                        $rand_ind = array_rand($items);
                        $video_id = $items[$rand_ind]->id->videoId;
                        $new_post_content .= '<br/><br/><div class="automaticx-video-container"><iframe allow="autoplay" width="' . $width . '" height="' . $height . '" src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
                    }
                }
                else
                {
                    if($image_query == '')
                    {
                        if($temp_post != '')
                        {
                            $image_query = $temp_post;
                        }
                        else
                        {
                            $image_query = $id;
                        }
                    }
                    $new_post_content .= aiomatic_get_youtube_video(trim(stripslashes(str_replace('&quot;', '"', $image_query))), '');
                }
            }
            if(!isset($post->ID) || $static_content != 'on')
            {
                set_transient('aiomatic_article_transient' . $md5v, $new_post_content, $cache_seconds);
                $tranzi = $new_post_content;
            }
            else
            {
                preg_match_all('#\[aiomatic-article([^\]]*?)\]#i', $post->post_content, $zamatches);
                if(isset($zamatches[0][0]) && $zamatches[0][0] != '')
                {
                    $tranzi = '';
                    $post->post_content = preg_replace('#\[aiomatic-article([^\]]*?)\]#i', $new_post_content, $post->post_content);
                    remove_filter('content_save_pre', 'wp_filter_post_kses');
                    remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                    remove_filter('title_save_pre', 'wp_filter_kses');
                    $post_updated = wp_update_post($post);
                    add_filter('content_save_pre', 'wp_filter_post_kses');
                    add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                    add_filter('title_save_pre', 'wp_filter_kses');
                }
                else
                {
                    set_transient('aiomatic_article_transient' . $md5v, $new_post_content, $cache_seconds);
                    $tranzi = $new_post_content;
                }
            }
        }
        elseif($tranzi == 'not_working')
        {
            return '';
        }
        return $tranzi;
    }
    else
    {
        return '';
    }
}

function aiomatic_assign_featured_image($attach_id, $post_id)
{
    if ($attach_id === 0 || !is_numeric($attach_id)) {
        return false;
    }
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    $res2 = set_post_thumbnail($post_id, $attach_id);
    if ($res2 === FALSE) {
        return false;
    }
    return get_the_post_thumbnail_url($attach_id);
}
function aiomatic_scrape_related_questions($query, $headings, $model, $temperature, $top_p, $presence_penalty, $frequency_penalty, $max_tokens)
{
    $headings = intval($headings);
    $results = array();
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['serpapi_auth']) && trim($aiomatic_Main_Settings['serpapi_auth']) != '')
    {
        $serpapi = 'https://serpapi.com/search.json?q=' . urlencode($query) . '&api_key=' . trim($aiomatic_Main_Settings['serpapi_auth']);
        $html_data = aiomatic_get_web_page($serpapi);
        if ($html_data !== FALSE) 
        {
            $json = json_decode($html_data);
            if ($json !== FALSE) 
            {
                if(isset($json->related_questions[0]->question))
                {
                    foreach($json->related_questions as $qq)
                    {
                        $answer = '';
                        if(isset($qq->snippet))
                        {
                            $answer = $qq->snippet;
                        }
                        elseif(isset($qq->title))
                        {
                            $answer = $qq->title;
                            if(isset($qq->list))
                            {
                                $answer .= ' ';
                                foreach($qq->list as $ll)
                                {
                                    $answer .= trim($ll, ' .') . ', ';
                                }
                                $answer = trim($answer, ' ,');
                            }
                        }
                        $rec = array("q" => $qq->question, "a" => $answer, "l" => $qq->link);
                        if(!isset($results[$qq->question]))
                        {
                            $results[$qq->question] = $rec;
                        }
                        if(count($results) >= $headings)
                        {
                            break;
                        }
                    }
                    if(count($results) > 0 && count($results) < $headings)
                    {
                        $ok = true;
                        while($ok && count($results) < $headings)
                        {
                            $last_elem = end($results);
                            sleep(1);
                            $serpapi = 'https://serpapi.com/search.json?q=' . urlencode($last_elem['q']);
                            $html_data = aiomatic_get_web_page($serpapi);
                            if ($html_data !== FALSE) 
                            {
                                $json = json_decode($html_data);
                                if ($json !== FALSE) 
                                {
                                    if(isset($json->related_questions[0]->question))
                                    {
                                        $count_before = count($results);
                                        foreach($json->related_questions as $qq)
                                        {
                                            $answer = '';
                                            if(isset($qq->snippet))
                                            {
                                                $answer = $qq->snippet;
                                            }
                                            elseif(isset($qq->title))
                                            {
                                                $answer = $qq->title;
                                                if(isset($qq->list))
                                                {
                                                    $answer .= ' ';
                                                    foreach($qq->list as $ll)
                                                    {
                                                        $answer .= trim($ll, ' .') . ', ';
                                                    }
                                                    $answer = trim($answer, ' ,');
                                                }
                                            }
                                            $rec = array("q" => $qq->question, "a" => $answer, "l" => $qq->link);
                                            if(!isset($results[$qq->question]))
                                            {
                                                $results[$qq->question] = $rec;
                                            }
                                            if(count($results) >= $headings)
                                            {
                                                break;
                                            }
                                        }
                                        $count_after = count($results);
                                        if($count_after == $count_before)
                                        {
                                            $ok = false;
                                        }
                                    }
                                    else
                                    {
                                        $ok = false;
                                    }
                                }
                                else
                                {
                                    $ok = false;
                                }
                            }
                            else
                            {
                                $ok = false;
                            }
                        }
                    }
                }
            }
        }
    }
    if(count($results) < $headings)
    {
        require_once (dirname(__FILE__) . "/res/simple_html_dom.php");
        $url = "https://www.bing.com/search?q=" . urlencode($query);
        $related_expre = 'div[data-tag="RelatedQnA.Item"]';
        $html_data = aiomatic_get_web_page($url);
        if ($html_data !== FALSE) 
        {
            $html_dom_original_html = aiomatic_str_get_html($html_data);
            if($html_dom_original_html !== false && method_exists($html_dom_original_html, 'find'))
            {
                $ret = $html_dom_original_html->find( trim($related_expre) );
                foreach ($ret as $element ) 
                {
                    $q = $element->find("div",0);
                    if($q !== null)
                    {
                        $q = $q->children(0);
                        if($q !== null)
                        {
                            $q = $q->children(0);
                            if($q !== null)
                            {
                                $q = $q->children(0);
                                if($q !== null)
                                {
                                    $q = $q->plaintext;
                                }
                            }
                        }
                    }
                    $a = $element->find("div",0);
                    if($a !== null)
                    {
                        $a = $a->children(1);
                        if($a !== null)
                        {
                            $a = $a->children(0);
                            if($a !== null)
                            {
                                $a = $a->children(0);
                                if($a !== null)
                                {
                                    $a = $a->children(0);
                                    if($a !== null)
                                    {
                                        $a = $a->plaintext;
                                    }
                                }
                            }
                        }
                    }
                    $l = $element->find("div",0);
                    if($l !== null)
                    {
                        $l = $l->children(1);
                        if($l !== null)
                        {
                            $l = $l->children(0);
                            if($l !== null)
                            {
                                $l = $l->children(0);
                                if($l !== null)
                                {
                                    $l = $l->children(1);
                                    if($l !== null)
                                    {
                                        $l = $l->children(0);
                                        if($l !== null)
                                        {
                                            $l = $l->children(0);
                                            if($l !== null)
                                            {
                                                $l = $l->children(0);
                                                if($l !== null)
                                                {
                                                    $l = $l->children(0);
                                                    if($l !== null)
                                                    {
                                                        $l = $l->getAttribute('href');
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if($q !== null && $a !== null && $l !== null)
                    {
                        $rec = array("q" => $q, "a" => $a, "l" => $l);
                        if(!isset($results[$q]))
                        {
                            $results[$q] = $rec;
                        }
                        if(count($results) >= $headings)
                        {
                            break;
                        }
                    }
                    else
                    {
                        break;
                    }
                }
                $html_dom_original_html->clear();
                unset($html_dom_original_html);
            }
        }
        if(count($results) > 0 && count($results) < $headings)
        {
            $ok = true;
            while($ok && count($results) < $headings)
            {
                $last_elem = end($results);
                sleep(1);
                $url = "https://www.bing.com/search?q=" . urlencode($last_elem['q']);
                $html_data = aiomatic_get_web_page($url);
                if ($html_data !== FALSE) 
                {
                    $html_dom_original_html = aiomatic_str_get_html($html_data);
                    if($html_dom_original_html !== false && method_exists($html_dom_original_html, 'find'))
                    {
                        $ret = $html_dom_original_html->find( trim($related_expre) );
                        if(!is_array($ret) || count($ret) == 0)
                        {
                            $html_dom_original_html->clear();
                            unset($html_dom_original_html);
                            break;
                        }
                        $count_before = count($results);
                        foreach ($ret as $element ) 
                        {
                            $q = $element->find("div",0);
                            if($q !== null)
                            {
                                $q = $q->children(0);
                                if($q !== null)
                                {
                                    $q = $q->children(0);
                                    if($q !== null)
                                    {
                                        $q = $q->children(0);
                                        if($q !== null)
                                        {
                                            $q = $q->plaintext;
                                        }
                                    }
                                }
                            }
                            $a = $element->find("div",0);
                            if($a !== null)
                            {
                                $a = $a->children(1);
                                if($a !== null)
                                {
                                    $a = $a->children(0);
                                    if($a !== null)
                                    {
                                        $a = $a->children(0);
                                        if($a !== null)
                                        {
                                            $a = $a->children(0);
                                            if($a !== null)
                                            {
                                                $a = $a->plaintext;
                                            }
                                        }
                                    }
                                }
                            }
                            $l = $element->find("div",0);
                            if($l !== null)
                            {
                                $l = $l->children(1);
                                if($l !== null)
                                {
                                    $l = $l->children(0);
                                    if($l !== null)
                                    {
                                        $l = $l->children(0);
                                        if($l !== null)
                                        {
                                            $l = $l->children(1);
                                            if($l !== null)
                                            {
                                                $l = $l->children(0);
                                                if($l !== null)
                                                {
                                                    $l = $l->children(0);
                                                    if($l !== null)
                                                    {
                                                        $l = $l->children(0);
                                                        if($l !== null)
                                                        {
                                                            $l = $l->children(0);
                                                            if($l !== null)
                                                            {
                                                                $l = $l->getAttribute('href');
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if($q !== null && $a !== null && $l !== null)
                            {
                                $rec = array("q" => $q, "a" => $a, "l" => $l);
                                if(!isset($results[$q]))
                                {
                                    $results[$q] = $rec;
                                }
                                if(count($results) >= $headings)
                                {
                                    break;
                                }
                            }
                            else
                            {
                                break;
                            }
                        }
                        $count_after = count($results);
                        if($count_after == $count_before)
                        {
                            $ok = false;
                        }
                        $html_dom_original_html->clear();
                        unset($html_dom_original_html);
                    }
                    else
                    {
                        $ok = false;
                    }
                }
                else
                {
                    $ok == false;
                }
            }
        }
    }
    if(count($results) < $headings)
    {
        $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
        if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
        {
            return $results;
        }
        $appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
        $appids = array_filter($appids);
        $token = $appids[array_rand($appids)];
        $headings_ai_command = 'Write ' . $headings . ' PAA related questions, each on a new line, for the title: "' . $query . '"';
        $query_token_count = count(aiomatic_encode($headings_ai_command));
        $available_tokens = $max_tokens - $query_token_count;
        if($available_tokens <= 16)
        {
            $string_len = strlen($headings_ai_command);
            $string_len = $string_len / 2;
            $string_len = intval(0 - $string_len);
            $headings_ai_command = substr($headings_ai_command, 0, $string_len);
            $headings_ai_command = trim($headings_ai_command);
            $query_token_count = count(aiomatic_encode($headings_ai_command));
            $available_tokens = $max_tokens - $query_token_count;
        }
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
        {
            if(aiomatic_is_aiomaticapi_key($token))
            {
                $api_service = 'AiomaticAPI';
            }
            else
            {
                $api_service = 'OpenAI';
            }
            aiomatic_log_to_file('Calling ' . $api_service . ' for headings generator: ' . $headings_ai_command);
        }
        $aierror = '';
        $finish_reason = '';
        $generated_text = aiomatic_generate_text($token, $model, $headings_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'shortcodeHeadingsArticle', 0, $finish_reason, $aierror);
        if($generated_text === false)
        {
            aiomatic_log_to_file('Title generator error: ' . $aierror);
            return $results;
        }
        else
        {
            $generated_text = ucfirst(trim(trim(trim(trim($generated_text), '.'), ' “”‘’"\'')));
            $generated_text_arr = preg_split('/\r\n|\r|\n/', $generated_text);
            $generated_text_arr = array_filter($generated_text_arr);
            foreach($generated_text_arr as $gen_head)
            {
                $rec = array("q" => $gen_head, "a" => '', "l" => '');
                if(!isset($results[$gen_head]))
                {
                    $results[$gen_head] = $rec;
                }
                if(count($results) >= $headings)
                {
                    break;
                }
            }
        }
    }

    return $results;
}
function aiomatic_is_aiomaticapi_key($token)
{
    $token_prepro = explode('_', $token);
    if(isset($token_prepro[1]) && strlen($token_prepro[1]) > 10 && is_numeric($token_prepro[0]))
    {
        return true;
    }
    return false;
}
function aiomatic_is_trained_model($model)
{
    if(stristr($model, ':ft-') !== false)
    {
        return true;
    }
    return false;
}
function aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, $retry_count, &$finish_reason, &$error)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    $embeddings_enabled = false;
    if(stristr($env, 'singlePostWriter') !== false)
    {
        if(isset($aiomatic_Main_Settings['embeddings_single']) && $aiomatic_Main_Settings['embeddings_single'] == 'on')
        {
            $embeddings_enabled = true;
        }
    }
    elseif(stristr($env, 'shortcodeHeadingsArticle') !== false)
    {
        if(isset($aiomatic_Main_Settings['embeddings_related']) && $aiomatic_Main_Settings['embeddings_related'] == 'on')
        {
            $embeddings_enabled = true;
        }
    }
    elseif(stristr($env, 'shortcodeContentArticle') !== false || stristr($env, 'shortcodeHeadingArticle') !== false || stristr($env, 'shortcodeKeywordArticle') !== false || stristr($env, 'shortcodeCompletion') !== false)
    {
        if(isset($aiomatic_Main_Settings['embeddings_article_short']) && $aiomatic_Main_Settings['embeddings_article_short'] == 'on')
        {
            $embeddings_enabled = true;
        }
    }
    elseif(stristr($env, 'shortcodeChat') !== false)
    {
        if(isset($aiomatic_Main_Settings['embeddings_chat_short']) && $aiomatic_Main_Settings['embeddings_chat_short'] == 'on')
        {
            $embeddings_enabled = true;
        }
    }
    elseif(stristr($env, 'shortcodeCEditor') !== false)
    {
        if(isset($aiomatic_Main_Settings['embeddings_edit_short']) && $aiomatic_Main_Settings['embeddings_edit_short'] == 'on')
        {
            $embeddings_enabled = true;
        }
    }
    elseif(stristr($env, 'keywordCompletion') !== false || stristr($env, 'titleCEditor') !== false || stristr($env, 'contentCEditor') !== false || stristr($env, 'contentCompletion') !== false || stristr($env, 'headingCompletion') !== false || stristr($env, 'titleCEditor') !== false || stristr($env, 'titleCEditor') !== false)
    {
        if(isset($aiomatic_Main_Settings['embeddings_edit']) && $aiomatic_Main_Settings['embeddings_edit'] == 'on')
        {
            $embeddings_enabled = true;
        }
    }
    elseif(stristr($env, 'tagID') !== false || stristr($env, 'categoryID') !== false || stristr($env, 'keywordID') !== false || stristr($env, 'titleID') !== false || stristr($env, 'contentID') !== false || stristr($env, 'headingID') !== false)
    {
        if(isset($aiomatic_Main_Settings['embeddings_bulk']) && $aiomatic_Main_Settings['embeddings_bulk'] == 'on')
        {
            $embeddings_enabled = true;
        }
    }
    $wpaicg_embedding_content = '';
    if(!aiomatic_is_aiomaticapi_key($token))
    {
        if($embeddings_enabled == true)
        {
            $embed_rez = aiomatic_embeddings_result($aicontent);
            if($embed_rez['status'] == 'error')
            {
                if($embed_rez['data'] != 'No results found' && $embed_rez['data'] != 'No data returned')
                {
                    aiomatic_log_to_file('Embeddings failed: ' . print_r($embed_rez, true));
                }
            }
            else
            {
                $wpaicg_embedding_content = $embed_rez['data'];
                $aicontent_temp = '"' . $wpaicg_embedding_content . '" ' . $aicontent;
                $suffix_tokens = count(aiomatic_encode($aicontent_temp));
                $available_tokens = $available_tokens - $suffix_tokens;
                if($available_tokens <= 0)
                {
                    aiomatic_log_to_file('Negative available tokens resulted after embeddings, skipping it.');
                }
                else
                {
                    $aicontent = $aicontent_temp;
                }
            }
        }
        if(aiomatic_is_trained_model($model))
        {
            if (isset($aiomatic_Main_Settings['prompt_suffix']) && $aiomatic_Main_Settings['prompt_suffix'] != '')
            {
                $prompt_suffix = $aiomatic_Main_Settings['prompt_suffix'];
            }
            else
            {
                $prompt_suffix = ' ->';
            }
            $aicontent_temp = $aicontent . $prompt_suffix;
            $suffix_tokens = count(aiomatic_encode($aicontent_temp));
            $available_tokens = $available_tokens - $suffix_tokens;
            if($available_tokens <= 0)
            {
                aiomatic_log_to_file('Negative available tokens resulted after prompt suffix addition: ' . $prompt_suffix);
            }
            else
            {
                $aicontent = $aicontent_temp;
            }
        }
    }
    $aiomatic_Limit_Settings = get_option('aiomatic_Limit_Settings', false);
    $stop = null;
    $session = aiomatic_get_session_id();
    $mode = 'text';
    $maxResults = 1;
    $query = new Aiomatic_Query($aicontent, $available_tokens, $model, $temperature, $stop, $env, $mode, $token, $session, $maxResults, '');
    $ok = apply_filters( 'aiomatic_ai_allowed', true, $aiomatic_Limit_Settings );
    if ( $ok !== true ) {
        $error = 'API calls of the plugin are Rate Limited: ' . $ok;
        return false;
    }
    $delay = '';
    if(isset($aiomatic_Main_Settings['enable_detailed_logging']) && $aiomatic_Main_Settings['enable_detailed_logging'] == 'on' && $GLOBALS['aiomatic_debug'] === true)
    {
        aiomatic_log_to_file('Generating AI Text using model: ' . $model . ' and prompt: ' . $aicontent);
    }
    if (isset($aiomatic_Main_Settings['request_delay']) && $aiomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($aiomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $aiomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($aiomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($aiomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        usleep(intval($delay) * 1000);
    }
    if($temperature < 0 || $temperature > 1)
    {
        $temperature = 1;
    }
    if($top_p < 0 || $top_p > 1)
    {
        $top_p = 1;
    }
    if($presence_penalty < -2 || $presence_penalty > 2)
    {
        $presence_penalty = 0;
    }
    if($frequency_penalty < -2 || $frequency_penalty > 2)
    {
        $frequency_penalty = 0;
    }
    if(aiomatic_is_aiomaticapi_key($token))
    {
        $pargs = array();
        $api_url = 'https://aiomaticapi.com/apis/ai/v1/text/';
        $pargs['apikey'] = trim($token);
        $pargs['model'] = trim($model);
        $pargs['temperature'] = $temperature;
        $pargs['top_p'] = $top_p;
        $pargs['presence_penalty'] = $presence_penalty;
        $pargs['frequency_penalty'] = $frequency_penalty;
        $pargs['prompt'] = trim($aicontent);
        $ai_response = aiomatic_get_web_page_api($api_url, $pargs);
        if($ai_response === false)
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after initial failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, intval($retry_count) + 1, $finish_reason, $error);
            }
            else
            {
                $error = 'Error: Failed to get AiomaticAPI response!';
                return false;
            }
        }
        $ai_json = json_decode($ai_response);
        if($ai_json === false)
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after decode failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, intval($retry_count) + 1, $finish_reason, $error);
            }
            else
            {
                $error = 'Error: Failed to decode AiomaticAPI response: ' . $ai_response;
                return false;
            }
        }
        if(isset($ai_json->error))
        {
            if (stristr($ai_json->error, '[RATE LIMITED]') === false && isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after error failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, intval($retry_count) + 1, $finish_reason, $error);
            }
            else
            {
                $error = 'Error while processing AI response: ' . $ai_json->error;
                return false;
            }
        }
        if(!isset($ai_json->result))
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after parse failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, intval($retry_count) + 1, $finish_reason, $error);
            }
            else
            {
                $error = 'Error: Failed to parse AiomaticAPI response: ' . $ai_response;
                return false;
            }
        }
        if(isset($ai_json->remainingtokens))
        {
            set_transient('aiomaticapi_tokens', $ai_json->remainingtokens, 86400);
        }
        $ai_json->result = apply_filters( 'aiomatic_ai_reply', $ai_json->result, $query );
        return $ai_json->result;
    }
    else
    {
        $base_params = [
            'model' => $model,
            'prompt' => $aicontent,
            'max_tokens' => $available_tokens,
            'temperature' => $temperature,
            'top_p' => $top_p,
            'presence_penalty' => $presence_penalty,
            'frequency_penalty' => $frequency_penalty
        ];
        if(aiomatic_is_trained_model($model))
        {
            if (isset($aiomatic_Main_Settings['completion_suffix']) && $aiomatic_Main_Settings['completion_suffix'] != '')
            {
                $base_params['stop'] = $aiomatic_Main_Settings['completion_suffix'];
            }
            else
            {
                $base_params['stop'] = ' ###';
            }
        }
        try
        {
            $send_json = safe_json_encode($base_params);
        }
        catch(Exception $e)
        {
            $error = 'Error: Exception in API payload encoding: ' . print_r($e->getMessage(), true);
            return false;
        }
        if($send_json === false)
        {
            $error = 'Error: Failed to encode API payload: ' . print_r($aicontent, true);
            return false;
        }
        $api_call = wp_remote_post(
            'https://api.openai.com/v1/completions',
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ),
                'body'        => $send_json,
                'method'      => 'POST',
                'data_format' => 'body',
                'timeout'     => 999,
            )
        );
        if(is_wp_error( $api_call ))
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after initial failure: ' . print_r($api_call, true));
                sleep(1);
                return aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, intval($retry_count) + 1, $finish_reason, $error);
            }
            else
            {
                $error = 'Error: Failed to get initial API response: ' . print_r($api_call, true);
                return false;
            }
        }
        else
        {
            $result = json_decode( $api_call['body'] );
            if($result === false)
            {
                if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                {
                    aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after decode failure: ' . print_r($api_call['body'], true));
                    sleep(1);
                    return aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, intval($retry_count) + 1, $finish_reason, $error);
                }
                else
                {
                    $error = 'Error: Failed to decode initial API response: ' . print_r($api_call, true);
                    return false;
                }
            }
            if(isset($result->type))
            {
                if($result->type == 'insufficient_quota')
                {
                    $error = 'Error: You exceeded your OpenAI quota limit, please wait a period for the quota to refill (initial call).';
                    return false;
                }
                else
                {
                    if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                    {
                        aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after type failure: ' . print_r($api_call['body'], true));
                        sleep(1);
                        return aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, intval($retry_count) + 1, $finish_reason, $error);
                    }
                    else
                    {
                        $error = 'Error: An error occurred when initially calling OpenAI API: ' . print_r($result, true);
                        return false;
                    }
                }
            }
            if(!isset($result->choices[0]->text))
            {
                if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                {
                    aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after choices failure: ' . print_r($api_call['body'], true));
                    sleep(1);
                    return aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, intval($retry_count) + 1, $finish_reason, $error);
                }
                else
                {
                    $error = 'Error: Choices not found in initial API result: ' . print_r($result, true);
                    return false;
                }
            }
            else
            {
                $result->choices[0]->text = apply_filters( 'aiomatic_ai_reply', $result->choices[0]->text, $query );
                $finish_reason = $result->choices[0]->finish_reason;
                if($is_chat == true)
                {
                    $chat_max_characters = 16000;
                    $max_continue_characters = 12000;
                    if($finish_reason == 'stop')
                    {
                        if (empty($result->choices[0]->text) && isset($aiomatic_Main_Settings['max_chat_retry']) && $aiomatic_Main_Settings['max_chat_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_chat_retry']) && intval($aiomatic_Main_Settings['max_chat_retry']) > $retry_count)
                        {
                            aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') chat API call after AI writer ended conversation.');
                            return aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, intval($retry_count) + 1, $finish_reason, $error);
                        }
                        else
                        {
                            return $result->choices[0]->text;
                        }
                    }
                    else
                    {
                        if(strstr($model, 'davinci') !== false && strstr($model, ':ft-') === false)
                        {
                            $max_tokens = 4000;
                        }
                        else
                        {
                            $max_tokens = 2048;
                        }
                        $return_text = $result->choices[0]->text;
                        $aicontent .= $return_text;
                        while($finish_reason != 'stop' && strlen($return_text) < $chat_max_characters)
                        {
                            if(strlen($aicontent) > $max_continue_characters)
                            {
                                $aicontent = substr($aicontent, 0, (0-$max_continue_characters));
                            }
                            $aicontent = trim($aicontent);
                            if(empty($aicontent))
                            {
                                break;
                            }
                            $query_token_count = count(aiomatic_encode($aicontent));
                            $available_tokens = $max_tokens - $query_token_count;
                            if($available_tokens <= 100)
                            {
                                $string_len = strlen($aicontent);
                                $string_len = $string_len / 2;
                                $string_len = intval(0 - $string_len);
                                $aicontent = substr($aicontent, 0, $string_len);
                                $aicontent = trim($aicontent);
                                if(empty($aicontent))
                                {
                                    break;
                                }
                                $query_token_count = count(aiomatic_encode($aicontent));
                                $available_tokens = $max_tokens - $query_token_count;
                            }
                            $query = new Aiomatic_Query($aicontent, $available_tokens, $model, $temperature, $stop, $env, $mode, $token, $session, $maxResults, '');
                            $ok = apply_filters( 'aiomatic_ai_allowed', true, $aiomatic_Limit_Settings );
                            if ( $ok !== true ) {
                                aiomatic_log_to_file('API calls of the plugin are Rate Limited: ' . $ok);
                                break;
                            }
                            $aierror = '';
                            $generated_text = aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, $is_chat, $env, 0, $finish_reason, $aierror);
                            if($generated_text === false)
                            {
                                aiomatic_log_to_file('Chat response completion error: ' . $aierror);
                                break;
                            }
                            else
                            {
                                $generated_text = apply_filters( 'aiomatic_ai_reply', $generated_text, $query );
                                $return_text .= $generated_text;
                                $aicontent .= $generated_text;
                            }
                        }
                        return $return_text;
                    }
                }
                else
                {
                    return $result->choices[0]->text;
                }
            }
        }
    }
    $error = 'Failed to finish API call correctly.';
    return false;
}

function aiomatic_get_models($token, $retry_count, &$error)
{
    $delay = '';
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['request_delay']) && $aiomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($aiomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $aiomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($aiomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($aiomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        usleep($delay);
    }
    if(aiomatic_is_aiomaticapi_key($token))
    {
        $pargs = array();
        $api_url = 'https://aiomaticapi.com/apis/ai/v1/models/';
        $pargs['apikey'] = trim($token);
        $ai_response = aiomatic_get_web_page_api($api_url, $pargs);
        if($ai_response === false)
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') AiomaticAPI model API call after initial failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_get_models($token, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error: Failed to get AiomaticAPI response!';
                return false;
            }
        }
        $ai_json = json_decode($ai_response);
        if($ai_json === false)
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') AiomaticAPI model API call after decode failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_get_models($token, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error: Failed to decode AiomaticAPI response: ' . $ai_response;
                return false;
            }
        }
        if(isset($ai_json->error))
        {
            if (stristr($ai_json->error, '[RATE LIMITED]') === false && isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') AiomaticAPI model API call after error failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_get_models($token, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error while processing AI response: ' . $ai_json->error;
                return false;
            }
        }
        if(!isset($ai_json->result))
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') AiomaticAPI model API call after result failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_get_models($token, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error: Failed to parse AiomaticAPI response: ' . $ai_response;
                return false;
            }
        }
        if(isset($ai_json->remainingtokens))
        {
            set_transient('aiomaticapi_tokens', $ai_json->remainingtokens, 86400);
        }
        return $ai_json->result;
    }
    else
    {
        $api_call = wp_remote_get(
            'https://api.openai.com/v1/models',
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ),
                'data_format' => 'body',
                'timeout'     => 999,
            )
        );
        if(is_wp_error( $api_call ))
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') model API call after initial failure: ' . print_r($api_call, true));
                sleep(1);
                return aiomatic_get_models($token, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error: Failed to get initial API response: ' . print_r($api_call, true);
                return false;
            }
        }
        else
        {
            $result = json_decode( $api_call['body'] );
            if($result === false)
            {
                if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                {
                    aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') model API call after decode failure: ' . print_r($api_call['body'], true));
                    sleep(1);
                    return aiomatic_get_models($token, intval($retry_count) + 1, $error);
                }
                else
                {
                    $error = 'Error: Failed to decode initial API response: ' . print_r($api_call, true);
                    return false;
                }
            }
            if(isset($result->type))
            {
                if($result->type == 'insufficient_quota')
                {
                    $error = 'Error: You exceeded your OpenAI quota limit, please wait a period for the quota to refill (initial call).';
                    return false;
                }
                else
                {
                    if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                    {
                        aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') model API call after type failure: ' . print_r($api_call['body'], true));
                        sleep(1);
                        return aiomatic_get_models($token, intval($retry_count) + 1, $error);
                    }
                    else
                    {
                        $error = 'Error: An error occurred when initially calling OpenAI models API: ' . print_r($result, true);
                        return false;
                    }
                }
            }
            if(!isset($result->data[0]->id))
            {
                if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                {
                    aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') model API call after choices failure: ' . print_r($api_call['body'], true));
                    sleep(1);
                    return aiomatic_get_models($token, intval($retry_count) + 1, $error);
                }
                else
                {
                    $error = 'Error: Choices not found in initial API result: ' . print_r($result, true);
                    return false;
                }
            }
            else
            {
                return $result->data;
            }
        }
    }
    $error = 'Failed to finish API call correctly.';
    return false;
}

function aiomatic_edit_text($token, $model, $instruction, $aicontent, $temperature, $top_p, $env, $retry_count, &$error)
{
    $aiomatic_Limit_Settings = get_option('aiomatic_Limit_Settings', false);
    $stop = null;
    $session = aiomatic_get_session_id();
    $mode = 'edit';
    $maxResults = 1;
    $available_tokens = 1000;
    $query = new Aiomatic_Query($aicontent, $available_tokens, $model, $temperature, $stop, $env, $mode, $token, $session, $maxResults, '');
    $ok = apply_filters( 'aiomatic_ai_allowed', true, $aiomatic_Limit_Settings );
    if ( $ok !== true ) {
        $error = 'API calls of the plugin are Rate Limited: ' . $ok;
        return false;
    }
    $delay = '';
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['request_delay']) && $aiomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($aiomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $aiomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($aiomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($aiomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        usleep($delay);
    }
    if(aiomatic_is_aiomaticapi_key($token))
    {
        $pargs = array();
        $api_url = 'https://aiomaticapi.com/apis/ai/v1/edit/';
        $pargs['apikey'] = trim($token);
        $pargs['temperature'] = $temperature;
        $pargs['top_p'] = $top_p;
        $pargs['instruction'] = trim($instruction);
        $pargs['input'] = trim($aicontent);
        $pargs['model'] = trim($model);
        $ai_response = aiomatic_get_web_page_api($api_url, $pargs);
        if($ai_response === false)
        {
            $error = 'Error: Failed to get AiomaticAPI response!';
            return false;
        }
        $ai_json = json_decode($ai_response);
        if($ai_json === false)
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after decode edit failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_edit_text($token, $model, $instruction, $aicontent, $temperature, $top_p, $env, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error: Failed to decode AiomaticAPI response: ' . $ai_response;
                return false;
            }
        }
        if(isset($ai_json->error))
        {
            if (stristr($ai_json->error, '[RATE LIMITED]') === false && isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after error edit failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_edit_text($token, $model, $instruction, $aicontent, $temperature, $top_p, $env, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error while processing AI response: ' . $ai_json->error;
                return false;
            }
        }
        if(!isset($ai_json->result))
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after result edit failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_edit_text($token, $model, $instruction, $aicontent, $temperature, $top_p, $env, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error: Failed to parse AiomaticAPI response: ' . $ai_response;
                return false;
            }
        }
        if(isset($ai_json->remainingtokens))
        {
            set_transient('aiomaticapi_tokens', $ai_json->remainingtokens, 86400);
        }
        $ai_json->result = apply_filters( 'aiomatic_ai_reply', $ai_json->result, $query );
        return $ai_json->result;
    }
    else
    {
        try
        {
            $send_json = safe_json_encode( [
                'model' => $model,
                'input' => $aicontent,
                'instruction' => $instruction,
                'temperature' => $temperature,
                'top_p' => $top_p
            ] );
        }
        catch(Exception $e)
        {
            $error = 'Error: Exception in API payload encoding: ' . print_r($e->getMessage(), true);
            return false;
        }
        if($send_json === false)
        {
            $error = 'Error: Failed to encode API payload: ' . print_r($aicontent, true);
            return false;
        }
        $api_call = wp_remote_post(
            'https://api.openai.com/v1/edits',
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ),
                'body'        => $send_json,
                'method'      => 'POST',
                'data_format' => 'body',
                'timeout'     => 999,
            )
        );
        if(is_wp_error( $api_call ))
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after initial edit failure: ' . print_r($api_call, true));
                sleep(1);
                return aiomatic_edit_text($token, $model, $instruction, $aicontent, $temperature, $top_p, $env, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error: Failed to get initial API response: ' . print_r($api_call, true);
                return false;
            }
        }
        else
        {
            $result = json_decode( $api_call['body'] );
            if($result === false)
            {
                if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                {
                    aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after decode edit failure: ' . print_r($api_call['body'], true));
                    sleep(1);
                    return aiomatic_edit_text($token, $model, $instruction, $aicontent, $temperature, $top_p, $env, intval($retry_count) + 1, $error);
                }
                else
                {
                    $error = 'Error: Failed to decode initial API response: ' . print_r($api_call, true);
                    return false;
                }
            }
            if(isset($result->type))
            {
                if($result->type == 'insufficient_quota')
                {
                    $error = 'Error: You exceeded your OpenAI quota limit, please wait a period for the quota to refill (initial call).';
                    return false;
                }
                else
                {
                    if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                    {
                        aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after initial edit failure: ' . print_r($api_call['body'], true));
                        sleep(1);
                        return aiomatic_edit_text($token, $model, $instruction, $aicontent, $temperature, $top_p, $env, intval($retry_count) + 1, $error);
                    }
                    else
                    {
                        $error = 'Error: An error occurred when initially calling OpenAI API: ' . print_r($result, true);
                        return false;
                    }
                }
            }
            if(!isset($result->choices[0]->text))
            {
                if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                {
                    aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after choices edit failure: ' . print_r($api_call['body'], true));
                    sleep(1);
                    return aiomatic_edit_text($token, $model, $instruction, $aicontent, $temperature, $top_p, $env, intval($retry_count) + 1, $error);
                }
                else
                {
                    $error = 'Error: Choices not found in initial API result: ' . print_r($result, true);
                    return false;
                }
            }
            else
            {
                $result->choices[0]->text = apply_filters( 'aiomatic_ai_reply', $result->choices[0]->text, $query );
                return $result->choices[0]->text;
            }
        }
    }
    $error = 'Failed to finish API call correctly.';
    return false;
}
function aiomatic_generate_ai_image($token, $number, $prompt, $size, $env, $retry_count, &$error)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    $aiomatic_Limit_Settings = get_option('aiomatic_Limit_Settings', false);
    $stop = null;
    $session = aiomatic_get_session_id();
    $mode = 'image';
    $maxResults = 1;
    $temperature = 1;
    $model = 'dall-e';
    $query = new Aiomatic_Query($prompt, 1000, $model, $temperature, $stop, $env, $mode, $token, $session, $maxResults, $size);
    $ok = apply_filters( 'aiomatic_ai_allowed', true, $aiomatic_Limit_Settings );
    if ( $ok !== true ) {
        $error = 'API calls of the plugin are Rate Limited: ' . $ok;
        return false;
    }
    $delay = '';
    $prompt = trim($prompt);
    if($prompt == '')
    {
        return false;
    }
    if(strlen($prompt) > 1000)
    {
        $prompt = substr($prompt, 0, 1000);
    }
    if(isset($aiomatic_Main_Settings['enable_detailed_logging']) && $aiomatic_Main_Settings['enable_detailed_logging'] == 'on')
    {
        aiomatic_log_to_file('Generating AI Image using prompt: ' . $prompt);
    }
    if (isset($aiomatic_Main_Settings['request_delay']) && $aiomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($aiomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $aiomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($aiomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($aiomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        usleep($delay);
    }
    if($size != '256x256' && $size != '512x512' && $size != '1024x1024')
    {
        $size = '1024x1024';
    }
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    $return_arr = array();
    if(aiomatic_is_aiomaticapi_key($token))
    {
        $pargs = array();
        $api_url = 'https://aiomaticapi.com/apis/ai/v1/image/';
        $pargs['apikey'] = trim($token);
        $pargs['prompt'] = trim($prompt);
        $pargs['size'] = $size;
        $ai_response = aiomatic_get_web_page_api($api_url, $pargs);
        if($ai_response === false)
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after initial AiomaticAPI response: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_generate_ai_image($token, $number, $prompt, $size, $env, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error: Failed to get AiomaticAPI image response!';
                return false;
            }
        }
        $ai_json = json_decode($ai_response);
        if($ai_json === false)
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after decode AiomaticAPI response: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_generate_ai_image($token, $number, $prompt, $size, $env, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error: Failed to decode AiomaticAPI image response: ' . $ai_response;
                return false;
            }
        }
        if(isset($ai_json->error))
        {
            if (stristr($ai_json->error, '[RATE LIMITED]') === false && isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after error AiomaticAPI response: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_generate_ai_image($token, $number, $prompt, $size, $env, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error while processing AI image response: ' . $ai_json->error;
                return false;
            }
        }
        if(!isset($ai_json->result))
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after result AiomaticAPI response: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_generate_ai_image($token, $number, $prompt, $size, $env, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Error: Failed to parse AiomaticAPI image response: ' . $ai_response;
                return false;
            }
        }
        $ai_json->result = apply_filters( 'aiomatic_ai_reply', $ai_json->result, $query );
        if(isset($ai_json->remainingtokens))
        {
            set_transient('aiomaticapi_tokens', $ai_json->remainingtokens, 86400);
        }
        if (isset($aiomatic_Main_Settings['copy_locally']) && $aiomatic_Main_Settings['copy_locally'] == 'on') 
        {
            $localpath = aiomatic_copy_image_locally($ai_json->result);
            if($localpath !== false)
            {
                if ((isset($aiomatic_Main_Settings['ai_resize_height']) && $aiomatic_Main_Settings['ai_resize_height'] !== '') || (isset($aiomatic_Main_Settings['ai_resize_width']) && $aiomatic_Main_Settings['ai_resize_width'] !== ''))
                {
                    try
                    {
                        if(!class_exists('\Eventviva\ImageResize')){require_once (dirname(__FILE__) . "/res/ImageResize/ImageResize.php");}
                        $imageRes = new ImageResize($localpath[1]);
                        $imageRes->quality_jpg = 100;
                        if ((isset($aiomatic_Main_Settings['ai_resize_height']) && $aiomatic_Main_Settings['ai_resize_height'] !== '') && (isset($aiomatic_Main_Settings['ai_resize_width']) && $aiomatic_Main_Settings['ai_resize_width'] !== ''))
                        {
                            $imageRes->resizeToBestFit($aiomatic_Main_Settings['ai_resize_width'], $aiomatic_Main_Settings['ai_resize_height'], true);
                        }
                        elseif (isset($aiomatic_Main_Settings['ai_resize_width']) && $aiomatic_Main_Settings['ai_resize_width'] !== '')
                        {
                            $imageRes->resizeToWidth($aiomatic_Main_Settings['ai_resize_width'], true);
                        }
                        elseif (isset($aiomatic_Main_Settings['ai_resize_height']) && $aiomatic_Main_Settings['ai_resize_height'] !== '')
                        {
                            $imageRes->resizeToHeight($aiomatic_Main_Settings['ai_resize_height'], true);
                        }
                        $imageRes->save($localpath[1]);
                    }
                    catch(Exception $e)
                    {
                        aiomatic_log_to_file('Failed to resize AI generated image: ' . $localpath[0] . ' to sizes ' . $aiomatic_Main_Settings['ai_resize_width'] . ' - ' . $aiomatic_Main_Settings['ai_resize_height'] . '. Exception thrown ' . esc_html($e->getMessage()) . '!');
                    }
                }
                $return_arr[] = $localpath[0];
            }
            else
            {
                $return_arr[] = $ai_json->result;
            }
        }
        else
        {
            $return_arr[] = $ai_json->result;
        }
    }
    else
    {
        try
        {
            $send_json = safe_json_encode( [
                'n' => intval($number),
                'prompt' => $prompt,
                'size' => $size,
                'response_format' => 'url'
            ] );
        }
        catch(Exception $e)
        {
            $error = 'Error: Exception in API payload encoding: ' . print_r($e->getMessage(), true);
            return false;
        }
        if($send_json === false)
        {
            $error = 'Error: Failed to encode API payload: ' . print_r($prompt, true);
            return false;
        }
        $api_call = wp_remote_post(
            'https://api.openai.com/v1/images/generations',
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ),
                'body'        => $send_json,
                'method'      => 'POST',
                'data_format' => 'body',
                'timeout'     => 999,
            )
        );
        if(is_wp_error( $api_call ))
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after initial DALLE response: ' . print_r($api_call, true));
                sleep(1);
                return aiomatic_generate_ai_image($token, $number, $prompt, $size, $env, intval($retry_count) + 1, $error);
            }
            else
            {
                $error = 'Failed to get DallE API response: ' . print_r($api_call, true);
                return false;
            }
        }
        else
        {
            $result = json_decode( $api_call['body'] );
            if($result === false)
            {
                if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                {
                    aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after decode DALLE response: ' . print_r($api_call['body'], true));
                    sleep(1);
                    return aiomatic_generate_ai_image($token, $number, $prompt, $size, $env, intval($retry_count) + 1, $error);
                }
                else
                {
                    $error = 'Failed to decode initial DallE API response: ' . print_r($api_call, true);
                    return false;
                }
            }
            else
            {
                if(isset($result->type))
                {
                    if($result->type == 'insufficient_quota')
                    {
                        $error = 'You exceeded your OpenAI quota limit, please wait a period for the quota to refill (initial call).';
                        return false;
                    }
                    else
                    {
                        if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                        {
                            aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after type DALLE response: ' . print_r($api_call['body'], true));
                            sleep(1);
                            return aiomatic_generate_ai_image($token, $number, $prompt, $size, $env, intval($retry_count) + 1, $error);
                        }
                        else
                        {
                            $error = 'An error occurred when initially calling OpenAI API, no type found: ' . print_r($result, true);
                            return false;
                        }
                    }
                }
                if(!isset($result->data))
                {
                    if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                    {
                        aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') API call after data DALLE response: ' . print_r($api_call['body'], true));
                        sleep(1);
                        return aiomatic_generate_ai_image($token, $number, $prompt, $size, $env, intval($retry_count) + 1, $error);
                    }
                    else
                    {
                        $error = 'An error occurred when initially calling OpenAI data API: ' . print_r($result, true);
                        return false;
                    }
                }
                else
                {
                    foreach($result->data as $rdata)
                    {
                        $rdata->url = apply_filters( 'aiomatic_ai_reply', $rdata->url, $query );
                        if (isset($aiomatic_Main_Settings['copy_locally']) && $aiomatic_Main_Settings['copy_locally'] == 'on') 
                        {
                            $localpath = aiomatic_copy_image_locally($rdata->url);
                            if($localpath !== false)
                            {
                                if ((isset($aiomatic_Main_Settings['ai_resize_height']) && $aiomatic_Main_Settings['ai_resize_height'] !== '') || (isset($aiomatic_Main_Settings['ai_resize_width']) && $aiomatic_Main_Settings['ai_resize_width'] !== ''))
                                {
                                    try
                                    {
                                        if(!class_exists('\Eventviva\ImageResize')){require_once (dirname(__FILE__) . "/res/ImageResize/ImageResize.php");}
                                        $imageRes = new ImageResize($localpath[1]);
                                        $imageRes->quality_jpg = 100;
                                        if ((isset($aiomatic_Main_Settings['ai_resize_height']) && $aiomatic_Main_Settings['ai_resize_height'] !== '') && (isset($aiomatic_Main_Settings['ai_resize_width']) && $aiomatic_Main_Settings['ai_resize_width'] !== ''))
                                        {
                                            $imageRes->resizeToBestFit($aiomatic_Main_Settings['ai_resize_width'], $aiomatic_Main_Settings['ai_resize_height'], true);
                                        }
                                        elseif (isset($aiomatic_Main_Settings['ai_resize_width']) && $aiomatic_Main_Settings['ai_resize_width'] !== '')
                                        {
                                            $imageRes->resizeToWidth($aiomatic_Main_Settings['ai_resize_width'], true);
                                        }
                                        elseif (isset($aiomatic_Main_Settings['ai_resize_height']) && $aiomatic_Main_Settings['ai_resize_height'] !== '')
                                        {
                                            $imageRes->resizeToHeight($aiomatic_Main_Settings['ai_resize_height'], true);
                                        }
                                        $imageRes->save($localpath[1]);
                                    }
                                    catch(Exception $e)
                                    {
                                        aiomatic_log_to_file('Failed to resize AI generated image: ' . $localpath[0] . ' to sizes ' . $aiomatic_Main_Settings['ai_resize_width'] . ' - ' . $aiomatic_Main_Settings['ai_resize_height'] . '. Exception thrown ' . esc_html($e->getMessage()) . '!');
                                    }
                                }
                                $return_arr[] = $localpath[0];
                            }
                            else
                            {
                                $return_arr[] = $rdata->url;
                            }
                        }
                        else
                        {
                            $return_arr[] = $rdata->url;
                        }
                    }
                }
            }
        }
    }
    return $return_arr;
}
function aiomatic_copy_image_locally($image_url)
{
    $upload_dir = wp_upload_dir();
    global $wp_filesystem;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
        wp_filesystem($creds);
    }
    if(substr( $image_url, 0, 10 ) === "data:image")
    {
        $data = explode(',', $image_url);
        if(isset($data[1]))
        {
            $image_data = base64_decode($data[1]);
            if($image_data === FALSE)
            {
                return false;
            }
        }
        else
        {
            return false;
        }
        preg_match('{data:image/(.*?);}', $image_url, $ex_matches);
        if(isset($ex_matches[1]))
        {
            $image_url = 'image.' . $ex_matches[1];
        }
        else
        {
            $image_url = 'image.jpg';
        }
    }
    else
    {
        $image_data = aiomatic_get_web_page($image_url);
        if ($image_data === FALSE || strpos($image_data, '<Message>Access Denied</Message>') !== FALSE) 
        {
            return false;
        }
    }
    $filename = basename($image_url);
    $filename = explode("?", $filename);
    $filename = $filename[0];
    $filename = urlencode($filename);
    $filename = str_replace('%', '-', $filename);
    $filename = str_replace('#', '-', $filename);
    $filename = str_replace('&', '-', $filename);
    $filename = str_replace('{', '-', $filename);
    $filename = str_replace('}', '-', $filename);
    $filename = str_replace('\\', '-', $filename);
    $filename = str_replace('<', '-', $filename);
    $filename = str_replace('>', '-', $filename);
    $filename = str_replace('*', '-', $filename);
    $filename = str_replace('/', '-', $filename);
    $filename = str_replace('$', '-', $filename);
    $filename = str_replace('\'', '-', $filename);
    $filename = str_replace('"', '-', $filename);
    $filename = str_replace(':', '-', $filename);
    $filename = str_replace('@', '-', $filename);
    $filename = str_replace('+', '-', $filename);
    $filename = str_replace('|', '-', $filename);
    $filename = str_replace('=', '-', $filename);
    $filename = str_replace('`', '-', $filename);
    $file_parts = pathinfo($filename);
    if(!isset($file_parts['extension']))
    {
        $file_parts['extension'] = '';
    }
    switch($file_parts['extension'])
    {
        case "":
        if(!aiomatic_endsWith($filename, '.jpg'))
            $filename .= '.jpg';
        break;
        case NULL:
        if(!aiomatic_endsWith($filename, '.jpg'))
            $filename .= '.jpg';
        break;
    }
    if (wp_mkdir_p($upload_dir['path'] . '/localimages'))
    {
        $file = $upload_dir['path'] . '/localimages/' . $filename;
        $ret_path = $upload_dir['url'] . '/localimages/' . $filename;
    }
    else
    {
        $file = $upload_dir['basedir'] . '/' . $filename;
        $ret_path = $upload_dir['baseurl'] . '/' . $filename;
    }
    if($wp_filesystem->exists($file))
    {
        if(empty($file_parts['extension']))
        {
            $file_parts['extension'] = 'jpg';
        }
        $unid = uniqid();
        $file .= $unid . '.' . $file_parts['extension'];
        $ret_path .= $unid . '.' . $file_parts['extension'];
    }
    
    $ret = $wp_filesystem->put_contents($file, $image_data);
    if ($ret === FALSE) {
        return false;
    }
    return array($ret_path, $file);
}

function aiomatic_generate_random_token($len) {
    $characters = "abcdefghijklmnopqrstuvwxyz0123456789-";
    $word = "";
    for ($i = 0; $i < $len; $i++) {
        $word .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $word;
}
function aiomatic_run_rule($param, $auto = 1, $ret_content = 0)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
    {
        aiomatic_log_to_file('You need to insert a valid OpenAI/AiomaticAPI API Key for this to work!');
        return 'fail';
    }
	$appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
    $appids = array_filter($appids);
    $token = $appids[array_rand($appids)];
    if($ret_content == 0)
    {
        $f = fopen(get_temp_dir() . 'aiomatic_' . $param, 'w');
        if($f !== false)
        {
            $flock_disabled = explode(',', ini_get('disable_functions'));
            if(!in_array('flock', $flock_disabled))
            {
                if (!flock($f, LOCK_EX | LOCK_NB)) {
                    return 'nochange';
                }
            }
        }
        
        $GLOBALS['wp_object_cache']->delete('aiomatic_running_list', 'options');
        if (!get_option('aiomatic_running_list')) {
            $running = array();
        } else {
            $running = get_option('aiomatic_running_list');
        }
        if (!empty($running)) {
            if (in_array($param, $running)) {
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                    aiomatic_log_to_file('Only one instance of this rule is allowed. Rule is already running!');
                }
                return 'nochange';
            }
        }
        $running[] = $param;
        update_option('aiomatic_running_list', $running, false);
        register_shutdown_function('aiomatic_clear_flag_at_shutdown', $param);
        if (isset($aiomatic_Main_Settings['rule_timeout']) && $aiomatic_Main_Settings['rule_timeout'] != '') {
            $timeout = intval($aiomatic_Main_Settings['rule_timeout']);
        } else {
            $timeout = 3600;
        }
        ini_set('safe_mode', 'Off');
        ini_set('max_execution_time', $timeout);
        ini_set('ignore_user_abort', 1);
        ini_set('user_agent', aiomatic_get_random_user_agent());
        if(function_exists('ignore_user_abort'))
        {
            ignore_user_abort(true);
        }
                if(function_exists('set_time_limit'))
        {
            set_time_limit($timeout);
        }
    }
    if (isset($aiomatic_Main_Settings['player_width']) && $aiomatic_Main_Settings['player_width'] !== '') {
        $width = esc_attr($aiomatic_Main_Settings['player_width']);
    }
    else
    {
        $width = 580;
    }
    if (isset($aiomatic_Main_Settings['player_height']) && $aiomatic_Main_Settings['player_height'] !== '') {
        $height = esc_attr($aiomatic_Main_Settings['player_height']);
    }
    else
    {
        $height = 380;
    }
    $posts_inserted         = 0;
    if (isset($aiomatic_Main_Settings['aiomatic_enabled']) && $aiomatic_Main_Settings['aiomatic_enabled'] == 'on') {
        try 
        {
            $old_title        = '';
            $cont             = 0;
            $found            = 0;
            $enable_comments  = '1';
            $enable_pingback  = '1';
            $ai_command       = '';
            $headings         = '';
            $images           = '';
            $videos           = '';
            $max              = PHP_INT_MAX;
            $post_title       = '';
            $default_category = '';
            $extra_categories = '';
            $min_char         = '1';
            $post_status     = 'publish';
            $remove_default  = '';
            $title_ai_command = '';
            $strip_title     = '';
            $title_source    = '';
            $title_once      = '';
            $category_model  = 'text-davinci-003';
            $category_ai_command  = '';
            $tag_model       = 'text-davinci-003';
            $tag_ai_command  = '';
            $min_time        = '';
            $max_time        = '';
            $skip_spin       = '';
            $skip_translate  = '';
            $title_model     = 'text-davinci-003';
            $post_type       = 'post';
            $accept_comments = 'closed';
            $post_user_name  = 1;
            $item_create_tag = '';
            $can_create_tag  = 'disabled';
            $item_tags       = '';
            $max             = 50;
            $auto_categories = 'disabled';
            $custom_fields   = '';
            $custom_tax      = '';
            $temperature     = '';
            $post_prepend    = '';
            $post_append     = '';
            $enable_ai_images = '';
            $top_p           = '';
            $presence_penalty = '';
            $frequency_penalty = '';
            $royalty_free    = '';
            $image_size      = '256x256';
            $headings_list   = '';
            $images_list     = '';
            $wpml_lang       = '';
            $post_format     = 'post-format-standard';
            $post_array      = array();
            $max_tokens      = 2048;
            $max_seed_tokens = 1000;
            $model           = 'text-davinci-003';
            $max_continue_tokens = 500;
            $GLOBALS['wp_object_cache']->delete('aiomatic_rules_list', 'options');
            if (!get_option('aiomatic_rules_list')) {
                $rules = array();
            } else {
                $rules = get_option('aiomatic_rules_list');
            }
            if (!empty($rules)) {
                foreach ($rules as $request => $bundle[]) {
                    if ($cont == $param) {
                        $bundle_values    = array_values($bundle);
                        $myValues         = $bundle_values[$cont];
                        $array_my_values  = array_values($myValues);for($iji=0;$iji<count($array_my_values);++$iji){if(is_string($array_my_values[$iji])){$array_my_values[$iji]=stripslashes($array_my_values[$iji]);}}
                        $schedule         = isset($array_my_values[0]) ? $array_my_values[0] : '';
                        $active           = isset($array_my_values[1]) ? $array_my_values[1] : '';
                        $last_run         = isset($array_my_values[2]) ? $array_my_values[2] : '';
                        $max              = isset($array_my_values[3]) ? $array_my_values[3] : '';
                        $post_status      = isset($array_my_values[4]) ? $array_my_values[4] : '';
                        $post_type        = isset($array_my_values[5]) ? $array_my_values[5] : '';
                        $post_user_name   = isset($array_my_values[6]) ? $array_my_values[6] : '';
                        $item_create_tag  = isset($array_my_values[7]) ? $array_my_values[7] : '';
                        $default_category = isset($array_my_values[8]) ? $array_my_values[8] : '';
                        $auto_categories  = isset($array_my_values[9]) ? $array_my_values[9] : '';
                        $can_create_tag   = isset($array_my_values[10]) ? $array_my_values[10] : '';
                        $enable_comments  = isset($array_my_values[11]) ? $array_my_values[11] : '';
                        $image_url        = isset($array_my_values[12]) ? $array_my_values[12] : '';
                        $post_title       = isset($array_my_values[13]) ? htmlspecialchars_decode($array_my_values[13]) : '';
                        $enable_pingback  = isset($array_my_values[14]) ? $array_my_values[14] : '';
                        $post_format      = isset($array_my_values[15]) ? $array_my_values[15] : '';
                        $min_char         = isset($array_my_values[16]) ? $array_my_values[16] : '';
                        $custom_fields    = isset($array_my_values[17]) ? $array_my_values[17] : '';
                        $custom_tax       = isset($array_my_values[18]) ? $array_my_values[18] : '';
                        $temperature      = isset($array_my_values[19]) ? $array_my_values[19] : '';
                        $top_p            = isset($array_my_values[20]) ? $array_my_values[20] : '';
                        $presence_penalty = isset($array_my_values[21]) ? $array_my_values[21] : '';
                        $frequency_penalty = isset($array_my_values[22]) ? $array_my_values[22] : '';
                        $royalty_free     = isset($array_my_values[23]) ? $array_my_values[23] : '';
                        $ai_command       = isset($array_my_values[24]) ? $array_my_values[24] : '';
                        $max_tokens       = isset($array_my_values[25]) ? $array_my_values[25] : 2048;
                        $max_seed_tokens  = isset($array_my_values[26]) ? $array_my_values[26] : 1000;
                        $max_continue_tokens= isset($array_my_values[27]) ? $array_my_values[27] : 500;
                        $model            = isset($array_my_values[28]) ? $array_my_values[28] : 'text-davinci-003';
                        $headings         = isset($array_my_values[29]) ? $array_my_values[29] : '';
                        $images           = isset($array_my_values[30]) ? $array_my_values[30] : '';
                        $videos           = isset($array_my_values[31]) ? $array_my_values[31] : '';
                        $post_prepend     = isset($array_my_values[32]) ? $array_my_values[32] : '';
                        $post_append      = isset($array_my_values[33]) ? $array_my_values[33] : '';
                        $enable_ai_images = isset($array_my_values[34]) ? $array_my_values[34] : '';
                        $ai_command_image = isset($array_my_values[35]) ? $array_my_values[35] : '';
                        $image_size       = isset($array_my_values[36]) ? $array_my_values[36] : '';
                        $headings_list    = isset($array_my_values[37]) ? $array_my_values[37] : '';
                        $images_list      = isset($array_my_values[38]) ? $array_my_values[38] : '';
                        $wpml_lang        = isset($array_my_values[39]) ? $array_my_values[39] : '';
                        $remove_default   = isset($array_my_values[40]) ? $array_my_values[40] : '';
                        $title_model      = isset($array_my_values[41]) ? $array_my_values[41] : 'text-davinci-003';
                        $title_ai_command = isset($array_my_values[42]) ? $array_my_values[42] : '';
                        $strip_title      = isset($array_my_values[43]) ? $array_my_values[43] : '';
                        $title_once       = isset($array_my_values[44]) ? $array_my_values[44] : '';
                        $category_model   = isset($array_my_values[45]) ? $array_my_values[45] : 'text-davinci-003';
                        $category_ai_command= isset($array_my_values[46]) ? $array_my_values[46] : '';
                        $tag_model        = isset($array_my_values[47]) ? $array_my_values[47] : 'text-davinci-003';
                        $tag_ai_command   = isset($array_my_values[48]) ? $array_my_values[48] : '';
                        $min_time         = isset($array_my_values[49]) ? $array_my_values[49] : '';
                        $max_time         = isset($array_my_values[50]) ? $array_my_values[50] : '';
                        $skip_spin        = isset($array_my_values[51]) ? $array_my_values[51] : '';
                        $skip_translate   = isset($array_my_values[52]) ? $array_my_values[52] : '';
                        $title_source     = isset($array_my_values[53]) ? $array_my_values[53] : '';
                        $found            = 1;
                        break;
                    }
                    $cont = $cont + 1;
                }
            } else {
                aiomatic_log_to_file('No rules found for aiomatic_rules_list!');
                if($auto == 1)
                {
                    aiomatic_clearFromList($param);
                }
                return 'fail';
            }
            if(empty($max_tokens) || intval($max_tokens) <= 0)
            {
                $max_tokens = 2048;
            }
            if(intval($max_tokens) > 2048 && (!stristr($model, 'davinci') || strstr($model, ':ft-') === true))
            {
                $max_tokens = 2048;
            }
            if($max_seed_tokens === '')
            {
                $max_seed_tokens = 1000;
            }
            if($max_continue_tokens === '')
            {
                $max_continue_tokens = 500;
            }
            if ($found == 0) {
                aiomatic_log_to_file($param . ' not found in aiomatic_rules_list!');
                if($auto == 1)
                {
                    aiomatic_clearFromList($param);
                }
                return 'fail';
            } else {
                if($ret_content == 0)
                {
                    $GLOBALS['wp_object_cache']->delete('aiomatic_rules_list', 'options');
                    $rules = get_option('aiomatic_rules_list');
                    $rules[$param][2] = aiomatic_get_date_now();
                    update_option('aiomatic_rules_list', $rules, false);
                }
            }
            if ($enable_comments == '1') {
                $accept_comments = 'open';
            }
            $count = 1;
            if($temperature == '')
            {
                $temperature = 1;
            }
            else
            {
                $temperature = floatval($temperature);
            }
            if($top_p == '')
            {
                $top_p = 1;
            }
            else
            {
                $top_p = floatval($top_p);
            }
            if($frequency_penalty == '')
            {
                $frequency_penalty = 0;
            }
            else
            {
                $frequency_penalty = floatval($frequency_penalty);
            }
            if($presence_penalty == '')
            {
                $presence_penalty = 0;
            }
            else
            {
                $presence_penalty = floatval($presence_penalty);
            }
            $max_tokens = intval($max_tokens);
            $max_seed_tokens = intval($max_seed_tokens);
            $max_continue_tokens = intval($max_continue_tokens);
            $blog_title       = html_entity_decode(get_bloginfo('title'));
            $post_title = aiomatic_replaceSynergyShortcodes($post_title);
            $post_title_lines = preg_split('/\r\n|\r|\n/', $post_title);
            $additional_kws = array();
            $post_link = '';
            $user_name        = '';
            $featured_image   = '';
            $post_cats = '';
            $post_tagz = '';
            $post_excerpt = '';
            $final_content = '';
            $postID = '';
            $heading_val = '';
            $image_query = '';
            $temp_post = '';
            $cntx = count($post_title_lines);
            for($ji = 0; $ji < $cntx; $ji++)
            {
                if (filter_var($post_title_lines[$ji], FILTER_VALIDATE_URL) !== false) 
                {
                    if(aiomatic_endsWith($post_title_lines[$ji], '.txt'))
                    {
                        $txt_content = aiomatic_get_web_page($post_title_lines[$ji]);
                        if ($txt_content === FALSE) 
                        {
                            aiomatic_log_to_file('Failed to read text file: ' . $post_title_lines[$ji]);
                            if($auto == 1)
                            {
                                aiomatic_log_to_file($param);
                            }
                            continue;
                        }
                        unset($post_title_lines[$ji]);
                        $additional_kws = preg_split('/\r\n|\r|\n/', $txt_content);
                    }
                    else
                    {
                        aiomatic_log_to_file('Trying to parse RSS feed items: ' . $post_title_lines[$ji]);
                        try
                        {
                            if(!class_exists('SimplePie_Autoloader', false))
                            {
                                require_once(dirname(__FILE__) . "/res/simplepie/autoloader.php");
                            }
                        }
                        catch(Exception $e) 
                        {
                            aiomatic_log_to_file('Exception thrown in SimplePie autoloader: ' . $e->getMessage());
                            if($auto == 1)
                            {
                                aiomatic_log_to_file($param);
                            }
                            continue;
                        }
                        $feed = new SimplePie();
                        $feed->set_timeout(120);
                        $feed->set_feed_url($post_title_lines[$ji]);
                        $feed->enable_cache(false);
                        $feed->strip_htmltags(false);
                        $feed->init();
                        $feed->handle_content_type();
                        if ($feed->error()) 
                        {
                            aiomatic_log_to_file('Error in parsing RSS feed: ' . $feed->error() . ' for ' . $post_title_lines[$ji]);
                            if($auto == 1)
                            {
                                aiomatic_clearFromList($param);
                            }
                            continue;
                        }
                        $items = $feed->get_items();
                        foreach($items as $itemx)
                        {
                            $additional_kws[] = $itemx->get_title();
                        }
                        $item = $items[array_rand($items)];
                        $post_link = trim($item->get_permalink());
                        if ($fauthor = $item->get_author()) 
                        {
                            $user_name = $fauthor->get_name();
                        }
                        $feed_cats = array();
                        if(isset($item->category))
                        {
                            foreach($item->category as $cata)
                            {
                                $feed_cats[] = $cata->__toString();
                            }
                            if(count($feed_cats) == 0)
                            {
                                $feed_cats[] = $item->category->__toString();
                            }
                            $post_cats = implode(',', $feed_cats);
                        }
                        $post_excerpt = $item->get_description();
                        $final_content = $item->get_content();
                        unset($post_title_lines[$ji]);
                    }
                }
            }
            if(count($additional_kws) > 0)
            {
                $post_title_lines = array_merge($post_title_lines, $additional_kws);
            }
            if($title_once == '1')
            {
                $posted_items = array();
                $postsPerPage = 50000;
                $paged = 0;
                wp_suspend_cache_addition(true);
                $post_stati = get_post_stati();
                foreach ($post_stati as $key => $val) {
                    if ($val == 'auto-draft') {
                        unset($post_stati[$key]);
                    }
                    if ($val == 'inherit') {
                        unset($post_stati[$key]);
                    }
                    if ($val == 'request-pending') {
                        unset($post_stati[$key]);
                    }
                    if ($val == 'request-confirmed') {
                        unset($post_stati[$key]);
                    }
                    if ($val == 'request-failed') {
                        unset($post_stati[$key]);
                    }
                    if ($val == 'request-completed') {
                        unset($post_stati[$key]);
                    }
                    if ($val == 'future') {
                        unset($post_stati[$key]);
                    }
                }
                do
                {
                    $postOffset = $paged * $postsPerPage;
                    $query     = array(
                        'post_status' => $post_stati,
                        'post_type' => array(
                            'any'
                        ),
                        'numberposts' => $postsPerPage,
                        'fields' => 'ids',
                        'meta_key' => 'aiomatic_source_title',
                        'offset'  => $postOffset
                    );
                    $post_list = get_posts($query);
                    foreach ($post_list as $post) {
                        $orig_tit = get_post_meta($post, 'aiomatic_source_title', true);
                        if(!empty($orig_tit))
                        {
                            $posted_items[$orig_tit] = $post;
                        }
                    }
                    $paged++;
                }while(!empty($post_list));
                wp_suspend_cache_addition(false);
                unset($post_list);
                foreach($posted_items as $ptit => $pid)
                {
                    if (($key = array_search($ptit, $post_title_lines)) !== false) {
                        aiomatic_log_to_file('Skipping title, already processed: ' . $ptit);
                        unset($post_title_lines[$key]);
                    }
                }
            }
            $spintax = new AIomatic_Spintax();
            if ( ! function_exists( 'get_page_by_title' ) ) {
                include_once( ABSPATH . 'wp-includes/post.php' );
            }
            $orig_ai_command = $ai_command;
            $orig_ai_command_title = $title_ai_command;
            $orig_ai_command_category = $category_ai_command;
            $orig_ai_command_tag = $tag_ai_command;
            $orig_ai_command_image = $ai_command_image;
            if(isset($aiomatic_Main_Settings['attr_text']) && $aiomatic_Main_Settings['attr_text'] != '')
            {
                $img_attr = $aiomatic_Main_Settings['attr_text'];
            }
            else
            {
                $img_attr = '';
            }
            if($headings_list != '')
            {
                $headings_arr_temp = preg_split('/\r\n|\r|\n/', $headings_list);
                $headings_arr_temp = array_map('trim', $headings_arr_temp);
                $headings_arr = array();
                foreach($headings_arr_temp as $hat)
                {
                    $hat = aiomatic_replaceSynergyShortcodes($hat);
                    $hat = replaceAIPostShortcodes($hat, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                    $headings_arr[] = array('q' => $hat, 'a' => '');
                }
            }
            else
            {
                $headings_arr = array();
            }
            if($images_list != '')
            {
                $images_arr = preg_split('/\r\n|\r|\n/', $images_list);
                $images_arr = array_map('trim', $images_arr);
            }
            else
            {
                $images_arr = array();
            }
            while(true) 
            {
                $headings_arr_copy = $headings_arr;
                $added_img_list = array();
                $added_images = 0;
                $heading_results = array();
                if(count($post_title_lines) == 0)
                {
                    break;
                }
                if ($count > intval($max)) {
                    break;
                }
                $current_index = array_rand($post_title_lines);
                $post_title = trim($post_title_lines[$current_index]);
                $tprepp = $spintax->Parse($post_title);
                if($tprepp != false && $tprepp != '')
                {
                    $post_title = $tprepp;
                }
                $old_title = $post_title;
                $already_spinned = 0;
                if (filter_var($post_title, FILTER_VALIDATE_URL) === false && stristr($post_title, '%%ai_generated_title%%') === false)
                {
                    unset($post_title_lines[$current_index]);
                }
                if(stristr($post_title, '%%ai_generated_title%%') !== false || $title_source == 'ai')
                {
                    if($orig_ai_command_title == '')
                    {
                        $orig_ai_command_title = $post_title;
                    }
                    if($orig_ai_command_title != '')
                    {
                        $title_ai_command = $orig_ai_command_title;
                        $title_ai_command = preg_split('/\r\n|\r|\n/', $title_ai_command);
                        $title_ai_command = array_filter($title_ai_command);
                        if(count($title_ai_command) > 0)
                        {
                            $title_ai_command = $title_ai_command[array_rand($title_ai_command)];
                        }
                        else
                        {
                            $title_ai_command = '';
                        }
                        $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                        if(!empty($title_ai_command))
                        {
                            $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                        }
                        else
                        {
                            $title_ai_command = trim(strip_tags($post_title));
                        }
                        $title_ai_command = trim($title_ai_command);
                        if (filter_var($title_ai_command, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($title_ai_command, '.txt'))
                        {
                            $txt_content = aiomatic_get_web_page($title_ai_command);
                            if ($txt_content !== FALSE) 
                            {
                                $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                $txt_content = array_filter($txt_content);
                                if(count($txt_content) > 0)
                                {
                                    $txt_content = $txt_content[array_rand($txt_content)];
                                    if(trim($txt_content) != '') 
                                    {
                                        $title_ai_command = $txt_content;
                                        $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                        $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                    }
                                }
                            }
                        }
                        if(empty($title_ai_command))
                        {
                            aiomatic_log_to_file('Empty API post title seed expression provided!');
                        }
                        else
                        {
                            if(strlen($title_ai_command) > $max_seed_tokens * 4)
                            {
                                $title_ai_command = substr($title_ai_command, 0, (0 - ($max_seed_tokens * 4)));
                            }
                            $title_ai_command = trim($title_ai_command);
                            if(empty($title_ai_command))
                            {
                                aiomatic_log_to_file('Empty API title seed expression provided(2)! ' . print_r($title_ai_command, true));
                                break;
                            }
                            $query_token_count = count(aiomatic_encode($title_ai_command));
                            $available_tokens = $max_tokens - $query_token_count;
                            if($available_tokens <= 16)
                            {
                                $string_len = strlen($title_ai_command);
                                $string_len = $string_len / 2;
                                $string_len = intval(0 - $string_len);
                                $title_ai_command = substr($title_ai_command, 0, $string_len);
                                $title_ai_command = trim($title_ai_command);
                                if(empty($title_ai_command))
                                {
                                    aiomatic_log_to_file('Empty API seed expression provided (after processing) ' . print_r($title_ai_command, true));
                                    break;
                                }
                                $query_token_count = count(aiomatic_encode($title_ai_command));
                                $available_tokens = $max_tokens - $query_token_count;
                            }
                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                            {
                                if(aiomatic_is_aiomaticapi_key($token))
                                {
                                    $api_service = 'AiomaticAPI';
                                }
                                else
                                {
                                    $api_service = 'OpenAI';
                                }
                                aiomatic_log_to_file('Calling ' . $api_service . ' for title text: ' . $title_ai_command);
                            }
                            $aierror = '';
                            $finish_reason = '';
                            $generated_text = aiomatic_generate_text($token, $title_model, $title_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'titleID' . $param, 0, $finish_reason, $aierror);
                            if($generated_text === false)
                            {
                                aiomatic_log_to_file('Title generator error: ' . $aierror);
                                break;
                            }
                            else
                            {
                                $ai_title = ucfirst(trim(trim(trim(trim($generated_text), '.'), ' “”‘’"\'')));
                                if($title_source == 'ai')
                                {
                                    $old_title = $post_title;
                                    $post_title = $ai_title;
                                }
                                else
                                {
                                    $post_title = str_ireplace('%%ai_generated_title%%', $ai_title, $post_title);
                                }
                            }
                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                            {
                                if(aiomatic_is_aiomaticapi_key($token))
                                {
                                    $api_service = 'AiomaticAPI';
                                }
                                else
                                {
                                    $api_service = 'OpenAI';
                                }
                                aiomatic_log_to_file('Successfully got API title result from ' . $api_service . ': ' . $post_title);
                            }
                        }
                    }
                    else
                    {
                        aiomatic_log_to_file('Empty AI title query entered.');
                    }
                }
                if(empty($post_title))
                {
                    continue;
                }
                if (strpos($post_title, '%%') === false)
                {
                    if (!isset($aiomatic_Main_Settings['do_not_check_duplicates']) || $aiomatic_Main_Settings['do_not_check_duplicates'] != 'on') 
                    {
                        $zap = get_page_by_title(html_entity_decode($post_title), OBJECT, $post_type);
                        if($zap !== null)
                        {
                            aiomatic_log_to_file('Post with specified title already existing, skipping it: ' . $post_title);
                            unset($post_title_lines[$current_index]);
                            continue;
                        }
                    }
                    $new_post_title = $post_title;
                }
                else
                {
                    $new_post_title = $post_title;
                    $new_post_title = aiomatic_replaceContentShortcodes($new_post_title, $img_attr, $ai_command);
                    if (!isset($aiomatic_Main_Settings['do_not_check_duplicates']) || $aiomatic_Main_Settings['do_not_check_duplicates'] != 'on') 
                    {
                        $zap = get_page_by_title(html_entity_decode($new_post_title), OBJECT, $post_type);
                        if($zap !== null)
                        {
                            aiomatic_log_to_file('Post with specified title already published, skipping it: ' . $new_post_title);
                            unset($post_title_lines[$current_index]);
                            continue;
                        }
                    }
                }
                $get_img = '';
                if($royalty_free == '1')
                {
                    if($enable_ai_images == '1')
                    {
                        $query_words = $post_title;
                        if($image_query == '')
                        {
                            $image_query = $temp_post;
                        }
                        if($orig_ai_command_image == '')
                        {
                            $orig_ai_command_image = $image_query;
                        }
                        if($orig_ai_command_image != '')
                        {
                            $ai_command_image = $orig_ai_command_image;
                            $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                            $ai_command_image = array_filter($ai_command_image);
                            if(count($ai_command_image) > 0)
                            {
                                $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                            }
                            else
                            {
                                $ai_command_image = '';
                            }
                            $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                            if(!empty($ai_command_image))
                            {
                                $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                            }
                            else
                            {
                                $ai_command_image = trim(strip_tags($post_title));
                            }
                            $ai_command_image = trim($ai_command_image);
                            if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                            {
                                $txt_content = aiomatic_get_web_page($ai_command_image);
                                if ($txt_content !== FALSE) 
                                {
                                    $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                    $txt_content = array_filter($txt_content);
                                    if(count($txt_content) > 0)
                                    {
                                        $txt_content = $txt_content[array_rand($txt_content)];
                                        if(trim($txt_content) != '') 
                                        {
                                            $ai_command_image = $txt_content;
                                            $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                            $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                        }
                                    }
                                }
                            }
                            if(empty($ai_command_image))
                            {
                                aiomatic_log_to_file('Empty API featured image seed expression provided!');
                            }
                            else
                            {
                                if(strlen($ai_command_image) > 400)
                                {
                                    $ai_command_image = substr($ai_command_image, 0, 400);
                                }
                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                {
                                    if(aiomatic_is_aiomaticapi_key($token))
                                    {
                                        $api_service = 'AiomaticAPI';
                                    }
                                    else
                                    {
                                        $api_service = 'OpenAI';
                                    }
                                    aiomatic_log_to_file('Calling ' . $api_service . ' for featured image: ' . $ai_command_image);
                                }
                                $aierror = '';
                                $get_img = aiomatic_generate_ai_image($token, 1, $ai_command_image, $image_size, 'featuredImage', 0, $aierror);
                                if($get_img !== false)
                                {
                                    foreach($get_img as $tmpimg)
                                    {
                                        $get_img = $tmpimg;
                                        break;
                                    }
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                    {
                                        aiomatic_log_to_file('AI generated featured image returned: ' . $get_img);
                                    }
                                }
                                else
                                {
                                    aiomatic_log_to_file('Failed to generate AI featured image: ' . $aierror);
                                    $get_img = '';
                                }
                            }
                        }
                        else
                        {
                            aiomatic_log_to_file('Empty AI featured image query entered.');
                        }
                    }
                    elseif($enable_ai_images == '2')
                    {
                        $query_words = $post_title;
                        if($image_query == '')
                        {
                            $image_query = $temp_post;
                        }
                        if($orig_ai_command_image == '')
                        {
                            $orig_ai_command_image = $image_query;
                        }
                        if($orig_ai_command_image != '')
                        {
                            $ai_command_image = $orig_ai_command_image;
                            $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                            $ai_command_image = array_filter($ai_command_image);
                            if(count($ai_command_image) > 0)
                            {
                                $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                            }
                            else
                            {
                                $ai_command_image = '';
                            }
                            $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                            if(!empty($ai_command_image))
                            {
                                $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                            }
                            else
                            {
                                $ai_command_image = trim(strip_tags($post_title));
                            }
                            $ai_command_image = trim($ai_command_image);
                            if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                            {
                                $txt_content = aiomatic_get_web_page($ai_command_image);
                                if ($txt_content !== FALSE) 
                                {
                                    $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                    $txt_content = array_filter($txt_content);
                                    if(count($txt_content) > 0)
                                    {
                                        $txt_content = $txt_content[array_rand($txt_content)];
                                        if(trim($txt_content) != '') 
                                        {
                                            $ai_command_image = $txt_content;
                                            $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                            $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                        }
                                    }
                                }
                            }
                            if(empty($ai_command_image))
                            {
                                aiomatic_log_to_file('Empty API featured image seed expression provided!');
                            }
                            else
                            {
                                if(strlen($ai_command_image) > 2000)
                                {
                                    $ai_command_image = substr($ai_command_image, 0, 2000);
                                }
                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                {
                                    $api_service = 'Stability.AI';
                                    aiomatic_log_to_file('Calling ' . $api_service . ' for featured image: ' . $ai_command_image);
                                }
                                if($image_size == '256x256')
                                {
                                    $width = '512';
                                    $height = '512';
                                }
                                elseif($image_size == '512x512')
                                {
                                    $width = '512';
                                    $height = '512';
                                }
                                elseif($image_size == '1024x1024')
                                {
                                    $width = '1024';
                                    $height = '1024';
                                }
                                else
                                {
                                    $width = '512';
                                    $height = '512';
                                }
                                $get_img = aiomatic_generate_stability_image($ai_command_image, $height, $width, 'featuredStableImage', 0, false);
                                if($get_img !== false)
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                    {
                                        aiomatic_log_to_file('AI generated featured image returned: ' . $get_img[1]);
                                    }
                                }
                                else
                                {
                                    aiomatic_log_to_file('Failed to generate Stability.AI featured image.');
                                    $get_img = '';
                                }
                            }
                        }
                        else
                        {
                            aiomatic_log_to_file('Empty AI featured image query entered.');
                        }
                    }
                    else
                    {
                        $image_query_set = false;
                        $query_words = '';
                        $ai_command_image = '';
                        if($orig_ai_command_image == '')
                        {
                            $orig_ai_command_image = $image_query;
                        }
                        if($orig_ai_command_image != '')
                        {
                            $ai_command_image = $orig_ai_command_image;
                            $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                            $ai_command_image = array_filter($ai_command_image);
                            if(count($ai_command_image) > 0)
                            {
                                $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                            }
                            else
                            {
                                $ai_command_image = '';
                            }
                            $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                            if(!empty($ai_command_image))
                            {
                                $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                            }
                            else
                            {
                                $ai_command_image = trim(strip_tags($post_title));
                            }
                            $ai_command_image = trim($ai_command_image);
                            if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                            {
                                $txt_content = aiomatic_get_web_page($ai_command_image);
                                if ($txt_content !== FALSE) 
                                {
                                    $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                    $txt_content = array_filter($txt_content);
                                    if(count($txt_content) > 0)
                                    {
                                        $txt_content = $txt_content[array_rand($txt_content)];
                                        if(trim($txt_content) != '') 
                                        {
                                            $ai_command_image = $txt_content;
                                            $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                            $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                        }
                                    }
                                }
                            }
                        }
                        if($ai_command_image != '')
                        {
                            $query_words = $ai_command_image;
                            $image_query = $ai_command_image;
                            $image_query_set = true;
                        }
                        if(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'textrazor')
                        {
                            if(isset($aiomatic_Main_Settings['textrazor_key']) && trim($aiomatic_Main_Settings['textrazor_key']) != '')
                            {
                                try
                                {
                                    if(!class_exists('TextRazor'))
                                    {
                                        require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                    }
                                    TextRazorSettings::setApiKey(trim($aiomatic_Main_Settings['textrazor_key']));
                                    $textrazor = new TextRazor();
                                    $textrazor->addExtractor('entities');
                                    $response = $textrazor->analyze($image_query);
                                    if (isset($response['response']['entities'])) 
                                    {
                                        foreach ($response['response']['entities'] as $entity) 
                                        {
                                            $query_words = '';
                                            if(isset($entity['entityEnglishId']))
                                            {
                                                $query_words = $entity['entityEnglishId'];
                                            }
                                            else
                                            {
                                                $query_words = $entity['entityId'];
                                            }
                                            if($query_words != '')
                                            {
                                                $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 10, true);
                                                if(!empty($z_img))
                                                {
                                                    $get_img = $z_img;
                                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                        aiomatic_log_to_file('Royalty Free Image Generated with help of TextRazor (kw: "' . $query_words . '"): ' . $z_img);
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                catch(Exception $e)
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                        aiomatic_log_to_file('Failed to search for keywords using TextRazor (2): ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                        elseif(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'openai')
                        {
                            if(isset($aiomatic_Main_Settings['keyword_prompts']) && trim($aiomatic_Main_Settings['keyword_prompts']) != '')
                            {
                                if(isset($aiomatic_Main_Settings['keyword_model']) && $aiomatic_Main_Settings['keyword_model'] != '')
                                {
                                    $kw_model = $aiomatic_Main_Settings['keyword_model'];
                                }
                                else
                                {
                                    $kw_model = 'text-davinci-003';
                                }
                                $title_ai_command = trim($aiomatic_Main_Settings['keyword_prompts']);
                                $title_ai_command = preg_split('/\r\n|\r|\n/', $title_ai_command);
                                $title_ai_command = array_filter($title_ai_command);
                                if(count($title_ai_command) > 0)
                                {
                                    $title_ai_command = $title_ai_command[array_rand($title_ai_command)];
                                }
                                else
                                {
                                    $title_ai_command = '';
                                }
                                $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                if(!empty($title_ai_command))
                                {
                                    $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                }
                                $title_ai_command = trim($title_ai_command);
                                if (filter_var($title_ai_command, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($title_ai_command, '.txt'))
                                {
                                    $txt_content = aiomatic_get_web_page($title_ai_command);
                                    if ($txt_content !== FALSE) 
                                    {
                                        $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                        $txt_content = array_filter($txt_content);
                                        if(count($txt_content) > 0)
                                        {
                                            $txt_content = $txt_content[array_rand($txt_content)];
                                            if(trim($txt_content) != '') 
                                            {
                                                $title_ai_command = $txt_content;
                                                $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                                $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                            }
                                        }
                                    }
                                }
                                if(empty($title_ai_command))
                                {
                                    aiomatic_log_to_file('Empty API keyword extractor seed expression provided!');
                                }
                                else
                                {
                                    $title_ai_command = 'Extract a comma separated list of relevant keywords from the text: ' . trim(strip_tags($post_title));
                                    if(strlen($title_ai_command) > $max_seed_tokens * 4)
                                    {
                                        $title_ai_command = substr($title_ai_command, 0, (0 - ($max_seed_tokens * 4)));
                                    }
                                    $title_ai_command = trim($title_ai_command);
                                    if(empty($title_ai_command))
                                    {
                                        aiomatic_log_to_file('Empty API title seed expression provided(3)! ' . print_r($title_ai_command, true));
                                    }
                                    else
                                    {
                                        $query_token_count = count(aiomatic_encode($title_ai_command));
                                        $available_tokens = $max_tokens - $query_token_count;
                                        if($available_tokens <= 16)
                                        {
                                            $string_len = strlen($title_ai_command);
                                            $string_len = $string_len / 2;
                                            $string_len = intval(0 - $string_len);
                                            $title_ai_command = substr($title_ai_command, 0, $string_len);
                                            $title_ai_command = trim($title_ai_command);
                                            $query_token_count = count(aiomatic_encode($title_ai_command));
                                            $available_tokens = $max_tokens - $query_token_count;
                                        }
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                        {
                                            if(aiomatic_is_aiomaticapi_key($token))
                                            {
                                                $api_service = 'AiomaticAPI';
                                            }
                                            else
                                            {
                                                $api_service = 'OpenAI';
                                            }
                                            aiomatic_log_to_file('Calling ' . $api_service . ' for title text: ' . $title_ai_command);
                                        }
                                        $aierror = '';
                                        $finish_reason = '';
                                        $generated_text = aiomatic_generate_text($token, $kw_model, $title_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'keywordID' . $param, 0, $finish_reason, $aierror);
                                        if($generated_text === false)
                                        {
                                            aiomatic_log_to_file('Keyword generator error: ' . $aierror);
                                            $ai_title = '';
                                        }
                                        else
                                        {
                                            $ai_title = trim(trim(trim(trim($generated_text), '.'), ' “”‘’"\''));
                                            $ai_titles = explode(',', $ai_title);
                                            foreach($ai_titles as $query_words)
                                            {
                                                $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, trim($query_words), $img_attr, 10, true);
                                                if(!empty($z_img))
                                                {
                                                    $get_img = $z_img;
                                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                        aiomatic_log_to_file('Royalty Free Image Generated with help of AI (kw: "' . $query_words . '"): ' . $z_img);
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                        {
                                            if(aiomatic_is_aiomaticapi_key($token))
                                            {
                                                $api_service = 'AiomaticAPI';
                                            }
                                            else
                                            {
                                                $api_service = 'OpenAI';
                                            }
                                            aiomatic_log_to_file('Successfully got API keyword result from ' . $api_service . ': ' . $ai_title);
                                        }
                                    }
                                }
                            }
                        }
                        if(empty($get_img))
                        {
                            if($image_query_set == true && $image_query != '')
                            {
                                $get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $image_query, $img_attr, 10, true);
                                if($get_img == '' || $get_img === false)
                                {
                                    if(isset($aiomatic_Main_Settings['bimage']) && $aiomatic_Main_Settings['bimage'] == 'on')
                                    {
                                        $image_query = $keyword_class->keywords($image_query, 1);
                                        $get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $image_query, $img_attr, 20, true);
                                    }
                                }
                            }
                            if(empty($get_img))
                            {
                                $keyword_class = new Aiomatic_keywords();
                                $query_words = $keyword_class->keywords($post_title, 2);
                                $get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 10, true);
                                if($get_img == '' || $get_img === false)
                                {
                                    if(isset($aiomatic_Main_Settings['bimage']) && $aiomatic_Main_Settings['bimage'] == 'on')
                                    {
                                        $query_words = $keyword_class->keywords($post_title, 1);
                                        $get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 20, true);
                                        if($get_img == '' || $get_img === false)
                                        {
                                            if(isset($aiomatic_Main_Settings['no_royalty_skip']) && $aiomatic_Main_Settings['no_royalty_skip'] == 'on')
                                            {
                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                    aiomatic_log_to_file('Skipping importing because no royalty free image found.');
                                                }
                                                unset($post_title_lines[$current_index]);
                                                continue;
                                            }
                                        }
                                    }
                                    else
                                    {
                                        if(isset($aiomatic_Main_Settings['no_royalty_skip']) && $aiomatic_Main_Settings['no_royalty_skip'] == 'on')
                                        {
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                aiomatic_log_to_file('Skipping importing because no royalty free image found.');
                                            }
                                            unset($post_title_lines[$current_index]);
                                            continue;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if (isset($aiomatic_Main_Settings['spin_text']) && $aiomatic_Main_Settings['spin_text'] !== 'disabled') 
                {
                    $already_spinned = '1';
                }
                $my_post                              = array();
                $my_post['aiomatic_post_image']       = $get_img;
                if($enable_ai_images == '2')
                {
                    $my_post['aiomatic_local_image']      = '1';
                }
                else
                {
                    $my_post['aiomatic_local_image']      = '0';
                }
                $my_post['aiomatic_enable_pingbacks'] = $enable_pingback;
                $my_post['default_category']          = $default_category;
                $my_post['post_type']                 = $post_type;
                $my_post['comment_status']            = $accept_comments;
                $my_post['post_status']               = $post_status;
                $my_post['post_author']               = $post_user_name;
                $ai_command = $orig_ai_command;
                $ai_command = aiomatic_replaceSynergyShortcodes($ai_command);
                if(!empty($ai_command))
                {
                    $aicontent = replaceAIPostShortcodes($ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                }
                else
                {
                    $aicontent = trim(strip_tags($post_title));
                }
                if (filter_var($aicontent, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($aicontent, '.txt'))
                {
                    $txt_content = aiomatic_get_web_page($aicontent);
                    if ($txt_content !== FALSE) 
                    {
                        $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                        $txt_content = array_filter($txt_content);
                        if(count($txt_content) > 0)
                        {
                            $txt_content = $txt_content[array_rand($txt_content)];
                            if(trim($txt_content) != '') 
                            {
                                $aicontent = $txt_content;
                                $aicontent = aiomatic_replaceSynergyShortcodes($aicontent);
                                $aicontent = replaceAIPostShortcodes($aicontent, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                            }
                        }
                    }
                }
                $last_char = substr($aicontent, -1);
                if(!ctype_punct($last_char))
                {
                    $aicontent .= '.';
                }
                if(strlen($aicontent) > $max_seed_tokens * 4)
                {
                    $aicontent = substr($aicontent, 0, (0-($max_seed_tokens * 4)));
                }
                $aicontent = trim($aicontent);
                if(empty($aicontent))
                {
                    aiomatic_log_to_file('Empty API seed expression provided! ' . print_r($ai_command, true));
                    break;
                }
                $query_token_count = count(aiomatic_encode($aicontent));
                $available_tokens = $max_tokens - $query_token_count;
                if($available_tokens <= 16)
                {
                    $string_len = strlen($aicontent);
                    $string_len = $string_len / 2;
                    $string_len = intval(0 - $string_len);
                    $aicontent = substr($aicontent, 0, $string_len);
                    $aicontent = trim($aicontent);
                    if(empty($aicontent))
                    {
                        aiomatic_log_to_file('Empty API seed expression provided (after processing) ' . print_r($ai_command, true));
                        break;
                    }
                    $query_token_count = count(aiomatic_encode($aicontent));
                    $available_tokens = $max_tokens - $query_token_count;
                }
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                {
                    if(aiomatic_is_aiomaticapi_key($token))
                    {
                        $api_service = 'AiomaticAPI';
                    }
                    else
                    {
                        $api_service = 'OpenAI';
                    }
                    aiomatic_log_to_file('Calling ' . $api_service . ' for text: ' . $aicontent);
                }
                $aierror = '';
                $finish_reason = '';
                $generated_text = aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'contentID' . $param, 0, $finish_reason, $aierror);
                if($generated_text === false)
                {
                    aiomatic_log_to_file($aierror);
                    break;
                }
                else
                {
                    $new_post_content = ucfirst(trim(nl2br(trim($generated_text))));
                }
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                {
                    if(aiomatic_is_aiomaticapi_key($token))
                    {
                        $api_service = 'AiomaticAPI';
                    }
                    else
                    {
                        $api_service = 'OpenAI';
                    }
                    aiomatic_log_to_file('Successfully got API result from ' . $api_service . '.');
                }
                if($min_char == '')
                {
                    $min_char = 0;
                }
                else
                {
                    $min_char = intval($min_char);
                }
                $cnt = 1;
                $max_fails = 10;
                $failed_calls = 0;
                $heading_results = $headings_arr;
                if($headings != '' && is_numeric($headings))
                {
                    if(count($heading_results) < $headings)
                    {
                        $heading_results_ai = aiomatic_scrape_related_questions($new_post_title, $headings, $model, $temperature, $top_p, $presence_penalty, $frequency_penalty, $max_tokens);
                        $heading_results = array_merge($heading_results, $heading_results_ai);
                    }
                }
                $ai_retry = false;
                if($image_size == '')
                {
                    $image_size = '256x256';
                }
                if(strlen($new_post_content) > $min_char)
                {
                    $add_my_image = '';
                    $temp_get_img = '';
                    if(count($heading_results) > 0)
                    {
                        $rand_heading = '';
                        $saverand = array_rand($heading_results);
                        $rand_heading = $heading_results[$saverand];
                        unset($heading_results[$saverand]);
                        if(isset($rand_heading['q']))
                        {
                            $rand_heading['q'] = preg_replace('#^\d+\.([\s\S]*)#i', '$1', $rand_heading['q']);
                            $heading_val = '<h2>' . $rand_heading['q'] . '</h2>';
                            if($rand_heading['a'] != '')
                            {
                                $heading_val .= '<span>' . $rand_heading['a'] . '</span>';
                            }
                            $image_query = $rand_heading['q'];
                        }
                    }
                    if($heading_val == '')
                    {
                        $temp_post = trim($new_post_content);
                    }
                    else
                    {
                        $temp_post = trim($heading_val);
                    }
                    
                    if($images != '' && is_numeric($images) && $images > $added_images)
                    {
                        $query_words = $post_title;
                        if($image_query == '')
                        {
                            $image_query = $temp_post;
                        }
                        if($enable_ai_images == '1')
                        {
                            if($orig_ai_command_image == '')
                            {
                                $orig_ai_command_image = $image_query;
                            }
                            if($orig_ai_command_image != '')
                            {
                                $ai_command_image = $orig_ai_command_image;
                                $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                                $ai_command_image = array_filter($ai_command_image);
                                if(count($ai_command_image) > 0)
                                {
                                    $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                                }
                                else
                                {
                                    $ai_command_image = '';
                                }
                                $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                if(!empty($ai_command_image))
                                {
                                    $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                }
                                else
                                {
                                    $ai_command_image = trim(strip_tags($post_title));
                                }
                                $ai_command_image = trim($ai_command_image);
                                if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                                {
                                    $txt_content = aiomatic_get_web_page($ai_command_image);
                                    if ($txt_content !== FALSE) 
                                    {
                                        $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                        $txt_content = array_filter($txt_content);
                                        if(count($txt_content) > 0)
                                        {
                                            $txt_content = $txt_content[array_rand($txt_content)];
                                            if(trim($txt_content) != '') 
                                            {
                                                $ai_command_image = $txt_content;
                                                $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                                $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                            }
                                        }
                                    }
                                }
                                if(empty($ai_command_image))
                                {
                                    aiomatic_log_to_file('Empty API image seed expression provided!');
                                }
                                else
                                {
                                    if(strlen($ai_command_image) > 400)
                                    {
                                        $ai_command_image = substr($ai_command_image, 0, 400);
                                    }
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                    {
                                        if(aiomatic_is_aiomaticapi_key($token))
                                        {
                                            $api_service = 'AiomaticAPI';
                                        }
                                        else
                                        {
                                            $api_service = 'OpenAI';
                                        }
                                        aiomatic_log_to_file('Calling ' . $api_service . ' for image: ' . $ai_command_image);
                                    }
                                    $aierror = '';
                                    $temp_get_imgs = aiomatic_generate_ai_image($token, 1, $ai_command_image, $image_size, 'contentImage', 0, $aierror);
                                    if($temp_get_imgs !== false)
                                    {
                                        foreach($temp_get_imgs as $tmpimg)
                                        {
                                            $added_images++;
                                            $added_img_list[] = $tmpimg;
                                            $temp_get_img = $tmpimg;
                                        }
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                        {
                                            aiomatic_log_to_file('AI generated image returned: ' . $temp_get_img);
                                        }
                                    }
                                    else
                                    {
                                        aiomatic_log_to_file('Failed to generate AI image: ' . $aierror);
                                        $temp_get_img = '';
                                    }
                                }
                            }
                            else
                            {
                                aiomatic_log_to_file('Empty AI image query entered.');
                            }
                        }
                        elseif($enable_ai_images == '2')
                        {
                            if($orig_ai_command_image == '')
                            {
                                $orig_ai_command_image = $image_query;
                            }
                            if($orig_ai_command_image != '')
                            {
                                $ai_command_image = $orig_ai_command_image;
                                $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                                $ai_command_image = array_filter($ai_command_image);
                                if(count($ai_command_image) > 0)
                                {
                                    $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                                }
                                else
                                {
                                    $ai_command_image = '';
                                }
                                $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                if(!empty($ai_command_image))
                                {
                                    $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                }
                                else
                                {
                                    $ai_command_image = trim(strip_tags($post_title));
                                }
                                $ai_command_image = trim($ai_command_image);
                                if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                                {
                                    $txt_content = aiomatic_get_web_page($ai_command_image);
                                    if ($txt_content !== FALSE) 
                                    {
                                        $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                        $txt_content = array_filter($txt_content);
                                        if(count($txt_content) > 0)
                                        {
                                            $txt_content = $txt_content[array_rand($txt_content)];
                                            if(trim($txt_content) != '') 
                                            {
                                                $ai_command_image = $txt_content;
                                                $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                                $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                            }
                                        }
                                    }
                                }
                                if(empty($ai_command_image))
                                {
                                    aiomatic_log_to_file('Empty API image seed expression provided!');
                                }
                                else
                                {
                                    if(strlen($ai_command_image) > 2000)
                                    {
                                        $ai_command_image = substr($ai_command_image, 0, 2000);
                                    }
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                    {
                                        $api_service = 'Stability.AI';
                                        aiomatic_log_to_file('Calling ' . $api_service . ' for image: ' . $ai_command_image);
                                    }
                                    if($image_size == '256x256')
                                    {
                                        $width = '512';
                                        $height = '512';
                                    }
                                    elseif($image_size == '512x512')
                                    {
                                        $width = '512';
                                        $height = '512';
                                    }
                                    elseif($image_size == '1024x1024')
                                    {
                                        $width = '1024';
                                        $height = '1024';
                                    }
                                    else
                                    {
                                        $width = '512';
                                        $height = '512';
                                    }
                                    $temp_get_imgs = aiomatic_generate_stability_image($ai_command_image, $height, $width, 'contentStableImage', 0, false);
                                    if($temp_get_imgs !== false)
                                    {
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                        {
                                            aiomatic_log_to_file('AI generated image returned: ' . $temp_get_imgs[1]);
                                        }
                                        $added_images++;
                                        $added_img_list[] = $temp_get_imgs[1];
                                        $temp_get_img = $temp_get_imgs[1];
                                    }
                                    else
                                    {
                                        aiomatic_log_to_file('Failed to generate Stability.AI image.');
                                        $temp_get_img = '';
                                    }
                                }
                            }
                            else
                            {
                                aiomatic_log_to_file('Empty AI image query entered.');
                            }
                        }
                        elseif(count($images_arr) > 0)
                        {
                            $first_el = array_shift($images_arr);
                            $first_el = aiomatic_replaceSynergyShortcodes($first_el);
                            $added_images++;
                            $added_img_list[] = $first_el;
                            $temp_get_img = $first_el;
                        }
                        else
                        {
                            $image_query_set = false;
                            $query_words = '';
                            $ai_command_image = '';
                            if($orig_ai_command_image == '')
                            {
                                $orig_ai_command_image = $image_query;
                            }
                            if($orig_ai_command_image != '')
                            {
                                $ai_command_image = $orig_ai_command_image;
                                $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                                $ai_command_image = array_filter($ai_command_image);
                                if(count($ai_command_image) > 0)
                                {
                                    $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                                }
                                else
                                {
                                    $ai_command_image = '';
                                }
                                $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                if(!empty($ai_command_image))
                                {
                                    $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                }
                                else
                                {
                                    $ai_command_image = trim(strip_tags($post_title));
                                }
                                $ai_command_image = trim($ai_command_image);
                                if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                                {
                                    $txt_content = aiomatic_get_web_page($ai_command_image);
                                    if ($txt_content !== FALSE) 
                                    {
                                        $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                        $txt_content = array_filter($txt_content);
                                        if(count($txt_content) > 0)
                                        {
                                            $txt_content = $txt_content[array_rand($txt_content)];
                                            if(trim($txt_content) != '') 
                                            {
                                                $ai_command_image = $txt_content;
                                                $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                                $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                            }
                                        }
                                    }
                                }
                            }
                            if($ai_command_image != '')
                            {
                                $query_words = $ai_command_image;
                                $image_query = $ai_command_image;
                                $image_query_set = true;
                            }
                            if(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'textrazor')
                            {
                                if(isset($aiomatic_Main_Settings['textrazor_key']) && trim($aiomatic_Main_Settings['textrazor_key']) != '')
                                {
                                    try
                                    {
                                        if(!class_exists('TextRazor'))
                                        {
                                            require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                        }
                                        TextRazorSettings::setApiKey(trim($aiomatic_Main_Settings['textrazor_key']));
                                        $textrazor = new TextRazor();
                                        $textrazor->addExtractor('entities');
                                        $response = $textrazor->analyze($image_query);
                                        if (isset($response['response']['entities'])) 
                                        {
                                            foreach ($response['response']['entities'] as $entity) 
                                            {
                                                $query_words = '';
                                                if(isset($entity['entityEnglishId']))
                                                {
                                                    $query_words = $entity['entityEnglishId'];
                                                }
                                                else
                                                {
                                                    $query_words = $entity['entityId'];
                                                }
                                                if($query_words != '')
                                                {
                                                    $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 10, false);
                                                    if(!empty($z_img))
                                                    {
                                                        $added_images++;
                                                        $added_img_list[] = $z_img;
                                                        $temp_get_img = $z_img;
                                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                            aiomatic_log_to_file('Royalty Free Image Generated with help of TextRazor (kw: "' . $query_words . '"): ' . $z_img);
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    catch(Exception $e)
                                    {
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                            aiomatic_log_to_file('Failed to search for keywords using TextRazor (2): ' . $e->getMessage());
                                        }
                                    }
                                }
                            }
                            elseif(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'openai')
                            {
                                if(isset($aiomatic_Main_Settings['keyword_prompts']) && trim($aiomatic_Main_Settings['keyword_prompts']) != '')
                                {
                                    if(isset($aiomatic_Main_Settings['keyword_model']) && $aiomatic_Main_Settings['keyword_model'] != '')
                                    {
                                        $kw_model = $aiomatic_Main_Settings['keyword_model'];
                                    }
                                    else
                                    {
                                        $kw_model = 'text-davinci-003';
                                    }
                                    $title_ai_command = trim($aiomatic_Main_Settings['keyword_prompts']);
                                    $title_ai_command = preg_split('/\r\n|\r|\n/', $title_ai_command);
                                    $title_ai_command = array_filter($title_ai_command);
                                    if(count($title_ai_command) > 0)
                                    {
                                        $title_ai_command = $title_ai_command[array_rand($title_ai_command)];
                                    }
                                    else
                                    {
                                        $title_ai_command = '';
                                    }
                                    $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                    if(!empty($title_ai_command))
                                    {
                                        $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                    }
                                    $title_ai_command = trim($title_ai_command);
                                    if (filter_var($title_ai_command, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($title_ai_command, '.txt'))
                                    {
                                        $txt_content = aiomatic_get_web_page($title_ai_command);
                                        if ($txt_content !== FALSE) 
                                        {
                                            $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                            $txt_content = array_filter($txt_content);
                                            if(count($txt_content) > 0)
                                            {
                                                $txt_content = $txt_content[array_rand($txt_content)];
                                                if(trim($txt_content) != '') 
                                                {
                                                    $title_ai_command = $txt_content;
                                                    $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                                    $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                                }
                                            }
                                        }
                                    }
                                    if(empty($title_ai_command))
                                    {
                                        aiomatic_log_to_file('Empty API keyword extractor seed expression provided!');
                                    }
                                    else
                                    {
                                        $title_ai_command = 'Extract a comma separated list of relevant keywords from the text: ' . trim(strip_tags($post_title));
                                        if(strlen($title_ai_command) > $max_seed_tokens * 4)
                                        {
                                            $title_ai_command = substr($title_ai_command, 0, (0 - ($max_seed_tokens * 4)));
                                        }
                                        $title_ai_command = trim($title_ai_command);
                                        if(empty($title_ai_command))
                                        {
                                            aiomatic_log_to_file('Empty API title seed expression provided(4)! ' . print_r($title_ai_command, true));
                                        }
                                        else
                                        {
                                            $query_token_count = count(aiomatic_encode($title_ai_command));
                                            $available_tokens = $max_tokens - $query_token_count;
                                            if($available_tokens <= 16)
                                            {
                                                $string_len = strlen($title_ai_command);
                                                $string_len = $string_len / 2;
                                                $string_len = intval(0 - $string_len);
                                                $title_ai_command = substr($title_ai_command, 0, $string_len);
                                                $title_ai_command = trim($title_ai_command);
                                                $query_token_count = count(aiomatic_encode($title_ai_command));
                                                $available_tokens = $max_tokens - $query_token_count;
                                            }
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                            {
                                                if(aiomatic_is_aiomaticapi_key($token))
                                                {
                                                    $api_service = 'AiomaticAPI';
                                                }
                                                else
                                                {
                                                    $api_service = 'OpenAI';
                                                }
                                                aiomatic_log_to_file('Calling ' . $api_service . ' for title text: ' . $title_ai_command);
                                            }
                                            $aierror = '';
                                            $finish_reason = '';
                                            $generated_text = aiomatic_generate_text($token, $kw_model, $title_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'keywordID' . $param, 0, $finish_reason, $aierror);
                                            if($generated_text === false)
                                            {
                                                aiomatic_log_to_file('Keyword generator error: ' . $aierror);
                                                $ai_title = '';
                                            }
                                            else
                                            {
                                                $ai_title = trim(trim(trim(trim($generated_text), '.'), ' “”‘’"\''));
                                                $ai_titles = explode(',', $ai_title);
                                                foreach($ai_titles as $query_words)
                                                {
                                                    $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, trim($query_words), $img_attr, 10, false);
                                                    if(!empty($z_img))
                                                    {
                                                        $added_images++;
                                                        $added_img_list[] = $z_img;
                                                        $temp_get_img = $z_img;
                                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                            aiomatic_log_to_file('Royalty Free Image Generated with help of AI (kw: "' . $query_words . '"): ' . $z_img);
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                            {
                                                if(aiomatic_is_aiomaticapi_key($token))
                                                {
                                                    $api_service = 'AiomaticAPI';
                                                }
                                                else
                                                {
                                                    $api_service = 'OpenAI';
                                                }
                                                aiomatic_log_to_file('Successfully got API keyword result from ' . $api_service . ': ' . $ai_title);
                                            }
                                        }
                                    }
                                }
                            }
                            if(empty($temp_get_img))
                            {
                                $keyword_class = new Aiomatic_keywords();
                                $query_words = $keyword_class->keywords($image_query, 2);
                                $temp_img_attr = '';
                                $temp_get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $temp_img_attr, 10, false);
                                if($temp_get_img == '' || $temp_get_img === false)
                                {
                                    $query_words = $keyword_class->keywords($image_query, 1);
                                    $temp_get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $temp_img_attr, 20, false);
                                    if($temp_get_img == '' || $temp_get_img === false)
                                    {
                                        $temp_get_img = '';
                                    }
                                    else
                                    {
                                        if(!in_array($temp_get_img, $added_img_list))
                                        {
                                            $added_images++;
                                            $added_img_list[] = $temp_get_img;
                                        }
                                        else
                                        {
                                            $temp_get_img = '';
                                        }
                                    }
                                }
                                else
                                {
                                    if(!in_array($temp_get_img, $added_img_list))
                                    {
                                        $added_images++;
                                        $added_img_list[] = $temp_get_img;
                                    }
                                    else
                                    {
                                        $temp_get_img = '';
                                    }
                                }
                            }
                        }
                        if($temp_get_img != '')
                        {
                            $add_my_image = '<br/><img class="aiomatic_image_class" src="' . $temp_get_img . '" alt="' . $query_words . '"><br/>';
                        }
                    }
                    if($heading_val == '')
                    {
                        $new_post_content = $add_my_image . $new_post_content;
                    }
                    else
                    {
                        $new_post_content = $add_my_image . $heading_val . ' ' . $new_post_content;
                    }
                }
                else
                {
                    $ai_continue_title = $post_title;
                    while(strlen(strip_tags($new_post_content)) < $min_char)
                    {
                        $just_set_fallback = false;
                        $image_query = '';
                        $heading_val = '';
                        if(count($heading_results) > 0)
                        {
                            $rand_heading = '';
                            $saverand = array_rand($heading_results);
                            $rand_heading = $heading_results[$saverand];
                            unset($heading_results[$saverand]);
                            if(isset($rand_heading['q']))
                            {
                                $rand_heading['q'] = preg_replace('#^\d+\.([\s\S]*)#i', '$1', $rand_heading['q']);
                                $heading_val = '<h2>' . $rand_heading['q'] . '</h2>' . '<span>' . $rand_heading['a'];
                                $image_query = $rand_heading['q'];
                            }
                        }
                        
                        if($heading_val == '')
                        {
                            $temp_post = trim($new_post_content);
                        }
                        else
                        {
                            $temp_post = trim($heading_val);
                        }
                        if(strlen($temp_post) > $max_continue_tokens * 4)
                        {
                            $negative_contiue_tokens = 0 - ($max_continue_tokens * 4);
                            $newaicontent = substr($temp_post, $negative_contiue_tokens);
                        }
                        else
                        {
                            $newaicontent = $temp_post;
                        }
                        $add_me_to_text = '';
                        if($ai_retry == true)
                        {
                            $just_set_fallback = true;
                            if(count($headings_arr_copy) == 0)
                            {
                                if (isset($aiomatic_Main_Settings['alternate_continue']) && $aiomatic_Main_Settings['alternate_continue'] == 'on')
                                {
                                    $newaicontent = $newaicontent . ' ' . $ai_continue_title;
                                }
                                else
                                {
                                    $aierror = '';
                                    $finish_reason = '';
                                    $generated_text = aiomatic_generate_text($token, $model, 'Write a People Also Asked question related to "' . $ai_continue_title . '"', 2048, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'headingID' . $param, 0, $finish_reason, $aierror);
                                    if($generated_text === false)
                                    {
                                        aiomatic_log_to_file('Similarity finding failed: ' . $aierror);
                                        $newaicontent = $aicontent;
                                    }
                                    else
                                    {
                                        $newaicontent = ucfirst(trim(nl2br(trim($generated_text))));
                                        if(empty($newaicontent))
                                        {
                                            $newaicontent = $aicontent;
                                        }
                                        else
                                        {
                                            $newaicontent = preg_replace('#^\d+\.([\s\S]*)#i', '$1', $newaicontent);
                                            $add_me_to_text = '<h3>' . $newaicontent . '</h3> ';
                                            $ai_continue_title = $newaicontent;
                                        }
                                    }
                                }
                            }
                            else
                            {
                                $randomIndex = array_rand($headings_arr_copy);
                                $newaicontent = $headings_arr_copy[$randomIndex];
                                unset($headings_arr_copy[$randomIndex]);
                                $newaicontent = preg_replace('#^\d+\.([\s\S]*)#i', '$1', $newaicontent);
                                $add_me_to_text = '<h3>' . $newaicontent . '</h3> ';
                            }
                        }
                        $ai_retry = false;
                        $newaicontent = trim($newaicontent);
                        $query_token_count = count(aiomatic_encode($newaicontent));
                        $available_tokens = $max_tokens - $query_token_count;
                        if($available_tokens <= 16)
                        {
                            $string_len = strlen($newaicontent);
                            $string_len = $string_len / 2;
                            $string_len = intval(0 - $string_len);
                            $newaicontent = substr($newaicontent, 0, $string_len);
                            $newaicontent = trim($newaicontent);
                            if(empty($newaicontent))
                            {
                                aiomatic_log_to_file('Empty API seed expression provided (after processing) ' . print_r($temp_post, true));
                                break;
                            }
                            $query_token_count = count(aiomatic_encode($newaicontent));
                            $available_tokens = $max_tokens - $query_token_count;
                        }
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                            if(aiomatic_is_aiomaticapi_key($token))
                            {
                                $api_service = 'AiomaticAPI';
                            }
                            else
                            {
                                $api_service = 'OpenAI';
                            }
                            $rwair = '';
                            if($just_set_fallback == true)
                            {
                                $rwair = '(fallback)';
                            }
                            aiomatic_log_to_file('Calling ' . $api_service . ' again (' . $cnt . ')' . $rwair . ', to meet minimum character limit: ' . $min_char . ' - current char count: ' . strlen(strip_tags($new_post_content)));
                        }
                        $aiwriter = '';
                        $aierror = '';
                        $finish_reason = '';
                        $generated_text = aiomatic_generate_text($token, $model, $newaicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'contentID' . $param, 0, $finish_reason, $aierror);
                        if($generated_text === false)
                        {
                            aiomatic_log_to_file($aierror);
                            break;
                        }
                        else
                        {
                            $aiwriter = $add_me_to_text . ucfirst(trim(nl2br(trim($generated_text))));
                        }
                        $add_my_image = '';
                        $temp_get_img = '';
                        if($aiwriter == '')
                        {
                            $ai_retry = true;
                            if($just_set_fallback == true)
                            {
                                aiomatic_log_to_file('Ending execution, already retried once');
                                break;
                            }
                            continue;
                        }
                        if($images != '' && is_numeric($images) && $images > $added_images)
                        {
                            $image_query_set = false;
                            $query_words = '';
                            $ai_command_image = '';
                            if($orig_ai_command_image == '')
                            {
                                $orig_ai_command_image = $image_query;
                            }
                            if($orig_ai_command_image != '')
                            {
                                $ai_command_image = $orig_ai_command_image;
                                $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                                $ai_command_image = array_filter($ai_command_image);
                                if(count($ai_command_image) > 0)
                                {
                                    $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                                }
                                else
                                {
                                    $ai_command_image = '';
                                }
                                $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                if(!empty($ai_command_image))
                                {
                                    $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                }
                                else
                                {
                                    $ai_command_image = trim(strip_tags($post_title));
                                }
                                $ai_command_image = trim($ai_command_image);
                                if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                                {
                                    $txt_content = aiomatic_get_web_page($ai_command_image);
                                    if ($txt_content !== FALSE) 
                                    {
                                        $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                        $txt_content = array_filter($txt_content);
                                        if(count($txt_content) > 0)
                                        {
                                            $txt_content = $txt_content[array_rand($txt_content)];
                                            if(trim($txt_content) != '') 
                                            {
                                                $ai_command_image = $txt_content;
                                                $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                                $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                            }
                                        }
                                    }
                                }
                            }
                            if($ai_command_image != '')
                            {
                                $query_words = $ai_command_image;
                                $image_query = $ai_command_image;
                                $image_query_set = true;
                            }
                            else
                            {
                                $query_words = $post_title;
                            }
                            if($image_query == '')
                            {
                                $image_query = $temp_post;
                            }
                            if($enable_ai_images == '1')
                            {
                                if($orig_ai_command_image == '')
                                {
                                    $orig_ai_command_image = $image_query;
                                }
                                if($orig_ai_command_image != '')
                                {
                                    $ai_command_image = $orig_ai_command_image;
                                    $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                                    $ai_command_image = array_filter($ai_command_image);
                                    if(count($ai_command_image) > 0)
                                    {
                                        $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                                    }
                                    else
                                    {
                                        $ai_command_image = '';
                                    }
                                    $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                    if(!empty($ai_command_image))
                                    {
                                        $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                    }
                                    else
                                    {
                                        $ai_command_image = trim(strip_tags($post_title));
                                    }
                                    $ai_command_image = trim($ai_command_image);
                                    if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                                    {
                                        $txt_content = aiomatic_get_web_page($ai_command_image);
                                        if ($txt_content !== FALSE) 
                                        {
                                            $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                            $txt_content = array_filter($txt_content);
                                            if(count($txt_content) > 0)
                                            {
                                                $txt_content = $txt_content[array_rand($txt_content)];
                                                if(trim($txt_content) != '') 
                                                {
                                                    $ai_command_image = $txt_content;
                                                    $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                                    $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                                }
                                            }
                                        }
                                    }
                                    if(empty($ai_command_image))
                                    {
                                        aiomatic_log_to_file('Empty API image seed expression provided!');
                                    }
                                    else
                                    {
                                        if(strlen($ai_command_image) > 400)
                                        {
                                            $ai_command_image = substr($ai_command_image, 0, 400);
                                        }
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                        {
                                            if(aiomatic_is_aiomaticapi_key($token))
                                            {
                                                $api_service = 'AiomaticAPI';
                                            }
                                            else
                                            {
                                                $api_service = 'OpenAI';
                                            }
                                            aiomatic_log_to_file('Calling ' . $api_service . ' for image: ' . $ai_command_image);
                                        }
                                        $aierror = '';
                                        $temp_get_imgs = aiomatic_generate_ai_image($token, 1, $ai_command_image, $image_size, 'contentImage', 0, $aierror);
                                        if($temp_get_imgs !== false)
                                        {
                                            foreach($temp_get_imgs as $tmpimg)
                                            {
                                                $added_images++;
                                                $added_img_list[] = $tmpimg;
                                                $temp_get_img = $tmpimg;
                                            }
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                            {
                                                aiomatic_log_to_file('AI generated image returned: ' . $temp_get_img);
                                            }
                                        }
                                        else
                                        {
                                            aiomatic_log_to_file('Failed to generate AI image: ' . $aierror);
                                            $temp_get_img = '';
                                        }
                                    }
                                }
                                else
                                {
                                    aiomatic_log_to_file('Empty AI image query entered.');
                                }
                            }
                            elseif($enable_ai_images == '2')
                            {
                                if($orig_ai_command_image == '')
                                {
                                    $orig_ai_command_image = $image_query;
                                }
                                if($orig_ai_command_image != '')
                                {
                                    $ai_command_image = $orig_ai_command_image;
                                    $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                                    $ai_command_image = array_filter($ai_command_image);
                                    if(count($ai_command_image) > 0)
                                    {
                                        $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                                    }
                                    else
                                    {
                                        $ai_command_image = '';
                                    }
                                    $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                    if(!empty($ai_command_image))
                                    {
                                        $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                    }
                                    else
                                    {
                                        $ai_command_image = trim(strip_tags($post_title));
                                    }
                                    $ai_command_image = trim($ai_command_image);
                                    if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                                    {
                                        $txt_content = aiomatic_get_web_page($ai_command_image);
                                        if ($txt_content !== FALSE) 
                                        {
                                            $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                            $txt_content = array_filter($txt_content);
                                            if(count($txt_content) > 0)
                                            {
                                                $txt_content = $txt_content[array_rand($txt_content)];
                                                if(trim($txt_content) != '') 
                                                {
                                                    $ai_command_image = $txt_content;
                                                    $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                                    $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                                }
                                            }
                                        }
                                    }
                                    if(empty($ai_command_image))
                                    {
                                        aiomatic_log_to_file('Empty API image seed expression provided!');
                                    }
                                    else
                                    {
                                        if(strlen($ai_command_image) > 2000)
                                        {
                                            $ai_command_image = substr($ai_command_image, 0, 2000);
                                        }
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                        {
                                            $api_service = 'Stability.AI';
                                            aiomatic_log_to_file('Calling ' . $api_service . ' for image: ' . $ai_command_image);
                                        }
                                        if($image_size == '256x256')
                                        {
                                            $width = '512';
                                            $height = '512';
                                        }
                                        elseif($image_size == '512x512')
                                        {
                                            $width = '512';
                                            $height = '512';
                                        }
                                        elseif($image_size == '1024x1024')
                                        {
                                            $width = '1024';
                                            $height = '1024';
                                        }
                                        else
                                        {
                                            $width = '512';
                                            $height = '512';
                                        }
                                        $temp_get_imgs = aiomatic_generate_stability_image($ai_command_image, $height, $width, 'contentStableImage', 0, false);
                                        if($temp_get_imgs !== false)
                                        {
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                            {
                                                aiomatic_log_to_file('AI generated image returned: ' . $temp_get_imgs[1]);
                                            }
                                            $added_images++;
                                            $added_img_list[] = $temp_get_imgs[1];
                                            $temp_get_img = $temp_get_imgs[1];
                                        }
                                        else
                                        {
                                            aiomatic_log_to_file('Failed to generate Stability.AI image.');
                                            $temp_get_img = '';
                                        }
                                    }
                                }
                                else
                                {
                                    aiomatic_log_to_file('Empty AI image query entered.');
                                }
                            }
                            elseif(count($images_arr) > 0)
                            {
                                $first_el = array_shift($images_arr);
                                $first_el = aiomatic_replaceSynergyShortcodes($first_el);
                                $added_images++;
                                $added_img_list[] = $first_el;
                                $temp_get_img = $first_el;
                            }
                            else
                            {
                                $query_words = '';
                                if(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'textrazor')
                                {
                                    if(isset($aiomatic_Main_Settings['textrazor_key']) && trim($aiomatic_Main_Settings['textrazor_key']) != '')
                                    {
                                        try
                                        {
                                            if(!class_exists('TextRazor'))
                                            {
                                                require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                            }
                                            TextRazorSettings::setApiKey(trim($aiomatic_Main_Settings['textrazor_key']));
                                            $textrazor = new TextRazor();
                                            $textrazor->addExtractor('entities');
                                            $response = $textrazor->analyze($image_query);
                                            if (isset($response['response']['entities'])) 
                                            {
                                                foreach ($response['response']['entities'] as $entity) 
                                                {
                                                    $query_words = '';
                                                    if(isset($entity['entityEnglishId']))
                                                    {
                                                        $query_words = $entity['entityEnglishId'];
                                                    }
                                                    else
                                                    {
                                                        $query_words = $entity['entityId'];
                                                    }
                                                    if($query_words != '')
                                                    {
                                                        $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 10, false);
                                                        if(!empty($z_img))
                                                        {
                                                            $added_images++;
                                                            $added_img_list[] = $z_img;
                                                            $temp_get_img = $z_img;
                                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                                aiomatic_log_to_file('Royalty Free Image Generated with help of TextRazor (kw: "' . $query_words . '"): ' . $z_img);
                                                            }
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        catch(Exception $e)
                                        {
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                aiomatic_log_to_file('Failed to search for keywords using TextRazor (2): ' . $e->getMessage());
                                            }
                                        }
                                    }
                                }
                                elseif(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'openai')
                                {
                                    if(isset($aiomatic_Main_Settings['keyword_prompts']) && trim($aiomatic_Main_Settings['keyword_prompts']) != '')
                                    {
                                        if(isset($aiomatic_Main_Settings['keyword_model']) && $aiomatic_Main_Settings['keyword_model'] != '')
                                        {
                                            $kw_model = $aiomatic_Main_Settings['keyword_model'];
                                        }
                                        else
                                        {
                                            $kw_model = 'text-davinci-003';
                                        }
                                        $title_ai_command = trim($aiomatic_Main_Settings['keyword_prompts']);
                                        $title_ai_command = preg_split('/\r\n|\r|\n/', $title_ai_command);
                                        $title_ai_command = array_filter($title_ai_command);
                                        if(count($title_ai_command) > 0)
                                        {
                                            $title_ai_command = $title_ai_command[array_rand($title_ai_command)];
                                        }
                                        else
                                        {
                                            $title_ai_command = '';
                                        }
                                        $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                        if(!empty($title_ai_command))
                                        {
                                            $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                        }
                                        $title_ai_command = trim($title_ai_command);
                                        if (filter_var($title_ai_command, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($title_ai_command, '.txt'))
                                        {
                                            $txt_content = aiomatic_get_web_page($title_ai_command);
                                            if ($txt_content !== FALSE) 
                                            {
                                                $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                                $txt_content = array_filter($txt_content);
                                                if(count($txt_content) > 0)
                                                {
                                                    $txt_content = $txt_content[array_rand($txt_content)];
                                                    if(trim($txt_content) != '') 
                                                    {
                                                        $title_ai_command = $txt_content;
                                                        $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                                        $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                                    }
                                                }
                                            }
                                        }
                                        if(empty($title_ai_command))
                                        {
                                            aiomatic_log_to_file('Empty API keyword extractor seed expression provided!');
                                        }
                                        else
                                        {
                                            $title_ai_command = 'Extract a comma separated list of relevant keywords from the text: ' . trim(strip_tags($post_title));
                                            if(strlen($title_ai_command) > $max_seed_tokens * 4)
                                            {
                                                $title_ai_command = substr($title_ai_command, 0, (0 - ($max_seed_tokens * 4)));
                                            }
                                            $title_ai_command = trim($title_ai_command);
                                            if(empty($title_ai_command))
                                            {
                                                aiomatic_log_to_file('Empty API title seed expression provided(5)! ' . print_r($title_ai_command, true));
                                            }
                                            else
                                            {
                                                $query_token_count = count(aiomatic_encode($title_ai_command));
                                                $available_tokens = $max_tokens - $query_token_count;
                                                if($available_tokens <= 16)
                                                {
                                                    $string_len = strlen($title_ai_command);
                                                    $string_len = $string_len / 2;
                                                    $string_len = intval(0 - $string_len);
                                                    $title_ai_command = substr($title_ai_command, 0, $string_len);
                                                    $title_ai_command = trim($title_ai_command);
                                                    $query_token_count = count(aiomatic_encode($title_ai_command));
                                                    $available_tokens = $max_tokens - $query_token_count;
                                                }
                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                                {
                                                    if(aiomatic_is_aiomaticapi_key($token))
                                                    {
                                                        $api_service = 'AiomaticAPI';
                                                    }
                                                    else
                                                    {
                                                        $api_service = 'OpenAI';
                                                    }
                                                    aiomatic_log_to_file('Calling ' . $api_service . ' for title text: ' . $title_ai_command);
                                                }
                                                $aierror = '';
                                                $finish_reason = '';
                                                $generated_text = aiomatic_generate_text($token, $kw_model, $title_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'keywordID' . $param, 0, $finish_reason, $aierror);
                                                if($generated_text === false)
                                                {
                                                    aiomatic_log_to_file('Keyword generator error: ' . $aierror);
                                                    $ai_title = '';
                                                }
                                                else
                                                {
                                                    $ai_title = trim(trim(trim(trim($generated_text), '.'), ' “”‘’"\''));
                                                    $ai_titles = explode(',', $ai_title);
                                                    foreach($ai_titles as $query_words)
                                                    {
                                                        $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, trim($query_words), $img_attr, 10, false);
                                                        if(!empty($z_img))
                                                        {
                                                            $added_images++;
                                                            $added_img_list[] = $z_img;
                                                            $temp_get_img = $z_img;
                                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                                aiomatic_log_to_file('Royalty Free Image Generated with help of AI (kw: "' . $query_words . '"): ' . $z_img);
                                                            }
                                                            break;
                                                        }
                                                    }
                                                }
                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                                {
                                                    if(aiomatic_is_aiomaticapi_key($token))
                                                    {
                                                        $api_service = 'AiomaticAPI';
                                                    }
                                                    else
                                                    {
                                                        $api_service = 'OpenAI';
                                                    }
                                                    aiomatic_log_to_file('Successfully got API keyword result from ' . $api_service . ': ' . $ai_title);
                                                }
                                            }
                                        }
                                    }
                                }
                                if(empty($temp_get_img))
                                {
                                    $keyword_class = new Aiomatic_keywords();
                                    $query_words = $keyword_class->keywords($image_query, 2);
                                    $temp_img_attr = '';
                                    $temp_get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $temp_img_attr, 10, false);
                                    if($temp_get_img == '' || $temp_get_img === false)
                                    {
                                        $query_words = $keyword_class->keywords($image_query, 1);
                                        $temp_get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $temp_img_attr, 20, false);
                                        if($temp_get_img == '' || $temp_get_img === false)
                                        {
                                            $temp_get_img = '';
                                        }
                                        else
                                        {
                                            if(!in_array($temp_get_img, $added_img_list))
                                            {
                                                $added_images++;
                                                $added_img_list[] = $temp_get_img;
                                            }
                                            else
                                            {
                                                $temp_get_img = '';
                                            }
                                        }
                                    }
                                    else
                                    {
                                        if(!in_array($temp_get_img, $added_img_list))
                                        {
                                            $added_images++;
                                            $added_img_list[] = $temp_get_img;
                                        }
                                        else
                                        {
                                            $temp_get_img = '';
                                        }
                                    }
                                }
                            }
                            if($temp_get_img != '')
                            {
                                $add_my_image = '<br/><img class="aiomatic_image_class" src="' . $temp_get_img . '" alt="' . $query_words . '"><br/>';
                            }
                        }
                        if($heading_val == '')
                        {
                            if($add_my_image == '')
                            {
                                $add_my_image = ' ';
                            }
                            $new_post_content .= $add_my_image . trim(nl2br($aiwriter));
                        }
                        else
                        {
                            $new_post_content .= $add_my_image . $heading_val . ' ' . trim(nl2br($aiwriter)) . '</span>';
                        }
                        if($enable_ai_images == '0')
                        {
                            sleep(1);
                        }
                        $cnt++;
                    }
                }
                if($strip_title == '1')
                {
                    $new_post_content = str_replace($post_title, '', $new_post_content);
                }
                if (isset($aiomatic_Main_Settings['swear_filter']) && $aiomatic_Main_Settings['swear_filter'] == 'on') 
                {
                    require_once(dirname(__FILE__) . "/res/swear.php");
                    $new_post_content = aiomatic_filterwords($new_post_content);
                }
                $arr = aiomatic_spin_and_translate($new_post_title, $new_post_content, '3', $skip_spin, $skip_translate);
                if($arr[0] != $new_post_title)
                {
                    $new_post_title = $arr[0];
                    if (!isset($aiomatic_Main_Settings['do_not_check_duplicates']) || $aiomatic_Main_Settings['do_not_check_duplicates'] != 'on') 
                    {
                        $zap = get_page_by_title(html_entity_decode($new_post_title), OBJECT, $post_type);
                        if($zap !== null)
                        {
                            aiomatic_log_to_file('Post with specified title already existing (after spin/translate), skipping it: ' . $new_post_title);
                            unset($post_title_lines[$current_index]);
                            continue;
                        }
                    }
                }
                $new_post_content            = $arr[1];
                if (isset($aiomatic_Main_Settings['spin_text']) && $aiomatic_Main_Settings['spin_text'] !== 'disabled') 
                {
                    $already_spinned = '1';
                }
                if ($auto_categories == 'content') {
                    $extra_categories            = aiomatic_extractKeyWords($new_post_content);
                    $extra_categories            = implode(',', $extra_categories);
                }
                elseif ($auto_categories == 'title') {
                    $extra_categories            = aiomatic_extractKeyWords($new_post_title);
                    $extra_categories            = implode(',', $extra_categories);
                }
                elseif ($auto_categories == 'both') {
                    $extra_categories            = aiomatic_extractKeyWords($new_post_content);
                    $extra_categories            = implode(',', $extra_categories);
                    $extra_categories2            = aiomatic_extractKeyWords($new_post_title);
                    $extra_categories2            = implode(',', $extra_categories2);
                    if($extra_categories2 != '')
                    {
                        $extra_categories .= ',' . $extra_categories2;
                    }
                }
                elseif ($auto_categories == 'ai') 
                {
                    $category_ai_command = $orig_ai_command_category;
                    $category_ai_command = preg_split('/\r\n|\r|\n/', $category_ai_command);
                    $category_ai_command = array_filter($category_ai_command);
                    if(count($category_ai_command) > 0)
                    {
                        $category_ai_command = $category_ai_command[array_rand($category_ai_command)];
                    }
                    else
                    {
                        $category_ai_command = '';
                    }
                    $category_ai_command = aiomatic_replaceSynergyShortcodes($category_ai_command);
                    if(!empty($category_ai_command))
                    {
                        $category_ai_command = replaceAIPostShortcodes($category_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                    }
                    else
                    {
                        $category_ai_command = trim(strip_tags('Write a comma separated list of categories, for the post title: %%post_title%%'));
                    }
                    $category_ai_command = trim($category_ai_command);
                    if (filter_var($category_ai_command, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($category_ai_command, '.txt'))
                    {
                        $txt_content = aiomatic_get_web_page($category_ai_command);
                        if ($txt_content !== FALSE) 
                        {
                            $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                            $txt_content = array_filter($txt_content);
                            if(count($txt_content) > 0)
                            {
                                $txt_content = $txt_content[array_rand($txt_content)];
                                if(trim($txt_content) != '') 
                                {
                                    $category_ai_command = $txt_content;
                                    $category_ai_command = aiomatic_replaceSynergyShortcodes($category_ai_command);
                                    $category_ai_command = replaceAIPostShortcodes($category_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                }
                            }
                        }
                    }
                    if(empty($category_ai_command))
                    {
                        aiomatic_log_to_file('Empty API post category seed expression provided!');
                    }
                    else
                    {
                        if(strlen($category_ai_command) > $max_seed_tokens * 4)
                        {
                            $category_ai_command = substr($category_ai_command, 0, (0 - ($max_seed_tokens * 4)));
                        }
                        $category_ai_command = trim($category_ai_command);
                        if(empty($category_ai_command))
                        {
                            aiomatic_log_to_file('Empty API category seed expression provided! ' . print_r($category_ai_command, true));
                            break;
                        }
                        $query_token_count = count(aiomatic_encode($category_ai_command));
                        $available_tokens = $max_tokens - $query_token_count;
                        if($available_tokens <= 16)
                        {
                            $string_len = strlen($category_ai_command);
                            $string_len = $string_len / 2;
                            $string_len = intval(0 - $string_len);
                            $category_ai_command = substr($category_ai_command, 0, $string_len);
                            $category_ai_command = trim($category_ai_command);
                            if(empty($category_ai_command))
                            {
                                aiomatic_log_to_file('Empty API seed expression provided (after processing) ' . print_r($category_ai_command, true));
                                break;
                            }
                            $query_token_count = count(aiomatic_encode($category_ai_command));
                            $available_tokens = $max_tokens - $query_token_count;
                        }
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                        {
                            if(aiomatic_is_aiomaticapi_key($token))
                            {
                                $api_service = 'AiomaticAPI';
                            }
                            else
                            {
                                $api_service = 'OpenAI';
                            }
                            aiomatic_log_to_file('Calling ' . $api_service . ' for category generator: ' . $category_ai_command);
                        }
                        $aierror = '';
                        $finish_reason = '';
                        $generated_text = aiomatic_generate_text($token, $category_model, $category_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'categoryID' . $param, 0, $finish_reason, $aierror);
                        if($generated_text === false)
                        {
                            aiomatic_log_to_file('Category generator error: ' . $aierror);
                            break;
                        }
                        else
                        {
                            $extra_categories = $generated_text;
                        }
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                        {
                            if(aiomatic_is_aiomaticapi_key($token))
                            {
                                $api_service = 'AiomaticAPI';
                            }
                            else
                            {
                                $api_service = 'OpenAI';
                            }
                            aiomatic_log_to_file('Successfully got API category result from ' . $api_service . ': ' . $generated_text);
                        }
                    }
                }
                else
                {
                    $extra_categories = '';
                }
                $my_post['extra_categories'] = $extra_categories;
                
                $item_tags                   = aiomatic_extractKeyWords($new_post_content, 3);
                $item_tags                   = implode(',', $item_tags);
                $title_tags                   = aiomatic_extractKeyWords($new_post_title, 3);
                $title_tags                   = implode(',', $title_tags);
                $item_create_tag_sp = $spintax->Parse($item_create_tag);
                if ($can_create_tag == 'content') {
                    $post_the_tags = ($item_create_tag_sp != '' ? $item_create_tag_sp . ',' : '') . $item_tags;
                    $my_post['extra_tags']       = $item_tags;
                } else if ($can_create_tag == 'title') {
                    $post_the_tags = ($item_create_tag_sp != '' ? $item_create_tag_sp . ',' : '') . $title_tags;
                    $my_post['extra_tags']       = $title_tags;
                } else if ($can_create_tag == 'both') {
                    $post_the_tags = ($item_create_tag_sp != '' ? $item_create_tag_sp . ',' : '') . ($item_tags != '' ? $item_tags . ',' : '') . $title_tags;
                    $my_post['extra_tags']       = ($item_tags != '' ? $item_tags . ',' : '') . $title_tags;
                } else if ($can_create_tag == 'ai') {
                    $ai_tags = '';
                    $tag_ai_command = $orig_ai_command_tag;
                    $tag_ai_command = preg_split('/\r\n|\r|\n/', $tag_ai_command);
                    $tag_ai_command = array_filter($tag_ai_command);
                    if(count($tag_ai_command) > 0)
                    {
                        $tag_ai_command = $tag_ai_command[array_rand($tag_ai_command)];
                    }
                    else
                    {
                        $tag_ai_command = '';
                    }
                    $tag_ai_command = aiomatic_replaceSynergyShortcodes($tag_ai_command);
                    if(!empty($tag_ai_command))
                    {
                        $tag_ai_command = replaceAIPostShortcodes($tag_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                    }
                    else
                    {
                        $tag_ai_command = trim(strip_tags('Write a comma separated list of tags, for the post title: %%post_title%%'));
                    }
                    $tag_ai_command = trim($tag_ai_command);
                    if (filter_var($tag_ai_command, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($tag_ai_command, '.txt'))
                    {
                        $txt_content = aiomatic_get_web_page($tag_ai_command);
                        if ($txt_content !== FALSE) 
                        {
                            $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                            $txt_content = array_filter($txt_content);
                            if(count($txt_content) > 0)
                            {
                                $txt_content = $txt_content[array_rand($txt_content)];
                                if(trim($txt_content) != '') 
                                {
                                    $tag_ai_command = $txt_content;
                                    $tag_ai_command = aiomatic_replaceSynergyShortcodes($tag_ai_command);
                                    $tag_ai_command = replaceAIPostShortcodes($tag_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                }
                            }
                        }
                    }
                    if(empty($tag_ai_command))
                    {
                        aiomatic_log_to_file('Empty API post tag seed expression provided!');
                    }
                    else
                    {
                        if(strlen($tag_ai_command) > $max_seed_tokens * 4)
                        {
                            $tag_ai_command = substr($tag_ai_command, 0, (0 - ($max_seed_tokens * 4)));
                        }
                        $tag_ai_command = trim($tag_ai_command);
                        if(empty($tag_ai_command))
                        {
                            aiomatic_log_to_file('Empty API tag seed expression provided! ' . print_r($tag_ai_command, true));
                            break;
                        }
                        $query_token_count = count(aiomatic_encode($tag_ai_command));
                        $available_tokens = $max_tokens - $query_token_count;
                        if($available_tokens <= 16)
                        {
                            $string_len = strlen($tag_ai_command);
                            $string_len = $string_len / 2;
                            $string_len = intval(0 - $string_len);
                            $tag_ai_command = substr($tag_ai_command, 0, $string_len);
                            $tag_ai_command = trim($tag_ai_command);
                            if(empty($tag_ai_command))
                            {
                                aiomatic_log_to_file('Empty API seed expression provided (after processing) ' . print_r($tag_ai_command, true));
                                break;
                            }
                            $query_token_count = count(aiomatic_encode($tag_ai_command));
                            $available_tokens = $max_tokens - $query_token_count;
                        }
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                        {
                            if(aiomatic_is_aiomaticapi_key($token))
                            {
                                $api_service = 'AiomaticAPI';
                            }
                            else
                            {
                                $api_service = 'OpenAI';
                            }
                            aiomatic_log_to_file('Calling ' . $api_service . ' for tag generator: ' . $tag_ai_command);
                        }
                        $aierror = '';
                        $finish_reason = '';
                        $generated_text = aiomatic_generate_text($token, $tag_model, $tag_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'tagID' . $param, 0, $finish_reason, $aierror);
                        if($generated_text === false)
                        {
                            aiomatic_log_to_file('Tag generator error: ' . $aierror);
                            break;
                        }
                        else
                        {
                            $ai_tags = $generated_text;
                        }
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                        {
                            if(aiomatic_is_aiomaticapi_key($token))
                            {
                                $api_service = 'AiomaticAPI';
                            }
                            else
                            {
                                $api_service = 'OpenAI';
                            }
                            aiomatic_log_to_file('Successfully got API tag result from ' . $api_service . ': ' . $generated_text);
                        }
                    }
                    $post_the_tags = ($item_create_tag_sp != '' ? $item_create_tag_sp . ',' : '') . $ai_tags;
                    $my_post['extra_tags']       = $ai_tags;
                } else {
                    $post_the_tags = $item_create_tag_sp;
                    $my_post['extra_tags']       = '';
                }
                $my_post['tags_input'] = $post_the_tags;
                $new_post_content        = html_entity_decode($new_post_content);
                $new_post_content = str_replace('</ iframe>', '</iframe>', $new_post_content);
                if ($videos == '1') 
                {
                    if (isset($aiomatic_Main_Settings['yt_app_id']) && trim($aiomatic_Main_Settings['yt_app_id']) != '') {
                        $items = array();
                        $vid_id = '';
                        $za_app = explode(',', $aiomatic_Main_Settings['yt_app_id']);
                        $za_app = trim($za_app[array_rand($za_app)]);
                        $feed_uri = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&key=' . $za_app;
                        $feed_uri .= '&maxResults=10';
                        $feed_uri .= '&q='.urlencode(trim(stripslashes(str_replace('&quot;', '"', $new_post_title))));
                        $ch  = curl_init();
                        if ($ch !== FALSE) {
                            if (isset($aiomatic_Main_Settings['proxy_url']) && $aiomatic_Main_Settings['proxy_url'] != '') {
                                curl_setopt($ch, CURLOPT_PROXY, $aiomatic_Main_Settings['proxy_url']);
                                if (isset($aiomatic_Main_Settings['proxy_auth']) && $aiomatic_Main_Settings['proxy_auth'] != '') {
                                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $aiomatic_Main_Settings['proxy_auth']);
                                }
                            }
                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                            curl_setopt($ch, CURLOPT_HTTPGET, 1);
                            curl_setopt($ch, CURLOPT_REFERER, get_site_url());
                            curl_setopt($ch, CURLOPT_URL, $feed_uri);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                            $exec = curl_exec($ch);
                            curl_close($ch);
                            if ($exec !== FALSE) {
                                $json  = json_decode($exec);
                                if(isset($json->items))
                                {
                                    $items = $json->items;
                                    if (count($items) == 0) 
                                    {
                                        $feed_uri = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&key=' . $za_app;
                                        $feed_uri .= '&maxResults=10';
                                        $keyword_class = new Aiomatic_keywords();
                                        $new_post_title = $keyword_class->keywords($new_post_title, 2);
                                        $feed_uri .= '&q='.urlencode(trim(stripslashes(str_replace('&quot;', '"', $new_post_title))));
                                        $ch  = curl_init();
                                        if ($ch !== FALSE) {
                                            if (isset($aiomatic_Main_Settings['proxy_url']) && $aiomatic_Main_Settings['proxy_url'] != '') {
                                                curl_setopt($ch, CURLOPT_PROXY, $aiomatic_Main_Settings['proxy_url']);
                                                if (isset($aiomatic_Main_Settings['proxy_auth']) && $aiomatic_Main_Settings['proxy_auth'] != '') {
                                                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $aiomatic_Main_Settings['proxy_auth']);
                                                }
                                            }
                                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                                            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                                            curl_setopt($ch, CURLOPT_HTTPGET, 1);
                                            curl_setopt($ch, CURLOPT_REFERER, get_site_url());
                                            curl_setopt($ch, CURLOPT_URL, $feed_uri);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                            $exec = curl_exec($ch);
                                            curl_close($ch);
                                            if ($exec === FALSE) {
                                                $json  = json_decode($exec);
                                                if(isset($json->items))
                                                {
                                                    $items = $json->items;
                                                }
                                            }
                                        }
                                    }
                                }
                                else
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                        aiomatic_log_to_file('YouTube API returned error: ' . $exec);
                                    }
                                }
                            }
                        }
                        if(isset($items[0]->id->videoId))
                        {
                            $rand_ind = array_rand($items);
                            $video_id = $items[$rand_ind]->id->videoId;
                            $new_post_content .= '<br/><br/><div class="automaticx-video-container"><iframe allow="autoplay" width="' . $width . '" height="' . $height . '" src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
                        }
                    }
                    else
                    {
                        $new_post_content .= aiomatic_get_youtube_video(trim(stripslashes(str_replace('&quot;', '"', $new_post_title))), '');
                    }
                }
                $post_prepender = replaceAIPostShortcodes($post_prepend, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                $post_appender = replaceAIPostShortcodes($post_append, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                $post_appender = aiomatic_replaceSynergyShortcodes($post_appender);
                $post_prepender = aiomatic_replaceSynergyShortcodes($post_prepender);
                if($ret_content == 1)
                {
                    return array($post_prepender . ' ' . $new_post_content . ' ' . $post_appender, $new_post_title);
                }
                $my_post['post_content'] = $post_prepender . ' ' . $new_post_content . ' ' . $post_appender;
                $my_post['post_title']           = $new_post_title;
                $my_post['aiomatic_source_title']   = $post_title;
                $my_post['aiomatic_timestamp']   = aiomatic_get_date_now();
                $my_post['aiomatic_post_format'] = $post_format;
                if (isset($default_category) && $default_category !== 'aiomatic_no_category_12345678') {
                    $extra_categories_temp = trim(get_cat_name($default_category) . ',' .$extra_categories, ',');
                }
                else
                {
                    $extra_categories_temp = $extra_categories;
                }
                $block_arr = array();
                $custom_arr = array();
                if($custom_fields != '')
                {
                    if(stristr($custom_fields, '=>') != false)
                    {
                        $rule_arr = explode(',', trim($custom_fields));
                        foreach($rule_arr as $rule)
                        {
                            $my_args = explode('=>', trim($rule));
                            if(isset($my_args[1]))
                            {
                                $my_args[1] = do_shortcode($my_args[1]);
                                $my_args[0] = do_shortcode($my_args[0]);
                                $custom_field_content = trim($my_args[1]);
                                $custom_field_content = aiomatic_replaceContentShortcodes($custom_field_content, $img_attr, $ai_command);
                                $custom_field_content = replaceAIPostShortcodes($custom_field_content, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                $custom_field_content = aiomatic_replaceSynergyShortcodes($custom_field_content);
                                $custom_field_content = $spintax->Parse($custom_field_content, $block_arr);
                                if(stristr($my_args[1], 'serialize_') !== false)
                                {
                                    $custom_arr[trim($my_args[0])] = array(str_replace('serialize_', '', $custom_field_content));
                                }
                                else
                                {
                                    if(stristr($my_args[0], '[') !== false && stristr($my_args[0], ']') !== false)
                                    {
                                        preg_match_all('#([^\[\]]*?)\[([^\[\]]*?)\]#', $my_args[0], $cfm);
                                        if(isset($cfm[2][0]))
                                        {
                                            if(isset($custom_arr[trim($cfm[1][0])]) && is_array($custom_arr[trim($cfm[1][0])]))
                                            {
                                                $custom_arr[trim($cfm[1][0])] = array_merge($custom_arr[trim($cfm[1][0])], array(trim($cfm[2][0]) => $custom_field_content));
                                            }
                                            else
                                            {
                                                $custom_arr[trim($cfm[1][0])] = array(trim($cfm[2][0]) => $custom_field_content);
                                            }
                                        }
                                        else
                                        {
                                            $custom_arr[trim($my_args[0])] = $custom_field_content;
                                        }
                                    }
                                    else
                                    {
                                        $custom_arr[trim($my_args[0])] = $custom_field_content;
                                    }
                                }
                            }
                        }
                    }
                }
                $custom_arr = array_merge($custom_arr, array('aiomatic_auto_post_spinned' => $already_spinned, 'aiomatic_post_cats' => $extra_categories_temp, 'aiomatic_post_tags' => $post_the_tags));
                $my_post['meta_input'] = $custom_arr;
                $custom_tax_arr = array();
                if($custom_tax != '')
                {
                    if(stristr($custom_tax, '=>') != false)
                    {
                        $rule_arr = explode(';', trim($custom_tax));
                        foreach($rule_arr as $rule)
                        {
                            $my_args = explode('=>', trim($rule));
                            if(isset($my_args[1]))
                            {
                                $custom_tax_content = trim($my_args[1]);
                                $custom_tax_content = aiomatic_replaceContentShortcodes($custom_tax_content, $img_attr, $ai_command);
                                $custom_tax_content = replaceAIPostShortcodes($custom_tax_content, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, $old_title);
                                $custom_tax_content = aiomatic_replaceSynergyShortcodes($custom_tax_content);
                                $custom_tax_content = $spintax->Parse($custom_tax_content, $block_arr);
                                if(isset($custom_tax_arr[trim($my_args[0])]))
                                {
                                    $custom_tax_arr[trim($my_args[0])] .= ',' . $custom_tax_content;
                                }
                                else
                                {
                                    $custom_tax_arr[trim($my_args[0])] = $custom_tax_content;
                                }
                            }
                        }
                    }
                }
                if(count($custom_tax_arr) > 0)
                {
                    $my_post['taxo_input'] = $custom_tax_arr;
                }
                if ($enable_pingback == '1') {
                    $my_post['ping_status'] = 'open';
                } else {
                    $my_post['ping_status'] = 'closed';
                }
                if($min_time != '' && $max_time != '')
                {
                    $t1 = strtotime($min_time);
                    $t2 = strtotime($max_time);
                    if($t1 != false && $t2 != false)
                    {
                        $int = rand($t1, $t2);
                        $my_post['post_date'] = date('Y-m-d H:i:s', $int);
                    }
                }
                elseif($min_time != '')
                {
                    $t1 = strtotime($min_time);
                    if($t1 != false)
                    {
                        $my_post['post_date'] = date('Y-m-d H:i:s', $t1);
                    }
                }
                elseif($max_time != '')
                {
                    $t1 = strtotime($max_time);
                    if($t1 != false)
                    {
                        $my_post['post_date'] = date('Y-m-d H:i:s', $t1);
                    }
                }
                $post_array[] = $my_post;
                $count++;
            }
            foreach ($post_array as $post) {
                remove_filter('content_save_pre', 'wp_filter_post_kses');
                remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');remove_filter('title_save_pre', 'wp_filter_kses');
                $post_id = wp_insert_post($post, true);
                add_filter('content_save_pre', 'wp_filter_post_kses');
                add_filter('content_filtered_save_pre', 'wp_filter_post_kses');add_filter('title_save_pre', 'wp_filter_kses');
                if (!is_wp_error($post_id)) {
                    $posts_inserted++;
                    $default_categories = array();
                    if($remove_default == '1' && ($auto_categories != 'disabled' || (isset($default_category) && $default_category !== 'aiomatic_no_category_12345678' && $default_category[0] !== 'aiomatic_no_category_12345678')))
                    {
                        $default_categories = wp_get_post_categories($post_id);
                    }
                    if(isset($post['taxo_input']))
                    {
                        foreach($post['taxo_input'] as $taxn => $taxval)
                        {
                            $taxn = trim($taxn);
                            $taxval = trim($taxval);
                            if(is_taxonomy_hierarchical($taxn))
                            {
                                $taxval = array_map('trim', explode(',', $taxval));
                                for($ii = 0; $ii < count($taxval); $ii++)
                                {
                                    if(!is_numeric($taxval[$ii]))
                                    {
                                        $xtermid = get_term_by('name', $taxval[$ii], $taxn);
                                        if($xtermid !== false)
                                        {
                                            $taxval[$ii] = intval($xtermid->term_id);
                                        }
                                        else
                                        {
                                            wp_insert_term( $taxval[$ii], $taxn);
                                            $xtermid = get_term_by('name', $taxval[$ii], $taxn);
                                            if($xtermid !== false)
                                            {
                                                if($wpml_lang != '' && function_exists('pll_set_term_language'))
                                                {
                                                    pll_set_term_language($xtermid->term_id, $wpml_lang); 
                                                }
                                                elseif($wpml_lang != '' && has_filter('wpml_object_id'))
                                                {
                                                    $wpml_element_type = apply_filters( 'wpml_element_type', $taxn );
                                                    $pars['element_id'] = $xtermid->term_id;
                                                    $pars['element_type'] = $wpml_element_type;
                                                    $pars['language_code'] = $wpml_lang;
                                                    $pars['trid'] = FALSE;
                                                    $pars['source_language_code'] = NULL;
                                                    do_action('wpml_set_element_language_details', $pars);
                                                }
                                                $taxval[$ii] = intval($xtermid->term_id);
                                            }
                                        }
                                    }
                                }
                                wp_set_post_terms($post_id, $taxval, $taxn, true);
                            }
                            else
                            {
                                wp_set_post_terms($post_id, trim($taxval), $taxn, true);
                            }
                        }
                    }
                    if (isset($post['aiomatic_post_format']) && $post['aiomatic_post_format'] != '' && $post['aiomatic_post_format'] != 'post-format-standard') {
                        wp_set_post_terms($post_id, $post['aiomatic_post_format'], 'post_format', true);
                    }
                    $featured_path = '';
                    $get_img = $post['aiomatic_post_image'];
                    if ($get_img != '') {
                        if($post['aiomatic_local_image'] == '1')
                        {
                            $local_get_img = $get_img[0];
                            if (!aiomatic_assign_featured_image_path($local_get_img, $post_id)) {
                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                    aiomatic_log_to_file('aiomatic_assign_featured_image_path failed for ' . $local_get_img);
                                }
                            } else {
                                $featured_path = $get_img[1];
                                update_post_meta( $post_id, 'aiomatic_featured_img', $featured_path );
                            }
                        }
                        else
                        {
                            if (!aiomatic_generate_featured_image($get_img, $post_id)) {
                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                    aiomatic_log_to_file('aiomatic_generate_featured_image failed for ' . $get_img);
                                }
                            } else {
                                $featured_path = $get_img;
                                update_post_meta( $post_id, 'aiomatic_featured_img', $featured_path );
                            }
                        }
                    }
                    if($featured_path == '')
                    {
                        if ($image_url != '') {
                            $replacement = str_replace(array('[', ']'), '', $my_post['post_title']);
                            $image_url_temp = str_replace('%%item_title%%', $replacement, $image_url);
                            $image_url_temp = preg_replace_callback('#%%random_image\[([^\]]*?)\](\[\d+\])?%%#', function ($matches) {
                                if(isset($matches[2]))
                                {
                                    $chance = trim($matches[2], '[]');
                                }
                                else
                                {
                                    $chance = '';
                                }
                                $my_img = aiomatic_get_random_image_google($matches[1], 0, 0, $chance);
                                return $my_img;
                            }, $image_url_temp);
                            $img_rulx = $spintax->Parse(trim($image_url_temp));
                            $img_rulx = explode(',', $img_rulx);
                            $img_rulx = trim($img_rulx[array_rand($img_rulx)]);
                            if(is_numeric($img_rulx))
                            {
                                $featured_path = aiomatic_assign_featured_image($img_rulx, $post_id);
                            }
                            else
                            {
                                if($img_rulx != '')
                                {
                                    if (!aiomatic_generate_featured_image($img_rulx, $post_id)) {
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                            aiomatic_log_to_file('aiomatic_generate_featured_image failed to default value: ' . $img_rulx . '!');
                                        }
                                    } else {
                                        $featured_path = $img_rulx;
                                    }
                                }
                            }
                        }
                    }
                    if ($auto_categories != 'disabled') {
                        if ($post['extra_categories'] != '') {
                            $extra_cats = explode(',', $post['extra_categories']);
                            foreach($extra_cats as $extra_cat)
                            {
                                $termid = aiomatic_create_terms('category', '0', trim($extra_cat));
                                wp_set_post_terms($post_id, $termid, 'category', true);
                                if($wpml_lang != '' && function_exists('pll_set_term_language'))
                                {
                                    foreach($termid as $tx)
                                    {
                                        pll_set_term_language($tx, $wpml_lang); 
                                    }
                                }
                                elseif($wpml_lang != '' && has_filter('wpml_object_id'))
                                {
                                    $wpml_element_type = apply_filters( 'wpml_element_type', 'product_cat' );
                                    foreach($termid as $tx)
                                    {
                                        $pars['element_id'] = $tx;
                                        $pars['element_type'] = $wpml_element_type;
                                        $pars['language_code'] = $wpml_lang;
                                        $pars['trid'] = FALSE;
                                        $pars['source_language_code'] = NULL;
                                        do_action('wpml_set_element_language_details', $pars);
                                    }
                                }
                            }
                        }
                    }
                    if (isset($default_category) && $default_category !== 'aiomatic_no_category_12345678') {
                        $cats   = array();
                        $cats[] = $default_category;
                        global $sitepress;
                        if($wpml_lang != '' && has_filter('wpml_current_language') && $sitepress != null)
                        {
                            $current_language = apply_filters( 'wpml_current_language', NULL );
                            $sitepress->switch_lang($wpml_lang);
                        }
                        wp_set_post_categories($post_id, $cats, true);
                        if($wpml_lang != '' && has_filter('wpml_current_language') && $sitepress != null)
                        {
                            $sitepress->switch_lang($current_language);
                        }
                    }
                    $tax_rez = wp_set_object_terms( $post_id, 'aiomatic_' . $param, 'coderevolution_post_source', true);
                    if (is_wp_error($tax_rez)) {
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                            aiomatic_log_to_file('wp_set_object_terms failed for: ' . $post_id . '!');
                        }
                    }
                    if($remove_default == '1' && ($auto_categories != 'disabled' || (isset($default_category) && $default_category !== 'aiomatic_no_category_12345678' && $default_category[0] !== 'aiomatic_no_category_12345678')))
                    {
                        $new_categories = wp_get_post_categories($post_id);
                        if(isset($default_categories) && !($default_categories == $new_categories))
                        {
                            foreach($default_categories as $dc)
                            {
                                $rem_cat = get_category( $dc );
                                wp_remove_object_terms( $post_id, $rem_cat->slug, 'category' );
                            }
                        }
                    }
                    aiomatic_addPostMeta($post_id, $post, $param, $featured_path);
                    if($wpml_lang != '' && (class_exists('SitePress') || function_exists('wpml_object_id')))
                    {
                        $wpml_element_type = apply_filters( 'wpml_element_type', $post_type );
                        $pars['element_id'] = $post_id;
                        $pars['element_type'] = $wpml_element_type;
                        $pars['language_code'] = $wpml_lang;
                        $pars['source_language_code'] = NULL;
                        do_action('wpml_set_element_language_details', $pars);

                        global $wp_filesystem;
                        if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                            include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
                            wp_filesystem($creds);
                        }
                        if($wp_filesystem->exists(WP_PLUGIN_DIR . '/sitepress-multilingual-cms/inc/wpml-api.php'))
                        {
                            include_once( WP_PLUGIN_DIR . '/sitepress-multilingual-cms/inc/wpml-api.php' );
                        }
                        $wpml_lang = trim($wpml_lang);
                        if(function_exists('wpml_update_translatable_content'))
                        {
                            wpml_update_translatable_content('post_' . $post_type, $post_id, $wpml_lang);
                            if($my_post['post_title'] != '')
                            {
                                global $sitepress;
                                global $wpdb;
                                $keyid = md5($my_post['post_title']);
                                $keyName = $keyid . '_wpml';
                                $rezxxxa = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}postmeta WHERE `meta_key` = '$keyName' limit 1", ARRAY_A );
                                if(count($rezxxxa) != 0)
                                {
                                    $metaRow = $rezxxxa[0];
                                    $metaValue = $metaRow['meta_value'];
                                    $metaParts = explode('_', $metaValue);
                                    $sitepress->set_element_language_details($post_id, 'post_'.$my_post['post_type'] , $metaParts[0], $wpml_lang, $metaParts[1] ); 
                                }
                                else
                                {
                                    $ptrid = $sitepress->get_element_trid($post_id);
                                    update_post_meta($post_id, $keyid.'_wpml', $ptrid.'_'.$wpml_lang );
                                }
                            }
                            
                        }
                    }
                    elseif($wpml_lang != '' && function_exists('pll_set_post_language'))
                    {
                        pll_set_post_language($post_id, $wpml_lang);
                    }
                } else {
                    aiomatic_log_to_file('Failed to insert post into wp database! Title:' . $post['post_title'] . '! Error: ' . $post_id->get_error_message() . 'Error code: ' . $post_id->get_error_code() . 'Error data: ' . $post_id->get_error_data());
                    continue;
                }
            }
            unset($post_array);
        }
        catch (Exception $e) {
            aiomatic_log_to_file('Exception thrown ' . esc_html($e->getMessage()) . '!');
            if($auto == 1)
                {
                    aiomatic_clearFromList($param);
                }
            return 'fail';
        }
        
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('Rule ID ' . esc_html($param) . ' succesfully run! ' . esc_html($posts_inserted) . ' posts created!');
        }
        if (isset($aiomatic_Main_Settings['send_email']) && $aiomatic_Main_Settings['send_email'] == 'on' && $aiomatic_Main_Settings['email_address'] !== '') {
            try {
                $to        = $aiomatic_Main_Settings['email_address'];
                $subject   = '[aiomatic] Rule running report - ' . aiomatic_get_date_now();
                $message   = 'Rule ID ' . esc_html($param) . ' succesfully run! ' . esc_html($posts_inserted) . ' posts created!';
                $headers[] = 'From: AIomatic Plugin <aiomatic@noreply.net>';
                $headers[] = 'Reply-To: noreply@aiomatic.com';
                $headers[] = 'X-Mailer: PHP/' . phpversion();
                $headers[] = 'Content-Type: text/html';
                $headers[] = 'Charset: ' . get_option('blog_charset', 'UTF-8');
                wp_mail($to, $subject, $message, $headers);
            }
            catch (Exception $e) {
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                    aiomatic_log_to_file('Failed to send mail: Exception thrown ' . esc_html($e->getMessage()) . '!');
                }
            }
        }
    }
    if ($posts_inserted == 0) {
        if($auto == 1)
                {
                    aiomatic_clearFromList($param);
                }
        return 'nochange';
    } else {
        if($auto == 1)
                {
                    aiomatic_clearFromList($param);
                }
        return 'ok';
    }
}
function aiomatic_file_get_contents_advanced($url, $headers = '', $referrer = 'self', $user_agent = false)
{
    $content = false;
    if (parse_url($url, PHP_URL_SCHEME) != '' && function_exists('curl_init')) 
    {
        $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
        $max_redirects = 10;
        $ch = curl_init();
        if($ch !== false)
        {
            curl_setopt($ch, CURLOPT_URL, $url);
            if (strtolower($referrer) == 'self') {
                curl_setopt($ch, CURLOPT_REFERER, $url);
            } elseif (strlen($referrer)) {
                curl_setopt($ch, CURLOPT_REFERER, $referrer);
            }
            if ($user_agent) {
                curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            } 
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $headers = trim($headers);
            if (strlen($headers)) {
                $headers_array = explode(PHP_EOL, $headers);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_array);
            }
            if (isset($aiomatic_Main_Settings['proxy_url']) && $aiomatic_Main_Settings['proxy_url'] != '' && $aiomatic_Main_Settings['proxy_url'] != 'disable' && $aiomatic_Main_Settings['proxy_url'] != 'disabled') {
                $prx = explode(',', $aiomatic_Main_Settings['proxy_url']);
                $randomness = array_rand($prx);
                curl_setopt( $ch, CURLOPT_PROXY, trim($prx[$randomness]));
                if (isset($aiomatic_Main_Settings['proxy_auth']) && $aiomatic_Main_Settings['proxy_auth'] != '') 
                {
                    $prx_auth = explode(',', $aiomatic_Main_Settings['proxy_auth']);
                    if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
                    {
                        curl_setopt( $ch, CURLOPT_PROXYUSERPWD, trim($prx_auth[$randomness]));
                    }
                }
            }
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            if (ini_get('open_basedir') == '') 
            {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_MAXREDIRS, $max_redirects);
            } 
            else 
            {
                $base_url = $url;
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                $rch = curl_copy_handle($ch);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($rch, CURLOPT_SSL_VERIFYPEER, false);
                do 
                {
                    curl_setopt($rch, CURLOPT_URL, $url);
                    curl_setopt($rch, CURLOPT_REFERER, $url);
                    $header = curl_exec($rch);
                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/', $header, $matches);
                            $url = trim(array_pop($matches));
                            if (strlen($url) && substr($url, 0, 1) == '/') {
                                $url = $base_url . $url;
                            }
                        } else {
                            $code = 0;
                        }
                    }
                } 
                while ($code && --$max_redirects);
                curl_close($rch);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_REFERER, $url);
            }
            curl_setopt($ch, CURLOPT_HEADER, false);
            $content = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code != 200) {
                $content = false;
            }
            curl_close($ch);
        }
    }
    if (!isset($content) || $content === false) {
        stream_context_set_default(array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false,), 'http' => array('method' => 'HEAD', 'timeout' => 10, 'user_agent' => $user_agent)));
        $content = file_get_contents($url);
    }
    return $content;
}
function aiomatic_get_random_image_google($keyword, $min_width = 0, $min_height = 0, $chance = '')
{
    if($chance != '' && is_numeric($chance))
    {
        $chance = intval($chance);
        if(mt_rand(0, 99) >= $chance)
        {
            return '';
        }
    }
    $gimageurl = 'https://www.google.com/search?q=' . urlencode($keyword . ' -site:depositphotos.com -site:123rf.com') . '&tbm=isch&tbs=il:cl&sa=X';
    $res = aiomatic_file_get_contents_advanced($gimageurl, '', 'self', 'Mozilla/5.0 (Windows NT 10.0;WOW64;rv:97.0) Gecko/20100101 Firefox/97.0/3871tuT2p1u-81');
    preg_match_all('/\["([\w%-\.\/:\?&=]+\.jpg|\.jpeg|\.gif|\.png|\.bmp|\.wbmp|\.webm|\.xbm)",\d+,\d+\]/i', $res, $matches);
    $items = $matches[0];
    if (count($items)) {
        shuffle($items);
        foreach ($items as $item) {
            preg_match('#\["(.*?)",(.*?),(.*?)\]#', $item, $matches);
            if (count($matches) == 4 && ($min_width > 0 || $min_width <= $matches[3]) && ($min_height > 0 || $min_height <= $matches[2])) {
                return $matches[1];
            }
        }
    }
    return '';
}
$aiomatic_fatal = false;
function aiomatic_clear_flag_at_shutdown($param)
{
    $error = error_get_last();
    if ($error !== null && $error['type'] === E_ERROR && $GLOBALS['aiomatic_fatal'] === false) {
        $GLOBALS['aiomatic_fatal'] = true;
        $running = array();
        update_option('aiomatic_running_list', $running);
        aiomatic_log_to_file('[FATAL] Exit error: ' . $error['message'] . ', file: ' . $error['file'] . ', line: ' . $error['line'] . ' - rule ID: ' . $param . '!');
        aiomatic_clearFromList($param);
    }
    else
    {
        aiomatic_clearFromList($param);
    }
}
add_filter('the_title', 'aiomatic_add_affiliate_keyword');
add_filter('the_content', 'aiomatic_add_affiliate_keyword');
add_filter('the_excerpt', 'aiomatic_add_affiliate_keyword');
function aiomatic_add_affiliate_keyword($content)
{
    $rules  = get_option('aiomatic_keyword_list');
    if(!is_array($rules))
    {
       $rules = array();
    }
    $output = '';
    if (!empty($rules)) {
        foreach ($rules as $request => $value) {
            if (is_array($value) && isset($value[1]) && $value[1] != '') {
                $repl = $value[1];
            } else {
                $repl = $request;
            }
            if (isset($value[0]) && $value[0] != '') {
                $content = preg_replace('\'(?!((<.*?)|(<a.*?)))(\b' . preg_quote($request, '\'') . '\b)(?!(([^<>]*?)>)|([^>]*?<\/a>))\'i', '<a href="' . esc_url($value[0]) . '" target="_blank">' . esc_html($repl) . '</a>', $content);
            } else {
                $content = preg_replace('\'(?!((<.*?)|(<a.*?)))(\b' . preg_quote($request, '\'') . '\b)(?!(([^<>]*?)>)|([^>]*?<\/a>))\'i', esc_html($repl), $content);
            }
        }
    }
    return $content;
}

function aiomatic_generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, &$img_attr, $res_cnt = 3, $no_copy = false)
{
    if(isset($aiomatic_Main_Settings['enable_detailed_logging']) && $aiomatic_Main_Settings['enable_detailed_logging'] == 'on')
    {
        aiomatic_log_to_file('Searching for a royalty free image for keyword: ' . $query_words);
    }
    $original_url = '';
    $rand_arr = array();
    if(isset($aiomatic_Main_Settings['pixabay_api']) && $aiomatic_Main_Settings['pixabay_api'] != '')
    {
        $rand_arr[] = 'pixabay';
    }
    if(isset($aiomatic_Main_Settings['flickr_api']) && $aiomatic_Main_Settings['flickr_api'] !== '')
    {
        $rand_arr[] = 'flickr';
    }
    if(isset($aiomatic_Main_Settings['pexels_api']) && $aiomatic_Main_Settings['pexels_api'] !== '')
    {
        $rand_arr[] = 'pexels';
    }
    if(isset($aiomatic_Main_Settings['pixabay_scrape']) && $aiomatic_Main_Settings['pixabay_scrape'] == 'on')
    {
        $rand_arr[] = 'pixabayscrape';
    }
    if(isset($aiomatic_Main_Settings['unsplash_api']) && $aiomatic_Main_Settings['unsplash_api'] == 'on')
    {
        $rand_arr[] = 'unsplash';
    }
    if(isset($aiomatic_Main_Settings['google_images']) && $aiomatic_Main_Settings['google_images'] == 'on')
    {
        $rand_arr[] = 'google';
    }
    $rez = false;
    while(($rez === false || $rez === '') && count($rand_arr) > 0)
    {
        $rand = array_rand($rand_arr);
        if($rand_arr[$rand] == 'pixabay')
        {
            unset($rand_arr[$rand]);
            if(isset($aiomatic_Main_Settings['img_ss']) && $aiomatic_Main_Settings['img_ss'] == 'on')
            {
                $img_ss = '1';
            }
            else
            {
                $img_ss = '0';
            }
            if(isset($aiomatic_Main_Settings['img_editor']) && $aiomatic_Main_Settings['img_editor'] == 'on')
            {
                $img_editor = '1';
            }
            else
            {
                $img_editor = '0';
            }
            $rez = aiomatic_get_pixabay_image($aiomatic_Main_Settings['pixabay_api'], $query_words, $aiomatic_Main_Settings['img_language'], $aiomatic_Main_Settings['imgtype'], $aiomatic_Main_Settings['scrapeimg_orientation'], $aiomatic_Main_Settings['img_order'], $aiomatic_Main_Settings['img_cat'], $aiomatic_Main_Settings['img_mwidth'], $aiomatic_Main_Settings['img_width'], $img_ss, $img_editor, $original_url, $res_cnt);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Pixabay', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://pixabay.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'morguefile')
        {
            unset($rand_arr[$rand]);
            $rez = aiomatic_get_morguefile_image($aiomatic_Main_Settings['morguefile_api'], $aiomatic_Main_Settings['morguefile_secret'], $query_words, $original_url);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'MorgueFile', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', 'https://morguefile.com/', $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://morguefile.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'flickr')
        {
            unset($rand_arr[$rand]);
            $rez = aiomatic_get_flickr_image($aiomatic_Main_Settings, $query_words, $original_url, $res_cnt);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Flickr', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://www.flickr.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'pexels')
        {
            unset($rand_arr[$rand]);
            $rez = aiomatic_get_pexels_image($aiomatic_Main_Settings, $query_words, $original_url, $res_cnt);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Pexels', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://www.pexels.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'pixabayscrape')
        {
            unset($rand_arr[$rand]);
            $rez = aiomatic_scrape_pixabay_image($aiomatic_Main_Settings, $query_words, $original_url);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Pixabay', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://pixabay.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'unsplash')
        {
            unset($rand_arr[$rand]);
            $rez = aiomatic_scrape_unsplash_image($query_words, $original_url);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Unsplash', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://unsplash.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'google')
        {
            unset($rand_arr[$rand]);
            $original_url = 'https://google.com/';
            $rez = aiomatic_get_random_image_google($query_words, 0, 0, '');
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Google Images', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://google.com/', $img_attr);
            }
        }
        else
        {
            aiomatic_log_to_file('Unrecognized free file source: ' . $rand_arr[$rand]);
            unset($rand_arr[$rand]);
        }
    }
    $img_attr = str_replace('%%image_source_name%%', '', $img_attr);
    $img_attr = str_replace('%%image_source_url%%', '', $img_attr);
    $img_attr = str_replace('%%image_source_website%%', '', $img_attr);
    if($rez !== false && $rez !== '')
    {
        if($no_copy !== true)
        {
            if(isset($aiomatic_Main_Settings['copy_locally']) && $aiomatic_Main_Settings['copy_locally'] == 'on')
            {
                $localpath = aiomatic_copy_image_locally($rez);
                if($localpath !== false)
                {
                    $rez = $localpath[0];
                }
            }
        }
    }
    return $rez;
}
function aiomatic_scrape_pixabay_image($aiomatic_Main_Settings, $query, &$original_url)
{
    $original_url = 'https://pixabay.com';
    $featured_image = '';
    $feed_uri = 'https://pixabay.com/en/photos/';
    if($query != '')
    {
        $feed_uri .= '?q=' . urlencode($query);
    }

    if($aiomatic_Main_Settings['scrapeimgtype'] != 'all')
    {
        $feed_uri .= '&image_type=' . $aiomatic_Main_Settings['scrapeimgtype'];
    }
    if($aiomatic_Main_Settings['scrapeimg_orientation'] != '')
    {
        $feed_uri .= '&orientation=' . $aiomatic_Main_Settings['scrapeimg_orientation'];
    }
    if($aiomatic_Main_Settings['scrapeimg_order'] != '' && $aiomatic_Main_Settings['scrapeimg_order'] != 'any')
    {
        $feed_uri .= '&order=' . $aiomatic_Main_Settings['scrapeimg_order'];
    }
    if($aiomatic_Main_Settings['scrapeimg_cat'] != '')
    {
        $feed_uri .= '&category=' . $aiomatic_Main_Settings['scrapeimg_cat'];
    }
    if($aiomatic_Main_Settings['scrapeimg_height'] != '')
    {
        $feed_uri .= '&min_height=' . $aiomatic_Main_Settings['scrapeimg_height'];
    }
    if($aiomatic_Main_Settings['scrapeimg_width'] != '')
    {
        $feed_uri .= '&min_width=' . $aiomatic_Main_Settings['scrapeimg_width'];
    }
    $exec = aiomatic_get_web_page($feed_uri);
    if ($exec !== FALSE) 
    {
        preg_match_all('/<a href="([^"]+?)".+?(?:data-lazy|src)="([^"]+?\.jpg|png)"/i', $exec, $matches);
        if (!empty($matches[2])) {
            $p = array_combine($matches[1], $matches[2]);
            if(count($p) > 0)
            {
                shuffle($p);
                foreach ($p as $key => $val) {
                    $featured_image = $val;
                    if(!is_numeric($key))
                    {
                        if(substr($key, 0, 4) !== "http")
                        {
                            $key = 'https://pixabay.com' . $key;
                        }
                        $original_url = $key;
                    }
                    else
                    {
                        $original_url = 'https://pixabay.com';
                    }
                    break;
                }
            }
        }
    }
    else
    {
        aiomatic_log_to_file('Error while getting api url: ' . $feed_uri);
        return false;
    }
    return $featured_image;
}
function aiomatic_scrape_unsplash_image($query, &$original_url)
{
    $original_url = 'https://unsplash.com/';
    $feed_uri = 'https://source.unsplash.com/1600x900/';
    if($query != '')
    {
        $feed_uri .= '?' . urlencode($query);
    }
    error_reporting(0);
    ini_set('default_socket_timeout', 120);
    $exec = get_headers($feed_uri);
    error_reporting(E_ALL);
    if ($exec === FALSE || !is_array($exec))
    {
        aiomatic_log_to_file('Error while getting api url: ' . $feed_uri);
    }
    $nono = false;
    $locx = false;
    foreach($exec as $ex)
    {
        if(strstr($ex, 'Location:') !== false)
        {
            if(strstr($ex, 'source-404') !== false)
            {
                $nono = true;
            }
            $locx = $ex;
            $locx = preg_replace('/^Location: /', '', $locx);
            break;
        }
    }
    if($nono == true)
    {
        aiomatic_log_to_file('NO image found on Unsplash for query: ' . $query);
        return false;
    }
    else
    {
        if($locx == false)
        {
            aiomatic_log_to_file('Failed to parse response: ' . $feed_uri);
            return false;
        }
        $original_url = $locx;
        return $locx;
    }
}
function aiomatic_get_pexels_image($aiomatic_Main_Settings, $query, &$original_url, $max)
{
    $original_url = 'https://pexels.com';
    $featured_image = '';
    $feed_uri = 'https://api.pexels.com/v1/search?query=' . urlencode($query) . '&per_page=' . $max;
     
    {
        $ch               = curl_init();
        if ($ch === FALSE) {
            aiomatic_log_to_file('Failed to init curl for flickr!');
            return false;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: ' . $aiomatic_Main_Settings['pexels_api']));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $feed_uri);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $exec = curl_exec($ch);
        curl_close($ch);
        if (stristr($exec, 'photos') === FALSE) {
            aiomatic_log_to_file('Unrecognized Pexels API response: ' . $exec . ' URI: ' . $feed_uri);
            return false;
        }
        $items = json_decode ( $exec, true );
        if(!isset($items['photos']))
        {
            aiomatic_log_to_file('Failed to find photo node in Pexels response: ' . $exec . ' URI: ' . $feed_uri);
            return false;
        }
        if(count($items['photos']) == 0)
        {
            return $featured_image;
        }
        $x = 0;
        shuffle($items['photos']);
        while($featured_image == '' && isset($items['photos'][$x]))
        {
            $item = $items['photos'][$x];
            if(isset($item['src']['large']))
            {
                $featured_image = $item['src']['large'];
            }
            elseif(isset($item['src']['medium']))
            {
                $featured_image = $item['src']['medium'];
            }
            elseif(isset($item['src']['small']))
            {
                $featured_image = $item['src']['small'];
            }
            elseif(isset($item['src']['portrait']))
            {
                $featured_image = $item['src']['portrait'];
            }
            elseif(isset($item['src']['landscape']))
            {
                $featured_image = $item['src']['landscape'];
            }
            elseif(isset($item['src']['original']))
            {
                $featured_image = $item['src']['original'];
            }
            elseif(isset($item['src']['tiny']))
            {
                $featured_image = $item['src']['tiny'];
            }
            if($featured_image != '')
            {
                $original_url = $item['url'];
            }
            $x++;
        }
    }
    return $featured_image;
}
function aiomatic_get_flickr_image($aiomatic_Main_Settings, $query, &$original_url, $max)
{
    $original_url = 'https://www.flickr.com';
    $featured_image = '';
    $feed_uri = 'https://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=' . $aiomatic_Main_Settings['flickr_api'] . '&media=photos&per_page=' . esc_html($max) . '&format=php_serial&text=' . urlencode($query);
    if(isset($aiomatic_Main_Settings['flickr_license']) && $aiomatic_Main_Settings['flickr_license'] != '-1')
    {
        $feed_uri .= '&license=' . $aiomatic_Main_Settings['flickr_license'];
    }
    if(isset($aiomatic_Main_Settings['flickr_order']) && $aiomatic_Main_Settings['flickr_order'] != '')
    {
        $feed_uri .= '&sort=' . $aiomatic_Main_Settings['flickr_order'];
    }
    $feed_uri .= '&extras=description,license,date_upload,date_taken,owner_name,icon_server,original_format,last_update,geo,tags,machine_tags,o_dims,views,media,path_alias,url_sq,url_t,url_s,url_q,url_m,url_n,url_z,url_c,url_l,url_o';
     
    {
        $ch               = curl_init();
        if ($ch === FALSE) {
            aiomatic_log_to_file('Failed to init curl for flickr!');
            return false;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Referer: https://www.flickr.com/'));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $feed_uri);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $exec = curl_exec($ch);
        curl_close($ch);
        if (stristr($exec, 'photos') === FALSE) {
            aiomatic_log_to_file('Unrecognized Flickr API response: ' . $exec . ' URI: ' . $feed_uri);
            return false;
        }
        $items = unserialize ( $exec );
        if(!isset($items['photos']['photo']))
        {
            aiomatic_log_to_file('Failed to find photo node in response: ' . $exec . ' URI: ' . $feed_uri);
            return false;
        }
        if(count($items['photos']['photo']) == 0)
        {
            return $featured_image;
        }
        $x = 0;
        shuffle($items['photos']['photo']);
        while($featured_image == '' && isset($items['photos']['photo'][$x]))
        {
            $item = $items['photos']['photo'][$x];
            if(isset($item['url_o']))
            {
                $featured_image = $item['url_o'];
            }
            elseif(isset($item['url_l']))
            {
                $featured_image = $item['url_l'];
            }
            elseif(isset($item['url_c']))
            {
                $featured_image = $item['url_c'];
            }
            elseif(isset($item['url_z']))
            {
                $featured_image = $item['url_z'];
            }
            elseif(isset($item['url_n']))
            {
                $featured_image = $item['url_n'];
            }
            elseif(isset($item['url_m']))
            {
                $featured_image = $item['url_m'];
            }
            elseif(isset($item['url_q']))
            {
                $featured_image = $item['url_q'];
            }
            elseif(isset($item['url_s']))
            {
                $featured_image = $item['url_s'];
            }
            elseif(isset($item['url_t']))
            {
                $featured_image = $item['url_t'];
            }
            elseif(isset($item['url_sq']))
            {
                $featured_image = $item['url_sq'];
            }
            if($featured_image != '')
            {
                $original_url = 'https://www.flickr.com/photos/' . $item['owner'] . '/' . $item['id'];
            }
            $x++;
        }
    }
    return $featured_image;
}
function aiomatic_get_morguefile_image($app_id, $app_secret, $query, &$original_url)
{
    $featured_image = '';
    if(!class_exists('aiomatic_morguefile'))
    {
        require_once (dirname(__FILE__) . "/res/morguefile/mf.api.class.php");
    }
    $query = explode(' ', $query);
    $query = $query[0];
    {
        $mf = new aiomatic_morguefile($app_id, $app_secret);
        $rez = $mf->call('/images/search/sort/page/' . $query);
        if ($rez !== FALSE) 
        {
            $chosen_one = $rez->doc[array_rand($rez->doc)];
            if (isset($chosen_one->file_path_large)) 
            {
                return $chosen_one->file_path_large;
            }
            else
            {
                return false;
            }
        }
        else
        {
            aiomatic_log_to_file('Error while getting api response from morguefile.');
            return false;
        }
    }
    return $featured_image;
}
function aiomatic_get_pixabay_image($app_id, $query, $lang, $image_type, $orientation, $order, $image_category, $max_width, $min_width, $safe_search, $editors_choice, &$original_url, $get_max = 3)
{
    $original_url = 'https://pixabay.com';
    $featured_image = '';
    $feed_uri = 'https://pixabay.com/api/?key=' . $app_id;
    if($query != '')
    {
        $feed_uri .= '&q=' . urlencode($query);
    }
    $feed_uri .= '&per_page=' . $get_max;
    if($lang != '' && $lang != 'any')
    {
        $feed_uri .= '&lang=' . $lang;
    }
    if($image_type != '')
    {
        $feed_uri .= '&image_type=' . $image_type;
    }
    if($orientation != '')
    {
        $feed_uri .= '&orientation=' . $orientation;
    }
    if($order != '')
    {
        $feed_uri .= '&order=' . $order;
    }
    if($image_category != '')
    {
        $feed_uri .= '&category=' . $image_category;
    }
    if($max_width != '')
    {
        $feed_uri .= '&max_width=' . $max_width;
    }
    if($min_width != '')
    {
        $feed_uri .= '&min_width=' . $min_width;
    }
    if($safe_search == '1')
    {
        $feed_uri .= '&safesearch=true';
    }
    if($editors_choice == '1')
    {
        $feed_uri .= '&editors_choice=true';
    }
    $feed_uri .= '&callback=' . aiomatic_generateRandomString(6);
    $exec = aiomatic_get_web_page($feed_uri);
    if ($exec !== FALSE) 
    {
        if (stristr($exec, '"hits"') !== FALSE) 
        {
            $exec = preg_replace('#^[a-zA-Z0-9]*#', '', $exec);
            $exec = trim($exec, '()');
            $json  = json_decode($exec);
            $items = $json->hits;
            if (count($items) != 0) 
            {
                shuffle($items);
                foreach($items as $item)
                {
                    $featured_image = $item->webformatURL;
                    $original_url = $item->pageURL;
                    break;
                }
            }
        }
        else
        {
            aiomatic_log_to_file('Unknow response from api: ' . $feed_uri . ' - resp: ' . $exec);
            return false;
        }
    }
    else
    {
        aiomatic_log_to_file('Error while getting api url: ' . $feed_uri);
        return false;
    }
    return $featured_image;
}

function aiomatic_addPostMeta($post_id, $post, $param, $featured_img)
{
    add_post_meta($post_id, 'aiomatic_parent_rule', $param);
    add_post_meta($post_id, 'aiomatic_enable_pingbacks', $post['aiomatic_enable_pingbacks']);
    add_post_meta($post_id, 'aiomatic_comment_status', $post['comment_status']);
    add_post_meta($post_id, 'aiomatic_extra_categories', $post['extra_categories']);
    add_post_meta($post_id, 'aiomatic_extra_tags', $post['extra_tags']);
    add_post_meta($post_id, 'aiomatic_featured_img', $featured_img);
    add_post_meta($post_id, 'aiomatic_timestamp', $post['aiomatic_timestamp']);
    add_post_meta($post_id, 'aiomatic_source_title', $post['aiomatic_source_title']);
}
function aiomatic_endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}
function aiomatic_generate_featured_image($image_url, $post_id)
{
    $upload_dir = wp_upload_dir();
    global $wp_filesystem;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
        wp_filesystem($creds);
    }
    $image_data = $wp_filesystem->get_contents($image_url);
    if ($image_data === FALSE) {
        $image_data = aiomatic_get_web_page($image_url);
        if ($image_data === FALSE || strpos($image_data, '<Message>Access Denied</Message>') !== FALSE) {
            return false;
        }
    }
    $filename = basename($image_url);
    $temp     = explode("?", $filename);
    $filename = $temp[0];
    $filename = str_replace('%', '-', $filename);
    $filename = str_replace('#', '-', $filename);
    $filename = str_replace('&', '-', $filename);
    $filename = str_replace('{', '-', $filename);
    $filename = str_replace('}', '-', $filename);
    $filename = str_replace('\\', '-', $filename);
    $filename = str_replace('<', '-', $filename);
    $filename = str_replace('>', '-', $filename);
    $filename = str_replace('*', '-', $filename);
    $filename = str_replace('/', '-', $filename);
    $filename = str_replace('$', '-', $filename);
    $filename = str_replace('\'', '-', $filename);
    $filename = str_replace('"', '-', $filename);
    $filename = str_replace(':', '-', $filename);
    $filename = str_replace('@', '-', $filename);
    $filename = str_replace('+', '-', $filename);
    $filename = str_replace('|', '-', $filename);
    $filename = str_replace('=', '-', $filename);
    $filename = str_replace('`', '-', $filename);
    $filename = stripslashes(preg_replace_callback('#(%[a-zA-Z0-9_]*)#', function($matches){ return rand(0, 9); }, preg_quote($filename)));
    $file_parts = pathinfo($filename);
    $post_title = get_the_title($post_id);
    if($post_title != '')
    {
        $post_title = remove_accents( $post_title );
        $invalid = array(
            ' '   => '-',
            '%20' => '-',
            '_'   => '-',
        );
        $post_title = str_replace( array_keys( $invalid ), array_values( $invalid ), $post_title );
        $post_title = preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F415}](?:\x{200D}\x{1F9BA})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9BD})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9AF})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F471}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F9CF}\x{1F647}\x{1F926}\x{1F937}\x{1F46E}\x{1F482}\x{1F477}\x{1F473}\x{1F9B8}\x{1F9B9}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F486}\x{1F487}\x{1F6B6}\x{1F9CD}\x{1F9CE}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}\x{1F9D8}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F471}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F9CF}\x{1F647}\x{1F926}\x{1F937}\x{1F46E}\x{1F482}\x{1F477}\x{1F473}\x{1F9B8}\x{1F9B9}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F486}\x{1F487}\x{1F6B6}\x{1F9CD}\x{1F9CE}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}\x{1F9D8}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}-\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6D5}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6FA}\x{1F7E0}-\x{1F7EB}\x{1F90D}-\x{1F93A}\x{1F93C}-\x{1F945}\x{1F947}-\x{1F971}\x{1F973}-\x{1F976}\x{1F97A}-\x{1F9A2}\x{1F9A5}-\x{1F9AA}\x{1F9AE}-\x{1F9CA}\x{1F9CD}-\x{1F9FF}\x{1FA70}-\x{1FA73}\x{1FA78}-\x{1FA7A}\x{1FA80}-\x{1FA82}\x{1FA90}-\x{1FA95}]/u', '', $post_title);
        
        $post_title = preg_replace('/\.(?=.*\.)/', '', $post_title);
        $post_title = preg_replace('/-+/', '-', $post_title);
        $post_title = str_replace('-.', '.', $post_title);
        $post_title = strtolower( $post_title );
        if($post_title == '')
        {
            $post_title = uniqid();
        }
        if(isset($file_parts['extension']))
        {
            switch($file_parts['extension'])
            {
                case "":
                $filename = sanitize_title($post_title) . '.jpg';
                break;
                case NULL:
                $filename = sanitize_title($post_title) . '.jpg';
                break;
                default:
                $filename = sanitize_title($post_title) . '.' . $file_parts['extension'];
                break;
            }
        }
        else
        {
            $filename = sanitize_title($post_title) . '.jpg';
        }
    }
    else
    {
        if(isset($file_parts['extension']))
        {
            switch($file_parts['extension'])
            {
                case "":
                if(!aiomatic_endsWith($filename, '.jpg'))
                    $filename .= '.jpg';
                break;
                case NULL:
                if(!aiomatic_endsWith($filename, '.jpg'))
                    $filename .= '.jpg';
                break;
                default:
                if(!aiomatic_endsWith($filename, '.' . $file_parts['extension']))
                    $filename .= '.' . $file_parts['extension'];
                break;
            }
        }
        else
        {
            if(!aiomatic_endsWith($filename, '.jpg'))
                $filename .= '.jpg';
        }
    }
    $filename = sanitize_file_name($filename);
    if (wp_mkdir_p($upload_dir['path']))
        $file = $upload_dir['path'] . '/' . $post_id . '-' . $filename;
    else
        $file = $upload_dir['basedir'] . '/' . $post_id . '-' . $filename;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
        wp_filesystem($creds);
    }
    $ret = $wp_filesystem->put_contents($file, $image_data);
    if ($ret === FALSE) {
        return false;
    }
    $wp_filetype = wp_check_filetype($filename, null);
    if($wp_filetype['type'] == '')
    {
        $wp_filetype['type'] = 'image/png';
    }
    $attachment  = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if ((isset($aiomatic_Main_Settings['resize_height']) && $aiomatic_Main_Settings['resize_height'] !== '') || (isset($aiomatic_Main_Settings['resize_width']) && $aiomatic_Main_Settings['resize_width'] !== ''))
    {
        try
        {
            if(!class_exists('\Eventviva\ImageResize')){require_once (dirname(__FILE__) . "/res/ImageResize/ImageResize.php");}
            $imageRes = new ImageResize($file);
            $imageRes->quality_jpg = 100;
            if ((isset($aiomatic_Main_Settings['resize_height']) && $aiomatic_Main_Settings['resize_height'] !== '') && (isset($aiomatic_Main_Settings['resize_width']) && $aiomatic_Main_Settings['resize_width'] !== ''))
            {
                $imageRes->resizeToBestFit($aiomatic_Main_Settings['resize_width'], $aiomatic_Main_Settings['resize_height'], true);
            }
            elseif (isset($aiomatic_Main_Settings['resize_width']) && $aiomatic_Main_Settings['resize_width'] !== '')
            {
                $imageRes->resizeToWidth($aiomatic_Main_Settings['resize_width'], true);
            }
            elseif (isset($aiomatic_Main_Settings['resize_height']) && $aiomatic_Main_Settings['resize_height'] !== '')
            {
                $imageRes->resizeToHeight($aiomatic_Main_Settings['resize_height'], true);
            }
            $imageRes->save($file);
        }
        catch(Exception $e)
        {
            aiomatic_log_to_file('Failed to resize featured image: ' . $image_url . ' to sizes ' . $aiomatic_Main_Settings['resize_width'] . ' - ' . $aiomatic_Main_Settings['resize_height'] . '. Exception thrown ' . esc_html($e->getMessage()) . '!');
        }
    }
    $attach_id   = wp_insert_attachment($attachment, $file, $post_id);
    if ($attach_id === 0) {
        return false;
    }
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    $res2 = set_post_thumbnail($post_id, $attach_id);
    if ($res2 === FALSE) {
        return false;
    }
    $post_title = get_the_title($post_id);
    if($post_title != '')
    {
        update_post_meta($attach_id, '_wp_attachment_image_alt', $post_title);
    }
    return true;
}

function aiomatic_assign_featured_image_path($filename, $post_id)
{
    $wp_filetype = wp_check_filetype($filename, null);
    if($wp_filetype['type'] == '')
    {
        $wp_filetype['type'] = 'image/png';
    }
    $post_title = get_the_title($post_id);
    $attachment  = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => $post_title,
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if ((isset($aiomatic_Main_Settings['resize_height']) && $aiomatic_Main_Settings['resize_height'] !== '') || (isset($aiomatic_Main_Settings['resize_width']) && $aiomatic_Main_Settings['resize_width'] !== ''))
    {
        try
        {
            if(!class_exists('\Eventviva\ImageResize')){require_once (dirname(__FILE__) . "/res/ImageResize/ImageResize.php");}
            $imageRes = new ImageResize($filename);
            $imageRes->quality_jpg = 100;
            if ((isset($aiomatic_Main_Settings['resize_height']) && $aiomatic_Main_Settings['resize_height'] !== '') && (isset($aiomatic_Main_Settings['resize_width']) && $aiomatic_Main_Settings['resize_width'] !== ''))
            {
                $imageRes->resizeToBestFit($aiomatic_Main_Settings['resize_width'], $aiomatic_Main_Settings['resize_height'], true);
            }
            elseif (isset($aiomatic_Main_Settings['resize_width']) && $aiomatic_Main_Settings['resize_width'] !== '')
            {
                $imageRes->resizeToWidth($aiomatic_Main_Settings['resize_width'], true);
            }
            elseif (isset($aiomatic_Main_Settings['resize_height']) && $aiomatic_Main_Settings['resize_height'] !== '')
            {
                $imageRes->resizeToHeight($aiomatic_Main_Settings['resize_height'], true);
            }
            $imageRes->save($filename);
        }
        catch(Exception $e)
        {
            aiomatic_log_to_file('Failed to resize featured image: ' . $filename . ' to sizes ' . $aiomatic_Main_Settings['resize_width'] . ' - ' . $aiomatic_Main_Settings['resize_height'] . '. Exception thrown ' . esc_html($e->getMessage()) . '!');
        }
    }
    $attach_id   = wp_insert_attachment($attachment, $filename, $post_id);
    if ($attach_id === 0) {
        return false;
    }
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
    wp_update_attachment_metadata($attach_id, $attach_data);
    $res2 = set_post_thumbnail($post_id, $attach_id);
    if ($res2 === FALSE) {
        return false;
    }
    if($post_title != '')
    {
        update_post_meta($attach_id, '_wp_attachment_image_alt', $post_title);
    }
    return true;
}

function aiomatic_hour_diff($date1, $date2)
{
    $date1 = new DateTime($date1);
    $date2 = new DateTime($date2);
    
    $number1 = (int) $date1->format('U');
    $number2 = (int) $date2->format('U');
    return ($number1 - $number2) / 60;
}

function aiomatic_add_hour($date, $hour)
{
    $date1 = new DateTime($date);
    $date1->modify("$hour hours");
    $date1 = (array)$date1;
    foreach ($date1 as $key => $value) {
        if ($key == 'date') {
            return $value;
        }
    }
    return $date;
}

function aiomatic_wp_custom_css_files($src, $cont)
{
    wp_enqueue_style('aiomatic-thumbnail-css-' . $cont, $src, __FILE__);
}

function aiomatic_get_date_now($param = 'now')
{
    $date = new DateTime($param);
    $date = (array)$date;
    foreach ($date as $key => $value) {
        if ($key == 'date') {
            return $value;
        }
    }
    return '';
}

function aiomatic_create_terms($taxonomy, $parent, $terms_str)
{
    $terms          = explode('/', $terms_str);
    $categories     = array();
    $parent_term_id = $parent;
    foreach ($terms as $term) {
        $res = term_exists($term, $taxonomy, $parent);
        if ($res != NULL && $res != 0 && count($res) > 0 && isset($res['term_id'])) {
            $parent_term_id = $res['term_id'];
            $categories[]   = $parent_term_id;
        } else {
            $new_term = wp_insert_term($term, $taxonomy, array(
                'parent' => $parent
            ));
            if (!is_wp_error( $new_term ) && $new_term != NULL && $new_term != 0 && count($new_term) > 0 && isset($new_term['term_id'])) {
                $parent_term_id = $new_term['term_id'];
                $categories[]   = $parent_term_id;
            }
        }
    }
    
    return $categories;
}
function aiomatic_getExcerpt($the_content)
{
    $preview = aiomatic_strip_html_tags($the_content);
    $preview = wp_trim_words($preview, 55);
    return $preview;
}

function aiomatic_getPlainContent($the_content)
{
    $preview = aiomatic_strip_html_tags($the_content);
    $preview = wp_trim_words($preview, 999999);
    return $preview;
}
function aiomatic_getItemImage($img)
{
    if($img == '')
    {
        return '';
    }
    $preview = '<img src="' . esc_url($img) . '" alt="image" />';
    return $preview;
}
function aiomatic_get_session_id() {
    if ( !isset( $_SESSION ) ) {
        return uniqid();
    }
    if ( isset( $_SESSION['aiomatic_session_id'] ) ) {
        return $_SESSION['aiomatic_session_id'];
    }
    else {
        $session_id = uniqid();
        $_SESSION['aiomatic_session_id'] = $session_id;
        return $session_id;
    }
}
add_action('init', 'aiomatic_create_taxonomy', 0);
add_action( 'enqueue_block_editor_assets', 'aiomatic_enqueue_block_editor_assets' );
function aiomatic_enqueue_block_editor_assets() {
    $all_models = aiomatic_get_all_models();
    $all_edit_models = array_merge($all_models, AIOMATIC_EDIT_MODELS);
	wp_register_style('aiomatic-browser-style', plugins_url('styles/aiomatic-browser.css', __FILE__), false, '1.0.0');
    wp_enqueue_style('aiomatic-browser-style');
	$block_js_display   = 'scripts/display-posts.js';
	wp_enqueue_script(
		'aiomatic-display-block-js', 
        plugins_url( $block_js_display, __FILE__ ), 
        array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		),
        '1.0.0'
	);
    $block_js_list   = 'scripts/list-posts.js';
	wp_enqueue_script(
		'aiomatic-list-block-js', 
        plugins_url( $block_js_list, __FILE__ ), 
        array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		),
        '1.0.0'
	);
    $block_js_article   = 'scripts/aiomatic-article.js';
	wp_enqueue_script(
		'aiomatic-article', 
        plugins_url( $block_js_article, __FILE__ ), 
        array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data' ),
        '1.0.0'
	);
    wp_localize_script('aiomatic-article', 'aiomatic_object', array(
		'models' => $all_models
	));
    $block_js_image   = 'scripts/aiomatic-image.js';
	wp_enqueue_script(
		'aiomatic-image', 
        plugins_url( $block_js_image, __FILE__ ), 
        array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data' ),
        '1.0.0'
	);
    $block_js_image   = 'scripts/aiomatic-stable-image.js';
	wp_enqueue_script(
		'aiomatic-stable-image', 
        plugins_url( $block_js_image, __FILE__ ), 
        array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data' ),
        '1.0.0'
	);
    $block_js_list   = 'scripts/sidebar.js';
	wp_enqueue_script(
		'aiomatic-sidebar-js', 
        plugins_url( $block_js_list, __FILE__ ), 
        array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data' ),
        '1.0.0'
	);
    $block_js_article   = 'scripts/aiomatic-completion.js';
	wp_enqueue_script(
		'aiomatic-completion', 
        plugins_url( $block_js_article, __FILE__ ), 
        array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data' ),
        '1.0.0'
	);
    wp_localize_script('aiomatic-completion', 'aiomatic_object', array(
		'models' => $all_models
	));
    $block_js_article   = 'scripts/aiomatic-editing.js';
	wp_enqueue_script(
		'aiomatic-editing', 
        plugins_url( $block_js_article, __FILE__ ), 
        array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data' ),
        '1.0.0'
	);
    wp_localize_script('aiomatic-editing', 'aiomatic_object', array(
		'models' => $all_edit_models
	));
    $block_js_article   = 'scripts/aiomatic-image-generator.js';
	wp_enqueue_script(
		'aiomatic-image-generator', 
        plugins_url( $block_js_article, __FILE__ ), 
        array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data' ),
        '1.0.0'
	);
    $block_js_article   = 'scripts/aiomatic-stable-image-generator.js';
	wp_enqueue_script(
		'aiomatic-stable-image-generator', 
        plugins_url( $block_js_article, __FILE__ ), 
        array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data' ),
        '1.0.0'
	);
    $block_js_article   = 'scripts/aiomatic-chat.js';
	wp_enqueue_script(
		'aiomatic-chat', 
        plugins_url( $block_js_article, __FILE__ ), 
        array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-data' ),
        '1.0.0'
	);
    wp_localize_script('aiomatic-chat', 'aiomatic_object', array(
		'models' => $all_models
	));
}
require(dirname(__FILE__) . "/res/StatisticsClass.php");

require(dirname(__FILE__) . "/res/QueryClass.php");
$aiomatic_stats = new Aiomatic_Statistics();
function aiomatic_create_taxonomy()
{
    if(AIOMATIC_IS_DEBUG === true)
    {
        $labels = array(
            'name' => 'AI Training File',
            'all_items' => 'All AI Training Files',
            'singular_name' => 'aiomatic_file',
            'add_new' => 'New AI Training File' ,
            'add_new_item' => 'Add New AI Training File',
            'edit_item' => 'Edit AI Training File',
            'new_item' => 'New AI Training File',
            'view_item' => 'View AI Training File',
            'search_items' => 'Search AI Training Files',
            'not_found' => 'No AI Training Files found',
            'not_found_in_trash' => 'No AI Training File found in Trash',
            'parent_item_colon' => 'Parent AI Training Files:',
            'menu_name' => 'AI Training Files',
        );
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'description' => 'AI Training Files',
            'supports' => array( 'title', 'editor', 'custom-fields' ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => false,
            'menu_position' => 66666665666666666,
            'publicly_queryable' => true,
            'exclude_from_search' => true,
            'has_archive' => false,
            'query_var' => true,
            'can_export' => true,
            'rewrite' => true,
            'capability_type' => 'post'
        );
        $admin_caps = array('capabilities' => array(
            'edit_post'          => 'manage_options',
            'read_post'          => 'manage_options',
            'delete_post'        => 'manage_options',
            'edit_posts'         => 'manage_options',
            'edit_others_posts'  => 'manage_options',
            'delete_posts'       => 'manage_options',
            'publish_posts'      => 'manage_options',
            'read_private_posts' => 'manage_options'
        ));
        $args = array_merge($args, $admin_caps);
        register_post_type( 'aiomatic_file', $args);

        $labels = array(
            'name' => 'AI Conversion File',
            'all_items' => 'All AI Conversion Files',
            'singular_name' => 'aiomatic_convert',
            'add_new' => 'New AI Conversion File' ,
            'add_new_item' => 'Add New AI Conversion File',
            'edit_item' => 'Edit AI Conversion File',
            'new_item' => 'New AI Conversion File',
            'view_item' => 'View AI Conversion File',
            'search_items' => 'Search AI Conversion Files',
            'not_found' => 'No AI Conversion Files found',
            'not_found_in_trash' => 'No AI Conversion File found in Trash',
            'parent_item_colon' => 'Parent AI Conversion Files:',
            'menu_name' => 'AI Conversion Files',
        );
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'description' => 'AI Conversion Files',
            'supports' => array( 'title', 'editor', 'custom-fields' ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => false,
            'menu_position' => 66666665666666666,
            'publicly_queryable' => true,
            'exclude_from_search' => true,
            'has_archive' => false,
            'query_var' => true,
            'can_export' => true,
            'rewrite' => true,
            'capability_type' => 'post'
        );
        $admin_caps = array('capabilities' => array(
            'edit_post'          => 'manage_options',
            'read_post'          => 'manage_options',
            'delete_post'        => 'manage_options',
            'edit_posts'         => 'manage_options',
            'edit_others_posts'  => 'manage_options',
            'delete_posts'       => 'manage_options',
            'publish_posts'      => 'manage_options',
            'read_private_posts' => 'manage_options'
        ));
        $args = array_merge($args, $admin_caps);
        register_post_type( 'aiomatic_convert', $args);

        $labels = array(
            'name' => 'AI Finetune',
            'all_items' => 'All AI Finetunes',
            'singular_name' => 'aiomatic_finetune',
            'add_new' => 'New AI Finetune' ,
            'add_new_item' => 'Add New AI Finetune',
            'edit_item' => 'Edit AI Finetune',
            'new_item' => 'New AI Finetune',
            'view_item' => 'View AI Finetune',
            'search_items' => 'Search AI Finetunes',
            'not_found' => 'No AI Finetunes found',
            'not_found_in_trash' => 'No AI Finetune found in Trash',
            'parent_item_colon' => 'Parent AI Finetune:',
            'menu_name' => 'AI Finetune',
        );
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'description' => 'AI Finetune',
            'supports' => array( 'title', 'editor', 'custom-fields' ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'show_in_rest' => false,
            'menu_position' => 66666665666666666,
            'publicly_queryable' => true,
            'exclude_from_search' => true,
            'has_archive' => false,
            'query_var' => true,
            'can_export' => true,
            'rewrite' => true,
            'capability_type' => 'post'
        );
        $admin_caps = array('capabilities' => array(
            'edit_post'          => 'manage_options',
            'read_post'          => 'manage_options',
            'delete_post'        => 'manage_options',
            'edit_posts'         => 'manage_options',
            'edit_others_posts'  => 'manage_options',
            'delete_posts'       => 'manage_options',
            'publish_posts'      => 'manage_options',
            'read_private_posts' => 'manage_options'
        ));
        $args = array_merge($args, $admin_caps);
        register_post_type( 'aiomatic_finetune', $args);
    }
    
    $labels = array(
        'name' => 'AI Embedding',
        'all_items' => 'All AI Embeddings',
        'singular_name' => 'aiomatic_embeddings',
        'add_new' => 'New AI Embedding' ,
        'add_new_item' => 'Add New AI Embeddings',
        'edit_item' => 'Edit AI Embeddings',
        'new_item' => 'New AI Embeddings',
        'view_item' => 'View AI Embeddings',
        'search_items' => 'Search AI Embeddings',
        'not_found' => 'No AI Embeddings found',
        'not_found_in_trash' => 'No AI Embeddings found in Trash',
        'parent_item_colon' => 'Parent AI Embeddings:',
        'menu_name' => 'AI Embeddings',
    );
    $args = array(
        'labels' => $labels,
        'hierarchical' => false,
        'description' => 'AI Embeddings',
        'supports' => array( 'title', 'editor' ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_nav_menus' => false,
        'show_in_admin_bar' => false,
        'show_in_rest' => false,
        'menu_position' => 66666665666666666,
        'publicly_queryable' => true,
        'exclude_from_search' => true,
        'has_archive' => false,
        'query_var' => true,
        'can_export' => false,
        'rewrite' => false,
        'capability_type' => 'post',
        'capabilities' => array(
            'create_posts' => false,
        )
    );
    $admin_caps = array('capabilities' => array(
        'edit_post'          => 'manage_options',
        'read_post'          => 'manage_options',
        'delete_post'        => 'manage_options',
        'edit_posts'         => 'manage_options',
        'edit_others_posts'  => 'manage_options',
        'delete_posts'       => 'manage_options',
        'publish_posts'      => 'manage_options',
        'read_private_posts' => 'manage_options'
    ));
    $args = array_merge($args, $admin_caps);
    register_post_type( 'aiomatic_embeddings', $args);

    if(!session_id())
    {
        session_start();
    }
    if ( function_exists( 'register_block_type' ) ) {
        register_block_type( 'aiomatic-automatic-ai-content-writer/aiomatic-display', array(
            'render_callback' => 'aiomatic_display_posts_shortcode',
        ) );
        register_block_type( 'aiomatic-automatic-ai-content-writer/aiomatic-list', array(
            'render_callback' => 'aiomatic_list_posts',
        ) );
        register_block_type( 'aiomatic-automatic-ai-content-writer/aiomatic-article', array(
            'render_callback' => 'aiomatic_article',
        ) );
        register_block_type( 'aiomatic-automatic-ai-content-writer/aiomatic-image', array(
            'render_callback' => 'aiomatic_image',
        ) );
        register_block_type( 'aiomatic-automatic-ai-content-writer/aiomatic-stable-image', array(
            'render_callback' => 'aiomatic_stable_image',
        ) );
        register_block_type( 'aiomatic-automatic-ai-content-writer/aiomatic-completion', array(
            'render_callback' => 'aiomatic_form_shortcode',
        ) );
        register_block_type( 'aiomatic-automatic-ai-content-writer/aiomatic-editing', array(
            'render_callback' => 'aiomatic_edit_shortcode',
        ) );
        register_block_type( 'aiomatic-automatic-ai-content-writer/aiomatic-image-generator', array(
            'render_callback' => 'aiomatic_image_shortcode',
        ) );
        register_block_type( 'aiomatic-automatic-ai-content-writer/aiomatic-stable-image-generator', array(
            'render_callback' => 'aiomatic_stable_image_shortcode',
        ) );
        register_block_type( 'aiomatic-automatic-ai-content-writer/aiomatic-chat', array(
            'render_callback' => 'aiomatic_chat_shortcode',
        ) );
    }
    if(!taxonomy_exists('coderevolution_post_source'))
    {
        $labels = array(
            'name' => _x('Post Source', 'taxonomy general name', 'aiomatic-automatic-ai-content-writer'),
            'singular_name' => _x('Post Source', 'taxonomy singular name', 'aiomatic-automatic-ai-content-writer'),
            'search_items' => esc_html__('Search Post Source', 'aiomatic-automatic-ai-content-writer'),
            'popular_items' => esc_html__('Popular Post Source', 'aiomatic-automatic-ai-content-writer'),
            'all_items' => esc_html__('All Post Sources', 'aiomatic-automatic-ai-content-writer'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => esc_html__('Edit Post Source', 'aiomatic-automatic-ai-content-writer'),
            'update_item' => esc_html__('Update Post Source', 'aiomatic-automatic-ai-content-writer'),
            'add_new_item' => esc_html__('Add New Post Source', 'aiomatic-automatic-ai-content-writer'),
            'new_item_name' => esc_html__('New Post Source Name', 'aiomatic-automatic-ai-content-writer'),
            'separate_items_with_commas' => esc_html__('Separate Post Source with commas', 'aiomatic-automatic-ai-content-writer'),
            'add_or_remove_items' => esc_html__('Add or remove Post Source', 'aiomatic-automatic-ai-content-writer'),
            'choose_from_most_used' => esc_html__('Choose from the most used Post Source', 'aiomatic-automatic-ai-content-writer'),
            'not_found' => esc_html__('No Post Sources found.', 'aiomatic-automatic-ai-content-writer'),
            'menu_name' => esc_html__('Post Source', 'aiomatic-automatic-ai-content-writer')
        );
        
        $args = array(
            'hierarchical' => false,
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'description' => 'Post Source',
            'labels' => $labels,
            'show_admin_column' => true,
            'update_count_callback' => '_update_post_term_count',
            'rewrite' => false
        );
        
        $add_post_type = array(
            'post',
            'page'
        );
        $xargs = array(
            'public'   => true,
            '_builtin' => false
        );
        $output = 'names'; 
        $operator = 'and';
        $post_types = get_post_types( $xargs, $output, $operator );
        if ( $post_types ) 
        {
            foreach ( $post_types  as $post_type ) {
                $add_post_type[] = $post_type;
            }
        }
        register_taxonomy('coderevolution_post_source', $add_post_type, $args);
        add_action('pre_get_posts', function($qry) {
            if (is_admin()) return;
            if (is_tax('coderevolution_post_source')){
                $qry->set_404();
            }
        });
    }
}

add_action( 'current_screen', function() {
    $custom_post_type = 'aiomatic_embeddings';
    $screen = get_current_screen();
    global $pagenow;
    if ( ! in_array( $pagenow, array( 'post-new.php' ), true )
         && 'post' === $screen->base
         && $custom_post_type === $screen->post_type ) 
    {
        add_action( 'admin_footer', 'aiomatic_hide_batch_update_buttons' );
    }

});

add_filter('post_updated_messages', 'aiomatic_contact_updated_messages');
function aiomatic_contact_updated_messages( $messages ) 
{
    global $post;
    if($post->post_type == 'aiomatic_embeddings')
    {
        $messages['aiomatic_embeddings'] = array(
            0 => '',
            1 => __('Embedding updated.'),
            2 => __('Custom field updated.'),
            3 => __('Custom field deleted.'),
            4 => __('Embedding updated.'),
            5 => isset($_GET['revision']) ? sprintf( __('Embedding restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => __('Embedding published.'),
            7 => __('Embedding saved.'),
            8 => __('Embedding submitted.'),
            9 => __('Embedding scheduled for: <strong>%1$s</strong>.'),
            10 => __('Embedding draft updated.')
        );
    }
    return $messages;
}
function aiomatic_hide_batch_update_buttons() {
	?>
	<script type="text/javascript">
	(function( $ ) {
		'use strict';
		$('#submitdiv .edit-post-status').remove();
		$('#submitdiv .edit-visibility').remove();
		$('#submitdiv .edit-timestamp').remove();
		$('#minor-publishing-actions').remove();
		$('#delete-action').remove();
		$('#aiomatic_meta_box_function_add').remove();
		$('#wp-content-media-buttons').remove();
	})( jQuery );
	</script>
	<?php
}


add_action( 'parse_request', 'aiomatic_redirect_after_trashing_get' );
function aiomatic_disable_create_newpost() {
    global $wp_post_types;
    $wp_post_types['aiomatic_embeddings']->cap->create_posts = 'do_not_allow';
}
add_action('init','aiomatic_disable_create_newpost');
function aiomatic_embeddings_result($aiomatic_message)
{
    require_once (dirname(__FILE__) . "/res/openai/Url.php"); 
    require_once (dirname(__FILE__) . "/res/openai/OpenAi.php"); 
    $result = array('status' => 'error','data' => '');
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['pinecone_app_id']) || trim($aiomatic_Main_Settings['pinecone_app_id']) == '') 
    {
        $result['data'] = 'Pinecone API key needed in plugin settings.';
    }
    if (!isset($aiomatic_Main_Settings['pinecone_index']) || trim($aiomatic_Main_Settings['pinecone_index']) == '') 
    {
        $result['data'] = 'Pinecone Index neededs to be added in plugin settings.';
    }
    if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
    {
        $result['data'] = 'OpenAI API key needed in plugin settings.';
    }
    else 
    {
        $appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
        $appids = array_filter($appids);
        if(count($appids) > 1)
        {
            $result['data'] = 'This feature is currently supported only if you enter a single OpenAI API key in the plugin\'s \'Main Settings\' menu.';
        }
        else
        {
            $token = $appids[array_rand($appids)];
            if(aiomatic_is_aiomaticapi_key($token))
            {
                $result['data'] = 'This feature is currently supported only for OpenAI API keys.';
            }
            else
            {
                $open_ai = new OpenAi($token);
            }
        }
    }
    if (isset($aiomatic_Main_Settings['embeddings_model']) && trim($aiomatic_Main_Settings['embeddings_model']) != '') 
    {
        $embeddings_model = trim($aiomatic_Main_Settings['embeddings_model']);
    }
    else
    {
        $embeddings_model = 'text-embedding-ada-002';
    }
    if (isset($aiomatic_Main_Settings['pinecone_topk']) && trim($aiomatic_Main_Settings['pinecone_topk']) != '') 
    {
        $pinecone_topk = intval(trim($aiomatic_Main_Settings['pinecone_topk']));
        if($pinecone_topk < 1 || $pinecone_topk > 10000)
        {
            $pinecone_topk = 1;
        }
    }
    else
    {
        $pinecone_topk = 1;
    }
    if(empty($result['data'])) 
    {
        $response = $open_ai->embeddings([
            'input' => $aiomatic_message,
            'model' => $embeddings_model
        ]);
        $response = json_decode($response, true);
        if (isset($response['error']) && !empty($response['error'])) {
            $result['data'] = $response['error']['message'];
        } else {
            $embedding = $response['data'][0]['embedding'];
            if (!empty($embedding)) {
                $headers = array(
                    'Content-Type' => 'application/json',
                    'Api-Key' => trim($aiomatic_Main_Settings['pinecone_app_id'])
                );
                $response = wp_remote_post('https://' . trim($aiomatic_Main_Settings['pinecone_index']) . '/query', array(
                    'headers' => $headers,
                    'body' => json_encode(array(
                        'vector' => $embedding,
                        'topK' => $pinecone_topk
                    ))
                ));
                if (is_wp_error($response)) {
                    $result['data'] = esc_html($response->get_error_message());
                } else {
                    $body = json_decode($response['body'], true);
                    if ($body) {
                        if (isset($body['matches']) && is_array($body['matches']) && count($body['matches'])) 
                        {
                            $data = '';
                            $found = false;
                            foreach($body['matches'] as $match){
                                $aiomatic_embedding = get_post($match['id']);
                                if ($aiomatic_embedding) {
                                    $data .= empty($data) ? $aiomatic_embedding->post_content : "\n" . $aiomatic_embedding->post_content;
                                    $found = true;
                                }
                            }
                            if($found == true)
                            {
                                $result['data'] = $data;
                                $result['status'] = 'success';
                            }
                            else
                            {
                                $result['data'] = 'No results found';
                            }
                        }
                    }
                }
            }
        }
    }
    return $result;
}
add_action('upgrader_process_complete', 'aiomatic_updatePlugin', 10, 2);
function aiomatic_updatePlugin(\WP_Upgrader $upgrader, array $hook_extra)
{
    if (is_array($hook_extra) && array_key_exists('action', $hook_extra) && array_key_exists('type', $hook_extra) && array_key_exists('plugins', $hook_extra)) {
        if ($hook_extra['action'] == 'update' && $hook_extra['type'] == 'plugin' && is_array($hook_extra['plugins']) && !empty($hook_extra['plugins'])) {
            $this_plugin = plugin_basename(__FILE__);
            foreach ($hook_extra['plugins'] as $key => $plugin) {
                if ($this_plugin == $plugin) {
                    $this_plugin_updated = true;
                    break;
                }
            }
            unset($key, $plugin, $this_plugin);
            if (isset($this_plugin_updated) && $this_plugin_updated === true) {
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                global $wpdb;
                global $charset_collate;
                aiomatict_register_aggregated_feed_table();
                $sql_create_table = "CREATE TABLE IF NOT EXISTS {$wpdb->aiomatict_shortcode_rez} (
                      post_id bigint(20) unsigned NOT NULL auto_increment,
                      post_hash text default '',
                      post_result text default '',
                      PRIMARY KEY  (post_id)
                 ) $charset_collate; ";
                dbDelta( $sql_create_table );
            }
        }
    }
}

register_activation_hook(__FILE__, 'aiomatic_activation_callback');
function aiomatic_activation_callback($defaults = FALSE)
{
    if (!get_option('aiomatic_posts_per_page') || $defaults === TRUE) {
        if ($defaults === FALSE) {
            add_option('aiomatic_posts_per_page', '12');
        } else {
            update_option('aiomatic_posts_per_page', '12');
        }
    }
    if (!get_option('aiomatic_Main_Settings') || $defaults === TRUE) {
        $aiomatic_Main_Settings = array(
            'aiomatic_enabled' => 'on',
            'translate' => 'disabled',
            'translate_source'  => 'disabled',
            'custom_html2' => '',
            'custom_html' => '',
            'google_trans_auth' => '',
            'serpapi_auth' => '',
            'yt_app_id' => '',
            'copy_locally' => '',
            'ai_resize_width' => '',
            'ai_resize_height' => '',
            'request_delay' => '',
            'player_height' => '',
            'player_width' => '',
            'sentence_list' => 'This is one %adjective %noun %sentence_ending
This is another %adjective %noun %sentence_ending
I %love_it %nouns , because they are %adjective %sentence_ending
My %family says this plugin is %adjective %sentence_ending
These %nouns are %adjective %sentence_ending',
            'sentence_list2' => 'Meet this %adjective %noun %sentence_ending
This is the %adjective %noun ever %sentence_ending
I %love_it %nouns , because they are the %adjective %sentence_ending
My %family says this plugin is very %adjective %sentence_ending
These %nouns are quite %adjective %sentence_ending',
            'variable_list' => 'adjective_very => %adjective;very %adjective;

adjective => clever;interesting;smart;huge;astonishing;unbelievable;nice;adorable;beautiful;elegant;fancy;glamorous;magnificent;helpful;awesome

noun_with_adjective => %noun;%adjective %noun

noun => plugin;WordPress plugin;item;ingredient;component;constituent;module;add-on;plug-in;addon;extension

nouns => plugins;WordPress plugins;items;ingredients;components;constituents;modules;add-ons;plug-ins;addons;extensions

love_it => love;adore;like;be mad for;be wild about;be nuts about;be crazy about

family => %adjective %family_members;%family_members

family_members => grandpa;brother;sister;mom;dad;grandma

sentence_ending => .;!;!!',
            'auto_clear_logs' => 'No',
            'run_after' => '',
            'pinecone_index' => '',
            'pinecone_topk' => '1',
            'embeddings_model' => 'text-embedding-ada-002',
            'run_before' => '',
            'enable_logging' => 'on',
            'app_id' => '',
            'stability_app_id' => '',
            'pinecone_app_id' => '',
            'steps' => '50',
            'cfg_scale' => '7',
            'clip_guidance_preset' => 'NONE',
            'stable_model' => 'stable-diffusion-512-v2-0',
            'sampler' => 'auto',
            'enable_detailed_logging' => '',
            'rule_timeout' => '3600',
            'email_address' => '',
            'send_email' => '',
            'best_password' => '',
            'best_user' => '',
            'improve_keywords' => 'disabled',
            'keyword_model' => 'text-davinci-003',
            'keyword_prompts' => 'Extract a comma separated list of relevant keywords from the text: \'%%post_title%%\'.',
            'spin_lang' => 'English',
            'exclude_words' => '',
            'spin_text' => 'disabled',
            'no_title' => '',
            'no_html_check' => 'on',
            'protect_html' => 'on',
            'swear_filter' => '',
            'apiKey' => '',
            'resize_height' => '',
            'resize_width' => '',
            'morguefile_api' => '',
            'morguefile_secret' => '',
            'pexels_api' => '',
            'flickr_api' => '',
            'flickr_license' => '',
            'flickr_order' => '',
            'pixabay_api' => '',
            'imgtype' => '',
            'img_order' => '',
            'img_cat' => '',
            'img_width' => '',
            'img_mwidth' => '',
            'img_ss' => '',
            'img_editor' => '',
            'img_language' => '',
            'unsplash_api' => '',
            'google_images' => '',
            'pixabay_scrape' => '',
            'scrapeimgtype' => '',
            'scrapeimg_orientation' => '',
            'scrapeimg_order' => '',
            'scrapeimg_cat' => '',
            'scrapeimg_width' => '',
            'scrapeimg_height' => '',
            'attr_text' => '',
            'textrazor_key' => '',
            'bimage' => '',
            'no_royalty_skip' => '',
            'proxy_url' => '',
            'proxy_auth' => '',
            'do_not_check_duplicates' => '',
            'embeddings_related' => '',
            'embeddings_edit_short' => '',
            'embeddings_article_short' => '',
            'embeddings_chat_short' => '',
            'embeddings_edit' => '',
            'embeddings_bulk' => '',
            'embeddings_single' => '',
            'alternate_continue' => '',
            'max_retry' => '3',
            'max_chat_retry' => '',
            'completion_suffix' => ' ###',
            'prompt_suffix' => ' ->',
            'ignored_users' => 'admin',
            'enable_tracking' => ''
        );
        if ($defaults === FALSE) {
            add_option('aiomatic_Main_Settings', $aiomatic_Main_Settings);
        } else {
            update_option('aiomatic_Main_Settings', $aiomatic_Main_Settings);
        }
    }
    if (!get_option('aiomatic_Spinner_Settings') || $defaults === TRUE) {
        $aiomatic_Spinner_Settings = array(
            'aiomatic_spinning' => '',
            'run_background' => '',
            'post_posts' => '',
            'post_pages'  => '',
            'post_custom' => '',
            'disabled_categories' => array(),
            'disable_tags' => '',
            'change_status' => 'no',
            'delay_post' => '',
            'append_spintax' => 'append',
            'ai_rewriter' => '',
            'ai_instruction' => '',
            'ai_instruction_title' => '',
            'edit_temperature' => '',
            'edit_top_p' => '',
            'max_char_chunks' => '',
            'no_title' => '',
            'edit_model' => 'text-davinci-003',
            'no_content' => '',
            'ai_featured_image' => 'disabled',
            'ai_featured_image_source' => '1',
            'ai_image_command' => 'A high detail photograph of %%post_title%%',
            'image_size' => '',
            'min_char' => '',
            'images' => '',
            'videos' => '',
            'headings' => '',
            'enable_ai_images' => '',
            'ai_command' => 'Write a formal article in English about: "%%post_title%%"',
            'max_seed_tokens' => '',
            'max_result_tokens' => '',
            'max_continue_tokens' => '',
            'max_tokens' => '2048',
            'temperature' => '1',
            'top_p' => '1',
            'presence_penalty' => '0',
            'frequency_penalty' => '0',
            'model' => 'text-davinci-003'

        );
        if ($defaults === FALSE) {
            add_option('aiomatic_Spinner_Settings', $aiomatic_Spinner_Settings);
        } else {
            update_option('aiomatic_Spinner_Settings', $aiomatic_Spinner_Settings);
        }
    }
    if (!get_option('aiomatic_Limit_Settings') || $defaults === TRUE) {
        $aiomatic_Limit_Settings = array(
            'user_credits' => '',
            'guest_credits' => '',
            'limit_message_not_logged' => 'You have reached the usage limit.',
            'limit_message_logged' => 'You have reached the usage limit.',
            'ignored_users'  => 'admin',
            'user_credit_type' => 'units',
            'guest_credit_type' => 'queries',
            'user_time_frame' => 'month',
            'guest_time_frame' => 'day',
            'is_absolute_user' => '',
            'is_absolute_guest' => '',
            'enable_limits' => ''
        );
        if ($defaults === FALSE) {
            add_option('aiomatic_Limit_Settings', $aiomatic_Limit_Settings);
        } else {
            update_option('aiomatic_Limit_Settings', $aiomatic_Limit_Settings);
        }
    }
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    global $wpdb;
    global $charset_collate;
    aiomatict_register_aggregated_feed_table();
    $sql_create_table = "CREATE TABLE IF NOT EXISTS {$wpdb->aiomatict_shortcode_rez} (
          post_id bigint(20) unsigned NOT NULL auto_increment,
          post_hash text default '',
          post_result text default '',
          PRIMARY KEY  (post_id)
     ) $charset_collate; ";
    
    dbDelta( $sql_create_table );
}
register_deactivation_hook(__FILE__,'aiomatict_deactivate_plugin');
function aiomatict_deactivate_plugin(){
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS $wpdb->aiomatict_shortcode_rez");
}
function aiomatict_get_item_table_columns(){
    return array(
        'post_id'=> '%d',
        'post_hash' => '%s',
        'post_result'=> '%s'
    );
}
function aiomatict_get_items($query=array()){
     global $wpdb;
     $defaults = array(
       'post_hash'=>''
     );
 
    $query = wp_parse_args($query, $defaults);
    
    extract($query);
    $allowed_fields = aiomatict_get_item_table_columns();
    $select_sql = "SELECT post_result FROM {$wpdb->aiomatict_shortcode_rez}";
    $join_sql='';
    $where_sql = $wpdb->prepare("WHERE post_hash = %s", $post_hash);

    $sql = "$select_sql $where_sql";
    $logs = $wpdb->get_results($sql);
    $logs = apply_filters('aiomatict_get_items', $logs, $query);
    return $logs;
}
add_action( 'init', 'aiomatict_register_aggregated_feed_table', 1 );
add_action( 'switch_blog', 'aiomatict_register_aggregated_feed_table' );
function aiomatict_insert_item($data=array()){
    global $wpdb;        
    $data = wp_parse_args($data, array(
                 'post_hash'=> '',
                 'post_result'=> ''
    ));
    $column_formats = aiomatict_get_item_table_columns();
    $data = array_change_key_case ( $data );
    $data = array_intersect_key($data, $column_formats);
    $data_keys = array_keys($data);
    $column_formats = array_merge(array_flip($data_keys), $column_formats);
    add_filter('query', 'aiomatict_modifyInsertQuery', 10);
    $wpdb->insert($wpdb->aiomatict_shortcode_rez, $data, $column_formats);
    remove_filter('query', 'aiomatict_modifyInsertQuery', 10);
    if($wpdb->insert_id == 0)
    {
        if($wpdb->last_error != '')
        {
            $query = htmlspecialchars( print_r($wpdb->last_query, true), ENT_QUOTES );
            aiomatic_log_to_file('WordPress database error: "' . $wpdb->last_error . '" QUERY: ' . $query);
        }
    }
    return $wpdb->insert_id;
}
function aiomatict_modifyInsertQuery( $query ){
    $count 	= 0;
	$query 	= preg_replace('/^(INSERT INTO)/i', 'INSERT IGNORE INTO', $query, 1 , $count );
	return $query;
}
function aiomatict_register_aggregated_feed_table() {
    global $wpdb;
    $wpdb->aiomatict_shortcode_rez = "{$wpdb->prefix}aiomatict_shortcode_rez";
}

add_action('aiomatic_new_post_cron', 'aiomatic_do_post', 10, 1);
add_action('transition_post_status', 'aiomatic_new_post', 10, 3);
function aiomatic_new_post($new_status, $old_status, $post)
{
    if ('publish' !== $new_status or 'publish' === $old_status)
    {
        return;
    }
    else
    {
        if($old_status == 'auto-draft' && $new_status == 'publish' && !has_post_thumbnail($post->ID) && ((function_exists('has_blocks') && has_blocks($post)) || ($post->post_content == '' && function_exists('has_blocks') && !class_exists('Classic_Editor'))))
        {
            $delay_it_is_gutenberg = true;
        }
        else
        {
            $delay_it_is_gutenberg = false;
        }
    }
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['aiomatic_enabled']) && $aiomatic_Main_Settings['aiomatic_enabled'] == 'on') 
    {
        $aiomatic_Spinner_Settings = get_option('aiomatic_Spinner_Settings', false);
        if (isset($aiomatic_Spinner_Settings['aiomatic_spinning']) && $aiomatic_Spinner_Settings['aiomatic_spinning'] == 'on') {
            if (isset($aiomatic_Spinner_Settings['delay_post']) && $aiomatic_Spinner_Settings['delay_post'] != '' && is_numeric($aiomatic_Spinner_Settings['delay_post'])) {
                if(wp_next_scheduled('aiomatic_new_post_cron', array($post)) === false)
                {
                    if($delay_it_is_gutenberg && $aiomatic_Spinner_Settings['delay_post'] < 2)
                    {
                        $aiomatic_Spinner_Settings['delay_post'] = 2;
                    }
                    wp_schedule_single_event(time() + $aiomatic_Spinner_Settings['delay_post'], 'aiomatic_new_post_cron', array($post));
                }
            }
            else
            {
                if (isset($aiomatic_Spinner_Settings['run_background']) && $aiomatic_Spinner_Settings['run_background'] == 'on') {
                    if($delay_it_is_gutenberg)
                    {
                        if(wp_next_scheduled('aiomatic_new_post_cron', array($post)) === false)
                        {
                            wp_schedule_single_event(time() + 2, 'aiomatic_new_post_cron', array($post));
                        }
                    }
                    else
                    {
                        $unique_id = uniqid();
                        update_option('aiomatic_do_post_uniqid', $unique_id);
                        $xcron_url = site_url( '?aiomatic_do_post_cronjob=1&post_id=' . $post->ID . '&aiomatic_do_post_key=' . $unique_id);
                        wp_remote_post( $xcron_url, array( 'timeout' => 1, 'blocking' => false, 'sslverify' => false ) );
                    }
                }
                else
                {
                    if($delay_it_is_gutenberg)
                    {
                        if(wp_next_scheduled('aiomatic_new_post_cron', array($post)) === false)
                        {
                            wp_schedule_single_event( time() + 2, 'aiomatic_new_post_cron', array($post) );
                        }
                    }
                    else
                    {
                        aiomatic_do_post($post);
                    }
                }
            }
        }
    }
}
add_action('init', 'aiomatic_do_post_callback', 0);
function aiomatic_do_post_callback()
{
    $secretp_key = get_option('aiomatic_do_post_uniqid', false);
    if (isset($_GET['aiomatic_do_post_cronjob']) && $_GET['aiomatic_do_post_cronjob'] == '1' && isset($_GET['post_id']) && is_numeric($_GET['post_id']) && $_GET['aiomatic_do_post_key'] === $secretp_key)
    {
        $post = get_post($_GET['post_id']);
        if($post !== null)
        {
            aiomatic_do_post($post);
            exit();
        }
    }
}

function replaceAIPostShortcodes($content, $post_link, $post_title, $blog_title, $post_excerpt, $post_content, $user_name, $featured_image, $post_cats, $post_tagz, $post_id, $img_attr, $old_title)
{
    $matches = array();
    $i = 0;
    preg_match_all('~%regex\(\s*\"([^"]+?)\s*"\s*[,;]\s*\"([^"]*)\"\s*(?:[,;]\s*\"([^"]*?)\s*\")?(?:[,;]\s*\"([^"]*?)\s*\")?(?:[,;]\s*\"([^"]*?)\s*\")?\)%~si', $content, $matches);
    if (is_array($matches) && count($matches) && is_array($matches[0])) {
        for($i = 0; $i < count($matches[0]); $i++)
        {
            if (isset($matches[0][$i])) $fullmatch = $matches[0][$i];
            if (isset($matches[1][$i])) $search_in = replaceAIPostShortcodes($matches[1][$i], $post_link, $post_title, $blog_title, $post_excerpt, $post_content, $user_name, $featured_image, $post_cats, $post_tagz, $post_id, $img_attr, $old_title);
            if (isset($matches[2][$i])) $matchpattern = $matches[2][$i];
            if (isset($matches[3][$i])) $element = $matches[3][$i];
            if (isset($matches[4][$i])) $delimeter = $matches[4][$i];if (isset($matches[5][$i])) $counter = $matches[5][$i];
            if (isset($matchpattern)) {
               if (preg_match('<^[\/#%+~[\]{}][\s\S]*[\/#%+~[\]{}]$>', $matchpattern, $z)) {
                  $ret = preg_match_all($matchpattern, $search_in, $submatches, PREG_PATTERN_ORDER);
               }
               else {
                  $ret = preg_match_all('~'.$matchpattern.'~si', $search_in, $submatches, PREG_PATTERN_ORDER);
               }
            }
            if (isset($submatches)) {
               if (is_array($submatches)) {
                  $empty_elements = array_keys($submatches[0], "");
                  foreach ($empty_elements as $e) {
                     unset($submatches[0][$e]);
                  }
                  $submatches[0] = array_unique($submatches[0]);
                  if (!is_numeric($element)) {
                     $element = 0;
                  }if (!is_numeric($counter)) {
                     $counter = 0;
                  }
                  if(isset($submatches[(int)($element)]))
                  {
                      $matched = $submatches[(int)($element)];
                  }
                  else
                  {
                      $matched = '';
                  }
                  $matched = array_unique((array)$matched);
                  if (empty($delimeter) || $delimeter == 'null') {
                     if (isset($matched[$counter])) $matched = $matched[$counter];
                  }
                  else {
                     $matched = implode($delimeter, $matched);
                  }
                  if (empty($matched)) {
                     $content = str_replace($fullmatch, '', $content);
                  } else {
                     $content = str_replace($fullmatch, $matched, $content);
                  }
               }
            }
        }
    }
    $spintax = new AIomatic_Spintax();
    $content = $spintax->process($content);
    $pcxxx = explode('<!- template ->', $content);
    $content = $pcxxx[array_rand($pcxxx)];
    $content = str_replace('%%random_sentence%%', aiomatic_random_sentence_generator(), $content);
    $content = str_replace('%%random_sentence2%%', aiomatic_random_sentence_generator(false), $content);
    $content = aiomatic_replaceSynergyShortcodes($content);
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['custom_html'])) {
        $content = str_replace('%%custom_html%%', $aiomatic_Main_Settings['custom_html'], $content);
    }
    if (isset($aiomatic_Main_Settings['custom_html2'])) {
        $content = str_replace('%%custom_html2%%', $aiomatic_Main_Settings['custom_html2'], $content);
    }
    $content = str_replace('%%post_link%%', $post_link, $content);
    $content = str_replace('%%post_title%%', $post_title, $content);
    $content = str_replace('%%post_original_title%%', $old_title, $content);
    $content = str_replace('%%blog_title%%', $blog_title, $content);
    $content = str_replace('%%post_excerpt%%', $post_excerpt, $content);
    $post_content = strip_shortcodes($post_content);
    $content = str_replace('%%post_content%%', $post_content, $content);
    $content = str_replace('%%post_content_plain_text%%', strip_tags($post_content), $content);
    $content = str_replace('%%author_name%%', $user_name, $content);
    $content = str_replace('%%featured_image%%', $featured_image, $content);
    $content = str_replace('%%post_cats%%', $post_cats, $content);
    $content = str_replace('%%post_tags%%', $post_tagz, $content);
    $img_attr = str_replace('%%image_source_name%%', '', $img_attr);
    $img_attr = str_replace('%%image_source_url%%', '', $img_attr);
    $img_attr = str_replace('%%image_source_website%%', '', $img_attr);
    $content = str_replace('%%royalty_free_image_attribution%%', $img_attr, $content);
    if($post_id != '')
    {
        preg_match_all('#%%!([^!]*?)!%%#', $content, $matched_content);
        if(isset($matched_content[1][0]))
        {
            foreach($matched_content[1] as $mc)
            {
                $post_custom_data = get_post_meta($post_id, $mc, true);
                if($post_custom_data != '')
                {
                    $content = str_replace('%%!' . $mc . '!%%', $post_custom_data, $content);
                }
                else
                {
                    $content = str_replace('%%!' . $mc . '!%%', '', $content);
                }
            }
        }
        preg_match_all('#%%!!([^!]*?)!!%%#', $content, $matched_content);
        if(isset($matched_content[1][0]))
        {
            foreach($matched_content[1] as $mc)
            {
                $ctaxs = '';
                $terms = get_the_terms( $post_id, $mc );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) )
                {
                    $ctaxs_arr = array();
                    foreach ( $terms as $term ) {
                        $ctaxs_arr[] = $term->slug;
                    }
                    $ctaxs = implode(',', $ctaxs_arr);
                }
                if($post_custom_data != '')
                {
                    $content = str_replace('%%!!' . $mc . '!!%%', $ctaxs, $content);
                }
                else
                {
                    $content = str_replace('%%!!' . $mc . '!!%%', '', $content);
                }
            }
        }
    }
    $content = preg_replace_callback('#%%random_image_url\[([^\]]*?)\]%%#', function ($matches) {
        $my_img = aiomatic_get_random_image_google($matches[1], 0, 0, '');
        return $my_img;
    }, $content);
    $content = preg_replace_callback('#%%random_image\[([^\]]*?)\](\[\d+\])?%%#', function ($matches) {
        if(isset($matches[2]))
        {
            $chance = trim($matches[2], '[]');
        }
        else
        {
            $chance = '';
        }
        $my_img = aiomatic_get_random_image_google($matches[1], 0, 0, $chance);
        return '<img src="' . $my_img . '">';
    }, $content);
    $content = preg_replace_callback('#%%random_video\[([^\]]*?)\](\[\d+\])?%%#', function ($matches) {
        if(isset($matches[2]))
        {
            $chance = trim($matches[2], '[]');
        }
        else
        {
            $chance = '';
        }
        $my_vid = aiomatic_get_youtube_video($matches[1], $chance);
        return $my_vid;
    }, $content);
    return $content;
}

function aiomatic_preg_grep_keys( $pattern, $input, $flags = 0 )
{
    if(!is_array($input))
    {
        return array();
    }
    $keys = preg_grep( $pattern, array_keys( $input ), $flags );
    $vals = array();
    foreach ( $keys as $key )
    {
        $vals[$key] = $input[$key];
    }
    return $vals;
}

function aiomatic_do_post($post, $manual = false)
{
    $post_link = '';
    $post_title = '';
    $blog_title = '';
    $post_excerpt = '';
    $final_content = '';
    $user_name = '';
    $featured_image = '';
    $post_cats = '';
    $post_tagz = '';
    $postID = '';
    $img_attr = '';
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['rule_timeout']) && $aiomatic_Main_Settings['rule_timeout'] != '') {
        $timeout = intval($aiomatic_Main_Settings['rule_timeout']);
    } else {
        $timeout = 3600;
    }
    ini_set('safe_mode', 'Off');
    ini_set('max_execution_time', $timeout);
    ini_set('ignore_user_abort', 1);
    ini_set('user_agent', aiomatic_get_random_user_agent());
    if(function_exists('ignore_user_abort'))
    {
        ignore_user_abort(true);
    }
    if(function_exists('set_time_limit'))
    {
        set_time_limit($timeout);
    }
    if (isset($aiomatic_Main_Settings['aiomatic_enabled']) && $aiomatic_Main_Settings['aiomatic_enabled'] == 'on') 
    {
        $aiomatic_Spinner_Settings = get_option('aiomatic_Spinner_Settings', false);
        $added_img_list = array();
        $added_images = 0;
        $heading_results = array();
        if ($manual || isset($aiomatic_Spinner_Settings['aiomatic_spinning']) && $aiomatic_Spinner_Settings['aiomatic_spinning'] == 'on') {
            if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
            {
                aiomatic_log_to_file('You need to insert a valid OpenAI/AiomaticAPI API Key for this to work!');
                return;
            }
            $appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
            $appids = array_filter($appids);
            $token = $appids[array_rand($appids)];
            if (!$manual && isset($aiomatic_Spinner_Settings['post_posts'])) {
                if ($aiomatic_Spinner_Settings['post_posts'] == 'on' && 'post' === $post->post_type) {
                    return;
                }
            }
            if (!$manual && isset($aiomatic_Spinner_Settings['post_pages'])) {
                if ($aiomatic_Spinner_Settings['post_pages'] == 'on' && 'page' === $post->post_type) {
                    return;
                }
            }
            if (!$manual && isset($aiomatic_Spinner_Settings['post_custom'])) {
                if ($aiomatic_Spinner_Settings['post_custom'] == 'on' && 'page' !== $post->post_type && 'post' !== $post->post_type) {
                    return;
                }
            }
            $meta = get_post_meta($post->ID, "aiomatic_published", true);
            if (!$manual && $meta == 'pub') {
                return;
            }
            update_post_meta($post->ID, "aiomatic_published", "pub");
            $meta = get_post_meta($post->ID, "aiomatic_auto_post_spinned", true);
            if ($meta === '1') {
                return;
            }
            if (isset($aiomatic_Main_Settings['player_width']) && $aiomatic_Main_Settings['player_width'] !== '') {
                $width = esc_attr($aiomatic_Main_Settings['player_width']);
            }
            else
            {
                $width = 580;
            }
            if (isset($aiomatic_Main_Settings['player_height']) && $aiomatic_Main_Settings['player_height'] !== '') {
                $height = esc_attr($aiomatic_Main_Settings['player_height']);
            }
            else
            {
                $height = 380;
            }
            $post_title = $post->post_title;
            $final_content = $post->post_content;
            if (isset($aiomatic_Spinner_Settings['ai_rewriter']) && $aiomatic_Spinner_Settings['ai_rewriter'] != '' && $aiomatic_Spinner_Settings['ai_rewriter'] != 'disabled')
            {
                if (isset($aiomatic_Spinner_Settings['edit_temperature']) && $aiomatic_Spinner_Settings['edit_temperature'] != '')
                {
                    $edit_temperature = floatval($aiomatic_Spinner_Settings['edit_temperature']);
                }
                else
                {
                    $edit_temperature = 0;
                }
                if (isset($aiomatic_Spinner_Settings['edit_model']) && $aiomatic_Spinner_Settings['edit_model'] != '')
                {
                    $model = trim($aiomatic_Spinner_Settings['edit_model']);
                }
                else
                {
                    $model = 'text-davinci-003';
                }
                if (isset($aiomatic_Spinner_Settings['edit_top_p']) && $aiomatic_Spinner_Settings['edit_top_p'] != '')
                {
                    $edit_top_p = floatval($aiomatic_Spinner_Settings['edit_top_p']);
                }
                else
                {
                    $edit_top_p = 1;
                }
                if ((isset($aiomatic_Spinner_Settings['ai_instruction']) && $aiomatic_Spinner_Settings['ai_instruction'] != '') || (isset($aiomatic_Spinner_Settings['ai_instruction_title']) && $aiomatic_Spinner_Settings['ai_instruction_title'] != ''))
                {
                    $all_models = aiomatic_get_all_models();
                    $completionmodels = $all_models;
                    $ai_instruction = trim($aiomatic_Spinner_Settings['ai_instruction']);
                    $ai_instruction = aiomatic_replaceSynergyShortcodes($ai_instruction);
                    $ai_instruction_title = trim($aiomatic_Spinner_Settings['ai_instruction_title']);
                    $ai_instruction_title = aiomatic_replaceSynergyShortcodes($ai_instruction_title);
                    $post_link = get_permalink($post->ID);
                    $blog_title       = html_entity_decode(get_bloginfo('title'));
                    $author_obj       = get_user_by('id', $post->post_author);
                    $user_name        = $author_obj->user_nicename;
                    $featured_image   = '';
                    wp_suspend_cache_addition(true);
                    $metas = get_post_custom($post->ID);
                    wp_suspend_cache_addition(false);
                    if(is_array($metas))
                    {
                        $rez_meta = aiomatic_preg_grep_keys('#.+?_featured_ima?ge?#i', $metas);
                    }
                    else
                    {
                        $rez_meta = array();
                    }
                    if(count($rez_meta) > 0)
                    {
                        foreach($rez_meta as $rm)
                        {
                            if(isset($rm[0]) && filter_var($rm[0], FILTER_VALIDATE_URL))
                            {
                                $featured_image = $rm[0];
                                break;
                            }
                        }
                    }
                    if($featured_image == '')
                    {
                        $featured_image = aiomatic_generate_thumbmail($post->ID);;
                    }
                    if($featured_image == '' && $final_content != '')
                    {
                        $dom     = new DOMDocument();
                        $internalErrors = libxml_use_internal_errors(true);
                        $dom->loadHTML($final_content);
                        libxml_use_internal_errors($internalErrors);
                        $tags      = $dom->getElementsByTagName('img');
                        foreach ($tags as $tag) {
                            $temp_get_img = $tag->getAttribute('src');
                            if ($temp_get_img != '') {
                                $temp_get_img = strtok($temp_get_img, '?');
                                $featured_image = rtrim($temp_get_img, '/');
                            }
                        }
                    }
                    $post_cats = '';
                    $post_categories = wp_get_post_categories( $post->ID );
                    foreach($post_categories as $c){
                        $cat = get_category( $c );
                        $post_cats .= $cat->name . ',';
                    }
                    $post_cats = trim($post_cats, ',');
                    if($post_cats != '')
                    {
                        $post_categories = explode(',', $post_cats);
                    }
                    else
                    {
                        $post_categories = array();
                    }
                    if(count($post_categories) == 0)
                    {
                        $terms = get_the_terms( $post->ID, 'product_cat' );
                        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                            foreach ( $terms as $term ) {
                                $post_categories[] = $term->slug;
                            }
                            $post_cats = implode(',', $post_categories);
                        }
                        
                    }
                    foreach($post_categories as $pc)
                    {
                        if (!$manual && isset($aiomatic_Spinner_Settings['disabled_categories']) && !empty($aiomatic_Spinner_Settings['disabled_categories'])) {
                            foreach($aiomatic_Spinner_Settings['disabled_categories'] as $disabled_cat)
                            {
                                if($manual != true && trim($pc) == get_cat_name($disabled_cat))
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging']) && $aiomatic_Main_Settings['enable_detailed_logging'] == 'on') 
                                    {
                                        aiomatic_log_to_file('Skipping post, has a disabled category: ' . $post->post_title);
                                    }
                                    return;
                                }
                            }
                        }
                    }
                    $post_tagz = '';
                    $post_tags = wp_get_post_tags( $post->ID );
                    foreach($post_tags as $t){
                        $post_tagz .= $t->name . ',';
                    }
                    $post_tagz = trim($post_tagz, ',');
                    if($post_tagz != '')
                    {
                        $post_tags = explode(',', $post_tagz);
                    }
                    else
                    {
                        $post_tags = array();
                    }
                    if(count($post_tags) == 0)
                    {
                        $terms = get_the_terms( $post->ID, 'product_tag' );
                        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                            foreach ( $terms as $term ) {
                                $post_tags[] = $term->slug;
                            }
                            $post_tagz = implode(',', $post_tags);
                        }
                        
                    }
                    foreach($post_tags as $pt)
                    {
                        if (!$manual && isset($aiomatic_Spinner_Settings['disable_tags']) && $aiomatic_Spinner_Settings['disable_tags'] != '') {
                            
                            $disable_tags = explode(",", $aiomatic_Spinner_Settings['disable_tags']);
                            foreach($disable_tags as $disabled_tag)
                            {
                                if($manual != true && trim($pt) == trim($disabled_tag))
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging']) && $aiomatic_Main_Settings['enable_detailed_logging'] == 'on') 
                                    {
                                        aiomatic_log_to_file('Skipping post, has a disabled tag: ' . $post->post_title);
                                    }
                                    return;
                                }
                            }
                        }
                    }
                    $ai_instruction = replaceAIPostShortcodes($ai_instruction, $post_link, $post_title, $blog_title, $post->post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $post->ID, '', '');
                    $ai_instruction = trim($ai_instruction);
                    $ai_instruction_title = replaceAIPostShortcodes($ai_instruction_title, $post_link, $post_title, $blog_title, $post->post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $post->ID, '', '');
                    $ai_instruction_title = trim($ai_instruction_title);
                    
                    if(isset($aiomatic_Spinner_Settings['protect_html']) && $aiomatic_Spinner_Settings['protect_html'] == 'on')
                    {
                        if(!in_array($model, $completionmodels))
                        {
                            if(!empty($ai_instruction))
                            {
                                $ai_instruction .= ", numbers in brackets are protected terms, keep them unchanged in the returned text.";
                            }
                        }
                        else
                        {
                            $ai_instruction .= ", don't edit HTML tags, only text.";
                        }
                    }
                    $pre_tags_matches = array();
                    $pre_tags_matches_s = array();
                    $conseqMatchs = array();
                    $htmlfounds = array();
                    if(!in_array($model, $completionmodels))
                    {
                        $final_content_pre = aiomatic_replaceExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                    }
                    else
                    {
                        $final_content_pre = $final_content;
                    }
                    $instructions_token_count = count(aiomatic_encode($ai_instruction));
                    $instructions_token_count_title = count(aiomatic_encode($ai_instruction_title));
                    $title_token_count = count(aiomatic_encode($post_title));
                    $available_title_tokens = 3000 - ($instructions_token_count_title + $title_token_count);
                    $title_ai_edited = '';
                    if ((!isset($aiomatic_Spinner_Settings['no_title']) || $aiomatic_Spinner_Settings['no_title'] != 'on') && !empty($ai_instruction_title))
                    {
                        if($available_title_tokens < 0)
                        {
                            aiomatic_log_to_file('Skipping editing title, it is too long:, has a disabled tag: ' . $post->post_title);
                        }
                        else
                        {
                            if(in_array($model, $completionmodels))
                            {
                                $prompt = $ai_instruction_title . ': ' . $post_title;
                                $error = '';
                                $finish_reason = '';
                                $max_tokens = 2048;
                                if(strstr($model, 'davinci') !== false && strstr($model, ':ft-') === false)
                                {
                                    $max_tokens = 4000;
                                }
                                $query_token_count = count(aiomatic_encode($prompt));
                                $available_tokens = $max_tokens - $query_token_count;
                                if($available_tokens <= 16)
                                {
                                    $string_len = strlen($prompt);
                                    $string_len = $string_len / 2;
                                    $string_len = intval(0 - $string_len);
                                    $prompt = substr($prompt, 0, $string_len);
                                    $prompt = trim($prompt);
                                    if(empty($prompt))
                                    {
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                            aiomatic_log_to_file('Empty API seed expression provided (after processing)');
                                        }
                                    }
                                    else
                                    {
                                        $query_token_count = count(aiomatic_encode($prompt));
                                        $available_tokens = $max_tokens - $query_token_count;
                                    }
                                }
                                $response_text = aiomatic_generate_text($token, $model, $prompt, $available_tokens, $edit_temperature, $edit_top_p, 0, 0, false, 'titleCEditor', 0, $finish_reason, $error);
                                if($response_text === false)
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                        aiomatic_log_to_file('Post ID ' . $post->ID . ' failed to edit post title using AI: ' . $error);
                                    }
                                }
                                else
                                {
                                    $title_ai_edited = $response_text;
                                    $post_title = $response_text;
                                }
                            }
                            else
                            {
                                $aierror = '';
                                $edited_content = aiomatic_edit_text($token, $model, $ai_instruction_title, $post_title, $edit_temperature, $edit_top_p, 'titleEditor', 0, $aierror);
                                if($edited_content !== false)
                                {
                                    $title_ai_edited = $edited_content;
                                    $post_title = $edited_content;
                                }
                                else
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                        aiomatic_log_to_file('Post ID ' . $post->ID . ' failed to edit post title using AI: ' . $aierror);
                                    }
                                }
                            }
                        }
                    }
                    $content_token_count = count(aiomatic_encode($final_content_pre));
                    $available_tokens = 3000 - ($instructions_token_count + $content_token_count);
                    if($available_tokens < 0)
                    {
                        if (isset($aiomatic_Spinner_Settings['max_char_chunks']) && $aiomatic_Spinner_Settings['max_char_chunks'] != '') 
                        {
                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                aiomatic_log_to_file('Splitting text into chunks of ' . $aiomatic_Spinner_Settings['max_char_chunks'] . ' characters.');
                            }
                            $chunk_split = str_split($final_content_pre, $aiomatic_Spinner_Settings['max_char_chunks']);
                        }
                        else
                        {
                            $chunk_split = str_split($final_content_pre, 4000);
                        }
                    }
                    else
                    {
                        if (isset($aiomatic_Spinner_Settings['max_char_chunks']) && $aiomatic_Spinner_Settings['max_char_chunks'] != '') 
                        {
                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                aiomatic_log_to_file('Splitting text into chunks of ' . $aiomatic_Spinner_Settings['max_char_chunks'] . ' characters.');
                            }
                            $chunk_split = str_split($final_content_pre, $aiomatic_Spinner_Settings['max_char_chunks']);
                        }
                        else
                        {
                            $chunk_split = array($final_content_pre);
                        }
                    }
                    $one_success = false;
                    $final_content_ai = '';
                    $exclude_count_before = 0;
                    if ((!isset($aiomatic_Spinner_Settings['no_content']) || $aiomatic_Spinner_Settings['no_content'] != 'on') && !empty($ai_instruction))
                    {
                        foreach($chunk_split as $my_little_chunk)
                        {
                            if(!in_array($model, $completionmodels))
                            {
                                $exclude_count_before += aiomatic_countExcludes($my_little_chunk);
                            }
                            if(in_array($model, $completionmodels))
                            {
                                $prompt = $ai_instruction . ': ' . $my_little_chunk;
                                $error = '';
                                $finish_reason = '';
                                $max_tokens = 2048;
                                if(strstr($model, 'davinci') !== false && strstr($model, ':ft-') === false)
                                {
                                    $max_tokens = 4000;
                                }
                                $query_token_count = count(aiomatic_encode($prompt));
                                $available_tokens = $max_tokens - $query_token_count;
                                if($available_tokens <= 16)
                                {
                                    $string_len = strlen($prompt);
                                    $string_len = $string_len / 2;
                                    $string_len = intval(0 - $string_len);
                                    $prompt = substr($prompt, 0, $string_len);
                                    $prompt = trim($prompt);
                                    if(empty($prompt))
                                    {
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                            aiomatic_log_to_file('Empty API seed expression provided (after processing)');
                                        }
                                    }
                                    else
                                    {
                                        $query_token_count = count(aiomatic_encode($prompt));
                                        $available_tokens = $max_tokens - $query_token_count;
                                    }
                                }
                                $response_text = aiomatic_generate_text($token, $model, $prompt, $available_tokens, $edit_temperature, $edit_top_p, 0, 0, false, 'contentCEditor', 0, $finish_reason, $error);
                                if($response_text === false)
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                        aiomatic_log_to_file('Post ID ' . $post->ID . ' failed to edit post chunk using AI: ' . $error . ' !-! ' . $ai_instruction . ' !-! ' . $my_little_chunk);
                                    }
                                    $final_content_ai .= $my_little_chunk;
                                }
                                else
                                {
                                    $final_content_ai .= $response_text;
                                    $one_success = true;
                                }
                            }
                            else
                            {
                                $aierror = '';
                                $edited_content = aiomatic_edit_text($token, $model, $ai_instruction, $my_little_chunk, $edit_temperature, $edit_top_p, 'contentEditor', 0, $aierror);
                                if($edited_content !== false)
                                {
                                    $final_content_ai .= $edited_content;
                                    $one_success = true;
                                }
                                else
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                        aiomatic_log_to_file('Post ID ' . $post->ID . ' failed to edit post chunk using AI: ' . $aierror . ' !-! ' . $ai_instruction . ' !-! ' . $my_little_chunk);
                                    }
                                    $final_content_ai .= $my_little_chunk;
                                }
                            }
                        }
                    }
                    if($one_success === false)
                    {
                        $final_content_ai = '';
                    }
                    if($final_content_ai != '')
                    {
                        if(!in_array($model, $completionmodels))
                        {
                            $exclude_count_after = aiomatic_countExcludes($final_content_ai);
                        }
                        else
                        {
                            $exclude_count_after = 0;
                        }
                        if((!isset($aiomatic_Spinner_Settings['no_html_check']) || $aiomatic_Spinner_Settings['no_html_check'] != 'on') && $exclude_count_before != $exclude_count_after)
                        {
                            aiomatic_log_to_file('Post edit failed, as HTML tags were removed by the AI editor. Because of this, edits are not saved. Count of HTML tags missing: ' . ($exclude_count_before - $exclude_count_after));
                        }
                        else
                        {
                            if(!in_array($model, $completionmodels))
                            {
                                $final_content_ai = aiomatic_restoreExcludes($final_content_ai, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                            }
                            $final_content = $final_content_ai;
                            $args = array();
                            $args['ID'] = $post->ID;
                            $args['post_content'] = $final_content;
                            if($title_ai_edited != '')
                            {
                                $args['post_title'] = $title_ai_edited;
                            }
                            if (isset($aiomatic_Spinner_Settings['change_status']) && $aiomatic_Spinner_Settings['change_status'] != '' && $aiomatic_Spinner_Settings['change_status'] != 'no') 
                            {
                                $args['post_status'] = $aiomatic_Spinner_Settings['change_status'];
                            }
                            remove_filter('content_save_pre', 'wp_filter_post_kses');
                            remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                            remove_filter('title_save_pre', 'wp_filter_kses');
                            $post_updated = wp_update_post($args);
                            add_filter('content_save_pre', 'wp_filter_post_kses');
                            add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                            add_filter('title_save_pre', 'wp_filter_kses');
                            if (is_wp_error($post_updated)) {
                                $errors = $post_updated->get_error_messages();
                                foreach ($errors as $error) {
                                    aiomatic_log_to_file('Error occured while updating post "' . $post->post_title . '": ' . $error);
                                }
                            }
                            else
                            {
                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                    aiomatic_log_to_file('Post ID ' . $post->ID . ' was successfully updated with AI generated content.');
                                }
                            }
                        }
                    }
                    else
                    {
                        if($title_ai_edited != '')
                        {
                            $args = array();
                            $args['ID'] = $post->ID;
                            $args['post_title'] = $title_ai_edited;
                            if (isset($aiomatic_Spinner_Settings['change_status']) && $aiomatic_Spinner_Settings['change_status'] != '' && $aiomatic_Spinner_Settings['change_status'] != 'no') 
                            {
                                $args['post_status'] = $aiomatic_Spinner_Settings['change_status'];
                            }
                            remove_filter('content_save_pre', 'wp_filter_post_kses');
                            remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                            remove_filter('title_save_pre', 'wp_filter_kses');
                            $post_updated = wp_update_post($args);
                            add_filter('content_save_pre', 'wp_filter_post_kses');
                            add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                            add_filter('title_save_pre', 'wp_filter_kses');
                            if (is_wp_error($post_updated)) {
                                $errors = $post_updated->get_error_messages();
                                foreach ($errors as $error) {
                                    aiomatic_log_to_file('Error occured while updating post for title "' . $post->post_title . '": ' . $error);
                                }
                            }
                            else
                            {
                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                    aiomatic_log_to_file('Post ID ' . $post->ID . ' was successfully updated with AI generated title.');
                                }
                            }
                        }
                        else
                        {
                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                aiomatic_log_to_file('Post ID ' . $post->ID . ' failed to be editted, nothing returned from AI editor');
                            }
                        }
                    }
                }
            }
            $updated = false;
            if (isset($aiomatic_Spinner_Settings['temperature']) && $aiomatic_Spinner_Settings['temperature'] != '')
            {
                $temperature = floatval($aiomatic_Spinner_Settings['temperature']);
            }
            else
            {
                $temperature = 1;
            }
            if (isset($aiomatic_Spinner_Settings['top_p']) && $aiomatic_Spinner_Settings['top_p'] != '')
            {
                $top_p = floatval($aiomatic_Spinner_Settings['top_p']);
            }
            else
            {
                $top_p = 1;
            }
            if (isset($aiomatic_Spinner_Settings['presence_penalty']) && $aiomatic_Spinner_Settings['presence_penalty'] != '')
            {
                $presence_penalty = floatval($aiomatic_Spinner_Settings['presence_penalty']);
            }
            else
            {
                $presence_penalty = 0;
            }
            if (isset($aiomatic_Spinner_Settings['frequency_penalty']) && $aiomatic_Spinner_Settings['frequency_penalty'] != '')
            {
                $frequency_penalty = floatval($aiomatic_Spinner_Settings['frequency_penalty']);
            }
            else
            {
                $frequency_penalty = 0;
            }
            if (isset($aiomatic_Spinner_Settings['max_seed_tokens']) && $aiomatic_Spinner_Settings['max_seed_tokens'] != '')
            {
                $max_seed_tokens = intval($aiomatic_Spinner_Settings['max_seed_tokens']);
            }
            else
            {
                $max_seed_tokens = 500;
            }
            if (isset($aiomatic_Spinner_Settings['model']) && $aiomatic_Spinner_Settings['model'] != '')
            {
                $model = $aiomatic_Spinner_Settings['model'];
            }
            else
            {
                $model = 'text-davinci-003';
            }
            if (isset($aiomatic_Spinner_Settings['max_tokens']) && $aiomatic_Spinner_Settings['max_tokens'] != '')
            {
                $max_tokens = intval($aiomatic_Spinner_Settings['max_tokens']);
            }
            else
            {
                $max_tokens = 2048;
            }
            
            if($max_tokens <= 0)
            {
                $max_tokens = 2048;
            }
            if($max_tokens > 2048 && (!stristr($model, 'davinci') || strstr($model, ':ft-') === true))
            {
                $max_tokens = 2048;
            }
            if (isset($aiomatic_Spinner_Settings['append_spintax']) && $aiomatic_Spinner_Settings['append_spintax'] != '' && $aiomatic_Spinner_Settings['append_spintax'] != 'disabled')
            {
                if (isset($aiomatic_Spinner_Settings['headings']) && $aiomatic_Spinner_Settings['headings'] != '')
                {
                    $headings = intval($aiomatic_Spinner_Settings['headings']);
                }
                else
                {
                    $headings = '';
                }
                if (isset($aiomatic_Spinner_Settings['images']) && $aiomatic_Spinner_Settings['images'] != '')
                {
                    $images = intval($aiomatic_Spinner_Settings['images']);
                }
                else
                {
                    $images = '';
                }
                if (isset($aiomatic_Spinner_Settings['videos']) && $aiomatic_Spinner_Settings['videos'] != '')
                {
                    $videos = $aiomatic_Spinner_Settings['videos'];
                }
                else
                {
                    $videos = '';
                }
                if (isset($aiomatic_Spinner_Settings['max_result_tokens']) && $aiomatic_Spinner_Settings['max_result_tokens'] != '')
                {
                    $max_result_tokens = intval($aiomatic_Spinner_Settings['max_result_tokens']);
                }
                else
                {
                    $max_result_tokens = 2048;
                }

                if (isset($aiomatic_Spinner_Settings['ai_command']) && $aiomatic_Spinner_Settings['ai_command'] != '')
                {
                    $aicontent = trim(strip_tags($aiomatic_Spinner_Settings['ai_command']));
                    $aicontent = aiomatic_replaceSynergyShortcodes($aicontent);
                    $post_link = get_permalink($post->ID);
                    $blog_title       = html_entity_decode(get_bloginfo('title'));
                    $author_obj       = get_user_by('id', $post->post_author);
                    $user_name        = $author_obj->user_nicename;
                    $featured_image   = '';
                    wp_suspend_cache_addition(true);
                    $metas = get_post_custom($post->ID);
                    wp_suspend_cache_addition(false);
                    if(is_array($metas))
                    {
                        $rez_meta = aiomatic_preg_grep_keys('#.+?_featured_ima?ge?#i', $metas);
                    }
                    else
                    {
                        $rez_meta = array();
                    }
                    if(count($rez_meta) > 0)
                    {
                        foreach($rez_meta as $rm)
                        {
                            if(isset($rm[0]) && filter_var($rm[0], FILTER_VALIDATE_URL))
                            {
                                $featured_image = $rm[0];
                                break;
                            }
                        }
                    }
                    if($featured_image == '')
                    {
                        $featured_image = aiomatic_generate_thumbmail($post->ID);;
                    }
                    if($featured_image == '' && $final_content != '')
                    {
                        $dom     = new DOMDocument();
                        $internalErrors = libxml_use_internal_errors(true);
                        $dom->loadHTML($final_content);
                        libxml_use_internal_errors($internalErrors);
                        $tags      = $dom->getElementsByTagName('img');
                        foreach ($tags as $tag) {
                            $temp_get_img = $tag->getAttribute('src');
                            if ($temp_get_img != '') {
                                $temp_get_img = strtok($temp_get_img, '?');
                                $featured_image = rtrim($temp_get_img, '/');
                            }
                        }
                    }
                    $post_cats = '';
                    $post_categories = wp_get_post_categories( $post->ID );
                    foreach($post_categories as $c){
                        $cat = get_category( $c );
                        $post_cats .= $cat->name . ',';
                    }
                    $post_cats = trim($post_cats, ',');
                    if($post_cats != '')
                    {
                        $post_categories = explode(',', $post_cats);
                    }
                    else
                    {
                        $post_categories = array();
                    }
                    if(count($post_categories) == 0)
                    {
                        $terms = get_the_terms( $post->ID, 'product_cat' );
                        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                            foreach ( $terms as $term ) {
                                $post_categories[] = $term->slug;
                            }
                            $post_cats = implode(',', $post_categories);
                        }
                        
                    }
                    foreach($post_categories as $pc)
                    {
                        if (!$manual && isset($aiomatic_Spinner_Settings['disabled_categories']) && !empty($aiomatic_Spinner_Settings['disabled_categories'])) {
                            foreach($aiomatic_Spinner_Settings['disabled_categories'] as $disabled_cat)
                            {
                                if($manual != true && trim($pc) == get_cat_name($disabled_cat))
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging']) && $aiomatic_Main_Settings['enable_detailed_logging'] == 'on') 
                                    {
                                        aiomatic_log_to_file('Skipping post, has a disabled category: ' . $post->post_title);
                                    }
                                    return;
                                }
                            }
                        }
                    }
                    $post_tagz = '';
                    $post_tags = wp_get_post_tags( $post->ID );
                    foreach($post_tags as $t){
                        $post_tagz .= $t->name . ',';
                    }
                    $post_tagz = trim($post_tagz, ',');
                    if($post_tagz != '')
                    {
                        $post_tags = explode(',', $post_tagz);
                    }
                    else
                    {
                        $post_tags = array();
                    }
                    if(count($post_tags) == 0)
                    {
                        $terms = get_the_terms( $post->ID, 'product_tag' );
                        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                            foreach ( $terms as $term ) {
                                $post_tags[] = $term->slug;
                            }
                            $post_tagz = implode(',', $post_tags);
                        }
                        
                    }
                    foreach($post_tags as $pt)
                    {
                        if (!$manual && isset($aiomatic_Spinner_Settings['disable_tags']) && $aiomatic_Spinner_Settings['disable_tags'] != '') {
                            
                            $disable_tags = explode(",", $aiomatic_Spinner_Settings['disable_tags']);
                            foreach($disable_tags as $disabled_tag)
                            {
                                if($manual != true && trim($pt) == trim($disabled_tag))
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging']) && $aiomatic_Main_Settings['enable_detailed_logging'] == 'on') 
                                    {
                                        aiomatic_log_to_file('Skipping post, has a disabled tag: ' . $post->post_title);
                                    }
                                    return;
                                }
                            }
                        }
                    }
                    $aicontent = replaceAIPostShortcodes($aicontent, $post_link, $post_title, $blog_title, $post->post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $post->ID, '', '');
                }
                else
                {
                    $aicontent = trim(strip_tags($final_content));
                    if(empty($aicontent))
                    {
                        $aicontent = trim(strip_tags($post->post_excerpt));
                    }
                    if(empty($aicontent))
                    {
                        $aicontent = trim(strip_tags($post_title));
                        $last_char = substr($aicontent, -1);
                        if(!ctype_punct($last_char))
                        {
                            $aicontent .= '.';
                        }
                    }
                }
                $aicontent = trim($aicontent);
                $query_token_count = count(aiomatic_encode($aicontent));
                if($query_token_count > $max_seed_tokens)
                {
                    $aicontent = substr($aicontent, 0, (0-($max_seed_tokens * 4)));
                    $query_token_count = count(aiomatic_encode($aicontent));
                }
                $available_tokens = $max_tokens - $query_token_count;
                if($available_tokens > $max_result_tokens)
                {
                    $available_tokens = $max_result_tokens;
                }
                if($available_tokens <= 16)
                {
                    $string_len = strlen($aicontent);
                    $string_len = $string_len / 2;
                    $string_len = intval(0 - $string_len);
                    $aicontent = substr($aicontent, 0, $string_len);
                    $aicontent = trim($aicontent);
                    if(empty($aicontent))
                    {
                        aiomatic_log_to_file('Empty API seed expression provided (after processing) ' . print_r($aicontent, true));
                        return;
                    }
                    $query_token_count = count(aiomatic_encode($aicontent));
                    $available_tokens = $max_tokens - $query_token_count;
                }
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                {
                    if(aiomatic_is_aiomaticapi_key($token))
                    {
                        $api_service = 'AiomaticAPI';
                    }
                    else
                    {
                        $api_service = 'OpenAI';
                    }
                    aiomatic_log_to_file('Calling ' . $api_service . ' Post Editor with seed command: ' . $aicontent);
                }
                $aierror = '';
                $aiwriter = '';
                $finish_reason = '';
                $generated_text = aiomatic_generate_text($token, $model, $aicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'contentCompletion', 0, $finish_reason, $aierror);
                if($generated_text === false)
                {
                    aiomatic_log_to_file($aierror);
                    return;
                }
                else
                {
                    $aiwriter = ucfirst(trim(nl2br(trim($generated_text))));
                }
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                {
                    if(aiomatic_is_aiomaticapi_key($token))
                    {
                        $api_service = 'AiomaticAPI';
                    }
                    else
                    {
                        $api_service = 'OpenAI';
                    }
                    aiomatic_log_to_file($api_service . ' responded successfully, post edited, ID: ' . $post->ID);
                }
                $ai_created_data = '';

                $prepp = ucfirst(trim(nl2br($aiwriter)));
                if($prepp != false && $prepp != '')
                {
                    $ai_created_data = $prepp;
                    $updated = true;
                }
                $image_query = '';
                $heading_val = '';
                if($updated == true)
                {
                    if($headings != '' && is_numeric($headings))
                    {
                        $heading_results = aiomatic_scrape_related_questions($ai_created_data, $headings, $model, $temperature, $top_p, $presence_penalty, $frequency_penalty, $max_tokens);
                    }
                    $need_more = true;
                    if (isset($aiomatic_Spinner_Settings['min_char']) && $aiomatic_Spinner_Settings['min_char'] != '') 
                    {
                        $min_char = intval($aiomatic_Spinner_Settings['min_char']);
                        $cnt = 1;
                        $max_fails = 10;
                        $failed_calls = 0;
                        if (isset($aiomatic_Spinner_Settings['max_continue_tokens']) && $aiomatic_Spinner_Settings['max_continue_tokens'] != '')
                        {
                            $max_continue_tokens = intval($aiomatic_Spinner_Settings['max_continue_tokens']);
                        }
                        else
                        {
                            $max_continue_tokens = 1000;
                        }
                        $ai_retry = false;
                        $ai_continue_title = $post_title;
                        while(strlen(strip_tags($ai_created_data)) < $min_char)
                        {
                            $need_more = false;
                            $just_set_fallback = false;
                            $image_query = '';
                            $heading_val = '';
                            if(count($heading_results) > 0)
                            {
                                $rand_heading = '';
                                $saverand = array_rand($heading_results);
                                $rand_heading = $heading_results[$saverand];
                                unset($heading_results[$saverand]);
                                if(isset($rand_heading['q']))
                                {
                                    $rand_heading['q'] = preg_replace('#^\d+\.([\s\S]*)#i', '$1', $rand_heading['q']);
                                    $heading_val = '<h2>' . $rand_heading['q'] . '</h2>' . '<span>' . $rand_heading['a'];
                                    $image_query = $rand_heading['q'];
                                }
                            }
                            if($heading_val == '')
                            {
                                $temp_post = trim($ai_created_data);
                            }
                            else
                            {
                                $temp_post = trim($heading_val);
                            }
                            if(strlen($temp_post) > $max_continue_tokens * 4)
                            {
                                $negative_contiue_tokens = 0 - ($max_continue_tokens * 4);
                                $newaicontent = substr($temp_post, $negative_contiue_tokens);
                            }
                            else
                            {
                                $newaicontent = $temp_post;
                            }
                            $add_me_to_text = '';
                            if($ai_retry == true)
                            {
                                $just_set_fallback = true;
                                if (isset($aiomatic_Main_Settings['alternate_continue']) && $aiomatic_Main_Settings['alternate_continue'] == 'on')
                                {
                                    $newaicontent = $newaicontent . ' ' . $ai_continue_title;
                                }
                                else
                                {
                                    $aierror = '';
                                    $finish_reason = '';
                                    $generated_text = aiomatic_generate_text($token, $model, 'Write a People Also Asked question related to "' . $ai_continue_title . '"', 2048, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'headingCompletion', 0, $finish_reason, $aierror);
                                    if($generated_text === false)
                                    {
                                        aiomatic_log_to_file('Similarity finding failed: ' . $aierror);
                                        $newaicontent = $aicontent;
                                    }
                                    else
                                    {
                                        $newaicontent = ucfirst(trim(nl2br(trim($generated_text))));
                                        if(empty($newaicontent))
                                        {
                                            $newaicontent = $aicontent;
                                        }
                                        else
                                        {
                                            $newaicontent = preg_replace('#^\d+\.([\s\S]*)#i', '$1', $newaicontent);
                                            $add_me_to_text = '<h3>' . $newaicontent . '</h3> ';
                                            $ai_continue_title = $newaicontent;
                                        }
                                    }
                                }
                            }
                            $ai_retry = false;
                            $newaicontent = trim($newaicontent);
                            $query_token_count = count(aiomatic_encode($newaicontent));
                            $available_tokens = $max_tokens - $query_token_count;
                            if($available_tokens <= 16)
                            {
                                $string_len = strlen($newaicontent);
                                $string_len = $string_len / 2;
                                $string_len = intval(0 - $string_len);
                                $newaicontent = substr($newaicontent, 0, $string_len);
                                $newaicontent = trim($newaicontent);
                                if(empty($newaicontent))
                                {
                                    aiomatic_log_to_file('Empty API seed expression provided (after processing) ' . print_r($temp_post, true));
                                    break;
                                }
                                $query_token_count = count(aiomatic_encode($newaicontent));
                                $available_tokens = $max_tokens - $query_token_count;
                            }
                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                if(aiomatic_is_aiomaticapi_key($token))
                                {
                                    $api_service = 'AiomaticAPI';
                                }
                                else
                                {
                                    $api_service = 'OpenAI';
                                }
                                aiomatic_log_to_file('Calling ' . $api_service . ' again (' . $cnt . ') from text editor, to meet minimum character limit: ' . $min_char . ' - current char count: ' . strlen(strip_tags($ai_created_data)));
                            }
                            $aierror = '';
                            $aiwriter = '';
                            $finish_reason = '';
                            $generated_text = aiomatic_generate_text($token, $model, $newaicontent, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'contentCompletion', 0, $finish_reason, $aierror);
                            if($generated_text === false)
                            {
                                aiomatic_log_to_file($aierror);
                                break;
                            }
                            else
                            {
                                $aiwriter = $add_me_to_text . ucfirst(trim(nl2br(trim($generated_text))));
                            }
                            
                            if($aiwriter == '')
                            {
                                $ai_retry = true;
                                if($just_set_fallback == true)
                                {
                                    aiomatic_log_to_file('Ending execution, already retried once');
                                    break;
                                }
                                continue;
                            }
                            $add_my_image = '';

                            $temp_get_img = '';
                            if($images != '' && is_numeric($images) && $images > $added_images)
                            {
                                $query_words = '';
                                if($image_query == '')
                                {
                                    $image_query = $temp_post;
                                }
                                if (isset($aiomatic_Spinner_Settings['enable_ai_images']) && ($aiomatic_Spinner_Settings['enable_ai_images'] == '1' || $aiomatic_Spinner_Settings['enable_ai_images'] == 'on')) 
                                {
                                    if (isset($aiomatic_Spinner_Settings['image_size']) && trim($aiomatic_Spinner_Settings['image_size']) != '')
                                    {
                                        $image_size = trim($aiomatic_Spinner_Settings['image_size']);
                                    }
                                    else
                                    {
                                        $image_size = '1024x1024';
                                    }
                                    $get_img = '';
                                    $query_words = $post_title;
                                    if($image_query == '')
                                    {
                                        $image_query = $temp_post;
                                    }
                                    $orig_ai_command_image = '';
                                    if (isset($aiomatic_Spinner_Settings['ai_image_command']) && $aiomatic_Spinner_Settings['ai_image_command'] != '')
                                    {
                                        $orig_ai_command_image = $aiomatic_Spinner_Settings['ai_image_command'];
                                    }
                                    if($orig_ai_command_image == '')
                                    {
                                        $orig_ai_command_image = $image_query;
                                    }
                                    if($orig_ai_command_image != '')
                                    {
                                        $ai_command_image = $orig_ai_command_image;
                                        $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                                        $ai_command_image = array_filter($ai_command_image);
                                        if(count($ai_command_image) > 0)
                                        {
                                            $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                                        }
                                        else
                                        {
                                            $ai_command_image = '';
                                        }
                                        $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                        if(!empty($ai_command_image))
                                        {
                                            $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                        }
                                        else
                                        {
                                            $ai_command_image = trim(strip_tags($post_title));
                                        }
                                        $ai_command_image = trim($ai_command_image);
                                        if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                                        {
                                            $txt_content = aiomatic_get_web_page($ai_command_image);
                                            if ($txt_content !== FALSE) 
                                            {
                                                $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                                $txt_content = array_filter($txt_content);
                                                if(count($txt_content) > 0)
                                                {
                                                    $txt_content = $txt_content[array_rand($txt_content)];
                                                    if(trim($txt_content) != '') 
                                                    {
                                                        $ai_command_image = $txt_content;
                                                        $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                                        $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                                    }
                                                }
                                            }
                                        }
                                        if(empty($ai_command_image))
                                        {
                                            aiomatic_log_to_file('Empty API image seed expression provided!');
                                        }
                                        else
                                        {
                                            if(strlen($ai_command_image) > 400)
                                            {
                                                $ai_command_image = substr($ai_command_image, 0, 400);
                                            }
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                            {
                                                if(aiomatic_is_aiomaticapi_key($token))
                                                {
                                                    $api_service = 'AiomaticAPI';
                                                }
                                                else
                                                {
                                                    $api_service = 'OpenAI';
                                                }
                                                aiomatic_log_to_file('Calling ' . $api_service . ' for image: ' . $ai_command_image);
                                            }
                                            $aierror = '';
                                            $get_img = aiomatic_generate_ai_image($token, 1, $ai_command_image, $image_size, 'editContentImage', 0, $aierror);
                                            if($get_img !== false)
                                            {
                                                foreach($get_img as $tmpimg)
                                                {
                                                    $added_images++;
                                                    $added_img_list[] = $tmpimg;
                                                    $temp_get_img = $tmpimg;
                                                }
                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                                {
                                                    aiomatic_log_to_file('AI generated image returned: ' . $tmpimg);
                                                }
                                            }
                                            else
                                            {
                                                aiomatic_log_to_file('Failed to generate AI image: ' . $aierror);
                                                $get_img = '';
                                            }
                                        }
                                    }
                                    else
                                    {
                                        aiomatic_log_to_file('Empty AI image query entered.');
                                    }
                                }
                                elseif (isset($aiomatic_Spinner_Settings['enable_ai_images']) && $aiomatic_Spinner_Settings['enable_ai_images'] == '2') 
                                {
                                    if (isset($aiomatic_Spinner_Settings['image_size']) && trim($aiomatic_Spinner_Settings['image_size']) != '')
                                    {
                                        $image_size = trim($aiomatic_Spinner_Settings['image_size']);
                                    }
                                    else
                                    {
                                        $image_size = '1024x1024';
                                    }
                                    $get_img = '';
                                    $query_words = $post_title;
                                    if($image_query == '')
                                    {
                                        $image_query = $temp_post;
                                    }
                                    $orig_ai_command_image = '';
                                    if (isset($aiomatic_Spinner_Settings['ai_image_command']) && $aiomatic_Spinner_Settings['ai_image_command'] != '')
                                    {
                                        $orig_ai_command_image = $aiomatic_Spinner_Settings['ai_image_command'];
                                    }
                                    if($orig_ai_command_image == '')
                                    {
                                        $orig_ai_command_image = $image_query;
                                    }
                                    if($orig_ai_command_image != '')
                                    {
                                        $ai_command_image = $orig_ai_command_image;
                                        $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                                        $ai_command_image = array_filter($ai_command_image);
                                        if(count($ai_command_image) > 0)
                                        {
                                            $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                                        }
                                        else
                                        {
                                            $ai_command_image = '';
                                        }
                                        $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                        if(!empty($ai_command_image))
                                        {
                                            $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                        }
                                        else
                                        {
                                            $ai_command_image = trim(strip_tags($post_title));
                                        }
                                        $ai_command_image = trim($ai_command_image);
                                        if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                                        {
                                            $txt_content = aiomatic_get_web_page($ai_command_image);
                                            if ($txt_content !== FALSE) 
                                            {
                                                $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                                $txt_content = array_filter($txt_content);
                                                if(count($txt_content) > 0)
                                                {
                                                    $txt_content = $txt_content[array_rand($txt_content)];
                                                    if(trim($txt_content) != '') 
                                                    {
                                                        $ai_command_image = $txt_content;
                                                        $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                                        $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                                    }
                                                }
                                            }
                                        }
                                        if(empty($ai_command_image))
                                        {
                                            aiomatic_log_to_file('Empty API image seed expression provided!');
                                        }
                                        else
                                        {
                                            if(strlen($ai_command_image) > 2000)
                                            {
                                                $ai_command_image = substr($ai_command_image, 0, 2000);
                                            }
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                            {
                                                $api_service = 'Stability.AI';
                                                aiomatic_log_to_file('Calling ' . $api_service . ' for image: ' . $ai_command_image);
                                            }
                                            if($image_size == '256x256')
                                            {
                                                $width = '512';
                                                $height = '512';
                                            }
                                            elseif($image_size == '512x512')
                                            {
                                                $width = '512';
                                                $height = '512';
                                            }
                                            elseif($image_size == '1024x1024')
                                            {
                                                $width = '1024';
                                                $height = '1024';
                                            }
                                            else
                                            {
                                                $width = '512';
                                                $height = '512';
                                            }
                                            $temp_get_imgs = aiomatic_generate_stability_image($ai_command_image, $height, $width, 'editorContentStableImage', 0, false);
                                            if($temp_get_imgs !== false)
                                            {
                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                                {
                                                    aiomatic_log_to_file('AI generated image returned: ' . $temp_get_imgs[1]);
                                                }
                                                $added_images++;
                                                $added_img_list[] = $temp_get_imgs[1];
                                                $temp_get_img = $temp_get_imgs[1];
                                            }
                                            else
                                            {
                                                aiomatic_log_to_file('Failed to generate Stability.AI image.');
                                                $temp_get_img = '';
                                            }
                                        }
                                    }
                                    else
                                    {
                                        aiomatic_log_to_file('Empty AI image query entered.');
                                    }
                                }
                                elseif (!isset($aiomatic_Spinner_Settings['enable_ai_images']) || $aiomatic_Spinner_Settings['enable_ai_images'] == '0') 
                                {
                                    if (isset($aiomatic_Spinner_Settings['ai_image_command']) && $aiomatic_Spinner_Settings['ai_image_command'] != '')
                                    {
                                        $image_query = $aiomatic_Spinner_Settings['ai_image_command'];
                                    }
                                    if(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'textrazor')
                                    {
                                        if(isset($aiomatic_Main_Settings['textrazor_key']) && trim($aiomatic_Main_Settings['textrazor_key']) != '')
                                        {
                                            try
                                            {
                                                if(!class_exists('TextRazor'))
                                                {
                                                    require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                                }
                                                TextRazorSettings::setApiKey(trim($aiomatic_Main_Settings['textrazor_key']));
                                                $textrazor = new TextRazor();
                                                $textrazor->addExtractor('entities');
                                                $response = $textrazor->analyze($image_query);
                                                if (isset($response['response']['entities'])) 
                                                {
                                                    foreach ($response['response']['entities'] as $entity) 
                                                    {
                                                        $query_words = '';
                                                        if(isset($entity['entityEnglishId']))
                                                        {
                                                            $query_words = $entity['entityEnglishId'];
                                                        }
                                                        else
                                                        {
                                                            $query_words = $entity['entityId'];
                                                        }
                                                        if($query_words != '')
                                                        {
                                                            $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 10, false);
                                                            if(!empty($z_img))
                                                            {
                                                                $added_images++;
                                                                $added_img_list[] = $z_img;
                                                                $temp_get_img = $z_img;
                                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                                    aiomatic_log_to_file('Royalty Free Image Generated with help of TextRazor (kw: "' . $query_words . '"): ' . $z_img);
                                                                }
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            catch(Exception $e)
                                            {
                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                    aiomatic_log_to_file('Failed to search for keywords using TextRazor (2): ' . $e->getMessage());
                                                }
                                            }
                                        }
                                    }
                                    elseif(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'openai')
                                    {
                                        if(isset($aiomatic_Main_Settings['keyword_prompts']) && trim($aiomatic_Main_Settings['keyword_prompts']) != '')
                                        {
                                            if(isset($aiomatic_Main_Settings['keyword_model']) && $aiomatic_Main_Settings['keyword_model'] != '')
                                            {
                                                $kw_model = $aiomatic_Main_Settings['keyword_model'];
                                            }
                                            else
                                            {
                                                $kw_model = 'text-davinci-003';
                                            }
                                            $title_ai_command = trim($aiomatic_Main_Settings['keyword_prompts']);
                                            $title_ai_command = preg_split('/\r\n|\r|\n/', $title_ai_command);
                                            $title_ai_command = array_filter($title_ai_command);
                                            if(count($title_ai_command) > 0)
                                            {
                                                $title_ai_command = $title_ai_command[array_rand($title_ai_command)];
                                            }
                                            else
                                            {
                                                $title_ai_command = '';
                                            }
                                            $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                            if(!empty($title_ai_command))
                                            {
                                                $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                            }
                                            $title_ai_command = trim($title_ai_command);
                                            if (filter_var($title_ai_command, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($title_ai_command, '.txt'))
                                            {
                                                $txt_content = aiomatic_get_web_page($title_ai_command);
                                                if ($txt_content !== FALSE) 
                                                {
                                                    $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                                    $txt_content = array_filter($txt_content);
                                                    if(count($txt_content) > 0)
                                                    {
                                                        $txt_content = $txt_content[array_rand($txt_content)];
                                                        if(trim($txt_content) != '') 
                                                        {
                                                            $title_ai_command = $txt_content;
                                                            $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                                            $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                                        }
                                                    }
                                                }
                                            }
                                            if(empty($title_ai_command))
                                            {
                                                aiomatic_log_to_file('Empty API keyword extractor seed expression provided!');
                                            }
                                            else
                                            {
                                                $title_ai_command = 'Extract a comma separated list of relevant keywords from the text: ' . trim(strip_tags($post_title));
                                                if(strlen($title_ai_command) > $max_seed_tokens * 4)
                                                {
                                                    $title_ai_command = substr($title_ai_command, 0, (0 - ($max_seed_tokens * 4)));
                                                }
                                                $title_ai_command = trim($title_ai_command);
                                                if(empty($title_ai_command))
                                                {
                                                    aiomatic_log_to_file('Empty API title seed expression provided(6)! ' . print_r($title_ai_command, true));
                                                }
                                                else
                                                {
                                                    $query_token_count = count(aiomatic_encode($title_ai_command));
                                                    $available_tokens = $max_tokens - $query_token_count;
                                                    if($available_tokens <= 16)
                                                    {
                                                        $string_len = strlen($title_ai_command);
                                                        $string_len = $string_len / 2;
                                                        $string_len = intval(0 - $string_len);
                                                        $title_ai_command = substr($title_ai_command, 0, $string_len);
                                                        $title_ai_command = trim($title_ai_command);
                                                        $query_token_count = count(aiomatic_encode($title_ai_command));
                                                        $available_tokens = $max_tokens - $query_token_count;
                                                    }
                                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                                    {
                                                        if(aiomatic_is_aiomaticapi_key($token))
                                                        {
                                                            $api_service = 'AiomaticAPI';
                                                        }
                                                        else
                                                        {
                                                            $api_service = 'OpenAI';
                                                        }
                                                        aiomatic_log_to_file('Calling ' . $api_service . ' for title text: ' . $title_ai_command);
                                                    }
                                                    $aierror = '';
                                                    $finish_reason = '';
                                                    $generated_text = aiomatic_generate_text($token, $kw_model, $title_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'keywordCompletion', 0, $finish_reason, $aierror);
                                                    if($generated_text === false)
                                                    {
                                                        aiomatic_log_to_file('Keyword generator error: ' . $aierror);
                                                        $ai_title = '';
                                                    }
                                                    else
                                                    {
                                                        $ai_title = trim(trim(trim(trim($generated_text), '.'), ' “”‘’"\''));
                                                        $ai_titles = explode(',', $ai_title);
                                                        foreach($ai_titles as $query_words)
                                                        {
                                                            $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, trim($query_words), $img_attr, 10, false);
                                                            if(!empty($z_img))
                                                            {
                                                                $added_images++;
                                                                $added_img_list[] = $z_img;
                                                                $temp_get_img = $z_img;
                                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                                    aiomatic_log_to_file('Royalty Free Image Generated with help of AI (kw: "' . $query_words . '"): ' . $z_img);
                                                                }
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                                    {
                                                        if(aiomatic_is_aiomaticapi_key($token))
                                                        {
                                                            $api_service = 'AiomaticAPI';
                                                        }
                                                        else
                                                        {
                                                            $api_service = 'OpenAI';
                                                        }
                                                        aiomatic_log_to_file('Successfully got API keyword result from ' . $api_service . ': ' . $ai_title);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if(empty($temp_get_img))
                                    {
                                        $keyword_class = new Aiomatic_keywords();
                                        $query_words = $keyword_class->keywords($image_query, 2);
                                        $temp_img_attr = '';
                                        $temp_get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $temp_img_attr, 10, false);
                                        if($temp_get_img == '' || $temp_get_img === false)
                                        {
                                            $query_words = $keyword_class->keywords($image_query, 1);
                                            $temp_get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $temp_img_attr, 20, false);
                                            if($temp_get_img == '' || $temp_get_img === false)
                                            {
                                                $temp_get_img = '';
                                            }
                                            else
                                            {
                                                if(!in_array($temp_get_img, $added_img_list))
                                                {
                                                    $added_images++;
                                                    $added_img_list[] = $temp_get_img;
                                                }
                                                else
                                                {
                                                    $temp_get_img = '';
                                                }
                                            }
                                        }
                                        else
                                        {
                                            if(!in_array($temp_get_img, $added_img_list))
                                            {
                                                $added_images++;
                                                $added_img_list[] = $temp_get_img;
                                            }
                                            else
                                            {
                                                $temp_get_img = '';
                                            }
                                        }
                                    }
                                }
                            }
                            if($temp_get_img != '')
                            {
                                $add_my_image = '<img class="aiomatic_image_class" src="' . $temp_get_img . '" alt="' . $query_words . '"><br/>';
                            }
                            if($heading_val == '')
                            {
                                if($add_my_image == '')
                                {
                                    $add_my_image = ' ';
                                }
                                $ai_created_data .= $add_my_image . trim(nl2br($aiwriter));
                            }
                            else
                            {
                                $ai_created_data .= $add_my_image . $heading_val . ' ' . trim(nl2br($aiwriter)) . '</span>';
                            }
                            sleep(1);
                            $cnt++;
                        }
                    }
                    if($need_more === true)
                    {
                        $add_my_image = '';
                        $temp_get_img = '';
                        if(count($heading_results) > 0)
                        {
                            $rand_heading = '';
                            $saverand = array_rand($heading_results);
                            $rand_heading = $heading_results[$saverand];
                            unset($heading_results[$saverand]);
                            if(isset($rand_heading['q']))
                            {
                                $rand_heading['q'] = preg_replace('#^\d+\.([\s\S]*)#i', '$1', $rand_heading['q']);
                                $heading_val = '<h2>' . $rand_heading['q'] . '</h2>' . '<span>' . $rand_heading['a'];
                                $image_query = $rand_heading['q'];
                            }
                        }
                        if($images != '' && is_numeric($images) && $images > $added_images)
                        {
                            if($heading_val == '')
                            {
                                $temp_post = trim($ai_created_data);
                            }
                            else
                            {
                                $temp_post = trim($heading_val);
                            }
                            $query_words = '';
                            if($image_query == '')
                            {
                                $image_query = $temp_post;
                            }
                            if (isset($aiomatic_Spinner_Settings['enable_ai_images']) && ($aiomatic_Spinner_Settings['enable_ai_images'] == '1' || $aiomatic_Spinner_Settings['enable_ai_images'] == 'on')) 
                            {
                                if (isset($aiomatic_Spinner_Settings['image_size']) && trim($aiomatic_Spinner_Settings['image_size']) != '')
                                {
                                    $image_size = trim($aiomatic_Spinner_Settings['image_size']);
                                }
                                else
                                {
                                    $image_size = '1024x1024';
                                }
                                $get_img = '';
                                $query_words = $post_title;
                                $orig_ai_command_image = '';
                                if (isset($aiomatic_Spinner_Settings['ai_image_command']) && $aiomatic_Spinner_Settings['ai_image_command'] != '')
                                {
                                    $orig_ai_command_image = $aiomatic_Spinner_Settings['ai_image_command'];
                                }
                                if($orig_ai_command_image == '')
                                {
                                    $orig_ai_command_image = $image_query;
                                }
                                if($orig_ai_command_image != '')
                                {
                                    $ai_command_image = $orig_ai_command_image;
                                    $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                                    $ai_command_image = array_filter($ai_command_image);
                                    if(count($ai_command_image) > 0)
                                    {
                                        $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                                    }
                                    else
                                    {
                                        $ai_command_image = '';
                                    }
                                    $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                    if(!empty($ai_command_image))
                                    {
                                        $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                    }
                                    else
                                    {
                                        $ai_command_image = trim(strip_tags($post_title));
                                    }
                                    $ai_command_image = trim($ai_command_image);
                                    if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                                    {
                                        $txt_content = aiomatic_get_web_page($ai_command_image);
                                        if ($txt_content !== FALSE) 
                                        {
                                            $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                            $txt_content = array_filter($txt_content);
                                            if(count($txt_content) > 0)
                                            {
                                                $txt_content = $txt_content[array_rand($txt_content)];
                                                if(trim($txt_content) != '') 
                                                {
                                                    $ai_command_image = $txt_content;
                                                    $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                                    $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                                }
                                            }
                                        }
                                    }
                                    if(empty($ai_command_image))
                                    {
                                        aiomatic_log_to_file('Empty API image seed expression provided!');
                                    }
                                    else
                                    {
                                        if(strlen($ai_command_image) > 400)
                                        {
                                            $ai_command_image = substr($ai_command_image, 0, 400);
                                        }
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                        {
                                            if(aiomatic_is_aiomaticapi_key($token))
                                            {
                                                $api_service = 'AiomaticAPI';
                                            }
                                            else
                                            {
                                                $api_service = 'OpenAI';
                                            }
                                            aiomatic_log_to_file('Calling ' . $api_service . ' for image: ' . $ai_command_image);
                                        }
                                        $aierror = '';
                                        $get_img = aiomatic_generate_ai_image($token, 1, $ai_command_image, $image_size, 'editContentImage', 0, $aierror);
                                        if($get_img !== false)
                                        {
                                            foreach($get_img as $tmpimg)
                                            {
                                                $added_images++;
                                                $added_img_list[] = $tmpimg;
                                                $temp_get_img = $tmpimg;
                                            }
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                            {
                                                aiomatic_log_to_file('AI generated image returned: ' . $tmpimg);
                                            }
                                        }
                                        else
                                        {
                                            aiomatic_log_to_file('Failed to generate AI image: ' . $aierror);
                                            $get_img = '';
                                        }
                                    }
                                }
                                else
                                {
                                    aiomatic_log_to_file('Empty AI image query entered.');
                                }
                            }
                            elseif (isset($aiomatic_Spinner_Settings['enable_ai_images']) && $aiomatic_Spinner_Settings['enable_ai_images'] == '2') 
                            {
                                if (isset($aiomatic_Spinner_Settings['image_size']) && trim($aiomatic_Spinner_Settings['image_size']) != '')
                                {
                                    $image_size = trim($aiomatic_Spinner_Settings['image_size']);
                                }
                                else
                                {
                                    $image_size = '1024x1024';
                                }
                                $get_img = '';
                                $query_words = $post_title;
                                if($image_query == '')
                                {
                                    $image_query = $temp_post;
                                }
                                $orig_ai_command_image = '';
                                if (isset($aiomatic_Spinner_Settings['ai_image_command']) && $aiomatic_Spinner_Settings['ai_image_command'] != '')
                                {
                                    $orig_ai_command_image = $aiomatic_Spinner_Settings['ai_image_command'];
                                }
                                if($orig_ai_command_image == '')
                                {
                                    $orig_ai_command_image = $image_query;
                                }
                                if($orig_ai_command_image != '')
                                {
                                    $ai_command_image = $orig_ai_command_image;
                                    $ai_command_image = preg_split('/\r\n|\r|\n/', $ai_command_image);
                                    $ai_command_image = array_filter($ai_command_image);
                                    if(count($ai_command_image) > 0)
                                    {
                                        $ai_command_image = $ai_command_image[array_rand($ai_command_image)];
                                    }
                                    else
                                    {
                                        $ai_command_image = '';
                                    }
                                    $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                    if(!empty($ai_command_image))
                                    {
                                        $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                    }
                                    else
                                    {
                                        $ai_command_image = trim(strip_tags($post_title));
                                    }
                                    $ai_command_image = trim($ai_command_image);
                                    if (filter_var($ai_command_image, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($ai_command_image, '.txt'))
                                    {
                                        $txt_content = aiomatic_get_web_page($ai_command_image);
                                        if ($txt_content !== FALSE) 
                                        {
                                            $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                            $txt_content = array_filter($txt_content);
                                            if(count($txt_content) > 0)
                                            {
                                                $txt_content = $txt_content[array_rand($txt_content)];
                                                if(trim($txt_content) != '') 
                                                {
                                                    $ai_command_image = $txt_content;
                                                    $ai_command_image = aiomatic_replaceSynergyShortcodes($ai_command_image);
                                                    $ai_command_image = replaceAIPostShortcodes($ai_command_image, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                                }
                                            }
                                        }
                                    }
                                    if(empty($ai_command_image))
                                    {
                                        aiomatic_log_to_file('Empty API image seed expression provided!');
                                    }
                                    else
                                    {
                                        if(strlen($ai_command_image) > 2000)
                                        {
                                            $ai_command_image = substr($ai_command_image, 0, 2000);
                                        }
                                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                        {
                                            $api_service = 'Stability.AI';
                                            aiomatic_log_to_file('Calling ' . $api_service . ' for image: ' . $ai_command_image);
                                        }
                                        if($image_size == '256x256')
                                        {
                                            $width = '512';
                                            $height = '512';
                                        }
                                        elseif($image_size == '512x512')
                                        {
                                            $width = '512';
                                            $height = '512';
                                        }
                                        elseif($image_size == '1024x1024')
                                        {
                                            $width = '1024';
                                            $height = '1024';
                                        }
                                        else
                                        {
                                            $width = '512';
                                            $height = '512';
                                        }
                                        $temp_get_imgs = aiomatic_generate_stability_image($ai_command_image, $height, $width, 'editorContentStableImage', 0, false);
                                        if($temp_get_imgs !== false)
                                        {
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                            {
                                                aiomatic_log_to_file('AI generated image returned: ' . $temp_get_imgs[1]);
                                            }
                                            $added_images++;
                                            $added_img_list[] = $temp_get_imgs[1];
                                            $temp_get_img = $temp_get_imgs[1];
                                        }
                                        else
                                        {
                                            aiomatic_log_to_file('Failed to generate Stability.AI image.');
                                            $temp_get_img = '';
                                        }
                                    }
                                }
                                else
                                {
                                    aiomatic_log_to_file('Empty AI image query entered.');
                                }
                            }
                            elseif (!isset($aiomatic_Spinner_Settings['enable_ai_images']) || $aiomatic_Spinner_Settings['enable_ai_images'] == '0') 
                            {
                                if (isset($aiomatic_Spinner_Settings['ai_image_command']) && $aiomatic_Spinner_Settings['ai_image_command'] != '')
                                {
                                    $image_query = $aiomatic_Spinner_Settings['ai_image_command'];
                                }
                                if(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'textrazor')
                                {
                                    if(isset($aiomatic_Main_Settings['textrazor_key']) && trim($aiomatic_Main_Settings['textrazor_key']) != '')
                                    {
                                        try
                                        {
                                            if(!class_exists('TextRazor'))
                                            {
                                                require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                            }
                                            TextRazorSettings::setApiKey(trim($aiomatic_Main_Settings['textrazor_key']));
                                            $textrazor = new TextRazor();
                                            $textrazor->addExtractor('entities');
                                            $response = $textrazor->analyze($image_query);
                                            if (isset($response['response']['entities'])) 
                                            {
                                                foreach ($response['response']['entities'] as $entity) 
                                                {
                                                    $query_words = '';
                                                    if(isset($entity['entityEnglishId']))
                                                    {
                                                        $query_words = $entity['entityEnglishId'];
                                                    }
                                                    else
                                                    {
                                                        $query_words = $entity['entityId'];
                                                    }
                                                    if($query_words != '')
                                                    {
                                                        $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 10, false);
                                                        if(!empty($z_img))
                                                        {
                                                            $added_images++;
                                                            $added_img_list[] = $z_img;
                                                            $temp_get_img = $z_img;
                                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                                aiomatic_log_to_file('Royalty Free Image Generated with help of TextRazor (kw: "' . $query_words . '"): ' . $z_img);
                                                            }
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        catch(Exception $e)
                                        {
                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                aiomatic_log_to_file('Failed to search for keywords using TextRazor (2): ' . $e->getMessage());
                                            }
                                        }
                                    }
                                }
                                elseif(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'openai')
                                {
                                    if(isset($aiomatic_Main_Settings['keyword_prompts']) && trim($aiomatic_Main_Settings['keyword_prompts']) != '')
                                    {
                                        if(isset($aiomatic_Main_Settings['keyword_model']) && $aiomatic_Main_Settings['keyword_model'] != '')
                                        {
                                            $kw_model = $aiomatic_Main_Settings['keyword_model'];
                                        }
                                        else
                                        {
                                            $kw_model = 'text-davinci-003';
                                        }
                                        $title_ai_command = trim($aiomatic_Main_Settings['keyword_prompts']);
                                        $title_ai_command = preg_split('/\r\n|\r|\n/', $title_ai_command);
                                        $title_ai_command = array_filter($title_ai_command);
                                        if(count($title_ai_command) > 0)
                                        {
                                            $title_ai_command = $title_ai_command[array_rand($title_ai_command)];
                                        }
                                        else
                                        {
                                            $title_ai_command = '';
                                        }
                                        $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                        if(!empty($title_ai_command))
                                        {
                                            $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                        }
                                        $title_ai_command = trim($title_ai_command);
                                        if (filter_var($title_ai_command, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($title_ai_command, '.txt'))
                                        {
                                            $txt_content = aiomatic_get_web_page($title_ai_command);
                                            if ($txt_content !== FALSE) 
                                            {
                                                $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                                $txt_content = array_filter($txt_content);
                                                if(count($txt_content) > 0)
                                                {
                                                    $txt_content = $txt_content[array_rand($txt_content)];
                                                    if(trim($txt_content) != '') 
                                                    {
                                                        $title_ai_command = $txt_content;
                                                        $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                                        $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                                    }
                                                }
                                            }
                                        }
                                        if(empty($title_ai_command))
                                        {
                                            aiomatic_log_to_file('Empty API keyword extractor seed expression provided!');
                                        }
                                        else
                                        {
                                            $title_ai_command = 'Extract a comma separated list of relevant keywords from the text: ' . trim(strip_tags($post_title));
                                            if(strlen($title_ai_command) > $max_seed_tokens * 4)
                                            {
                                                $title_ai_command = substr($title_ai_command, 0, (0 - ($max_seed_tokens * 4)));
                                            }
                                            $title_ai_command = trim($title_ai_command);
                                            if(empty($title_ai_command))
                                            {
                                                aiomatic_log_to_file('Empty API title seed expression provided(7)! ' . print_r($title_ai_command, true));
                                            }
                                            else
                                            {
                                                $query_token_count = count(aiomatic_encode($title_ai_command));
                                                $available_tokens = $max_tokens - $query_token_count;
                                                if($available_tokens <= 16)
                                                {
                                                    $string_len = strlen($title_ai_command);
                                                    $string_len = $string_len / 2;
                                                    $string_len = intval(0 - $string_len);
                                                    $title_ai_command = substr($title_ai_command, 0, $string_len);
                                                    $title_ai_command = trim($title_ai_command);
                                                    $query_token_count = count(aiomatic_encode($title_ai_command));
                                                    $available_tokens = $max_tokens - $query_token_count;
                                                }
                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                                {
                                                    if(aiomatic_is_aiomaticapi_key($token))
                                                    {
                                                        $api_service = 'AiomaticAPI';
                                                    }
                                                    else
                                                    {
                                                        $api_service = 'OpenAI';
                                                    }
                                                    aiomatic_log_to_file('Calling ' . $api_service . ' for title text: ' . $title_ai_command);
                                                }
                                                $aierror = '';
                                                $finish_reason = '';
                                                $generated_text = aiomatic_generate_text($token, $kw_model, $title_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'keywordCompletion', 0, $finish_reason, $aierror);
                                                if($generated_text === false)
                                                {
                                                    aiomatic_log_to_file('Keyword generator error: ' . $aierror);
                                                    $ai_title = '';
                                                }
                                                else
                                                {
                                                    $ai_title = trim(trim(trim(trim($generated_text), '.'), ' “”‘’"\''));
                                                    $ai_titles = explode(',', $ai_title);
                                                    foreach($ai_titles as $query_words)
                                                    {
                                                        $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, trim($query_words), $img_attr, 10, false);
                                                        if(!empty($z_img))
                                                        {
                                                            $added_images++;
                                                            $added_img_list[] = $z_img;
                                                            $temp_get_img = $z_img;
                                                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                                aiomatic_log_to_file('Royalty Free Image Generated with help of AI (kw: "' . $query_words . '"): ' . $z_img);
                                                            }
                                                            break;
                                                        }
                                                    }
                                                }
                                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                                {
                                                    if(aiomatic_is_aiomaticapi_key($token))
                                                    {
                                                        $api_service = 'AiomaticAPI';
                                                    }
                                                    else
                                                    {
                                                        $api_service = 'OpenAI';
                                                    }
                                                    aiomatic_log_to_file('Successfully got API keyword result from ' . $api_service . ': ' . $ai_title);
                                                }
                                            }
                                        }
                                    }
                                }
                                if(empty($temp_get_img))
                                {
                                    $keyword_class = new Aiomatic_keywords();
                                    $query_words = $keyword_class->keywords($image_query, 2);
                                    $temp_img_attr = '';
                                    $temp_get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $temp_img_attr, 10, false);
                                    if($temp_get_img == '' || $temp_get_img === false)
                                    {
                                        $query_words = $keyword_class->keywords($image_query, 1);
                                        $temp_get_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $temp_img_attr, 20, false);
                                        if($temp_get_img == '' || $temp_get_img === false)
                                        {
                                            $temp_get_img = '';
                                        }
                                        else
                                        {
                                            if(!in_array($temp_get_img, $added_img_list))
                                            {
                                                $added_images++;
                                                $added_img_list[] = $temp_get_img;
                                            }
                                            else
                                            {
                                                $temp_get_img = '';
                                            }
                                        }
                                    }
                                    else
                                    {
                                        if(!in_array($temp_get_img, $added_img_list))
                                        {
                                            $added_images++;
                                            $added_img_list[] = $temp_get_img;
                                        }
                                        else
                                        {
                                            $temp_get_img = '';
                                        }
                                    }
                                }
                            }
                        }
                        if($heading_val != '')
                        {
                            $ai_created_data = $heading_val . ' ' . $ai_created_data;
                            $updated = true;
                        }
                        if($temp_get_img != '')
                        {
                            $ai_created_data = '<img class="aiomatic_image_class" src="' . $temp_get_img . '" alt="' . $query_words . '">' . ' ' . $ai_created_data;
                            $updated = true;
                        }
                    }
                }
                if($ai_created_data != false && $ai_created_data != '')
                {
                    if (isset($aiomatic_Spinner_Settings['append_spintax']) && $aiomatic_Spinner_Settings['append_spintax'] == 'append') 
                    {
                        $final_content = $final_content . ' <br/> ' . $ai_created_data;
                        $updated = true;
                    }
                    elseif (isset($aiomatic_Spinner_Settings['append_spintax']) && $aiomatic_Spinner_Settings['append_spintax'] == 'preppend')
                    {
                        $final_content = $ai_created_data . ' <br/> ' . $final_content;
                        $updated = true;
                    }
                }
                if ($videos == 'on') 
                {
                    if (isset($aiomatic_Main_Settings['yt_app_id']) && trim($aiomatic_Main_Settings['yt_app_id']) != '') 
                    {
                        $items = array();
                        $vid_id = '';
                        $za_app = explode(',', $aiomatic_Main_Settings['yt_app_id']);
                        $za_app = trim($za_app[array_rand($za_app)]);
                        $feed_uri = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&key=' . $za_app;
                        $feed_uri .= '&maxResults=10';
                        $feed_uri .= '&q='.urlencode(trim(stripslashes(str_replace('&quot;', '"', $post_title))));
                        $ch  = curl_init();
                        if ($ch !== FALSE) {
                            if (isset($aiomatic_Main_Settings['proxy_url']) && $aiomatic_Main_Settings['proxy_url'] != '') {
                                curl_setopt($ch, CURLOPT_PROXY, $aiomatic_Main_Settings['proxy_url']);
                                if (isset($aiomatic_Main_Settings['proxy_auth']) && $aiomatic_Main_Settings['proxy_auth'] != '') {
                                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $aiomatic_Main_Settings['proxy_auth']);
                                }
                            }
                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                            curl_setopt($ch, CURLOPT_HTTPGET, 1);
                            curl_setopt($ch, CURLOPT_REFERER, get_site_url());
                            curl_setopt($ch, CURLOPT_URL, $feed_uri);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                            $exec = curl_exec($ch);
                            curl_close($ch);
                            if ($exec !== FALSE) {
                                $json  = json_decode($exec);
                                if(isset($json->items))
                                {
                                    $items = $json->items;
                                    if (count($items) == 0) 
                                    {
                                        $feed_uri = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&key=' . $za_app;
                                        $feed_uri .= '&maxResults=10';
                                        $keyword_class = new Aiomatic_keywords();
                                        $post_title = $keyword_class->keywords($post_title, 2);
                                        $feed_uri .= '&q='.urlencode(trim(stripslashes(str_replace('&quot;', '"', $post_title))));
                                        $ch  = curl_init();
                                        if ($ch !== FALSE) {
                                            if (isset($aiomatic_Main_Settings['proxy_url']) && $aiomatic_Main_Settings['proxy_url'] != '') {
                                                curl_setopt($ch, CURLOPT_PROXY, $aiomatic_Main_Settings['proxy_url']);
                                                if (isset($aiomatic_Main_Settings['proxy_auth']) && $aiomatic_Main_Settings['proxy_auth'] != '') {
                                                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $aiomatic_Main_Settings['proxy_auth']);
                                                }
                                            }
                                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                                            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                                            curl_setopt($ch, CURLOPT_HTTPGET, 1);
                                            curl_setopt($ch, CURLOPT_REFERER, get_site_url());
                                            curl_setopt($ch, CURLOPT_URL, $feed_uri);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                            $exec = curl_exec($ch);
                                            curl_close($ch);
                                            if ($exec === FALSE) {
                                                $json  = json_decode($exec);
                                                if(isset($json->items))
                                                {
                                                    $items = $json->items;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if(isset($items[0]->id->videoId))
                        {
                            $rand_ind = array_rand($items);
                            $video_id = $items[$rand_ind]->id->videoId;
                            $final_content .= '<br/><br/><div class="automaticx-video-container"><iframe allow="autoplay" width="' . $width . '" height="' . $height . '" src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
                            $updated = true;
                        }
                    }
                    else
                    {
                        $final_content .= aiomatic_get_youtube_video(trim(stripslashes(str_replace('&quot;', '"', $post_title))), '');
                        $updated = true;
                    }
                }
                if($updated == true)
                {
                    $args = array();
                    $args['ID'] = $post->ID;
                    if (isset($aiomatic_Main_Settings['swear_filter']) && $aiomatic_Main_Settings['swear_filter'] == 'on') 
                    {
                        require_once(dirname(__FILE__) . "/res/swear.php");
                        $final_content = aiomatic_filterwords($final_content);
                    }
                    $args['post_content'] = $final_content;
                    $args['post_title'] = $post_title;
                    if (isset($aiomatic_Spinner_Settings['change_status']) && $aiomatic_Spinner_Settings['change_status'] != '' && $aiomatic_Spinner_Settings['change_status'] != 'no') 
                    {
                        $args['post_status'] = $aiomatic_Spinner_Settings['change_status'];
                    }
                    remove_filter('content_save_pre', 'wp_filter_post_kses');
                    remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                    remove_filter('title_save_pre', 'wp_filter_kses');
                    $post_updated = wp_update_post($args);
                    add_filter('content_save_pre', 'wp_filter_post_kses');
                    add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
                    add_filter('title_save_pre', 'wp_filter_kses');
                    if (is_wp_error($post_updated)) {
                        $errors = $post_updated->get_error_messages();
                        foreach ($errors as $error) {
                            aiomatic_log_to_file('Error occured while updating post "' . $post->post_title . '": ' . $error);
                        }
                    }
                    else
                    {
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                            aiomatic_log_to_file('Post ID ' . $post->ID . ' was successfully updated with AI generated content.');
                        }
                    }
                }
            }
            if (isset($aiomatic_Spinner_Settings['ai_featured_image']) && $aiomatic_Spinner_Settings['ai_featured_image'] != '' && $aiomatic_Spinner_Settings['ai_featured_image'] != 'disabled')
            {
                if (isset($aiomatic_Spinner_Settings['image_size']) && trim($aiomatic_Spinner_Settings['image_size']) != '')
                {
                    $image_size = trim($aiomatic_Spinner_Settings['image_size']);
                }
                else
                {
                    $image_size = '1024x1024';
                }
                if (isset($aiomatic_Spinner_Settings['ai_image_command']) && $aiomatic_Spinner_Settings['ai_image_command'] != '')
                {
                    $aicontent = trim(strip_tags($aiomatic_Spinner_Settings['ai_image_command']));
                    $aicontent = aiomatic_replaceSynergyShortcodes($aicontent);
                    $post_link = get_permalink($post->ID);
                    $blog_title       = html_entity_decode(get_bloginfo('title'));
                    $author_obj       = get_user_by('id', $post->post_author);
                    $user_name        = $author_obj->user_nicename;
                    $featured_image   = '';
                    wp_suspend_cache_addition(true);
                    $metas = get_post_custom($post->ID);
                    wp_suspend_cache_addition(false);
                    if(is_array($metas))
                    {
                        $rez_meta = aiomatic_preg_grep_keys('#.+?_featured_ima?ge?#i', $metas);
                    }
                    else
                    {
                        $rez_meta = array();
                    }
                    if(count($rez_meta) > 0)
                    {
                        foreach($rez_meta as $rm)
                        {
                            if(isset($rm[0]) && filter_var($rm[0], FILTER_VALIDATE_URL))
                            {
                                $featured_image = $rm[0];
                                break;
                            }
                        }
                    }
                    if($featured_image == '')
                    {
                        $featured_image = aiomatic_generate_thumbmail($post->ID);;
                    }
                    if($featured_image == '' && $final_content != '')
                    {
                        $dom     = new DOMDocument();
                        $internalErrors = libxml_use_internal_errors(true);
                        $dom->loadHTML($final_content);
                        libxml_use_internal_errors($internalErrors);
                        $tags      = $dom->getElementsByTagName('img');
                        foreach ($tags as $tag) {
                            $temp_get_img = $tag->getAttribute('src');
                            if ($temp_get_img != '') {
                                $temp_get_img = strtok($temp_get_img, '?');
                                $featured_image = rtrim($temp_get_img, '/');
                            }
                        }
                    }
                    $post_cats = '';
                    $post_categories = wp_get_post_categories( $post->ID );
                    foreach($post_categories as $c){
                        $cat = get_category( $c );
                        $post_cats .= $cat->name . ',';
                    }
                    $post_cats = trim($post_cats, ',');
                    if($post_cats != '')
                    {
                        $post_categories = explode(',', $post_cats);
                    }
                    else
                    {
                        $post_categories = array();
                    }
                    if(count($post_categories) == 0)
                    {
                        $terms = get_the_terms( $post->ID, 'product_cat' );
                        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                            foreach ( $terms as $term ) {
                                $post_categories[] = $term->slug;
                            }
                            $post_cats = implode(',', $post_categories);
                        }
                        
                    }
                    foreach($post_categories as $pc)
                    {
                        if (!$manual && isset($aiomatic_Spinner_Settings['disabled_categories']) && !empty($aiomatic_Spinner_Settings['disabled_categories'])) {
                            foreach($aiomatic_Spinner_Settings['disabled_categories'] as $disabled_cat)
                            {
                                if($manual != true && trim($pc) == get_cat_name($disabled_cat))
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging']) && $aiomatic_Main_Settings['enable_detailed_logging'] == 'on') 
                                    {
                                        aiomatic_log_to_file('Skipping post, has a disabled category: ' . $post->post_title);
                                    }
                                    return;
                                }
                            }
                        }
                    }
                    $post_tagz = '';
                    $post_tags = wp_get_post_tags( $post->ID );
                    foreach($post_tags as $t){
                        $post_tagz .= $t->name . ',';
                    }
                    $post_tagz = trim($post_tagz, ',');
                    if($post_tagz != '')
                    {
                        $post_tags = explode(',', $post_tagz);
                    }
                    else
                    {
                        $post_tags = array();
                    }
                    if(count($post_tags) == 0)
                    {
                        $terms = get_the_terms( $post->ID, 'product_tag' );
                        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                            foreach ( $terms as $term ) {
                                $post_tags[] = $term->slug;
                            }
                            $post_tagz = implode(',', $post_tags);
                        }
                        
                    }
                    foreach($post_tags as $pt)
                    {
                        if (!$manual && isset($aiomatic_Spinner_Settings['disable_tags']) && $aiomatic_Spinner_Settings['disable_tags'] != '') {
                            
                            $disable_tags = explode(",", $aiomatic_Spinner_Settings['disable_tags']);
                            foreach($disable_tags as $disabled_tag)
                            {
                                if($manual != true && trim($pt) == trim($disabled_tag))
                                {
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging']) && $aiomatic_Main_Settings['enable_detailed_logging'] == 'on') 
                                    {
                                        aiomatic_log_to_file('Skipping post, has a disabled tag: ' . $post->post_title);
                                    }
                                    return;
                                }
                            }
                        }
                    }
                    $aicontent = replaceAIPostShortcodes($aicontent, $post_link, $post_title, $blog_title, $post->post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $post->ID, '', '');
                }
                else
                {
                    $aicontent = trim(strip_tags($final_content));
                    if(empty($aicontent))
                    {
                        $aicontent = trim(strip_tags($post->post_excerpt));
                    }
                    if(empty($aicontent))
                    {
                        $aicontent = trim(strip_tags($post_title));
                        $last_char = substr($aicontent, -1);
                        if(!ctype_punct($last_char))
                        {
                            $aicontent .= '.';
                        }
                    }
                }
                if(isset($aiomatic_Spinner_Settings['ai_featured_image_source']) && $aiomatic_Spinner_Settings['ai_featured_image_source'] != '')
                {
                    $fisource = $aiomatic_Spinner_Settings['ai_featured_image_source'];
                }
                else
                {
                    $fisource = '1';
                }
                if($fisource == '1')
                {
                    $aicontent = trim($aicontent);
                    if(strlen($aicontent) > 400)
                    {
                        $aicontent = substr($aicontent, 0, 400);
                    }
                    $aierror = '';
                    $temp_get_imgs = aiomatic_generate_ai_image($token, 1, $aicontent, $image_size, 'editFeaturedImage', 0, $aierror);
                    if($temp_get_imgs !== false)
                    {
                        foreach($temp_get_imgs as $tmpimg)
                        {
                            if (!aiomatic_generate_featured_image($tmpimg, $post->ID)) {
                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                    aiomatic_log_to_file('aiomatic_generate_featured_image failed using OpenAI/AiomaticAPI for ' . $tmpimg);
                                }
                            }
                            break;
                        }
                    }
                    else
                    {
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                            aiomatic_log_to_file('Post ID ' . $post->ID . ' failed to generated a featured image using OpenAI/AiomaticAPI: ' . $aierror);
                        }
                    }
                }
                elseif($fisource == '2')
                {
                    $aicontent = trim($aicontent);
                    if(strlen($aicontent) > 2000)
                    {
                        $aicontent = substr($aicontent, 0, 2000);
                    }
                    if($image_size == '256x256')
                    {
                        $width = '512';
                        $height = '512';
                    }
                    elseif($image_size == '512x512')
                    {
                        $width = '512';
                        $height = '512';
                    }
                    elseif($image_size == '1024x1024')
                    {
                        $width = '1024';
                        $height = '1024';
                    }
                    else
                    {
                        $width = '512';
                        $height = '512';
                    }
                    $temp_get_imgs = aiomatic_generate_stability_image($aicontent, $height, $width, 'editorFeaturedStableImage', 0, false);
                    if($temp_get_imgs !== false)
                    {
                        $temp_get_img_local = $temp_get_imgs[0];
                        if (!aiomatic_assign_featured_image_path($temp_get_img_local, $post->ID)) {
                            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                aiomatic_log_to_file('aiomatic_generate_featured_image failed using Stability.AI for ' .$temp_get_imgs[1]);
                            }
                        }
                    }
                    else
                    {
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                            aiomatic_log_to_file('Post ID ' . $post->ID . ' failed to generated a featured image using Stability.AI');
                        }
                    }
                }
                elseif($fisource == '0')
                {
                    $img_set = false;
                    $img_attr = '';
                    $postID = $post->ID;
                    $post_excerpt = $post->post_excerpt;
                    $query_words = '';
                    $image_query = $post_title;
                    if (isset($aiomatic_Spinner_Settings['ai_image_command']) && $aiomatic_Spinner_Settings['ai_image_command'] != '')
                    {
                        $image_query = $aiomatic_Spinner_Settings['ai_image_command'];
                    }
                    if(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'textrazor')
                    {
                        if(isset($aiomatic_Main_Settings['textrazor_key']) && trim($aiomatic_Main_Settings['textrazor_key']) != '')
                        {
                            try
                            {
                                if(!class_exists('TextRazor'))
                                {
                                    require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                }
                                TextRazorSettings::setApiKey(trim($aiomatic_Main_Settings['textrazor_key']));
                                $textrazor = new TextRazor();
                                $textrazor->addExtractor('entities');
                                $response = $textrazor->analyze($aicontent);
                                if (isset($response['response']['entities'])) 
                                {
                                    foreach ($response['response']['entities'] as $entity) 
                                    {
                                        $query_words = '';
                                        if(isset($entity['entityEnglishId']))
                                        {
                                            $query_words = $entity['entityEnglishId'];
                                        }
                                        else
                                        {
                                            $query_words = $entity['entityId'];
                                        }
                                        if($query_words != '')
                                        {
                                            $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 10, true);
                                            if(!empty($z_img))
                                            {
                                                if (!aiomatic_generate_featured_image($z_img, $post->ID)) {
                                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                        aiomatic_log_to_file('aiomatic_generate_featured_image failed using royalty free image: ' . $z_img);
                                                    }
                                                }
                                                else
                                                {
                                                    $img_set = true;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            catch(Exception $e)
                            {
                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                    aiomatic_log_to_file('Failed to search for keywords using TextRazor (2): ' . $e->getMessage());
                                }
                            }
                        }
                    }
                    elseif(isset($aiomatic_Main_Settings['improve_keywords']) && trim($aiomatic_Main_Settings['improve_keywords']) == 'openai')
                    {
                        if(isset($aiomatic_Main_Settings['keyword_prompts']) && trim($aiomatic_Main_Settings['keyword_prompts']) != '')
                        {
                            if(isset($aiomatic_Main_Settings['keyword_model']) && $aiomatic_Main_Settings['keyword_model'] != '')
                            {
                                $kw_model = $aiomatic_Main_Settings['keyword_model'];
                            }
                            else
                            {
                                $kw_model = 'text-davinci-003';
                            }
                            $title_ai_command = trim($aiomatic_Main_Settings['keyword_prompts']);
                            $title_ai_command = preg_split('/\r\n|\r|\n/', $title_ai_command);
                            $title_ai_command = array_filter($title_ai_command);
                            if(count($title_ai_command) > 0)
                            {
                                $title_ai_command = $title_ai_command[array_rand($title_ai_command)];
                            }
                            else
                            {
                                $title_ai_command = '';
                            }
                            $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                            if(!empty($title_ai_command))
                            {
                                $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                            }
                            $title_ai_command = trim($title_ai_command);
                            if (filter_var($title_ai_command, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($title_ai_command, '.txt'))
                            {
                                $txt_content = aiomatic_get_web_page($title_ai_command);
                                if ($txt_content !== FALSE) 
                                {
                                    $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                                    $txt_content = array_filter($txt_content);
                                    if(count($txt_content) > 0)
                                    {
                                        $txt_content = $txt_content[array_rand($txt_content)];
                                        if(trim($txt_content) != '') 
                                        {
                                            $title_ai_command = $txt_content;
                                            $title_ai_command = aiomatic_replaceSynergyShortcodes($title_ai_command);
                                            $title_ai_command = replaceAIPostShortcodes($title_ai_command, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, $img_attr, '');
                                        }
                                    }
                                }
                            }
                            if(empty($title_ai_command))
                            {
                                aiomatic_log_to_file('Empty API keyword extractor seed expression provided!');
                            }
                            else
                            {
                                $title_ai_command = 'Extract a comma separated list of relevant keywords from the text: ' . trim(strip_tags($post_title));
                                if(strlen($title_ai_command) > $max_seed_tokens * 4)
                                {
                                    $title_ai_command = substr($title_ai_command, 0, (0 - ($max_seed_tokens * 4)));
                                }
                                $title_ai_command = trim($title_ai_command);
                                if(empty($title_ai_command))
                                {
                                    aiomatic_log_to_file('Empty API title seed expression provided(8)! ' . print_r($title_ai_command, true));
                                }
                                else
                                {
                                    $query_token_count = count(aiomatic_encode($title_ai_command));
                                    $available_tokens = $max_tokens - $query_token_count;
                                    if($available_tokens <= 16)
                                    {
                                        $string_len = strlen($title_ai_command);
                                        $string_len = $string_len / 2;
                                        $string_len = intval(0 - $string_len);
                                        $title_ai_command = substr($title_ai_command, 0, $string_len);
                                        $title_ai_command = trim($title_ai_command);
                                        $query_token_count = count(aiomatic_encode($title_ai_command));
                                        $available_tokens = $max_tokens - $query_token_count;
                                    }
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                    {
                                        if(aiomatic_is_aiomaticapi_key($token))
                                        {
                                            $api_service = 'AiomaticAPI';
                                        }
                                        else
                                        {
                                            $api_service = 'OpenAI';
                                        }
                                        aiomatic_log_to_file('Calling ' . $api_service . ' for title text: ' . $title_ai_command);
                                    }
                                    $aierror = '';
                                    $finish_reason = '';
                                    $generated_text = aiomatic_generate_text($token, $kw_model, $title_ai_command, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'keywordCompletion', 0, $finish_reason, $aierror);
                                    if($generated_text === false)
                                    {
                                        aiomatic_log_to_file('Keyword generator error: ' . $aierror);
                                        $ai_title = '';
                                    }
                                    else
                                    {
                                        $ai_title = trim(trim(trim(trim($generated_text), '.'), ' “”‘’"\''));
                                        $ai_titles = explode(',', $ai_title);
                                        foreach($ai_titles as $query_words)
                                        {
                                            $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, trim($query_words), $img_attr, 10, true);
                                            if(!empty($z_img))
                                            {
                                                if (!aiomatic_generate_featured_image($z_img, $post->ID)) {
                                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                                        aiomatic_log_to_file('aiomatic_generate_featured_image failed using royalty free image: ' . $z_img);
                                                    }
                                                }
                                                else
                                                {
                                                    $img_set = true;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) 
                                    {
                                        if(aiomatic_is_aiomaticapi_key($token))
                                        {
                                            $api_service = 'AiomaticAPI';
                                        }
                                        else
                                        {
                                            $api_service = 'OpenAI';
                                        }
                                        aiomatic_log_to_file('Successfully got API keyword result from ' . $api_service . ': ' . $ai_title);
                                    }
                                }
                            }
                        }
                    }
                    if($img_set == false)
                    {
                        $keyword_class = new Aiomatic_keywords();
                        $query_words = $keyword_class->keywords($image_query, 2);
                        $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 10, true);
                        if($z_img == '' || $z_img === false)
                        {
                            if(isset($aiomatic_Main_Settings['bimage']) && $aiomatic_Main_Settings['bimage'] == 'on')
                            {
                                $query_words = $keyword_class->keywords($image_query, 1);
                                $z_img = aiomatic_get_free_image($aiomatic_Main_Settings, $query_words, $img_attr, 20, true);
                            }
                        }
                        if(!empty($z_img))
                        {
                            if (!aiomatic_generate_featured_image($z_img, $post->ID)) {
                                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                                    aiomatic_log_to_file('aiomatic_generate_featured_image failed using royalty free image: ' . $z_img);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
function aiomatic_get_youtube_video($keyword, $chance = '')
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['player_width']) && $aiomatic_Main_Settings['player_width'] !== '') {
        $width = esc_attr($aiomatic_Main_Settings['player_width']);
    }
    else
    {
        $width = 580;
    }
    if (isset($aiomatic_Main_Settings['player_height']) && $aiomatic_Main_Settings['player_height'] !== '') {
        $height = esc_attr($aiomatic_Main_Settings['player_height']);
    }
    else
    {
        $height = 380;
    }
    if($chance != '' && is_numeric($chance))
    {
        $chance = intval($chance);
        if(mt_rand(0, 99) >= $chance)
        {
            return '';
        }
    }
    $res = aiomatic_file_get_contents_advanced('https://www.youtube.com/results?search_query=' . urlencode($keyword), '', 'self', 'Mozilla/5.0 (Windows NT 10.0;WOW64;rv:97.0) Gecko/20100101 Firefox/97.0/3871tuT2p1u-81');
    preg_match_all('/"\/watch\?v=([^"&?\/\s]{11})"/', $res, $matches);
    if(isset($matches[1]))
    {
        $items = $matches[1];
        if (count($items) > 0) 
        {
            return '<br/><br/><div class="automaticx-video-container"><iframe allow="autoplay" width="' . $width . '" height="' . $height . '" src="https://www.youtube.com/embed/' . $items[rand(0, count($items) - 1)] . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
        }
    }
    return '';
}
function aiomatic_generate_thumbmail( $post_id )
{    
    $post = get_post($post_id);
    $post_parent_id = $post->post_parent === 0 ? $post->ID : $post->post_parent;
    if ( has_post_thumbnail($post_parent_id) )
    {
        if ($id_attachment = get_post_thumbnail_id($post_parent_id)) {
            $the_image  = wp_get_attachment_url($id_attachment, false);
            return $the_image;
        }
    }
    $attachments = array_values(get_children(array(
        'post_parent' => $post_parent_id, 
        'post_status' => 'inherit', 
        'post_type' => 'attachment', 
        'post_mime_type' => 'image', 
        'order' => 'ASC', 
        'orderby' => 'menu_order ID') 
    ));
    if( sizeof($attachments) > 0 ) {
        $the_image  = wp_get_attachment_url($attachments[0]->ID, false);
        return $the_image;
    }
    $image_url = aiomatic_extractThumbnail($post->post_content);
    return $image_url;
}
function aiomatic_extractThumbnail($content) {
    $att = aiomatic_getUrls($content);
    if(count($att) > 0)
    {
        foreach($att as $link)
        {
            $mime = aiomatic_get_mime($link);
            if(stristr($mime, "image/") !== FALSE){
                return $link;
            }
        }
    }
    else
    {
        return '';
    }
    return '';
}
function aiomatic_getUrls($string) {
    $regex = '/https?\:\/\/[^\"\' \n\s]+/i';
    preg_match_all($regex, $string, $matches);
    return ($matches[0]);
}
function aiomatic_get_mime ($filename) {
    $mime_types = array(
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'mts' => 'video/mp2t',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'wmv' => 'video/x-ms-wmv',
        'mp4' => 'video/mp4',
        'm4p' => 'video/m4p',
        'm4v' => 'video/m4v',
        'mpg' => 'video/mpg',
        'mp2' => 'video/mp2',
        'mpe' => 'video/mpe',
        'mpv' => 'video/mpv',
        'm2v' => 'video/m2v',
        'm4v' => 'video/m4v',
        '3g2' => 'video/3g2',
        '3gpp' => 'video/3gpp',
        'f4v' => 'video/f4v',
        'f4p' => 'video/f4p',
        'f4a' => 'video/f4a',
        'f4b' => 'video/f4b',
        '3gp' => 'video/3gp',
        'avi' => 'video/x-msvideo',
        'mpeg' => 'video/mpeg',
        'mpegps' => 'video/mpeg',
        'webm' => 'video/webm',
        'mpeg4' => 'video/mp4',
        'mkv' => 'video/mkv',
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );
    $ext = array_values(array_slice(explode('.', $filename), -1));$ext = $ext[0];

    if(stristr($filename, 'dailymotion.com'))
    {
        return 'application/octet-stream';
    }
    if (function_exists('mime_content_type')) {
        error_reporting(0);
        $mimetype = mime_content_type($filename);
        error_reporting(E_ALL);
        if($mimetype == '')
        {
            if (array_key_exists($ext, $mime_types)) {
                return $mime_types[$ext];
            } else {
                return 'application/octet-stream';
            }
        }
        return $mimetype;
    }
    elseif (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);
        if($mimetype === false)
        {
            if (array_key_exists($ext, $mime_types)) {
                return $mime_types[$ext];
            } else {
                return 'application/octet-stream';
            }
        }
        return $mimetype;

    } elseif (array_key_exists($ext, $mime_types)) {
        return $mime_types[$ext];
    } else {
        return 'application/octet-stream';
    }
}

function aiomatic_spin_text($title, $content, $alt = false)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    $titleSeparator         = '[19459000]';
    $text                   = $title . ' ' . $titleSeparator . ' ' . $content;
    $text                   = html_entity_decode($text);
    preg_match_all("/<[^<>]+>/is", $text, $matches, PREG_PATTERN_ORDER);
    $htmlfounds         = array_filter(array_unique($matches[0]));
    $htmlfounds[]       = '&quot;';
    $imgFoundsSeparated = array();
    foreach ($htmlfounds as $key => $currentFound) {
        if (stristr($currentFound, '<img') && stristr($currentFound, 'alt')) {
            $altSeparator   = '';
            $colonSeparator = '';
            if (stristr($currentFound, 'alt="')) {
                $altSeparator   = 'alt="';
                $colonSeparator = '"';
            } elseif (stristr($currentFound, 'alt = "')) {
                $altSeparator   = 'alt = "';
                $colonSeparator = '"';
            } elseif (stristr($currentFound, 'alt ="')) {
                $altSeparator   = 'alt ="';
                $colonSeparator = '"';
            } elseif (stristr($currentFound, 'alt= "')) {
                $altSeparator   = 'alt= "';
                $colonSeparator = '"';
            } elseif (stristr($currentFound, 'alt=\'')) {
                $altSeparator   = 'alt=\'';
                $colonSeparator = '\'';
            } elseif (stristr($currentFound, 'alt = \'')) {
                $altSeparator   = 'alt = \'';
                $colonSeparator = '\'';
            } elseif (stristr($currentFound, 'alt= \'')) {
                $altSeparator   = 'alt= \'';
                $colonSeparator = '\'';
            } elseif (stristr($currentFound, 'alt =\'')) {
                $altSeparator   = 'alt =\'';
                $colonSeparator = '\'';
            }
            if (trim($altSeparator) != '') {
                $currentFoundParts = explode($altSeparator, $currentFound);
                $preAlt            = $currentFoundParts[1];
                $preAltParts       = explode($colonSeparator, $preAlt);
                $altText           = $preAltParts[0];
                if (trim($altText) != '') {
                    unset($preAltParts[0]);
                    $imgFoundsSeparated[] = $currentFoundParts[0] . $altSeparator;
                    $imgFoundsSeparated[] = $colonSeparator . implode('', $preAltParts);
                    $htmlfounds[$key]     = '';
                }
            }
        }
    }
    if (count($imgFoundsSeparated) != 0) {
        $htmlfounds = array_merge($htmlfounds, $imgFoundsSeparated);
    }
    preg_match_all("/<\!--.*?-->/is", $text, $matches2, PREG_PATTERN_ORDER);
    $newhtmlfounds = $matches2[0];
    preg_match_all("/\[.*?\]/is", $text, $matches3, PREG_PATTERN_ORDER);
    $shortcodesfounds = $matches3[0];
    $htmlfounds       = array_merge($htmlfounds, $newhtmlfounds, $shortcodesfounds);
    $in               = 0;
    $cleanHtmlFounds  = array();
    foreach ($htmlfounds as $htmlfound) {
        if ($htmlfound == '[19459000]') {
        } elseif (trim($htmlfound) == '') {
        } else {
            $cleanHtmlFounds[] = $htmlfound;
        }
    }
    $htmlfounds = $cleanHtmlFounds;
    $start      = 19459001;
    foreach ($htmlfounds as $htmlfound) {
        $text = str_replace($htmlfound, '[' . $start . ']', $text);
        $start++;
    }
    try {
        require_once(dirname(__FILE__) . "/res/aiomatic-text-spinner.php");
        $phpTextSpinner = new PhpTextSpinner();
        if ($alt === FALSE) {
            $spinContent = $phpTextSpinner->spinContent($text);
        } else {
            $spinContent = $phpTextSpinner->spinContentAlt($text);
        }
        $translated = $phpTextSpinner->runTextSpinner($spinContent);
    }
    catch (Exception $e) {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('Exception thrown in spinText ' . $e);
        }
        return false;
    }
    preg_match_all('{\[.*?\]}', $translated, $brackets);
    $brackets = $brackets[0];
    $brackets = array_unique($brackets);
    foreach ($brackets as $bracket) {
        if (stristr($bracket, '19')) {
            $corrrect_bracket = str_replace(' ', '', $bracket);
            $corrrect_bracket = str_replace('.', '', $corrrect_bracket);
            $corrrect_bracket = str_replace(',', '', $corrrect_bracket);
            $translated       = str_replace($bracket, $corrrect_bracket, $translated);
        }
    }
    if (stristr($translated, $titleSeparator)) {
        $start = 19459001;
        foreach ($htmlfounds as $htmlfound) {
            $translated = str_replace('[' . $start . ']', $htmlfound, $translated);
            $start++;
        }
        $contents = explode($titleSeparator, $translated);
        $title    = $contents[0];
        $content  = $contents[1];
    } else {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('Failed to parse spinned content, separator not found');
        }
        return false;
    }
    return array(
        $title,
        $content
    );
}


function aiomatic_best_spin_text($title, $content)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['best_user']) || $aiomatic_Main_Settings['best_user'] == '' || !isset($aiomatic_Main_Settings['best_password']) || $aiomatic_Main_Settings['best_password'] == '') {
        aiomatic_log_to_file('Please insert a valid "The Best Spinner" user name and password.');
        return FALSE;
    }
    $titleSeparator   = '[19459000]';
    $newhtml             = $title . ' ' . $titleSeparator . ' ' . $content;
    $url              = 'http://thebestspinner.com/api.php';
    $data             = array();
    $data['action']   = 'authenticate';
    $data['format']   = 'php';
    $data['username'] = $aiomatic_Main_Settings['best_user'];
    $data['password'] = $aiomatic_Main_Settings['best_password'];
    $ch               = curl_init();
    if ($ch === FALSE) {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('Failed to init curl!');
        }
        return FALSE;
    }
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    $fdata = "";
    foreach ($data as $key => $val) {
        $fdata .= "$key=" . urlencode($val) . "&";
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    $html = curl_exec($ch);
    curl_close($ch);
    if ($html === FALSE) {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('"The Best Spinner" failed to exec curl.');
        }
        return FALSE;
    }
    $output = unserialize($html);
    if ($output['success'] == 'true') {
        $session                = $output['session'];
        $data                   = array();
        $data['session']        = $session;
        $data['format']         = 'php';
        $data['protectedterms'] = '';
        $data['action']         = 'replaceEveryonesFavorites';
        $data['maxsyns']        = '100';
        $data['quality']        = '1';
        $ch = curl_init();
        if ($ch === FALSE) {
            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                aiomatic_log_to_file('Failed to init curl');
            }
            return FALSE;
        }
        $newhtml = html_entity_decode($newhtml);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        $spinned = '';
        if(str_word_count($newhtml) > 4000)
        {
            while($newhtml != '')
            {
                $first30k = substr($newhtml, 0, 30000);
                $first30k = rtrim($first30k, '(*');
                $first30k = ltrim($first30k, ')*');
                $newhtml = substr($newhtml, 30000);
                $data['text']           = $first30k;
                $fdata = "";
                foreach ($data as $key => $val) {
                    $fdata .= "$key=" . urlencode($val) . "&";
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
                $output = curl_exec($ch);
                if ($output === FALSE) {
                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                        aiomatic_log_to_file('"The Best Spinner" failed to exec curl after auth.');
                    }
                    return FALSE;
                }
                $output = unserialize($output);
                if ($output['success'] == 'true') {
                    $spinned .= ' ' . $output['output'];
                } else {
                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                        aiomatic_log_to_file('"The Best Spinner" failed to spin article.');
                    }
                    return FALSE;
                }
            }
        }
        else
        {
            $data['text'] = $newhtml;
            $fdata = "";
            foreach ($data as $key => $val) {
                $fdata .= "$key=" . urlencode($val) . "&";
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
            $output = curl_exec($ch);
            if ($output === FALSE) {
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                    aiomatic_log_to_file('"The Best Spinner" failed to exec curl after auth.');
                }
                return FALSE;
            }
            $output = unserialize($output);
            if ($output['success'] == 'true') {
                $spinned = $output['output'];
            } else {
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                    aiomatic_log_to_file('"The Best Spinner" failed to spin article: ' . print_r($output, true));
                }
                return FALSE;
            }
        }
        curl_close($ch);
        $result = explode($titleSeparator, $spinned);
        if (count($result) < 2) {
            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                aiomatic_log_to_file('"The Best Spinner" failed to spin article - titleseparator not found.' . print_r($output, true));
            }
            return FALSE;
        }
        $spintax = new AIomatic_Spintax();
        $result[0] = $spintax->Parse($result[0]);
        $result[1] = $spintax->Parse($result[1]);
        return $result;

    } else {
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('"The Best Spinner" authentification failed.');
        }
        return FALSE;
    }
}
class AIomatic_Spintax {
    static $countBlocks = 0;
    static $blocks = [];
    public static function Parse($text, $count = [])
    {
        if (strpos($text, '#block#') !== false) {
            $text = stripslashes(preg_replace_callback('|#block#(.*?)#/block#|si', ['Aiomatic_Spintax', 'replaceBlock'], $text));
            $newBlocks = self::$blocks;
            shuffle($newBlocks);
            $count_from = $count_to = 0;
            if (!empty($count)) {
                $count_from = (int) $count[0] > 0 ? (int) $count[0] : 1;
                $count_to = ((int) $count[1] == 0 || (int) $count[1] > count($newBlocks)) ? count($newBlocks) : (int) $count[1];
            }
            $cntBlocks = rand($count_from, $count_to);
            $cntBlocks = ($cntBlocks == 0 || $cntBlocks > count($newBlocks)) ? count($newBlocks) : $cntBlocks;
            for ($i = 0; $i < $cntBlocks; $i++) {
                $p = implode("</p><p>", $newBlocks[$i]);
                $p = str_replace('<br />', '', $p);
                $p = '<p>' . $p . '</p>';
                $text = str_replace('{#block' . ($i + 1) . '#}', $p, $text);
            }
            $text = stripslashes(preg_replace('|{#block.*?#}|si', '', $text));
            self::$countBlocks = 0;
            self::$blocks = array();
        }
        $text = str_replace('</p><br />', '</p>', $text);
        $final = preg_replace('#(<br \/>\n*)+$#', '', self::process($text));
        return $final;
    }
    public static function replaceBlock($text)
    {
        if (!empty($text[1])) {
            preg_match_all('|#p#(.*?)#/p#|si', $text[1], $matches);
            if (!empty($matches[1])) {
                $p = $matches[1];
                shuffle($p);
                foreach ($p AS $key => $val) {
                    if (empty($val)) continue;
                    $test = explode('#s#', $val);
                    $index = array_rand($test, 1);
                    $test = $test[$index];
                    $test = explode("\n", $test);
                    shuffle($test);
                    $text = implode("</p><p>", $test);
                    $text = '<p>'. $text . '</p>';
                    self::$blocks[self::$countBlocks][] = $text;
                }
            } else {
                self::$blocks[self::$countBlocks][] = trim($text[1]);
            }
        }
        self::$countBlocks++;
        return '{#block' . self::$countBlocks . '#}';
    }
    public static function process($text)
    {
        $pattern = '/\{(((?>[^\{\}]+)|(?R))*)\}/x';
        return preg_replace_callback($pattern, ['Aiomatic_Spintax', 'replace'], $text);
    }
    public static function replace($text)
    {
        $text = self::process($text[1]);
        $parts = explode('|', $text);
        return $parts[array_rand($parts)];
    }
}
function aiomatic_replaceExcludes($text, &$htmlfounds, &$pre_tags_matches, &$pre_tags_matches_s, &$conseqMatchs)
{
    preg_match_all ( '{<script.*?script>}s', $text, $script_matchs );
    $script_matchs = $script_matchs [0];
    preg_match_all ( '{<pre.*?/pre>}s', $text, $pre_matchs );
    $pre_matchs = $pre_matchs [0];
    preg_match_all ( '{<code.*?/code>}s', $text, $code_matchs );
    $code_matchs = $code_matchs [0];
    preg_match_all ( "/<[^<>]+>/is", $text, $matches, PREG_PATTERN_ORDER );
    $htmlfounds = array_filter ( array_unique ( $matches [0] ) );
    $htmlfounds = array_merge ( $script_matchs, $pre_matchs, $code_matchs, $htmlfounds );
    $htmlfounds [] = '&quot;';
    $imgFoundsSeparated = array ();
    $new_imgFoundsSeparated = array ();
    $altSeparator = '';
    $colonSeparator = '';
    foreach ( $htmlfounds as $key => $currentFound ) 
    {
        if (stristr ( $currentFound, '<img' ) && stristr ( $currentFound, 'alt' ) && ! stristr ( $currentFound, 'alt=""' )) 
        {
            $altSeparator = '';
            $colonSeparator = '';
            if (stristr ( $currentFound, 'alt="' )) {
                $altSeparator = 'alt="';
                $colonSeparator = '"';
            } elseif (stristr ( $currentFound, 'alt = "' )) {
                $altSeparator = 'alt = "';
                $colonSeparator = '"';
            } elseif (stristr ( $currentFound, 'alt ="' )) {
                $altSeparator = 'alt ="';
                $colonSeparator = '"';
            } elseif (stristr ( $currentFound, 'alt= "' )) {
                $altSeparator = 'alt= "';
                $colonSeparator = '"';
            } elseif (stristr ( $currentFound, 'alt=\'' )) {
                $altSeparator = 'alt=\'';
                $colonSeparator = '\'';
            } elseif (stristr ( $currentFound, 'alt = \'' )) {
                $altSeparator = 'alt = \'';
                $colonSeparator = '\'';
            } elseif (stristr ( $currentFound, 'alt= \'' )) {
                $altSeparator = 'alt= \'';
                $colonSeparator = '\'';
            } elseif (stristr ( $currentFound, 'alt =\'' )) {
                $altSeparator = 'alt =\'';
                $colonSeparator = '\'';
            }
            if (trim ( $altSeparator ) != '') 
            {
                $currentFoundParts = explode ( $altSeparator, $currentFound );
                $preAlt = $currentFoundParts [1];
                $preAltParts = explode ( $colonSeparator, $preAlt );
                $altText = $preAltParts [0];
                if (trim ( $altText ) != '') 
                {
                    unset ( $preAltParts [0] );
                    $past_alt_text = implode ( $colonSeparator, $preAltParts );
                    $imgFoundsSeparated [] = $currentFoundParts [0] . $altSeparator;
                    $imgFoundsSeparated [] = $colonSeparator . $past_alt_text;
                    $htmlfounds [$key] = '';
                }
            }
        }
    }
    $title_separator = str_replace ( 'alt', 'title', $altSeparator );
    if($title_separator == '')
    {
        $title_separator = 'title';
    }
    foreach ( $imgFoundsSeparated as $img_part ) 
    {
        if (stristr ( $img_part, ' title' )) 
        {
            $img_part_parts = explode ( $title_separator, $img_part );
            $pre_title_part = $img_part_parts [0] . $title_separator;
            $post_title_parts = explode ( $colonSeparator, $img_part_parts [1] );
            $found_title = $post_title_parts [0];
            unset ( $post_title_parts [0] );
            $past_title_text = implode ( $colonSeparator, $post_title_parts );
            $post_title_part = $colonSeparator . $past_title_text;
            $new_imgFoundsSeparated [] = $pre_title_part;
            $new_imgFoundsSeparated [] = $post_title_part;
        } else {
            $new_imgFoundsSeparated [] = $img_part;
        }
    }
    if (count ( $new_imgFoundsSeparated ) != 0) {
        $htmlfounds = array_merge ( $htmlfounds, $new_imgFoundsSeparated );
    }
    preg_match_all ( "/<\!--.*?-->/is", $text, $matches2, PREG_PATTERN_ORDER );
    $newhtmlfounds = $matches2 [0];
    preg_match_all ( "/\[.*?\]/is", $text, $matches3, PREG_PATTERN_ORDER );
    $shortcodesfounds = $matches3 [0];
    $htmlfounds = array_merge ( $htmlfounds, $newhtmlfounds, $shortcodesfounds );
    $in = 0;
    $cleanHtmlFounds = array ();
    foreach ( $htmlfounds as $htmlfound ) {
        
        if ($htmlfound == '[19459000]') {
        } elseif (trim ( $htmlfound ) == '') {
        } else {
            $cleanHtmlFounds [] = $htmlfound;
        }
    }
    $htmlfounds = array_filter ( $cleanHtmlFounds );
    $start = 19459001;
    foreach ( $htmlfounds as $htmlfound ) {
        $text = str_replace ( $htmlfound, '[' . $start . ']', $text );
        $start ++;
    }
    $text = str_replace ( '.{', '. {', $text );
    preg_match_all ( '!(?:\[1945\d*\][\s]*){2,}!s', $text, $conseqMatchs );
    $startConseq = 19659001;
    foreach ( $conseqMatchs [0] as $conseqMatch ) {
        $text = preg_replace ( '{' . preg_quote ( trim ( $conseqMatch ) ) . '}', '[' . $startConseq . ']', $text, 1 );
        $startConseq ++;
    }
    preg_match_all ( '{\[.*?\]}', $text, $pre_tags_matches );
    $pre_tags_matches = ($pre_tags_matches [0]);
    preg_match_all ( '{\s*\[.*?\]\s*}u', $text, $pre_tags_matches_s );
    $pre_tags_matches_s = ($pre_tags_matches_s [0]);
    $text = str_replace ( '[', "\n\n[", $text );
    $text = str_replace ( ']', "]\n\n", $text );
	return $text;	
}
function aiomatic_countExcludes($translated)
{
    preg_match_all ( '{\[.*?\]}', $translated, $bracket_matchs );
    $bracket_matchs = $bracket_matchs[0];
    return count($bracket_matchs);
}
function aiomatic_restoreExcludes($translated, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs){
    $translated = preg_replace ( '{]\s*?1945}', '][1945', $translated );
    $translated = preg_replace ( '{ 19459(\d*?)]}', ' [19459$1]', $translated );
    $translated = str_replace ( '[ [1945', '[1945', $translated );
    $translated = str_replace ( '], ', ']', $translated );
    preg_match_all ( '{\[.*?\]}', $translated, $bracket_matchs );
    $bracket_matchs = $bracket_matchs [0];
    foreach ( $bracket_matchs as $single_bracket ) 
    {
        if (stristr ( $single_bracket, '1' ) && stristr ( $single_bracket, '9' )) {
            $single_bracket_clean = str_replace ( array (
                    ',',
                    ' ' 
            ), '', $single_bracket );
            $translated = str_replace ( $single_bracket, $single_bracket_clean, $translated );
        }
    }
    preg_match_all ( '{\[\d*?\]}', $translated, $post_tags_matches );
    $post_tags_matches = ($post_tags_matches [0]);
    if (count ( $pre_tags_matches ) == count ( $post_tags_matches )) 
    {
        if ($pre_tags_matches !== $post_tags_matches) 
        {
            $i = 0;
            foreach ( $post_tags_matches as $post_tags_match ) {
                $translated = preg_replace ( '{' . preg_quote ( trim ( $post_tags_match ) ) . '}', '[' . $i . ']', $translated, 1 );
                $i ++;
            }
            $i = 0;
            foreach ( $pre_tags_matches as $pre_tags_match ) {
                $translated = str_replace ( '[' . $i . ']', $pre_tags_match, $translated );
                $i ++;
            }
        }
    }
    $translated = str_replace ( "\n\n[", '[', $translated );
    $translated = str_replace ( "]\n\n", ']', $translated );
    $i = 0;
    foreach ( $pre_tags_matches_s as $pre_tags_match ) 
    {
        $pre_tags_match_h = htmlentities ( $pre_tags_match );
        if (stristr ( $pre_tags_match_h, '&nbsp;' )) {
            $pre_tags_match = str_replace ( '&nbsp;', ' ', $pre_tags_match_h );
        }
        $translated = preg_replace ( '{' . preg_quote ( trim ( $pre_tags_match ) ) . '}', "[$i]", $translated, 1 );
        $i ++;
    }
    $translated = preg_replace ( '{\s*\[}u', '[', $translated );
    $translated = preg_replace ( '{\]\s*}u', ']', $translated );
    $i = 0;
    foreach ( $pre_tags_matches_s as $pre_tags_match ) 
    {
        $pre_tags_match_h = htmlentities ( $pre_tags_match );
        if (stristr ( $pre_tags_match_h, '&nbsp;' )) {
            $pre_tags_match = str_replace ( '&nbsp;', ' ', $pre_tags_match_h );
        }
        $translated = preg_replace ( '{' . preg_quote ( "[$i]" ) . '}', $pre_tags_match, $translated, 1 );
        $i ++;
    }
    $startConseq = 19659001;
    foreach ( $conseqMatchs [0] as $conseqMatch ) {
        $translated = str_replace ( '[' . $startConseq . ']', $conseqMatch, $translated );
        $startConseq ++;
    }
    preg_match_all ( '!\[.*?\]!', $translated, $brackets );
    $brackets = $brackets [0];
    $brackets = array_unique ( $brackets );
    foreach ( $brackets as $bracket ) {
        if (stristr ( $bracket, '19' )) 
        {
            $corrrect_bracket = str_replace ( ' ', '', $bracket );
            $corrrect_bracket = str_replace ( '.', '', $corrrect_bracket );
            $corrrect_bracket = str_replace ( ',', '', $corrrect_bracket );
            $translated = str_replace ( $bracket, $corrrect_bracket, $translated );
        }
    }
    $start = 19459001;
    foreach ( $htmlfounds as $htmlfound ) {
        $translated = str_replace ( '[' . $start . ']', $htmlfound, $translated );
        $start ++;
    }
    return $translated;
}
function aiomatic_replaceAIExecludes($article, &$htmlfounds, $opt = false, $dymmy_char = '-')
{
    $htmlurls = array();$article = preg_replace('{data-image-description="(?:[^\"]*?)"}i', '', $article);
	if($opt === true){
		preg_match_all( "/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*?)<\/a>/s" ,$article,$matches,PREG_PATTERN_ORDER);
		$htmlurls=$matches[0];
	}
	$urls_txt = array();
	if($opt === true){
		preg_match_all('/https?:\/\/[^<\s]+/', $article,$matches_urls_txt);
		$urls_txt = $matches_urls_txt[0];
	}
	preg_match_all("/<[^<>]+>/is",$article,$matches,PREG_PATTERN_ORDER);
	$htmlfounds=$matches[0];
	preg_match_all('{\[nospin\].*?\[/nospin\]}s', $article,$matches_ns);
	$nospin = $matches_ns[0];
	//$pattern="\[.*?\]";
	//preg_match_all("/".$pattern."/s",$article,$matches2,PREG_PATTERN_ORDER);
	//$shortcodes=$matches2[0];
    $shortcodes=array();
	preg_match_all("/<script.*?<\/script>/is",$article,$matches3,PREG_PATTERN_ORDER);
	$js=$matches3[0];
	preg_match_all('/\d{2,}/s', $article,$matches_nums);
	$nospin_nums = $matches_nums[0];
	sort($nospin_nums);
	$nospin_nums = array_reverse($nospin_nums);
	$capped = array();
	if($opt === true){
		preg_match_all("{\b[A-Z][a-z']+\b[,]?}", $article,$matches_cap);
		$capped = $matches_cap[0];
		sort($capped);
		$capped=array_reverse($capped);
	}
	$curly_quote = array();
	if($opt === true){
		preg_match_all('{???.*????}', $article, $matches_curly_txt);
		$curly_quote = $matches_curly_txt[0];
		preg_match_all('{???.*????}', $article, $matches_curly_txt_s);
		$single_curly_quote = $matches_curly_txt_s[0];
		preg_match_all('{&quot;.*?&quot;}', $article, $matches_curly_txt_s_and);
		$single_curly_quote_and = $matches_curly_txt_s_and[0];
		preg_match_all('{&#8220;.*?&#8221}', $article, $matches_curly_txt_s_and_num);
		$single_curly_quote_and_num = $matches_curly_txt_s_and_num[0];
		$curly_quote_regular = array();
		preg_match_all('{".*?"}', $article, $matches_curly_txt_regular);
        $curly_quote_regular = $matches_curly_txt_regular[0];
		$curly_quote = array_merge($curly_quote , $single_curly_quote ,$single_curly_quote_and,$single_curly_quote_and_num,$curly_quote_regular);
	}
	$htmlfounds = array_merge($nospin, $shortcodes, $js, $htmlurls, $htmlfounds, $curly_quote, $urls_txt, $nospin_nums, $capped);
	$htmlfounds = array_filter(array_unique($htmlfounds));
	$i=1;
	foreach($htmlfounds as $htmlfound){
		$article = str_replace($htmlfound, '(' . $dymmy_char . $i . $dymmy_char . ')', $article);	
		$i++;
	}
    $article = str_replace(':(' . $dymmy_char, ': (' . $dymmy_char, $article);
	return $article;
}
function aiomatic_restoreAIExecludes($article, $htmlfounds, $dymmy_char = 'x'){
	$i=1;
	foreach($htmlfounds as $htmlfound){
		$article=str_replace( '(' . $dymmy_char . $i . $dymmy_char . ')', $htmlfound, $article);
		$i++;
	}
	$article = str_replace(array('[nospin]','[/nospin]'), '', $article);
	return $article;
}
function aiomatic_fix_spinned_content($final_content, $spinner)
{
    if ($spinner == 'wordai') {
        $final_content = str_replace('-LRB-', '(', $final_content);
        $final_content1 = preg_replace("/{\*\|.*?}/", '*', $final_content);
        if($final_content1 !== null)
        {
            $final_content = $final_content1;
        }
    }
    elseif ($spinner == 'spinrewriter' || $spinner == 'translate') {
        $final_content = str_replace('& #', '&#', $final_content);
        $final_content = preg_replace('#&\s([a-zA-Z]+?);#', '', $final_content);
    }
    return $final_content;
}
function aiomatic_spin_and_translate($post_title, $final_content, $methodtouse = '1', $skip_spin = '0', $skip_translate = '0')
{
    $translation = false;
    $pre_tags_matches = array();
    $pre_tags_matches_s = array();
    $conseqMatchs = array();
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if($skip_spin != '1')
    {
        if($methodtouse == '1' || $methodtouse == '3')
        {
            if (isset($aiomatic_Main_Settings['spin_text']) && $aiomatic_Main_Settings['spin_text'] !== 'disabled') {
                
                $htmlfounds = array();
                $final_content = aiomatic_replaceExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                if ($aiomatic_Main_Settings['spin_text'] == 'builtin') {
                    $translation = aiomatic_builtin_spin_text($post_title, $final_content);
                } elseif ($aiomatic_Main_Settings['spin_text'] == 'wikisynonyms') {
                    $translation = aiomatic_spin_text($post_title, $final_content, false);
                } elseif ($aiomatic_Main_Settings['spin_text'] == 'freethesaurus') {
                    $translation = aiomatic_spin_text($post_title, $final_content, true);
                } elseif ($aiomatic_Main_Settings['spin_text'] == 'best') {
                    $translation = aiomatic_best_spin_text($post_title, $final_content);
                } elseif ($aiomatic_Main_Settings['spin_text'] == 'wordai') {
                    $translation = aiomatic_wordai_spin_text($post_title, $final_content);
                } elseif ($aiomatic_Main_Settings['spin_text'] == 'spinrewriter') {
                    $translation = aiomatic_spinrewriter_spin_text($post_title, $final_content);
                } elseif ($aiomatic_Main_Settings['spin_text'] == 'spinnerchief') {
                    $translation = aiomatic_spinnerchief_spin_text($post_title, $final_content);
                } elseif ($aiomatic_Main_Settings['spin_text'] == 'chimprewriter') {
                    $translation = aiomatic_chimprewriter_spin_text($post_title, $final_content);
                } elseif ($aiomatic_Main_Settings['spin_text'] == 'contentprofessor') {
                    $translation = aiomatic_contentprofessor_spin_text($post_title, $final_content);
                }
                if ($translation !== FALSE) {
                    if (is_array($translation) && isset($translation[0]) && isset($translation[1])) {
                        if (!isset($aiomatic_Main_Settings['no_title']) || $aiomatic_Main_Settings['no_title'] != 'on') {
                            $final_content = $translation[1];
                        }
                        $post_title    = $translation[0];
                        
                        $final_content = aiomatic_fix_spinned_content($final_content, $aiomatic_Main_Settings['spin_text']);
                        $final_content = aiomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                        
                    } else {
                        $final_content = aiomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                            aiomatic_log_to_file('Text Spinning failed - malformed data ' . $aiomatic_Main_Settings['spin_text']);
                        }
                    }
                } else {
                    $final_content = aiomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                        aiomatic_log_to_file('Text Spinning Failed - returned false ' . $aiomatic_Main_Settings['spin_text']);
                    }
                }
            }
        }
    }
    if($skip_translate != '1')
    {
        if($methodtouse == '2' || $methodtouse == '3')
        {
            if (isset($aiomatic_Main_Settings['translate']) && $aiomatic_Main_Settings['translate'] != 'disabled') {
                if(isset($aiomatic_Main_Settings['translate_source']) && $aiomatic_Main_Settings['translate_source'] != 'disabled')
                {
                    $tr = $aiomatic_Main_Settings['translate_source'];
                }
                else
                {
                    $tr = 'auto';
                }
                $htmlfounds = array();
                $final_content = aiomatic_replaceExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                
                $translation = aiomatic_translate($post_title, $final_content, $tr, $aiomatic_Main_Settings['translate']);
                if (is_array($translation) && isset($translation[1]))
                {
                    $translation[1] = preg_replace('#(?<=[\*(])\s+(?=[\*)])#', '', $translation[1]);
                    $translation[1] = preg_replace('#([^(*\s]\s)\*+\)#', '$1', $translation[1]);
                    $translation[1] = preg_replace('#\(\*+([\s][^)*\s])#', '$1', $translation[1]);
                    $translation[1] = aiomatic_restoreExcludes($translation[1], $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                }
                else
                {
                    $final_content = aiomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                }
                if ($translation !== FALSE) {
                    if (is_array($translation) && isset($translation[0]) && isset($translation[1])) {
                        $post_title    = $translation[0];
                        $final_content = $translation[1];
                        $final_content = str_replace('</ iframe>', '</iframe>', $final_content);
                        if(stristr($final_content, '<head>') !== false)
                        {
                            $d = new DOMDocument;
                            $mock = new DOMDocument;
                            $internalErrors = libxml_use_internal_errors(true);
                            $d->loadHTML('<?xml encoding="utf-8" ?>' . $final_content);
                            libxml_use_internal_errors($internalErrors);
                            $body = $d->getElementsByTagName('body')->item(0);
                            foreach ($body->childNodes as $child)
                            {
                                $mock->appendChild($mock->importNode($child, true));
                            }
                            $new_post_content_temp = $mock->saveHTML();
                            if($new_post_content_temp !== '' && $new_post_content_temp !== false)
                            {
                                $new_post_content_temp = str_replace('<?xml encoding="utf-8" ?>', '', $new_post_content_temp);
                                $final_content = preg_replace("/_addload\(function\(\){([^<]*)/i", "", $new_post_content_temp); 
                            }
                        }
                        $final_content = htmlspecialchars_decode($final_content);
                        $final_content = str_replace('</ ', '</', $final_content);
                        $final_content = str_replace(' />', '/>', $final_content);
                        $final_content = str_replace('< br/>', '<br/>', $final_content);
                        $final_content = str_replace('< / ', '</', $final_content);
                        $final_content = str_replace(' / >', '/>', $final_content);
                        $final_content = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $final_content);
                        $post_title = preg_replace('{&\s*#\s*(\d+)\s*;}', '&#$1;', $post_title);
                        $post_title = htmlspecialchars_decode($post_title);
                        $post_title = str_replace('</ ', '</', $post_title);
                        $post_title = str_replace(' />', '/>', $post_title);
                        $post_title = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $post_title);
                    } else {
                        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                            aiomatic_log_to_file('Translation failed - malformed data!');
                        }
                    }
                } else {
                    if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                        aiomatic_log_to_file('Translation Failed - returned false!');
                    }
                }
            }
        }
    }
    return array(
        $post_title,
        $final_content
    );
}

function aiomatic_translate($title, $content, $from, $to)
{
    $ch                     = FALSE;
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    try {
        if($from == 'disabled')
        {
            $from = 'auto';
        }
        if($from != 'en' && $from == $to)
        {
            $from = 'en';
        }
        elseif($from == 'en' && $from == $to)
        {
            return false;
        }
        if (isset($aiomatic_Main_Settings['google_trans_auth']) && trim($aiomatic_Main_Settings['google_trans_auth']) != '')
        {
            require_once(dirname(__FILE__) . "/res/translator-api.php");
            $ch = curl_init();
            if ($ch === FALSE) {
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                    aiomatic_log_to_file('Failed to init cURL in translator!');
                }
                return false;
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $GoogleTranslatorAPI = new GoogleTranslatorAPI($ch, $aiomatic_Main_Settings['google_trans_auth']);
            $translated = '';
            $translated_title = '';
            if($content != '')
            {
                if(strlen($content) > 30000)
                {
                    while($content != '')
                    {
                        $first30k = substr($content, 0, 30000);
                        $content = substr($content, 30000);
                        $translated_temp       = $GoogleTranslatorAPI->translateText($first30k, $from, $to);
                        $translated .= ' ' . $translated_temp;
                    }
                }
                else
                {
                    $translated       = $GoogleTranslatorAPI->translateText($content, $from, $to);
                }
            }
            if($title != '')
            {
                $translated_title = $GoogleTranslatorAPI->translateText($title, $from, $to);
            }
            curl_close($ch);
        }
        else
        {
            require_once(dirname(__FILE__) . "/res/aiomatic-translator.php");
            $ch = curl_init();
            if ($ch === FALSE) {
                if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                    aiomatic_log_to_file('Failed to init cURL in translator!');
                }
                return false;
            }
            curl_setopt($ch, CURLOPT_USERAGENT, aiomatic_get_random_user_agent());
            if (isset($aiomatic_Main_Settings['proxy_url']) && $aiomatic_Main_Settings['proxy_url'] != '') {
                $prx = explode(',', $aiomatic_Main_Settings['proxy_url']);
                $randomness = array_rand($prx);
                curl_setopt( $ch, CURLOPT_PROXY, trim($prx[$randomness]));
                if (isset($aiomatic_Main_Settings['proxy_auth']) && $aiomatic_Main_Settings['proxy_auth'] != '') 
                {
                    $prx_auth = explode(',', $aiomatic_Main_Settings['proxy_auth']);
                    if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
                    {
                        curl_setopt( $ch, CURLOPT_PROXYUSERPWD, trim($prx_auth[$randomness]) );
                    }
                }
            }
            $GoogleTranslator = new GoogleTranslator($ch);
            $translated = '';
            $translated_title = '';
            if($content != '')
            {
                if(strlen($content) > 13000)
                {
                    while($content != '')
                    {
                        $first30k = substr($content, 0, 13000);
                        $content = substr($content, 13000);
                        $translated_temp       = $GoogleTranslator->translateText($first30k, $from, $to);
                        if (strpos($translated, '<h2>The page you have attempted to translate is already in ') !== false) {
                            throw new Exception('Page content already in ' . $to);
                        }
                        if (strpos($translated, 'Error 400 (Bad Request)!!1') !== false) {
                            throw new Exception('Unexpected error while translating page!');
                        }
                        if(substr_compare($translated_temp, '</pre>', -strlen('</pre>')) === 0){$translated_temp = substr_replace($translated_temp ,"", -6);}if(substr( $translated_temp, 0, 5 ) === "<pre>"){$translated_temp = substr($translated_temp, 5);}
                        $translated .= ' ' . $translated_temp;
                    }
                }
                else
                {
                    $translated       = $GoogleTranslator->translateText($content, $from, $to);
                    if (strpos($translated, '<h2>The page you have attempted to translate is already in ') !== false) {
                        throw new Exception('Page content already in ' . $to);
                    }
                    if (strpos($translated, 'Error 400 (Bad Request)!!1') !== false) {
                        throw new Exception('Unexpected error while translating page!');
                    }
                }
            }
            if($title != '')
            {
                $translated_title = $GoogleTranslator->translateText($title, $from, $to);
            }
            if (strpos($translated_title, '<h2>The page you have attempted to translate is already in ') !== false) {
                throw new Exception('Page title already in ' . $to);
            }
            if (strpos($translated_title, 'Error 400 (Bad Request)!!1') !== false) {
                throw new Exception('Unexpected error while translating page title!');
            }
            curl_close($ch);
        }
    }
    catch (Exception $e) {
        curl_close($ch);
        if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
            aiomatic_log_to_file('Exception thrown in GoogleTranslator ' . $e);
        }
        return false;
    }
    if(substr_compare($translated_title, '</pre>', -strlen('</pre>')) === 0){$title = substr_replace($translated_title ,"", -6);}else{$title = $translated_title;}if(substr( $title, 0, 5 ) === "<pre>"){$title = substr($title, 5);}
    if(substr_compare($translated, '</pre>', -strlen('</pre>')) === 0){$text = substr_replace($translated ,"", -6);}else{$text = $translated;}if(substr( $text, 0, 5 ) === "<pre>"){$text = substr($text, 5);}
    $text  = preg_replace('/' . preg_quote('html lang=') . '.*?' . preg_quote('>') . '/', '', $text);
    $text  = preg_replace('/' . preg_quote('!DOCTYPE') . '.*?' . preg_quote('<') . '/', '', $text);
    $text  = preg_replace('#https:\/\/translate\.google\.com\/translate\?hl=en&amp;prev=_t&amp;sl=en&amp;tl=pl&amp;u=([^><"\'\s\n]*)#i', urldecode('$1'), $text);
    return array(
        $title,
        $text
    );
}

function aiomatic_strip_html_tags($str)
{
    $str = html_entity_decode($str);
    $str1 = preg_replace('/(<|>)\1{2}/is', '', $str);
    if($str1 !== null)
    {
        $str = $str1;
    }
    $str1 = preg_replace(array(
        '@<head[^>]*?>.*?</head>@siu',
        '@<style[^>]*?>.*?</style>@siu',
        '@<script[^>]*?.*?</script>@siu',
        '@<noscript[^>]*?.*?</noscript>@siu'
    ), "", $str);
    if($str1 !== null)
    {
        $str = $str1;
    }
    $str = strip_tags($str);
    return $str;
}

register_activation_hook(__FILE__, 'aiomatic_check_version');
function aiomatic_check_version()
{
    if (!function_exists('curl_init')) {
        echo '<h3>'.esc_html__('Please enable curl PHP extension. Please contact your hosting provider\'s support to help you in this matter.', 'aiomatic-automatic-ai-content-writer').'</h3>';
        die;
    }
    global $wp_version;
    if (!current_user_can('activate_plugins')) {
        echo '<p>' . esc_html__('You are not allowed to activate plugins!', 'aiomatic-automatic-ai-content-writer') . '</p>';
        die;
    }
    $php_version_required = '5.0';
    $wp_version_required  = '2.7';
    
    if (version_compare(PHP_VERSION, $php_version_required, '<')) {
        deactivate_plugins(basename(__FILE__));
        echo '<p>' . sprintf(esc_html__('This plugin can not be activated because it requires a PHP version greater than %1$s. Please update your PHP version before you activate it.', 'aiomatic-automatic-ai-content-writer'), $php_version_required) . '</p>';
        die;
    }
    
    if (version_compare($wp_version, $wp_version_required, '<')) {
        deactivate_plugins(basename(__FILE__));
        echo '<p>' . sprintf(esc_html__('This plugin can not be activated because it requires a WordPress version greater than %1$s. Please go to Dashboard -> Updates to get the latest version of WordPress.', 'aiomatic-automatic-ai-content-writer'), $wp_version_required) . '</p>';
        die;
    }
}

function aiomatic_isSecure() {
    return
      (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || $_SERVER['SERVER_PORT'] == 443;
}

function aiomatic_base64_to_jpeg($base64_string, $output_file, $ret_path) 
{
    global $wp_filesystem;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
       wp_filesystem($creds);
    }
    if ($wp_filesystem->exists($output_file)) 
    {
        return array($output_file, $ret_path); 
    }
    $ifp = fopen($output_file, 'wb'); 
    if($ifp !== false)
    {
        $decoded = base64_decode($base64_string);
        if($ifp !== false)
        {
            $rez = fwrite($ifp, $decoded);
            if($rez === false)
            {
                aiomatic_log_to_file('Failed to write file: ' . $output_file);
                return false;
            }
        }
        else
        {
            aiomatic_log_to_file('Failed to decode response file: ' . $base64_string);
            return false;
        }
        fclose($ifp);
    }
    else
    {
        aiomatic_log_to_file('Failed to open file: ' . $output_file);
        return false;
    }
    return array($output_file, $ret_path); 
}
function aiomatic_generate_stability_image($text = '', $height = '512', $width = '512', $env = '', $retry_count = 0, $returnbase64 = false)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['stable_model']) || trim($aiomatic_Main_Settings['stable_model']) == '') 
    {
        $stable_model = 'stable-diffusion-512-v2-0';
    }
    else
    {
        $stable_model = trim($aiomatic_Main_Settings['stable_model']);
    }
    $appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['stability_app_id']));
    $appids = array_filter($appids);
    $token = $appids[array_rand($appids)];
    $aiomatic_Limit_Settings = get_option('aiomatic_Limit_Settings', false);
    $stop = null;
    $session = aiomatic_get_session_id();
    $mode = 'stable';
    $maxResults = 1;
    $available_tokens = 1000;
    $temperature = 1;
    $query = new Aiomatic_Query($text, $available_tokens, $stable_model, $temperature, $stop, $env, $mode, $token, $session, $maxResults, $width . 'x' . $height);
    $ok = apply_filters( 'aiomatic_ai_allowed', true, $aiomatic_Limit_Settings );
    if ( $ok !== true ) {
        return false;
    }
    if(strlen($text) > 2000)
    {
        $text = substr($text, 0, 2000);
    }
    if(isset($aiomatic_Main_Settings['enable_detailed_logging']) && $aiomatic_Main_Settings['enable_detailed_logging'] == 'on')
    {
        aiomatic_log_to_file('Generating Stability.AI Image using prompt: ' . $text . ' height: ' . $height . ' width: ' . $width);
    }
    if (!isset($aiomatic_Main_Settings['stability_app_id']) || trim($aiomatic_Main_Settings['stability_app_id']) == '') 
    {
        aiomatic_log_to_file('You need to enter a Stability.AI API key in the plugin\'s "Main Settings" menu to use this feature!');
        return false;
    }
    if(intval($height) < 512 || intval($height) > 2048)
    {
        aiomatic_log_to_file('Invalid height (512-2048): ' . $height);
        return false;
    }
    if(intval($width) < 512 || intval($width) > 2048)
    {
        aiomatic_log_to_file('Invalid width (512-2048): ' . $width);
        return false;
    }
    if(intval($width) * intval($height) > 1048576)
    {
        aiomatic_log_to_file('Width x Height must not be greater than 1 Megapixel (1048576), current is: ' . intval($width) * intval($height));
        return false;
    }
    if (!isset($aiomatic_Main_Settings['steps']) || trim($aiomatic_Main_Settings['steps']) == '') 
    {
        $steps = '50';
    }
    else
    {
        $steps = trim($aiomatic_Main_Settings['steps']);
    }
    if (!isset($aiomatic_Main_Settings['cfg_scale']) || trim($aiomatic_Main_Settings['cfg_scale']) == '') 
    {
        $cfg_scale = '7';
    }
    else
    {
        $cfg_scale = trim($aiomatic_Main_Settings['cfg_scale']);
    }
    if (!isset($aiomatic_Main_Settings['clip_guidance_preset']) || trim($aiomatic_Main_Settings['clip_guidance_preset']) == '') 
    {
        $clip_guidance_preset = 'NONE';
    }
    else
    {
        $clip_guidance_preset = trim($aiomatic_Main_Settings['clip_guidance_preset']);
    }
    if (!isset($aiomatic_Main_Settings['sampler']) || trim($aiomatic_Main_Settings['sampler']) == '') 
    {
        $sampler = 'auto';
    }
    else
    {
        $sampler = trim($aiomatic_Main_Settings['sampler']);
    }
    if(intval($steps) < 10 || intval($steps) > 250)
    {
        aiomatic_log_to_file('Invalid steps count provided (10-250): ' . intval($steps));
        return false;
    }
    if(intval($cfg_scale) < 0 || intval($cfg_scale) > 35)
    {
        aiomatic_log_to_file('Invalid cfg_scale count provided (0-35): ' . intval($cfg_scale));
        return false;
    }
    $api_url = 'https://api.stability.ai/v1alpha/generation/' . $stable_model . '/text-to-image';
    $ch = curl_init();
    if($ch === false)
    {
        if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
        {
            aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') Stability API call after initial failure: ' . print_r($api_url, true));
            sleep(1);
            return aiomatic_generate_stability_image($text, $height, $width, $env, intval($retry_count) + 1, $returnbase64);
        }
        else
        {
            aiomatic_log_to_file('Failed to create Stability curl request.');
            return false;
        }
    }
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'Authorization: ' . $token));
    $post_fields = '{"samples": 1,';
    if(trim($cfg_scale) != '' && trim($cfg_scale) != '7')
    {
        $post_fields .= '"cfg_scale": ' . trim($cfg_scale) . ',';
    }
    if(trim($clip_guidance_preset) != '' && trim($clip_guidance_preset) != 'NONE')
    {
        $post_fields .= '"clip_guidance_preset": ' . trim($clip_guidance_preset) . ',';
    }
    if(trim($height) != '' && trim($height) != '512')
    {
        $post_fields .= '"height": ' . trim($height) . ',';
    }
    if(trim($width) != '' && trim($width) != '512')
    {
        $post_fields .= '"width": ' . trim($width) . ',';
    }
    if(trim($steps) != '' && trim($steps) != '50')
    {
        $post_fields .= '"steps": ' . trim($steps) . ',';
    }
    if(trim($sampler) != '' && trim($sampler) != 'auto')
    {
        $post_fields .= '"sampler": ' . trim($sampler) . ',';
    }
    $post_fields .= '"text_prompts": [{"text": "' . str_replace('"', '\'', $text) . '","weight": 1}]}';
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $ai_response = curl_exec($ch);
    $info = curl_getinfo($ch);
    if($info['http_code'] != 200)
    {
        if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
        {
            aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') Stability API call after http_code failure: ' . print_r($api_url, true));
            sleep(1);
            return aiomatic_generate_stability_image($text, $height, $width, $env, intval($retry_count) + 1, $returnbase64);
        }
        else
        {
            $er = ' ';
            $json_resp = json_decode($ai_response, true);
            if($json_resp !== false)
            {
                $er .= 'Error: ' . $json_resp['name'] . ': ' . $json_resp['message'];
            }
            aiomatic_log_to_file('Invalid return code from API: ' . $info['http_code'] . $er);
            aiomatic_log_to_file('PostFields: ' . $post_fields);
            return false;
        }
    }
    curl_close($ch);
    if($ai_response === false)
    {
        if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
        {
            aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') Stability API call after response failure: ' . print_r($api_url, true));
            sleep(1);
            return aiomatic_generate_stability_image($text, $height, $width, $env, intval($retry_count) + 1, $returnbase64);
        }
        else
        {
            aiomatic_log_to_file('Failed to get AI response: ' . $api_url);
            return false;
        }
    }
    else
    {
        $json_resp = json_decode($ai_response, true);
        if($json_resp === false)
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') Stability API call after decode failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_generate_stability_image($text, $height, $width, $env, intval($retry_count) + 1, $returnbase64);
            }
            else
            {
                aiomatic_log_to_file('Failed to decode AI response: ' . $ai_response);
                return false;
            }
        }
        if(!isset($json_resp['artifacts'][0]['base64']))
        {
            if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
            {
                aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') Stability API call after response failure: ' . print_r($api_url, true));
                sleep(1);
                return aiomatic_generate_stability_image($text, $height, $width, $env, intval($retry_count) + 1, $returnbase64);
            }
            else
            {
                aiomatic_log_to_file('Invalid AI response: ' . $ai_response);
                return false;
            }
        }
        $seed = rand();
        if(isset($json_resp['artifacts'][0]['seed']))
        {
            $seed = $json_resp['artifacts'][0]['seed'];
        }
        $upload_dir = wp_upload_dir();
        $filename = $seed . '.png';
        if (wp_mkdir_p($upload_dir['path'] . '/localimages'))
        {
            $file = $upload_dir['path'] . '/localimages/' . $filename;
            $ret_path = $upload_dir['url'] . '/localimages/' . $filename;
        }
        else
        {
            $file = $upload_dir['basedir'] . '/' . $filename;
            $ret_path = $upload_dir['baseurl'] . '/' . $filename;
        }
        $reason = ''; 
        if(isset($json_resp['artifacts'][0]['finishReason']))
        {
            $reason = $json_resp['artifacts'][0]['finishReason'];
            if($reason == 'ERROR')
            {
                if (isset($aiomatic_Main_Settings['max_retry']) && $aiomatic_Main_Settings['max_retry'] != '' && is_numeric($aiomatic_Main_Settings['max_retry']) && intval($aiomatic_Main_Settings['max_retry']) > $retry_count)
                {
                    aiomatic_log_to_file('Retrying (' . intval($retry_count) + 1 . ') Stability API call after error failure: ' . print_r($api_url, true));
                    sleep(1);
                    return aiomatic_generate_stability_image($text, $height, $width, $env, intval($retry_count) + 1, $returnbase64);
                }
                else
                {
                    aiomatic_log_to_file('An error was encountered during API call: ' . $ai_response);
                    return false;
                }
            }
            elseif($reason == 'CONTENT_FILTERED')
            {
                aiomatic_log_to_file('The image was filtered, by the nudity filter, blurred parts may appear in it, prompt: ' . $ret_path);
            }
        }
        $img = $json_resp['artifacts'][0]['base64'];
        $img = apply_filters( 'aiomatic_ai_reply', $img, $query );
        if($returnbase64 == true)
        {
            return $img;
        }
        $rezi = aiomatic_base64_to_jpeg($img, $file, $ret_path);
        return $rezi;
    }
}
function aiomatic_admin_footer()
{
?>
    <div class="aiomatic-overlay" style="display: none">
        <div class="aiomatic_modal">
            <div class="aiomatic_modal_head">
                <span class="aiomatic_modal_title"><?php echo esc_html__('GPT3 Modal', 'aiomatic-automatic-ai-content-writer');?></span>
                <span class="aiomatic_modal_close">&times;</span>
            </div>
            <div class="aiomatic_modal_content"></div>
        </div>
    </div>
    <div class="wpcgai_lds-ellipsis" style="display: none">
        <div class="aiomatic-generating-title"><?php echo esc_html__('Generating content...', 'aiomatic-automatic-ai-content-writer');?></div>
        <div class="aiomatic-generating-process"></div>
        <div class="aiomatic-timer"></div>
    </div>
<?php
}
add_action('admin_init', 'aiomatic_register_mysettings');
function aiomatic_register_mysettings()
{
    require_once (dirname(__FILE__) . "/res/aiomatic-finetune.php"); 
    aiomatic_cron_schedule();
    if(isset($_GET['aiomatic_page']))
    {
        $curent_page = $_GET["aiomatic_page"];
    }
    else
    {
        $curent_page = '';
    }
    $all_rules = get_option('aiomatic_rules_list', array());
    if($all_rules === false)
    {
        $all_rules = array();
    }
    $rules_count = count($all_rules);
    $rules_per_page = get_option('aiomatic_posts_per_page', 12);
    $max_pages = ceil($rules_count/$rules_per_page);
    if($max_pages == 0)
    {
        $max_pages = 1;
    }
    $last_url = (aiomatic_isSecure() ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    if(stristr($last_url, 'aiomatic_items_panel') !== false && (!is_numeric($curent_page) || $curent_page > $max_pages || $curent_page <= 0))
    {
        if(stristr($last_url, 'aiomatic_page=') === false)
        {
            if(stristr($last_url, '?') === false)
            {
                $last_url .= '?aiomatic_page=' . $max_pages;
            }
            else
            {
                $last_url .= '&aiomatic_page=' . $max_pages;
            }
        }
        else
        {
            if(isset($_GET['aiomatic_page']))
            {
                $curent_page = $_GET["aiomatic_page"];
            }
            else
            {
                $curent_page = '';
            }
            if(is_numeric($curent_page))
            {
                $last_url = str_replace('aiomatic_page=' . $curent_page, 'aiomatic_page=' . $max_pages, $last_url);
            }
            else
            {
                if(stristr($last_url, '?') === false)
                {
                    $last_url .= '?aiomatic_page=' . $max_pages;
                }
                else
                {
                    $last_url .= '&aiomatic_page=' . $max_pages;
                }
            }
        }
        aiomatic_redirect($last_url);
    }
    register_setting('aiomatic_option_group', 'aiomatic_Main_Settings');
    register_setting('aiomatic_option_group2', 'aiomatic_Spinner_Settings');
    register_setting('aiomatic_option_group3', 'aiomatic_Limit_Settings');
    if (is_multisite()) {
        if (!get_option('aiomatic_Main_Settings')) {
            aiomatic_activation_callback(TRUE);
        }
    }
}
function aiomatic_redirect($url, $statusCode = 301)
{
  if(!function_exists('wp_redirect'))
  {
     include_once( ABSPATH . 'wp-includes/pluggable.php' );
  }
  wp_redirect($url, $statusCode);
  die();
}

function aiomatic_get_plugin_url()
{
    return plugins_url('', __FILE__);
}

function aiomatic_get_file_url($url)
{
    return esc_url(aiomatic_get_plugin_url() . '/' . $url);
}
add_action('wp_enqueue_scripts', 'aiomatic_wp_load_files');
function aiomatic_wp_load_files()
{
    wp_enqueue_style('cr-front-css', plugins_url('styles/front.css', __FILE__));
}
function aiomatic_admin_load_files()
{
    wp_register_style('aiomatic-browser-style', plugins_url('styles/aiomatic-browser.css', __FILE__), false, '1.0.0');
    wp_enqueue_style('aiomatic-browser-style');
    wp_register_style('aiomatic-custom-style', plugins_url('styles/coderevolution-style.css', __FILE__), false, '1.0.0');
    wp_enqueue_style('aiomatic-custom-style');
    wp_enqueue_script('jquery');
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
}
function aiomatic_admin_load_playground()
{
    wp_register_script('aiomatic-playground-script', plugins_url('scripts/playground.js', __FILE__), array('jquery'), '1.0.0');
    wp_enqueue_script('aiomatic-playground-script');
}
function aiomatic_admin_load_embeddings()
{
    wp_register_script('aiomatic-embeddings-script', plugins_url('scripts/embeddings.js', __FILE__), array('jquery'), '1.0.0');
    wp_enqueue_script('aiomatic-embeddings-script');
    wp_localize_script('aiomatic-embeddings-script', 'aiomatic_object', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('openai-ajax-nonce')
	));
    wp_register_style('aiomatic-embeddings-style', plugins_url('styles/embeddings.css', __FILE__), false, '1.0.0');
    wp_enqueue_style('aiomatic-embeddings-style');
}
function aiomatic_admin_load_training()
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (isset($aiomatic_Main_Settings['prompt_suffix']) && $aiomatic_Main_Settings['prompt_suffix'] != '')
    {
        $prompt_suffix = $aiomatic_Main_Settings['prompt_suffix'];
    }
    else
    {
        $prompt_suffix = ' ->';
    }
    if (isset($aiomatic_Main_Settings['completion_suffix']) && $aiomatic_Main_Settings['completion_suffix'] != '')
    {
        $completion_suffix = $aiomatic_Main_Settings['completion_suffix'];
    }
    else
    {
        $completion_suffix = ' ###';
    }
    wp_register_script('aiomatic-training-script', plugins_url('scripts/training.js', __FILE__), array('jquery'), '1.0.0');
    wp_enqueue_script('aiomatic-training-script');
	wp_localize_script('aiomatic-training-script', 'aiomatic_object', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'maxfilesize' => wp_max_upload_size(),
        'prompt_suffix' => $prompt_suffix,
        'completion_suffix' => $completion_suffix
	));
    wp_register_style('aiomatic-training-style', plugins_url('styles/training.css', __FILE__), false, '1.0.0');
    wp_enqueue_style('aiomatic-training-style');
}

function aiomatic_random_sentence_generator($first = true)
{
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if ($first == false) {
        $r_sentences = $aiomatic_Main_Settings['sentence_list2'];
    } else {
        $r_sentences = $aiomatic_Main_Settings['sentence_list'];
    }
    $r_variables = $aiomatic_Main_Settings['variable_list'];
    $r_sentences = trim($r_sentences);
    $r_variables = trim($r_variables, ';');
    $r_variables = trim($r_variables);
    $r_sentences = str_replace("\r\n", "\n", $r_sentences);
    $r_sentences = str_replace("\r", "\n", $r_sentences);
    $r_sentences = explode("\n", $r_sentences);
    $r_variables = str_replace("\r\n", "\n", $r_variables);
    $r_variables = str_replace("\r", "\n", $r_variables);
    $r_variables = explode("\n", $r_variables);
    $r_vars      = array();
    for ($x = 0; $x < count($r_variables); $x++) {
        $var = explode("=>", trim($r_variables[$x]));
        if (isset($var[1])) {
            $key          = strtolower(trim($var[0]));
            $words        = explode(";", trim($var[1]));
            $r_vars[$key] = $words;
        }
    }
    $max_s    = count($r_sentences) - 1;
    $rand_s   = rand(0, $max_s);
    $sentence = $r_sentences[$rand_s];
    $sentence = str_replace(' ,', ',', ucfirst(aiomatic_replace_words($sentence, $r_vars)));
    $sentence = str_replace(' .', '.', $sentence);
    $sentence = str_replace(' !', '!', $sentence);
    $sentence = str_replace(' ?', '?', $sentence);
    $sentence = trim($sentence);
    return $sentence;
}

function aiomatic_get_word($key, $r_vars)
{
    if (isset($r_vars[$key])) {
        
        $words  = $r_vars[$key];
        $w_max  = count($words) - 1;
        $w_rand = rand(0, $w_max);
        return aiomatic_replace_words(trim($words[$w_rand]), $r_vars);
    } else {
        return "";
    }
    
}

function aiomatic_replace_words($sentence, $r_vars)
{
    
    if (str_replace('%', '', $sentence) == $sentence)
        return $sentence;
    
    $words = explode(" ", $sentence);
    
    $new_sentence = array();
    for ($w = 0; $w < count($words); $w++) {
        
        $word = trim($words[$w]);
        
        if ($word != '') {
            if (preg_match('/^%([^%\n]*)$/', $word, $m)) {
                $varkey         = trim($m[1]);
                $new_sentence[] = aiomatic_get_word($varkey, $r_vars);
            } else {
                $new_sentence[] = $word;
            }
        }
    }
    return implode(" ", $new_sentence);
}

// Add a shortcode to the WordPress editor
add_shortcode('aiomatic-text-completion-form', 'aiomatic_form_shortcode');

// The shortcode function that displays the form
function aiomatic_form_shortcode($atts) {
    $echome = '';
    $atts = shortcode_atts( array(
        'temperature' => '0.7',
        'top_p' => '1',
        'presence_penalty' => '0',
        'frequency_penalty' => '0',
        'model' => 'text-davinci-003',
        'user_token_cap_per_day' => '',
        'prompt_templates' => '',
        'prompt_editable' => ''
    ), $atts );

    //accessing the parameters like this
    $temp = $atts['temperature'];
    $top_p = $atts['top_p'];
    $presence = $atts['presence_penalty'];
    $frequency = $atts['frequency_penalty'];
    $model = $atts['model'];
    $user_token_cap_per_day = $atts['user_token_cap_per_day'];
    $prompt_templates = $atts['prompt_templates'];
    $prompt_editable = $atts['prompt_editable'];
    $user_id = '0';
    if(!empty($user_token_cap_per_day))
    {
        $user_id = get_current_user_id();
    }
    if (!wp_style_is( 'fontawesome', 'enqueued' )) {
        wp_register_style( 'fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/6.2.1/css/font-awesome.min.css', false, '6.2.1' );
        wp_enqueue_style( 'fontawesome' );
    } 
    wp_enqueue_script('openai-completion-ajax', plugins_url('scripts/openai-completion-ajax.js', __FILE__), array('jquery'));
	wp_localize_script('openai-completion-ajax', 'aiomatic_completition_ajax_object', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('openai-ajax-nonce'),
		'model' => $model,
		'temp' => $temp,
		'top_p' => $top_p,
		'presence' => $presence,
		'frequency' => $frequency,
        'user_token_cap_per_day' => $user_token_cap_per_day,
        'user_id' => $user_id
	));
    wp_enqueue_style('css-ai-front', plugins_url('styles/form-front.css', __FILE__));
    $all_models = aiomatic_get_all_models();
    $models = $all_models; 
    if($model != 'default' && !in_array($model, $models))
    {
        $echome .= 'Invalid model provided!';
        return $echome;
    }
    if($temp != 'default' && floatval($temp) < 0 || floatval($temp) > 1)
    {
        $echome .= 'Invalid temperature provided!';
        return $echome;
    }
    if($top_p != 'default' && floatval($top_p) < 0 || floatval($top_p) > 1)
    {
        $echome .= 'Invalid top_p provided!';
        return $echome;
    }
    if($presence != 'default' && floatval($presence) < -2 || floatval($presence) > 2)
    {
        $echome .= 'Invalid presence_penalty provided!';
        return $echome;
    }
    if($frequency != 'default' && floatval($frequency) < -2 || floatval($frequency) > 2)
    {
        $echome .= 'Invalid frequency_penalty provided!';
        return $echome;
    }
	// Display the form
	$echome .= '
		<form id="openai-ai-form" method="post">
			<div class="form-group">';
    $echome .= '<div id="aiomatic_input" ';
    if($prompt_editable != 'no' && $prompt_editable != '0' && $prompt_editable != 'disabled' && $prompt_editable != 'disable' && $prompt_editable != 'false')
    {
        $echome .= 'contenteditable="true" ';
    }
    $echome .= 'class="form-control" placeholder="Write your AI command here"></div>';
    if($prompt_templates != '')
    {
        $predefined_prompts_arr = explode(';', $prompt_templates);
        $echome .= '<select id="aiomatic_completion_templates" class="cr_width_full">';
        $echome .= '<option disabled selected>' . esc_html__("Please select a prompt", 'aiomatic-automatic-ai-content-writer') . '</option>';
        foreach($predefined_prompts_arr as $sval)
        {
            $ppro = explode('|~|~|', $sval);
            if(isset($ppro[1]))
            {
                $echome .= '<option value="' . esc_attr($ppro[1]) . '">' . esc_html($ppro[0]) . '</option>';
            }
            else
            {
                $echome .= '<option value="' . esc_attr($sval) . '">' . esc_html($sval) . '</option>';
            }
        }
        $echome .= '</select>';
    }
    if($model == 'default' || $model == '')
    {
        $echome .= '<label for="model-selector">Model:</label><select class="aiomatic-ai-input" id="model-selector">';
        foreach ($models as $model) {
            $echome .= "<option value='" . $model . "'>" . $model . "</option>";
        }
        $echome .= '</select>';
    }
    if($temp == 'default' || $temp == '')
    {
        $echome .= '<label for="temperature-input">Temperature:</label><input type="number" min="0" step="0.1" max="1" class="aiomatic-ai-input" id="temperature-input" name="temperature" value="1">';
    }
    if($top_p == 'default' || $top_p == '')
    {
        $echome .= '<label for="top_p-input">Top_p:</label><input type="number" min="0" step="0.1" max="1" class="aiomatic-ai-input" id="top_p-input" name="top_p" value="1">';
    }
    if($presence == 'default' || $presence == '')
    {
        $echome .= '<label for="presence-input">Presence Penalty:</label><input type="number" min="0" step="0.1" max="1" class="aiomatic-ai-input" id="presence-input" name="presence" value="0">';
    }
    if($frequency == 'default' || $frequency == '')
    {
        $echome .= '<label for="frequency-input">Frequency Penalty:</label><input type="number" min="0" step="0.1" max="1" class="aiomatic-ai-input" id="frequency-input" name="frequency" value="0">';
    }
	$echome .= '</div>';
    $echome .= '<button type="button" id="copy-button" class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Copy to clipboard">
    <i class="fas fa-copy"></i>
    </button>';

    if($prompt_templates == '')
    {
        $echome .= '<button type="button" id="openai-speech-button" class="btn btn-primary">
                <i class="fas fa-microphone"></i>
            </button>';
    }
    $echome .= '<button type="button" id="aisubmitbut" onclick="openaifunct()" class="btn btn-primary">Submit</button>
            <div id="openai-response"></div>
		</form> 
	';
    return $echome;
}

add_action('wp_ajax_aiomatic_form_submit', 'aiomatic_form_submit');
add_action('wp_ajax_nopriv_aiomatic_form_submit', 'aiomatic_form_submit');

function aiomatic_form_submit() {
	check_ajax_referer('openai-ajax-nonce', 'nonce');
    if(!isset($_POST['presence']) || !isset($_POST['input_text']) || !isset($_POST['model']) || !isset($_POST['temp']) || !isset($_POST['top_p']) || !isset($_POST['frequency']))
    {
        aiomatic_log_to_file('Incomplete POST request for text editing: ' . print_r($_POST, true));
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $user_token_cap_per_day = sanitize_text_field($_POST['user_token_cap_per_day']);
    if(!empty($user_token_cap_per_day))
    {
        $user_token_cap_per_day = intval($user_token_cap_per_day);
    }
	$user_id = sanitize_text_field($_POST['user_id']);
	$input_text = $_POST['input_text'];
	$model = sanitize_text_field($_POST['model']);
	$temperature = sanitize_text_field($_POST['temp']);
	$top_p = sanitize_text_field($_POST['top_p']);
	$presence_penalty = sanitize_text_field($_POST['presence']);
	$frequency_penalty = sanitize_text_field($_POST['frequency']);
    $all_models = aiomatic_get_all_models();
    $models = $all_models;
    if(!in_array($model, $models))
    {
        aiomatic_log_to_file('Invalid model provided: ' . $model);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $temperature = floatval($temperature);
    $top_p = floatval($top_p);
    $presence_penalty = floatval($presence_penalty);
    $frequency_penalty = floatval($frequency_penalty);
    if($temperature < 0 || $temperature > 1)
    {
        aiomatic_log_to_file('Invalid temperature provided: ' . $temperature);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    if($top_p < 0 || $top_p > 1)
    {
        aiomatic_log_to_file('Invalid top_p provided: ' . $top_p);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    if($presence_penalty < -2 || $presence_penalty > 2)
    {
        aiomatic_log_to_file('Invalid presence_penalty provided: ' . $presence_penalty);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    if($frequency_penalty < -2 || $frequency_penalty > 2)
    {
        aiomatic_log_to_file('Invalid frequency_penalty provided: ' . $frequency_penalty);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
    {
        aiomatic_log_to_file('You need to insert a valid OpenAI/AiomaticAPI API Key for this to work!');
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
        wp_die();
    }
    $used_token_count = 0;
    if(is_numeric($user_token_cap_per_day))
    {
        if(empty($user_id) || $user_id == 0)
        {
            $response_text = sprintf( wp_kses( __( 'You are not allowed to access this form if you are not logged in. Please <a href="%s" target="_blank">log in</a> to continue.', 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), wp_login_url(get_permalink()) );
            echo $response_text;
            wp_die();
        }
        $used_token_count = get_user_meta($user_id, 'aiomatic_used_tokens', true);
        if($used_token_count !== '' && $used_token_count !== false && is_numeric($used_token_count))
        {
            $used_token_count = intval($used_token_count);
            if($used_token_count > $user_token_cap_per_day)
            {
                $response_text = 'Daily token count for your user account was exceeded! Please try again tomorrow.';
                echo $response_text;
                wp_die();
            }
        }
        else
        {
            $used_token_count = 0;
        }
    }
	$appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
    $appids = array_filter($appids);
    $token = $appids[array_rand($appids)];
    $max_tokens = 2048;
    if(strstr($model, 'davinci') !== false && strstr($model, ':ft-') === false)
    {
        $max_tokens = 4000;
    }
    $query_token_count = count(aiomatic_encode($input_text));
    $available_tokens = $max_tokens - $query_token_count;
    if($available_tokens <= 16)
    {
        $string_len = strlen($input_text);
        $string_len = $string_len / 2;
        $string_len = intval(0 - $string_len);
        $input_text = substr($input_text, 0, $string_len);
        $input_text = trim($input_text);
        if(empty($input_text))
        {
            aiomatic_log_to_file('Empty API seed expression provided (after processing)');
            return '';
        }
        $query_token_count = count(aiomatic_encode($input_text));
        $available_tokens = $max_tokens - $query_token_count;
    }
	$error = '';
    $finish_reason = '';
    $response_text = aiomatic_generate_text($token, $model, $input_text, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, false, 'shortcodeCompletion', 0, $finish_reason, $error);
    if($response_text === false)
    {
        aiomatic_log_to_file('Error occurred when calling API in submit: ' . $error);
        $response_text = 'Failed to generate content, please try again later!';
    }
    else
    {
        $inp_count = count(aiomatic_encode($input_text));
        $resp_count = count(aiomatic_encode($response_text));
        $used_token_count = intval($used_token_count) + $inp_count + $resp_count;
        update_user_meta($user_id, 'aiomatic_used_tokens', $used_token_count);
    }
	echo $response_text;
	wp_die();
}

// Add a shortcode to the WordPress editor
add_shortcode('aiomatic-text-editing-form', 'aiomatic_edit_shortcode');

// The shortcode function that displays the form
function aiomatic_edit_shortcode($atts) {
    $echome = '';
    $atts = shortcode_atts( array(
        'temperature' => '0.7',
        'top_p' => '1',
        'model' => 'text-davinci-edit-001',
        'user_token_cap_per_day' => '',
        'prompt_templates' => '',
        'prompt_editable' => ''
    ), $atts );

    //accessing the parameters like this
    $temp = $atts['temperature'];
    $top_p = $atts['top_p'];
    $model = $atts['model'];
    $prompt_templates = $atts['prompt_templates'];
    $prompt_editable = $atts['prompt_editable'];
    $user_token_cap_per_day = $atts['user_token_cap_per_day'];
    $user_id = '0';
    if(!empty($user_token_cap_per_day))
    {
        $user_id = get_current_user_id();
    }
    if (!wp_style_is( 'fontawesome', 'enqueued' )) {
        wp_register_style( 'fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/6.2.1/css/font-awesome.min.css', false, '6.2.1' );
        wp_enqueue_style( 'fontawesome' );
    } 
    wp_enqueue_script('openai-edit-ajax', plugins_url('scripts/openai-edit-ajax.js', __FILE__), array('jquery'));
	wp_localize_script('openai-edit-ajax', 'aiomatic_edit_ajax_object', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('openai-ajax-nonce'),
		'temp' => $temp,
		'top_p' => $top_p,
		'model' => $model,
        'user_token_cap_per_day' => $user_token_cap_per_day,
        'user_id' => $user_id
	));
    wp_enqueue_style('css-ai-front', plugins_url('styles/form-front.css', __FILE__));
    $all_models = aiomatic_get_all_models();
    $models = array_merge($all_models, array('text-davinci-edit-001', 'code-davinci-edit-001'));
    if($model != 'default' && !in_array($model, $models))
    {
        $echome .= 'Invalid model provided!';
        return $echome;
    }
    if($temp != 'default' && floatval($temp) < 0 || floatval($temp) > 1)
    {
        $echome .= 'Invalid temperature provided!';
        return $echome;
    }
    if($top_p != 'default' && floatval($top_p) < 0 || floatval($top_p) > 1)
    {
        $echome .= 'Invalid top_p provided!';
        return $echome;
    }
	// Display the form
	$echome .= '
		<form id="openai-ai-form" method="post">
			<div class="form-group">
            <textarea class="aiomatic-edit-textarea aiomatic-edit-area" rows="8" id="aiomatic_edit_input" placeholder="Write your text to be edited here"></textarea>';

    $echome .= '<textarea class="aiomatic-edit-textarea aiomatic-instruction-area" rows="8" id="aiomatic_edit_instruction" placeholder="Write your AI instruction here"';
    if($prompt_editable == 'no' || $prompt_editable == '0' || $prompt_editable == 'disabled' || $prompt_editable == 'disable' || $prompt_editable == "false")
    {
        $echome .= ' disabled';
    }
    $echome .= '></textarea>';
    $echome .= '<textarea class="aiomatic-edit-textarea aiomatic-response-area" rows="5" id="aiomatic_edit_response" disabled placeholder="You will see the edited result here"></textarea>';
    
    if($model == 'default' || $model == '')
    {
        $echome .= '<label for="model-edit-selector">Model:</label><select class="aiomatic-ai-input" id="model-edit-selector">';
        foreach ($models as $model) {
            $echome .= "<option value='" . $model . "'>" . $model . "</option>";
        }
        $echome .= '</select>';
    }
    if($temp == 'default' || $temp == '')
    {
        $echome .= '<label for="temperature-edit-input">Temperature:</label><input type="number" min="0" step="0.1" max="1" class="aiomatic-ai-input" id="temperature-edit-input" name="temperature" value="0">';
    }
    if($top_p == 'default' || $top_p == '')
    {
        $echome .= '<label for="top_p-edit-input">Top_p:</label><input type="number" min="0" step="0.1" max="1" class="aiomatic-ai-input" id="top_p-edit-input" name="top_p" value="1">';
    }
    if($prompt_templates != '')
    {
        $predefined_prompts_arr = explode(';', $prompt_templates);
        $echome .= '<select id="aiomatic_edit_templates" class="cr_width_full">';
        $echome .= '<option disabled selected>' . esc_html__("Please select a prompt", 'aiomatic-automatic-ai-content-writer') . '</option>';
        foreach($predefined_prompts_arr as $sval)
        {
            $ppro = explode('|~|~|', $sval);
            if(isset($ppro[1]))
            {
                $echome .= '<option value="' . esc_attr($ppro[1]) . '">' . esc_html($ppro[0]) . '</option>';
            }
            else
            {
                $echome .= '<option value="' . esc_attr($sval) . '">' . esc_html($sval) . '</option>';
            }
        }
        $echome .= '</select>';
    }
	$echome .= '</div>'; 
    $echome .= '<button type="button" id="copy-edit-button" class="btn btn-primary" data-toggle="tooltip" data-placement="top" title="Copy to clipboard">
    <i class="fas fa-copy"></i>
    </button>';
    
    if($prompt_templates == '')
    {
       $echome .= '<button type="button" id="openai-edit-speech-button" class="btn btn-primary">
            <i class="fas fa-microphone"></i>
        </button>';
    }
    $echome .= '<button type="button" id="aieditsubmitbut" onclick="openaieditfunct()" class="btn btn-primary">Submit</button>
            <div id="openai-edit-response"></div>
		</form> 
	';
    return $echome;
}

add_action('wp_ajax_aiomatic_edit_submit', 'aiomatic_edit_submit');
add_action('wp_ajax_nopriv_aiomatic_edit_submit', 'aiomatic_edit_submit');

function aiomatic_edit_submit() {
	check_ajax_referer('openai-ajax-nonce', 'nonce');
    if(!isset($_POST['instruction']) || !isset($_POST['input_text']) || !isset($_POST['model']) || !isset($_POST['temp']) || !isset($_POST['top_p']))
    {
        aiomatic_log_to_file('Incomplete POST request for text editing: ' . print_r($_POST, true));
        $response_text = 'Failed to generate image, please try again later!';
        echo $response_text;
	    wp_die();
    }
	$instruction = $_POST['instruction'];
	$input_text = $_POST['input_text'];
	$model = sanitize_text_field($_POST['model']);
	$temperature = sanitize_text_field($_POST['temp']);
	$top_p = sanitize_text_field($_POST['top_p']);
    $user_token_cap_per_day = sanitize_text_field($_POST['user_token_cap_per_day']);
    if(!empty($user_token_cap_per_day))
    {
        $user_token_cap_per_day = intval($user_token_cap_per_day);
    }
	$user_id = sanitize_text_field($_POST['user_id']);
    $temperature = floatval($temperature);
    $top_p = floatval($top_p);
    $all_models = aiomatic_get_all_models();
    $models = array_merge($all_models, array('text-davinci-edit-001', 'code-davinci-edit-001'));
    if(!in_array($model, $models))
    {
        aiomatic_log_to_file('Invalid editing model provided: ' . $model);
        $response_text = 'Failed to edit content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    if($temperature < 0 || $temperature > 1)
    {
        aiomatic_log_to_file('Invalid temperature provided: ' . $temperature);
        $response_text = 'Failed to edit content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    if($top_p < 0 || $top_p > 1)
    {
        aiomatic_log_to_file('Invalid top_p provided: ' . $top_p);
        $response_text = 'Failed to edit content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    if(empty($instruction))
    {
        $response_text = 'You need to add an instruction for the text editing!';
        echo $response_text;
	    wp_die();
    }
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
    {
        aiomatic_log_to_file('You need to insert a valid OpenAI/AiomaticAPI API Key for this to work!');
        $response_text = 'Failed to edit content, please try again later!';
        echo $response_text;
        wp_die();
    }
    $used_token_count = 0;
    if(is_numeric($user_token_cap_per_day))
    {
        if(empty($user_id) || $user_id == 0)
        {
            $response_text = sprintf( wp_kses( __( 'You are not allowed to access this form if you are not logged in. Please <a href="%s" target="_blank">log in</a> to continue.', 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), wp_login_url(get_permalink()) );
            echo $response_text;
            wp_die();
        }
        $used_token_count = get_user_meta($user_id, 'aiomatic_used_edit_tokens', true);
        if($used_token_count !== '' && $used_token_count !== false && is_numeric($used_token_count))
        {
            $used_token_count = intval($used_token_count);
            if($used_token_count > $user_token_cap_per_day)
            {
                $response_text = 'Daily token count for your user account was exceeded! Please try again tomorrow.';
                echo $response_text;
                wp_die();
            }
        }
        else
        {
            $used_token_count = 0;
        }
    }
	$appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
    $appids = array_filter($appids);
    $token = $appids[array_rand($appids)];
    $all_models = aiomatic_get_all_models();
    $completionmodels = $all_models;
    if(in_array($model, $completionmodels))
    {
        $prompt = $instruction . ': ' . $input_text;
        $error = '';
        $finish_reason = '';
        $max_tokens = 2048;
        if(strstr($model, 'davinci') !== false && strstr($model, ':ft-') === false)
        {
            $max_tokens = 4000;
        }
        $query_token_count = count(aiomatic_encode($prompt));
        $available_tokens = $max_tokens - $query_token_count;
        if($available_tokens <= 16)
        {
            $string_len = strlen($prompt);
            $string_len = $string_len / 2;
            $string_len = intval(0 - $string_len);
            $prompt = substr($prompt, 0, $string_len);
            $prompt = trim($prompt);
            if(empty($prompt))
            {
                aiomatic_log_to_file('Empty API seed expression provided (after processing)');
                $response_text = 'Failed to edit content, please try again later!';
            }
            else
            {
                $query_token_count = count(aiomatic_encode($prompt));
                $available_tokens = $max_tokens - $query_token_count;
            }
        }
        $response_text = aiomatic_generate_text($token, $model, $prompt, $available_tokens, $temperature, $top_p, 0, 0, false, 'shortcodeCEditor', 0, $finish_reason, $error);
        if($response_text === false)
        {
            aiomatic_log_to_file('Error occurred when calling API in edit: ' . $error);
            $response_text = 'Failed to edit content, please try again later!';
        }
        else
        {
            $inp_count = count(aiomatic_encode($prompt));
            $resp_count = count(aiomatic_encode($response_text));
            $used_token_count = intval($used_token_count) + $inp_count + $resp_count;
            update_user_meta($user_id, 'aiomatic_used_tokens', $used_token_count);
        }
        echo $response_text;
        wp_die();
    }
    else
    {
        $aierror = '';
        $response_text = aiomatic_edit_text($token, $model, $instruction, $input_text, $temperature, $top_p, 'shortcodeEditor', 0, $aierror);
        if($response_text === false)
        {
            aiomatic_log_to_file('Error occurred when calling API using edit model: ' . $aierror);
            $response_text = 'Failed to edit content, please try again later!';
        }
        else
        {
            $instr_count = count(aiomatic_encode($instruction));
            $inp_count = count(aiomatic_encode($input_text));
            $resp_count = count(aiomatic_encode($response_text));
            $used_token_count = intval($used_token_count) + $instr_count + $inp_count + $resp_count;
            update_user_meta($user_id, 'aiomatic_used_edit_tokens', $used_token_count);
        }
        echo $response_text;
        wp_die();
    }
}

// Add a shortcode to the WordPress editor
add_shortcode('aiomatic-image-generator-form', 'aiomatic_image_shortcode');

// The shortcode function that displays the form
function aiomatic_image_shortcode($atts) {
    $echome = '';
    $atts = shortcode_atts( array(
        'image_size' => 'default',
        'user_token_cap_per_day' => '',
        'prompt_templates' => '',
        'prompt_editable' => ''
    ), $atts );
    $user_token_cap_per_day = $atts['user_token_cap_per_day'];
    $prompt_templates = $atts['prompt_templates'];
    $prompt_editable = $atts['prompt_editable'];
    $user_id = '0';
    if(!empty($user_token_cap_per_day))
    {
        $user_id = get_current_user_id();
    }
    //accessing the parameters like this
    $image_size = $atts['image_size'];
    if (!wp_style_is( 'fontawesome', 'enqueued' )) {
        wp_register_style( 'fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/6.2.1/css/font-awesome.min.css', false, '6.2.1' );
        wp_enqueue_style( 'fontawesome' );
    } 
    $image_placeholder = plugins_url('res/loading.gif', __FILE__);
    wp_enqueue_script('openai-image-ajax', plugins_url('scripts/openai-image-ajax.js', __FILE__), array('jquery'));
	wp_localize_script('openai-image-ajax', 'aiomatic_image_ajax_object', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('openai-ajax-nonce'),
		'image_size' => $image_size,
		'image_placeholder' => $image_placeholder,
        'user_token_cap_per_day' => $user_token_cap_per_day,
        'user_id' => $user_id
	));
    wp_enqueue_style('css-ai-front', plugins_url('styles/form-front.css', __FILE__));
    $sizes = array('1024x1024', '512x512', '256x256');
    if($image_size != 'default' && !in_array($image_size, $sizes))
    {
        $echome .= 'Invalid image size provided!';
        return $echome;
    }
	// Display the form
	$echome .= '
		<form id="openai-ai-form" method="post">
			<div class="form-group">';
    $echome .= '<textarea class="aiomatic-image-textarea aiomatic-image-instruction-area" rows="8" id="aiomatic_image_instruction" placeholder="Write your AI instruction here"';
    if($prompt_editable == 'no' || $prompt_editable == '0' || $prompt_editable == 'disabled' || $prompt_editable == 'disable' || $prompt_editable == "false")
    {
        $echome .= ' disabled';
    }
    $echome .= '></textarea>';   
    if($prompt_templates != '')
    {
        $predefined_prompts_arr = explode(';', $prompt_templates);
        $echome .= '<select id="aiomatic_image_templates" class="cr_width_full">';
        $echome .= '<option disabled selected>' . esc_html__("Please select a prompt", 'aiomatic-automatic-ai-content-writer') . '</option>';
        foreach($predefined_prompts_arr as $sval)
        {
            $ppro = explode('|~|~|', $sval);
            if(isset($ppro[1]))
            {
                $echome .= '<option value="' . esc_attr($ppro[1]) . '">' . esc_html($ppro[0]) . '</option>';
            }
            else
            {
                $echome .= '<option value="' . esc_attr($sval) . '">' . esc_html($sval) . '</option>';
            }
        }
        $echome .= '</select>';
    }
    $echome .= '<br/>
            <div class="aiomatic-image-result cr_image_center" id="aiomatic_image_div"><img id="aiomatic_image_response" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII="></div>';
    if($image_size == 'default' || empty($image_size))
    {
        $echome .= '<label for="ai-image-size-selector">Image Size:</label><select class="aiomatic-ai-input" id="ai-image-size-selector">';
        foreach ($sizes as $size) {
            $echome .= "<option value='" . $size . "'>" . $size . "</option>";
        }
        $echome .= '</select>';
    }
	$echome .= '</div>';
    if($prompt_templates == '')
    {
        $echome .= '<button type="button" id="openai-image-speech-button" class="btn btn-primary">
                <i class="fas fa-microphone"></i>
            </button>';
    }
    $echome .= '<button type="button" id="aiimagesubmitbut" onclick="openaiimagefunct()" class="btn btn-primary">Submit</button>
            <div id="openai-image-response"></div>
		</form> 
	';
    return $echome;
}


// Add a shortcode to the WordPress editor
add_shortcode('aiomatic-stable-image-generator-form', 'aiomatic_stable_image_shortcode');

// The shortcode function that displays the form
function aiomatic_stable_image_shortcode($atts) {
    $echome = '';
    $atts = shortcode_atts( array(
        'image_size' => 'default',
        'user_token_cap_per_day' => '',
        'prompt_templates' => '',
        'prompt_editable' => ''
    ), $atts );
    $user_token_cap_per_day = $atts['user_token_cap_per_day'];
    $prompt_templates = $atts['prompt_templates'];
    $prompt_editable = $atts['prompt_editable'];
    $user_id = '0';
    if(!empty($user_token_cap_per_day))
    {
        $user_id = get_current_user_id();
    }
    //accessing the parameters like this
    $image_size = $atts['image_size'];
    if (!wp_style_is( 'fontawesome', 'enqueued' )) {
        wp_register_style( 'fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/6.2.1/css/font-awesome.min.css', false, '6.2.1' );
        wp_enqueue_style( 'fontawesome' );
    } 
    $image_placeholder = plugins_url('res/loading.gif', __FILE__);
    wp_enqueue_script('openai-stable-image-ajax', plugins_url('scripts/openai-stable-image-ajax.js', __FILE__), array('jquery'));
	wp_localize_script('openai-stable-image-ajax', 'aiomatic_stable_image_ajax_object', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('openai-ajax-nonce'),
		'image_size' => $image_size,
		'image_placeholder' => $image_placeholder,
        'user_token_cap_per_day' => $user_token_cap_per_day,
        'user_id' => $user_id
	));
    wp_enqueue_style('css-ai-front', plugins_url('styles/form-front.css', __FILE__));
    $sizes = array('1024x1024', '512x512');
    if($image_size != 'default' && !in_array($image_size, $sizes))
    {
        $echome .= 'Invalid image size provided!';
        return $echome;
    }
	// Display the form
	$echome .= '
		<form id="openai-ai-form" method="post">
			<div class="form-group">';
    $echome .= '<textarea class="aiomatic-image-textarea aiomatic-image-instruction-area" rows="8" id="aiomatic_stable_image_instruction" placeholder="Write your AI instruction here"';
    if($prompt_editable == 'no' || $prompt_editable == '0' || $prompt_editable == 'disabled' || $prompt_editable == 'disable' || $prompt_editable == "false")
    {
        $echome .= ' disabled';
    }
    $echome .= '></textarea>';
    if($prompt_templates != '')
    {
        $predefined_prompts_arr = explode(';', $prompt_templates);
        $echome .= '<select id="aiomatic_stable_image_templates" class="cr_width_full">';
        $echome .= '<option disabled selected>' . esc_html__("Please select a prompt", 'aiomatic-automatic-ai-content-writer') . '</option>';
        foreach($predefined_prompts_arr as $sval)
        {
            $ppro = explode('|~|~|', $sval);
            if(isset($ppro[1]))
            {
                $echome .= '<option value="' . esc_attr($ppro[1]) . '">' . esc_html($ppro[0]) . '</option>';
            }
            else
            {
                $echome .= '<option value="' . esc_attr($sval) . '">' . esc_html($sval) . '</option>';
            }
        }
        $echome .= '</select>';
    }        
    $echome .= '<br/>
            <div class="aiomatic-image-result cr_image_center" id="aiomatic_image_div"><img id="aiomatic_stable_image_response" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII="></div>';
    if($image_size == 'default' || empty($image_size))
    {
        $echome .= '<label for="model-stable-size-selector">Image Size:</label><select class="aiomatic-ai-input" id="model-stable-size-selector">';
        foreach ($sizes as $size) {
            $echome .= "<option value='" . $size . "'>" . $size . "</option>";
        }
        $echome .= '</select>';
    }
	$echome .= '</div>';
    if($prompt_templates == '')
    {
            $echome .= '<button type="button" id="openai-stable-image-speech-button" class="btn btn-primary">
                <i class="fas fa-microphone"></i>
            </button>';
    }
			$echome .= '<button type="button" id="aistableimagesubmitbut" onclick="stableimagefunct()" class="btn btn-primary">Submit</button>
            <div id="openai-stable-image-response"></div>
		</form> 
	';
    return $echome;
}

add_action('wp_ajax_aiomatic_image_ajax_submit', 'aiomatic_image_submit');
add_action('wp_ajax_nopriv_aiomatic_image_ajax_submit', 'aiomatic_image_submit');

function aiomatic_image_submit() {
	check_ajax_referer('openai-ajax-nonce', 'nonce');
    if(!isset($_POST['image_size']) || !isset($_POST['instruction']))
    {
        aiomatic_log_to_file('Incomplete POST request for DALLE2 images: ' . print_r($_POST, true));
        $response_text = 'Failed to generate image, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $user_token_cap_per_day = sanitize_text_field($_POST['user_token_cap_per_day']);
    if(!empty($user_token_cap_per_day))
    {
        $user_token_cap_per_day = intval($user_token_cap_per_day);
    }
	$user_id = sanitize_text_field($_POST['user_id']);
	$image_size = $_POST['image_size'];
	$instruction = $_POST['instruction'];
    $sizes = array('1024x1024', '512x512', '256x256');
    if(!in_array($image_size, $sizes))
    {
        aiomatic_log_to_file('Invalid image size provided: ' . $image_size);
        $response_text = 'Failed to generate image, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
    {
        aiomatic_log_to_file('You need to insert a valid OpenAI/AiomaticAPI API Key for this to work!');
        $response_text = 'Failed to generate image, please try again later!';
        echo $response_text;
        wp_die();
    }
    $used_token_count = 0;
    if(is_numeric($user_token_cap_per_day))
    {
        if(empty($user_id) || $user_id == 0)
        {
            $response_text = sprintf( wp_kses( __( 'You are not allowed to access this form if you are not logged in. Please <a href="%s" target="_blank">log in</a> to continue.', 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), wp_login_url(get_permalink()) );
            echo $response_text;
            wp_die();
        }
        $used_token_count = get_user_meta($user_id, 'aiomatic_used_image_tokens', true);
        if($used_token_count !== '' && $used_token_count !== false && is_numeric($used_token_count))
        {
            $used_token_count = intval($used_token_count);
            if($used_token_count > $user_token_cap_per_day)
            {
                $response_text = 'Daily token count for your user account was exceeded! Please try again tomorrow.';
                echo $response_text;
                wp_die();
            }
        }
        else
        {
            $used_token_count = 0;
        }
    }
	$appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
    $appids = array_filter($appids);
    $token = $appids[array_rand($appids)];
	$aierror = '';
    $response_text = aiomatic_generate_ai_image($token, 1, $instruction, $image_size, 'shortcodeImageForm', 0, $aierror);
    if($response_text !== false && is_array($response_text))
    {
        foreach($response_text as $tmpimg)
        {
            echo $tmpimg;
            wp_die();
        }
        $used_token_count = intval($used_token_count) + 1000;
        update_user_meta($user_id, 'aiomatic_used_image_tokens', $used_token_count);
    }
    aiomatic_log_to_file('Error occurred when calling image API: ' . $aierror . ' -- ' . print_r($response_text, true));
    $response_text = 'Failed to generate image, please try again later!';
	echo $response_text;
	wp_die();
}

add_action('wp_ajax_aiomatic_stable_image_ajax_submit', 'aiomatic_stable_image_submit');
add_action('wp_ajax_nopriv_aiomatic_stable_image_ajax_submit', 'aiomatic_stable_image_submit');

function aiomatic_stable_image_submit() 
{
    $response_text = 'Failed to generate image, please try again later!';
	check_ajax_referer('openai-ajax-nonce', 'nonce');
    if(!isset($_POST['image_size']) || !isset($_POST['instruction']))
    {
        aiomatic_log_to_file('Incomplete POST request for stable images: ' . print_r($_POST, true));
        $response_text = 'Failed to generate image, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $user_token_cap_per_day = sanitize_text_field($_POST['user_token_cap_per_day']);
    if(!empty($user_token_cap_per_day))
    {
        $user_token_cap_per_day = intval($user_token_cap_per_day);
    }
	$user_id = sanitize_text_field($_POST['user_id']);
	$image_size = $_POST['image_size'];
	$instruction = $_POST['instruction'];
    $sizes = array('1024x1024', '512x512');
    if(!in_array($image_size, $sizes))
    {
        aiomatic_log_to_file('Invalid image size provided: ' . $image_size);
        $response_text = 'Failed to generate image, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['stability_app_id']) || trim($aiomatic_Main_Settings['stability_app_id']) == '') 
    {
        aiomatic_log_to_file('You need to insert a valid Stability.AI API Key for this to work!');
        $response_text = 'Failed to generate image, please try again later!';
        echo $response_text;
        wp_die();
    }
    $used_token_count = 0;
    if(is_numeric($user_token_cap_per_day))
    {
        if(empty($user_id) || $user_id == 0)
        {
            $response_text = sprintf( wp_kses( __( 'You are not allowed to access this form if you are not logged in. Please <a href="%s" target="_blank">log in</a> to continue.', 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), wp_login_url(get_permalink()) );
            echo $response_text;
            wp_die();
        }
        $used_token_count = get_user_meta($user_id, 'aiomatic_used_stable_image_tokens', true);
        if($used_token_count !== '' && $used_token_count !== false && is_numeric($used_token_count))
        {
            $used_token_count = intval($used_token_count);
            if($used_token_count > $user_token_cap_per_day)
            {
                $response_text = 'Daily token count for your user account was exceeded! Please try again tomorrow.';
                echo $response_text;
                wp_die();
            }
        }
        else
        {
            $used_token_count = 0;
        }
    }
    if($image_size == '512x512')
    {
        $width = '512';
        $height = '512';
    }
    elseif($image_size == '1024x1024')
    {
        $width = '1024';
        $height = '1024';
    }
    else
    {
        $width = '512';
        $height = '512';
    }
    $temp_get_imgs = aiomatic_generate_stability_image($instruction, $height, $width, 'shortcodeChatStableImage', 0, true);
    if($temp_get_imgs !== false)
    {
        $used_token_count = intval($used_token_count) + 1000;
        update_user_meta($user_id, 'aiomatic_used_stable_image_tokens', $used_token_count);
        echo $temp_get_imgs;
        wp_die();
    }
    aiomatic_log_to_file('Error occurred when calling image API!');
    $response_text = 'Failed to generate image, please try again later!';
	echo $response_text;
	wp_die();
}

// Add a shortcode to the WordPress editor
add_shortcode('aiomatic-chat-form', 'aiomatic_chat_shortcode');

// The shortcode function that displays the form
function aiomatic_chat_shortcode($atts) {
    $atts = shortcode_atts( array(
        'temperature' => '0.8',
        'top_p' => '1',
        'presence_penalty' => '0',
        'frequency_penalty' => '0',
        'model' => 'text-davinci-003',
        'instant_response' => 'false',
        'chat_preppend_text' => '',
        'user_message_preppend' => '',
        'ai_message_preppend' => '',
        'ai_first_message' => '',
        'chat_mode' => '',
        'user_token_cap_per_day' => '',
        'persistent' => '',
        'prompt_templates' => '',
        'prompt_editable' => ''
    ), $atts );

    $return_me = '';
    //accessing the parameters like this
    $temp = $atts['temperature'];
    $top_p = $atts['top_p'];
    $presence = $atts['presence_penalty'];
    $frequency = $atts['frequency_penalty'];
    $model = $atts['model'];
    $instant_response = $atts['instant_response'];
    $chat_preppend_text = $atts['chat_preppend_text'];
    $user_message_preppend = $atts['user_message_preppend'];
    $ai_message_preppend = $atts['ai_message_preppend'];
    $ai_first_message = $atts['ai_first_message'];
    $chat_mode = $atts['chat_mode'];
    $user_token_cap_per_day = $atts['user_token_cap_per_day'];
    $persistent = $atts['persistent'];
    $prompt_templates = $atts['prompt_templates'];
    $prompt_editable = $atts['prompt_editable'];
    $user_id = '0';
    $chat_history = '';
    if(!empty($user_token_cap_per_day) || ($persistent != 'off' && $persistent != '0' && $persistent != ''))
    {
        $user_id = get_current_user_id();
        if(($persistent != 'off' && $persistent != '0' && $persistent != '') && $user_id != 0)
        {
            $chat_history = get_user_meta($user_id, 'aiomatic_chat_history_' . $persistent, true);
            if(empty($chat_history))
            {
                $chat_history = '';
            }
        }
    }
    if (!wp_style_is( 'fontawesome', 'enqueued' )) {
        wp_register_style( 'fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/6.2.1/css/font-awesome.min.css', false, '6.2.1' );
        wp_enqueue_style( 'fontawesome' );
    } 
    if($chat_mode == 'images' || $chat_mode == 'image')
    {
        wp_enqueue_script('openai-chat-images-ajax', plugins_url('scripts/openai-chat-images-ajax.js', __FILE__), array('jquery'));
        wp_localize_script('openai-chat-images-ajax', 'aiomatic_chat_image_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('openai-ajax-images-nonce'),
            'persistent' => $persistent,
            'persistentnonce' => wp_create_nonce('openai-persistent-nonce'),
            'user_token_cap_per_day' => $user_token_cap_per_day,
            'user_id' => $user_id,
        ));
        wp_enqueue_style('css-ai-front', plugins_url('styles/form-front.css', __FILE__));
        // Display the form
        $return_me .= '
            <form id="openai-ai-image-form" method="post">
                <div class="form-group">
                    <div id="aiomatic_chat_history" class="ai-chat form-control">';
                    if($chat_history != '')
                    {
                        $return_me .= $chat_history;
                    }
                    else
                    {
                        if($ai_first_message != '')
                        {
                            $return_me .= '<div class="ai-bubble ai-other">' . $ai_first_message . '</div>';
                        }
                    }
                    $return_me .= '</div>';
                    $return_me .= '<textarea id="aiomatic_image_chat_input" rows="2" class="chat-form-control" placeholder="Enter your image chat query here"';
                    if($prompt_editable == 'no' || $prompt_editable == '0' || $prompt_editable == 'disabled' || $prompt_editable == 'disable' || $prompt_editable == "false")
                    {
                        $return_me .= ' disabled';
                    }
                    $return_me .= '></textarea>';
                    if($prompt_templates != '')
                    {
                        $predefined_prompts_arr = explode(';', $prompt_templates);
                        $return_me .= '<select id="aiomatic_image_chat_templates" class="cr_width_full">';
                        $return_me .= '<option disabled selected>' . esc_html__("Please select a prompt", 'aiomatic-automatic-ai-content-writer') . '</option>';
                        foreach($predefined_prompts_arr as $sval)
                        {
                            $ppro = explode('|~|~|', $sval);
                            if(isset($ppro[1]))
                            {
                                $return_me .= '<option value="' . esc_attr($ppro[1]) . '">' . esc_html($ppro[0]) . '</option>';
                            }
                            else
                            {
                                $return_me .= '<option value="' . esc_attr($sval) . '">' . esc_html($sval) . '</option>';
                            }
                        }
                        $return_me .= '</select>';
                    }        
                    $return_me .= '</div>
                <button type="button" id="aiimagechatsubmitbut" onclick="openaiimagechatfunct()" class="btn btn-primary">Submit</button>
                <div id="openai-image-chat-response"></div>
            </form> 
        ';
    }
    else
    {
        wp_enqueue_script('openai-chat-ajax', plugins_url('scripts/openai-chat-ajax.js', __FILE__), array('jquery'));
        if(stristr($chat_preppend_text, '%%') !== false)
        {
            $post_link = '';
            $post_title = '';
            $blog_title = html_entity_decode(get_bloginfo('title'));
            $post_excerpt = '';
            $final_content = '';
            $user_name = '';
            $featured_image = '';
            $post_cats = '';
            $post_tagz = '';
            $postID = '';
            global $post;
            if(isset($post->ID))
            {
                $post_link = get_permalink($post->ID);
                $blog_title       = html_entity_decode(get_bloginfo('title'));
                $author_obj       = get_user_by('id', $post->post_author);
                $user_name        = $author_obj->user_nicename;
                $final_content = $post->post_content;
                $post_title    = $post->post_title;
                $featured_image   = '';
                wp_suspend_cache_addition(true);
                $metas = get_post_custom($post->ID);
                wp_suspend_cache_addition(false);
                if(is_array($metas))
                {
                    $rez_meta = aiomatic_preg_grep_keys('#.+?_featured_ima?ge?#i', $metas);
                }
                else
                {
                    $rez_meta = array();
                }
                if(count($rez_meta) > 0)
                {
                    foreach($rez_meta as $rm)
                    {
                        if(isset($rm[0]) && filter_var($rm[0], FILTER_VALIDATE_URL))
                        {
                            $featured_image = $rm[0];
                            break;
                        }
                    }
                }
                if($featured_image == '')
                {
                    $featured_image = aiomatic_generate_thumbmail($post->ID);;
                }
                if($featured_image == '' && $final_content != '')
                {
                    $dom     = new DOMDocument();
                    $internalErrors = libxml_use_internal_errors(true);
                    $dom->loadHTML($final_content);
                    libxml_use_internal_errors($internalErrors);
                    $tags      = $dom->getElementsByTagName('img');
                    foreach ($tags as $tag) {
                        $temp_get_img = $tag->getAttribute('src');
                        if ($temp_get_img != '') {
                            $temp_get_img = strtok($temp_get_img, '?');
                            $featured_image = rtrim($temp_get_img, '/');
                        }
                    }
                }
                $post_cats = '';
                $post_categories = wp_get_post_categories( $post->ID );
                foreach($post_categories as $c){
                    $cat = get_category( $c );
                    $post_cats .= $cat->name . ',';
                }
                $post_cats = trim($post_cats, ',');
                if($post_cats != '')
                {
                    $post_categories = explode(',', $post_cats);
                }
                else
                {
                    $post_categories = array();
                }
                if(count($post_categories) == 0)
                {
                    $terms = get_the_terms( $post->ID, 'product_cat' );
                    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                        foreach ( $terms as $term ) {
                            $post_categories[] = $term->slug;
                        }
                        $post_cats = implode(',', $post_categories);
                    }
                    
                }
                $post_tagz = '';
                $post_tags = wp_get_post_tags( $post->ID );
                foreach($post_tags as $t){
                    $post_tagz .= $t->name . ',';
                }
                $post_tagz = trim($post_tagz, ',');
                if($post_tagz != '')
                {
                    $post_tags = explode(',', $post_tagz);
                }
                else
                {
                    $post_tags = array();
                }
                if(count($post_tags) == 0)
                {
                    $terms = get_the_terms( $post->ID, 'product_tag' );
                    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
                        foreach ( $terms as $term ) {
                            $post_tags[] = $term->slug;
                        }
                        $post_tagz = implode(',', $post_tags);
                    }
                    
                }
                $post_excerpt = $post->post_excerpt;
                $postID = $post->ID;
            }
            $chat_preppend_text = replaceAIPostShortcodes($chat_preppend_text, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, '', '');
            if (filter_var($chat_preppend_text, FILTER_VALIDATE_URL) !== false && aiomatic_endsWith($chat_preppend_text, '.txt'))
            {
                $txt_content = aiomatic_get_web_page($chat_preppend_text);
                if ($txt_content !== FALSE) 
                {
                    $txt_content = preg_split('/\r\n|\r|\n/', $txt_content);
                    $txt_content = array_filter($txt_content);
                    if(count($txt_content) > 0)
                    {
                        $txt_content = $txt_content[array_rand($txt_content)];
                        if(trim($txt_content) != '') 
                        {
                            $chat_preppend_text = $txt_content;
                            $chat_preppend_text = replaceAIPostShortcodes($chat_preppend_text, $post_link, $post_title, $blog_title, $post_excerpt, $final_content, $user_name, $featured_image, $post_cats, $post_tagz, $postID, '', '');
                        }
                    }
                }
            }
        }
        wp_localize_script('openai-chat-ajax', 'aiomatic_chat_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('openai-ajax-nonce'),
            'model' => $model,
            'temp' => $temp,
            'top_p' => $top_p,
            'presence' => $presence,
            'frequency' => $frequency,
            'instant_response' => $instant_response,
            'chat_preppend_text' => $chat_preppend_text,
            'user_message_preppend' => $user_message_preppend,
            'ai_message_preppend' => $ai_message_preppend,
            'user_token_cap_per_day' => $user_token_cap_per_day,
            'user_id' => $user_id,
            'persistent' => $persistent,
            'persistentnonce' => wp_create_nonce('openai-persistent-nonce')
        ));
        wp_enqueue_style('css-ai-front', plugins_url('styles/form-front.css', __FILE__));
        $all_models = aiomatic_get_all_models();
        $models = $all_models; 
        if($model != 'default' && !in_array($model, $models))
        {
            $return_me .= 'Invalid model provided!';
            return $return_me;
        }
        if($temp != 'default' && floatval($temp) < 0 || floatval($temp) > 1)
        {
            $return_me .= 'Invalid temperature provided!';
            return $return_me;
        }
        if($top_p != 'default' && floatval($top_p) < 0 || floatval($top_p) > 1)
        {
            $return_me .= 'Invalid top_p provided!';
            return $return_me;
        }
        if($presence != 'default' && floatval($presence) < -2 || floatval($presence) > 2)
        {
            $return_me .= 'Invalid presence_penalty provided!';
            return $return_me;
        }
        if($frequency != 'default' && floatval($frequency) < -2 || floatval($frequency) > 2)
        {
            $return_me .= 'Invalid frequency_penalty provided!';
            return $return_me;
        }
        // Display the form
        $return_me .= '
            <form id="openai-ai-form" method="post">
                <div class="form-group">
                    <div id="aiomatic_chat_history" class="ai-chat form-control">';
        if($chat_history != '')
        {
            $return_me .= $chat_history;
        }
        else
        {
            if($ai_first_message != '')
            {
                $return_me .= '<div class="ai-bubble ai-other">' . $ai_first_message . '</div>';
            }
        }
        $return_me .= '</div>';
        $return_me .= '<textarea id="aiomatic_chat_input" rows="2" class="chat-form-control" placeholder="Enter your chat message here"';
        if($prompt_editable == 'no' || $prompt_editable == '0' || $prompt_editable == 'disabled' || $prompt_editable == 'disable' || $prompt_editable == "false")
        {
            $return_me .= ' disabled';
        }
        $return_me .= '></textarea>';
        if($prompt_templates != '')
        {
            $predefined_prompts_arr = explode(';', $prompt_templates);
            $return_me .= '<select id="aiomatic_chat_templates" class="cr_width_full">';
            $return_me .= '<option disabled selected>' . esc_html__("Please select a prompt", 'aiomatic-automatic-ai-content-writer') . '</option>';
            foreach($predefined_prompts_arr as $sval)
            {
                $ppro = explode('|~|~|', $sval);
                if(isset($ppro[1]))
                {
                    $return_me .= '<option value="' . esc_attr($ppro[1]) . '">' . esc_html($ppro[0]) . '</option>';
                }
                else
                {
                    $return_me .= '<option value="' . esc_attr($sval) . '">' . esc_html($sval) . '</option>';
                }
            }
            $return_me .= '</select>';
        }        
        if($model == 'default' || $model == '')
        {
            $return_me .= '<label for="model-chat-selector">Model:</label><select class="aiomatic-ai-input" id="model-chat-selector">';
            foreach ($models as $model) {
                $return_me .= "<option value='" . $model . "'>" . $model . "</option>";
            }
            $return_me .= '</select>';
        }
        if($temp == 'default' || $temp == '')
        {
            $return_me .= '<label for="temperature-chat-input">Temperature:</label><input type="number" min="0" step="0.1" max="1" class="aiomatic-ai-input" id="temperature-chat-input" name="temperature" value="1">';
        }
        if($top_p == 'default' || $top_p == '')
        {
            $return_me .= '<label for="top_p-chat-input">Top_p:</label><input type="number" min="0" step="0.1" max="1" class="aiomatic-ai-input" id="top_p-chat-input" name="top_p" value="1">';
        }
        if($presence == 'default' || $presence == '')
        {
            $return_me .= '<label for="presence-chat-input">Presence Penalty:</label><input type="number" min="0" step="0.1" max="1" class="aiomatic-ai-input" id="presence-chat-input" name="presence" value="0">';
        }
        if($frequency == 'default' || $frequency == '')
        {
            $return_me .= '<label for="frequency-chat-input">Frequency Penalty:</label><input type="number" min="0" step="0.1" max="1" class="aiomatic-ai-input" id="frequency-chat-input" name="frequency" value="0">';
        }
        $return_me .= '</div>
                <button type="button" id="aichatsubmitbut" onclick="openaichatfunct()" class="btn btn-primary">Submit</button>
                <div id="openai-chat-response"></div>
            </form> 
        ';
    }
    return $return_me;
}

add_action('wp_ajax_aiomatic_chat_submit', 'aiomatic_chat_submit');
add_action('wp_ajax_nopriv_aiomatic_chat_submit', 'aiomatic_chat_submit');

function aiomatic_chat_submit() {
	check_ajax_referer('openai-ajax-nonce', 'nonce');
    if(!isset($_POST['input_text']) || !isset($_POST['model']) || !isset($_POST['temp']) || !isset($_POST['presence']) || !isset($_POST['frequency']) || !isset($_POST['remember_string']))
    {
        aiomatic_log_to_file('Incomplete POST request for chat: ' . print_r($_POST, true));
        $response_text = 'Failed to generate image, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $user_token_cap_per_day = sanitize_text_field($_POST['user_token_cap_per_day']);
    if(!empty($user_token_cap_per_day))
    {
        $user_token_cap_per_day = intval($user_token_cap_per_day);
    }
	$user_id = sanitize_text_field($_POST['user_id']);
	$input_text = $_POST['input_text'];
	$remember_string = $_POST['remember_string'];
    if(!empty(trim($remember_string)))
    {
        $input_text = trim($remember_string) . PHP_EOL . $input_text;
    }
	$model = sanitize_text_field($_POST['model']);
	$temperature = sanitize_text_field($_POST['temp']);
	$top_p = sanitize_text_field($_POST['top_p']);
	$presence_penalty = sanitize_text_field($_POST['presence']);
	$frequency_penalty = sanitize_text_field($_POST['frequency']);
    $all_models = aiomatic_get_all_models();
    $models = $all_models;
    if(!in_array($model, $models))
    {
        aiomatic_log_to_file('Invalid model provided: ' . $model);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $temperature = floatval($temperature);
    $top_p = floatval($top_p);
    $presence_penalty = floatval($presence_penalty);
    $frequency_penalty = floatval($frequency_penalty);
    if($temperature < 0 || $temperature > 1)
    {
        aiomatic_log_to_file('Invalid temperature provided: ' . $temperature);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    if($top_p < 0 || $top_p > 1)
    {
        aiomatic_log_to_file('Invalid top_p provided: ' . $top_p);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    if($presence_penalty < -2 || $presence_penalty > 2)
    {
        aiomatic_log_to_file('Invalid presence_penalty provided: ' . $presence_penalty);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    if($frequency_penalty < -2 || $frequency_penalty > 2)
    {
        aiomatic_log_to_file('Invalid frequency_penalty provided: ' . $frequency_penalty);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
    {
        aiomatic_log_to_file('You need to insert a valid OpenAI/AiomaticAPI API Key for this to work!');
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
        wp_die();
    }
    $used_token_count = 0;
    if(is_numeric($user_token_cap_per_day))
    {
        if(empty($user_id) || $user_id == 0)
        {
            $response_text = sprintf( wp_kses( __( 'You are not allowed to access this form if you are not logged in. Please <a href="%s" target="_blank">log in</a> to continue.', 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), wp_login_url(get_permalink()) );
            echo $response_text;
            wp_die();
        }
        $used_token_count = get_user_meta($user_id, 'aiomatic_used_chat_tokens', true);
        if($used_token_count !== '' && $used_token_count !== false && is_numeric($used_token_count))
        {
            $used_token_count = intval($used_token_count);
            if($used_token_count > $user_token_cap_per_day)
            {
                $response_text = 'Daily token count for your user account was exceeded! Please try again tomorrow.';
                echo $response_text;
                wp_die();
            }
        }
        else
        {
            $used_token_count = 0;
        }
    }
	$appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
    $appids = array_filter($appids);
    $token = $appids[array_rand($appids)];
    $max_tokens = 2048;
    if(strstr($model, 'davinci') !== false && strstr($model, ':ft-') === false)
    {
        $max_tokens = 4000;
    }
    $query_token_count = count(aiomatic_encode($input_text));
    $available_tokens = $max_tokens - $query_token_count;
    if($available_tokens <= 16)
    {
        $string_len = strlen($input_text);
        $string_len = $string_len / 2;
        $string_len = intval(0 - $string_len);
        $input_text = substr($input_text, 0, $string_len);
        $input_text = trim($input_text);
        if(empty($input_text))
        {
            aiomatic_log_to_file('Empty API seed expression provided (after processing)');
            return '';
        }
        $query_token_count = count(aiomatic_encode($input_text));
        $available_tokens = $max_tokens - $query_token_count;
    }
	$error = '';
    $finish_reason = '';
    $response_text = aiomatic_generate_text($token, $model, $input_text, $available_tokens, $temperature, $top_p, $presence_penalty, $frequency_penalty, true, 'shortcodeChat', 0, $finish_reason, $error);
    if($response_text === false)
    {
        aiomatic_log_to_file('Error occurred when calling API in chat: ' . $error);
        $response_text = 'Failed to generate content, please try again later!';
    }
    else
    {
        $inp_count = count(aiomatic_encode($input_text));
        $resp_count = count(aiomatic_encode($response_text));
        $used_token_count = intval($used_token_count) + $inp_count + $resp_count;
        update_user_meta($user_id, 'aiomatic_used_chat_tokens', $used_token_count);
    }
	echo trim(stripslashes($response_text));
	wp_die();
}

function aiomatic_starts_with($newx_url, $query)
{
    if(substr( $newx_url, 0, strlen($query) ) === $query)
    {
        return true;
    }
    return false;
}
add_action('wp_ajax_aiomatic_image_chat_submit', 'aiomatic_image_chat_submit');
add_action('wp_ajax_nopriv_aiomatic_image_chat_submit', 'aiomatic_image_chat_submit');

function aiomatic_image_chat_submit() 
{
    $echo_ok = false;
	check_ajax_referer('openai-ajax-images-nonce', 'nonce');
    if(!isset($_POST['input_text']))
    {
        aiomatic_log_to_file('Incomplete POST request for image chat: ' . print_r($_POST, true));
        $response_text = 'Failed to generate image, please try again later!';
        echo $response_text;
	    wp_die();
    }
    $user_token_cap_per_day = sanitize_text_field($_POST['user_token_cap_per_day']);
    if(!empty($user_token_cap_per_day))
    {
        $user_token_cap_per_day = intval($user_token_cap_per_day);
    }
	$user_id = sanitize_text_field($_POST['user_id']);
	$input_text = $_POST['input_text'];
    $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
    if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
    {
        aiomatic_log_to_file('You need to insert a valid OpenAI/AiomaticAPI API Key for this to work!');
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
        wp_die();
    }
    $used_token_count = 0;
    if(is_numeric($user_token_cap_per_day))
    {
        if(empty($user_id) || $user_id == 0)
        {
            $response_text = sprintf( wp_kses( __( 'You are not allowed to access this form if you are not logged in. Please <a href="%s" target="_blank">log in</a> to continue.', 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), wp_login_url(get_permalink()) );
            echo $response_text;
            wp_die();
        }
        $used_token_count = get_user_meta($user_id, 'aiomatic_used_image_chat_tokens', true);
        if($used_token_count !== '' && $used_token_count !== false && is_numeric($used_token_count))
        {
            $used_token_count = intval($used_token_count);
            if($used_token_count > $user_token_cap_per_day)
            {
                $response_text = 'Daily token count for your user account was exceeded! Please try again tomorrow.';
                echo $response_text;
                wp_die();
            }
        }
        else
        {
            $used_token_count = 0;
        }
    }
	$appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
    $appids = array_filter($appids);
    $token = $appids[array_rand($appids)];
	$error = '';
    $image_size = '512x512';
    $response_text = aiomatic_generate_ai_image($token, 1, $input_text, $image_size, 'shortcodeImageChat', 0, $aierror);
    if($response_text === false)
    {
        aiomatic_log_to_file('Error occurred when calling API in image chat: ' . $error);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
        wp_die();
    }
    else
    {
        foreach($response_text as $tmpimg)
        {
            echo '<a href="' . $tmpimg . '" target="_blank"><img src="' . $tmpimg . '"></a>';
            $echo_ok = true;
        }
        $used_token_count = intval($used_token_count) + 1000;
        update_user_meta($user_id, 'aiomatic_used_image_chat_tokens', $used_token_count);
    }
    if($echo_ok === false)
    {
        aiomatic_log_to_file('No image returned from API call: ' . $input_text);
        $response_text = 'Failed to generate content, please try again later!';
        echo $response_text;
    }
	wp_die();
}

add_action('wp_ajax_aiomatic_user_meta_save', 'aiomatic_user_meta_save');
add_action('wp_ajax_nopriv_aiomatic_user_meta_save', 'aiomatic_user_meta_save');

function aiomatic_user_meta_save() 
{
	check_ajax_referer('openai-persistent-nonce', 'nonce');
    if(!isset($_POST['x_input_text']))
    {
        aiomatic_log_to_file('Failed to save persistent conversation, no x_input_text: ' . print_r($_POST, true));
	    wp_die();
    }
    if(!isset($_POST['user_id']))
    {
        aiomatic_log_to_file('Failed to save persistent conversation, no user_id: ' . print_r($_POST, true));
	    wp_die();
    }
	$user_id = sanitize_text_field($_POST['user_id']);
    if(!isset($_POST['persistent']))
    {
        aiomatic_log_to_file('Failed to save persistent conversation, no persistentid: ' . print_r($_POST, true));
	    wp_die();
    }
	$persistent = sanitize_text_field($_POST['persistent']);
    if(empty($user_id) || $user_id == 0)
    {
        aiomatic_log_to_file('Failed to save persistent conversation, user_id is not valid: ' . print_r($_POST, true));
	    wp_die();
    }
	$x_input_text = $_POST['x_input_text'];
    if(!empty($x_input_text))
    {
        update_user_meta($user_id, 'aiomatic_chat_history_' . $persistent, $x_input_text);
    }
	wp_die();
}
?>
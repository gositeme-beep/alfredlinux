<?php
/**
 * The main template file.
 *
 * @package WordPress
 */

/**
*	Get Current page object
**/
$page = get_page($post->ID);
$current_page_id = '';

if(isset($page->ID))
{
    $current_page_id = $page->ID;
}

//Check if gallery template
global $photography_page_gallery_id;
if(!empty($photography_page_gallery_id))
{
	$current_page_id = $photography_page_gallery_id;
}

//Check if password protected
get_template_part("/templates/template-password");

//important to apply dynamic header & footer style
global $photography_homepage_style;
$photography_homepage_style = 'fullscreen';

get_header(); 

//Get gallery images
$all_photo_arr = get_post_meta($current_page_id, 'wpsimplegallery_gallery', true);

//Get global gallery sorting
$all_photo_arr = photography_resort_gallery_img($all_photo_arr);
$count_photo = count($all_photo_arr);

$images_array = array();

if(!empty($all_photo_arr))
{
	foreach($all_photo_arr as $photo_id)
	{
	    $image_url = wp_get_attachment_image_src($photo_id, 'original', true);
	    $images_array[] = $image_url[0];
	}
}

wp_enqueue_script("photography-kenburns", get_template_directory_uri()."/js/kenburns.js", false, THEMEVERSION, true);
wp_register_script("photography-kenburns-gallery", get_template_directory_uri()."/js/custom/kenburns.js", false, THEMEVERSION, true);	
$params = array(
  'images' => json_encode($images_array),
);

wp_localize_script("photography-kenburns-gallery", 'tgKenburnsParams', $params );
wp_enqueue_script("photography-kenburns-gallery", get_template_directory_uri()."/js/custom/kenburns.js", false, THEMEVERSION, true);

//Get timer setting				
$tg_kenburns_timer = kirki_get_option('tg_kenburns_timer');

if(empty($tg_kenburns_timer))
{
	$tg_kenburns_timer = 5000;
}
else
{
	$tg_kenburns_timer = $tg_kenburns_timer*1000;
}

//Get zoom level
$tg_kenburns_zoom = kirki_get_option('tg_kenburns_zoom');
if(empty($tg_kenburns_zoom))
{
	$tg_kenburns_zoom = 1.1;
}
else
{
	$tg_kenburns_zoom = 1+($tg_kenburns_zoom/10);
}

//Get transition speed
$tg_kenburns_trans = kirki_get_option('tg_kenburns_trans');
if(empty($tg_kenburns_trans))
{
	$tg_kenburns_trans = 1000;
}
?>
<div id="kenburns_overlay"></div>
<canvas id="kenburns" data-timer="<?php echo esc_attr($tg_kenburns_timer); ?>" data-zoom="<?php echo esc_attr($tg_kenburns_zoom); ?>" data-trans="<?php echo esc_attr($tg_kenburns_trans); ?>">
    <p><?php esc_html_e('Your browser doesn\'t support canvas!', 'photography' ); ?></p>
</canvas>

<?php
	get_footer();
?>
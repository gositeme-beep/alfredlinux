<?php
/**
 * The main template file for display portfolio page.
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

//important to apply dynamic header and footer style
global $photography_homepage_style;
$photography_homepage_style = 'flow';

get_header(); 

//Run flow gallery data
wp_enqueue_script("photography-ppflip", get_template_directory_uri()."/js/jquery.ppflip.js", false, THEMEVERSION, true);
wp_enqueue_script("photography-touchwipe", get_template_directory_uri()."/js/jquery.touchwipe.1.1.1.js", false, THEMEVERSION, true);

wp_register_script("photography-script-flow-gallery", get_template_directory_uri()."/js/custom/flow-gallery.js", false, THEMEVERSION, true);
   
$params = array(
  'ajaxurl' => admin_url('admin-ajax.php'),
  'galleryID' => $current_page_id,
);

//Check if gallery cached XML file is available
$xml_cache_path = THEMEUPLOAD.'/flow-gallery-'.$current_page_id.'.xml';
				
if(file_exists($xml_cache_path))
{
	$params['cache'] = THEMEUPLOADURL.'flow-gallery-'.$current_page_id.'.xml';
}
else
{
	$params['cache'] = 0;
}

wp_localize_script('photography-script-flow-gallery', 'tgFlowParams', $params );
wp_enqueue_script("photography-script-flow-gallery", get_template_directory_uri()."/js/custom/flow-gallery.js", false, THEMEVERSION, true);
?>

</div>

<a id="imgflow-prevslide" class="load-item"></a>
<a id="imgflow-nextslide" class="load-item"></a>

<div id="imageFlow">
	<div class="text">
		<div class="title"></div>
		<div class="legend"></div>
	</div>
</div>

<?php
	$tg_flow_enable_reflection = kirki_get_option('tg_flow_enable_reflection');
?>
<input type="hidden" id="tg_flow_enable_reflection" name="tg_flow_enable_reflection" value="<?php echo esc_attr($tg_flow_enable_reflection); ?>"/>

<?php
	$tg_flow_enable_lightbox = kirki_get_option('tg_flow_enable_lightbox');
	
	if(!empty($tg_flow_enable_lightbox))
	{
?>
<div id="fancy_gallery" style="display:none;">
<?php
$all_photo_arr = get_post_meta($current_page_id, 'wpsimplegallery_gallery', true);

foreach($all_photo_arr as $key => $photo)
{
	$full_image_url = wp_get_attachment_image_src( $photo, 'original' );
	$thumb_image_url = wp_get_attachment_image_src( $photo, 'medium' );
	$image_caption = get_post_field('post_excerpt', $photo);
?>
<a data-rel="photography_gallery_<?php echo esc_attr($current_page_id); ?>" id="fancy_gallery<?php echo esc_attr($key); ?>" href="<?php echo esc_url($full_image_url[0]); ?>" class="fancy-gallery" data-thumb="<?php echo esc_url($thumb_image_url[0]); ?>" data-key="<?php echo intval($key); ?>" <?php echo photography_get_lightbox_caption_attr($photo); ?>></a>
<?php
}
?>
</div>
<?php
	}
?>

<?php
	//important to apply dynamic footer style
	$photography_homepage_style = 'flow';
	
	get_footer();
?>
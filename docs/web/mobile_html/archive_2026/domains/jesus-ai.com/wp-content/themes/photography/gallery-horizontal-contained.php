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

//Get gallery images
$all_photo_arr = get_post_meta($current_page_id, 'wpsimplegallery_gallery', true);

//Get global gallery sorting
$all_photo_arr = photography_resort_gallery_img($all_photo_arr);

global $photography_homepage_style;
$photography_homepage_style = 'horizontal';

get_header();

wp_enqueue_script("photography-horizontal-gallery", get_template_directory_uri()."/js/horizontal_gallery.js", false, THEMEVERSION, true);

global $photography_topbar;

//Get gallery header
get_template_part("/templates/template-gallery-header");
?>

<!-- Begin content -->
<div class="transparent horizontal">
	<div id="horizontal_gallery">
	<table id="horizontal_gallery_wrapper">
	<tbody><tr>
	<?php
	    foreach($all_photo_arr as $photo_id)
		{
		    $small_image_url = '';
		    $hyperlink_url = get_permalink($photo_id);
		    $thumb_image_url = '';
		    
		    if(!empty($photo_id))
		    {
		    	$image_url = wp_get_attachment_image_src($photo_id, 'original', true);
		    }
		    
		    //Get image meta data
		    $image_caption = get_post_field('post_excerpt', $photo_id);
		    $image_alt = get_post_meta($photo_id, '_wp_attachment_image_alt', true);
	?>
	<td>
	    <?php 
	    	if(isset($image_url[0]) && !empty($image_url[0]))
	    	{
	    		//Check if enable horizontal gallery lightbox
	    		$tg_lightbox_enable_horizontal_gallery = kirki_get_option('tg_lightbox_enable_horizontal_gallery');
	    ?>
	    <?php
	    	if(!empty($tg_lightbox_enable_horizontal_gallery))
	    	{
	    ?>
	    	<a data-rel="photography_gallery_<?php echo esc_attr($current_page_id); ?>" <?php echo photography_get_lightbox_caption_attr($photo_id); ?> class="fancy-gallery" href="<?php echo esc_url($image_url[0]); ?>">
	    <?php
	    	}
	    ?>
	    	<div class="gallery_image_wrapper">
		    	<img src="<?php echo esc_url($image_url[0]); ?>" alt="<?php echo esc_attr($image_alt); ?>" class="horizontal_gallery_img"/>
	    	</div>
	    <?php
	    	if(!empty($tg_lightbox_enable_horizontal_gallery))
	    	{
	    ?>
	    	</a>
	    <?php
	    	}		
	    ?>
	    <?php
	    	}		
	    ?>
	    <div class="wp-caption aligncenter">
		    <p class="wp-caption-text"><?php echo esc_html($image_caption); ?></p>
		    <?php
		    	//Get image purchase URL
		    	$photography_purchase_url = get_post_meta($photo_id, 'photography_purchase_url', true);
		    	
		    	if(!empty($photography_purchase_url))
		    	{
		    ?>
		    <a href="<?php echo esc_url($photography_purchase_url); ?>" class="button ghost"><i class="fa fa-shopping-cart marginright"></i><?php esc_html_e('Purchase', 'photography' ); ?></a>
		    <?php
		    	}
		    ?>
	    </div>
	</td>
	
	<?php
	    }
	?>
	</tr></tbody>
	</table>
	
	</div>
</div>
</div>
<?php
	get_footer();
?>
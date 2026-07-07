<?php
/**
 * The main template file for display gallery page.
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
global $photograhy_page_gallery_id;
if(!empty($photograhy_page_gallery_id))
{
	$current_page_id = $photograhy_page_gallery_id;
}

//Check if password protected
get_template_part("/templates/template-password");

//Get gallery images
$all_photo_arr = get_post_meta($current_page_id, 'wpsimplegallery_gallery', true);

//Get global gallery sorting
$all_photo_arr = photography_resort_gallery_img($all_photo_arr);

get_header();

global $photography_page_content_class;
$photography_page_content_class = 'wide';

global $photography_topbar;

//Get gallery header
get_template_part("/templates/template-gallery-header");

//Register javascript
wp_enqueue_script('masonry');
wp_enqueue_script("photography-script-gallery-metro", get_template_directory_uri()."/js/custom/gallery-mixed-masonry.js", false, THEMEVERSION, true);
?>

<div class="inner">

	<div class="inner_wrapper nopadding">
	
	<div id="page_main_content" class="sidebar_content full_width nopadding fixed_column">
	
	<div id="portfolio_mixed_filter_wrapper" class="portfolio_mixed_filter_wrapper gallery three_cols portfolio-content section content clearfix wide" data-columns="3" data-gutter="0">
	
	<?php
		$tg_full_image_caption = kirki_get_option('tg_full_image_caption');
		
		$large_counter = 1;
		$next_number_to_add = 4;
		$next_trigger = 1;
	
	    foreach($all_photo_arr as $key => $photo_id)
	    {
	        $small_image_url = '';
	        $image_url = '';
	        
	        //Calculated columns size
			$wrapper_class = 'three_cols normal_size';
			$grid_wrapper_class = 'classic3_cols normal_size';
			$column_class = 'one_third gallery3 normal_size';
			$photography_image_size = 'photography-gallery-grid';
			
			$large_counter_trigger = FALSE;
			
			if($next_trigger == $key+1)
			{
				$large_counter_trigger = TRUE;
				$next_trigger = $next_trigger+$next_number_to_add;
				
				if($next_number_to_add == 4)
				{
					$next_number_to_add = 2;
				}
				else if($next_number_to_add==2)
				{
					$next_number_to_add = 4;
				}
			}
			
			if($large_counter_trigger)
			{
				$wrapper_class = 'three_cols double_size';
				$grid_wrapper_class = 'classic3_cols double_size';
				$column_class = 'one_third gallery3 double_size';
				$photography_image_size = 'photography-gallery-grid-large';
			}
			
			$mobile_image_url = wp_get_attachment_image_src($photo_id, 'medium_large', true);
			$poster_image_url = wp_get_attachment_image_src($photo_id, 'medium', true);
			
			$large_counter++;
	        
	        if(!empty($photo_id))
	        {
	        	$image_url = wp_get_attachment_image_src($photo_id, 'original', true);
	        	$small_image_url = wp_get_attachment_image_src($photo_id, $photography_image_size, true);
	        }
	        
	        //Get image meta data
	        $image_caption = get_post_field('post_excerpt', $photo_id);
	        $image_alt = get_post_meta($photo_id, '_wp_attachment_image_alt', true);
	        
	        //Get image purchase URL
			$photography_purchase_url = get_post_meta($photo_id, 'photography_purchase_url', true);
			
			if(!empty($photography_purchase_url))
			{
			    $image_caption.= '<a href="'.esc_url($photography_purchase_url).'" class="button ghost"><i class="fa fa-shopping-cart marginright"></i>'.esc_html__('Purchase', 'photography' ).'</a>';
			}
	?>
	<div class="element grid <?php echo esc_attr($grid_wrapper_class); ?> <?php echo esc_attr(photography_get_hover_effect()); ?>">
	
		<div class="<?php echo esc_attr($column_class); ?> static filterable gallery_type animated<?php echo esc_attr($key+1); ?>" data-id="post-<?php echo esc_attr($key+1); ?>">
		
			<?php 
			    if(isset($image_url[0]) && !empty($image_url[0]))
			    {
			?>		
			    <a data-rel="photography_gallery_<?php echo esc_attr($current_page_id); ?>" <?php echo photography_get_lightbox_caption_attr($photo_id, true, $poster_image_url[0]); ?> href="<?php echo esc_url($image_url[0]); ?>" <?php echo photography_get_progressive_attr($photo_id, $small_image_url[0], $mobile_image_url[0], true, 'fancy-gallery'); ?>>
			        <img src="<?php echo esc_url($small_image_url[0]); ?>" alt="<?php echo esc_attr($image_alt); ?>" />
			    </a>
			<?php
			    }		
			?>
		
		</div>
		
	</div>
	<?php
		}
	?>
		
	</div>
	
	</div>

</div>
</div>

</div>
<?php get_footer(); ?>
<!-- End content -->
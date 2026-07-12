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

global $photography_topbar;

//Get gallery header
get_template_part("/templates/template-gallery-header");
?>

<!-- Begin content -->
    
<div class="inner">

	<div class="inner_wrapper nopadding">
	
	<div id="page_main_content" class="sidebar_content full_width nopadding fixed_column">
	
	<div id="portfolio_filter_wrapper" class="gallery one_col portfolio-content section content clearfix" data-columns="1">
	
	<?php
		$tg_lightbox_enable_caption = kirki_get_option('tg_lightbox_enable_caption');
	
	    foreach($all_photo_arr as $key => $photo_id)
	    {
	        $small_image_url = '';
	        $image_url = '';
	        
	        if(!empty($photo_id))
	        {
	        	$image_url = wp_get_attachment_image_src($photo_id, 'original', true);
	        	$small_image_url = wp_get_attachment_image_src($photo_id, 'original', true);
	        	$mobile_image_url = wp_get_attachment_image_src($photo_id, 'medium_large', true);
	        	$poster_image_url = wp_get_attachment_image_src($photo_id, 'medium', true);
	        }
	        
	        //Get image meta data
	        $image_alt = get_post_meta($photo_id, '_wp_attachment_image_alt', true);
	?>
	<div class="element grid classic1_cols <?php echo esc_attr(photography_get_hover_effect()); ?>">
	
		<div class="one gallery1 static filterable gallery_type animated<?php echo esc_attr($key+1); ?> fade-in" data-id="post-<?php echo esc_attr($key+1); ?>">
		
			<?php 
			    if(isset($image_url[0]) && !empty($image_url[0]))
			    {
			?>		
			    <a data-rel="photography_gallery_<?php echo esc_attr($current_page_id); ?>" <?php echo photography_get_lightbox_caption_attr($photo_id, true, $poster_image_url[0]); ?> href="<?php echo esc_url($image_url[0]); ?>" <?php echo photography_get_progressive_attr($photo_id, $small_image_url[0], $mobile_image_url[0], true, 'fancy-gallery'); ?>>
			        <img src="<?php echo esc_url(photography_get_progressive_preview_image($photo_id, 'original')); ?>" alt="<?php echo esc_attr($image_alt); ?>" class="preview" />
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
	
	<?php
	if (comments_open($post->ID)) 
	{
	?>
	<div class="standard_wrapper fullwidth_comment_wrapper gallery_comment_wrapper">
		<?php comments_template( '', true ); ?>
	</div>
	<?php
	}
	?>
	
	</div>

</div>
</div>
<br class="clear"/><br/>
</div>
<?php get_footer(); ?>
<!-- End content -->
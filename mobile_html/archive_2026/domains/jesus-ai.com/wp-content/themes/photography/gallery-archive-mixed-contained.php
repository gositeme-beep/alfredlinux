<?php
/**
 * Template Name: Gallery Archive Mixed Masonry Contained
 * The main template file for display gallery page.
 *
 * @package WordPress
*/

/**
*	Get Current page object
**/
$ob_page = get_page($post->ID);
$current_page_id = '';

if(isset($ob_page->ID))
{
    $current_page_id = $ob_page->ID;
}

get_header();

//Check if disable slideshow hover effect
$tg_gallery_hover_slide = kirki_get_option( "tg_gallery_hover_slide" );

//Get gallery archive gallery name style
$tg_gallery_archive_info_style = kirki_get_option( "tg_gallery_archive_info_style" );

if(!empty($tg_gallery_hover_slide))
{
	wp_enqueue_script("cycle", get_template_directory_uri()."/js/jquery.cycle2.min.js", false, THEMEVERSION, true);
	wp_enqueue_script("photography-custom-cycle", get_template_directory_uri()."/js/custom_cycle.js", false, THEMEVERSION, true);
}

//Register javascript
wp_enqueue_script('masonry');
wp_enqueue_script("photography-script-gallery-metro", get_template_directory_uri()."/js/custom/gallery-mixed-masonry.js", false, THEMEVERSION, true);

global $photography_screen_class;
$photography_screen_class = 'single_gallery';

//Include custom header feature
get_template_part("/templates/template-header");
?>

<!-- Begin content -->
<div class="inner">

	<div class="inner_wrapper nopadding">
	
	<div id="page_main_content" class="sidebar_content full_width nopadding fixed_column">
	
	<?php 
        if(empty($term) && have_posts()) 
		{
	?>
		 <div class="standard_wrapper">
	<?php
        while ( have_posts() ) : the_post(); ?>		
	        <?php the_content(); break;  ?>
    <?php endwhile; ?>
    </div>
    <?php
    }
    ?>
	
	<div id="portfolio_mixed_filter_wrapper" class="portfolio_mixed_filter_wrapper gallery three_cols portfolio-content section content clearfix" data-columns="3" data-gutter="30">
	
	<?php
	    //Get galleries
	    global $wp_query;
	    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
	    $pp_portfolio_items_page = -1;
	    
	    $query_string = 'paged='.$paged.'&orderby=menu_order&order=ASC&post_type=galleries&posts_per_page=-1&suppress_filters=0';
	    
	    if(!empty($term))
	    {
	        $query_string .= '&gallerycat='.$term;
	    }
	    
	    if(THEMEDEMO)
	    {
		    $query_string .= '&gallerycat='.DEMOGALLERYID;
	    }

	    query_posts($query_string);
	    
	    $large_counter = 1;
		$next_number_to_add = 4;
		$next_trigger = 1;
	
	    $key = 0;
	    if (have_posts()) : while (have_posts()) : the_post();
	    	$small_image_url = array();
	        $image_url = '';
	        $gallery_ID = get_the_ID();
	        
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
			
			$large_counter++;
	        		
	        if(has_post_thumbnail($gallery_ID, 'original'))
	        {
	            $image_id = get_post_thumbnail_id($gallery_ID);
	            $small_image_url = wp_get_attachment_image_src($image_id, $photography_image_size, true);
	        }
	        
	        $permalink_url = get_permalink($gallery_ID);
	?>
	<div class="element masonry <?php echo esc_attr($grid_wrapper_class); ?> <?php echo esc_attr(photography_get_hover_effect()); ?>">
	
		<div class="<?php echo esc_attr($column_class); ?> static filterable gallery_type archive animated<?php echo esc_attr($key+1); ?> <?php echo esc_attr($tg_gallery_archive_info_style); ?>" data-id="post-<?php echo esc_attr($key+1); ?>">
		
			<?php 
			    if(!empty($small_image_url[0]))
			    {
			?>	
			    <a href="<?php echo esc_url($permalink_url); ?>" <?php echo photography_get_lightbox_caption_attr($image_id); ?> <?php echo photography_get_progressive_attr($image_id, $small_image_url[0], '', true, ''); ?>>
			    	<div class="gallery_archive_desc">
				    	<div class="gallery_archive_desc_content">
					    	<div class="gallery_archive_desc_inner">
					    		<h4><?php the_title(); ?></h4>
					    		<div class="post_detail"><?php the_excerpt(); ?></div>
					    	</div>
				    	</div>
			    	</div>
			    	<?php
				    	$all_photo_arr = array();
				    	
				    	if(!empty($tg_gallery_hover_slide))
				    	{
				    		//Get gallery images
				    		$all_photo_arr = get_post_meta($gallery_ID, 'wpsimplegallery_gallery', true);
				    		
				    		//Get only 5 recent photos
				    		$all_photo_arr = array_slice($all_photo_arr, 0, 5);
				    	}
				    	
					    if(!empty($all_photo_arr))
					    {
					?>
					<ul class="gallery_img_slides">
					<?php
					    foreach($all_photo_arr as $photo)
					    {
					    	$slide_image_url = wp_get_attachment_image_src($photo, $photography_image_size, true);
					?>
					<li><img src="<?php echo esc_url($slide_image_url[0]); ?>" alt="" class="static"/></li>
					<?php
					    }
					?>
					</ul>
					<?php
					    }
					?>
			        <img src="<?php echo esc_url($small_image_url[0]); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" />
			    </a>
			<?php
			    }		
			?>
		
		</div>
		
	</div>
	<?php
	    $key++;
	    endwhile; endif;	
	?>
		
	</div>
	
	</div>

</div>
</div>
</div>
<?php get_footer(); ?>
<!-- End content -->
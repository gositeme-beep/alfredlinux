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

$tg_menu_layout = photography_menu_layout();
if($tg_menu_layout == 'leftmenu')
{
	$photography_homepage_style = 'fullscreen';
}

//Include custom header feature
global $photography_screen_class;
$photography_screen_class = 'split';

global $photography_page_content_class;
$photography_page_content_class = 'split';

get_header();

get_template_part("/templates/template-header");

//Register javascript
wp_enqueue_script('masonry');
wp_register_script("photography-script-gallery-masonry", get_template_directory_uri()."/js/custom/portfolio-grid.js", false, THEMEVERSION, true);	
$params = array(
  'ajaxurl' => admin_url('admin-ajax.php'),
  'ajax_nonce' => wp_create_nonce('tgajax-post-contact-nonce'),
);

wp_localize_script("photography-script-gallery-masonry", 'tgPortfolioParams', $params );
wp_enqueue_script("photography-script-gallery-masonry", get_template_directory_uri()."/js/custom/portfolio-grid.js", false, THEMEVERSION, true);
?>
<div class="post_caption">
    <h1><?php echo the_title(); ?></h1>
    <?php
	    $gallery_excerpt = get_the_excerpt();
	
	    if(!empty($gallery_excerpt))
	    {
	?>
	    <hr class="title_break"/>
	    <div class="page_tagline">
	    	<?php echo wp_kses_post($gallery_excerpt); ?>
	    </div>
	<?php
	    }
	?>
</div>

<div class="inner">

    <!-- Begin main content -->
    <div class="inner_wrapper">

	    <div class="sidebar_content full_width fixed_column">
	
			<div id="portfolio_filter_wrapper" class="masonry gallery two_cols portfolio-content section content clearfix split" data-columns="2" data-gutter="30" data-next-action="photography_portfolio_grid" data-type="masonry" data-layout="contain" data-page-id="<?php echo esc_attr($current_page_id); ?>">
	
			<?php
				foreach($all_photo_arr as $key => $photo_id)
				{
				    $small_image_url = '';
				    $image_url = '';
				    
				    if(!empty($photo_id))
				    {
				    	$image_url = wp_get_attachment_image_src($photo_id, 'original', true);
				    	$small_image_url = wp_get_attachment_image_src($photo_id, 'photography-gallery-masonry', true);
				    	$mobile_image_url = wp_get_attachment_image_src($photo_id, 'medium_large', true);
						$poster_image_url = wp_get_attachment_image_src($photo_id, 'medium', true);
				    }
				    
				    //Get image meta data
				    $image_alt = get_post_meta($photo_id, '_wp_attachment_image_alt', true);
			?>
			<div class="element grid masonry classic2_cols <?php echo esc_attr(photography_get_hover_effect()); ?>">
			
				<div class="one_half gallery2 static filterable gallery_type animated<?php echo esc_attr($key+1); ?>" data-id="post-<?php echo esc_attr($key+1); ?>">
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
			
			<?php
				get_template_part("/templates/template-footer-split");
			?>
	
	    </div>
	
    </div>
    <!-- End main content -->
    	
</div>

<br class="clear"/><br/>
<?php
	get_footer();
?>
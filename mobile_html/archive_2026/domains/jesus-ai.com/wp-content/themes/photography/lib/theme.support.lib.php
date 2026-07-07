<?php
if ( function_exists( 'add_theme_support' ) ) {
	// Setup thumbnail support
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-background' );
	add_theme_support( 'post-formats', array( 'link', 'quote' ) );
}

if ( function_exists( 'add_image_size' ) ) { 
	//Check if enable progressive image option
	$tg_enable_lazy_loading = kirki_get_option('tg_enable_lazy_loading');
	
	//Setup image grid dimensions
	$pp_gallery_grid_image_width = get_option('pp_gallery_grid_image_width');
	if(empty($pp_gallery_grid_image_width))
	{
		$pp_gallery_grid_image_width = 705;
	}
	$pp_gallery_grid_image_height = get_option('pp_gallery_grid_image_height');
	if(empty($pp_gallery_grid_image_height))
	{
		$pp_gallery_grid_image_height = 529;
	}
	$image_crop = true;
	if($pp_gallery_grid_image_height == 9999)
	{
		$image_crop = false;
	}
	add_image_size( 'photography-gallery-grid', intval($pp_gallery_grid_image_width), intval($pp_gallery_grid_image_height), $image_crop );
	
	//Add progressive lazy loading image
	if(!empty($tg_enable_lazy_loading))
	{
		add_image_size( 'photography-gallery-grid-progressive', intval($pp_gallery_grid_image_width/20), intval($pp_gallery_grid_image_height/20), $image_crop );
	}
	
	
	//Setup image grid large dimensions
	$pp_gallery_grid_large_image_width = get_option('pp_gallery_grid_large_image_width');
	if(empty($pp_gallery_grid_large_image_width))
	{
		$pp_gallery_grid_large_image_width = 987;
	}
	$pp_gallery_grid_large_image_height = get_option('pp_gallery_grid_large_image_height');
	if(empty($pp_gallery_grid_large_image_height))
	{
		$pp_gallery_grid_large_image_height = 740;
	}
	$image_crop = true;
	if($pp_gallery_grid_large_image_height == 9999)
	{
		$image_crop = false;
	}
	add_image_size( 'photography-gallery-grid-large', intval($pp_gallery_grid_large_image_width), intval($pp_gallery_grid_large_image_height), $image_crop );
	
	//Add progressive lazy loading image
	if(!empty($tg_enable_lazy_loading))
	{
		add_image_size( 'photography-gallery-grid-large-progressive', intval($pp_gallery_grid_large_image_width/20), intval($pp_gallery_grid_large_image_height/20), $image_crop );
	}
	
	
	//Setup image masonry dimensions
	$pp_gallery_masonry_image_width = get_option('pp_gallery_masonry_image_width');
	if(empty($pp_gallery_masonry_image_width))
	{
		$pp_gallery_masonry_image_width = 705;
	}
	$pp_gallery_masonry_image_height = get_option('pp_gallery_masonry_image_height');
	if(empty($pp_gallery_masonry_image_height))
	{
		$pp_gallery_masonry_image_height = 9999;
	}
	$image_crop = true;
	if($pp_gallery_masonry_image_height == 9999)
	{
		$image_crop = false;
	}
	add_image_size( 'photography-gallery-masonry', intval($pp_gallery_masonry_image_width), intval($pp_gallery_masonry_image_height), $image_crop );
	
	//Add progressive lazy loading image
	if(!empty($tg_enable_lazy_loading))
	{
		add_image_size( 'photography-gallery-masonry-progressive', intval($pp_gallery_masonry_image_width/20), intval($pp_gallery_masonry_image_height/20), $image_crop );
	}
	
	
	//Setup image striped dimensions
	$pp_gallery_striped_image_width = get_option('pp_gallery_striped_image_width');
	if(empty($pp_gallery_striped_image_width))
	{
		$pp_gallery_striped_image_width = 270;
	}
	$pp_gallery_striped_image_height = get_option('pp_gallery_striped_image_height');
	if(empty($pp_gallery_striped_image_height))
	{
		$pp_gallery_striped_image_height = 690;
	}
	$image_crop = true;
	if($pp_gallery_striped_image_height == 9999)
	{
		$image_crop = false;
	}
	add_image_size( 'photography-gallery-striped', intval($pp_gallery_striped_image_width), intval($pp_gallery_striped_image_height), $image_crop );
	
	//Add progressive lazy loading image
	if(!empty($tg_enable_lazy_loading))
	{
		add_image_size( 'photography-gallery-striped-progressive', intval($pp_gallery_striped_image_width/20), intval($pp_gallery_striped_image_height/20), $image_crop );
	}
	
	
	//Setup image blog dimensions
	$pp_blog_image_width = get_option('pp_blog_image_width');
	if(empty($pp_blog_image_width))
	{
		$pp_blog_image_width = 960;
	}
	$pp_blog_image_height = get_option('pp_blog_image_height');
	if(empty($pp_blog_image_height))
	{
		$pp_blog_image_height = 636;
	}
	$image_crop = true;
	if($pp_blog_image_height == 9999)
	{
		$image_crop = false;
	}
	add_image_size( 'photography-blog', intval($pp_blog_image_width), intval($pp_blog_image_height), $image_crop );
	
	//Add progressive lazy loading image
	if(!empty($tg_enable_lazy_loading))
	{
		add_image_size( 'photography-blog-progressive', intval($pp_blog_image_width/20), intval($pp_blog_image_height/20), $image_crop );
	}
}

add_action( 'after_setup_theme', 'photography_woocommerce_support' );

function photography_woocommerce_support() {
    	add_theme_support( 'woocommerce' );
}

add_filter('wp_get_attachment_image_attributes', 'photography_responsive_image_fix');

function photography_responsive_image_fix($attr) {
    if (isset($attr['sizes'])) unset($attr['sizes']);
    if (isset($attr['srcset'])) unset($attr['srcset']);
    return $attr;
}

add_filter('wp_calculate_image_sizes', '__return_false', PHP_INT_MAX);
add_filter('wp_calculate_image_srcset', '__return_false', PHP_INT_MAX);
remove_filter('the_content', 'wp_make_content_images_responsive');

/* Flush rewrite rules for custom post types. */
add_action( 'after_switch_theme', 'flush_rewrite_rules' );
?>
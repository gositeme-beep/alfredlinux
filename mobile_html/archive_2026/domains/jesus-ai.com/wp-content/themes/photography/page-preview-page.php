<?php
/**
 * The main template file for preview content builder display page.
 *
 * @package WordPress
*/

/**
*	Get Current page object
**/
if(!is_null($post))
{
	$page_obj = get_page($post->ID);
}

$current_page_id = '';

/**
*	Get current page id
**/

if(!is_null($post) && isset($page_obj->ID))
{
    $current_page_id = $page_obj->ID;
}

get_header(); 

//if dont have password set
if(!post_password_required())
{
	wp_enqueue_script("photography-custom-onepage", get_template_directory_uri()."/js/custom_onepage.js", false, THEMEVERSION, true);
?>

<?php
//Get page header display setting
$page_show_title = get_post_meta($current_page_id, 'page_show_title', true);

if(empty($page_show_title))
{
	//Get current page tagline
	$page_tagline = get_post_meta($current_page_id, 'page_tagline', true);

	$pp_page_bg = '';
	//Get page featured image
	if(has_post_thumbnail($current_page_id, 'full'))
    {
        $image_id = get_post_thumbnail_id($current_page_id); 
        $image_thumb = wp_get_attachment_image_src($image_id, 'full', true);
        
        if(isset($image_thumb[0]) && !empty($image_thumb[0]))
        {
        	$pp_page_bg = $image_thumb[0];
        }
    }
    
    //Check if add blur effect
	$tg_page_title_img_blur = kirki_get_option('tg_page_title_img_blur');
	
	$tg_page_title_font_alignment = kirki_get_option('tg_page_title_font_alignment');
	$tg_page_title_bg_vertical_alignment = kirki_get_option('tg_page_title_bg_vertical_alignment');
	
	//Check if enable content builder
	$ppb_enable = get_post_meta($current_page_id, 'ppb_enable', true);
	
	global $photography_topbar;
?>
<div id="page_caption" class="<?php if(!empty($pp_page_bg)) { ?>hasbg parallax <?php echo esc_attr($tg_page_title_bg_vertical_alignment); ?> <?php } ?> <?php if(!empty($photography_topbar)) { ?>withtopbar<?php } ?> <?php if(!empty($screen_class)) { ?>split<?php } ?> <?php if(!empty($ppb_enable)) { ?>ppb_enable<?php } ?>">
	<?php if(!empty($pp_page_bg)) { ?>
		<div id="bg_regular" style="background-image:url(<?php echo esc_url($pp_page_bg); ?>);"></div>
	<?php } ?>
	<?php
	    if(!empty($tg_page_title_img_blur))
	    {
	?>
	<div id="bg_blurred" style="background-image:url(<?php echo admin_url('admin-ajax.php').'?action=photography_blurred&src='.esc_url($pp_page_bg); ?>);"></div>
	<?php
	    }
	?>
	
	<div class="page_title_wrapper">
		<div class="page_title_inner <?php if($tg_page_title_font_alignment == 'left' OR $tg_page_title_font_alignment == 'right') { ?>standard_wrapper<?php } ?>">
			<h1 <?php if(!empty($pp_page_bg) && !empty($photography_topbar)) { ?>class ="withtopbar"<?php } ?>><?php the_title(); ?></h1>
			<?php
				$tg_page_tagline_alignment = kirki_get_option('tg_page_tagline_alignment');
	
		    	if(empty($pp_page_bg)) 
		    	{
		    ?>
		    	<hr class="title_break">
		    <?php
		    	}
		    ?>
		</div>
	</div>
</div>
<?php
}
?>

<div class="ppb_wrapper">
<?php
	//Check if live content builder mode
	$is_live_builder = false;
	if(isset($_GET['ppb_live']))
	{
		$is_live_builder = true;
		
		wp_enqueue_script("photography-live-builder", get_template_directory_uri()."/js/custom_livebuilder.js", false, THEMEVERSION, true);
	}

	$ppb_form_data_order = get_transient('photography_'.$post->ID.'_data_order');
	$ppb_page_content = '';
	
	if(isset($ppb_form_data_order))
	{
	    $ppb_form_item_arr = explode(',', $ppb_form_data_order);
	}
	
	$ppb_shortcodes = array();
	
	require_once get_template_directory() . "/lib/contentbuilder.shortcode.lib.php";
	
	if(isset($ppb_form_item_arr[0]) && !empty($ppb_form_item_arr[0]))
	{
	    $ppb_shortcode_code = '';
	    $ppb_form_item_data = get_transient('photography_'.$post->ID.'_data');
	    $ppb_form_item_size = get_transient('photography_'.$post->ID.'_size');
	
	    foreach($ppb_form_item_arr as $key => $ppb_form_item)
	    {
	    	if(isset($ppb_form_item_data[$ppb_form_item]))
		    {
		    	$ppb_form_item_data_obj = json_decode(stripslashes($ppb_form_item_data[$ppb_form_item]));
		    	
		    	$ppb_shortcode_content_name = $ppb_form_item_data_obj->shortcode.'_content';
		    	
		    	if(isset($ppb_form_item_data_obj->$ppb_shortcode_content_name))
		    	{
		    		$ppb_shortcode_code = '['.$ppb_form_item_data_obj->shortcode.' size="'.$ppb_form_item_size[$ppb_form_item].'" ';
		    		
		    		//Get shortcode title
		    		$ppb_shortcode_title_name = $ppb_form_item_data_obj->shortcode.'_title';
		    		if(isset($ppb_form_item_data_obj->$ppb_shortcode_title_name))
		    		{
		    			$ppb_shortcode_code.= 'title="'.esc_attr(rawurldecode($ppb_form_item_data_obj->$ppb_shortcode_title_name), ENT_QUOTES, "UTF-8").'" ';
		    		}
		    		
		    		//Get shortcode attributes
		    		if(isset($ppb_shortcodes[$ppb_form_item_data_obj->shortcode]))
		    		{
			    		$ppb_shortcode_arr = $ppb_shortcodes[$ppb_form_item_data_obj->shortcode];
			    		
			    		foreach($ppb_shortcode_arr['attr'] as $attr_name => $attr_item)
			    		{
			    			$ppb_shortcode_attr_name = $ppb_form_item_data_obj->shortcode.'_'.$attr_name;
			    			
			    			if(isset($ppb_form_item_data_obj->$ppb_shortcode_attr_name))
			    			{
			    				$ppb_shortcode_code.= $attr_name.'="'.esc_attr(rawurldecode($ppb_form_item_data_obj->$ppb_shortcode_attr_name)).'" ';
			    			}
			    		}
			    	}
			    	
			    	//Check if in live builder
		    		if($is_live_builder)
					{
					    $ppb_shortcode_code.= 'builder_id="'.esc_attr($ppb_form_item).'" ';
					}
	
		    		$ppb_shortcode_code.= ']'.rawurldecode($ppb_form_item_data_obj->$ppb_shortcode_content_name).'[/'.$ppb_form_item_data_obj->shortcode.']';
		    	}
		    	else if(isset($ppb_shortcodes[$ppb_form_item_data_obj->shortcode]))
		    	{
		    		$ppb_shortcode_code = '['.$ppb_form_item_data_obj->shortcode.' size="'.$ppb_form_item_size[$ppb_form_item].'" ';
		    		
		    		//Get shortcode title
		    		$ppb_shortcode_title_name = $ppb_form_item_data_obj->shortcode.'_title';
		    		if(isset($ppb_form_item_data_obj->$ppb_shortcode_title_name))
		    		{
		    			$ppb_shortcode_code.= 'title="'.esc_attr(rawurldecode($ppb_form_item_data_obj->$ppb_shortcode_title_name), ENT_QUOTES, "UTF-8").'" ';
		    		}
		    		
		    		//Get shortcode attributes
		    		if(isset($ppb_shortcodes[$ppb_form_item_data_obj->shortcode]))
		    		{
			    		$ppb_shortcode_arr = $ppb_shortcodes[$ppb_form_item_data_obj->shortcode];
			    		
			    		foreach($ppb_shortcode_arr['attr'] as $attr_name => $attr_item)
			    		{
			    			$ppb_shortcode_attr_name = $ppb_form_item_data_obj->shortcode.'_'.$attr_name;
			    			
			    			if(isset($ppb_form_item_data_obj->$ppb_shortcode_attr_name))
			    			{
			    				$ppb_shortcode_code.= $attr_name.'="'.esc_attr(rawurldecode($ppb_form_item_data_obj->$ppb_shortcode_attr_name)).'" ';
			    			}
			    		}
			    	}
		    		
		    		//Check if in live builder
		    		if($is_live_builder)
					{
					    $ppb_shortcode_code.= 'builder_id="'.esc_attr($ppb_form_item).'" ';
					}
		    		
		    		$ppb_shortcode_code.= ']';
		    	}
		    	
		    	$ppb_page_content.= photography_apply_content($ppb_shortcode_code);
	        }
        }
    }
    //echo $ppb_shortcode_code.'<hr/>';
    echo $ppb_page_content;
}
?>
</div>

<?php
	//Disable all link for live builder
	if($is_live_builder)
	{
?>
<script>
jQuery(document).ready(function(){
	jQuery('body a').on('click', function() { return false; });
	parent.triggerResize();
	parent.hideLoading();
});
</script>
<?php
	}
?>

<?php get_footer(); ?>
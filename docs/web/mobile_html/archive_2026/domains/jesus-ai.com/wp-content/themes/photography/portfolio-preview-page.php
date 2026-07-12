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

//Include custom header feature
get_template_part("/templates/template-portfolio-header");

//if dont have password set
if(!post_password_required())
{
	wp_enqueue_script("photography-custom-onepage", get_template_directory_uri()."/js/custom_onepage.js", false, THEMEVERSION, true);
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
//Check if enable portfolio comment
if (comments_open($post->ID)) 
{
?>
<br class="clear"/><br/><br/><hr class="small"/><br class="clear"/><br/>
<div class="standard_wrapper">
	<div class="fullwidth_comment_wrapper">
		<?php comments_template( '', true ); ?>
	</div>
</div>
<br class="clear"/><br/>
<?php
}
?>

<?php
//Check if displays recent portfolio
$tg_portfolio_recent = kirki_get_option('tg_portfolio_recent');

if(!empty($tg_portfolio_recent))
{
	$args = array(
        'numberposts' => 4,
        'order' => 'DESC',
        'orderby' => 'date',
        'post_type' => array('portfolios'),
        'suppress_filters' => 0,
    );
    $recent_post = get_posts($args);
    
    if(!empty($recent_post))
    {
?>
<br class="clear"/><br/>
<div class="standard_wrapper single_portfolio">
	<hr class="small"/><br class="clear"/><br/>
	<h6 class="subtitle"><span><?php esc_html_e('Recent Portfolios', 'photography' ); ?></span></h6><hr class="title_break"/>
	<br class="clear"/>
	<div class="single_recent_portfolio gallery four_cols portfolio-content section content clearfix" data-columns="4">
<?php
	foreach($recent_post as $recent_item)
	{
		$image_url = '';
		$portfolio_ID = $recent_item->ID;
		    	
		if(has_post_thumbnail($portfolio_ID, 'large'))
		{
		    $image_id = get_post_thumbnail_id($portfolio_ID);
		    $image_url = wp_get_attachment_image_src($image_id, 'large', true);
		    
		    $small_image_url = wp_get_attachment_image_src($image_id, 'photography-gallery-grid', true);
		}
		
		$portfolio_link_url = get_post_meta($portfolio_ID, 'portfolio_link_url', true);
		
		if(empty($portfolio_link_url))
		{
		    $permalink_url = get_permalink($portfolio_ID);
		}
		else
		{
		    $permalink_url = $portfolio_link_url;
		}
		
		//Get portfolio category
		$portfolio_item_set = '';
		$portfolio_item_sets = wp_get_object_terms($portfolio_ID, 'portfoliosets');
		
		if(is_array($portfolio_item_sets))
		{
		    foreach($portfolio_item_sets as $set)
		    {
		    	$portfolio_item_set.= $set->slug.' ';
		    }
		}
?>
	<div class="element grid classic4_cols <?php echo esc_attr(photography_get_hover_effect()); ?> <?php echo esc_attr($portfolio_item_set); ?>" data-type="<?php echo esc_attr($portfolio_item_set); ?>">
	
		<div class="one_fourth gallery4 classic static filterable gallery_type">
		<?php 
				if(!empty($image_url[0]))
				{
			?>		
				<?php
						$portfolio_type = get_post_meta($portfolio_ID, 'portfolio_type', true);
						$portfolio_video_id = get_post_meta($portfolio_ID, 'portfolio_video_id', true);
						
						switch($portfolio_type)
						{
						case 'External Link':
							$portfolio_link_url = get_post_meta($portfolio_ID, 'portfolio_link_url', true);
					?>
					<a target="_blank" href="<?php echo esc_url($portfolio_link_url); ?>">
						<img src="<?php echo esc_url($small_image_url[0]); ?>" alt="<?php echo esc_attr($recent_item->post_title); ?>"/>
						<div class="portfolio_classic_icon_wrapper">
							<div class="portfolio_classic_icon_content">
								<i class="fa fa-chain"></i>
							</div>
						</div>
		            </a>
					
					<?php
						break;
						//end external link
						
						case 'Portfolio Content':
        				default:
        			?>
        			<a href="<?php echo esc_url(get_permalink($portfolio_ID)); ?>">
        				<img src="<?php echo esc_url($small_image_url[0]); ?>" alt="<?php echo esc_attr($recent_item->post_title); ?>" />
        				<div class="portfolio_classic_icon_wrapper">
							<div class="portfolio_classic_icon_content">
								<i class="fa fa-mail-forward"></i>
							</div>
						</div>
		            </a>
	                
	                <?php
						break;
						//portfolio content
        				
        				case 'Image':
					?>
					<a data-rel="photography_portfolio_<?php echo esc_attr($current_page_id); ?>" <?php echo photography_get_portfolio_lightbox_caption_attr($recent_item->post_title); ?> href="<?php echo esc_url($image_url[0]); ?>" class="fancy-gallery">
						<img src="<?php echo esc_url($small_image_url[0]); ?>" alt="<?php echo esc_attr($recent_item->post_title); ?>" />
						<div class="portfolio_classic_icon_wrapper">
							<div class="portfolio_classic_icon_content">
								<i class="fa fa-search-plus"></i>
							</div>
						</div>
	                </a>
					
					<?php
						break;
						//end image
						
						case 'Youtube Video':
					?>
					
					<a data-rel="photography_portfolio_<?php echo esc_attr($current_page_id); ?>" href="https://www.youtube.com/embed/<?php echo esc_attr($portfolio_video_id); ?>" <?php echo photography_get_portfolio_lightbox_caption_attr($recent_item->post_title, $small_image_url[0]); ?> class="lightbox_youtube" data-options="width:900, height:488">
						<img src="<?php echo esc_url($small_image_url[0]); ?>" alt="<?php echo esc_attr($recent_item->post_title); ?>" />
						<div class="portfolio_classic_icon_wrapper">
							<div class="portfolio_classic_icon_content">
								<i class="fa fa-play"></i>
							</div>
						</div>
		            </a>
					
					<?php
						break;
						//end youtube
					
					case 'Vimeo Video':
					?>
					<a data-rel="photography_portfolio_<?php echo esc_attr($current_page_id); ?>" href="https://player.vimeo.com/video/<?php echo esc_attr($portfolio_video_id); ?>?badge=0" <?php echo photography_get_portfolio_lightbox_caption_attr($recent_item->post_title, $small_image_url[0]); ?> class="lightbox_vimeo" data-options="width:900, height:506">
						<img src="<?php echo esc_url($small_image_url[0]); ?>" alt="<?php echo esc_attr($recent_item->post_title); ?>" />
						<div class="portfolio_classic_icon_wrapper">
							<div class="portfolio_classic_icon_content">
								<i class="fa fa-play"></i>
							</div>
						</div>
		            </a>
					
					<?php
						break;
						//end vimeo
						
					case 'Self-Hosted Video':
					
						//Get video URL
						$portfolio_mp4_url = get_post_meta($portfolio_ID, 'portfolio_mp4_url', true);
						$preview_image = wp_get_attachment_image_src($image_id, 'large', true);
					?>
					<a data-rel="photography_portfolio_<?php echo esc_attr($current_page_id); ?>"  href="<?php echo esc_url($portfolio_mp4_url); ?>" data-src="<?php echo esc_url($portfolio_mp4_url); ?>" <?php echo photography_get_portfolio_lightbox_caption_attr($recent_item->post_title, $small_image_url[0]); ?> class="lightbox_vimeo">
						<img src="<?php echo esc_url($small_image_url[0]); ?>" alt="<?php echo esc_attr($recent_item->post_title); ?>" />
						<div class="portfolio_classic_icon_wrapper">
							<div class="portfolio_classic_icon_content">
								<i class="fa fa-play"></i>
							</div>
						</div>
		            </a>
					
					<?php
						break;
						//end self-hosted
					?>
					
					<?php
						}
						//end switch
					?>
					
					<div id="portfolio_desc_<?php echo esc_attr($portfolio_ID); ?>" class="portfolio_desc portfolio4 filterable">
        			    <h5><?php echo esc_html($recent_item->post_title); ?></h5>
					    <div class="post_detail"><?php echo esc_html($recent_item->post_excerpt); ?></div>
				    </div>
			<?php
				}		
			?>
		</div>
	</div>
<?php
	}
	//End foreach
?>
	</div>
</div>
<?php
	//Check if display portfolio page link
	$tg_portfolio_url = kirki_get_option('tg_portfolio_url');
	
	if(!empty($tg_portfolio_url))
	{
?>
	<div class="portfolio_recent_link">
		<a href="<?php echo esc_url($tg_portfolio_url); ?>"><i class="fa fa-th-large marginright middle"></i><h6><?php esc_html_e('View All Portfolios', 'photography' ); ?></h6></a>
	</div>
	<div class="standard_wrapper single_portfolio">
		<br class="clear"/><br/><hr class="small"/>
	</div>
<?php
	}
?>

<?php
	}
}
?>
		    
<?php
    $tg_portfolio_next_prev = kirki_get_option('tg_portfolio_next_prev');
    
    if(!empty($tg_portfolio_next_prev))
    {

    $args = array(
    	'before'           => '<p>' . esc_html__('Pages:', 'photography'),
    	'after'            => '</p>',
    	'link_before'      => '',
    	'link_after'       => '',
    	'next_or_number'   => 'number',
    	'nextpagelink'     => esc_html__('Next page', 'photography'),
    	'previouspagelink' => esc_html__('Previous page', 'photography'),
    	'pagelink'         => '%',
    	'echo'             => 1
    );
    wp_link_pages($args);
?>
<?php
    //Get Previous and Next Post
    $prev_post = get_previous_post();
    
    //If previous post is empty then get last post
    if(empty($prev_post))
    {
    	$args = array(
            'numberposts' => 1,
            'order' => 'ASC',
            'orderby' => 'menu_order',
            'post_type' => array('portfolios'),
        );
        $prev_post = get_posts($args);
        
    	$prev_post_bak = $prev_post[0];
    	unset($prev_post);
    	$prev_post = $prev_post_bak;
    }
    
    $next_post = get_next_post();
    
    //If next post is empty then get first post
    if(empty($next_post))
    {
    	$args = array(
            'numberposts' => 1,
            'order' => 'DESC',
            'orderby' => 'menu_order',
            'post_type' => array('portfolios'),
        );
        $next_post = get_posts($args);
    	
    	$next_post_bak = $next_post[0];
    	unset($next_post);
    	$next_post = $next_post_bak;
    }
?>
<div class="portfolio_post_wrapper">
<?php
   //Get Next Post
   if (!empty($next_post)): 
   $next_image_thumb = wp_get_attachment_image_src(get_post_thumbnail_id($next_post->ID), 'thumbnail', true);
   if(isset($next_image_thumb[0]))
   {
       $image_file_name = basename($next_image_thumb[0]);
   }
?>
   <div class="portfolio_post_next">
   		<a class="portfolio_next tooltip" title="<?php echo esc_attr($next_post->post_title); ?> →" href="<?php echo esc_url(get_permalink( $next_post->ID )); ?>">
     		<i class="fa fa-angle-right"></i>
     	</a>
    </div>
<?php endif; ?>

<?php
   //Get Previous Post
   if (!empty($prev_post)): 
   	$prev_image_thumb = wp_get_attachment_image_src(get_post_thumbnail_id($prev_post->ID), 'thumbnail', true);
   	if(isset($prev_image_thumb[0]))
   	{
   	    $image_file_name = basename($prev_image_thumb[0]);
   	}
?>
   	<div class="portfolio_post_previous">
   		<a class="portfolio_prev tooltip" title="← <?php echo esc_attr($prev_post->post_title); ?>" href="<?php echo esc_url(get_permalink( $prev_post->ID )); ?>">
     		<i class="fa fa-angle-left"></i>
     	</a>
    </div>
<?php endif; ?>

</div>
<?php
    
} //End if display previous and next portfolios
?>

<?php
	//Disable all link for live builder
	if($is_live_builder)
	{
?>
<script>
jQuery(document).ready(function(){
	jQuery('body a').live('click', function() { return false; });
	parent.triggerResize();
	parent.hideLoading();
});
</script>
<?php
	}
?>

<?php get_footer(); ?>
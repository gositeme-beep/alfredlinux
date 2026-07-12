<?php
/**
 * The main template file for display fullscreen vimeo video
 *
 * @package WordPress
 */
 
//Check if content builder preview
if(isset($_GET['rel']) && !empty($_GET['rel']) && isset($_GET['ppb_preview']))
{
	get_template_part("page-preview");
	die;
}

$portfolio_video_id = get_post_meta($post->ID, 'portfolio_video_id', true);

//important to apply dynamic header & footer style
global $photography_homepage_style;
$photography_homepage_style = 'fullscreen_video';

get_header();
?>

<div id="youtube_bg">
	<iframe src="//www.youtube.com/embed/<?php echo esc_attr($portfolio_video_id); ?>?autoplay=1&hd=1&rel=0&showinfo=0&wmode=opaque" frameborder="0" allowfullscreen></iframe>
</div>

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
            'order' => 'DESC',
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
            'order' => 'ASC',
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
   		<a class="portfolio_next tooltip" title="<?php echo esc_attr($next_post->post_title); ?>" href="<?php echo esc_url(get_permalink( $next_post->ID )); ?>">
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
   		<a class="portfolio_prev tooltip" title="<?php echo esc_attr($prev_post->post_title); ?>" href="<?php echo esc_url(get_permalink( $prev_post->ID )); ?>">
     		<i class="fa fa-angle-left"></i>
     	</a>
    </div>
<?php endif; ?>

</div>
<?php
    
} //End if display previous and next portfolios
?>

<?php
	get_footer();
?>
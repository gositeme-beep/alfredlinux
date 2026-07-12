<?php
/**
 * Template Name: Client Archive Circle
 * The main template file for display clients page.
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
get_template_part("/templates/template-header");
?>

<!-- Begin content -->
<?php
	//Get all portfolio items for paging
	global $wp_query;
	$numberposts = -1;
	if(THEMEDEMO)
	{
		$numberposts = 5;
	}
	
	$query_string = 'orderby=menu_order&order=ASC&post_type=clients&numberposts='.$numberposts.'&suppress_filters=0&posts_per_page='.$numberposts;
	query_posts($query_string);
?>
    
<div class="inner">

	<div class="inner_wrapper nopadding">
		
<?php
	if(!post_password_required())
	{
?>
	
	<?php
	    if(!empty($post->post_content) && empty($term))
	    {
	?>
	    <div class="standard_wrapper"><?php echo photography_apply_content($post->post_content); ?></div><br class="clear"/><br/>
	<?php
	    }
	    elseif(!empty($term) && !empty($ob_term->description))
	    { 
	?>
	    <div class="standard_wrapper"><?php echo $ob_term->description; ?></div><br class="clear"/><br/>
	<?php
	    }
	?>
	
	<div id="page_main_content" class="sidebar_content full_width nopadding fixed_column">
	
	<div class="team_wrapper">
	
	<?php
		$key = 0;
		if (have_posts()) : while (have_posts()) : the_post();
			$key++;
			$image_url = '';
			$client_ID = get_the_ID();
					
			if(has_post_thumbnail($client_ID, 'photography-gallery-grid'))
			{
			    $image_id = get_post_thumbnail_id($client_ID);
			    $client_thumbnail = wp_get_attachment_image_src($image_id, 'large', true);
			}
			
			$permalink_url = get_permalink($client_ID);
			
			$client_galleries = get_post_meta($client_ID, 'client_galleries', true);
			$client_description = get_post_meta($client_ID, 'client_description', true);
			
			if(isset($client_thumbnail[0]) && !empty($client_thumbnail[0]))
		    {
			    $last_class = '';
			    if($key%5 == 0)
			    {
				    $last_class = 'last';
			    }
	?>
	
	<div class="one_fifth <?php echo esc_attr($last_class); ?> nopadding grid">
		
		<div class="post_img client_circle">
			<a href="<?php echo esc_url($permalink_url); ?>" title="<?php echo esc_attr(get_the_title()); ?>">
		    	<img class="team_pic animated" data-animation="fadeIn" src="<?php echo esc_url($client_thumbnail[0]); ?>" alt="" />
			</a>
		</div>
		
		<div class="client_info">
			 <h5><?php the_title(); ?></h5>
			 <div class="client_info_galleries">
			 	<?php 
				 	$client_galleries_count = 0;
				 	if(is_array($client_galleries))
				 	{
					 	$client_galleries_count = count($client_galleries);
				 	}
				 	
				 	echo intval($client_galleries_count); 
				?>
				 	<span class="client_info_galleries_label">
				<?php
				 	if($client_galleries_count < 1)
				 	{
					 	echo esc_html_e('Gallery', 'photography' );
				 	}
				 	else
				 	{
					 	echo esc_html_e('Galleries', 'photography' );
				 	}
				?>
				 	</span>
			 </div>
		</div>

	</div>
	<?php
			}
		endwhile; endif;
	?>
		
	</div>
	
	</div>
<?php
}
//if password protected
else
{
?>
<div class="standard_wrapper"><br class="clear"/><br/><?php the_content(); ?></div>
<?php	
}
?>
<br class="clear"/><br/>
</div>
</div>

</div>
<?php get_footer(); ?>
<!-- End content -->
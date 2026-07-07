<?php
/**
 * The main template file for display error page.
 *
 * @package WordPress
*/

get_header(); 

//Register javascript
wp_enqueue_script('masonry');
wp_enqueue_script("photography-script-blog-grid", get_template_directory_uri()."/js/custom_blog_grid.js", false, THEMEVERSION, true);

$tg_page_title_font_alignment = kirki_get_option('tg_page_title_font_alignment');
?>

<!-- Begin content -->
<div id="page_caption">
	<div class="page_title_wrapper">
		<div class="page_title_inner  <?php if($tg_page_title_font_alignment == 'left' OR $tg_page_title_font_alignment == 'right') { ?>standard_wrapper<?php } ?>">
		    <h1><?php esc_html_e('Error Not Found!', 'photography' ); ?></h1>
		    <hr class="title_break"/>
		    <div class="page_tagline">
		        <?php esc_html_e( "We're sorry, the page you have looked for does not exist in our content!", 'photography' ); ?><br/>
		        	<?php esc_html_e( "Perhaps you would like to go to our homepage or try searching below.", 'photography' ); ?>
		    </div>
		</div>
	</div>
</div>

<div id="page_content_wrapper">

    <div class="inner">
    
    	<!-- Begin main content -->
    	<div class="inner_wrapper">
    	
	    	<div class="search_form_wrapper">
	    		<form class="searchform" method="get" action="<?php echo esc_url(home_url('/')); ?>">
			    	<input style="width:100%" type="text" class="field searchform-s" name="s" value="<?php the_search_query(); ?>" placeholder="<?php esc_html_e('Type to search and hit enter...', 'photography' ); ?>">
			    </form>
    		</div>
	    	
	    	<br/>
	    	
	    	<h5><?php esc_html_e('Or try to browse our latest posts instead?', 'photography' ); ?></h5><br/>
	    		
	    		<div id="blog_grid_wrapper" class="sidebar_content full_width">
	    		<?php
				
				$query_string ="items=6&post_type=post&paged=$paged";
				query_posts($query_string);
				$key = 0;
				
				if (have_posts()) : while (have_posts()) : the_post();
					
					$animate_layer = $key+7;
					$image_thumb = '';
												
					if(has_post_thumbnail(get_the_ID(), 'large'))
					{
					    $image_id = get_post_thumbnail_id(get_the_ID());
					    $image_thumb = wp_get_attachment_image_src($image_id, 'large', true);
					}
				?>
				
				<!-- Begin each blog post -->
				<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				
					<div class="post_wrapper grid_layout">
					
						<?php
						    //Get post featured content
						    $post_ft_type = get_post_meta(get_the_ID(), 'post_ft_type', true);
						    
						    switch($post_ft_type)
						    {
						    	case 'Image':
						    	default:
						        	if(!empty($image_thumb))
						        	{
						        		$small_image_url = wp_get_attachment_image_src($image_id, 'photography-blog', true);
						        		$image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
						?>
						
						    	    <div class="post_img small static">
						    	    	<a href="<?php the_permalink(); ?>" <?php echo photography_get_progressive_attr($image_id, $small_image_url[0], '', false); ?>>
						    	    		<img src="<?php echo esc_url(photography_get_progressive_preview_image($image_id, 'photography-blog')); ?>" alt="<?php echo esc_attr($image_alt); ?>" class="preview"/>
						                </a>
						    	    </div>
						
						<?php
						    		}
						    	break;
						    	
						    	case 'Vimeo Video':
						    		$post_ft_vimeo = get_post_meta(get_the_ID(), 'post_ft_vimeo', true);
						?>
						    		<?php echo do_shortcode('[tg_vimeo video_id="'.$post_ft_vimeo.'" width="670" height="377"]'); ?>
						    		<br/>
						<?php
						    	break;
						    	
						    	case 'Youtube Video':
						    		$post_ft_youtube = get_post_meta(get_the_ID(), 'post_ft_youtube', true);
						?>
						    		<?php echo do_shortcode('[tg_youtube video_id="'.$post_ft_youtube.'" width="670" height="377"]'); ?>
						    		<br/>
						<?php
						    	break;
						    	
						    } //End switch
						?>
					    
					    <div class="blog_grid_content">
							<?php
						    	//Check post format
						    	$post_format = get_post_format(get_the_ID());
								
								switch($post_format)
								{
									case 'quote':
							?>		
									<div class="post_quote_wrapper">
										<div class="post_quote_title">
										    <?php the_content(); ?>
										</div>
										<div class="post_detail">
									        <?php the_title(); ?>
										</div>
									</div>
							<?php
									break;
									
									case 'link':
							?>		
									<div class="post_header quote">
										<div class="post_quote_title grid">
											<?php the_content(); ?>
											<div class="post_detail">
										    	<?php echo date_i18n(THEMEDATEFORMAT, get_the_time('U')); ?>
										    	<?php
										    		//Get Post's Categories
										    		$post_categories = wp_get_post_categories($post->ID);
										    		if(!empty($post_categories))
										    		{
										    	?>
										    		<?php echo esc_html_e('In', 'photography' ); ?>
										    	<?php
										    	    	foreach($post_categories as $c)
										    	    	{
										    	    		$cat = get_category( $c );
										    	?>
										    	    	<a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"><?php echo esc_html($cat->name); ?></a>
										    	<?php
										    	    	}
										    	    }
										    	?>
										    </div>
										</div>
									</div>
							<?php
									break;
									
									default:
						    ?>
							    <div class="post_header grid">
							    	<div class="post_info_cat">
										    <?php
										    	//Get Post's Categories
										    	$post_categories = wp_get_post_categories($post->ID);
										    	
										    	$count_categories = count($post_categories);
												$i = 0;
										    	
										    	if(!empty($post_categories))
										    	{
										        	foreach($post_categories as $key => $c)
										        	{
										        		$cat = get_category( $c );
										    ?>
										        	<a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"><?php echo esc_html($cat->name); ?></a>
										    <?php
										    			if(++$i != $count_categories) 
										    			{
										    				echo '&nbsp;/&nbsp;';
										    			}
										        	}
										        }
										    ?>
								    	</div>
							    	<h6><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></h6>
							    	<hr class="title_break"/>
							    	<div class="post_detail">
									    <?php echo date_i18n(THEMEDATEFORMAT, get_the_time('U')); ?>
									</div>
							    </div>
							    
							    <?php
							    	$tg_blog_grid_display_excerpt = kirki_get_option('tg_blog_grid_display_excerpt');
				    
								    if(!empty($tg_blog_grid_display_excerpt))
								    {
							    		echo photography_substr(get_the_excerpt(), 170);
							    	}
							    ?>
						    <?php
						    		break;
						    	}
						    ?>
					    </div>
					    
					</div>
				
				</div>
				<!-- End each blog post -->
				
				<?php $key++; ?>
				<?php 
					endwhile; endif; 
					wp_reset_postdata();
				?>
	    		</div>
    		</div>
    		
    		<div class="sidebar_wrapper">
    		
	    	    <div class="sidebar_top"></div>
	    	
	    	    <div class="sidebar">
	    	    
	    	    	<div class="content">
	    	    
	    	    		<ul class="sidebar_widget">
	    	    		<?php dynamic_sidebar('404 Not Found Sidebar'); ?>
	    	    		</ul>
	    	    	
	    	    	</div>
	    	
	    	    </div>
	    	    <br class="clear"/>
	    	
	    	    <div class="sidebar_bottom"></div>
	    	</div>
    	</div>
    	
</div>
<br class="clear"/>
<?php get_footer(); ?>
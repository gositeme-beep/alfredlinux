<?php
function photography_portfolio_link_target()
{
	$return_html = '';
	$tg_portfolio_target_blank = get_theme_mod('tg_portfolio_target_blank', 1);
	
	if(!empty($tg_portfolio_target_blank)) {
		$return_html.= 'target="_blank"';
	}
	
	return $return_html;
}

function photography_get_site_domain()
{
	$site_url = site_url();
	$parse = parse_url($site_url);
	
	if(isset($parse['host']) && !empty($parse['host'])) {
		return $parse['host'];
	}
	else {
		return false;
	}
}

/**
* Setup blog pagination function
**/
function photography_pagination($pages = '', $range = 4)
{
	 $showitems = ($range * 2)+1;
	 
	 global $paged;
	 if(empty($paged)) $paged = 1;
	 
	 if($pages == '')
	 {
	 global $wp_query;
	 $pages = $wp_query->max_num_pages;
	 if(!$pages)
	 {
	 $pages = 1;
	 }
	 }
	 
	 if(1 != $pages)
	 {
	 echo "<div class=\"pagination\">";
	 if($paged > 2 && $paged > $range+1 && $showitems < $pages) echo "<a href='".get_pagenum_link(1)."'>&laquo; ".esc_html_e('First', 'photography')."</a>";
	 if($paged > 1 && $showitems < $pages) echo "<a href='".get_pagenum_link($paged - 1)."'>&lsaquo; ".esc_html_e('Previous', 'photography')."</a>";
	 
	 for ($i=1; $i <= $pages; $i++)
	 {
	 if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems ))
	 {
	 echo ($paged == $i)? "<span class=\"current\">".$i."</span>":"<a href='".get_pagenum_link($i)."' class=\"inactive\">".$i."</a>";
	 }
	 }
	 
	 if ($paged < $pages && $showitems < $pages) echo "<a href=\"".get_pagenum_link($paged + 1)."\">".esc_html_e('Next', 'photography')." &rsaquo;</a>";
	 if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages) echo "<a href='".get_pagenum_link($pages)."'>".esc_html_e('Last', 'photography')." &raquo;</a>";
	 echo "</div>";
	 }
}
    
/**
*	Setup comment style
**/
function photography_comment($comment, $args, $depth) 
{
	$GLOBALS['comment'] = $comment; 
?>
   
	<div class="comment" id="comment-<?php comment_ID() ?>">
		<div class="gravatar">
         	<?php echo get_avatar($comment,$size='60',$default='' ); ?>
      	</div>
      
      	<div class="right">
			<?php if ($comment->comment_approved == '0') : ?>
         		<em><?php echo esc_html_e('(Your comment is awaiting moderation.)', 'photography') ?></em>
         		<br />
      		<?php endif; ?>
			
			<?php
				if(!empty($comment->comment_author_url))
				{
			?>
					<h7><a href="<?php echo esc_url($comment->comment_author_url); ?>"><?php echo esc_html($comment->comment_author); ?></a></h7>
			<?php
				}
				else
				{
			?>
					<h7><?php echo esc_html($comment->comment_author); ?></h7>
			<?php
				}
			?>
			
			<div class="comment_date"><?php echo date_i18n(THEMEDATEFORMAT, strtotime($comment->comment_date)); ?> <?php echo esc_html_e('at', 'photography') ?> <?php echo date_i18n(THEMETIMEFORMAT, strtotime($comment->comment_date)); ?></div>
			<?php 
      			if($depth < 3)
      			{
      		?>
      			<?php comment_reply_link(array_merge( $args, array('depth' => $depth,
'reply_text' =>  esc_html__('Reply', 'photography'), 'login_text' => esc_html__('Login to Reply', 'photography'), 'max_depth' => $args['max_depth']))) ?>
			<?php
				}
			?>
			<br class="clear"/>
      		<?php ' '.comment_text() ?>

      	</div>
    </div>
    <?php 
        if($depth == 1)
        {
    ?>
    <br class="clear"/><hr/><div style="height:20px"></div>
    <?php
    	}
    ?>
<?php
}

// Substring without losing word meaning and
// tiny words (length 3 by default) are included on the result.
// "..." is added if result do not reach original string length

function photography_substr($str, $length, $minword = 3)
{
    $sub = '';
    $len = 0;
    $str = strip_tags($str);
    
    foreach (explode(' ', $str) as $word)
    {
        $part = (($sub != '') ? ' ' : '') . $word;
        $sub .= $part;
        $len += strlen($part);
        
        if (strlen($word) > $minword && strlen($sub) >= $length)
        {
            break;
        }
    }
    
    return $sub . (($len < strlen($str)) ? '...' : '');
}


/**
*	Setup recent posts widget
**/
function photography_posts($sort = 'recent', $items = 3, $echo = TRUE, $show_thumb = TRUE) 
{
	$return_html = '';
	
	if($sort == 'recent')
	{
		$args = array(
			'numberposts' => $items,
			'order' => 'DESC',
			'orderby' => 'date',
			'post_type' => 'post',
			'post_status' => 'publish',
			'suppress_filters' => 0,
		);
		$posts = get_posts($args);
		$title = esc_html__('Recent Posts', 'photography');
	}
	else
	{
		$args = array(
			'numberposts' => $items,
			'order' => 'DESC',
			'orderby' => 'comment_count',
			'post_type' => 'post',
			'post_status' => 'publish',
			'suppress_filters' => 0,
		);
		$posts = get_posts($args);
		$title = esc_html__('Popular Posts', 'photography'); 
	}
	
	if(!empty($posts))
	{
		$return_html.= '<h2 class="widgettitle"><span>'.$title.'</span></h2>';
		$return_html.= '<ul class="posts blog ';
		
		if($show_thumb)
		{
			$return_html.= 'withthumb ';
		}
		
		$return_html.= '">';
		
		$count_post = count($posts);

			foreach($posts as $post)
			{
				$image_thumb = get_post_meta($post->ID, 'blog_thumb_image_url', true);
				$return_html.= '<li>';
				
				if(!empty($show_thumb) && has_post_thumbnail($post->ID, 'thumbnail'))
				{
					$image_id = get_post_thumbnail_id($post->ID);
					$image_url = wp_get_attachment_image_src($image_id, 'thumbnail', true);
					
					$return_html.= '<div class="post_circle_thumb"><a href="'.get_permalink($post->ID).'"><img src="'.$image_url[0].'" alt="" /></a></div>';
				}
				
				$return_html.= '<a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a><div class="post_attribute">'.date_i18n(THEMEDATEFORMAT, get_the_time('U', $post->ID)).'</div>';
				$return_html.= '</li>';

			}	

		$return_html.= '</ul>';

	}
	
	if($echo)
	{
		echo stripslashes($return_html);
	}
	else
	{
		return $return_html;
	}
}

function photography_cat_posts($cat_id = '', $items = 5, $echo = TRUE, $show_thumb = TRUE) 
{
	$return_html = '';
	$posts = get_posts('numberposts='.$items.'&order=DESC&orderby=date&category='.$cat_id);
	$title = get_cat_name($cat_id);
	$category_link = get_category_link($cat_id);
	$count_post = count($posts);
	
	if(!empty($posts))
	{

		$return_html.= '<h2 class="widgettitle"><span>'.$title.'</span></h2>';
		$return_html.= '<ul class="posts blog ';
		
		if($show_thumb)
		{
			$return_html.= 'withthumb ';
		}
		
		$return_html.= '">';

			foreach($posts as $key => $post)
			{
				$return_html.= '<li>';
			
				if(!empty($show_thumb) && has_post_thumbnail($post->ID, 'thumbnail'))
				{
					$image_id = get_post_thumbnail_id($post->ID);
					$image_url = wp_get_attachment_image_src($image_id, 'thumbnail', true);
					
					$return_html.= '<div class="post_circle_thumb"><a href="'.get_permalink($post->ID).'"><img class="alignleft frame post_thumb" src="'.$image_url[0].'" alt="" /></a></div>';
				}
				
				$return_html.= '<a href="'.get_permalink($post->ID).'">'.photography_substr($post->post_title, 50).'</a><div class="post_attribute">'.date_i18n(THEMEDATEFORMAT, get_the_time('U', $post->ID)).'</div>';
				$return_html.= '</li>';
			}	

		$return_html.= '</ul><br class="clear"/>';

	}
	
	if($echo)
	{
		echo stripslashes($return_html);
	}
	else
	{
		return $return_html;
	}
}

function photography_image_from_description($data) {
    preg_match_all('/<img src="([^"]*)"([^>]*)>/i', $data, $matches);
    return $matches[1][0];
}


function photography_select_image($img, $size) {
    $img = explode('/', $img);
    $filename = array_pop($img);

    // The sizes listed here are the ones Flickr provides by default.  Pass the array index in the

    // 0 for square, 1 for thumb, 2 for small, etc.
    $s = array(
        '_s.', // square
        '_q.', // thumb
        '_m.', // small
        '.',   // medium
        '_b.'  // large
    );

    $img[] = preg_replace('/(_(s|t|m|b))?\./i', $s[$size], $filename);
    return implode('/', $img);
}

function photography_get_flickr($settings) 
{
	if (!function_exists('MagpieRSS')) {
	    // Check if another plugin is using RSS, may not work
	    require_once ABSPATH . WPINC . '/class-simplepie.php';
	}
	
	if(!isset($settings['items']) || empty($settings['items']))
	{
		$settings['items'] = 9;
	}
	
	// get the feeds
	if ($settings['type'] == "user") { $rss_url = 'https://api.flickr.com/services/feeds/photos_public.gne?id=' . $settings['id'] . '&per_page='.$settings['items'].'&format=rss_200'; }
	elseif ($settings['type'] == "favorite") { $rss_url = 'https://api.flickr.com/services/feeds/photos_faves.gne?id=' . $settings['id'] . '&format=rss_200'; }
	elseif ($settings['type'] == "set") { $rss_url = 'https://api.flickr.com/services/feeds/photoset.gne?set=' . $settings['set'] . '&nsid=' . $settings['id'] . '&format=rss_200'; }
	elseif ($settings['type'] == "group") { $rss_url = 'https://api.flickr.com/services/feeds/groups_pool.gne?id=' . $settings['id'] . '&format=rss_200'; }
	elseif ($settings['type'] == "public" || $settings['type'] == "community") { $rss_url = 'https://api.flickr.com/services/feeds/photos_public.gne?tags=' . $settings['tags'] . '&format=rss_200'; }
	else {
	    print '<strong>No "type" parameter has been setup. Check your settings, or provide the parameter as an argument.</strong>';
	    die();
	}
	
	$flickr_cache_path = THEMEUPLOAD.'/flickr_'.$settings['id'].'_'.$settings['items'].'.cache';
		
	if(file_exists($flickr_cache_path))
	{
	    $flickr_cache_timer = intval((time()-filemtime($flickr_cache_path))/60);
	}
	else
	{
	    $flickr_cache_timer = 0;
	}
	
	$photos_arr = array();
	
	if(!file_exists($flickr_cache_path) OR $flickr_cache_timer > 15)
	{
		# get rss file
		$feed = new SimplePie();
		$feed->set_feed_url($rss_url);
		$feed->enable_cache(FALSE);
		$feed->init();
		$feed->handle_content_type();
		
		foreach ($feed->get_items() as $key => $item)
		{
			$enclosure = $item->get_enclosure();
			$img = photography_image_from_description($item->get_description()); 
			$thumb_url = photography_select_image($img, 1);
			$large_url = photography_select_image($img, 4);
			
			$photos_arr[] = array(
				'title' => $enclosure->get_title(),
				'thumb_url' => $thumb_url,
				'original_url' => $large_url,
				'url' => $large_url,
				'link' => $item->get_link(),
			);
			
			$current = intval($key+1);
			
			if($current == $settings['items'])
			{
				break;
			}
		} 
		
		if(!empty($photos_arr))
		{
			if(file_exists($flickr_cache_path))
			{
			    unlink($flickr_cache_path);
			}
			
			//Writing cache file
			file_put_contents($flickr_cache_path, serialize($photos_arr));
		}
	}
	else
	{
		$file = file_get_contents($flickr_cache_path);
		
		if(empty($file))
		{
			global $wp_filesystem;
			$file = $wp_filesystem->get_contents($flickr_cache_path);
		}
					
		if(!empty($file))
		{
		    $photos_arr = unserialize($file);			
		}
	}

	return $photos_arr;
}

function photography_get_instagram_response_without_token_from_json( $user ) {

    $user = trim( $user );
    $url  = 'https://instagram.com/' .$user.'/?__a=1';

    $request = wp_remote_get( $url );

    if (is_wp_error($request) || 200 != wp_remote_retrieve_response_code($request)) {
        return new WP_Error('instagram error', $request);
    }

    $result = json_decode( wp_remote_retrieve_body( $request ) );

    if ( empty( $result ) ) {
        return new WP_Error('instagram error', 'empty result');
    }

    return $result;
}

function photography_get_instagram_response_without_token( $user ) {
	
	$user = trim( $user );
    $url  = 'https://instagram.com/' .$user;

    $request = wp_remote_get( $url );

    if (is_wp_error($request) || 200 != wp_remote_retrieve_response_code($request)) {
        return new WP_Error('instagram error', $request);
    }
    
	$body = wp_remote_retrieve_body( $request );

    $doc = new DOMDocument();

    @$doc->loadHTML( $body );

    $script_tags = $doc->getElementsByTagName( 'script' );

    $json = '';

    foreach ( $script_tags as $script_tag ) {
        if ( strpos( $script_tag->nodeValue, 'window._sharedData = ' ) !== false ) {
            $json = $script_tag->nodeValue;
            break;
        }
    }

    $json   = str_replace( array( 'window._sharedData = ', '};' ), array( '', '}' ), $json );
    $result = json_decode( $json );

    if ( empty( $result ) ) {
        return new WP_Error('instagram error', 'empty result');
    }

    return $result;
}

function photography_get_instagram($username, $access_token, $items = 8)
{   
	$wp_filesystem = photography_get_wp_filesystem();
	
	$photos_arr = array();

    if(!empty($username))
    {
	    $instagram_cache_path = THEMEUPLOAD.'/instagram_'.$username.'_'.$items.'.cache';
		
		if(file_exists($instagram_cache_path))
		{
		    $instagram_cache_timer = intval((time()-filemtime($instagram_cache_path))/60);
		}
		else
		{
		    $instagram_cache_timer = 0;
		}
		
		$photos_arr = array();
		
		//Check if already update Instagram cache to new API
		$pp_is_update_instagram_photography = get_option('pp_is_update_instagram_photography');
		
		if(!file_exists($instagram_cache_path) OR $instagram_cache_timer > 300 OR empty($pp_is_update_instagram_photography))
		{
			$result = photography_get_instagram_response_without_token_from_json($username);
		    $result_photos = $result->graphql->user->edge_owner_to_timeline_media->edges;
		
			if(is_array($result_photos) && !empty($result_photos))
			{
				foreach ($result_photos as $key => $item)
				{
					$small_thumb_url = $item->node->thumbnail_resources[0]->src;
					$thumb_url = $item->node->thumbnail_resources[3]->src;
					$large_url = $item->node->thumbnail_resources[4]->src;
					
					$photos_arr[] = array(
						'thumb_url' => $thumb_url,
						'url' => $large_url,
						'link' => 'https://instagram.com/p/'.$item->node->shortcode,
					);
					
					if(($key+1) == $items)
					{
						break;
					}
				} 
			}
			
			if(!empty($photos_arr))
			{
				if(file_exists($instagram_cache_path))
				{
				    unlink($instagram_cache_path);
				}
				
				if(photography_connect_fs())
				{
					//Writing cache file
					$wp_filesystem->put_contents(
					  $instagram_cache_path,
					  serialize($photos_arr)
					);
				}
				else
				{
					file_put_contents($instagram_cache_path, serialize($photos_arr));
				}
				
				if(empty($pp_is_update_instagram_photography))
				{
					//updated Instagram cache to new API
					update_option('pp_is_update_instagram_photography', 1);
				}
			}
			else
			{
				if(photography_connect_fs())
				{
					$file = $wp_filesystem->get_contents($instagram_cache_path);
				}
				else
				{
					$file = file_get_contents($instagram_cache_path);
				}
						
				if(!empty($file))
				{
				    $photos_arr = unserialize($file);			
				}
			}
		}
		else
		{
			if(photography_connect_fs())
			{
				$file = $wp_filesystem->get_contents($instagram_cache_path);
			}
			else
			{
				$file = file_get_contents($instagram_cache_path);
			}
						
			if(!empty($file))
			{
			    $photos_arr = unserialize($file);
			}
		}
    } 
    else 
    {
    	echo 'Invalid username and access token';
    }
    
    return $photos_arr;
}

function photography_connect_fs()
{
  $wp_filesystem = photography_get_wp_filesystem();

  if( false === ($credentials = request_filesystem_credentials('')) ) 
  {
    return false;
  }

  //check if credentials are correct or not.
  if(!WP_Filesystem($credentials)) 
  { 
    request_filesystem_credentials('');
    return false;
  }

  return true;
}

function photography_get_browser() 
{ 
    $u_agent = $_SERVER['HTTP_USER_AGENT']; 
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }
    
    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) 
    { 
        $bname = 'Internet Explorer'; 
        $ub = "MSIE"; 
    }
    elseif(preg_match('/Firefox/i',$u_agent)) 
    { 
        $bname = 'Mozilla Firefox'; 
        $ub = "Firefox"; 
    } 
    elseif(preg_match('/Chrome/i',$u_agent)) 
    { 
        $bname = 'Google Chrome'; 
        $ub = "Chrome"; 
    } 
    elseif(preg_match('/Safari/i',$u_agent)) 
    { 
        $bname = 'Apple Safari'; 
        $ub = "Safari"; 
    } 
    elseif(preg_match('/Opera/i',$u_agent)) 
    { 
        $bname = 'Opera'; 
        $ub = "Opera"; 
    } 
    elseif(preg_match('/Netscape/i',$u_agent)) 
    { 
        $bname = 'Netscape'; 
        $ub = "Netscape"; 
    }
    else
    { 
        $bname = 'Internet Explorer'; 
        $ub = "MSIE"; 
    }
    
    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    
    // see how many we have
    if(isset($matches['browser']))
    {
    	$i = count($matches['browser']);
    }
    else
    {
	    $i = 0;
	    $matches['version'] = 1;
    }
    if ($i != 1) {
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        }
        elseif(isset($matches['version'][1])) {
            $version= $matches['version'][1];
        }
        else
        {
	        $version = 11;
        }
    }
    else {
        $version= $matches['version'][0];
    }
    
    // check if we have a number
    if ($version==null || $version=="") {$version="?";}
    
    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
}

function photography_auto_link_twitter($tweet)
{
    //Convert urls to <a> links
	$tweet = preg_replace("/([\w]+\:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/", "<a target=\"_blank\" href=\"$1\">$1</a>", $tweet);

	//Convert hashtags to twitter searches in <a> links
	$tweet = preg_replace("/#([A-Za-z0-9\/\.]*)/", "<a target=\"_new\" href=\"https://twitter.com/search?q=$1\">#$1</a>", $tweet);

	//Convert attags to twitter profiles in <a> links
	$tweet = preg_replace("/@([A-Za-z0-9\/\.]*)/", "<a href=\"https://www.twitter.com/$1\">@$1</a>", $tweet);

	return $tweet;
}

function photography_resort_gallery_img($all_photo_arr)
{
	$sorted_all_photo_arr = array();
	$tg_gallery_sort = kirki_get_option('tg_gallery_sort');

	if(!empty($tg_gallery_sort) && !empty($all_photo_arr))
	{
		switch($tg_gallery_sort)
		{
			case 'drag':
			default:
				foreach($all_photo_arr as $key => $gallery_img)
				{
					$sorted_all_photo_arr[$key] = $gallery_img;
				}
			break;
			case 'post_date':
				foreach($all_photo_arr as $key => $gallery_img)
				{
					$gallery_img_meta = get_post($gallery_img);
					$gallery_img_date = strtotime($gallery_img_meta->post_date);
					
					$sorted_all_photo_arr[$gallery_img_date] = $gallery_img;
					krsort($sorted_all_photo_arr);
				}
			break;
			
			case 'post_date_old':
				foreach($all_photo_arr as $key => $gallery_img)
				{
					$gallery_img_meta = get_post($gallery_img);
					$gallery_img_date = strtotime($gallery_img_meta->post_date);
					
					$sorted_all_photo_arr[$gallery_img_date] = $gallery_img;
					ksort($sorted_all_photo_arr);
				}
			break;
			
			case 'rand':
				shuffle($all_photo_arr);
				$sorted_all_photo_arr = $all_photo_arr;
			break;
			
			case 'title':
				foreach($all_photo_arr as $key => $gallery_img)
				{
					$gallery_img_meta = get_post($gallery_img);
					$gallery_img_title = $gallery_img_meta->post_title;
					if(empty($gallery_img_title) OR is_null($gallery_img_title))
					{
						$gallery_img_title = $gallery_img_meta->ID;
					}
					
					if(!isset($sorted_all_photo_arr[$gallery_img_title]))
					{
						$sorted_all_photo_arr[$gallery_img_title] = $gallery_img;	
					}
					else
					{
						$sorted_all_photo_arr[$gallery_img_title.$gallery_img_meta->ID] = $gallery_img;
					}
					
					ksort($sorted_all_photo_arr);
				}
			break;
			
			case 'filename':
				foreach($all_photo_arr as $key => $gallery_img)
				{
					$gallery_img_filename = basename(get_attached_file($gallery_img));
					
					if(empty($gallery_img_filename))
					{
						$gallery_img_filename = $gallery_img;
					}
					
					if(!isset($sorted_all_photo_arr[$gallery_img_filename]))
					{
						$sorted_all_photo_arr[$gallery_img_filename] = $gallery_img;	
					}
					else
					{
						$sorted_all_photo_arr[$gallery_img_filename.$gallery_img] = $gallery_img;
					}
					
					ksort($sorted_all_photo_arr);
				}
			break;
		}
		
		return $sorted_all_photo_arr;
	}
	else
	{
		return array();
	}
}

function photography_apply_content($pp_content) {
	$pp_content = apply_filters('the_content', $pp_content);
    $pp_content = str_replace(']]>', ']]>', $pp_content);
    
    return $pp_content;
}

function photography_apply_builder($page_id, $post_type = 'page', $print = TRUE) 
{
	$ppb_form_data_order = get_post_meta($page_id, 'ppb_form_data_order');
	$ppb_page_content = '';
	
	if(isset($ppb_form_data_order[0]))
	{
	    $ppb_form_item_arr = explode(',', $ppb_form_data_order[0]);
	}
	
	$ppb_shortcodes = array();
	
	require_once get_template_directory() . "/lib/contentbuilder.shortcode.lib.php";
	
	if(isset($ppb_form_item_arr[0]) && !empty($ppb_form_item_arr[0]))
	{
	    $ppb_shortcode_code = '';
	
	    foreach($ppb_form_item_arr as $key => $ppb_form_item)
	    {
	    	$ppb_form_item_data = get_post_meta($page_id, $ppb_form_item.'_data');
	    	$ppb_form_item_size = get_post_meta($page_id, $ppb_form_item.'_size');
	    	$ppb_form_item_data_obj = json_decode($ppb_form_item_data[0]);
			
			if(property_exists($ppb_form_item_data_obj, 'shortcode')) {
	    		$ppb_shortcode_content_name = $ppb_form_item_data_obj->shortcode.'_content';
			}
	    	
	    	if(isset($ppb_form_item_data_obj->$ppb_shortcode_content_name))
	    	{
	    		$ppb_shortcode_code = '['.$ppb_form_item_data_obj->shortcode.' size="'.$ppb_form_item_size[0].'" ';
	    		
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

	    		$ppb_shortcode_code.= ']'.rawurldecode($ppb_form_item_data_obj->$ppb_shortcode_content_name).'[/'.$ppb_form_item_data_obj->shortcode.']';
	    	}
	    	else if(property_exists($ppb_form_item_data_obj, 'shortcode') && isset($ppb_shortcodes[$ppb_form_item_data_obj->shortcode]))
	    	{
	    		$ppb_shortcode_code = '['.$ppb_form_item_data_obj->shortcode.' size="'.$ppb_form_item_size[0].'" ';
	    		
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
	    		
	    		$ppb_shortcode_code.= ']';
	    	}
	    	//pp_debug($ppb_shortcode_code);
	    	
	    	if($print)
	    	{
	    		echo photography_apply_content($ppb_shortcode_code);
	    	}
	    	else
	    	{
		    	$ppb_page_content.= photography_apply_content($ppb_shortcode_code);
	    	}
        }
    }

    if(!$print)
    {
    	return $ppb_page_content;
    }
    	
}

function photography_get_excerpt_by_id($post_id)
{
	$the_post = get_post($post_id); //Gets post ID
	$the_excerpt = $the_post->post_content; //Gets post_content to be used as a basis for the excerpt
	$excerpt_length = 35; //Sets excerpt length by word count
	$the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
	$words = explode(' ', $the_excerpt, $excerpt_length + 1);
	if(count($words) > $excerpt_length) :
	array_pop($words);
	array_push($words, '…');
	$the_excerpt = implode(' ', $words);
	endif;
	$the_excerpt = '<p>' . $the_excerpt . '</p>';
	return $the_excerpt;
}

function photography_get_image_id($url) 
{
	$attachment_id = attachment_url_to_postid($url);
	if(empty($attachment_id))
	{
		$attachment_id = $url;
	}
	return $attachment_id;
}

function photograhy_aasort(&$array, $key) 
{
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}

if(!function_exists('get_dynamic_sidebar'))
{
	function get_dynamic_sidebar($index = 1)
	{
		$sidebar_contents = "";
		ob_start();
		dynamic_sidebar($index);
		$sidebar_contents = ob_get_clean();
		return $sidebar_contents;
	}
}

function photography_update_urls($options,$oldurl,$newurl)
{	
	global $wpdb;
	$results = array();
	$queries = array(
	'content' =>		array("UPDATE $wpdb->posts SET post_content = replace(post_content, %s, %s)",  esc_html__('Content Items (Posts, Pages, Custom Post Types, Revisions)','photography') ),
	'excerpts' =>		array("UPDATE $wpdb->posts SET post_excerpt = replace(post_excerpt, %s, %s)", esc_html__('Excerpts','photography') ),
	'attachments' =>	array("UPDATE $wpdb->posts SET guid = replace(guid, %s, %s) WHERE post_type = 'attachment'",  esc_html__('Attachments','photography') ),
	'links' =>			array("UPDATE $wpdb->links SET link_url = replace(link_url, %s, %s)", esc_html__('Links','photography') ),
	'custom' =>			array("UPDATE $wpdb->postmeta SET meta_value = replace(meta_value, %s, %s)",  esc_html__('Custom Fields','photography') ),
	'guids' =>			array("UPDATE $wpdb->posts SET guid = replace(guid, %s, %s)",  esc_html__('GUIDs','photography') )
	);

	foreach($options as $option){
	    if( $option == 'custom' ){
	    	$n = 0;
	    	$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta" );
	    	$page_size = 10000;
	    	$pages = ceil( $row_count / $page_size );
	    	
	    	for( $page = 0; $page < $pages; $page++ ) {
	    		$current_row = 0;
	    		$start = $page * $page_size;
	    		$end = $start + $page_size;
	    		$pmquery = "SELECT * FROM $wpdb->postmeta WHERE meta_value <> ''";
	    		$items = $wpdb->get_results( $pmquery );
	    		foreach( $items as $item ){
	    		$value = $item->meta_value;
	    		if( trim($value) == '' )
	    			continue;
	    		
	    			$edited = photography_unserialize_replace( $oldurl, $newurl, $value );
	    		
	    			if( $edited != $value ){
	    				$fix = $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %s", $edited, $item->meta_id) );
	    				if( $fix )
	    					$n++;
	    			}
	    		}
	    	}
	    	$results[$option] = array($n, $queries[$option][1]);
	    }
	    else{
	    	$result = $wpdb->query( $wpdb->prepare( $queries[$option][0], $oldurl, $newurl) );
	    	$results[$option] = array($result, $queries[$option][1]);
	    }
	}
	return $results;			
}


function photography_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) 
{
    try {
    	if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
    		$data = photography_unserialize_replace( $from, $to, $unserialized, true );
    	}
    	elseif ( is_array( $data ) ) {
    		$_tmp = array( );
    		foreach ( $data as $key => $value ) {
    			$_tmp[ $key ] = photography_unserialize_replace( $from, $to, $value, false );
    		}
    		$data = $_tmp;
    		unset( $_tmp );
    	}
    	else {
    		if ( is_string( $data ) )
    			$data = str_replace( $from, $to, $data );
    	}
    	if ( $serialised )
    		return serialize( $data );
    } catch( Exception $error ) {
    }
    return $data;
}

function photography_get_first_title_word($title) {
	return $title;
}

function photography_menu_layout() {
	$tg_menu_layout = kirki_get_option('tg_menu_layout');
	if(THEMEDEMO && isset($_GET['menulayout']) && !empty($_GET['menulayout']))
	{
		$tg_menu_layout = $_GET['menulayout'];
	}
	
	return $tg_menu_layout;
}

/**
* photography_is_woocommerce_page - Returns true if on a page which uses WooCommerce templates (cart and checkout are standard pages with shortcodes and which are also included)
*
* @access public
* @return bool
*/
function photography_is_woocommerce_page() 
{
	if(  function_exists ( "is_woocommerce" ) && is_woocommerce()){
	        return true;
	}
	$woocommerce_keys   =   array ( "woocommerce_shop_page_id") ;
	foreach ( $woocommerce_keys as $wc_page_id ) {
	        if ( get_the_ID () == get_option ( $wc_page_id , 0 ) ) {
	                return true ;
	        }
	}
	return false;
}

function photography_check_system()
{
	$has_error = 0;
	$return_html = '<div class="tg_system_status_wrapper">';
	
	$return_html.= '<h4>System Status</h4><br/>';

	//Get max_execution_time
	$max_execution_time = ini_get('max_execution_time');
	$max_execution_time_class = '';
	$max_execution_time_text = '';
	if($max_execution_time < 180)
	{
		$max_execution_time_class = 'tg_error';
		$has_error = 1;
		$max_execution_time_text = '*RECOMMENDED 180';
	}
	$return_html.= '<div class="'.$max_execution_time_class.'">max_execution_time: '.$max_execution_time.' '.$max_execution_time_text.'</div>';
	
	//Get memory_limit
	$memory_limit = ini_get('memory_limit');
	$memory_limit_class = '';
	$memory_limit_text = '';
	if(intval($memory_limit) < 128)
	{
		$memory_limit_class = 'tg_error';
		$has_error = 1;
		$memory_limit_text = '*RECOMMENDED 128M';
	}
	$return_html.= '<div class="'.$memory_limit_class.'">memory_limit: '.$memory_limit.' '.$memory_limit_text.'</div>';
	
	//Get post_max_size
	$post_max_size = ini_get('post_max_size');
	$post_max_size_class = '';
	$post_max_size_text = '';
	if(intval($post_max_size) < 32)
	{
		$post_max_size_class = 'tg_error';
		$has_error = 1;
		$post_max_size_text = '*RECOMMENDED 32M';
	}
	$return_html.= '<div class="'.$post_max_size_class.'">post_max_size: '.$post_max_size.' '.$post_max_size_text.'</div>';
	
	//Get upload_max_filesize
	$upload_max_filesize = ini_get('upload_max_filesize');
	$upload_max_filesize_class = '';
	$upload_max_filesize_text = '';
	if(intval($upload_max_filesize) < 32)
	{
		$upload_max_filesize_class = 'tg_error';
		$has_error = 1;
		$upload_max_filesize_text = '*RECOMMENDED 32M';
	}
	$return_html.= '<div class="'.$upload_max_filesize_class.'">upload_max_filesize: '.$upload_max_filesize.' '.$upload_max_filesize_text.'</div>';
	
	//Get max_input_vars
	$max_input_vars = ini_get('max_input_vars');
	$max_input_vars_class = '';
	$max_input_vars_text = '';
	if(intval($max_input_vars) < 2000)
	{
		$max_input_vars_class = 'tg_error';
		$has_error = 1;
		$max_input_vars_text = '*RECOMMENDED 2000';
	}
	$return_html.= '<div class="'.$max_input_vars_class.'">max_input_vars: '.$max_input_vars.' '.$max_input_vars_text.'</div>';
	
	if(!empty($has_error))
	{
		$return_html.= '<br/><hr/>We are sorry, the demo data could not import properly. It most likely due to PHP configurations on your server. Please fix configuration in System Status which are reported in <span class="tg_error">RED</span>';
	}
	
	$return_html.= '</div>' ;
	
	return $return_html;
}

function photography_hex_darker($rgb, $darker=2)
{
    $hash = (strpos($rgb, '#') !== false) ? '#' : '';
    $rgb = (strlen($rgb) == 7) ? str_replace('#', '', $rgb) : ((strlen($rgb) == 6) ? $rgb : false);
    if(strlen($rgb) != 6) return $hash.'000000';
    $darker = ($darker > 1) ? $darker : 1;

    list($R16,$G16,$B16) = str_split($rgb,2);

    $R = sprintf("%02X", floor(hexdec($R16)/$darker));
    $G = sprintf("%02X", floor(hexdec($G16)/$darker));
    $B = sprintf("%02X", floor(hexdec($B16)/$darker));

    return $hash.$R.$G.$B;        
}

function photography_get_dynamic_sidebar($index = 1)
{
    $sidebar_contents = "";
    ob_start();
    dynamic_sidebar($index);
    $sidebar_contents = ob_get_clean();
    return $sidebar_contents;
}

function photography_hex_to_rgb($hex) 
{
	$hex = str_replace("#", "", $hex);
	$color = array();
	
	if(strlen($hex) == 3) {
	    $color['r'] = hexdec(substr($hex, 0, 1) . $r);
	    $color['g'] = hexdec(substr($hex, 1, 1) . $g);
	    $color['b'] = hexdec(substr($hex, 2, 1) . $b);
	}
	else if(strlen($hex) == 6) {
	    $color['r'] = hexdec(substr($hex, 0, 2));
	    $color['g'] = hexdec(substr($hex, 2, 2));
	    $color['b'] = hexdec(substr($hex, 4, 2));
	}
	
	return $color;
}

function photography_available_widgets() 
{
	global $wp_registered_widget_controls;

	$widget_controls = $wp_registered_widget_controls;

	$available_widgets = array();

	foreach ( $widget_controls as $widget ) {

		if ( ! empty( $widget['id_base'] ) && ! isset( $available_widgets[$widget['id_base']] ) ) { // no dupes

			$available_widgets[$widget['id_base']]['id_base'] = $widget['id_base'];
			$available_widgets[$widget['id_base']]['name'] = $widget['name'];

		}

	}

	return $available_widgets;
}

function photography_import_data( $data ) 
{
	global $wp_registered_sidebars;

	// Have valid data?
	// If no data or could not decode
	if ( empty( $data ) || ! is_object( $data ) ) {
		wp_die(
			esc_html__('Import data could not be read. Please try a different file.', 'photography' ),
			'',
			array( 'back_link' => true )
		);
	}

	// Get all available widgets site supports
	$available_widgets = photography_available_widgets();

	// Get all existing widget instances
	$widget_instances = array();
	foreach ( $available_widgets as $widget_data ) {
		$widget_instances[$widget_data['id_base']] = get_option( 'widget_' . $widget_data['id_base'] );
	}

	// Begin results
	$results = array();

	// Loop import data's sidebars
	foreach ( $data as $sidebar_id => $widgets ) {

		// Skip inactive widgets
		// (should not be in export file)
		if ( 'wp_inactive_widgets' == $sidebar_id ) {
			continue;
		}

		// Check if sidebar is available on this site
		// Otherwise add widgets to inactive, and say so
		if ( isset( $wp_registered_sidebars[$sidebar_id] ) ) {
			$sidebar_available = true;
			$use_sidebar_id = $sidebar_id;
			$sidebar_message_type = 'success';
			$sidebar_message = '';
		} else {
			$sidebar_available = false;
			$use_sidebar_id = 'wp_inactive_widgets'; // add to inactive if sidebar does not exist in theme
			$sidebar_message_type = 'error';
			$sidebar_message = esc_html__('Sidebar does not exist in theme (using Inactive)', 'photography' );
		}

		// Result for sidebar
		$results[$sidebar_id]['name'] = ! empty( $wp_registered_sidebars[$sidebar_id]['name'] ) ? $wp_registered_sidebars[$sidebar_id]['name'] : $sidebar_id; // sidebar name if theme supports it; otherwise ID
		$results[$sidebar_id]['message_type'] = $sidebar_message_type;
		$results[$sidebar_id]['message'] = $sidebar_message;
		$results[$sidebar_id]['widgets'] = array();

		// Loop widgets
		foreach ( $widgets as $widget_instance_id => $widget ) {

			$fail = false;

			// Get id_base (remove -# from end) and instance ID number
			$id_base = preg_replace( '/-[0-9]+$/', '', $widget_instance_id );
			$instance_id_number = str_replace( $id_base . '-', '', $widget_instance_id );

			// Does site support this widget?
			if ( ! $fail && ! isset( $available_widgets[$id_base] ) ) {
				$fail = true;
				$widget_message_type = 'error';
				$widget_message = esc_html__('Site does not support widget', 'photography' ); // explain why widget not imported
			}

			// Filter to modify settings object before conversion to array and import
			// Leave this filter here for backwards compatibility with manipulating objects (before conversion to array below)
			// Ideally the newer wie_widget_settings_array below will be used instead of this
			$widget = apply_filters( 'wie_widget_settings', $widget ); // object

			// Convert multidimensional objects to multidimensional arrays
			// Some plugins like Jetpack Widget Visibility store settings as multidimensional arrays
			// Without this, they are imported as objects and cause fatal error on Widgets page
			// If this creates problems for plugins that do actually intend settings in objects then may need to consider other approach: https://wordpress.org/support/topic/problem-with-array-of-arrays
			// It is probably much more likely that arrays are used than objects, however
			$widget = json_decode( json_encode( $widget ), true );

			// Does widget with identical settings already exist in same sidebar?
			if ( ! $fail && isset( $widget_instances[$id_base] ) ) {

				// Get existing widgets in this sidebar
				$sidebars_widgets = get_option( 'sidebars_widgets' );
				$sidebar_widgets = isset( $sidebars_widgets[$use_sidebar_id] ) ? $sidebars_widgets[$use_sidebar_id] : array(); // check Inactive if that's where will go

				// Loop widgets with ID base
				$single_widget_instances = ! empty( $widget_instances[$id_base] ) ? $widget_instances[$id_base] : array();
				foreach ( $single_widget_instances as $check_id => $check_widget ) {

					// Is widget in same sidebar and has identical settings?
					if ( in_array( "$id_base-$check_id", $sidebar_widgets ) && (array) $widget == $check_widget ) {

						$fail = true;
						$widget_message_type = 'warning';
						$widget_message = esc_html__('Widget already exists', 'photography' ); // explain why widget not imported

						break;

					}

				}

			}

			// No failure
			if ( ! $fail ) {

				// Add widget instance
				$single_widget_instances = get_option( 'widget_' . $id_base ); // all instances for that widget ID base, get fresh every time
				$single_widget_instances = ! empty( $single_widget_instances ) ? $single_widget_instances : array( '_multiwidget' => 1 ); // start fresh if have to
				$single_widget_instances[] = $widget; // add it

					// Get the key it was given
					end( $single_widget_instances );
					$new_instance_id_number = key( $single_widget_instances );

					// If key is 0, make it 1
					// When 0, an issue can occur where adding a widget causes data from other widget to load, and the widget doesn't stick (reload wipes it)
					if ( '0' === strval( $new_instance_id_number ) ) {
						$new_instance_id_number = 1;
						$single_widget_instances[$new_instance_id_number] = $single_widget_instances[0];
						unset( $single_widget_instances[0] );
					}

					// Move _multiwidget to end of array for uniformity
					if ( isset( $single_widget_instances['_multiwidget'] ) ) {
						$multiwidget = $single_widget_instances['_multiwidget'];
						unset( $single_widget_instances['_multiwidget'] );
						$single_widget_instances['_multiwidget'] = $multiwidget;
					}

					// Update option with new widget
					update_option( 'widget_' . $id_base, $single_widget_instances );

				// Assign widget instance to sidebar
				$sidebars_widgets = get_option( 'sidebars_widgets' ); // which sidebars have which widgets, get fresh every time
				$new_instance_id = $id_base . '-' . $new_instance_id_number; // use ID number from new widget instance
				$sidebars_widgets[$use_sidebar_id][] = $new_instance_id; // add new instance to sidebar
				update_option( 'sidebars_widgets', $sidebars_widgets ); // save the amended data

				// Success message
				if ( $sidebar_available ) {
					$widget_message_type = 'success';
					$widget_message = esc_html__('Imported', 'photography' );
				} else {
					$widget_message_type = 'warning';
					$widget_message = esc_html__('Imported to Inactive', 'photography' );
				}

			}

			// Result for widget instance
			$results[$sidebar_id]['widgets'][$widget_instance_id]['name'] = isset( $available_widgets[$id_base]['name'] ) ? $available_widgets[$id_base]['name'] : $id_base; // widget name or ID if name not available (not supported by site)
			$results[$sidebar_id]['widgets'][$widget_instance_id]['title'] = ! empty( $widget['title'] ) ? $widget['title'] : esc_html__('No Title', 'photography' ); // show "No Title" if widget instance is untitled
			$results[$sidebar_id]['widgets'][$widget_instance_id]['message_type'] = $widget_message_type;
			$results[$sidebar_id]['widgets'][$widget_instance_id]['message'] = $widget_message;

		}

	}

	// Return results
	return $results;
}

function photography_count_client_images($client_id = '')
{
	if(!empty($client_id))
	{
		$images_counter = 0;
		$client_galleries = get_post_meta($client_id, 'client_galleries', true);
		
		if(is_array($client_galleries) && !empty($client_galleries))
		{
			foreach($client_galleries as $gallery_id)
			{
				$all_photo_arr = get_post_meta($gallery_id, 'wpsimplegallery_gallery', true);
				$images_counter += intval(count($all_photo_arr));
			}
		}
		
		return intval($images_counter);
	}
	else
	{
		return 0;
	}
}

function photography_set_map_api()
{
	//Get Google Map API Key
	$pp_googlemap_api_key = get_option('pp_googlemap_api_key');
	
	if(empty($pp_googlemap_api_key))
	{
		wp_enqueue_script("google_maps", "https://maps.googleapis.com/maps/api/js", false, THEMEVERSION, true);
	}
	else
	{
		wp_enqueue_script("google_maps", "https://maps.googleapis.com/maps/api/js?key=".$pp_googlemap_api_key, false, THEMEVERSION, true);
	}
}

function photography_live_builder_begin_wrapper($item_id = '', $item_type = '')
{
	//Check if live content builder mode
	if(isset($_GET['ppb_live']) && !empty($item_id))
	{
		$return_html = '<div id="live_'.esc_attr($item_id).'" class="ppb_live_edit_wrapper '.esc_attr($item_type).'">';
		$return_html.= '<div class="ppb_live_action">';
		
		if(empty($item_type) OR $item_type != 'ppb_divider')
		{
			$return_html.= '<a href="javascript:;" class="ppb_add_after" data-builder-id="'.esc_attr($item_id).'" title="'.esc_html__('Add', 'photography' ).'"><span class="dashicons dashicons-arrow-down-alt"></span></a>';
		
			$return_html.= '<a href="javascript:;" class="ppb_edit" data-builder-id="'.esc_attr($item_id).'" title="'.esc_html__('Edit', 'photography' ).'"><span class="dashicons dashicons-welcome-write-blog"></span></a>';
			
			$return_html.= '<a href="javascript:;" class="ppb_duplicate" data-builder-id="'.esc_attr($item_id).'" title="'.esc_html__('Duplicate', 'photography' ).'"><span class="dashicons dashicons-admin-page"></span></a>';
		}
		
		$return_html.= '<a href="javascript:;" class="ppb_remove" data-builder-id="'.esc_attr($item_id).'" title="'.esc_html__('Remove', 'photography' ).'"><span class="dashicons dashicons-no"></span></a>';
		
		$return_html.= '</div>';
		
	    return $return_html;
	}
	else
	{
		return '';
	}
}

function photography_live_builder_end_wrapper($item_id = '')
{
	//Check if live content builder mode
	if(isset($_GET['ppb_live']) && !empty($item_id))
	{
	    return '</div>';
	}
	else
	{
		return '';
	}
}

function photography_blur($gdImageResource, $blurFactor = 3)
{
  // blurFactor has to be an integer
  $blurFactor = round($blurFactor);
  
  $originalWidth = imagesx($gdImageResource);
  $originalHeight = imagesy($gdImageResource);

  $smallestWidth = ceil($originalWidth * pow(0.5, $blurFactor));
  $smallestHeight = ceil($originalHeight * pow(0.5, $blurFactor));

  // for the first run, the previous image is the original input
  $prevImage = $gdImageResource;
  $prevWidth = $originalWidth;
  $prevHeight = $originalHeight;

  // scale way down and gradually scale back up, blurring all the way
  for($i = 0; $i < $blurFactor; $i += 1)
  {    
    // determine dimensions of next image
    $nextWidth = $smallestWidth * pow(2, $i);
    $nextHeight = $smallestHeight * pow(2, $i);

    // resize previous image to next size
    $nextImage = imagecreatetruecolor($nextWidth, $nextHeight);
    imagecopyresized($nextImage, $prevImage, 0, 0, 0, 0, 
      $nextWidth, $nextHeight, $prevWidth, $prevHeight);

    // apply blur filter
    imagefilter($nextImage, IMG_FILTER_GAUSSIAN_BLUR);

    // now the new image becomes the previous image for the next step
    $prevImage = $nextImage;
    $prevWidth = $nextWidth;
      $prevHeight = $nextHeight;
  }

  // scale back to original size and blur one more time
  imagecopyresized($gdImageResource, $nextImage, 
    0, 0, 0, 0, $originalWidth, $originalHeight, $nextWidth, $nextHeight);
  imagefilter($gdImageResource, IMG_FILTER_GAUSSIAN_BLUR);

  // clean up
  imagedestroy($prevImage);

  // return result
  return $gdImageResource;
}

function photography_themegoods_action() 
{
	$pp_verified_envato_photography = get_option('pp_verified_envato_photography');
	
	if(!$pp_verified_envato_photography OR empty($pp_verified_envato_photography))
	{
		if(defined('THEMEGOODS') && THEMEGOODS)
		{
			update_option("pp_verified_envato_photography", true);
			update_option("pp_envato_personal_token", '[ThemeGoods Activation]');
		}
		
		if(defined('ENVATO_HOSTED_SITE')) 
		{
	    	update_option("pp_verified_envato_photography", true);
			update_option("pp_envato_personal_token", '[Envato Hosted Activation]');
		}
	}
}

if(!function_exists('photography_is_registered'))
{
	function photography_is_registered() {
		$photography_is_registered = get_option("envato_purchase_code_".ENVATOITEMID);
		
		if(!empty($photography_is_registered)) {
			return $photography_is_registered;
		}
		else {
			return false;
		}
	}
}

function photography_register_theme($purchase_code = '') {
	if(!empty($purchase_code)) {
		update_option("envato_purchase_code_".ENVATOITEMID, $purchase_code);
		
		return true;
	}
	else {
		return false;
	}
}

function photography_unregister_theme() {
	$result = delete_option("envato_purchase_code_".ENVATOITEMID);
	return $result;
}

function photography_starts_with($haystack, $needle) 
{
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

function photography_get_hover_effect($type = 'gallery')
{
	if($type == 'gallery')
	{
		$tg_gallery_hover_effect = kirki_get_option('tg_gallery_hover_effect');
		
		return $tg_gallery_hover_effect;
	}
	else if($type == 'portfolio')
	{
		$tg_portfolio_hover_effect = kirki_get_option('tg_portfolio_hover_effect');
		
		return $tg_portfolio_hover_effect;
	}
}

function photography_get_lightbox_caption_attr($photo_id = '', $with_purchase = true, $poster = '')
{
	$return_html = '';
	
	//Get lightbox setting
	$tg_lightbox_enable = kirki_get_option('tg_lightbox_enable');
	$tg_lightbox_plugin = kirki_get_option('tg_lightbox_plugin');
	$tg_lightbox_enable_caption = kirki_get_option('tg_lightbox_enable_caption');
	
	if(!empty($tg_lightbox_enable) && !empty($photo_id) && !empty($tg_lightbox_enable_caption)) 
	{
		$image_caption = get_post_field('post_excerpt', $photo_id);
		$photography_purchase_url = get_post_meta($photo_id, 'photography_purchase_url', true);
		$purchase_html = '';
		
		if(!empty($photography_purchase_url) && $with_purchase)
		{
			$purchase_html = '<a href="'.esc_url($photography_purchase_url).'" class="button ghost"><i class="fa fa-shopping-cart marginright"></i>'.esc_html__('Purchase', 'photography' ).'</a>';
		}
		
		if($tg_lightbox_plugin == 'ilightbox')
		{
			$return_html.= 'data-caption="'.esc_attr($image_caption.$purchase_html).'"';
		}
		else if($tg_lightbox_plugin == 'modulobox')
		{
			$return_html.= 'data-title="'.esc_attr($image_caption).'" data-desc="'.esc_attr($purchase_html).'"';
			
			if(!empty($poster))
			{
				$return_html.= ' data-thumb="'.esc_url($poster).'"';
			}
		}
		
		return $return_html;
	}
	else
	{
		return '';
	}
}

function photography_get_portfolio_lightbox_caption_attr($title = '', $poster = '')
{
	$return_html = '';
	
	//Get lightbox setting
	$tg_lightbox_enable = kirki_get_option('tg_lightbox_enable');
	$tg_lightbox_plugin = kirki_get_option('tg_lightbox_plugin');
	$tg_lightbox_enable_caption = kirki_get_option('tg_lightbox_enable_caption');
	
	if(!empty($tg_lightbox_enable) && !empty($title) && !empty($tg_lightbox_enable_caption)) 
	{
		if($tg_lightbox_plugin == 'ilightbox')
		{
			$return_html.= 'data-caption="'.esc_attr($title).'"';
			
			if(!empty($poster))
			{
				$return_html.= 'data-options="thumbnail: \''.esc_url($poster).'\'"';
			}
		}
		else if($tg_lightbox_plugin == 'modulobox')
		{
			$return_html.= 'data-title="'.esc_attr($title).'"';
			
			if(!empty($poster))
			{
				$return_html.= ' data-thumb="'.esc_url($poster).'"';
			}
		}
		
		return $return_html;
	}
	else
	{
		return '';
	}
}

function photography_is_filterable_portfolio()
{
	//Get all project service and sorting option
	$tg_portfolio_filterable = kirki_get_option('tg_portfolio_filterable');
	    
	if(!empty($tg_portfolio_filterable))
	{
		return true;
	}
	else
	{
		return false;
	}
}

function photography_get_progressive_attr($image_id = '', $full_image_url = '', $medium_image_url = '', $is_srcset = false, $class = '')
{
	$return_html = '';
	
	if(!empty($image_id))
	{
		//Check if enable progressive image option
		$tg_enable_lazy_loading = kirki_get_option('tg_enable_lazy_loading');
		
		if(!empty($tg_enable_lazy_loading))
		{
			$srcset_html = '';
			if($is_srcset && !empty($medium_image_url))
			{
				$srcset_html.= 'data-srcset="'.esc_url($medium_image_url).' 320w, '.esc_url($full_image_url).' 768w" sizes="100vw"';
			}
			else
			{
				$srcset_html.= 'data-srcset="'.esc_url($full_image_url).' 320w" sizes="100vw"';
			}
			
			$return_html.= $srcset_html.' class="replace progressive '.esc_attr($class).'"';
		}
		else
		{
			$return_html.= ' class="'.esc_attr($class).'"';
		}
	}
	
	return $return_html;
}

function photography_get_progressive_preview_image($image_id = '', $image_size = '')
{
	$return_url = '';
	
	if(!empty($image_id) && filter_var($image_id, FILTER_VALIDATE_URL) === FALSE)
	{
		//Check if enable progressive image option
		$tg_enable_lazy_loading = kirki_get_option('tg_enable_lazy_loading');
		
		if(!empty($tg_enable_lazy_loading))
		{
			if($image_size == 'medium')
			{
				$return_url = wp_get_attachment_image_src($image_id, 'medium', true);
			}
			else
			{
				$return_url = wp_get_attachment_image_src($image_id, $image_size.'-progressive', true);	
			}
		}
		else
		{
			$return_url = wp_get_attachment_image_src($image_id, $image_size, true);
		}
	}
	else
	{
		$return_url = $image_id;
		return $return_url;
	}
	
	if(isset($return_url[0]))
	{
		return $return_url[0];
	}
	else
	{
		return '';
	}
}

/*function photography_get_progressive_preview_image_name($image_size = '')
{
	$return_image_size = '';
	
	//Check if enable progressive image option
	$tg_enable_lazy_loading = kirki_get_option('tg_enable_lazy_loading');
	
	if(!empty($tg_enable_lazy_loading))
	{
		switch($image_size)
		{
			case 'photography-gallery-masonry':
				$return_image_size = 'photography-gallery-masonry';
			break;
		}
	}
	else
	{
		switch($image_size)
		{
			case 'photography-gallery-masonry':
				$return_image_size = 'photography-gallery-masonry';
			break;
		}
	}
}*/

function photography_check_instagram_authorization() {
	
	//Check if Instagram plugin is installed	
	$instagram_widget_settings_notice = '';
	$meks_easy_instagram_widget = 'meks-easy-instagram-widget/meks-easy-instagram-widget.php';
	$meks_easy_instagram_widget_activated = is_plugin_active($meks_easy_instagram_widget);
	
	if(!$meks_easy_instagram_widget_activated)
	{
		$instagram_widget_settings_notice = 'Required plugin "Meks Easy Photo Feed Widget" is required. <a href="'.admin_url("themes.php?page=install-required-plugins").'">Please install the plugin here</a>. or read more detailed instruction about <a href="https://themes.themegoods.com/photography/doc/instagram-photostream-issue/" target="_blank">How to setup the plugin here</a>';
	}
	else
	{
		//Verify Instagram API authorisation
		$instagram_widget_settings = get_option('meks_instagram_settings');
		
		if(empty($instagram_widget_settings))
		{
			$instagram_widget_settings_notice = 'Please authorize with your Instagram account <a href="'.admin_url("options-general.php?page=meks-instagram").'">here</a>';
		}
		else
		{
			$instagram_widget_settings_notice = true;
		}
	}
	
	return $instagram_widget_settings_notice;
}

function photography_get_instagram_using_plugin($cache_prefix = 'photostream', $limit = 20) {
	
	$photos_arr = array();
	
	//Get Instagram settings from plugin
	$meks_instagram_settings = get_option('meks_instagram_settings');
	
	//delete_option('photography_'.$cache_prefix.$limit);
	
	//Get Instagram cached data
	$instagram_cache_data = get_option('photography_'.$meks_instagram_settings['user_id'].$limit);
	//delete_option('photography_'.$meks_instagram_settings['user_id'].$limit);
	
	/*print '<pre>';
	var_dump($instagram_cache_data['data']);
	print '</pre>';*/
	
	
	$instagram_cache_timer = 300;
	
	if(!empty($instagram_cache_data) && !empty($instagram_cache_data['time']))
	{
		$instagram_cache_timer = time() - $instagram_cache_data['time'];
	}
	if(THEMEDEMO)
	{
		$instagram_cache_timer = 1;
	}
	
	//var_dump($instagram_cache_timer);
	
	if(!isset($instagram_cache_data['data']) OR empty($instagram_cache_data['data']) OR $instagram_cache_timer > 30000)
	{
		//Get Instagram JSON response from API
		if($meks_instagram_settings['api_type'] == 'business') {
			$response = wp_remote_get( 'https://graph.facebook.com/v7.0/' . $meks_instagram_settings['user_id'] . '/media?fields=media_url,thumbnail_url,media_type,permalink&limit='. $limit .'&access_token=' . $meks_instagram_settings['access_token'] );
		}
		else
		{
			$response = wp_remote_get( 'https://graph.instagram.com/'. $meks_instagram_settings['user_id'] .'/media?fields=media_url,thumbnail_url,media_type,permalink&limit='.$limit.'&access_token='.$meks_instagram_settings['access_token']);
		}
		//var_dump($response);
		$response_decode = json_decode( wp_remote_retrieve_body( $response ) );
		$result_photos = $response_decode->data;
	
		if(is_array($result_photos) && !empty($result_photos))
		{
			foreach ($result_photos as $key => $item)
			{
				if($item->media_type != 'VIDEO') {
					$small_thumb_url = $item->permalink.'media?size=t';
					$thumb_url = $item->permalink.'media?size=m';
					$large_url = $item->permalink.'media?size=l';
					
					$photos_arr[] = array(
						'thumb_url' => $thumb_url,
						'small_thumb_url' => $small_thumb_url,
						'original_url' => $item->media_url,
						'url' => $large_url,
						'link' => $item->permalink,
					);
				}
			} 
		}
		
		//var_dump('get from Instagram server');
		
		//Writing cache data
		if(!empty($photos_arr))
		{
			$cache_timestamp_instagram = time();
			
			$instagram_cache_data = array(
				'data' => $photos_arr,
				'time' => $cache_timestamp_instagram,
			);
			
			//Write cache to WordPress option
			update_option('photography_'.$meks_instagram_settings['user_id'].$limit, $instagram_cache_data);
			
			//var_dump('Successfully write cache');
		}
	}
	else if(isset($instagram_cache_data['data']) && !empty($instagram_cache_data['data']))
	{
		$photos_arr = $instagram_cache_data['data'];
		
		//var_dump('get from cached option');
		
		/*print '<pre>';
		var_dump($photos_arr);
		print '</pre>';*/
		
		if(THEMEDEMO) {
			$photography_upload_ig_images = get_option('photography_upload_ig_images');
			
			if(!$photography_upload_ig_images) {
				foreach($photos_arr as $key => $photo_arr) {
					if(isset($photo_arr['original_url']) && !empty($photo_arr['original_url'])) {
						$result_cache = photography_upload_from_url($photo_arr['original_url']);
						$photos_arr[$key]['cache_url'] = $result_cache['url'];
						$photos_arr[$key]['attachment_id'] = $result_cache['attachment_id'];	
					}
				}
				
				add_option('photography_upload_ig_images', true);
				/*print '<pre>';
				var_dump($photos_arr);
				print '</pre>';*/
				
				//Write cache to WordPress option
				$cache_timestamp_instagram = time();
				
				$instagram_cache_data = array(
					'data' => $photos_arr,
					'time' => $cache_timestamp_instagram,
				);
				
				//Write cache to WordPress option
				update_option('photography_'.$meks_instagram_settings['user_id'].$limit, $instagram_cache_data);
			}
		}
	}
	
	return $photos_arr;
}

/**
 * Retrieves an image from a URL and uploads it using ld_handle_upload_from_path. See that function for more details.
 *
 * Note: This function should also work for local file paths as well, but the implementation is slightly different than ld_handle_upload_from_path.
 *
 * @param $image_url
 * @param int $attach_to_post
 * @param bool|true $add_to_media
 * @return array|bool
 */
function photography_upload_from_url( $image_url, $attach_to_post = 0, $add_to_media = true ) {
	$remote_image = fopen($image_url, 'r');
	
	if ( !$remote_image ) return false;
	
	$meta = stream_get_meta_data( $remote_image );
	
	$image_meta = false;
	$image_filetype = false;
	
	if ( $meta && !empty($meta['wrapper_data']) ) {
		foreach( $meta['wrapper_data'] as $v ) {
			if ( preg_match('/Content\-Type: ?((image)\/?(jpe?g|png|gif|bmp))/i', $v, $matches ) ) {
				$image_meta = $matches[1];
				$image_filetype = $matches[3];
			}
		}
	}
	
	// Resource did not provide an image.
	if ( !$image_meta ) return false;
	
	$v = basename($image_url);
	if ( $v && strlen($v) > 6 ) {
		// Create a filename from the URL's file, if it is long enough
		$path = $v;
	}else{
		// Short filenames should use the path from the URL (not domain)
		$url_parsed = parse_url( $image_url );
		$path = isset($url_parsed['path']) ? $url_parsed['path'] : $image_url;
	}
	
	$path = preg_replace('/(https?:|\/|www\.|\.[a-zA-Z]{2,4}$)/i', '', $path );
	$filename_no_ext = sanitize_title_with_dashes( $path, '', 'save' );
	
	$extension = $image_filetype;
	$filename = $filename_no_ext . "." . $extension;
	
	// Simulate uploading a file through $_FILES. We need a temporary file for this.
	$stream_content = stream_get_contents( $remote_image );
	
	$tmp = tmpfile();
	$tmp_path = stream_get_meta_data( $tmp )['uri'];
	fwrite( $tmp, $stream_content );
	fseek( $tmp, 0 ); // If we don't do this, WordPress thinks the file is empty
	
	$fake_FILE = array(
		'name'     => $filename,
		'type'     => 'image/' . $extension,
		'tmp_name' => $tmp_path,
		'error'    => UPLOAD_ERR_OK,
		'size'     => strlen( $stream_content ),
	);
	
	// Trick is_uploaded_file() by adding it to the superglobal
	$_FILES[basename( $tmp_path )] = $fake_FILE;
	
	// For wp_handle_upload to work:
	include_once ABSPATH . 'wp-admin/includes/media.php';
	include_once ABSPATH . 'wp-admin/includes/file.php';
	include_once ABSPATH . 'wp-admin/includes/image.php';
	
	$result = wp_handle_upload( $fake_FILE, array(
		'test_form' => false,
		'action'    => 'local',
	) );
	
	fclose( $tmp ); // Close tmp file
	@unlink( $tmp_path ); // Delete the tmp file. Closing it should also delete it, so hide any warnings with @
	unset( $_FILES[basename( $tmp_path )] ); // Clean up our $_FILES mess.
	
	fclose( $remote_image ); // Close the opened image resource
	
	$result['attachment_id'] = 0;
	
	if ( empty( $result['error'] ) && $add_to_media ) {
		$args = array(
			'post_title'     => $filename_no_ext,
			'post_content'   => '',
			'post_status'    => 'publish',
			'post_mime_type' => $result['type'],
		);
		
		$result['attachment_id'] = wp_insert_attachment( $args, $result['file'], $attach_to_post );
		
		$attach_data = wp_generate_attachment_metadata( $result['attachment_id'], $result['file'] );
		wp_update_attachment_metadata( $result['attachment_id'], $attach_data );
		
		if ( is_wp_error( $result['attachment_id'] ) ) {
			$result['attachment_id'] = 0;
		}
	}
	
	return $result;
}
?>
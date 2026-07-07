<?php
/**
 * The main template file for display gallery fullscreen.
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

//important to apply dynamic header & footer style
global $photography_homepage_style;
$photography_homepage_style = 'fullscreen';

get_header();

$cover = 1;
if(THEMEDEMO && isset($_GET['nocrop']) && $_GET['nocrop'] == 1)
{
	$cover = 0;
}

//Run gallery script data
wp_enqueue_style("photography-supersized", get_template_directory_uri()."/css/supersized.css", false, THEMEVERSION, "all");
wp_enqueue_style("photography-supersized-shutter", get_template_directory_uri()."/css/supersized.shutter.css", false, THEMEVERSION, "all");

wp_enqueue_script("photography-supersized", get_template_directory_uri()."/js/supersized.3.2.7.min.js", false, THEMEVERSION, true);
wp_enqueue_script("photography-supersized-shutter", get_template_directory_uri()."/js/supersized.shutter.min.js", false, THEMEVERSION, true);
wp_enqueue_script("photography-jquery-touchwipe", get_template_directory_uri()."/js/jquery.touchwipe.1.1.1.js", false, THEMEVERSION, true);
wp_enqueue_script("photography-supersized-gallery", admin_url('admin-ajax.php')."?action=photography_script_supersized_gallery&gallery_id=".$current_page_id."&cover=".$cover, false, THEMEVERSION, true);
?>
<?php
	$tg_full_arrow = kirki_get_option('tg_full_arrow');
	
	if(!empty($tg_full_arrow))
	{
?>
<div id="thumb-tray" class="load-item">
    <a id="prevslide" class="load-item"></a>
    <a id="nextslide" class="load-item"></a>
</div>
<?php
	}
	else
	{
?>
<a id="nextslide" class="load-item"></a>
<?php
	}
?>

<div id="controls-wrapper" class="load-item">
	<div id="controls">
	    <?php
	        $tg_full_image_caption = kirki_get_option('tg_full_image_caption');
	        if(!empty($tg_full_image_caption))
	        {
	    ?>
	        <!--Slide captions displayed here--> 
	        <div id="slidecaption"></div>
	    <?php
	        }
	    ?>
	</div>
</div>

<?php
	get_footer();
?>
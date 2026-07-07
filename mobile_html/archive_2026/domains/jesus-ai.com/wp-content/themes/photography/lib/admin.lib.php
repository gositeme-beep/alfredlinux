<?php
//photography_themegoods_action();

if( !function_exists('is_plugin_active') ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

$is_verified_envato_purchase_code = false;

//Get verified purchase code data
$is_verified_envato_purchase_code = photography_is_registered();

$customizer_styling_arr = array( 
	array('id'	=>	'bold_white', 'title' => 'Bold White Demo'),
	array('id'	=>	'bold_black', 'title' => 'Bold Black Demo'),
	array('id'	=>	'white1', 'title' => 'White Demo: Left Align Menu'),
	array('id'	=>	'white2', 'title' => 'White Demo: Center Align Menu'),
	array('id'	=>	'white3', 'title' => 'White Demo: With Top Bar'),
	array('id'	=>	'white4', 'title' => 'White Demo: Fullscreen Menu'),
	array('id'	=>	'white5', 'title' => 'White Demo: Left Vertical Menu'),
	array('id'	=>	'white6', 'title' => 'White Demo: Black Frame'),
	array('id'	=>	'white8', 'title' => 'White Demo: Boxed Layout'),
	array('id'	=>	'dark1', 'title' => 'Dark Demo: Left Align Menu'),
	array('id'	=>	'dark2', 'title' => 'Dark Demo: Center Align Menu'),
	array('id'	=>	'dark3', 'title' => 'Dark Demo: With Top Bar'),
	array('id'	=>	'dark4', 'title' => 'Dark Demo: Fullscreen Menu'),
	array('id'	=>	'dark5', 'title' => 'Dark Demo: Left Vertical Menu'),
	array('id'	=>	'dark6', 'title' => 'Dark Demo: White Frame'),
	array('id'	=>	'dark8', 'title' => 'Dark Demo: Boxed Layout'),
);

$customizer_styling_html = '';

//if verified envato purchase code
if($is_verified_envato_purchase_code)
{
	$customizer_styling_html.= '
		<div class="tg_notice">
			<span class="dashicons dashicons-warning"></span>
			Activating demo styling will replace all current theme customizer options.
		</div><br style="clear:both;"/>
		<ul id="get_styling_content" class="demo_list">';
	
	foreach($customizer_styling_arr as $key => $customizer_styling)
	{
		$last_class = '';
		if(($key+1)%3 == 0)
		{
			$last_class = 'last';
		}
		
		$customizer_styling_html.= '<li class="'.$last_class.'" data-styling="'.$customizer_styling['id'].'">
			    	<div class="item_content_wrapper">
			    		<div class="item_content">
			    			<div class="item_thumb">
			    				<img src="'.get_template_directory_uri().'/cache/demos/customizer/screenshots/'.$customizer_styling['id'].'.jpg" alt=""/></div>
			    			<div class="item_content">
			    				<div class="title"><strong>'.$customizer_styling['title'].'</strong></div>
						    	<div class="import">
							    	<input data-styling="'.$customizer_styling['id'].'" type="button" value="Activate" class="pp_get_styling_button button-primary"/>
							    </div>
						    </div>
					    </div>
				    </div>
			    </li>';
	}
	
	$customizer_styling_html.= '</ul>
	<div class="styling_message"><div class="import_message_success"><span class="tg_loading dashicons dashicons-update"></span>Data is being imported please be patient, don\'t navigate away from this page</div></div>';
}
else
{
	$customizer_styling_html.= '
		<div class="tg_notice">
			<span class="dashicons dashicons-warning" style="color:#FF3B30"></span> 
			<span style="color:#FF3B30">'.THEMENAME.' theme demos can only be imported with a valid Envato Token</span><br/><br/>
			Please visit <a href="javascript:jQuery(\'#pp_panel_registration_a\').trigger(\'click\');">Product Registration page</a> and enter a valid Envato Token to import the full '.THEMENAME.' demos and single pages through Content Builder.
		</div>';
}

//Begin Create demo import options
$demo_import_options_arr = array( 
	array('id'	=>	'demo1', 'title' => 'Demo 1', 'demo' => 1),
	array('id'	=>	'bold_white', 'title' => 'Bold White Demo', 'demo' => 4),
	array('id'	=>	'bold_black', 'title' => 'Bold Black Demo', 'demo' => 3),
);
//End Create demo import options

$demo_import_options_html = '';

//if verified envato purchase code
if($is_verified_envato_purchase_code)
{
	$wp_nonce = wp_create_nonce('photography_import_demo_content');
	
	$demo_import_options_html.= '<div class="tg_notice">
			<span class="dashicons dashicons-warning"></span>
			Importing a demo will create pages, posts, images, theme settings, widgets, sliders.<br/>Please make sure you install and activate all required plugins before importing any demo.
		</div><br style="clear:both;"/>
		
		<ul id="import_demo_content" class="demo_list">';
	
	foreach($demo_import_options_arr as $key => $demo_import_option)
	{
		$last_class = '';
		if(($key+1)%3 == 0)
		{
			$last_class = 'last';
		}
		
		$demo_url = 'https://themes.themegoods.com/photography/demo'.$demo_import_option['demo'].'/';
		
	$demo_import_options_html.= '<li class="'.$last_class.'" data-demo="'.$demo_import_option['demo'].'">
			    	<div class="item_content_wrapper">
			    		<div class="item_content">
			    			<div class="item_thumb">
			    				<a class="preview_wrapper" href="'.esc_url($demo_url).'" target="_blank">Preview</a>
			    				<img src="'.get_template_directory_uri().'/cache/demos/xml/demo'.$demo_import_option['demo'].'/'.$demo_import_option['demo'].'.jpg" alt=""/></div>
			    			<div class="item_content">
			    				<div class="title"><strong>'.$demo_import_option['title'].'</strong></div>
						    	<div class="import">
							    	<input data-demo="'.esc_attr($demo_import_option['demo']).'" data-nonce="'.esc_attr($wp_nonce).'" type="button" value="Import" class="pp_import_content_button upload_btn button-primary"/>
							    </div>
						    </div>
					    </div>
				    </div>
			    </li>';
	}
	
	$demo_import_options_html.= '</ul>
		<div class="import_message"><div class="import_message_success"><span class="tg_loading dashicons dashicons-update"></span>Data is being imported please be patient, don\'t navigate away from this page</div></div>';
}
else
{
	$demo_import_options_html.= '
		<div class="tg_notice">
			<span class="dashicons dashicons-warning" style="color:#FF3B30"></span> 
			<span style="color:#FF3B30">'.THEMENAME.' theme demos can only be imported with a valid purchase code</span><br/><br/>
			Please visit <a href="javascript:jQuery(\'#pp_panel_registration_a\').trigger(\'click\');">Product Registration page</a> and enter a valid purchase code to import the full '.THEMENAME.' demos and single pages through Content Builder.
		</div>';
}

/*
	Begin creating admin options
*/

$getting_started_html = '<div class="one_half">
		<div class="step_icon">
			<a href="'.admin_url("themes.php?page=install-required-plugins").'">
				<div class="step_number">Step <div class="int_number">1</div></div>
			</a>
		</div>
		<div class="step_info">
			<h3>Install the recommended plugins</h3>
			Theme has required and recommended plugins in order to build your website using layouts you saw on our demo site. We recommend you to install recommended plugins.
		</div>
	</div>
	
	<div class="one_half last">
		<div class="step_icon">
			<a href="javascript:;" onclick="jQuery(\'#pp_panel_demo-content_a\').trigger(\'click\');">
				<div class="step_number">Step <div class="int_number">2</div></div>
			</a>
		</div>
		<div class="step_info">
			<h3>Import the demo data</h3>
			Here you can import the demo data to your site. Doing this will make your site look like the demo site. It helps you to understand better the theme and build something similar to our demo quicker.
		</div>
	</div>
	
	<div class="one_half">
		<div class="step_icon">
			<a href="'.admin_url("customize.php").'">
				<div class="step_number">Step <div class="int_number">3</div></div>
			</a>
		</div>
		<div class="step_info">
			<h3>Customize theme elements and options</h3>
			Start customize theme\'s layouts, typography, elements colors using WordPress customize and see your changes in live preview instantly.
		</div>
	</div>
	
	<div class="one_half last">
		<div class="step_icon">
			<a href="'.admin_url("post-new.php?post_type=page").'">
				<div class="step_number">Step <div class="int_number">4</div></div>
			</a>
		</div>
		<div class="step_info">
			<h3>Create pages, galleries and portfolios</h3>
			'.THEMENAME.' support standard WordPress <a href="'.admin_url("post-new.php?post_type=page").'">page option</a>. Theme also has <a href="'.admin_url("post-new.php?post_type=galleries").'">gallery</a> and <a href="'.admin_url("post-new.php?post_type=portfolios").'">portfolios</a> options. You can use theme content builder to create and organise page contents.
		</div>
	</div>
	
	<div class="one_half">
		<div class="step_icon">
			<a href="'.admin_url("nav-menus.php").'">
				<div class="step_number">Step <div class="int_number">5</div></div>
			</a>
		</div>
		<div class="step_info">
			<h3>Setting up navigation menu</h3>
			Once you imported demo or created your own pages. You can setup navigation menu and assign to your website main header or any other places.
		</div>
	</div>
	
	<div class="one_half last">
		<div class="step_icon">
			<a href="'.admin_url("options-permalink.php").'">
				<div class="step_number">Step <div class="int_number">6</div></div>
			</a>
		</div>
		<div class="step_info">
			<h3>Permalinks structure</h3>
			You can change your website permalink structure to better SEO result. Click here to setup WordPress permalink options.
		</div>
	</div>';
	
$getting_started_html.='<br style="clear:both;"/>
	<h1>View Changelog</h1>
	<div class="getting_started_desc">We are always keep adding new features to our theme. This is why we highly recommend to stay up to date with each new theme version and all required plugins. You can view all <a href="https://themes.themegoods.com/photography/doc/theme-changelog/" target="_blank">theme changelog</a> here</div>
	<br style="clear:both;"/>
	
	<div style="height:30px"></div>
	';

$support_html = '';	
if($is_verified_envato_purchase_code)
{
	
	$support_html.='
	<div class="one_half nomargin">
		<div class="step_icon">
			<a href="https://themegoods.ticksy.com/submit/" target="_blank">
				<span class="dashicons dashicons-testimonial"></span>
				<div class="step_title">Submit a Ticket</div>
			</a>
		</div>
		<div class="step_info">
			<h3>Theme support</h3>
			We offer excellent support through our ticket system. Please make sure you prepare your purchased code first to access our services.
		</div>
	</div>
	
	<div class="one_half last nomargin">
		<div class="step_icon">
			<a href="https://themes.themegoods.com/photography/doc" target="_blank">
				<span class="dashicons dashicons-book-alt"></span>
				<div class="step_title">Theme Document</div>
			</a>
		</div>
		<div class="step_info">
			<h3>Online documentation</h3>
			This is the place to go find all reference aspects of theme functionalities. Our online documentation is resource for you to start using theme.
		</div>
	</div>
';
}
else
{
	$support_html = '
	<div class="tg_notice">
					<span class="dashicons dashicons-warning" style="color:#FF3B30"></span> 
					<span style="color:#FF3B30">'.THEMENAME.' theme support can only be activated with a valid Envato Token</span><br/><br/>
					Please visit <a href="javascript:jQuery(\'#pp_panel_registration_a\').trigger(\'click\');">Product Registration page</a> and enter a valid Envato Token to activate this section.
				</div>
		';
}

//Get product registration

//if verified envato purchase code
$check_icon = '';
$verification_desc = 'Thank you for choosing '.THEMENAME.' theme. Your product must be registered to receive many advantage features ex. demos import and support. We are sorry about this extra step but we built the activation system to prevent mass piracy of our themes. This will help us to better serve our paying customers.';

//Check if have any purchase code verification error
$response_html = '';
$purchase_code = '';
$register_button_html = '<input type="submit" name="submit" id="themegoods-envato-code-submit" class="button button-primary button-large" value="Register"/>';

//If already registered
if(!empty($is_verified_envato_purchase_code))
{
	$response_html.= '<br style="clear:both;"/><div class="tg_valid"><span class="dashicons dashicons-yes"></span>Your product is registered.</div>';
	$register_button_html = '<input type="submit" name="submit" id="themegoods-envato-code-unregister" class="button button-primary button-large" value="Unregister"/>';
	$purchase_code = $is_verified_envato_purchase_code;
}

//Displays purchase code verification response
if(isset($_GET['response']) && !empty($_GET['response'])) {
	$response_arr = json_decode(stripcslashes($_GET['response']));
	$purchase_code = '';
	if(isset($_GET['purchase_code']) && !empty($_GET['purchase_code'])) {
		$purchase_code = $_GET['purchase_code'];
	}
	
	if(isset($response_arr->response_code)) {
		if(!$response_arr->response_code) {
			$response_html.= '<br style="clear:both;"/><div class="tg_error"><span class="dashicons dashicons-warning"></span>'.$response_arr->response.'</div>';
		}
	}
	else {
		$response_html.= '<br style="clear:both;"/><div class="tg_error"><span class="dashicons dashicons-warning"></span> We can\'t verify your purchase of '.THEMENAME.' theme. Please make sure you enter correct purchase code. If you are sure you enter correct one. <a href="https://themegoods.ticksy.com" target="_blank">Please open a ticket</a> to us so our support staff can help you. Thank you very much.</div>';
	}
}

$product_registration_html ='
		<h1>Product Registration</h1>
		<div class="getting_started_desc">'.$verification_desc.'</div>
		<br style="clear:both;"/>
		
		<div style="height:10px"></div>
		
		<label for="pp_envato_personal_token">'.$check_icon.'Purchase Code</label>
		<small class="description">Please enter your Purchase Code.</small>';
$product_registration_html.= $register_button_html;

$purchase_code_input_class = '';
if(!empty($is_verified_envato_purchase_code)) {
	$purchase_code_input_class = 'themegoods-verified';
}

$product_registration_html.= '<input name="pp_envato_personal_token" id="pp_envato_personal_token" type="text" value="'.esc_attr($purchase_code).'" class="'.esc_attr($purchase_code_input_class).'"/>
		<input name="themegoods-site-domain" id="themegoods-site-domain" type="hidden" value="'.esc_attr(photography_get_site_domain()).'"/>
	';
	
	$product_registration_html.= $response_html;
	
if(isset($_GET['action']) && $_GET['action'] == 'invalid-purchase')
{
	$product_registration_html.='<br style="clear:both;"/><div style="height:20px"></div><div class="tg_error"><span class="dashicons dashicons-warning"></span> We can\'t find your purchase of '.THEMENAME.' theme. Please make sure you enter correct Envato Token. If you are sure you enter correct one. <a href="https://themegoods.ticksy.com" target="_blank">Please open a ticket</a> to us so our support staff can help you. Thank you very much.</div>
	
	<br style="clear:both;"/>
	
	<div style="height:10px"></div>';
}

if(!$is_verified_envato_purchase_code)
{
	$product_registration_html.='
	<br style="clear:both;"/>
	<div style="height:30px"></div>
	<h1>How to get Purchase Code</h1>
	<div style="height:5px"></div>
	<ol>
	 <li>You must be logged into the same Envato account that purchased '.THEMENAME.' theme.</li>
	 <li>Hover the mouse over your username at the top right corner of the screen.</li>
							<li>Click "Downloads" from the drop-down menu.</li>
							<li>Find '.THEMENAME.' theme your downloads list</li>
							<li>Click "Download" button and click "License certificate & purchase code" (available as PDF or text file).</li>
	</ol>
	<strong>You can see detailed article and video screencast about "how to find your purchase code" <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">here</a>.</strong>
';
}

//If already registered and add a link to license manager
if(!empty($is_verified_envato_purchase_code))
{
	$product_registration_html.='<br style="clear:both;"/><div style="height:20px"></div>
	<h1>Manage Your License</h1>
	<div class="getting_started_desc">To manage all your purchase code. Please open an account or login on <a href="https://license.themegoods.com/manager/" target="_blank">ThemeGoods License Manager</a>.
	</div>
	';
}

//Check if Envato Market plugin is installed	
$envato_market_activated = function_exists('envato_market');

if($is_verified_envato_purchase_code && !$envato_market_activated)
{
	$product_registration_html.='<br style="clear:both;"/><div style="height:50px"></div>
	<h1>Auto Update</h1>
	<div class="getting_started_desc">To enable auto update feature. You first must <a href="'.admin_url('themes.php?page=install-required-plugins').'">install Envato Market plugin</a> and enter your purchase code there. <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">Find your purchase code</a></div>
	<br style="clear:both;"/>
	
	<div style="height:10px"></div>
	';
}

//Get system info
$has_red_status = false;


//Get memory_limit
$memory_limit = ini_get('memory_limit');
$memory_limit_class = 'tg_valid';
$memory_limit_text = '';
if(intval($memory_limit) < 128)
{
    $memory_limit_class = 'tg_error';
    $has_error = 1;
    $memory_limit_text = '*RECOMMENDED 128M';
    
    $has_red_status = true;
}

$memory_limit_text = '<div class="'.$memory_limit_class.'">'.$memory_limit.' '.$memory_limit_text.'</div>';

//Get post_max_size
$post_max_size = ini_get('post_max_size');
$post_max_size_class = 'tg_valid';
$post_max_size_text = '';
if(intval($post_max_size) < 32)
{
    $post_max_size_class = 'tg_error';
    $has_error = 1;
    $post_max_size_text = '*RECOMMENDED 32M';
    
    $has_red_status = true;
}
$post_max_size_text = '<div class="'.$post_max_size_class.'">'.$post_max_size.' '.$post_max_size_text.'</div>';

//Get max_execution_time
$max_execution_time = ini_get('max_execution_time');
$max_execution_time_class = 'tg_valid';
$max_execution_time_text = '';
if($max_execution_time < 180)
{
    $max_execution_time_class = 'tg_error';
    $has_error = 1;
    $max_execution_time_text = '*RECOMMENDED 180';
    
    $has_red_status = true;
}
$max_execution_time_text = '<div class="'.$max_execution_time_class.'">'.$max_execution_time.' '.$max_execution_time_text.'</div>';

//Get max_input_vars
$max_input_vars = ini_get('max_input_vars');
$max_input_vars_class = 'tg_valid';
$max_input_vars_text = '';
if(intval($max_input_vars) < 2000)
{
    $max_input_vars_class = 'tg_error';
    $has_error = 1;
    $max_input_vars_text = '*RECOMMENDED 2000';
    
    $has_red_status = true;
}
$max_input_vars_text = '<div class="'.$max_input_vars_class.'">'.$max_input_vars.' '.$max_input_vars_text.'</div>';

//Get upload_max_filesize
$upload_max_filesize = ini_get('upload_max_filesize');
$upload_max_filesize_class = 'tg_valid';
$upload_max_filesize_text = '';
if(intval($upload_max_filesize) < 32)
{
    $upload_max_filesize_class = 'tg_error';
    $has_error = 1;
    $upload_max_filesize_text = '*RECOMMENDED 32M';
    
    $has_red_status = true;
}
$upload_max_filesize_text = '<div class="'.$upload_max_filesize_class.'">'.$upload_max_filesize.' '.$upload_max_filesize_text.'</div>';

//Get GD library version
if(function_exists('gd_info'))
{
	$php_gd_arr = gd_info();
}
else
{
	$php_gd_arr['GD Version'] = 'not installed';
}

$system_info_html = '';
if(!$is_verified_envato_purchase_code)
{
	$system_info_html = '<div style="height:20px"></div>
	<div class="tg_notice">
					<span class="dashicons dashicons-warning" style="color:#FF3B30"></span> 
					<span style="color:#FF3B30">'.THEMENAME.' Demos can only be imported with a valid purchase code</span><br/><br/>
					Please visit <a href="javascript:jQuery(\'#pp_panel_registration_a\').trigger(\'click\');">Product Registration page</a> and enter a valid Envato Token to import the full '.THEMENAME.' demos and single pages through Elementor.
				</div>
		
		<div style="height:50px"></div>
		';
}
else
{
	$system_info_html = '<table class="widefat" cellspacing="0">
			<thead>
				<tr>
					<th colspan="3">Server Environment</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="title">PHP Version:</td>
					<td class="help"><a href="javascript" title="The version of PHP installed on your hosting server." class="tooltipster">[?]</a></td>
					<td class="value">'.phpversion().'</td>
				</tr>
				<tr>
					<td class="title">WP Memory Limit:</td>
					<td class="help"><a href="javascript" title="The maximum amount of memory (RAM) that your site can use at one time." class="tooltipster">[?]</a></td>
					<td class="value">'.$memory_limit_text.'</td>
				</tr>
				<tr>
					<td class="title">PHP Post Max Size:</td>
					<td class="help"><a href="javascript" title="The largest file size that can be contained in one post." class="tooltipster">[?]</a></td>
					<td class="value">'.$post_max_size_text.'</td>
				</tr>
				<tr>
					<td class="title">PHP Time Limit:</td>
					<td class="help"><a href="javascript" title="The amount of time (in seconds) that your site will spend on a single operation before timing out (to avoid server lockups)" class="tooltipster">[?]</a></td>
					<td class="value">'.$max_execution_time_text.'</td>
				</tr>
				<tr>
					<td class="title">PHP Max Input Vars:</td>
					<td class="help"><a href="javascript" title="The maximum number of variables your server can use for a single function to avoid overloads." class="tooltipster">[?]</a></td>
					<td class="value">'.$max_input_vars_text.'</td>
				</tr>
				<tr>
					<td class="title">Max Upload Size:</td>
					<td class="help"><a href="javascript" title="The largest filesize that can be uploaded to your WordPress installation." class="tooltipster">[?]</a></td>
					<td class="value">'.$upload_max_filesize_text.'</td>
				</tr>
				<tr>
					<td class="title">GD Library:</td>
					<td class="help"><a href="javascript" title="This library help resizing images and improve site loading speed" class="tooltipster">[?]</a></td>
					<td class="value">'.$php_gd_arr['GD Version'].'</td>
				</tr>
			</tbody>
		</table>
		
		<div style="height:20px"></div>';
		
		//Check if required plugins is installed
		$photography_custom_post_activated = function_exists('post_type_galleries');
		$ocdi_activated = class_exists('OCDI_Plugin');
		
		if($photography_custom_post_activated && $ocdi_activated)
		{
			if($has_red_status)
			{
				$system_info_html.= '<div style="height:20px"></div>
			<div class="tg_notice">
				<span class="dashicons dashicons-warning" style="color:#FF3B30"></span> 
				<span>There are some settings which are below theme recommendation values and it might causes issue importing demo contents.</span>
			</div>';
			
				$import_demo_button_label = 'I understand and want to process demo importing process';
			}
			else
			{
				$import_demo_button_label = 'Begin importing demo process';
			}
			
			$system_info_html.= '<div class="tg_begin_import"><a href="'.admin_url('themes.php?page=tg-one-click-demo-import').'" class="button button-primary button-large">'.$import_demo_button_label.'</a></div>';
		}
		else
		{
			$system_info_html.= '<div style="height:20px"></div>
			<div class="tg_notice">
				<span class="dashicons dashicons-warning" style="color:#FF3B30"></span> 
				<span style="color:#FF3B30">One Click Demo Import, '.THEMENAME.' Custom Post Type plugins required</span><br/><br/>
				Please <a href="'.admin_url("themes.php?page=install-required-plugins").'">install and activate these required plugins.</a> first so demo contents can be imported properly.
			</div>';
		}
}

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
	//Verify Instagram API aurthorization
	$instagram_widget_settings = get_option('meks_instagram_settings');
	
	if(empty($instagram_widget_settings))
	{
		$instagram_widget_settings_notice = 'Please authorize with your Instagram account <a href="'.admin_url("options-general.php?page=meks-instagram").'">here</a>';
	}
	else
	{
		$instagram_widget_settings_notice = esc_html__('Authorized', 'photography');
	}
}

/*
	Begin creating admin options
*/

$api_url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

global $photograhy_options;

$photograhy_options = array (
 
//Begin admin header
array( 
		"name" => THEMENAME." Options",
		"type" => "title"
),
//End admin header

//Begin second tab "Home"
array( 	"name" => "Home",
		"type" => "section",
		"icon" => "dashicons-admin-home",	
),
array( "type" => "open"),

array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_home",
	"type" => "html",
	"html" => '
	<h1>Getting Started</h1>
	<div class="getting_started_desc">Welcome to '.THEMENAME.' theme. '.THEMENAME.' is now installed and ready to use! Please follow below steps to getting started.</div>
	<div style="height:50px"></div>
	'.$getting_started_html.'
	',
),

array( "type" => "close"),
//End second tab "Home"


//Begin second tab "Registration"
array( 	"name" => "Registration",
		"type" => "section",
		"icon" => "dashicons-admin-network",	
),
array( "type" => "open"),

array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_registration",
	"type" => "html",
	"html" => $product_registration_html,
),

array( "type" => "close"),
//End second tab "Registration"


//Begin second tab "Support"
array( 	"name" => "Support",
		"type" => "section",
		"icon" => "dashicons-sos",	
),
array( "type" => "open"),

array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_support",
	"type" => "html",
	"html" => '<h1>Support</h1>
	<div class="getting_started_desc">'.THEMENAME.' comes with 6 months of free support for every license you purchase. Support can be <a href="https://help.market.envato.com/hc/en-us/articles/207886473-Extending-and-Renewing-Item-Support">extended through subscriptions</a> via ThemeForest. All support for '.THEMENAME.' is handled through our support center on our company site. Below are all the resources we offer.</div>
	<div style="height:40px"></div>'.$support_html,
),

array( "type" => "close"),
//End second tab "Support"


//Begin second tab "General"
array( 	"name" => "General",
		"type" => "section",
		"icon" => "dashicons-admin-generic",	
),
array( "type" => "open"),

array( "name" => "<h2>Contact Form Settings</h2>Your email address",
	"desc" => "Enter which email address will be sent from contact form",
	"id" => SHORTNAME."_contact_email",
	"type" => "text",
	"validation" => "email",
	"std" => ""

),
array( "name" => "Select and sort contents on your contact page. Use fields you want to show on your contact form",
	"sort_title" => "Contact Form Manager",
	"desc" => "",
	"id" => SHORTNAME."_contact_form",
	"type" => "sortable",
	"options" => array(
		0 => 'Empty field',
		1 => 'Name',
		2 => 'Email',
		3 => 'Message',
		4 => 'Address',
		5 => 'Phone',
		6 => 'Mobile',
		7 => 'Company Name',
		8 => 'Country',
	),
	"options_disable" => array(1, 2, 3),
	"std" => ''
),

array( "name" => "Enable GDPR Compliant",
	"desc" => "If you enable this option, contact page add checkbox to support GDPR privacy law",
	"id" => SHORTNAME."_contact_enable_gdpr",
	"type" => "iphone_checkboxes",
	"std" => 1,
),

array( "name" => "<h2>Google Maps Setting</h2>API Key",
	"desc" => "Enter Google Maps API Key <a href=\"https://themegoods.ticksy.com/article/7785/\" target=\"_blank\">How to get API Key</a>",
	"id" => SHORTNAME."_googlemap_api_key",
	"type" => "text",
	"std" => ""
),

array( "name" => "Custom Google Maps Style",
	"desc" => "Enter javascript style array of map. You can get sample one from <a href=\"https://snazzymaps.com\" target=\"_blank\">Snazzy Maps</a>",
	"id" => SHORTNAME."_googlemap_style",
	"type" => "textarea",
	"std" => ""
),

array( "name" => "<h2>Captcha Settings</h2>Enable Captcha",
	"desc" => "If you enable this option, contact page will display captcha image to prevent possible spam",
	"id" => SHORTNAME."_contact_enable_captcha",
	"type" => "iphone_checkboxes",
	"std" => 1,
),

array( "name" => "<h2>Custom Sidebar Settings</h2>Add a new sidebar",
	"desc" => "Enter sidebar name",
	"id" => SHORTNAME."_sidebar0",
	"type" => "text",
	"validation" => "text",
	"std" => "",
),

array( "name" => "<h2>Custom Permalink Settings</h2>Gallery Page Slug",
	"desc" => "Enter custom permalink slug for single gallery page",
	"id" => SHORTNAME."_permalink_galleries",
	"type" => "text",
	"std" => "galleries"
),

array( "name" => "Gallery Category Page Slug",
	"desc" => "Enter custom permalink slug for single gallery category page",
	"id" => SHORTNAME."_permalink_gallerycat",
	"type" => "text",
	"std" => "gallerycat"
),

array( "name" => "Portfolio Page Slug",
	"desc" => "Enter custom permalink slug for single portfolio page",
	"id" => SHORTNAME."_permalink_portfolio",
	"type" => "text",
	"std" => "portfolios"
),

array( "name" => "Portfolio Category Page Slug",
	"desc" => "Enter custom permalink slug for single portfolio category page",
	"id" => SHORTNAME."_permalink_portfoliocat",
	"type" => "text",
	"std" => "portfoliosets"
),

array( "name" => "Client Page Slug",
"desc" => "Enter custom permalink slug for single client page",
"id" => SHORTNAME."_permalink_client",
"type" => "text",
"std" => "clients"
),

array( "type" => "close"),
//End second tab "General"


//Begin second tab "Styling"
array( "name" => "Styling",
	"type" => "section",
	"icon" => "dashicons-art",
),

array( "type" => "open"),

array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_get_styling_button",
	"type" => "html",
	"html" => $customizer_styling_html,
),
 
array( "type" => "close"),


//Begin second tab "Images"
array( "name" => "Images",
	"type" => "section",
	"icon" => "dashicons-format-image",
),

array( "type" => "open"),

array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_gallery_image_dimensions_notice",
	"type" => "html",
	"html" => '<div class="tg_notice"><span class="dashicons dashicons-warning"></span>These settings affect the display and dimensions of images in your gallery, blog pages – the display on the front-end will still be affected by CSS styles. After changing these settings you may need to <a href="https://wordpress.org/plugins/force-regenerate-thumbnails/" target="_blank">regenerate your thumbnails</a></div>',
),
array( "name" => "<h2>Gallery Grid Image Dimensions Settings</h2>Image Width",
	"desc" => "Enter gallery grid image width(in pixels).",
	"id" => SHORTNAME."_gallery_grid_image_width",
	"type" => "text",
	"std" => "705",
	"validation" => "text",
),
array( "name" => "Image Height",
	"desc" => "Enter gallery grid image height(in pixels). Please enter 9999 for auto height.",
	"id" => SHORTNAME."_gallery_grid_image_height",
	"type" => "text",
	"std" => "529",
	"validation" => "text",
),
array( "name" => "<h2>Gallery Grid Large Image Dimensions Settings</h2>Image Width",
	"desc" => "Enter gallery grid large image width(in pixels).",
	"id" => SHORTNAME."_gallery_grid_large_image_width",
	"type" => "text",
	"std" => "987",
	"validation" => "text",
),
array( "name" => "Image Height",
	"desc" => "Enter gallery grid large image height(in pixels). Please enter 9999 for auto height.",
	"id" => SHORTNAME."_gallery_grid_large_image_height",
	"type" => "text",
	"std" => "740",
	"validation" => "text",
),
array( "name" => "<h2>Gallery Masonry Image Dimensions Settings</h2>Image Width",
	"desc" => "Enter gallery masonry image width(in pixels).",
	"id" => SHORTNAME."_gallery_masonry_image_width",
	"type" => "text",
	"std" => "705",
	"validation" => "text",
),
array( "name" => "Image Height",
	"desc" => "Enter gallery masonry image height(in pixels). Please enter 9999 for auto height.",
	"id" => SHORTNAME."_gallery_masonry_image_height",
	"type" => "text",
	"std" => "9999",
	"validation" => "text",
),
array( "name" => "<h2>Gallery Striped Image Dimensions Settings</h2>Image Width",
	"desc" => "Enter gallery striped image width(in pixels).",
	"id" => SHORTNAME."_gallery_striped_image_width",
	"type" => "text",
	"std" => "270",
	"validation" => "text",
),
array( "name" => "Image Height",
	"desc" => "Enter gallery striped image height(in pixels). Please enter 9999 for auto height.",
	"id" => SHORTNAME."_gallery_striped_image_height",
	"type" => "text",
	"std" => "690",
	"validation" => "text",
),
array( "name" => "<h2>Blog Featured Image Dimensions Settings</h2>Image Width",
	"desc" => "Enter blog featured image width(in pixels).",
	"id" => SHORTNAME."_blog_image_width",
	"type" => "text",
	"std" => "960",
	"validation" => "text",
),
array( "name" => "Image Height",
	"desc" => "Enter blog featured image height(in pixels). Please enter 9999 for auto height.",
	"id" => SHORTNAME."_blog_image_height",
	"type" => "text",
	"std" => "636",
	"validation" => "text",
),
 
array( "type" => "close"),


//Begin fifth tab "Social Profiles"
array( 	"name" => "Social-Profiles",
		"type" => "section",
		"icon" => "dashicons-facebook",
),
array( "type" => "open"),
	
array( "name" => "<h2>Facebook Sharing Settings</h2>Disable Theme Facebook Open Graph Tags",
	"desc" => "If you are using other 3rd party plugin for handling Facebook Open Graph Tags. You can disable this option to avoid conflict with theme.",
	"id" => SHORTNAME."_facebook_theme_graph",
	"type" => "iphone_checkboxes",
	"std" => 1,
),
array( "name" => "<h2>Accounts Settings</h2>Facebook page URL",
	"desc" => "Enter full Facebook page URL",
	"id" => SHORTNAME."_facebook_url",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Twitter Username",
	"desc" => "Enter Twitter username",
	"id" => SHORTNAME."_twitter_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Flickr Username",
	"desc" => "Enter Flickr username",
	"id" => SHORTNAME."_flickr_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Youtube Profile URL",
	"desc" => "Enter Youtube Profile URL",
	"id" => SHORTNAME."_youtube_url",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Vimeo Username",
	"desc" => "Enter Vimeo username",
	"id" => SHORTNAME."_vimeo_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Tumblr Username",
	"desc" => "Enter Tumblr username",
	"id" => SHORTNAME."_tumblr_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Dribbble Username",
	"desc" => "Enter Dribbble username",
	"id" => SHORTNAME."_dribbble_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Linkedin URL",
	"desc" => "Enter full Linkedin URL",
	"id" => SHORTNAME."_linkedin_url",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Pinterest Username",
	"desc" => "Enter Pinterest username",
	"id" => SHORTNAME."_pinterest_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Instagram Username",
	"desc" => "Enter Instagram username",
	"id" => SHORTNAME."_instagram_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Behance Username",
	"desc" => "Enter Behance username",
	"id" => SHORTNAME."_behance_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "500px Profile URL",
	"desc" => "Enter 500px Profile URL",
	"id" => SHORTNAME."_500px_url",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "<h2>Photo Stream</h2>Select photo stream photo source. It displays before footer area",
	"desc" => "",
	"id" => SHORTNAME."_photostream",
	"type" => "select",
	"options" => array(
		'' => 'Disable Photo Stream',
		'instagram' => 'Instagram',
		'flickr' => 'Flickr',
	),
	"std" => ''
),
/*array( "name" => "Number of Photo Stream row",
	"desc" => "",
	"id" => SHORTNAME."_photostream_row",
	"type" => "select",
	"options" => array(
		1 => 1,
		2 => 2,
	),
	"std" => 2
),*/
array( "name" => "Connect with your Instagram account",
	"desc" => "",
	"id" => SHORTNAME."_instagram_account_notice",
	"type" => "html",
	"html" => "<div class='html_inline_wrapper'>".$instagram_widget_settings_notice."</div>",
),

array( "name" => "Flickr ID <a href=\"http://idgettr.com/\" target=\"_blank\">Find your Flickr ID here</a>",
	"desc" => "Enter Flickr ID",
	"id" => SHORTNAME."_flickr_id",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "type" => "close"),

//End fifth tab "Social Profiles"


//Begin second tab "Script"
array( "name" => "Script",
	"type" => "section",
	"icon" => "dashicons-format-aside",
),

array( "type" => "open"),

array( "name" => "<h2>CSS Settings</h2>Custom CSS for desktop",
	"desc" => "You can add your custom CSS here",
	"id" => SHORTNAME."_custom_css",
	"type" => "textarea",
	"std" => "",
	'validation' => '',
),

array( "name" => "Custom CSS for iPad Portrait View",
	"desc" => "You can add your custom CSS here",
	"id" => SHORTNAME."_custom_css_tablet_portrait",
	"type" => "textarea",
	"std" => "",
	'validation' => '',
),

array( "name" => "Custom CSS for iPhone Landscape View",
	"desc" => "You can add your custom CSS here",
	"id" => SHORTNAME."_custom_css_mobile_landscape",
	"type" => "textarea",
	"std" => "",
	'validation' => '',
),

array( "name" => "Custom CSS for iPhone Portrait View",
	"desc" => "You can add your custom CSS here",
	"id" => SHORTNAME."_custom_css_mobile_portrait",
	"type" => "textarea",
	"std" => "",
	'validation' => '',
),

array( "name" => "<h2>Optimisation Settings</h2>Combine and compress theme's CSS",
	"desc" => "Combine and compress all CSS files to one. Help reduce page load time. NOTE: If you enable child theme CSS compression is not support",
	"id" => SHORTNAME."_advance_combine_css",
	"type" => "iphone_checkboxes",
	"std" => 1
),

array( "name" => "Cache theme's custom CSS",
	"desc" => "Cache theme custom CSS code which generate by customizer options to help improving loading speed",
	"id" => SHORTNAME."_advance_cache_custom_css",
	"type" => "iphone_checkboxes",
	"std" => 1
),

array( "name" => "Combine and compress theme's javascript",
	"desc" => "Combine and compress all javascript files to one. Help reduce page load time",
	"id" => SHORTNAME."_advance_combine_js",
	"type" => "iphone_checkboxes",
	"std" => 1
),

array( "name" => "Disable WordPress heartbeat API",
	"desc" => "Check this option to disable WordPress heartbeat API to reduce server load and improving site loading speed",
	"id" => SHORTNAME."_advance_wp_heartbeat",
	"type" => "iphone_checkboxes",
	"std" => 0
),

array( "name" => "<h2>Cache Settings</h2>Clear Cache",
	"desc" => "Try to clear cache when you enable javascript and CSS compression and theme went wrong",
	"id" => SHORTNAME."_advance_clear_cache",
	"type" => "html",
	"html" => '<br/><a id="'.SHORTNAME.'_advance_clear_cache" href="'.$api_url.'" class="button">Click here to start clearing cache files</a>',
),
 
array( "type" => "close"),

//Begin second tab "System"
/*array( 	"name" => "System",
		"type" => "section",
		"icon" => "dashicons-dashboard",	
),
array( "type" => "open"),

array( "name" => "<h2>System Information</h2>",
	"desc" => "",
	"id" => SHORTNAME."_system",
	"type" => "html",
	"html" => $system_info_html,
),

array( "type" => "close"),*/
//End second tab "System"


//Begin second tab "Demo"
array( "name" => "Demo-Content",
	"type" => "section",
	"icon" => "dashicons-download",
),

array( "type" => "open"),

array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_import_demo_notice",
	"type" => "html",
	"html" => '<h1>Checklist before Importing Demo</h1><br/><strong>IMPORTANT</strong>: Demo importer can vary in time. The included required plugins need to be installed and activated before you import demo. Please check the Server Environment below to ensure your server meets all requirements for a successful import. <strong>Settings that need attention will be listed in red</strong>.
	',
),
array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_import_demo_content",
	"type" => "html",
	"html" => $system_info_html,
),
 
array( "type" => "close"),

array( 	"name" => "Buy-Another-License",
"type" => "section",
"icon" => "",		
),
array( "type" => "open"),

array( "type" => "close"),

);
?>
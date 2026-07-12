<?php
require_once get_template_directory() . "/modules/class-tgm-plugin-activation.php";
add_action( 'tgmpa_register', 'photography_require_plugins' );

function photography_require_plugins() {
 
    $plugins = array(
	    array(
	        'name'               => 'Photography Theme Custom Post Type',
	        'slug'               => 'photography-custom-post',
	        'source'             => 'https://themegoods-assets.b-cdn.net/photography-custom-post/photography-custom-post-v5.2.3.zip',
	        'required'           => true, 
	        'version'            => '5.2.3',
	    ),
	    array(
	        'name'               => 'One Click Demo Import',
	        'slug'      		 => 'one-click-demo-import',
	        'required'           => true, 
	        'version'            => '2.5.1',
	    ),
	    array(
	        'name'               => 'Revolution Slider',
	        'slug'               => 'revslider',
	        'source'             => 'https://themegoods-assets.b-cdn.net/revslider/revslider-v6.6.7.zip',
	        'required'           => true, 
	        'version'            => '6.6.7',
	    ),
	    array(
			'name'               => 'Envato Market',
			'slug'               => 'envato-market',
			'source'             => 'https://themegoods-assets.b-cdn.net/envato-market/envato-market-v2.0.7.zip',
			'required'           => true, 
			'version'            => '2.0.7',
		),
	    array(
	        'name'      => 'Multiple Post Thumbnails',
	        'slug'      => 'multiple-post-thumbnails',
	        'required'  => true, 
	    ),
	    array(
	        'name'      => 'MailChimp for WordPress',
	        'slug'      => 'mailchimp-for-wp',
	        'required'  => false, 
	    ),
	    array(
	        'name'      => 'WooCommerce',
	        'slug'      => 'woocommerce',
	        'required'  => false, 
	    ),
	    array(
	        'name'      => 'Meks Easy Photo Feed Widget',
	        'slug'      => 'meks-easy-instagram-widget',
	        'required'  => false, 
	    ),
	);
	
	$config = array(
		'domain'	=> 'photography',
        'default_path' => '',                      // Default absolute path to pre-packaged plugins.
        'menu'         => 'install-required-plugins', // Menu slug.
        'has_notices'  => true,                    // Show admin notices or not.
        'is_automatic' => true,                   // Automatically activate plugins after installation or not.
        'message'      => '',                      // Message to output right before the plugins table.
        'strings'          => array(
	        'page_title'                      => esc_html__('Install Required Plugins', 'photography' ),
	        'menu_title'                      => esc_html__('Install Plugins', 'photography' ),
	        'installing'                      => esc_html__('Installing Plugin: %s', 'photography' ),
	        'oops'                            => esc_html__('Something went wrong with the plugin API.', 'photography' ),
	        'return'                          => esc_html__('Return to Required Plugins Installer', 'photography' ),
	        'plugin_activated'                => esc_html__('Plugin activated successfully.', 'photography' ),
	        'complete'                        => esc_html__('All plugins installed and activated successfully. %s', 'photography' ),
	        'nag_type'                        => 'update-nag'
	    )
    );
 
    tgmpa( $plugins, $config );
 
}
?>
<?php
//Check if Gutenberg editor is available
if (function_exists( 'register_block_type' )) {
	global $pagenow;
    if($pagenow == 'edit.php')
    {
	    if((isset($_GET['post_type']) && $_GET['post_type'] == 'page') OR (isset($_GET['post_type']) && $_GET['post_type'] == 'portfolios'))
	    {
			// Add gutenberg edit link.
			add_filter( 'page_row_actions', 'photography_add_edit_link', 10, 2 );
			add_filter( 'post_row_actions', 'photography_add_edit_link', 10, 2 );
		}
	}
	
	add_action( 'admin_print_footer_scripts-post-new.php', 'photography_adopt_to_builder', 10 );
	add_action( 'admin_print_footer_scripts-post.php', 'photography_adopt_to_builder', 10 );
}

/**
 * Adds specigic Gutenberg edit link to the posts hover menu.
 *
 * @param  array   $actions Post actions.
 * @param  WP_Post $post    Edited post.
 *
 * @return array          Updated post actions.
 */
function photography_add_edit_link( $actions, $post ) {
	$edit_url = get_edit_post_link( $post->ID, 'raw' );
	$gutenberg_url = add_query_arg( 'gutenberg-editor', '', $edit_url );
	$classic_url = add_query_arg( 'classic-editor', '', $edit_url );

	$title       = _draft_or_post_title( $post->ID );
	$edit_action = array(
		'gutenberg' => sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $gutenberg_url ),
			esc_attr(
				sprintf(
					/* translators: %s: post title */
					__( 'Edit &#8220;%s&#8221; in the Gutenberg editor', 'photography' ),
					$title
				)
			),
			__( 'Gutenberg Editor', 'photography' )
		),
		'classic' => sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( $classic_url ),
			esc_attr(
				sprintf(
					/* translators: %s: post title */
					__( 'Edit &#8220;%s&#8221; in the Classic editor', 'photography' ),
					$title
				)
			),
			__( 'Classic Editor', 'photography' )
		),
	);

	// Insert the Gutenberg Edit action after the Edit action.
	$edit_offset = array_search( 'edit', array_keys( $actions ), true );
	$actions     = array_merge(
		array_slice( $actions, 0, $edit_offset + 1 ),
		$edit_action,
		array_slice( $actions, $edit_offset + 1 )
	);

	return $actions;
}

/**
 * Adopts to the chosen builder. Will add FB button to Gutenberg and trigger FB activation.
 *
 * @since 1.7
 * @access public
 * @return void
 */
function photography_adopt_to_builder() {
	global $post_type, $post;

	if ( is_object( $post )  && ($post_type == 'page' OR $post_type == 'portfolios')) {
		if ( isset( $_GET['gutenberg-editor'] )  OR (!isset( $_GET['gutenberg-editor'] ) && !isset( $_GET['classic-editor'] ))) {
			$editor_label = esc_attr__( 'Edit in Content Builder', 'photography' );
			$post_link = add_query_arg( 'classic-editor', 1, get_edit_post_link( $post->ID, 'raw' ) );
			$button       = '<a href="' . $post_link . '" id="content_builder_switch" class="button button-primary button-large">' . $editor_label . '</a>'; // WPCS: XSS ok.
			?>
			<script type="text/javascript">
			jQuery( window ).load( function() {
				var toolbar = jQuery( '.edit-post-header-toolbar' );

				if ( toolbar.length ) {
					toolbar.append( '<?php echo $button; ?>' );
				}
			} );
			</script>
			<?php
		}
		else if(isset( $_GET['classic-editor'] ) && $_GET['classic-editor'] == 1)
		{
		?>
			<script type="text/javascript">
			jQuery( window ).load( function() {
				jQuery('#enable_builder').trigger('click');
			} );
			</script>
		<?php
		}
	}
}
	
// Disable "Try Gutenberg" panel
remove_action( 'try_gutenberg_panel', 'wp_try_gutenberg_panel' );

add_action( 'enqueue_block_editor_assets', 'photography_custom_link_injection_to_gutenberg_toolbar' );
 
function photography_custom_link_injection_to_gutenberg_toolbar(){
   global $post_type, $post;

   if ( is_object( $post )  && ($post_type == 'page' OR $post_type == 'portfolios')) {
      wp_enqueue_script( 'photography-custom-link-in-toolbar', get_template_directory_uri() . '/functions/gutenberg/custom-link-in-toolbar.js', array(), '', true );   
   }
   
}
?>
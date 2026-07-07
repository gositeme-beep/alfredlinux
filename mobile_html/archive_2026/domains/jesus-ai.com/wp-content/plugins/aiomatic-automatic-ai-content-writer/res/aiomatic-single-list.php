<?php
add_action( 'wp_ajax_aiomatic_get_image', 'aiomatic_get_image' );
function aiomatic_get_image() {
    if(isset($_GET['id']) ){
        $image = wp_get_attachment_image( filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT ), 'thumbnail', false, array( 'id' => 'aiomatic-preview-image' ) );
        $data = array(
            'image'    => $image,
        );
        wp_send_json_success( $data );
    } else {
        wp_send_json_error();
    }
}
add_action( 'wp_ajax_create_post', 'aiomatic_create_post' );
function aiomatic_create_post() {
	check_ajax_referer( 'create_post', 'nonce' );
	$post_title = sanitize_text_field( $_POST['title'] );
	$post_content = wp_kses_post( $_POST['content'] );
	$post_excerpt = sanitize_text_field( $_POST['excerpt'] );
	$submit_status = sanitize_text_field( $_POST['submit_status'] );
	$post_sticky = sanitize_text_field( $_POST['post_sticky'] );
	$post_author = sanitize_text_field( $_POST['post_author'] );
	$aiomatic_image_id = sanitize_text_field( $_POST['aiomatic_image_id'] );
	$post_date = sanitize_text_field( $_POST['post_date'] );
	$post_tags = sanitize_text_field( $_POST['post_tags'] );
	$post_category = stripslashes(sanitize_text_field( $_POST['post_category'] ));
	$post_category = json_decode($post_category, true);
	if ( empty( $post_title ) || empty( $post_content ) ) {
	  wp_send_json_error( array( 'message' => 'Title and Content are required fields' ) );
	}
	$statuses = get_post_statuses();
	$statuses['trash'] = 'Trash';
	if(!array_key_exists($submit_status, $statuses))
	{
		wp_send_json_error( array( 'message' => 'Invalid post status submitted: ' . $submit_status . ' - ' .print_r($statuses, true) ) );
	}
	$author_obj = get_user_by('id', $post_author);
	if($author_obj === false)
	{
		wp_send_json_error( array( 'message' => 'Invalid post author submitted' ) );
	}
	$post_args = array(
		'post_title' => $post_title,
		'post_content' => $post_content,
		'post_excerpt' => $post_excerpt,
		'post_status' => $submit_status,
		'post_author' => $post_author,
		'post_date' => $post_date
	);
    if(!empty($post_tags))
	{
		$post_args['tags_input'] = $post_tags;
	}
	$post_id = wp_insert_post( $post_args );
	if ( is_wp_error( $post_id ) ) {
	  wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
	}
	if ($post_sticky == 'on') 
	{
		stick_post($post_id);
	}
	if(is_array($post_category))
	{
		$default_category = get_option('default_category');
		wp_set_post_categories($post_id, $post_category, true);
		if(is_numeric($default_category))
		{
			if(!in_array($default_category, $post_category))
			{
				$deftrerm = get_term_by('id', $default_category, 'category');
				if($deftrerm !== false)
				{
					wp_remove_object_terms( $post_id, $deftrerm->slug, 'category' );
				}
			}
		}
	}
	if($aiomatic_image_id != '' && is_numeric($aiomatic_image_id))
	{
		$aiomatic_image_id = intval($aiomatic_image_id);
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		set_post_thumbnail($post_id, $aiomatic_image_id);
	}
	wp_send_json_success( array( 'post_id' => $post_id ) );
}
add_action( 'wp_ajax_aiomatic_write_text', 'aiomatic_write_text' );
function aiomatic_write_text() {
	check_ajax_referer( 'openai-single-nonce', 'nonce' );
	if(!isset($_POST['prompt']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (prompt)' ) );
	}
	$prompt = stripslashes(sanitize_text_field( $_POST['prompt'] ));
	if(!isset($_POST['model']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (model)' ) );
	}
	$model = stripslashes(sanitize_text_field( $_POST['model'] ));
	if(!isset($_POST['max_tokens']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (max_tokens)' ) );
	}
	$max_tokens = stripslashes(sanitize_text_field( $_POST['max_tokens'] ));
	if(!isset($_POST['temperature']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (temperature)' ) );
	}
	$temperature = stripslashes(sanitize_text_field( $_POST['temperature'] ));
	if(!isset($_POST['title']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (title)' ) );
	}
	$title = stripslashes(sanitize_text_field( $_POST['title'] ));
	if(!isset($_POST['language']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (language)' ) );
	}
	$language = stripslashes(sanitize_text_field( $_POST['language'] ));
	if(!isset($_POST['writing_style']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (writing_style)' ) );
	}
	$writing_style = stripslashes(sanitize_text_field( $_POST['writing_style'] ));
	if(!isset($_POST['writing_tone']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (writing_tone)' ) );
	}
	$writing_tone = stripslashes(sanitize_text_field( $_POST['writing_tone'] ));
	if(!isset($_POST['topics']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (topics)' ) );
	}
	$topics = stripslashes(sanitize_text_field( $_POST['topics'] ));
	if(!isset($_POST['sections']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (sections)' ) );
	}
	$sections = stripslashes(sanitize_text_field( $_POST['sections'] ));
	if(!isset($_POST['sections_count']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (sections_count)' ) );
	}
	$sections_count = stripslashes(sanitize_text_field( $_POST['sections_count'] ));
	if(!isset($_POST['paragraph_count']))
	{
		wp_send_json_error( array( 'message' => 'Incorrect query sent (paragraph_count)' ) );
	}
	$paragraph_count = stripslashes(sanitize_text_field( $_POST['paragraph_count'] ));
	$temperature = floatval($temperature);
	$max_tokens = intval($max_tokens);
	$all_models = aiomatic_get_all_models();
	if(!in_array($model, $all_models))
    {
        $model = 'text-davinci-003';
    }
	$aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
	if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') {
		wp_send_json_error( array( 'message' => 'You need to enter an OpenAI API key in plugin settings!' ) );
	}
	$new_post_content = '';
	$appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
	$appids = array_filter($appids);
	$token = $appids[array_rand($appids)];

    $prompt = str_replace('%%title%%', $title, $prompt);
	$prompt = str_replace('%%language%%', $language, $prompt);
	$prompt = str_replace('%%writing_style%%', $writing_style, $prompt);
	$prompt = str_replace('%%writing_tone%%', $writing_tone, $prompt);
	$prompt = str_replace('%%topic%%', $topics, $prompt);
	$prompt = str_replace('%%sections%%', $sections, $prompt);
	$prompt = str_replace('%%sections_count%%', $sections_count, $prompt);
	$prompt = str_replace('%%paragraphs_per_section%%', $paragraph_count, $prompt);
	
	$query_token_count = count(aiomatic_encode($prompt));
	$available_tokens = $max_tokens - $query_token_count;
	if($available_tokens <= 16)
	{
		$string_len = strlen($prompt);
		$string_len = $string_len / 2;
		$string_len = intval(0 - $string_len);
		$aicontent = substr($prompt, 0, $string_len);
		$aicontent = trim($aicontent);
		if(empty($aicontent))
		{
			wp_send_json_error( array( 'message' => 'Incorrect prompt provided!' ) );
		}
		$query_token_count = count(aiomatic_encode($aicontent));
		$available_tokens = $max_tokens - $query_token_count;
	}
	$aierror = '';
	$finish_reason = '';
	$generated_text = aiomatic_generate_text($token, $model, $prompt, $available_tokens, $temperature, 1, 0, 0, false, 'singlePostWriter', 0, $finish_reason, $aierror);
	if($generated_text === false)
	{
		wp_send_json_error( array( 'message' => 'Failed to generate AI content: ' . $aierror) );
	}
	else
	{
		$new_post_content = trim(trim(trim($generated_text), '"\''));
	}
	wp_send_json_success( array( 'content' => $new_post_content ) );
}

function aiomatic_single_panel()
{
	$all_models = aiomatic_get_all_models();
	$language_names = array(
		esc_html__("English", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Spanish", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("French", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Italian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Afrikaans", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Albanian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Arabic", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Amharic", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Armenian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Belarusian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Bulgarian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Catalan", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Chinese Simplified", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Croatian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Czech", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Danish", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Dutch", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Estonian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Filipino", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Finnish", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Galician", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("German", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Greek", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Hebrew", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Hindi", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Hungarian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Icelandic", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Indonesian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Irish", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Japanese", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Korean", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Latvian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Lithuanian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Norwegian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Macedonian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Malay", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Maltese", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Persian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Polish", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Portuguese", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Romanian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Russian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Serbian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Slovak", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Slovenian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Swahili", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Swedish", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Thai", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Turkish", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Ukrainian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Vietnamese", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Welsh", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Yiddish", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Tamil", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Azerbaijani", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Kannada", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Basque", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Bengali", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Latin", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Chinese Traditional", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Esperanto", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Georgian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Telugu", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Gujarati", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Haitian Creole", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Urdu", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Burmese", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Bosnian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Cebuano", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Chichewa", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Corsican", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Frisian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Scottish Gaelic", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Hausa", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Hawaian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Hmong", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Igbo", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Javanese", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Kazakh", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Khmer", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Kurdish", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Kyrgyz", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Lao", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Luxembourgish", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Malagasy", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Malayalam", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Maori", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Marathi", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Mongolian", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Nepali", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Pashto", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Punjabi", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Samoan", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Sesotho", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Shona", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Sindhi", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Sinhala", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Somali", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Sundanese", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Swahili", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Tajik", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Uzbek", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Xhosa", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Yoruba", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Zulu", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Assammese", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Aymara", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Bambara", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Bhojpuri", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Dhivehi", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Dogri", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Ewe", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Guarani", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Ilocano", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Kinyarwanda", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Konkani", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Krio", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Kurdish - Sorani", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Lingala", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Luganda", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Maithili", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Meiteilon", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Mizo", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Odia", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Oromo", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Quechua", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Sanskrit", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Sepedi", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Tatar", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Tigrinya", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Tsonga", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Turkmen", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Twi", 'aiomatic-automatic-ai-content-writer'),
		esc_html__("Uyghur", 'aiomatic-automatic-ai-content-writer')
	);
?>
<div id="aiomatic-dialog" class="hidden" style="max-width:800px">
  <h3 class="aiomatic-middle"><?php echo esc_html__("Post created as draft. Choose what to do next:", 'aiomatic-automatic-ai-content-writer');?></h3>
  <p class="aiomatic-middle"><button id="aiomatic-success-button" adminurl="<?php echo admin_url('post.php?post=');?>" postid=""><?php echo esc_html__("Edit Created Post", 'aiomatic-automatic-ai-content-writer');?></button></p>
  <p class="aiomatic-middle"><button id="aiomatic-close-button" onclick="window.location='#';"><?php echo esc_html__("Continue Creating Posts With AI", 'aiomatic-automatic-ai-content-writer');?></button></p>
</div>


<div class="wrap">
<h1 class="wp-heading-inline">
<?php echo esc_html__("Single AI Post Creator", 'aiomatic-automatic-ai-content-writer'); ?></h1>
<hr class="wp-header-end">


<form name="aiomatic-single-post" action="<?php echo (aiomatic_isSecure() ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";?>" method="post" id="aiomatic-single-post">
<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">
<div id="post-body-content">

<div id="titlediv">
<div id="titlewrap">
	<h2 class="top_heading"><?php echo esc_html__("Post Title", 'aiomatic-automatic-ai-content-writer'); ?></h2>
	<input type="text" name="post_title" size="30" value="" id="title" spellcheck="true" autocomplete="off" placeholder="Post title" onkeyup="aiomatic_title_empty();">
</div>
	<div class="inside">
		<div id="edit-slug-box" class="hide-if-no-js">
				</div>
			</div></div><!-- /titlediv -->

	<div id="gendiv">
<div id="genwrap">
<hr/>
<h2 class="top_heading"><?php echo esc_html__("Post Sections", 'aiomatic-automatic-ai-content-writer'); ?></h2>
<div class="aiomatic-minor-publishing-actions">
<?php echo esc_html__("Number of created sections:", 'aiomatic-automatic-ai-content-writer'); ?>&nbsp;
<select name="section_count" id="section_count" class="postform">
	<option value="1">1</option>
	<option value="2" selected>2</option>
	<option value="3">3</option>
	<option value="4">4</option>
	<option value="5">5</option>
	<option value="6">6</option>
	<option value="7">7</option>
	<option value="8">8</option>
	<option value="9">9</option>
	<option value="10">10</option>
	<option value="11">11</option>
	<option value="12">12</option>
	<option value="13">13</option>
	<option value="14">14</option>
	<option value="15">15</option>
	<option value="16">16</option>
	<option value="17">17</option>
	<option value="18">18</option>
	<option value="19">19</option>
	<option value="20">20</option>
</select>&nbsp;
<input type="button" name="generate_sections" id="generate_sections" class="button button-primary button-large" value="Generate Sections">
</div>
	<textarea rows="5" name="post_sections" size="30" value="" id="post_sections" spellcheck="true" autocomplete="off" placeholder="Post Sections" class="coderevolution_gutenberg_input"></textarea>
</div>
	<div class="inside">
		<div id="gen-slug-box" class="hide-if-no-js">
				</div>
			</div></div><!-- /gendiv -->
			<hr/>
			<h2 class="top_heading"><?php echo esc_html__("Post Content", 'aiomatic-automatic-ai-content-writer'); ?></h2>
			<div class="aiomatic-minor-publishing-actions">
<?php echo esc_html__("Number of paragraphs per section:", 'aiomatic-automatic-ai-content-writer'); ?>&nbsp;
<select name="paragraph_count" id="paragraph_count" class="postform">
	<option value="1">1</option>
	<option value="2">2</option>
	<option value="3" selected>3</option>
	<option value="4">4</option>
	<option value="5">5</option>
	<option value="6">6</option>
	<option value="7">7</option>
	<option value="8">8</option>
	<option value="9">9</option>
	<option value="10">10</option>
	<option value="11">11</option>
	<option value="12">12</option>
	<option value="13">13</option>
	<option value="14">14</option>
	<option value="15">15</option>
	<option value="16">16</option>
	<option value="17">17</option>
	<option value="18">18</option>
	<option value="19">19</option>
	<option value="20">20</option>
	<option value="21">21</option>
	<option value="22">22</option>
	<option value="23">23</option>
	<option value="24">24</option>
	<option value="25">25</option>
	<option value="26">26</option>
	<option value="27">27</option>
	<option value="28">28</option>
	<option value="29">29</option>
	<option value="30">30</option>
</select>&nbsp;
<input type="button" name="generate_paragraphs" id="generate_paragraphs" class="button button-primary button-large" value="Generate Content">
</div>
	<?php
          $settings = array(
            'textarea_name' => 'post_content',
            'media_buttons' => true,
            'quicktags' => true,
            'tabindex' => '4'
          );
          wp_editor( '', 'post_content', $settings );
		  wp_nonce_field( 'create_post', 'create_post_nonce' );
        ?>

<div id="excdiv">
<div id="excwrap">
<hr/>
<h2 class="top_heading"><?php echo esc_html__("Post Excerpt", 'aiomatic-automatic-ai-content-writer'); ?></h2>
<div class="aiomatic-minor-publishing-actions">
<input type="button" name="generate_excerpt" id="generate_excerpt" class="button button-primary button-large" value="Generate Excerpt">
</div>
	<textarea rows="5" name="post_excerpt" size="30" value="" id="post_excerpt" spellcheck="true" autocomplete="off" placeholder="Post Excerpt" class="coderevolution_gutenberg_input"></textarea>
</div>
	<div class="inside">
		<div id="exc-slug-box" class="hide-if-no-js">
				</div>
			</div></div><!-- /gendiv -->


<div id="publishdiv">
<div id="publishwrap">
<hr/>
<div id="major-publishing-actions">
	<div class="coderevolution_gutenberg_input" id="publishing-action">
		<span class="spinner"></span>
					<input type="submit" name="publish" id="post_publish" class="coderevolution_gutenberg_input button button-primary button-large" value="Create Post">				</div>
					
	<div class="clear"></div>
</div>
</div>
	<div class="inside">
		<div id="publish-slug-box" class="hide-if-no-js">
				</div>
			</div></div><!-- /gendiv -->


	</div><!-- /post-body-content -->

	

<div id="postbox-container-1" class="postbox-container">
<div id="side-sortables" class="meta-box-sortables ui-sortable">

<div class="postbox ">
<div class="postbox-header"><h2 class="hndle ui-sortable-handle"><?php echo esc_html__("Topic", 'aiomatic-automatic-ai-content-writer');?></h2>
</div><div class="inside">
<div class="submitbox" id="submitpost">

<div id="minor-publishing">


<p class="aiomatic-middle">To get started, you can enter a topic here and start generating content!</p>
	<div class="aiomatic-minor-publishing-actions">
<textarea rows="5" id="aiomatic_topics" onkeyup="aiomatic_all_empty();" class="coderevolution_gutenberg_input" placeholder="The main topic of the content"></textarea>
					<div class="clear"></div>
					<p><input type="button" name="generate_title" id="generate_title" class="coderevolution_gutenberg_input button button-primary button-large" value="Generate Title"></p>
					<p><input type="button" name="generate_all" id="generate_all" class="coderevolution_gutenberg_input button button-primary button-large" value="Generate All"></p>
					<div class="clear"></div>
	</div>

	<div class="clear"></div>
</div>
</div>
	</div>
</div>

<div class="postbox ">
<div class="postbox-header"><h2 class="hndle ui-sortable-handle"><div class="tool" data-tip="Set the general parameters for your generated content."><?php echo esc_html__("Post Options", 'aiomatic-automatic-ai-content-writer');?>&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div></h2>
</div><div class="inside">
<div class="submitbox" id="otherpost">

<div id="other-publishing">


	<div class="aiomatic-minor-publishing-actions">
	<div class="cr-align-left">
	<div class="tool" data-tip="Set the post status.">Status:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
							<select id="submit_status" name="submit_status" class="coderevolution_gutenberg_input">
							<option value="draft" selected><?php echo esc_html__("Draft", 'aiomatic-automatic-ai-content-writer');?></option>
							<option value="pending"><?php echo esc_html__("Pending", 'aiomatic-automatic-ai-content-writer');?></option>
							<option value="publish"><?php echo esc_html__("Published", 'aiomatic-automatic-ai-content-writer');?></option>
							<option value="private"><?php echo esc_html__("Private", 'aiomatic-automatic-ai-content-writer');?></option>
							<option value="trash"><?php echo esc_html__("Trash", 'aiomatic-automatic-ai-content-writer');?></option>
							</select> 
					<div class="clear"></div>
					<div class="cr-align-left">
	<div class="tool" data-tip="Stick this post to the front page.">Sticky:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
							<select id="post_sticky" name="post_sticky" class="coderevolution_gutenberg_input">
							<option value="no"><?php echo esc_html__("No", 'aiomatic-automatic-ai-content-writer');?></option>
							<option value="yes"><?php echo esc_html__("Yes", 'aiomatic-automatic-ai-content-writer');?></option>
							</select> 
					<div class="clear"></div>
					<div class="cr-align-left">
	<div class="tool" data-tip="Set the post author.">Author:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
<?php
	$curruser = get_current_user_id();
    wp_dropdown_users(['class' => 'coderevolution_gutenberg_input', 'id' => 'post_author', 'name' => 'post_author', 'selected' => $curruser, 'role__in' => array('administrator', 'editor', 'author', 'contributor')]);
?>
					<div class="clear"></div>
					<div class="cr-align-left">
	<div class="tool" data-tip="Set the post publish date.">Publish Date:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
							<input type="datetime-local" id="post_date" name="post_date" value="<?php echo date('Y-m-d H:i:s'); ?>" class="coderevolution_gutenberg_input" />
					<div class="clear"></div>
					<div class="cr-align-left">
	<div class="tool" data-tip="Set the post categories.">Post Categories:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
							<?php
$default_category = get_option('default_category');
$args = array(
	'orderby'          => 'name',
	'hide_empty'       => 0,
	'echo'             => 0,
	'class'            => 'coderevolution_gutenberg_input',
	'id'               => 'post_category',
	'name'             => 'post_category',
	'selected'         => $default_category
);
$select_cats = wp_dropdown_categories($args);
$select_cats = str_replace( "name='post_category'", "name='post_category[]' multiple='multiple'", $select_cats );
$select_cats = str_replace( 'name="post_category"', 'name="post_category[]" multiple="multiple"', $select_cats );
echo $select_cats;
?>
					<div class="clear"></div><div class="cr-align-left">
	<div class="tool" data-tip="Set the post tags.">Post Tags:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
							<input id="post_tags" name="post_tags" type="text" list="post_tags_list" class="coderevolution_gutenberg_input" value=""/>
							<datalist id="post_tags_list">
<?php
$xtags = get_tags(array(
  'hide_empty' => false
));
if(!is_wp_error($xtags))
{
	foreach ($xtags as $tag) {
		echo '<option>' . $tag->name . '</option>';
	}
}
?>
							</datalist>
<small class="cr-align-left coderevolution_gutenberg_input"><?php echo esc_html__("Separate tags with commas", 'aiomatic-automatic-ai-content-writer');?></small>
					<div class="clear"></div>
	</div>

	<div class="clear"></div>
</div>
</div>
	</div>
</div>

<div class="postbox ">
<div class="postbox-header"><h2 class="hndle ui-sortable-handle"><?php echo esc_html__("Featured Image", 'aiomatic-automatic-ai-content-writer');?></h2>
</div><div class="inside">
<div class="submitbox" id="submitpost">

<div id="minor-publishing">
	<div class="aiomatic-minor-publishing-actions">
<?php
$image = '<div class="coderevolution_gutenberg_input"><img id="aiomatic-preview-image"/></div>';
echo $image; ?>
 <input type="hidden" name="aiomatic_image_id" id="aiomatic_image_id" value="" class="regular-text" />
 <input type='button' class="button-primary" value="<?php esc_attr_e( 'Select an image', 'mytextdomain' ); ?>" id="aiomatic_media_manager"/>


					<div class="clear"></div>
	</div>

	<div class="clear"></div>
</div>
</div>
	</div>
</div>

<div class="postbox ">
<div class="postbox-header"><h2 class="hndle ui-sortable-handle"><div class="tool" data-tip="Set the general parameters for your generated content."><?php echo esc_html__("Content Parameters", 'aiomatic-automatic-ai-content-writer');?>&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div></h2>
</div><div class="inside">
<div class="submitbox" id="otherpost">

<div id="other-publishing">


	<div class="aiomatic-minor-publishing-actions">
	<div class="cr-align-left">
	<div class="tool" data-tip="Set the language of the created content.">Language:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
							<input id="language" name="language" type="text" list="languages" class="coderevolution_gutenberg_input" value="English"/>
							<datalist id="languages">
<?php
foreach($language_names as $ln)
{
	echo '<option>' . $ln . '</option>';
}
?>
							</datalist>
					<div class="clear"></div>
					<div class="cr-align-left">
	<div class="tool" data-tip="Set the writing style for the created content.">Writing Style:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
							<input id="writing_style" name="writing_style" type="text" list="writing_styles" class="coderevolution_gutenberg_input" value="Creative"/>
							<datalist id="writing_styles">
							<option>Informative</option>
							<option>Academic</option>
							<option>Descriptive</option>
							<option>Detailed</option>
							<option>Dramative</option>
							<option>Fiction</option>
							<option>Expository</option>
							<option>Historical</option>
							<option>Dialogue</option>
							<option>Creative</option>
							<option>Critical</option>
							<option>Narrative</option>
							<option>Persuasive</option>
							<option>Reflective</option>
							<option>Argumentative</option>
							<option>Analytical</option>
							<option>Blog</option>
							<option>News</option>
							<option>Casual</option>
							<option>Pastoral</option>
							<option>Personal</option>
							<option>Poetic</option>
							<option>Satirical</option>
							<option>Sensory</option>
							<option>Articulate</option>
							<option>Monologue</option>
							<option>Colloquial</option>
							<option>Comparative</option>
							<option>Concise</option>
							<option>Biographical</option>
							<option>Anecdotal</option>
							<option>Evaluative</option>
							<option>Letter</option>
							<option>Lyrical</option>
							<option>Simple</option>
							<option>Vivid</option>
							<option>Journalistic</option>
							<option>Technical</option>
							<option>Direct</option>
							<option>Emotional</option>
							<option>Metaphorical</option>
							<option>Objective</option>
							<option>Rhetorical</option>
							<option>Theoretical</option>
							<option>Business</option>
							<option>Report</option>
							<option>Research</option>
							</datalist>
					<div class="clear"></div>
					<div class="cr-align-left">
	<div class="tool" data-tip="Set the writing tone for the created content.">Writing Tone:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
							<input id="writing_tone" name="writing_tone" type="text" list="writing_tones" class="coderevolution_gutenberg_input" value="Neutral"/>
							<datalist id="writing_tones">
							<option>Neutral</option>
							<option>Formal</option>
							<option>Assertive</option>
							<option>Cheerful</option>
							<option>Humorous</option>
							<option>Informal</option>
							<option>Inspirational</option>
							<option>Professional</option>
							<option>Emotional</option>
							<option>Persuasive</option>
							<option>Supportive</option>
							<option>Sarcastic</option>
							<option>Condescending</option>
							<option>Skeptical</option>
							<option>Narrative</option>
							<option>Journalistic</option>
							<option>Conversational</option>
							<option>Factual</option>
							<option>Friendly</option>
							<option>Polite</option>
							<option>Scientific</option>
							<option>Sensitive</option>
							<option>Sincere</option>
							<option>Curious</option>
							<option>Dissapointed</option>
							<option>Encouraging</option>
							<option>Optimistic</option>
							<option>Surprised</option>
							<option>Worried</option>
							<option>Confident</option>
							<option>Authoritative</option>
							<option>Nostalgic</option>
							<option>Sympathetic</option>
							<option>Suspenseful</option>
							<option>Romantic</option>
							<option>Serious</option>
							</datalist>
					<div class="clear"></div>
	</div>

	<div class="clear"></div>
</div>
</div>
	</div>
</div>

<div class="postbox ">
<div class="postbox-header"><h2 class="hndle ui-sortable-handle"><div class="tool" data-tip="General settings which will change the text generator behaviour."><?php echo esc_html__("Model Settings", 'aiomatic-automatic-ai-content-writer');?>&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>

</div></h2><div class="paddings_cr"><input type="button" name="aiomatic_toggle_model" id="aiomatic_toggle_model" onclick="aiomatic_call_func()" class="button button-primary button-large" value="Show"></div>
</div><div id="model_holder" class="inside cr_display_none">
<div class="submitbox" id="otherpost">

<div id="other-publishing">


	<div class="aiomatic-minor-publishing-actions">
	<div class="cr-align-left">
	<div class="tool" data-tip="Higher values means the model will take more risks. Between 0 and 1.">Temperature:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
	<input type="number" min="0" max="1" step="0.1" name="temperature" id="temperature" class="coderevolution_gutenberg_input" value="1">
					<div class="clear"></div>
					<div class="cr-align-left">
	<div class="tool" data-tip="Higher values means the model will generate more content. Between 1 and 4000.">Max Tokens:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
	<input type="number" min="1" max="4000" step="1" name="max_tokens" id="max_tokens" class="coderevolution_gutenberg_input" value="4000">
					<div class="clear"></div>
					<div class="cr-align-left">
	<div class="tool" data-tip="Select the AI model you wish to use for the content creator.">Model:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
							<select id="model" name="model" class="coderevolution_gutenberg_input">
<?php
foreach($all_models as $modelx)
{
   echo '<option value="' . $modelx .'"';
   echo '>' . esc_html($modelx) . '</option>';
}
?>
						</select>
					<div class="clear"></div>
	</div>

	<div class="clear"></div>
</div>
</div>
	</div>
</div>

<div class="postbox ">
<div class="postbox-header"><h2 class="hndle ui-sortable-handle"><div class="tool" data-tip="Enter your prompts, based on which each part of the content will be edited."><?php echo esc_html__("Prompts", 'aiomatic-automatic-ai-content-writer');?>&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div></h2><div class="paddings_cr"><input type="button" name="aiomatic_toggle_prompt" id="aiomatic_toggle_prompt" onclick="aiomatic_prompt_func()" class="button button-primary button-large" value="Show"></div>
</div><div id="prompt_holder" class="inside cr_display_none">
<div class="submitbox" id="submitpost">

<div id="prompt-publishing">


	<div class="aiomatic-minor-publishing-actions">
	<div class="cr-align-left">
	<div class="tool" data-tip="Prompt to be used for the Post Title. You can use the following shortcodes: %%topic%%, %%language%%, %%writing_style%%, %%writing_tone%%"><b>Title</b> Prompt:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
<textarea rows="6" id="prompt_title" placeholder="The prompt to be used for the title generator" class="coderevolution_gutenberg_input">Write a title for an article about "%%topic%%" in %%language%%. Style: %%writing_style%%. Tone: %%writing_tone%%. Must be between 40 and 60 characters.</textarea>
					<div class="clear"></div>
	</div>

	<div class="aiomatic-minor-publishing-actions">
	<div class="cr-align-left">
	<div class="tool" data-tip="Prompt to be used for the Post Sections. You can use the following shortcodes: %%title%%, %%language%%, %%writing_style%%, %%writing_tone%%, %%sections_count%%"><b>Sections</b> Prompt:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
<textarea rows="6" id="prompt_sections" placeholder="The prompt to be used for the sections generator" class="coderevolution_gutenberg_input">Write %%sections_count%% consecutive headings for an article about "%%title%%", in %%language%%. Style: %%writing_style%%. Tone: %%writing_tone%%.</textarea>
					<div class="clear"></div>
	</div>

	<div class="aiomatic-minor-publishing-actions">
	<div class="cr-align-left">
	<div class="tool" data-tip="Prompt to be used for the Post Content. You can use the following shortcodes: %%title%%, %%language%%, %%writing_style%%, %%writing_tone%%, %%sections%%, %%paragraphs_per_section%%"><b>Content</b> Prompt:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
<textarea rows="6" id="prompt_content" placeholder="The prompt to be used for the content generator" class="coderevolution_gutenberg_input">Write an article about "%%title%%" in %%language%%. The article is organized by the following headings:

%%sections%%

Write %%paragraphs_per_section%% paragraphs per heading.

Use HTML for formatting, include h2 tags, h3 tags, lists and bold.

Add an introduction and a conclusion.

Style: %%writing_style%%. Tone: %%writing_tone%%.</textarea>
					<div class="clear"></div>
	</div>

	<div class="aiomatic-minor-publishing-actions">
	<div class="cr-align-left">
	<div class="tool" data-tip="Prompt to be used for the Post Excerpt. You can use the following shortcodes: %%title%%, %%language%%, %%writing_style%%, %%writing_tone%%, %%sections%%"><b>Excerpt</b> Prompt:&nbsp;<i class="fas fa-question-circle" focusable="false" aria-hidden="true"></i>
                              </div>
							</div>
<textarea rows="6" id="prompt_excerpt" placeholder="The prompt to be used for the excerpt generator" class="coderevolution_gutenberg_input">Write an excerpt for an article about "%%title%%" in %%language%%. Style: %%writing_style%%. Tone: %%writing_tone%%. Must be between 150 and 250 characters.</textarea>
					<div class="clear"></div>
	</div>

	<div class="clear"></div>
</div>
</div>
	</div>
</div>

</div></div>
</div><!-- /post-body -->
<br class="clear">
</div></form><!-- /poststuff -->

</div>
<?php
}
?>
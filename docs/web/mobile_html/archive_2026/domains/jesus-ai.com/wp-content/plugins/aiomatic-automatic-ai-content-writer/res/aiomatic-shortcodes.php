<?php
function aiomatic_shortcodes_panel()
{
   $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
   if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
   {
      ?>
<h1><?php echo esc_html__("You must add an OpenAI API Key into the plugin's 'Main Settings' menu before you can use this feature!", 'aiomatic-automatic-ai-content-writer');?></h1>
<?php
return;
   }
?>
<div class="wp-header-end"></div>
<div class="wrap gs_popuptype_holder seo_pops">
<h2 class="cr_center"><?php echo esc_html__("Aiomatic Shortcodes", 'aiomatic-automatic-ai-content-writer');?></h2>

</div>


<div class="wrap">
        <h1><?php echo esc_html__("Shortcodes", 'aiomatic-automatic-ai-content-writer');?></h1>
        <nav class="nav-tab-wrapper">
            <a href="#tab-1" class="nav-tab nav-tab-active"><?php echo esc_html__("Built-in Shortcodes", 'aiomatic-automatic-ai-content-writer');?></a>
        </nav>
        <div id="tab-1" class="tab-content">
         <br/>
         <p>
   <h2><?php echo esc_html__("Available shortcodes:", 'aiomatic-automatic-ai-content-writer');?></h2> <ul><li><strong>[aiomatic-text-completion-form]</strong> <?php echo esc_html__("to add a form similar to OpenAI's Text Completion Playground, to generate AI written text based on prompts.", 'aiomatic-automatic-ai-content-writer');?></li>
   <li><strong>[aiomatic-text-editing-form]</strong> <?php echo esc_html__("to add a form similar to OpenAI's Playground, to generate AI written text based on prompts.", 'aiomatic-automatic-ai-content-writer');?></li>
   <li><strong>[aiomatic-image-generator-form]</strong> <?php echo esc_html__("to add a form to generate AI images (GPT-3) based on prompts.", 'aiomatic-automatic-ai-content-writer');?></li>
   <li><strong>[aiomatic-stable-image-generator-form]</strong> <?php echo esc_html__("to add a form to generate AI images (Stable Diffusion) based on prompts.", 'aiomatic-automatic-ai-content-writer');?></li>
   <li><strong>[aiomatic-chat-form]</strong> <?php echo esc_html__("to add a form to generate a chat similar to ChatGPT. However, please note that this is not ChatGPT, but instead it is a custom chatbot built on top of OpenAI API.", 'aiomatic-automatic-ai-content-writer');?></li>
   <li><strong>[aiomatic-article]</strong> <?php echo esc_html__("to automatically write an article based on the 'seed_expre' argument of the post content/excerpt/title where the shortcode is placed,", 'aiomatic-automatic-ai-content-writer');?></li>
   <li><strong>[aiomatic-image]</strong> <?php echo esc_html__("to automatically create an AI generated image (GPT-3) based on the 'seed_expre' argument of the post content/excerpt/title where the shortcode is placed,", 'aiomatic-automatic-ai-content-writer');?></li>
   <li><strong>[aiomatic-stable-image]</strong> <?php echo esc_html__("to automatically create an AI generated image (Stable Diffusion) based on the 'seed_expre' argument of the post content/excerpt/title where the shortcode is placed,", 'aiomatic-automatic-ai-content-writer');?></li>
   <li><strong>[aiomatic-list-posts]</strong> <?php echo esc_html__("to include a list that contains only posts imported by this plugin, and", 'aiomatic-automatic-ai-content-writer');?></li>
   <li><strong>[aiomatic-display-posts]</strong> <?php echo esc_html__("to include a WordPress like post listing. Usage:", 'aiomatic-automatic-ai-content-writer');?> [aiomatic-display-posts type='any/post/page/...' title_color='#ffffff' excerpt_color='#ffffff' read_more_text="Read More" link_to_source='yes' order='ASC/DESC' orderby='title/ID/author/name/date/rand/comment_count' title_font_size='19px', excerpt_font_size='19px' posts_per_page=number_of_posts_to_show category='posts_category' ruleid='ID_of_aiomatic_rule']</li></ul> 
   <br/><?php echo esc_html__("Example:", 'aiomatic-automatic-ai-content-writer');?> <b>[aiomatic-list-posts type='any' order='ASC' orderby='date' posts_per_page=50 category= '' ruleid='0']</b>
   <br/><?php echo esc_html__("Example 2:", 'aiomatic-automatic-ai-content-writer');?> <b>[aiomatic-display-posts include_excerpt='true' image_size='thumbnail' wrapper='div']</b>
   <br/><?php echo esc_html__("Example 3:", 'aiomatic-automatic-ai-content-writer');?> <b>[aiomatic-article seed_expre='Write an informal article about Climate Change' temperature='1' top_p='1' model='text-davinci-003' presence_penalty='0' frequency_penalty='0' min_char='500' max_tokens='2048' max_tokens='2048' max_seed_tokens='500' max_continue_tokens='500' images="2" headings="3" videos="on" static_content="off" cache_seconds="2592000"]</b>
   <br/><?php echo esc_html__("Example 4:", 'aiomatic-automatic-ai-content-writer');?> <b>[aiomatic-image seed_expre='A high detail photograph of a sports car driving on the highway' image_size='1024x1024' static_content='on' copy_locally='on' cache_seconds='2592000']</b>
   <br/><?php echo esc_html__("Example 5:", 'aiomatic-automatic-ai-content-writer');?> <b>[aiomatic-stable-image seed_expre='A high detail photograph of a sports car driving on the highway' image_size='1024x1024' static_content='on' copy_locally='on' cache_seconds='2592000']</b>
   <br/><?php echo esc_html__("Example 6:", 'aiomatic-automatic-ai-content-writer');?> <b>[aiomatic-text-completion-form temperature='default' top_p='default' model='default' presence_penalty='default' frequency_penalty='default' prompt_templates='' prompt_editable="on"]</b>
   <br/><?php echo esc_html__("Example 7:", 'aiomatic-automatic-ai-content-writer');?> <b>[aiomatic-text-editing-form temperature='default' top_p='default' model='default' prompt_templates='' prompt_editable="on"]</b>
   <br/><?php echo esc_html__("Example 8:", 'aiomatic-automatic-ai-content-writer');?> <b>[aiomatic-image-generator-form image_size='default' prompt_templates='' prompt_editable="on"]</b>
   <br/><?php echo esc_html__("Example 9:", 'aiomatic-automatic-ai-content-writer');?> <b>[aiomatic-stable-image-generator-form image_size='default' prompt_templates='' prompt_editable="on"]</b>
   <br/><?php echo esc_html__("Example 10:", 'aiomatic-automatic-ai-content-writer');?> <b>[aiomatic-chat-form temperature='default' top_p='default' model='default' presence_penalty='default' frequency_penalty='default' instant_response='false' chat_preppend_text='Act as a customer assistant, respond to every question in a helpful way.' user_message_preppend='User:' ai_message_preppend='AI:' ai_first_message='Hello, how can I help you today?' chat_mode='text' persistent='off' prompt_templates='' prompt_editable="on"]</b>
   </p>
   <h2><?php echo esc_html__("Currently supported models to be used in shortcodes:", 'aiomatic-automatic-ai-content-writer');?></h2>
<ul>
<?php
$all_models = aiomatic_get_all_models();
foreach($all_models as $modl)
{
   echo '<li>-&nbsp;' . $modl . '</li>';
}
?>
</ul></p>
        </div>
    </div>
<?php
}
?>
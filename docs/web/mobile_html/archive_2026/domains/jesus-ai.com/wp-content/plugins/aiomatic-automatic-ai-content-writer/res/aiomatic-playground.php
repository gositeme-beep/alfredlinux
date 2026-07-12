<?php
function aiomatic_playground_panel()
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
<h2 class="cr_center"><?php echo esc_html__("Aiomatic Playground", 'aiomatic-automatic-ai-content-writer');?></h2>

</div>


<div class="wrap">
        <h1><?php echo esc_html__("Playgrounds", 'aiomatic-automatic-ai-content-writer');?></h1>
        <nav class="nav-tab-wrapper">
            <a href="#tab-1" class="nav-tab nav-tab-active"><?php echo esc_html__("Text Completion", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-2" class="nav-tab"><?php echo esc_html__("Text Editing", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-3" class="nav-tab"><?php echo esc_html__("DALL-E 2 Image Generator", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-4" class="nav-tab"><?php echo esc_html__("Stable Diffusion Image Generator", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-5" class="nav-tab"><?php echo esc_html__("Aiomatic Chat", 'aiomatic-automatic-ai-content-writer');?></a>
        </nav>
        <div id="tab-1" class="tab-content">
         <br/>
        <?php echo aiomatic_form_shortcode(array( 'temperature' => 'default', 'top_p' => 'default', 'presence_penalty' => 'default', 'frequency_penalty' => 'default', 'model' => 'default' ));?>
        <br/>
        <p class="cr_image_center"><?php echo esc_html__("Shortcode alternative: ", 'aiomatic-automatic-ai-content-writer');?><b>[aiomatic-text-completion-form]</b></p>
        </div>
        <div id="tab-2" class="tab-content">
        <br/>
        <?php echo aiomatic_edit_shortcode(array( 'temperature' => 'default', 'top_p' => 'default', 'model' => 'default' ));?>
        <br/>
        <p class="cr_image_center"><?php echo esc_html__("Shortcode alternative: ", 'aiomatic-automatic-ai-content-writer');?><b>[aiomatic-text-editing-form]</b></p>
        </div>
        <div id="tab-3" class="tab-content">
        <br/>
        <?php echo aiomatic_image_shortcode(array( 'image_size' => 'default' ));?>
        <br/>
        <p class="cr_image_center"><?php echo esc_html__("Shortcode alternative: ", 'aiomatic-automatic-ai-content-writer');?><b>[aiomatic-image-generator-form]</b></p>
        </div>
        <div id="tab-4" class="tab-content">
        <br/>
        <?php echo aiomatic_stable_image_shortcode(array( 'image_size' => 'default' ));?>
        <br/>
        <p class="cr_image_center"><?php echo esc_html__("Shortcode alternative: ", 'aiomatic-automatic-ai-content-writer');?><b>[aiomatic-stable-image-generator-form]</b></p>
        </div>
        <div id="tab-5" class="tab-content">
        <br/>
        <?php echo aiomatic_chat_shortcode(array( 'temperature' => 'default', 'top_p' => 'default', 'presence_penalty' => 'default', 'frequency_penalty' => 'default', 'model' => 'default', 'instant_response' => 'true' ));?>
        <br/>
        <p class="cr_image_center"><?php echo esc_html__("Shortcode alternative: ", 'aiomatic-automatic-ai-content-writer');?><b>[aiomatic-chat-form]</b></p>
        </div>
    </div>
<?php
}
?>
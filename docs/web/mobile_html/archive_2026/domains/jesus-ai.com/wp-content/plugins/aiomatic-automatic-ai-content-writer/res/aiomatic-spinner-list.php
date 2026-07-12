<?php
function aiomatic_spinner_panel()
{
   $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
   if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
   {
      ?>
<h1><?php echo esc_html__("You must add an OpenAI API Key into the plugin's 'Main Settings' menu before you can use this feature!", 'aiomatic-automatic-ai-content-writer');?></h1>
<?php
return;
   }
   $all_models = aiomatic_get_all_models();
   $all_edit_models = array_merge($all_models, AIOMATIC_EDIT_MODELS);
?>
<div class="wp-header-end"></div>
<div class="wrap gs_popuptype_holder seo_pops">
    <div>
        <form id="myForm" method="post" action="<?php if(is_multisite() && is_network_admin()){echo '../options.php';}else{echo 'options.php';}?>">
        <div class="cr_autocomplete">
 <input type="password" id="PreventChromeAutocomplete" 
  name="PreventChromeAutocomplete" autocomplete="address-level4" />
</div>
<?php
    settings_fields('aiomatic_option_group2');
    do_settings_sections('aiomatic_option_group2');
    $aiomatic_Spinner_Settings = get_option('aiomatic_Spinner_Settings', false);
    if (isset($aiomatic_Spinner_Settings['aiomatic_spinning'])) {
        $aiomatic_spinning = $aiomatic_Spinner_Settings['aiomatic_spinning'];
    } else {
        $aiomatic_spinning = '';
    }
    if (isset($aiomatic_Spinner_Settings['post_posts'])) {
        $post_posts = $aiomatic_Spinner_Settings['post_posts'];
    } else {
        $post_posts = '';
    }
    if (isset($aiomatic_Spinner_Settings['post_pages'])) {
        $post_pages = $aiomatic_Spinner_Settings['post_pages'];
    } else {
        $post_pages = '';
    }
    if (isset($aiomatic_Spinner_Settings['post_custom'])) {
        $post_custom = $aiomatic_Spinner_Settings['post_custom'];
    } else {
        $post_custom = '';
    }
    if (isset($aiomatic_Spinner_Settings['disable_tags'])) {
        $disable_tags = $aiomatic_Spinner_Settings['disable_tags'];
    } else {
        $disable_tags = '';
    }
    if (isset($aiomatic_Spinner_Settings['change_status'])) {
        $change_status = $aiomatic_Spinner_Settings['change_status'];
    } else {
        $change_status = '';
    }
    if (isset($aiomatic_Spinner_Settings['delay_post'])) {
        $delay_post = $aiomatic_Spinner_Settings['delay_post'];
    } else {
        $delay_post = '';
    }
    if (isset($aiomatic_Spinner_Settings['run_background'])) {
        $run_background = $aiomatic_Spinner_Settings['run_background'];
    } else {
        $run_background = '';
    }
    if (isset($aiomatic_Spinner_Settings['append_spintax'])) {
        $append_spintax = $aiomatic_Spinner_Settings['append_spintax'];
    } else {
        $append_spintax = '';
    }
    if (isset($aiomatic_Spinner_Settings['ai_featured_image'])) {
        $ai_featured_image = $aiomatic_Spinner_Settings['ai_featured_image'];
    } else {
        $ai_featured_image = '';
    }
    if (isset($aiomatic_Spinner_Settings['ai_featured_image_source'])) {
        $ai_featured_image_source = $aiomatic_Spinner_Settings['ai_featured_image_source'];
    } else {
        $ai_featured_image_source = '';
    }
    if (isset($aiomatic_Spinner_Settings['ai_image_command'])) {
        $ai_image_command = $aiomatic_Spinner_Settings['ai_image_command'];
    } else {
        $ai_image_command = '';
    }
    if (isset($aiomatic_Spinner_Settings['image_size'])) {
        $image_size = $aiomatic_Spinner_Settings['image_size'];
    } else {
        $image_size = '';
    }
    if (isset($aiomatic_Spinner_Settings['min_char'])) {
        $min_char = $aiomatic_Spinner_Settings['min_char'];
    } else {
        $min_char = '';
    }
    if (isset($aiomatic_Spinner_Settings['videos'])) {
        $videos = $aiomatic_Spinner_Settings['videos'];
    } else {
        $videos = '';
    }
    if (isset($aiomatic_Spinner_Settings['headings'])) {
        $headings = $aiomatic_Spinner_Settings['headings'];
    } else {
        $headings = '';
    }
    if (isset($aiomatic_Spinner_Settings['enable_ai_images'])) {
        $enable_ai_images = $aiomatic_Spinner_Settings['enable_ai_images'];
    } else {
        $enable_ai_images = '';
    }
    if (isset($aiomatic_Spinner_Settings['images'])) {
        $images = $aiomatic_Spinner_Settings['images'];
    } else {
        $images = '';
    }
    if (isset($aiomatic_Spinner_Settings['max_tokens'])) {
        $max_tokens = $aiomatic_Spinner_Settings['max_tokens'];
    } else {
        $max_tokens = '';
    }
    if (isset($aiomatic_Spinner_Settings['max_seed_tokens'])) {
        $max_seed_tokens = $aiomatic_Spinner_Settings['max_seed_tokens'];
    } else {
        $max_seed_tokens = '';
    }
    if (isset($aiomatic_Spinner_Settings['max_result_tokens'])) {
        $max_result_tokens = $aiomatic_Spinner_Settings['max_result_tokens'];
    } else {
        $max_result_tokens = '';
    }
    if (isset($aiomatic_Spinner_Settings['max_continue_tokens'])) {
        $max_continue_tokens = $aiomatic_Spinner_Settings['max_continue_tokens'];
    } else {
        $max_continue_tokens = '';
    }
    if (isset($aiomatic_Spinner_Settings['model'])) {
        $model = $aiomatic_Spinner_Settings['model'];
    } else {
        $model = '';
    }
    if (isset($aiomatic_Spinner_Settings['ai_command'])) {
        $ai_command = $aiomatic_Spinner_Settings['ai_command'];
    } else {
        $ai_command = '';
    }
    if (isset($aiomatic_Spinner_Settings['temperature'])) {
        $temperature = $aiomatic_Spinner_Settings['temperature'];
    } else {
        $temperature = '';
    }
    if (isset($aiomatic_Spinner_Settings['top_p'])) {
        $top_p = $aiomatic_Spinner_Settings['top_p'];
    } else {
        $top_p = '';
    }
    if (isset($aiomatic_Spinner_Settings['presence_penalty'])) {
        $presence_penalty = $aiomatic_Spinner_Settings['presence_penalty'];
    } else {
        $presence_penalty = '';
    }
    if (isset($aiomatic_Spinner_Settings['frequency_penalty'])) {
        $frequency_penalty = $aiomatic_Spinner_Settings['frequency_penalty'];
    } else {
        $frequency_penalty = '';
    }
    if (isset($aiomatic_Spinner_Settings['ai_rewriter'])) {
        $ai_rewriter = $aiomatic_Spinner_Settings['ai_rewriter'];
    } else {
        $ai_rewriter = '';
    }
    if (isset($aiomatic_Spinner_Settings['ai_instruction'])) {
        $ai_instruction = $aiomatic_Spinner_Settings['ai_instruction'];
    } else {
        $ai_instruction = '';
    }
    if (isset($aiomatic_Spinner_Settings['ai_instruction_title'])) {
        $ai_instruction_title = $aiomatic_Spinner_Settings['ai_instruction_title'];
    } else {
        $ai_instruction_title = '';
    }
    if (isset($aiomatic_Spinner_Settings['edit_temperature'])) {
        $edit_temperature = $aiomatic_Spinner_Settings['edit_temperature'];
    } else {
        $edit_temperature = '';
    }
    if (isset($aiomatic_Spinner_Settings['edit_top_p'])) {
        $edit_top_p = $aiomatic_Spinner_Settings['edit_top_p'];
    } else {
        $edit_top_p = '';
    }
    if (isset($aiomatic_Spinner_Settings['max_char_chunks'])) {
        $max_char_chunks = $aiomatic_Spinner_Settings['max_char_chunks'];
    } else {
        $max_char_chunks = '';
    }
    if (isset($aiomatic_Spinner_Settings['no_title'])) {
        $no_title = $aiomatic_Spinner_Settings['no_title'];
    } else {
        $no_title = '';
    }
    if (isset($aiomatic_Spinner_Settings['edit_model'])) {
        $edit_model = $aiomatic_Spinner_Settings['edit_model'];
    } else {
        $edit_model = '';
    }
    if (isset($aiomatic_Spinner_Settings['no_html_check'])) {
        $no_html_check = $aiomatic_Spinner_Settings['no_html_check'];
    } else {
        $no_html_check = '';
    }
    if (isset($aiomatic_Spinner_Settings['protect_html'])) {
        $protect_html = $aiomatic_Spinner_Settings['protect_html'];
    } else {
        $protect_html = '';
    }
    if (isset($aiomatic_Spinner_Settings['no_content'])) {
        $no_content = $aiomatic_Spinner_Settings['no_content'];
    } else {
        $no_content = '';
    }
    if (isset($_GET['settings-updated'])) {
?>
<div id="message" class="updated">
<p class="cr_saved_notif"><strong>&nbsp;<?php echo esc_html__('Settings saved.', 'aiomatic-automatic-ai-content-writer');?></strong></p>
</div>
<?php
$get = get_option('coderevolution_settings_changed', 0);
if($get == 1)
{
    delete_option('coderevolution_settings_changed');
?>
<div id="message" class="updated">
<p class="cr_failed_notif"><strong>&nbsp;<?php echo esc_html__('Plugin registration failed!', 'aiomatic-automatic-ai-content-writer');?></strong></p>
</div>
<?php 
}
elseif($get == 2)
{
        delete_option('coderevolution_settings_changed');
?>
<div id="message" class="updated">
<p class="cr_saved_notif"><strong>&nbsp;<?php echo esc_html__('Plugin registration successful!', 'aiomatic-automatic-ai-content-writer');?></strong></p>
</div>
<?php 
}
elseif($get != 0)
{
        delete_option('coderevolution_settings_changed');
?>
<div id="message" class="updated">
<p class="cr_failed_notif"><strong>&nbsp;<?php echo esc_html($get);?></strong></p>
</div>
<?php 
}
    }
?>
<div>

<div class="aiomatic_class">
<table class="widefat">
    <tr>
    <td>
        <h1><span class="gs-sub-heading"><b><?php echo esc_html__("Article Editor:", 'aiomatic-automatic-ai-content-writer');?></b>&nbsp;</span>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Enable or disable automatic post modifications every time you publish a new post (manually or automatically).", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div></h1>
                    </td>
                    <td>
        <div class="slideThree">	
                            <input class="input-checkbox" type="checkbox" id="aiomatic_spinning" name="aiomatic_Spinner_Settings[aiomatic_spinning]"<?php
    if ($aiomatic_spinning == 'on')
        echo ' checked ';
?>>
                            <label for="aiomatic_spinning"></label>
                    </div>
                    </td>
                    </tr>
                    </table>
                    </div>
                    <div>
                    <hr/>
                    <table class="widefat"><tr><td colspan="2">
                    <tr><td colspan="2">
                    <h2><?php echo esc_html__("AI Content Rewriter Options:", 'aiomatic-automatic-ai-content-writer');?></h2>
        </td></tr><tr><td>
        <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("The plugin will rewrite the textual content of the published post, using AI.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b class="wpaiomatic-delete"><?php echo esc_html__("Enable AI Content Rewriting For The Published Posts:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <select id="ai_rewriter" name="aiomatic_Spinner_Settings[ai_rewriter]" onchange="mainChanged();" >
                              <option value="enabled"<?php
                                 if ($ai_rewriter == "enabled") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Enabled", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="disabled"<?php
                                 if ($ai_rewriter == "disabled") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Disabled", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
        </div>
        </td></tr><tr class="hideMain">
            <td class="cr_min_width_200">
                <div>
                    <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                            echo esc_html__("Select the AI model to use for text editing. Currently, the specialized edit models from OpenAI are in beta, because of this, at the moment, it is recommended to use a completion model.", 'aiomatic-automatic-ai-content-writer');
                            ?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("AI Model To Use For Text Editing:", 'aiomatic-automatic-ai-content-writer');?></b>
            </td>
            <td>
            <select id="edit_model" name="aiomatic_Spinner_Settings[edit_model]" >
<?php
foreach($all_edit_models as $modelx)
{
   echo '<option value="' . $modelx .'"';
   if ($edit_model == $modelx) 
   {
       echo " selected";
   }
   echo '>' . esc_html($modelx) . '</option>';
}
?>
            </select>
            </div>
            </td>
            </tr><tr class="hideMain">
            <td class="cr_min_width_200">
                <div>
                    <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                            echo esc_html__("Do you want to skip post title editing?", 'aiomatic-automatic-ai-content-writer');
                            ?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Skip Post Title Editing:", 'aiomatic-automatic-ai-content-writer');?></b>
            </td>
            <td>
            <input type="checkbox" id="no_title" name="aiomatic_Spinner_Settings[no_title]"<?php
    if ($no_title == 'on')
        echo ' checked ';
?>>
            </div>
            </td>
            </tr><tr class="hideMain"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Instruction for the AI editor, to edit post title. Nested shortcodes from other plugins also supported here.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Instructions to Send For the AI Editor (Title Editing):", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <textarea rows="1" name="aiomatic_Spinner_Settings[ai_instruction_title]" placeholder="Please insert a title editor instruction"><?php
    echo esc_textarea($ai_instruction_title);
?></textarea>
        </div>
        </td></tr>
            <tr class="hideMain">
            <td class="cr_min_width_200">
                <div>
                    <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                            echo esc_html__("Do you want to skip post content editing?", 'aiomatic-automatic-ai-content-writer');
                            ?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Skip Post Content Editing:", 'aiomatic-automatic-ai-content-writer');?></b>
            </td>
            <td>
            <input type="checkbox" id="no_content" name="aiomatic_Spinner_Settings[no_content]"<?php
    if ($no_content == 'on')
        echo ' checked ';
?>>
            </div>
            </td>
            </tr><tr class="hideMain"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Instruction for the AI editor, to edit post content. Nested shortcodes from other plugins also supported here.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Instructions to Send For the AI Editor (Content Editing):", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <textarea rows="1" name="aiomatic_Spinner_Settings[ai_instruction]" placeholder="Please insert a content editor instruction"><?php
    echo esc_textarea($ai_instruction);
?></textarea>
        </div>
        </td></tr>
            <tr class="hideMain"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("What sampling temperature to use. Higher values means the model will take more risks. Try 0.9 for more creative applications, and 0 (argmax sampling) for ones with a well-defined answer. We generally recommend altering this or top_p but not both.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("AI Content Editor Temperature:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="0" step="0.1" id="edit_temperature" name="aiomatic_Spinner_Settings[edit_temperature]" class="cr_450" value="<?php echo esc_html($edit_temperature);?>" placeholder="0">
        </div>
        </td></tr><tr class="hideMain"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("An alternative to sampling with temperature, called nucleus sampling, where the model considers the results of the tokens with top_p probability mass. So 0.1 means only the tokens comprising the top 10% probability mass are considered. We generally recommend altering this or temperature but not both.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("AI Content Editor Top_p:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="0" step="0.1" max="1" id="edit_top_p" name="aiomatic_Spinner_Settings[edit_top_p]" class="cr_450" value="<?php echo esc_html($edit_top_p);?>" placeholder="1">
        </div>
        </td></tr><tr class="hideMain"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Currently, as the AI editor is in beta, it might have difficulties editing longer texts. If you encounter this issue, you can limit the chunk size which is sent to the AI editor (in characters). Leave this blank if editing works well in your case.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Maximum Character Chunk Size To Send To The AI Editor (Optional):", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="100" step="1" id="max_char_chunks" name="aiomatic_Spinner_Settings[max_char_chunks]" class="cr_450" value="<?php echo esc_html($max_char_chunks);?>" placeholder="Max character count">
        </div>
        </td></tr>
            <tr class="hideMain">
            <td class="cr_min_width_200">
                <div>
                    <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                            echo esc_html__("Currently, because of an issue with the AI editor, sometimes it might remove parts of the HTML content you send to it for editing. The Aiomatic plugin can check if this happens and not change the post in these cases. If you check this checkbox, the edited content will be published, even if it misses some HTML tags. Do you want to publish edited content even if the AI editor removed some or all HTML content from the text?", 'aiomatic-automatic-ai-content-writer');
                            ?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Publish Edited Content Even if the AI Removed Parts of the HTML Text:", 'aiomatic-automatic-ai-content-writer');?></b>
            </td>
            <td>
            <input type="checkbox" id="no_html_check" name="aiomatic_Spinner_Settings[no_html_check]"<?php
    if ($no_html_check == 'on')
        echo ' checked ';
?>>
            </div>
            </td>
            </tr><tr class="hideMain">
            <td class="cr_min_width_200">
                <div>
                    <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                            echo esc_html__("Do you want to protect HTML tags in edited text? This will add to the prompt you enter, a phrase which specifies to protect HTML tags from the edited text.", 'aiomatic-automatic-ai-content-writer');
                            ?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Protect HTML Tags in Edited Text:", 'aiomatic-automatic-ai-content-writer');?></b>
            </td>
            <td>
            <input type="checkbox" id="protect_html" name="aiomatic_Spinner_Settings[protect_html]"<?php
    if ($protect_html == 'on')
        echo ' checked ';
?>>
            </div>
            </td>
            </tr>
                    <tr><td colspan="2">
                    <h2><?php echo esc_html__("AI Generated Featured Image Options:", 'aiomatic-automatic-ai-content-writer');?></h2>
        </td></tr><tr><td>
        <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("The plugin will generate AI generated or royalty free images, that will be assigned as featured images for posts.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b class="wpaiomatic-delete"><?php echo esc_html__("Enable Featured Image Assignation For Published Posts:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <select id="ai_featured_image" name="aiomatic_Spinner_Settings[ai_featured_image]" onchange="mainChanged2();"  >
                              <option value="enabled"<?php
                                 if ($ai_featured_image == "enabled") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Enabled", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="disabled"<?php
                                 if ($ai_featured_image == "disabled") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Disabled", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
        </div>
        </td></tr><tr class="hideMain2"><td>
        <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Select the source of the created featured images.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Featured Image Source:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <select id="ai_featured_image_source" name="aiomatic_Spinner_Settings[ai_featured_image_source]">
                              <option value="1"<?php
                                 if ($ai_featured_image_source == "1") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("OpenAI/AiomaticAPI", 'aiomatic-automatic-ai-content-writer');?></option>
                                 <?php
                                 if (isset($aiomatic_Main_Settings['stability_app_id']) && trim($aiomatic_Main_Settings['stability_app_id']) != '')
                                 {
                                 ?>
                                 <option value="2"<?php
                                    if ($ai_featured_image_source == "2") {
                                        echo " selected";
                                    }
                                    ?>><?php echo esc_html__("Stability.AI", 'aiomatic-automatic-ai-content-writer');?></option>
                                <?php
                                 }
                                 ?>
                              <option value="0"<?php
                                 if ($ai_featured_image_source == "0") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Royalty Free", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
        </div>
        </td></tr><tr class="hideMain2"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Set an seed command you want to send to OpenAI image generator. This command can be any given task or order, based on which, it will generate content for posts. You can use the following shortcodes here: %%post_title%%, %%post_content%%, %%post_content_plain_text%%, %%post_excerpt%%, %%post_cats%%, %%post_tags%%, %%featured_image%%, %%smart_hashtags%%, %%blog_title%%, %%author_name%%, %%post_link%%, %%random_sentence%%, %%random_sentence2%%. You can also use custom fields (post meta) that it's assigned to posts using custom shortcodes in this format: %%!custom_field_slug!%%. Example: if you wish to add data that is imported from the custom field post_data, you should use this shortcode: %%!post_data!%%. The length of this command should not be greater than the max token count set in the settings for the seed command - Update: nested shortcodes also supported (shortcodes generated by rules from other plugins). If you use Royalty Free Images as a source, you can also set their keywords here, if no keywords set, they will be automatically generated.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Seed Command To Send To OpenAI Image Generator:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <textarea rows="1" name="aiomatic_Spinner_Settings[ai_image_command]" placeholder="Please insert a command for the AI image generator"><?php
    echo esc_textarea($ai_image_command);
?></textarea>
        </div>
        </td></tr><tr class="hideMain2"><td>
        <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Set the size of the generated featured image.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Generated Featured Image Size:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <select id="image_size" name="aiomatic_Spinner_Settings[image_size]" >
                              <option value="256x256"<?php
                                 if ($image_size == "256x256") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("256x256", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="512x512"<?php
                                 if ($image_size == "512x512") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("512x512", 'aiomatic-automatic-ai-content-writer');?></option>
                                 <option value="1024x1024"<?php
                                 if ($image_size == "1024x1024") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("1024x1024", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
        </div>
        </td></tr><tr><td>
                    <h2><?php echo esc_html__("AI Content Completition Options:", 'aiomatic-automatic-ai-content-writer');?></h2>
        </td></tr><tr><td>
        <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("The plugin will generate AI content, that will be preppended or appended to each published post's content.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b class="wpaiomatic-delete"><?php echo esc_html__("Add AI Generated Content To The Published Posts:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <select id="append_spintax" name="aiomatic_Spinner_Settings[append_spintax]" onchange="mainChanged3();" >
                              <option value="append"<?php
                                 if ($append_spintax == "append") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Append To The End", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="preppend"<?php
                                 if ($append_spintax == "preppend") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Preppend To The Beginning", 'aiomatic-automatic-ai-content-writer');?></option>
                                 <option value="disabled"<?php
                                 if ($append_spintax == "disabled") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Disabled", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
        </div>
        </td></tr>
                    <tr class="hideMain3"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Set an seed command you want to send to OpenAI. This command can be any given task or order, based on which, it will generate content for posts. You can use the following shortcodes here: %%post_title%%, %%post_content%%, %%post_content_plain_text%%, %%post_excerpt%%, %%post_cats%%, %%post_tags%%, %%featured_image%%, %%smart_hashtags%%, %%blog_title%%, %%author_name%%, %%post_link%%, %%random_sentence%%, %%random_sentence2%%. You can also use custom fields (post meta) that it's assigned to posts using custom shortcodes in this format: %%!custom_field_slug!%%. Example: if you wish to add data that is imported from the custom field post_data, you should use this shortcode: %%!post_data!%%. The length of this command should not be greater than the max token count set in the settings for the seed command - Update: nested shortcodes also supported (shortcodes generated by rules from other plugins).", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Seed Command To Send To OpenAI:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <textarea rows="1" name="aiomatic_Spinner_Settings[ai_command]" placeholder="Please insert a command for the AI"><?php
    echo esc_textarea($ai_command);
?></textarea>
        </div>
        </td></tr><tr class="hideMain3"><td>
        <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo sprintf( wp_kses( __( "Select the minimum number of characters that the posts additional content should have. If the API returns content which has fewer characters than this number, another API call will be made, until this character limit is met. Please check about API rate limiting <a href='%s'>here</a>.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://beta.openai.com/docs/api-reference/introduction' ) );
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Created Post Minimum Character Count:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <input type="number" min="1" step="1" name="aiomatic_Spinner_Settings[min_char]" value="<?php echo esc_html($min_char);?>" placeholder="Please insert a minimum number of characters for posts" class="cr_width_full">
        </div>
        </td></tr><tr class="hideMain3"><td colspan="2">
                    <h2><?php echo esc_html__("Rich Content Creation Options:", 'aiomatic-automatic-ai-content-writer');?></h2>
        </td></tr>
        <tr class="hideMain3">
            <td class="cr_min_width_200">
                <div>
                    <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                            echo esc_html__("Set the maximum number of related headings to add to the created post content. This feature will use the 'People Also Ask' feature from Google and Bing. By default, the Bing engine is scraped, if you want to enable also Google scraping, add a SerpAPI key in the plugin's 'Main Settings' menu -> 'SerpAPI API Key' settings field.", 'aiomatic-automatic-ai-content-writer');
                            ?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Maximum Number Of Related Headings to Add To The Content:", 'aiomatic-automatic-ai-content-writer');?></b>
            </td>
            <td>
            <input type="number" min="0" name="aiomatic_Spinner_Settings[headings]" value="<?php echo esc_html($headings);?>" placeholder="Max heading count" class="cr_width_full">
            </div>
            </td>
            </tr>
            <tr class="hideMain3">
            <td class="cr_min_width_200">
                <div>
                    <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                            echo esc_html__("Set the maximum number of related images to add to the created post content. This feature will use the 'Royalty Free Image' settings from the plugin's 'Main Settings' menu.'", 'aiomatic-automatic-ai-content-writer');
                            ?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Maximum Number Of Related Images to Add To The Content:", 'aiomatic-automatic-ai-content-writer');?></b>
            </td>
            <td>
            <input type="number" min="0" name="aiomatic_Spinner_Settings[images]" value="<?php echo esc_html($images);?>" placeholder="Max image count" class="cr_width_full">
            </div>
            </td>
            </tr>
            <tr class="hideMain3">
            <td class="cr_min_width_200">
                <div>
                    <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                            echo esc_html__("Do you want to replace the royalty free image with an AI generated image?", 'aiomatic-automatic-ai-content-writer');
                            ?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("AI Image Source:", 'aiomatic-automatic-ai-content-writer');?></b>
            </td>
            <td>
            <select id="enable_ai_images" name="aiomatic_Spinner_Settings[enable_ai_images]" class="cr_width_full">
            <option value="1"<?php
    if ($enable_ai_images == '1' || $enable_ai_images == 'on')
        echo ' selected ';
?>><?php echo esc_html__("OpenAI/AiomaticAPI", 'aiomatic-automatic-ai-content-writer');?></option>
            <?php
            if (isset($aiomatic_Main_Settings['stability_app_id']) && trim($aiomatic_Main_Settings['stability_app_id']) != '')
            {
            ?>
            <option value="2"<?php
    if ($enable_ai_images == '2')
        echo ' selected ';
?>><?php echo esc_html__("Stability.AI", 'aiomatic-automatic-ai-content-writer');?></option>
            <?php
            }
            ?>
            <option value="0"<?php
    if ($enable_ai_images == '0' || $enable_ai_images == '')
        echo ' selected ';
?>><?php echo esc_html__("Royalty Free", 'aiomatic-automatic-ai-content-writer');?></option>
        </select>
            </div>
            </td>
            </tr>
            <tr class="hideMain3">
            <td class="cr_min_width_200">
                <div>
                    <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                            echo esc_html__("Add a related YouTube video to the end of to the created post content. This feature will require you to add at least one YouTube API key in the plugin's 'Main Settings' -> 'YouTube API Key List' settings field.", 'aiomatic-automatic-ai-content-writer');
                            ?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Add A Related Video To The End Of The Post:", 'aiomatic-automatic-ai-content-writer');?></b>
            </td>
            <td>
            <input type="checkbox" id="videos" name="aiomatic_Spinner_Settings[videos]"<?php
    if ($videos == 'on')
        echo ' checked ';
?>>
            </div>
            </td>
            </tr>
        <tr class="hideMain3"><td colspan="2">
                    <h2><?php echo esc_html__("OpenAI API Settings:", 'aiomatic-automatic-ai-content-writer');?></h2>
        </td></tr><tr class="hideMain3"><td>
        <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Select the AI Model you want to use.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("AI Model To Use:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <select id="model" name="aiomatic_Spinner_Settings[model]" >
<?php
foreach($all_models as $modelx)
{
   echo '<option value="' . $modelx .'"';
   if ($model == $modelx) 
   {
       echo " selected";
   }
   echo '>' . esc_html($modelx) . '</option>';
}
?>
                           </select>
        </div>
                    </td></tr><tr class="hideMain3"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Set the maximum number of API tokens to use with each request. This will define the length of the resulting API response. Each token usually consists of approximately 4 characters. Note that in this value the number of tokens sent to the API as an article seed will also be counted. The maximum amount which can be set it 2048.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Maximum Total Token Count To Use Per API Request:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="1" step="1" max="4000" id="max_tokens" name="aiomatic_Spinner_Settings[max_tokens]" class="cr_450" value="<?php echo esc_html($max_tokens);?>" placeholder="Maximum Token Count To Spend on Each Request">
        </div>
        </td></tr><tr class="hideMain3"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Set the maximum number of seed API tokens to use with each request. This will define the length of the resulting API response. Each token usually consists of approximately 4 characters. This defines how much content does the API receive each time you call it. If the API gets more initial data, better quality results will be expected. The maximum amount which can be set it 1000.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Maximum Seed Token Count To Use Per API Request:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="1" step="1" max="1000" id="max_seed_tokens" name="aiomatic_Spinner_Settings[max_seed_tokens]" class="cr_450" value="<?php echo esc_html($max_seed_tokens);?>" placeholder="Maximum Seed Token Count To Spend on Each Request">
        </div>
        </td></tr><tr class="hideMain3"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Set the maximum number of result API tokens to use with each request. This will define the length of the resulting API response. Each token usually consists of approximately 4 characters. This defines how much content does the API receive each time you call it. If the API gets more initial data, better quality results will be expected. The maximum amount which can be set it 1000.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Maximum Result Token Count To Use Per API Request:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="1" step="1" max="2048" id="max_result_tokens" name="aiomatic_Spinner_Settings[max_result_tokens]" class="cr_450" value="<?php echo esc_html($max_result_tokens);?>" placeholder="Maximum Result Token Count To Spend on Each Request">
        </div>
        </td></tr><tr class="hideMain3"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Set the maximum number of continue API tokens to use with each request. This will define the length of the resulting API response. Each token usually consists of approximately 4 characters. This defines how much content does the API receive each time you call it. If the API gets more initial data, better quality results will be expected. The maximum amount which can be set it 1000.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Maximum Continue Token Count To Use Per API Request:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="1" step="1" max="2048" id="max_continue_tokens" name="aiomatic_Spinner_Settings[max_continue_tokens]" class="cr_450" value="<?php echo esc_html($max_continue_tokens);?>" placeholder="Maximum Result Continue Count To Spend on Each Request">
        </div>
        </td></tr><tr class="hideMain3"><td colspan="2">
                    <h2><?php echo esc_html__("Advanced OpenAI API Settings:", 'aiomatic-automatic-ai-content-writer');?></h2>
                    
                    </td></tr><tr class="hideMain3"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("What sampling temperature to use. Higher values means the model will take more risks. Try 0.9 for more creative applications, and 0 (argmax sampling) for ones with a well-defined answer. We generally recommend altering this or top_p but not both.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("AI Content Writer Temperature:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="0" step="0.1" id="temperature" name="aiomatic_Spinner_Settings[temperature]" class="cr_450" value="<?php echo esc_html($temperature);?>" placeholder="1">
        </div>
        </td></tr><tr class="hideMain3"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("An alternative to sampling with temperature, called nucleus sampling, where the model considers the results of the tokens with top_p probability mass. So 0.1 means only the tokens comprising the top 10% probability mass are considered. We generally recommend altering this or temperature but not both.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("AI Content Writer Top_p:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="0" step="0.1" max="1" id="top_p" name="aiomatic_Spinner_Settings[top_p]" class="cr_450" value="<?php echo esc_html($top_p);?>" placeholder="1">
        </div>
        </td></tr><tr class="hideMain3"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Number between -2.0 and 2.0. Positive values penalize new tokens based on whether they appear in the text so far, increasing the model's likelihood to talk about new topics.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("AI Presence Penalty:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="-2" step="0.1" max="2" id="presence_penalty" name="aiomatic_Spinner_Settings[presence_penalty]" class="cr_450" value="<?php echo esc_html($presence_penalty);?>" placeholder="0">
        </div>
        </td></tr><tr class="hideMain3"><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Number between -2.0 and 2.0. Positive values penalize new tokens based on their existing frequency in the text so far, decreasing the model's likelihood to repeat the same line verbatim.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("AI Frequency Penalty:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="-2" step="0.1" max="2" id="frequency_penalty" name="aiomatic_Spinner_Settings[frequency_penalty]" class="cr_450" value="<?php echo esc_html($frequency_penalty);?>" placeholder="0">
        </div>
        </td></tr>
                    <tr><td colspan="2">
                    <h2><?php echo esc_html__("General Editing Settings:", 'aiomatic-automatic-ai-content-writer');?></h2>
                    
                    </td></tr><tr><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Do you want delay automatic editing of the posted article with this amount of seconds from post publish? This will create a single cron job for each post (cron is a requirement for this to function). If you leave this field blank, posts will be automatically spun on post publish.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Delay Article Editing By (Seconds):", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="number" min="0" step="1" id="delay_post" name="aiomatic_Spinner_Settings[delay_post]" class="cr_450" value="<?php echo esc_html($delay_post);?>" placeholder="Delay editing by X seconds">
        </div>
        </td></tr><tr><td>
        <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("This option will allow you to select if you want to run posting in async mode. This means that each time you publish a post, the plugin will try to execute it's task in the background - it will no longer block new post posting, while it finishes it's job.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Use Async Posting Method:", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="checkbox" id="run_background" name="aiomatic_Spinner_Settings[run_background]"<?php
    if ($run_background == 'on')
        echo ' checked ';
?>>
        </div>
        </td></tr><tr><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Do you want to disable automatically editing of WordPress 'posts'?", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Disable Editing of 'Posts':", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="checkbox" id="post_posts" name="aiomatic_Spinner_Settings[post_posts]"<?php
    if ($post_posts == 'on')
        echo ' checked ';
?>>
        </div>
        </td></tr><tr><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Do you want to disable automatically editing of WordPress 'pages'?", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Disable Editing of 'Pages':", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="checkbox" id="post_pages" name="aiomatic_Spinner_Settings[post_pages]"<?php
    if ($post_pages == 'on')
        echo ' checked ';
?>>
        </div>
        </td></tr><tr><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Do you want to disable automatically editing of WordPress 'pages'?", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Disable Editing of 'Custom Post Types':", 'aiomatic-automatic-ai-content-writer');?></b>
                    
                    </td><td>
                    <input type="checkbox" id="post_custom" name="aiomatic_Spinner_Settings[post_custom]"<?php
    if ($post_custom == 'on')
        echo ' checked ';
?>>
        </div>
        </td></tr><tr><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Do you want to disable automatically editing of WordPress categories?", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Disable Editing of Selected Categories:", 'aiomatic-automatic-ai-content-writer');?></b><br/>
                    <a onclick="toggleCats()" class="cr_pointer"><?php echo esc_html__("Show/Hide Categories List", 'aiomatic-automatic-ai-content-writer');?></a>
                    </td><td>
                    <br/>
                    <div id="hideCats" class="hideCats">
<?php
    $cat_args   = array(
        'orderby' => 'name',
        'hide_empty' => 0,
        'order' => 'ASC'
    );
    $categories = get_categories($cat_args);
    foreach ($categories as $category) {
?>
												<div>
													<label>
														<input
<?php
        if (isset($aiomatic_Spinner_Settings['disabled_categories']) && !empty($aiomatic_Spinner_Settings['disabled_categories'])) {
            checked(true, in_array($category->term_id, $aiomatic_Spinner_Settings['disabled_categories']));
        }
?>
 type="checkbox" name="aiomatic_Spinner_Settings[disabled_categories][]" value="<?php
        echo esc_html($category->term_id);
?>" /> 
														<span><?php
        echo esc_html(sanitize_text_field($category->name));
?></span>
													</label>
												</div>
<?php
    }
?>

        </div>
        </div>
        </td></tr><tr><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Input the tags for which you want to disable editing. You can enter more tags, separated by comma. Ex: cars, vehicles, red, luxury. To disable this feature, leave this field blank.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Disable Editing of Selected Tags:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <textarea rows="1" name="aiomatic_Spinner_Settings[disable_tags]" placeholder="Please insert the tags for which you want to disable editing"><?php
    echo esc_textarea($disable_tags);
?></textarea>
        </div>
        </td></tr><tr><td>
                    <div>
        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                        <div class="bws_hidden_help_text cr_min_260px">
<?php
    echo esc_html__("Select if you want to change post status after editing posts.", 'aiomatic-automatic-ai-content-writer');
?>
                        </div>
                    </div>
                    <b><?php echo esc_html__("Change Post Status After Editing:", 'aiomatic-automatic-ai-content-writer');?></b>
                    </div>
                    </td><td>
                    <div>
                    <select id="change_status" name="aiomatic_Spinner_Settings[change_status]" class="cr_width_full">
                    <option value="no" 
<?php
if ($change_status == 'no' || $change_status == '') 
{
    echo " selected";
}
?>
><?php echo esc_html__("No Change", 'aiomatic-automatic-ai-content-writer');?></option>
                    <option value="pending"<?php
if ($change_status == 'pending') 
{
    echo " selected";
}
?>><?php echo esc_html__("Pending", 'aiomatic-automatic-ai-content-writer');?></option>
                    <option value="draft"<?php
if ($change_status == 'draft') 
{
    echo " selected";
}
?>><?php echo esc_html__("Draft", 'aiomatic-automatic-ai-content-writer');?></option>
                    <option value="publish"<?php
if ($change_status == 'publish') 
{
    echo " selected";
}
?>><?php echo esc_html__("Published", 'aiomatic-automatic-ai-content-writer');?></option>
                    <option value="private"<?php
if ($change_status == 'private') 
{
    echo " selected";
}
?>><?php echo esc_html__("Private", 'aiomatic-automatic-ai-content-writer');?></option>
                    <option value="trash"<?php
if ($change_status == 'trash') 
{
    echo " selected";
}
?>><?php echo esc_html__("Trash", 'aiomatic-automatic-ai-content-writer');?></option>
                    </select>
        </div>
        </td></tr>
                    </table>
                    </div>
    <div><p class="submit"><input type="submit" name="btnSubmit" id="btnSubmit" class="button button-primary" onclick="unsaved = false;" value="<?php echo esc_html__("Save Settings", 'aiomatic-automatic-ai-content-writer');?>"/></p></div><div>
<a href="https://www.youtube.com/watch?v=5rbnu_uis7Y" target="_blank"><?php echo esc_html__("Nested Shortcodes also supported!", 'aiomatic-automatic-ai-content-writer');?></a><br/>
</div>
    </div>
    </form>
</div>
</div>
<?php
}
?>
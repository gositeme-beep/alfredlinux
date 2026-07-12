"use strict";
String.prototype.aitrim = function() {
    return this.replace(/^\s+|\s+$/g, "");
};
var input = document.getElementById("aiomatic_image_chat_input");
    input.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) { 
        e.preventDefault(); 
        openaiimagechatfunct();
        return false;
    }
});
function openaiimagechatfunct() {
    jQuery('#aiimagechatsubmitbut').attr('disabled', true);
    var input_textj = jQuery('#aiomatic_image_chat_input');
    var input_text = '';
    input_text = input_textj.val();
    input_textj.val('');
    var user_token_cap_per_day = aiomatic_chat_image_ajax_object.user_token_cap_per_day;
    var user_id = aiomatic_chat_image_ajax_object.user_id;
    var persistent = aiomatic_chat_image_ajax_object.persistent;
    if(input_text == '')
    {
        jQuery('#aiimagechatsubmitbut').attr('disabled', false);
        jQuery('#openai-image-chat-response').html('<div class="text-primary highlight-text-fail" role="status">Please add a text in the input field.</div>');
        console.log('Instruction cannot be empty.');
        return;
    }
    var x_input_text = jQuery('#aiomatic_chat_history').html();
    if(input_text.aitrim() != '')
    {
        jQuery('#aiomatic_chat_history').html(x_input_text + '<div class="ai-bubble ai-mine">' + input_text + '</div>');
    }
    jQuery('#openai-image-chat-response').html('<div><i class="fas fa-spinner fa-spin"></i></div>');
    
    jQuery.ajax({
        type: 'POST',
        url: aiomatic_chat_image_ajax_object.ajax_url,
        data: {
            action: 'aiomatic_image_chat_submit',
            input_text: input_text,
            user_token_cap_per_day: user_token_cap_per_day,
            nonce: aiomatic_chat_image_ajax_object.nonce,
            user_id: user_id
        },
        success: function(response) {
            if(response === 'Failed to generate content, please try again later!' || response === 'Daily token count for your user account was exceeded! Please try again tomorrow.' || response.startsWith('API calls of the plugin are Rate Limited: '))
            {
                jQuery('#openai-image-chat-response').html('<div class="text-primary highlight-text-fail" role="status">' + response + '</div>');
            }
            else if(response.startsWith("You are not allowed to access this form if you are not logged in. Please"))
            {
                jQuery('#openai-image-chat-response').html('<div class="text-primary highlight-text-fail" role="status">' + response + '</div>');
            }
            else
            {
                if(response == '')
                {
                    jQuery('#openai-image-chat-response').html('<div class="text-primary" role="status">No image was generated. Please try using a different text input.</div>');
                }
                else
                {
                    var x_input_text = jQuery('#aiomatic_chat_history').html();
                    if((persistent == 'on' || persistent == '1') && user_id != '0')
                    {
                        jQuery.ajax({
                            type: 'POST',
                            url: aiomatic_chat_ajax_object.ajax_url,
                            data: {
                                action: 'aiomatic_user_meta_save',
                                nonce: aiomatic_chat_ajax_object.persistentnonce,
                                x_input_text: x_input_text + '<div class="ai-bubble ai-other">' + response + '</div>',
                                user_id: user_id
                            },
                            success: function() {
                            },
                            error: function(error) {
                                console.log('Error while saving persistent user log: ' + error.responseText);
                            },
                        });
                    }
                    jQuery('#aiomatic_chat_history').html(x_input_text + '<div class="ai-bubble ai-other">' + response + '</div>');
                    // Clear the response container
                    jQuery('#openai-image-chat-response').html('');
                    // Enable the submit button
                    jQuery('#aiimagechatsubmitbut').attr('disabled', false);
                }
            }
            jQuery('#aiimagechatsubmitbut').attr('disabled', false);
        },
        error: function(error) {
            console.log('Error: ' + error.responseText);
            // Clear the response container
            jQuery('#openai-image-chat-response').html('<div class="text-primary highlight-text-fail" role="status">Failed to generate content, try again later.</div>');
            // Enable the submit button
            jQuery('#aiimagechatsubmitbut').attr('disabled', false);
        },
    });
}
jQuery(document).ready(function() {
    if(jQuery('#aiomatic_image_chat_templates').length)
    {
        jQuery('#aiomatic_image_chat_templates').change(function()
        {
            jQuery('#aiomatic_image_chat_input').val(jQuery( "#aiomatic_image_chat_templates" ).val());
        });
    }
});
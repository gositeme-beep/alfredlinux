"use strict";
String.prototype.aitrim = function() {
    return this.replace(/^\s+|\s+$/g, "");
};
function aiisAlphaNumeric(str) {
    var code, i, len;
    for (i = 0, len = str.length; i < len; i++) {
      code = str.charCodeAt(i);
      if (!(code > 47 && code < 58) && // numeric (0-9)
          !(code > 64 && code < 91) && // upper alpha (A-Z)
          !(code > 96 && code < 123)) { // lower alpha (a-z)
        return false;
      }
    }
    return true;
}
var input = document.getElementById("aiomatic_chat_input");
    input.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) { 
        e.preventDefault(); 
        openaichatfunct();
        return false;
    }
});
function airemovePrefix(mainString, substring) 
{
    if (mainString.startsWith(substring)) 
    {
      return mainString.slice(substring.length);
    } else 
    {
      return mainString;
    }
}
function airemoveAfter(mainString, substring) {
    var index = mainString.indexOf(substring);
    if (index !== -1) {
      return mainString.slice(0, index);
    } else {
      return mainString;
    }
  }
function openaichatfunct() {
    jQuery('#aichatsubmitbut').attr('disabled', true);
    var input_textj = jQuery('#aiomatic_chat_input');
    var input_text = '';
    /*
    if(input_text === '')
    {
        jQuery('#aichatsubmitbut').attr('disabled', false);
        return;
    }
    */
    input_text = input_textj.val();
    input_textj.val('');
    var user_message_preppend = aiomatic_chat_ajax_object.user_message_preppend;
    var ai_message_preppend = aiomatic_chat_ajax_object.ai_message_preppend;
    var x_input_text = jQuery('#aiomatic_chat_history').html();
    var remember_string = x_input_text.replace(/<div class="ai-bubble ai-other">([\s\S]*?)<\/div>/g, ai_message_preppend + "$1\n");
    remember_string = remember_string.replace(/<div class="ai-bubble ai-mine">([\s\S]*?)<\/div>/g, user_message_preppend + "$1\n");
    remember_string = remember_string.aitrim();
    remember_string = remember_string.slice(-12000);
    if(input_text.aitrim() != '')
    {
        input_text = input_text.replace(/(?:\r\n|\r|\n)/g, '<br>');
        jQuery('#aiomatic_chat_history').html(x_input_text + '<div class="ai-bubble ai-mine">' + input_text + '</div>');
    }
    jQuery('#openai-chat-response').html('<div><i class="fas fa-spinner fa-spin"></i></div>');
    var model = aiomatic_chat_ajax_object.model;
    var temp = aiomatic_chat_ajax_object.temp;
    var top_p = aiomatic_chat_ajax_object.top_p;
    var presence = aiomatic_chat_ajax_object.presence;
    var frequency = aiomatic_chat_ajax_object.frequency;
    var instant_response = aiomatic_chat_ajax_object.instant_response;
    var chat_preppend_text = aiomatic_chat_ajax_object.chat_preppend_text;
    var user_token_cap_per_day = aiomatic_chat_ajax_object.user_token_cap_per_day;
    var user_id = aiomatic_chat_ajax_object.user_id;
    var persistent = aiomatic_chat_ajax_object.persistent;
    if(model == 'default' || model == '')
    {
        model = jQuery( "#model-chat-selector option:selected" ).text();
    }
    if(temp == 'default' || temp == '')
    {
        temp = jQuery('#temperature-chat-input').val();
    }
    if(top_p == 'default' || top_p == '')
    {
        top_p = jQuery('#top_p-chat-input').val();
    }
    if(presence == 'default' || presence == '')
    {
        presence = jQuery('#presence-chat-input').val();
    }
    if(frequency == 'default' || frequency == '')
    {
        frequency = jQuery('#frequency-chat-input').val();
    }
    var lastch = input_text.charAt(input_text.length - 1);
    if(aiisAlphaNumeric(lastch))
    {
        input_text += '.';
    }
    if(user_message_preppend != '')
    {
        input_text = user_message_preppend + ' ' + input_text;
    }
    if(ai_message_preppend != '')
    {
        input_text = input_text + ' ' + ai_message_preppend;
    }
    if(chat_preppend_text != '')
    {
        remember_string = chat_preppend_text + '\n' + remember_string;
    }
    jQuery.ajax({
        type: 'POST',
        url: aiomatic_chat_ajax_object.ajax_url,
        data: {
            action: 'aiomatic_chat_submit',
            input_text: input_text,
            nonce: aiomatic_chat_ajax_object.nonce,
            model: model,
            temp: temp,
            top_p: top_p,
            presence: presence,
            frequency: frequency,
            user_token_cap_per_day: user_token_cap_per_day,
            remember_string: remember_string,
            user_id: user_id
        },
        success: function(response) {
            if(response === 'Failed to generate content, please try again later!' || response === 'Daily token count for your user account was exceeded! Please try again tomorrow.' || response.startsWith('API calls of the plugin are Rate Limited: '))
            {
                jQuery('#openai-chat-response').html('<div class="text-primary highlight-text-fail" role="status">' + response + '</div>');
            }
            else if(response.startsWith("You are not allowed to access this form if you are not logged in. Please"))
            {
                jQuery('#openai-chat-response').html('<div class="text-primary highlight-text-fail" role="status">' + response + '</div>');
            }
            else
            {
                if(response == '')
                {
                    jQuery('#openai-chat-response').html('<div class="text-primary" role="status">AI considers this as the end of the text. Please try using a different text input.</div>');
                }
                else
                {
                    if(ai_message_preppend != '')
                    {
                        response = airemovePrefix(response.aitrim(), ai_message_preppend);
                        response = response.aitrim();
                    }
                    if(user_message_preppend != '')
                    {
                        response = airemoveAfter(response.aitrim(), user_message_preppend);
                        response = response.aitrim();
                    }
                    response = response.replace(/\n/g, '<br>');
                    var x_input_text = jQuery('#aiomatic_chat_history').html();
                    if((persistent != 'off' && persistent != '0' && persistent != '') && user_id != '0')
                    {
                        jQuery.ajax({
                            type: 'POST',
                            url: aiomatic_chat_ajax_object.ajax_url,
                            data: {
                                action: 'aiomatic_user_meta_save',
                                nonce: aiomatic_chat_ajax_object.persistentnonce,
                                persistent: persistent,
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
                    if(instant_response == 'true')
                    {
                        jQuery('#aiomatic_chat_history').html(x_input_text + '<div class="ai-bubble ai-other">' + response + '</div>');
                        // Clear the response container
                        jQuery('#openai-chat-response').html('');
                        // Enable the submit button
                        jQuery('#aichatsubmitbut').attr('disabled', false);
                    }
                    else
                    {
                        jQuery('#openai-chat-response').html('<i class="fas fa-pen ai-writing-icon"></i>');
                        var i = 0;
                        function typeWriter() {
                            if (i < response.length) {
                                // Append the response to the input field
                                jQuery('#aiomatic_chat_history').html(x_input_text + '<div class="ai-bubble ai-other">' + response.substring(0, i + 1) + '</div>');
                                i++;
                                setTimeout(typeWriter, 50);
                            } else {
                                // Clear the response container
                                jQuery('#openai-chat-response').html('');
                                // Enable the submit button
                                jQuery('#aichatsubmitbut').attr('disabled', false);
                            }
                        }
                        typeWriter();
                    }
                }
            }
            jQuery('#aichatsubmitbut').attr('disabled', false);
        },
        error: function(error) {
            console.log('Error: ' + error.responseText);
            // Clear the response container
            jQuery('#openai-chat-response').html('<div class="text-primary highlight-text-fail" role="status">Failed to generate content, try again later.</div>');
            // Enable the submit button
            jQuery('#aichatsubmitbut').attr('disabled', false);
        },
    });
}
jQuery(document).ready(function() {
    if(jQuery('#aiomatic_chat_templates').length)
    {
        jQuery('#aiomatic_chat_templates').change(function()
        {
            jQuery('#aiomatic_chat_input').val(jQuery( "#aiomatic_chat_templates" ).val());
        });
    }
});
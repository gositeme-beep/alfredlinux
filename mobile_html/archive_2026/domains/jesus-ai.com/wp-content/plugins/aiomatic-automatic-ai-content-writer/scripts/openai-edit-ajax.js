"use strict";
function openaieditfunct() {
    jQuery('#aieditsubmitbut').attr('disabled', true);
    var input_text = jQuery('#aiomatic_edit_input').val();
    var instruction = jQuery('#aiomatic_edit_instruction').val();
    var model = aiomatic_edit_ajax_object.model;
    var user_token_cap_per_day = aiomatic_edit_ajax_object.user_token_cap_per_day;
    var user_id = aiomatic_edit_ajax_object.user_id;
    // Show the loading animation
    jQuery('#openai-edit-response').html('<div><i class="fas fa-spinner fa-spin"></i></div>');
    var temp = aiomatic_edit_ajax_object.temp;
    var top_p = aiomatic_edit_ajax_object.top_p;
    if(temp == 'default' || temp == '')
    {
        temp = jQuery('#temperature-edit-input').val();
    }
    if(top_p == 'default' || top_p == '')
    {
        top_p = jQuery('#top_p-edit-input').val();
    }
    if(model == 'default' || model == '')
    {
        model = jQuery( "#model-edit-selector option:selected" ).text();
    }
    if(instruction === "")
    {
        console.log('Instruction cannot be empty.');
        jQuery('#openai-edit-response').html('<div class="text-primary highlight-text-fail" role="status">Please add a command in the instruction field.</div>');
        jQuery('#aieditsubmitbut').attr('disabled', false);
    }
    else
    {
        jQuery.ajax({
            type: 'POST',
            url: aiomatic_edit_ajax_object.ajax_url,
            data: {
                action: 'aiomatic_edit_submit',
                input_text: input_text,
                instruction: instruction,
                nonce: aiomatic_edit_ajax_object.nonce,
                temp: temp,
                top_p: top_p,
                model: model,
                user_token_cap_per_day: user_token_cap_per_day,
                user_id: user_id
            },
            success: function(response) {
                if(response === 'Failed to edit content, please try again later!' || response === 'Daily token count for your user account was exceeded! Please try again tomorrow.' || response.startsWith('API calls of the plugin are Rate Limited: '))
                {
                    jQuery('#openai-edit-response').html('<div class="text-primary highlight-text-fail" role="status">' + response + '</div>');
                }
                else if(response.startsWith("You are not allowed to access this form if you are not logged in. Please"))
                {
                    jQuery('#openai-edit-response').html('<div class="text-primary highlight-text-fail" role="status">' + response + '</div>');
                }
                else
                {
                    if(response == '')
                    {
                        jQuery('#openai-edit-response').html('<div class="text-primary" role="status">No edit was returned. Please try using a different text input.</div>');
                    }
                    else
                    {
                        jQuery('#aiomatic_edit_response').val(response);
                        jQuery('#openai-edit-response').html('');
                    }
                }
                jQuery('#aieditsubmitbut').attr('disabled', false);
            },
            error: function(error) {
                console.log('Error: ' + error.responseText);
                jQuery('#openai-edit-response').html('<div class="text-primary highlight-text-fail" role="status">Failed to edit content, try again later.</div>');
                // Enable the submit button
                jQuery('#aieditsubmitbut').attr('disabled', false);
            },
        });
    }
}

var recognition;
var recognizing = false;
jQuery(document).ready(function() {
    jQuery('#copy-edit-button').click(function() {
        // Select the text in the input field
        var jsf = jQuery("#aiomatic_edit_response").val();
        navigator.clipboard.writeText(jsf);
    });
    if(!jQuery('#aiomatic_edit_templates').length)
    {
        // Check if the browser supports the Web Speech API
        if ('webkitSpeechRecognition' in window) {
            recognition = new webkitSpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;

            // Start the speech recognition when the button is clicked
            jQuery('#openai-edit-speech-button').click(function() {
                if (recognizing) {
                    recognition.stop();
                    recognizing = false;
                } else {
                    recognition.start();
                    recognizing = true;
                }
            });

            // Handle the speech recognition results
            recognition.onresult = function(event) {
                for (var i = event.resultIndex; i < event.results.length; ++i) {
                    if (event.results[i].isFinal) {
                        jQuery('#aiomatic_edit_input').val(jQuery('#aiomatic_edit_input').val() + event.results[i][0].transcript);
                    }
                }
            };
        }
    }
    else
    {
        jQuery('#aiomatic_edit_templates').change(function()
        {
            jQuery('#aiomatic_edit_instruction').val(jQuery( "#aiomatic_edit_templates" ).val());
        });
    }
});
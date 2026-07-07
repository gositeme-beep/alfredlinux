"use strict";
function openaiimagefunct() {
    jQuery('#aiimagesubmitbut').attr('disabled', true);
    var instructionx = jQuery('#aiomatic_image_instruction');
    var instruction = instructionx.val();
    if(instruction == '')
    {
        jQuery('#aiimagesubmitbut').attr('disabled', false);
        jQuery('#openai-image-response').html('<div class="text-primary highlight-text-fail" role="status">Please add a prompt in the input field.</div>');
        console.log('Instruction cannot be empty.');
        return;
    }
    var image_placeholder = aiomatic_image_ajax_object.image_placeholder;
    jQuery("#aiomatic_image_response").attr("src", image_placeholder).fadeIn();
    var image_size = aiomatic_image_ajax_object.image_size;
    var user_token_cap_per_day = aiomatic_image_ajax_object.user_token_cap_per_day;
    var user_id = aiomatic_image_ajax_object.user_id;
    // Show the loading animation
    jQuery('#openai-image-response').html('<div><i class="fas fa-spinner fa-spin"></i></div>');
    if(image_size == 'default' || image_size == '')
    {
        image_size = jQuery( "#ai-image-size-selector option:selected" ).text();
    }
    jQuery.ajax({
        type: 'POST',
        url: aiomatic_image_ajax_object.ajax_url,
        data: {
            action: 'aiomatic_image_ajax_submit',
            instruction: instruction,
            image_size: image_size,
            user_token_cap_per_day: user_token_cap_per_day,
            nonce: aiomatic_image_ajax_object.nonce,
            user_id: user_id
        },
        success: function(response) {
            if(response === 'Failed to generate image, please try again later!' || response === 'Daily token count for your user account was exceeded! Please try again tomorrow.' || response.startsWith('API calls of the plugin are Rate Limited: '))
            {
                jQuery('#openai-image-response').html('<div class="text-primary highlight-text-fail" role="status">' + response + '</div>');
                jQuery("#aiomatic_image_response").attr("src", '').fadeIn();
            }
            else if(response.startsWith("You are not allowed to access this form if you are not logged in. Please"))
            {
                jQuery('#openai-image-response').html('<div class="text-primary highlight-text-fail" role="status">' + response + '</div>');
            }
            else
            {
                if(response == '')
                {
                    jQuery('#openai-image-response').html('<div class="text-primary" role="status">No image was returned. Please try using a different text input.</div>');
                    jQuery("#aiomatic_image_response").attr("src", '').fadeIn();
                }
                else
                {
                    jQuery("#aiomatic_image_response").attr("src", response).fadeIn();
                    jQuery('#openai-image-response').html('');
                }
            }
            jQuery('#aiimagesubmitbut').attr('disabled', false);
        },
        error: function(error) {
            console.log('Error: ' + error.responseText);
            jQuery("#aiomatic_image_response").attr("src", '').fadeIn();
            // Clear the response container
            jQuery('#openai-image-response').html('<div class="text-primary highlight-text-fail" role="status">Failed to image content, try again later.</div>');
            // Enable the submit button
            jQuery('#aiimagesubmitbut').attr('disabled', false);
        },
    });
}

var recognition;
var recognizing = false;
jQuery(document).ready(function() {
    if(!jQuery('#aiomatic_image_templates').length)
    {
        // Check if the browser supports the Web Speech API
        if ('webkitSpeechRecognition' in window) {
            recognition = new webkitSpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;

            // Start the speech recognition when the button is clicked
            jQuery('#openai-image-speech-button').click(function() {
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
                        jQuery('#aiomatic_image_instruction').val(jQuery('#aiomatic_image_instruction').val() + event.results[i][0].transcript);
                    }
                }
                
            };
        }
    }
    else
    {
        jQuery('#aiomatic_image_templates').change(function()
        {
            jQuery('#aiomatic_image_instruction').val(jQuery( "#aiomatic_image_templates" ).val());
        });
    }
});
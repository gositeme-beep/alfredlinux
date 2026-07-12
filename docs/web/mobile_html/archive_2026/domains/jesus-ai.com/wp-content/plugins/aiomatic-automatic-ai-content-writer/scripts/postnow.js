"use strict";
function aiomatic_post_now(postId)
{
    if (confirm("Are you sure you want to submit this post now?") == true) {
        document.getElementById('aiomatic_submit_post').setAttribute('disabled','disabled');
        document.getElementById("aiomatic_span").innerHTML = 'Submitting... (please do not close or refresh this page) ';
        var data = {
             action: 'aiomatic_post_now',
             id: postId
        };
        jQuery.post(ajaxurl, data, function(response) {
            document.getElementById('aiomatic_submit_post').removeAttribute('disabled');
            document.getElementById("aiomatic_span").innerHTML = 'Done! ';
            location.reload();
        }).fail( function(xhr) 
        {
            document.getElementById("aiomatic_span").innerHTML = 'Error, please check the plugin\'s \'Activity and Logging\' menu for details!';
            console.log('Error occured in processing: ' + xhr.statusText + ' - please check plugin\'s \'Activity and Logging\' menu for details.');
        });
    } else {
        return;
    }
}
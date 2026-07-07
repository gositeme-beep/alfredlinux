"use strict"; 
( function( wp ) {
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginSidebar = wp.editPost.PluginSidebar;
	var el = wp.element.createElement;
    
	registerPlugin( 'aiomatic-sidebar', {
		render: function() {
            function updateMessage( ) {
                var postId = wp.data.select("core/editor").getCurrentPostId();
                if (confirm("Are you sure you want to submit this post now?") == true) {
                    document.getElementById('aiomatic_submit_post').setAttribute('disabled','disabled');
                    document.getElementById("aiomatic_span").innerHTML = 'Posting status: Submitting... (please do not close or refresh this page) ';
                    var data = {
                         action: 'aiomatic_post_now',
                         id: postId
                    };
                    jQuery.post(ajaxurl, data, function(response) {
                        document.getElementById('aiomatic_submit_post').removeAttribute('disabled');
                        document.getElementById("aiomatic_span").innerHTML = 'Posting status: Done! ';
                    }).fail( function(xhr) 
                    {
                        document.getElementById("aiomatic_span").innerHTML = 'Error, please check the plugin\'s \'Activity and Logging\' menu for details!';
                        console.log('Error occured in processing: ' + xhr.statusText + ' - please check plugin\'s \'Activity and Logging\' menu for details.');
                    });
                } else {
                    return;
                }
            }
			return el( PluginSidebar,
				{
					name: 'aiomatic-sidebar',
					icon: 'text',
					title: 'AIomatic AI Content Writer',
				},
				el(
                    'div', 
                    { className: 'coderevolution_gutenberg_div' },
                    el(
                        'h4',
                        { className: 'coderevolution_gutenberg_title' },
                        'Add AI Content To Post '
                    ),
                    el(
                        'input',
                        { type:'button', id:'aiomatic_submit_post', value:'Edit Post Now!', onClick: updateMessage, className: 'coderevolution_gutenberg_button button button-primary' }
                    ),
                    el(
                    'br'
                    ),
                    el(
                    'br'
                    ),
                    el(
                        'div', 
                        {id:'aiomatic_span'},
                        'Posting status: idle'
                    )
				)
			);
		},
	} );
} )( window.wp );
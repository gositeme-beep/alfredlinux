"use strict"; 
var { registerBlockType } = wp.blocks;
var gcel = wp.element.createElement;
var editing_options = '';
aiomatic_object.models.forEach(element => editing_options += '<option value="' + element + '">' + element + '</option>');
registerBlockType( 'aiomatic-automatic-ai-content-writer/aiomatic-chat', {
    title: 'AIomatic Chat Form',
    icon: 'text',
    category: 'embed',
    attributes: {
        temperature : {
            default: 'default',
            type:   'string',
        },
        top_p : {
            default: 'default',
            type:   'string',
        },
        presence_penalty : {
            default: 'default',
            type:   'string',
        },
        frequency_penalty : {
            default: 'default',
            type:   'string',
        },
        model : {
            default: 'default',
            type:   'string',
        },
        instant_response : {
            default: 'false',
            type:   'string',
        },
        chat_preppend_text : {
            default: '',
            type:   'string',
        },
        user_message_preppend : {
            default: '',
            type:   'string',
        },
        ai_message_preppend : {
            default: '',
            type:   'string',
        },
        ai_first_message : {
            default: '',
            type:   'string',
        },
        chat_mode : {
            default: '',
            type:   'string',
        },
        user_token_cap_per_day : {
            default: '',
            type:   'string',
        },
        persistent : {
            default: '',
            type:   'string',
        },
        prompt_templates : {
            default: '',
            type:   'string',
        },
        prompt_editable : {
            default: '',
            type:   'string',
        }
    },
    keywords: ['list', 'posts', 'aiomatic'],
    edit: (function( props ) {
        var temperature = props.attributes.temperature;
        var top_p = props.attributes.top_p;
        var presence_penalty = props.attributes.presence_penalty;
        var frequency_penalty = props.attributes.frequency_penalty;
        var model = props.attributes.model;
        var instant_response = props.attributes.instant_response;
        var chat_preppend_text = props.attributes.chat_preppend_text;
        var user_message_preppend = props.attributes.user_message_preppend;
        var ai_message_preppend = props.attributes.ai_message_preppend;
        var ai_first_message = props.attributes.ai_first_message;
        var chat_mode = props.attributes.chat_mode;
        var user_token_cap_per_day = props.attributes.user_token_cap_per_day;
        var persistent = props.attributes.persistent;
        var prompt_templates = props.attributes.prompt_templates;
        var prompt_editable = props.attributes.prompt_editable;
        function updateMessage( event ) {
            props.setAttributes( { temperature: event.target.value} );
		}
        function updateMessage3( event ) {
            props.setAttributes( { top_p: event.target.value} );
		}
        function updateMessage4( event ) {
            props.setAttributes( { presence_penalty: event.target.value} );
		}
        function updateMessage5( event ) {
            props.setAttributes( { frequency_penalty: event.target.value} );
		}
        function updateMessage6( event ) {
            props.setAttributes( { model: event.target.value} );
		}
        function updateMessage7( event ) {
            props.setAttributes( { instant_response: event.target.value} );
		}
        function updateMessage8( event ) {
            props.setAttributes( { chat_preppend_text: event.target.value} );
		}
        function updateMessage9( event ) {
            props.setAttributes( { user_message_preppend: event.target.value} );
		}
        function updateMessage10( event ) {
            props.setAttributes( { ai_message_preppend: event.target.value} );
		}
        function updateMessage11( event ) {
            props.setAttributes( { ai_first_message: event.target.value} );
		}
        function updateMessage12( event ) {
            props.setAttributes( { chat_mode: event.target.value} );
		}
        function updateMessage13( event ) {
            props.setAttributes( { user_token_cap_per_day: event.target.value} );
		}
        function updateMessage14( event ) {
            props.setAttributes( { persistent: event.target.value} );
		}
        function updateMessage15( event ) {
            props.setAttributes( { prompt_templates: event.target.value} );
		}
        function updateMessage16( event ) {
            props.setAttributes( { prompt_editable: event.target.value} );
		}
		return gcel(
			'div', 
			{ className: 'coderevolution_gutenberg_div' },
            gcel(
				'h4',
				{ className: 'coderevolution_gutenberg_title' },
                'AIomatic Chat Form ',
                gcel(
                    'div', 
                    {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                    ,
                    gcel(
                        'div', 
                        {className:'bws_hidden_help_text'},
                        'This block is used to generate an AI chat.'
                    )
                )
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'AI Temperature: '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'What sampling temperature to use. Higher values means the model will take more risks. Try 0.9 for more creative applications, and 0 (argmax sampling) for ones with a well-defined answer. We generally recommend altering this or top_p but not both.'
                )
            ),
			gcel(
				'input',
				{ type:'number',min:0,step:0.1,placeholder:'AI Temperature', value: temperature, onChange: updateMessage, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'AI Top_p: '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'An alternative to sampling with temperature, called nucleus sampling, where the model considers the results of the tokens with top_p probability mass. So 0.1 means only the tokens comprising the top 10% probability mass are considered. We generally recommend altering this or temperature but not both.'
                )
            ),
			gcel(
				'input',
				{ type:'number',min:0,max:1,step:0.1,placeholder:'AI Top_p', value: top_p, onChange: updateMessage3, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'AI Presence Penalty: '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Number between -2.0 and 2.0. Positive values penalize new tokens based on whether they appear in the text so far, increasing the model\'s likelihood to talk about new topics.'
                )
            ),
			gcel(
				'input',
				{ type:'number',min:-2,max:2,step:0.1,placeholder:'AI Presence Penalty', value: presence_penalty, onChange: updateMessage4, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'AI Frequency Penalty: '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Number between -2.0 and 2.0. Positive values penalize new tokens based on their existing frequency in the text so far, decreasing the model\'s likelihood to repeat the same line verbatim.'
                )
            ),
			gcel(
				'input',
				{ type:'number',min:-2,max:2,step:0.1,placeholder:'AI Frequency Penalty', value: frequency_penalty, onChange: updateMessage5, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'Model: '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Select the AI model you want to use to generate the content.'
                )
            ),
            gcel(
				'select',
				{ value: model, onChange: updateMessage6, className: 'coderevolution_gutenberg_select', dangerouslySetInnerHTML: {
                    __html: editing_options
                } }
            ),
            gcel(
                'br'
            ),
            gcel(
                'label',
                { className: 'coderevolution_gutenberg_label' },
                'Instant Chat Response: '
            ),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Select the chat should have an instant response.'
                )
            ),
            gcel(
                'select',
                { value: instant_response, onChange: updateMessage7, className: 'coderevolution_gutenberg_select' }, 
                gcel(
                    'option',
                    { value: 'false'},
                    'false'
                ), 
                gcel(
                    'option',
                    { value: 'true'},
                    'true'
                )
            ),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'Preppend Chat With Text (Not Shown To Users): '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Preppend the AI chat with this text, it will not be displayed to users. Using this settings field, you can teach the AI some info about your company, your requirements, give the AI some initial conditions and instructions. You can also use shortcodes in this field. List of supported shortcodes: %%post_title%%, %%post_content%%, %%post_content_plain_text%%, %%post_excerpt%%, %%post_cats%%, %%post_tags%%, %%featured_image%%, %%smart_hashtags%%, %%blog_title%%, %%author_name%%, %%post_link%%, %%random_sentence%%, %%random_sentence2%%. You can also use custom fields (post meta) that it\'s assigned to posts using custom shortcodes in this format: %%!custom_field_slug!%%. Example: if you wish to add data that is imported from the custom field post_data, you should use this shortcode: %%!post_data!%%. The length of this command should not be greater than the max token count set in the settings for the seed command - Update: nested shortcodes also supported (shortcodes generated by rules from other plugins). Example of prompt to pretain the AI --- Article: "%%post_content%%" \n\n Discussion: \n\n'
                )
            ),
			gcel(
				'input',
				{ type:'text',placeholder:'Preppend chat with this string (will not be shown to users)', value: chat_preppend_text, onChange: updateMessage8, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'Preppend Each User Message With Text (Not Shown To Users): '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Preppend each of the user messages with this text, it will not be displayed to users. Using this settings field, you can set a name to users in the conversation, like "Customer: ".'
                )
            ),
			gcel(
				'input',
				{ type:'text',placeholder:'Preppend user message with this string (will not be shown to users)', value: user_message_preppend, onChange: updateMessage9, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'Preppend Each AI Message With Text (Not Shown To Users): '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Preppend each of the AI messages with this text, it will not be displayed to users. Using this settings field, you can set a name to the AI in the conversation, like "Support Assistant: ".'
                )
            ),
			gcel(
				'input',
				{ type:'text',placeholder:'Preppend AI message with this string (will not be shown to users)', value: ai_message_preppend, onChange: updateMessage10, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'Set The First Message Of The AI ChatBot:'
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Sets The First Message Of The AI Chat Bot. This is displayed to users.'
                )
            ),
			gcel(
				'input',
				{ type:'text',placeholder:'First message of the chatbot', value: ai_first_message, onChange: updateMessage11, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
                'br'
            ),
            gcel(
                'label',
                { className: 'coderevolution_gutenberg_label' },
                'Chat Mode: '
            ),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Select the mode of the chat (images or text).'
                )
            ),
            gcel(
                'select',
                { value: chat_mode, onChange: updateMessage12, className: 'coderevolution_gutenberg_select' }, 
                gcel(
                    'option',
                    { value: 'text'},
                    'text'
                ), 
                gcel(
                    'option',
                    { value: 'images'},
                    'images'
                )
            ),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'Daily Token Count for Logged In Users: '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Set the daily token count for logged in users. Users who are not logged in will not be allowed to submit the form. To disable this feature, leave this field blank.'
                )
            ),
			gcel(
				'input',
				{ type:'number',min:0,placeholder:'Daily token count for users', value: user_token_cap_per_day, onChange: updateMessage13, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'Enable Persistent Chat: '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Select a unique ID for persistent conversations of users. You can create multiple persistent conversations using different IDs, added to different shortcodes.'
                )
            ),
			gcel(
				'input',
				{ type:'text',placeholder:'Select the persistent conversation ID, which will be saved for each user', value: persistent, onChange: updateMessage14, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'Prompt Templates (Semicolon Separated): '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Add a semicolon (;) separated list of prompt templates from which the users will be able to select and submit one.'
                )
            ),
			gcel(
				'input',
				{ type:'text',placeholder:'Template1;Template2;Template3', value: prompt_templates, onChange: updateMessage15, className: 'coderevolution_gutenberg_input' }
			),
            gcel(
				'br'
			),
            gcel(
				'label',
				{ className: 'coderevolution_gutenberg_label' },
                'Prompt Editable: '
			),
            gcel(
                'div', 
                {className:'bws_help_box bws_help_box_right dashicons dashicons-editor-help'}
                ,
                gcel(
                    'div', 
                    {className:'bws_hidden_help_text'},
                    'Select wheather the prompt will be editable by users. This is useful when combined with prompt templates from above, when you don\'t want the users to edit the entered template.'
                )
            ),
            gcel(
				'select',
				{ value: prompt_editable, onChange: updateMessage16, className: 'coderevolution_gutenberg_select' },
                gcel(
                    'option',
                    { value: 'yes'},
                    'yes'
                ), 
                gcel(
                    'option',
                    { value: 'no'},
                    'no'
                )
            ),
		);
    }),
    save: (function( props ) {
       return null;
    }),
} );
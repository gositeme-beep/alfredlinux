<?php
   function aiomatic_admin_settings()
   {
       $all_models = aiomatic_get_all_models();
       $language_names = array(
           esc_html__("Disabled", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Afrikaans (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Albanian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Arabic (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Amharic (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Armenian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Belarusian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Bulgarian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Catalan (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Chinese Simplified (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Croatian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Czech (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Danish (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Dutch (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("English (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Estonian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Filipino (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Finnish (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("French (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Galician (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("German (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Greek (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Hebrew (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Hindi (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Hungarian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Icelandic (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Indonesian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Irish (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Italian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Japanese (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Korean (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Latvian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Lithuanian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Norwegian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Macedonian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Malay (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Maltese (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Persian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Polish (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Portuguese (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Romanian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Russian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Serbian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Slovak (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Slovenian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Spanish (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Swahili (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Swedish (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Thai (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Turkish (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Ukrainian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Vietnamese (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Welsh (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Yiddish (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Tamil (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Azerbaijani (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Kannada (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Basque (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Bengali (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Latin (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Chinese Traditional (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Esperanto (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Georgian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Telugu (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Gujarati (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Haitian Creole (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Urdu (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Burmese (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Bosnian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Cebuano (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Chichewa (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Corsican (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Frisian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Scottish Gaelic (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Hausa (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Hawaian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Hmong (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Igbo (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Javanese (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Kazakh (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Khmer (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Kurdish (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Kyrgyz (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Lao (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Luxembourgish (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Malagasy (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Malayalam (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Maori (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Marathi (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Mongolian (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Nepali (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Pashto (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Punjabi (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Samoan (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Sesotho (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Shona (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Sindhi (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Sinhala (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Somali (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Sundanese (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Swahili (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Tajik (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Uzbek (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Xhosa (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Yoruba (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Zulu (Google Translate)", 'aiomatic-automatic-ai-content-writer'),

           esc_html__("Assammese (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Aymara (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Bambara (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Bhojpuri (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Dhivehi (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Dogri (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Ewe (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Guarani (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Ilocano (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Kinyarwanda (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Konkani (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Krio (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Kurdish - Sorani (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Lingala (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Luganda (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Maithili (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Meiteilon (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Mizo (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Odia (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Oromo (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Quechua (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Sanskrit (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Sepedi (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Tatar (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Tigrinya (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Tsonga (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Turkmen (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Twi (Google Translate)", 'aiomatic-automatic-ai-content-writer'),
           esc_html__("Uyghur (Google Translate)", 'aiomatic-automatic-ai-content-writer')
       );
       $language_codes = array(
           "disabled",
           "af",
           "sq",
           "ar",
           "am",
           "hy",
           "be",
           "bg",
           "ca",
           "zh-CN",
           "hr",
           "cs",
           "da",
           "nl",
           "en",
           "et",
           "tl",
           "fi",
           "fr",
           "gl",
           "de",
           "el",
           "iw",
           "hi",
           "hu",
           "is",
           "id",
           "ga",
           "it",
           "ja",
           "ko",
           "lv",
           "lt",
           "no",
           "mk",
           "ms",
           "mt",
           "fa",
           "pl",
           "pt",
           "ro",
           "ru",
           "sr",
           "sk",
           "sl",
           "es",
           "sw",
           "sv",   
           "th",
           "tr",
           "uk",
           "vi",
           "cy",
           "yi",
           "ta",
           "az",
           "kn",
           "eu",
           "bn",
           "la",
           "zh-TW",
           "eo",
           "ka",
           "te",
           "gu",
           "ht",
           "ur",
           "my",
           "bs",
           "ceb",
           "ny",
           "co",
           "fy",
           "gd",
           "ha",
           "haw",
           "hmn",
           "ig",
           "jw",
           "kk",
           "km",
           "ku",
           "ky",
           "lo",
           "lb",
           "mg",
           "ml",
           "mi",
           "mr",
           "mn",
           "ne",
           "ps",
           "pa",
           "sm",
           "st",
           "sn",
           "sd",
           "si",
           "so",
           "su",
           "sw",
           "tg",
           "uz",
           "xh",
           "yo",
           "zu",

           "as",
           "ay",
           "bm",
           "bho",
           "dv",
           "doi",
           "ee",
           "gn",
           "ilo",
           "rw",
           "gom",
           "kri",
           "ckb",
           "ln",
           "lg",
           "mai",
           "mni-Mtei",
           "lus",
           "or",
           "om",
           "qu",
           "sa",
           "nso",
           "tt",
           "ti",
           "ts",
           "tk",
           "ak",
           "ug"
       );
   ?>
<div class="wp-header-end"></div>
<div class="wrap gs_popuptype_holder seo_pops">
<h1><?php echo esc_html__("Main Settings", 'aiomatic-automatic-ai-content-writer');?></h1>
</div>
<div class="wrap">
        <nav class="nav-tab-wrapper">
            <a href="#tab-1" class="nav-tab nav-tab-active"><?php echo esc_html__("Plugin Activation", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-2" class="nav-tab"><?php echo esc_html__("API Keys", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-3" class="nav-tab"><?php echo esc_html__("Stability.AI API", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-4" class="nav-tab"><?php echo esc_html__("AI Images", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-8" class="nav-tab"><?php echo esc_html__("Royalty Free Images", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-5" class="nav-tab"><?php echo esc_html__("Statistics", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-6" class="nav-tab"><?php echo esc_html__("Fine-Tuning", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-13" class="nav-tab"><?php echo esc_html__("Embeddings", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-12" class="nav-tab"><?php echo esc_html__("Spin & Translate", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-7" class="nav-tab"><?php echo esc_html__("General Settings", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-9" class="nav-tab"><?php echo esc_html__("Random Sentence Generator", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-10" class="nav-tab"><?php echo esc_html__("Custom HTML", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-11" class="nav-tab"><?php echo esc_html__("Keyword Replacer", 'aiomatic-automatic-ai-content-writer');?></a>
        </nav>
      <form id="myForm" method="post" action="<?php if(is_multisite() && is_network_admin()){echo '../options.php';}else{echo 'options.php';}?>">
         <div class="cr_autocomplete">
            <input type="password" id="PreventChromeAutocomplete" 
               name="PreventChromeAutocomplete" autocomplete="address-level4" />
         </div>
         <?php
            settings_fields('aiomatic_option_group');
            do_settings_sections('aiomatic_option_group');
            $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
            if (isset($aiomatic_Main_Settings['aiomatic_enabled'])) {
                $aiomatic_enabled = $aiomatic_Main_Settings['aiomatic_enabled'];
            } else {
                $aiomatic_enabled = '';
            }
            
            if (isset($aiomatic_Main_Settings['sentence_list'])) {
                $sentence_list = $aiomatic_Main_Settings['sentence_list'];
            } else {
                $sentence_list = '';
            }
            if (isset($aiomatic_Main_Settings['sentence_list2'])) {
                $sentence_list2 = $aiomatic_Main_Settings['sentence_list2'];
            } else {
                $sentence_list2 = '';
            }
            if (isset($aiomatic_Main_Settings['player_height'])) {
                $player_height = $aiomatic_Main_Settings['player_height'];
            } else {
                $player_height = '';
            }
            if (isset($aiomatic_Main_Settings['player_width'])) {
                $player_width = $aiomatic_Main_Settings['player_width'];
            } else {
                $player_width = '';
            }
            if (isset($aiomatic_Main_Settings['variable_list'])) {
                $variable_list = $aiomatic_Main_Settings['variable_list'];
            } else {
                $variable_list = '';
            }
            if (isset($aiomatic_Main_Settings['enable_detailed_logging'])) {
                $enable_detailed_logging = $aiomatic_Main_Settings['enable_detailed_logging'];
            } else {
                $enable_detailed_logging = '';
            }
            if (isset($aiomatic_Main_Settings['proxy_url'])) {
                $proxy_url = $aiomatic_Main_Settings['proxy_url'];
            } else {
                $proxy_url = '';
            }
            if (isset($aiomatic_Main_Settings['proxy_auth'])) {
                $proxy_auth = $aiomatic_Main_Settings['proxy_auth'];
            } else {
                $proxy_auth = '';
            }
            if (isset($aiomatic_Main_Settings['run_before'])) {
                $run_before = $aiomatic_Main_Settings['run_before'];
            } else {
                $run_before = '';
            }
            if (isset($aiomatic_Main_Settings['run_after'])) {
                $run_after = $aiomatic_Main_Settings['run_after'];
            } else {
                $run_after = '';
            }
            if (isset($aiomatic_Main_Settings['embeddings_model'])) {
                $embeddings_model = $aiomatic_Main_Settings['embeddings_model'];
            } else {
                $embeddings_model = '';
            }
            if (isset($aiomatic_Main_Settings['pinecone_index'])) {
                $pinecone_index = $aiomatic_Main_Settings['pinecone_index'];
            } else {
                $pinecone_index = '';
            }
            if (isset($aiomatic_Main_Settings['pinecone_topk'])) {
                $pinecone_topk = $aiomatic_Main_Settings['pinecone_topk'];
            } else {
                $pinecone_topk = '';
            }
            if (isset($aiomatic_Main_Settings['embeddings_single'])) {
                $embeddings_single = $aiomatic_Main_Settings['embeddings_single'];
            } else {
                $embeddings_single = '';
            }
            if (isset($aiomatic_Main_Settings['embeddings_bulk'])) {
                $embeddings_bulk = $aiomatic_Main_Settings['embeddings_bulk'];
            } else {
                $embeddings_bulk = '';
            }
            if (isset($aiomatic_Main_Settings['embeddings_edit'])) {
                $embeddings_edit = $aiomatic_Main_Settings['embeddings_edit'];
            } else {
                $embeddings_edit = '';
            }
            if (isset($aiomatic_Main_Settings['embeddings_chat_short'])) {
                $embeddings_chat_short = $aiomatic_Main_Settings['embeddings_chat_short'];
            } else {
                $embeddings_chat_short = '';
            }
            if (isset($aiomatic_Main_Settings['embeddings_article_short'])) {
                $embeddings_article_short = $aiomatic_Main_Settings['embeddings_article_short'];
            } else {
                $embeddings_article_short = '';
            }
            if (isset($aiomatic_Main_Settings['embeddings_edit_short'])) {
                $embeddings_edit_short = $aiomatic_Main_Settings['embeddings_edit_short'];
            } else {
                $embeddings_edit_short = '';
            }
            if (isset($aiomatic_Main_Settings['embeddings_related'])) {
                $embeddings_related = $aiomatic_Main_Settings['embeddings_related'];
            } else {
                $embeddings_related = '';
            }
            if (isset($aiomatic_Main_Settings['do_not_check_duplicates'])) {
                $do_not_check_duplicates = $aiomatic_Main_Settings['do_not_check_duplicates'];
            } else {
                $do_not_check_duplicates = '';
            }
            if (isset($aiomatic_Main_Settings['alternate_continue'])) {
                $alternate_continue = $aiomatic_Main_Settings['alternate_continue'];
            } else {
                $alternate_continue = '';
            }
            if (isset($aiomatic_Main_Settings['max_retry'])) {
                $max_retry = $aiomatic_Main_Settings['max_retry'];
            } else {
                $max_retry = '';
            }
            if (isset($aiomatic_Main_Settings['max_chat_retry'])) {
                $max_chat_retry = $aiomatic_Main_Settings['max_chat_retry'];
            } else {
                $max_chat_retry = '';
            }
            if (isset($aiomatic_Main_Settings['enable_logging'])) {
                $enable_logging = $aiomatic_Main_Settings['enable_logging'];
            } else {
                $enable_logging = '';
            }
            if (isset($aiomatic_Main_Settings['enable_tracking'])) {
                $enable_tracking = $aiomatic_Main_Settings['enable_tracking'];
            } else {
                $enable_tracking = '';
            }
            if (isset($aiomatic_Main_Settings['completion_suffix'])) {
                $completion_suffix = $aiomatic_Main_Settings['completion_suffix'];
            } else {
                $completion_suffix = '';
            }
            if (isset($aiomatic_Main_Settings['prompt_suffix'])) {
                $prompt_suffix = $aiomatic_Main_Settings['prompt_suffix'];
            } else {
                $prompt_suffix = '';
            }
            if (isset($aiomatic_Main_Settings['app_id'])) {
                $app_id = $aiomatic_Main_Settings['app_id'];
            } else {
                $app_id = '';
            }
            if (isset($aiomatic_Main_Settings['stability_app_id'])) {
                $stability_app_id = $aiomatic_Main_Settings['stability_app_id'];
            } else {
                $stability_app_id = '';
            }
            if (isset($aiomatic_Main_Settings['pinecone_app_id'])) {
                $pinecone_app_id = $aiomatic_Main_Settings['pinecone_app_id'];
            } else {
                $pinecone_app_id = '';
            }
            if (isset($aiomatic_Main_Settings['steps'])) {
                $steps = $aiomatic_Main_Settings['steps'];
            } else {
                $steps = '';
            }
            if (isset($aiomatic_Main_Settings['cfg_scale'])) {
                $cfg_scale = $aiomatic_Main_Settings['cfg_scale'];
            } else {
                $cfg_scale = '';
            }
            if (isset($aiomatic_Main_Settings['clip_guidance_preset'])) {
                $clip_guidance_preset = $aiomatic_Main_Settings['clip_guidance_preset'];
            } else {
                $clip_guidance_preset = '';
            }
            if (isset($aiomatic_Main_Settings['stable_model'])) {
                $stable_model = $aiomatic_Main_Settings['stable_model'];
            } else {
                $stable_model = '';
            }
            if (isset($aiomatic_Main_Settings['sampler'])) {
                $sampler = $aiomatic_Main_Settings['sampler'];
            } else {
                $sampler = '';
            }
            if (isset($aiomatic_Main_Settings['auto_clear_logs'])) {
                $auto_clear_logs = $aiomatic_Main_Settings['auto_clear_logs'];
            } else {
                $auto_clear_logs = '';
            }
            if (isset($aiomatic_Main_Settings['rule_timeout'])) {
                $rule_timeout = $aiomatic_Main_Settings['rule_timeout'];
            } else {
                $rule_timeout = '';
            }
            if (isset($aiomatic_Main_Settings['send_email'])) {
                $send_email = $aiomatic_Main_Settings['send_email'];
            } else {
                $send_email = '';
            }
            if (isset($aiomatic_Main_Settings['email_address'])) {
                $email_address = $aiomatic_Main_Settings['email_address'];
            } else {
                $email_address = '';
            }
            if (isset($aiomatic_Main_Settings['translate'])) {
                $translate = $aiomatic_Main_Settings['translate'];
            } else {
                $translate = '';
            }
            if (isset($aiomatic_Main_Settings['translate_source'])) {
                $translate_source = $aiomatic_Main_Settings['translate_source'];
            } else {
                $translate_source = '';
            }
            if (isset($aiomatic_Main_Settings['spin_text'])) {
                $spin_text = $aiomatic_Main_Settings['spin_text'];
            } else {
                $spin_text = '';
            }
            if (isset($aiomatic_Main_Settings['no_title'])) {
                $no_title = $aiomatic_Main_Settings['no_title'];
            } else {
                $no_title = '';
            }
            if (isset($aiomatic_Main_Settings['swear_filter'])) {
                $swear_filter = $aiomatic_Main_Settings['swear_filter'];
            } else {
                $swear_filter = '';
            }
            if (isset($aiomatic_Main_Settings['google_trans_auth'])) {
                $google_trans_auth = $aiomatic_Main_Settings['google_trans_auth'];
            } else {
                $google_trans_auth = '';
            }
            if (isset($aiomatic_Main_Settings['serpapi_auth'])) {
                $serpapi_auth = $aiomatic_Main_Settings['serpapi_auth'];
            } else {
                $serpapi_auth = '';
            }
            if (isset($aiomatic_Main_Settings['yt_app_id'])) {
                $yt_app_id = $aiomatic_Main_Settings['yt_app_id'];
            } else {
                $yt_app_id = '';
            }
            if (isset($aiomatic_Main_Settings['ai_resize_width'])) {
                $ai_resize_width = $aiomatic_Main_Settings['ai_resize_width'];
            } else {
                $ai_resize_width = '';
            }
            if (isset($aiomatic_Main_Settings['copy_locally'])) {
                $copy_locally = $aiomatic_Main_Settings['copy_locally'];
            } else {
                $copy_locally = '';
            }
            if (isset($aiomatic_Main_Settings['ai_resize_height'])) {
                $ai_resize_height = $aiomatic_Main_Settings['ai_resize_height'];
            } else {
                $ai_resize_height = '';
            }
            if (isset($aiomatic_Main_Settings['textrazor_key'])) {
                $textrazor_key = $aiomatic_Main_Settings['textrazor_key'];
            } else {
                $textrazor_key = '';
            }
            if (isset($aiomatic_Main_Settings['keyword_prompts'])) {
                $keyword_prompts = $aiomatic_Main_Settings['keyword_prompts'];
            } else {
                $keyword_prompts = '';
            }
            if (isset($aiomatic_Main_Settings['keyword_model'])) {
                $keyword_model = $aiomatic_Main_Settings['keyword_model'];
            } else {
                $keyword_model = '';
            }
            if (isset($aiomatic_Main_Settings['improve_keywords'])) {
                $improve_keywords = $aiomatic_Main_Settings['improve_keywords'];
            } else {
                $improve_keywords = '';
            }
            if (isset($aiomatic_Main_Settings['best_user'])) {
                $best_user = $aiomatic_Main_Settings['best_user'];
            } else {
                $best_user = '';
            }
            if (isset($aiomatic_Main_Settings['spin_lang'])) {
                $spin_lang = $aiomatic_Main_Settings['spin_lang'];
            } else {
                $spin_lang = '';
            }
            if (isset($aiomatic_Main_Settings['exclude_words'])) {
                $exclude_words = $aiomatic_Main_Settings['exclude_words'];
            } else {
                $exclude_words = '';
            }
            if (isset($aiomatic_Main_Settings['best_password'])) {
                $best_password = $aiomatic_Main_Settings['best_password'];
            } else {
                $best_password = '';
            }
            if (isset($aiomatic_Main_Settings['morguefile_api'])) {
                $morguefile_api = $aiomatic_Main_Settings['morguefile_api'];
            } else {
                $morguefile_api = '';
            }
            if (isset($aiomatic_Main_Settings['morguefile_secret'])) {
                $morguefile_secret = $aiomatic_Main_Settings['morguefile_secret'];
            } else {
                $morguefile_secret = '';
            }
            if (isset($aiomatic_Main_Settings['pexels_api'])) {
                $pexels_api = $aiomatic_Main_Settings['pexels_api'];
            } else {
                $pexels_api = '';
            }
            if (isset($aiomatic_Main_Settings['flickr_api'])) {
                $flickr_api = $aiomatic_Main_Settings['flickr_api'];
            } else {
                $flickr_api = '';
            }
            if (isset($aiomatic_Main_Settings['flickr_license'])) {
                $flickr_license = $aiomatic_Main_Settings['flickr_license'];
            } else {
                $flickr_license = '';
            }
            if (isset($aiomatic_Main_Settings['flickr_order'])) {
                $flickr_order = $aiomatic_Main_Settings['flickr_order'];
            } else {
                $flickr_order = '';
            }
            if (isset($aiomatic_Main_Settings['pixabay_api'])) {
                $pixabay_api = $aiomatic_Main_Settings['pixabay_api'];
            } else {
                $pixabay_api = '';
            }
            if (isset($aiomatic_Main_Settings['imgtype'])) {
                $imgtype = $aiomatic_Main_Settings['imgtype'];
            } else {
                $imgtype = '';
            }
            if (isset($aiomatic_Main_Settings['img_order'])) {
                $img_order = $aiomatic_Main_Settings['img_order'];
            } else {
                $img_order = '';
            }
            if (isset($aiomatic_Main_Settings['request_delay'])) {
                $request_delay = $aiomatic_Main_Settings['request_delay'];
            } else {
                $request_delay = '';
            }
            if (isset($aiomatic_Main_Settings['img_cat'])) {
                $img_cat = $aiomatic_Main_Settings['img_cat'];
            } else {
                $img_cat = '';
            }
            if (isset($aiomatic_Main_Settings['img_width'])) {
                $img_width = $aiomatic_Main_Settings['img_width'];
            } else {
                $img_width = '';
            }
            if (isset($aiomatic_Main_Settings['img_mwidth'])) {
                $img_mwidth = $aiomatic_Main_Settings['img_mwidth'];
            } else {
                $img_mwidth = '';
            }
            if (isset($aiomatic_Main_Settings['img_ss'])) {
                $img_ss = $aiomatic_Main_Settings['img_ss'];
            } else {
                $img_ss = '';
            }
            if (isset($aiomatic_Main_Settings['img_editor'])) {
                $img_editor = $aiomatic_Main_Settings['img_editor'];
            } else {
                $img_editor = '';
            }
            if (isset($aiomatic_Main_Settings['img_language'])) {
                $img_language = $aiomatic_Main_Settings['img_language'];
            } else {
                $img_language = '';
            }
            if (isset($aiomatic_Main_Settings['unsplash_api'])) {
                $unsplash_api = $aiomatic_Main_Settings['unsplash_api'];
            } else {
                $unsplash_api = '';
            }
            if (isset($aiomatic_Main_Settings['google_images'])) {
                $google_images = $aiomatic_Main_Settings['google_images'];
            } else {
                $google_images = '';
            }
            if (isset($aiomatic_Main_Settings['pixabay_scrape'])) {
                $pixabay_scrape = $aiomatic_Main_Settings['pixabay_scrape'];
            } else {
                $pixabay_scrape = '';
            }
            if (isset($aiomatic_Main_Settings['scrapeimgtype'])) {
                $scrapeimgtype = $aiomatic_Main_Settings['scrapeimgtype'];
            } else {
                $scrapeimgtype = '';
            }
            if (isset($aiomatic_Main_Settings['scrapeimg_orientation'])) {
                $scrapeimg_orientation = $aiomatic_Main_Settings['scrapeimg_orientation'];
            } else {
                $scrapeimg_orientation = '';
            }
            if (isset($aiomatic_Main_Settings['scrapeimg_order'])) {
                $scrapeimg_order = $aiomatic_Main_Settings['scrapeimg_order'];
            } else {
                $scrapeimg_order = '';
            }
            if (isset($aiomatic_Main_Settings['scrapeimg_cat'])) {
                $scrapeimg_cat = $aiomatic_Main_Settings['scrapeimg_cat'];
            } else {
                $scrapeimg_cat = '';
            }
            if (isset($aiomatic_Main_Settings['scrapeimg_width'])) {
                $scrapeimg_width = $aiomatic_Main_Settings['scrapeimg_width'];
            } else {
                $scrapeimg_width = '';
            }
            if (isset($aiomatic_Main_Settings['scrapeimg_height'])) {
                $scrapeimg_height = $aiomatic_Main_Settings['scrapeimg_height'];
            } else {
                $scrapeimg_height = '';
            }
            if (isset($aiomatic_Main_Settings['attr_text'])) {
                $attr_text = $aiomatic_Main_Settings['attr_text'];
            } else {
                $attr_text = '';
            }
            if (isset($aiomatic_Main_Settings['bimage'])) {
                $bimage = $aiomatic_Main_Settings['bimage'];
            } else {
                $bimage = '';
            }
            if (isset($aiomatic_Main_Settings['no_royalty_skip'])) {
                $no_royalty_skip = $aiomatic_Main_Settings['no_royalty_skip'];
            } else {
                $no_royalty_skip = '';
            }
            if (isset($aiomatic_Main_Settings['custom_html2'])) {
                $custom_html2 = $aiomatic_Main_Settings['custom_html2'];
            } else {
                $custom_html2 = '';
            }
            if (isset($aiomatic_Main_Settings['custom_html'])) {
                $custom_html = $aiomatic_Main_Settings['custom_html'];
            } else {
                $custom_html = '';
            }
            if (isset($aiomatic_Main_Settings['resize_width'])) {
                $resize_width = $aiomatic_Main_Settings['resize_width'];
            } else {
                $resize_width = '';
            }
            if (isset($aiomatic_Main_Settings['resize_height'])) {
                $resize_height = $aiomatic_Main_Settings['resize_height'];
            } else {
                $resize_height = '';
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
            <div id="tab-1" class="tab-content">
            <div class="aiomatic_class">
               <table class="widefat">
                  <tr>
                     <td>
                        <h1>
                           <span class="gs-sub-heading"><b>Aiomatic Automatic Post Generator Plugin - <?php echo esc_html__('Main Switch:', 'aiomatic-automatic-ai-content-writer');?></b>&nbsp;</span>
                           <span class="cr_07_font">v<?php echo aiomatic_get_version();?>&nbsp;&nbsp;</span>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Enable or disable this plugin. This acts like a main switch.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                        </h1>
                     </td>
                     <td>
                        <div class="slideThree">	
                           <input class="input-checkbox" type="checkbox" id="aiomatic_enabled" name="aiomatic_Main_Settings[aiomatic_enabled]"<?php
                              if ($aiomatic_enabled == 'on')
                                  echo ' checked ';
                              ?>>
                           <label for="aiomatic_enabled"></label>
                        </div>
                     </td>
                  </tr>
               </table>
            </div>
            <?php if($aiomatic_enabled != 'on'){echo '<div class="crf_bord cr_color_red cr_auto_update">' . esc_html__('This feature of the plugin is disabled! Please enable it from the above switch.', 'aiomatic-automatic-ai-content-writer') . '</div>';}?>
               <table class="widefat">
                  <tr>
                     <td colspan="2">
                        <?php
                           $plugin = plugin_basename(__FILE__);
                           $plugin_slug = explode('/', $plugin);
                           $plugin_slug = $plugin_slug[0]; 
                           $uoptions = get_option($plugin_slug . '_registration', array());
                           if(isset($uoptions['item_id']) && isset($uoptions['item_name']) && isset($uoptions['created_at']) && isset($uoptions['buyer']) && isset($uoptions['licence']) && isset($uoptions['supported_until']))
                           {
                           ?>
                        <h3><b><?php echo esc_html__("Plugin Registration Info - Automatic Updates Enabled:", 'aiomatic-automatic-ai-content-writer');?></b> </h3>
                        <ul>
                           <li><b><?php echo esc_html__("Item Name:", 'aiomatic-automatic-ai-content-writer');?></b> <?php echo esc_html($uoptions['item_name']);?></li>
                           <li>
                              <b><?php echo esc_html__("Item ID:", 'aiomatic-automatic-ai-content-writer');?></b> <?php echo esc_html($uoptions['item_id']);?>
                           </li>
                           <li>
                              <b><?php echo esc_html__("Created At:", 'aiomatic-automatic-ai-content-writer');?></b> <?php echo esc_html($uoptions['created_at']);?>
                           </li>
                           <li>
                              <b><?php echo esc_html__("Buyer Name:", 'aiomatic-automatic-ai-content-writer');?></b> <?php echo esc_html($uoptions['buyer']);?>
                           </li>
                           <li>
                              <b><?php echo esc_html__("License Type:", 'aiomatic-automatic-ai-content-writer');?></b> <?php echo esc_html($uoptions['licence']);?>
                           </li>
                           <li>
                              <b><?php echo esc_html__("Supported Until:", 'aiomatic-automatic-ai-content-writer');?></b> <?php echo esc_html($uoptions['supported_until']);?>
                           </li>
                           <li>
                              <input type="submit" onclick="unsaved = false;" class="button button-primary" name="<?php echo esc_html($plugin_slug);?>_revoke_license" value="<?php echo esc_html__("Revoke License", 'aiomatic-automatic-ai-content-writer');?>">
                           </li>
                        </ul>
                        <?php
                           }
                           else
                           {
                           ?>
                        <div class="notice notice-error is-dismissible"><p><?php echo esc_html__("This is a trial version of the plugin. Automatic updates for this plugin are disabled. Please activate the plugin from below, so you can benefit of automatic updates for it!", 'aiomatic-automatic-ai-content-writer');?></p></div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo sprintf( wp_kses( __( 'Please input your Envato purchase code, to enable automatic updates in the plugin. To get your purchase code, please follow <a href="%s" target="_blank">this tutorial</a>. Info submitted to the registration server consists of: purchase code, site URL, site name, admin email. All these data will be used strictly for registration purposes.', 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( '//coderevolution.ro/knowledge-base/faq/how-do-i-find-my-items-purchase-code-for-plugin-license-activation/' ) );
                                 ?>
                           </div>
                        </div>
                        <b><?php echo esc_html__("Register Envato Purchase Code To Enable Automatic Updates:", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td><input type="text" name="<?php echo esc_html($plugin_slug);?>_register_code" value="" placeholder="<?php echo esc_html__("Envato Purchase Code", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full"></td>
                  </tr>
                  <tr>
                     <td></td>
                     <td><input type="submit" name="<?php echo esc_html($plugin_slug);?>_register" id="<?php echo esc_html($plugin_slug);?>_register" class="button button-primary" onclick="unsaved = false;" value="<?php echo esc_html__("Register Purchase Code", 'aiomatic-automatic-ai-content-writer');?>"/>
                        <?php
                           }
                           ?>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <hr/>
                     </td>
                     <td>
                        <hr/>
                     </td>
                  </tr>
               <tr><td colspan="2">
               <h3><?php echo esc_html__("Tips and tricks:", 'aiomatic-automatic-ai-content-writer');?></h3>
                  <ul>
                     <li><?php echo sprintf( wp_kses( __( 'Need help configuring this plugin? Please check out it\'s <a href="%s" target="_blank">video tutorial</a>.', 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://www.youtube.com/watch?v=ou3ATnTANJA' ) );?>
                     </li>
                     <li><?php echo sprintf( wp_kses( __( 'Having issues with the plugin? Please be sure to check out our <a href="%s" target="_blank">knowledge-base</a> before you contact <a href="%s" target="_blank">our support</a>!', 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( '//coderevolution.ro/knowledge-base' ), esc_url('//coderevolution.ro/support' ) );?></li>
                     <li><?php echo sprintf( wp_kses( __( 'Do you enjoy our plugin? Please give it a <a href="%s" target="_blank">rating</a>  on CodeCanyon, or check <a href="%s" target="_blank">our website</a>  for other cool plugins.', 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( '//codecanyon.net/downloads' ), esc_url( 'https://coderevolution.ro' ) );?></a></li>
                  </ul>
            </td>
               </tr>
                     </table>
                     </div>
                     <div id="tab-2" class="tab-content">
                        <table class="widefat">
               <tr><td colspan="2"><h3><?php echo esc_html__("OpenAPI Settings:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
               <tr>
            <td>
               <div>
                  <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                     <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                           echo sprintf( wp_kses( __( "Insert your OpenAI/AiomaticAPI API Keys (one per line). For OpenAI API, get your API key <a href='%s' target='_blank'>here</a>. For AiomaticAPI, get your API key <a href='%s' target='_blank'>here</a>.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://beta.openai.com/account/api-keys' ), esc_url( 'https://aiomaticapi.com/pricing/' ) );
                           ?>
                     </div>
                  </div>
                  <b><a href='https://beta.openai.com/account/api-keys' target='_blank'><?php echo esc_html__("OpenAI", 'aiomatic-automatic-ai-content-writer');?></a>&nbsp;/&nbsp;<a href='https://aiomaticapi.com/api-keys/' target='_blank'><?php echo esc_html__("AiomaticAPI", 'aiomatic-automatic-ai-content-writer');?></a>&nbsp;<?php echo esc_html__("API Keys (One Per Line):", 'aiomatic-automatic-ai-content-writer');?></b>   
               </div>
            </td>
            <td>
               <div>
                  <textarea rows="2" id="app_id" class="cr_textarea_pass cr_width_full" name="aiomatic_Main_Settings[app_id]" placeholder="<?php echo esc_html__("Please insert your OpenAI/AiomaticAPI API Key", 'aiomatic-automatic-ai-content-writer');?>"><?php
                     echo esc_textarea($app_id);
                     ?></textarea>
               </div>
            </td>
            </tr>
               <tr><td colspan="2"><h3><?php echo esc_html__("Stability.AI API Settings:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
               <tr>
            <td>
               <div>
                  <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                     <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                           echo sprintf( wp_kses( __( "Insert your Stability.AI API Keys (one per line). For Stability.AI API, get your Stability.AI key <a href='%s' target='_blank'>here</a>.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://beta.dreamstudio.ai/membership?tab=apiKeys' ) );
                           ?>
                     </div>
                  </div>
                  <b><a href='https://beta.dreamstudio.ai/membership?tab=apiKeys' target='_blank'><?php echo esc_html__("Stability.AI", 'aiomatic-automatic-ai-content-writer');?></a>&nbsp;<?php echo esc_html__("API Keys (One Per Line):", 'aiomatic-automatic-ai-content-writer');?></b>   
               </div>
            </td>
            <td>
               <div>
                  <textarea rows="2" class="cr_textarea_pass cr_width_full" autocomplete="off" id="stability_app_id" name="aiomatic_Main_Settings[stability_app_id]" placeholder="<?php echo esc_html__("Please insert your Stability.AI API Key", 'aiomatic-automatic-ai-content-writer');?>"><?php
                     echo esc_textarea($stability_app_id);
                     ?></textarea>
               </div>
            </td>
            </tr>
               <tr><td colspan="2"><h3><?php echo esc_html__("Pinecone API Settings:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
               <tr>
            <td>
               <div>
                  <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                     <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                           echo sprintf( wp_kses( __( "Insert your Pinecone API Key. For Pinecone API, get your API key <a href='%s' target='_blank'>here</a>.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://www.pinecone.io/' ) );
                           ?>
                     </div>
                  </div>
                  <b><a href='https://www.pinecone.io/' target='_blank'><?php echo esc_html__("Pinecone.io", 'aiomatic-automatic-ai-content-writer');?></a>&nbsp;<?php echo esc_html__("API Key:", 'aiomatic-automatic-ai-content-writer');?></b>   
               </div>
            </td>
            <td>
               <div>
                  <textarea rows="2" class="cr_textarea_pass cr_width_full" autocomplete="off" id="pinecone_app_id" name="aiomatic_Main_Settings[pinecone_app_id]" placeholder="<?php echo esc_html__("Please insert your Pinecone.io API Key", 'aiomatic-automatic-ai-content-writer');?>"><?php
                     echo esc_textarea($pinecone_app_id);
                     ?></textarea>
               </div>
            </td>
            </tr>
            <tr><td colspan="2"><h3><?php echo esc_html__("Other API Keys:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
            <tr>
                    <td>
                       <div>
                          <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                             <div class="bws_hidden_help_text cr_min_260px">
                                <?php
                                   echo sprintf( wp_kses( __( "If you want to use SerpAPI to get the related headings for the created posts, you must add your API key here. By default, the plugin scrapes Bing Search for related queries. Get your API key <a href='%s' target='_blank'>here</a>.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://serpapi.com/manage-api-key' ));
                                   ?>
                             </div>
                          </div>
                          <b><a href="https://serpapi.com/manage-api-key" target="_blank"><?php echo esc_html__("SerpAPI API Key (Optional) (Used for Related Headings)", 'aiomatic-automatic-ai-content-writer');?>:</a></b>
                       </div>
                    </td>
                    <td>
                       <div>
                          <input type="password" autocomplete="off" id="serpapi_auth" placeholder="<?php echo esc_html__("SerpAPI Key (optional)", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[serpapi_auth]" value="<?php
                             echo esc_html($serpapi_auth);
                             ?>" class="cr_width_full"/>
                       </div>
                    </td>
                 </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo sprintf( wp_kses( __( "Insert your YouTube API Key. Learn how to get one <a href='%s' target='_blank'>here</a>. This is used when adding YouTube videos to your post content. You can also enter a comma separated list of multiple API keys. This is optional, the Related Videos feature will work also without an API key entered.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://console.cloud.google.com/apis/credentials' ) );
                                    ?>
                              </div>
                           </div>
                           <b><a href="https://console.cloud.google.com/apis/credentials" target="_blank"><?php echo esc_html__("YouTube API Key List (Optional) (Used for Related Videos):", 'aiomatic-automatic-ai-content-writer');?></a></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="password" autocomplete="off" id="yt_app_id" name="aiomatic_Main_Settings[yt_app_id]" value="<?php
                              echo esc_html($yt_app_id);
                              ?>" class="cr_width_full" placeholder="<?php echo esc_html__("Please insert your YouTube API Key. You can also insert a list of comma separated API keys. The plugin will select one to user, each time when it runs, at random.", 'aiomatic-automatic-ai-content-writer');?>">
                        </div>
                     </td>
                  </tr>
                  <tr>
                    <td>
                       <div>
                          <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                             <div class="bws_hidden_help_text cr_min_260px">
                                <?php
                                   echo sprintf( wp_kses( __( "If you wish to use the official version of the Google Translator API for translation, you must enter first a Google API Key. Get one <a href='%s' target='_blank'>here</a>. Please enable the 'Cloud Translation API' in <a href='%s' target='_blank'>Google Cloud Console</a>. Translation will work even without even without entering an API key here, but in this case, an unofficial Google Translate API will be used.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://console.cloud.google.com/apis/credentials' ), esc_url( 'https://console.cloud.google.com/marketplace/browse?q=translate' ) );
                                   ?>
                             </div>
                          </div>
                          <b><a href="https://console.cloud.google.com/apis/credentials" target="_blank"><?php echo esc_html__("Google Translator API Key (Optional)", 'aiomatic-automatic-ai-content-writer');?>:</a></b>
                       </div>
                    </td>
                    <td>
                       <div>
                          <input type="password" autocomplete="off" id="google_trans_auth" placeholder="<?php echo esc_html__("API Key (optional)", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[google_trans_auth]" value="<?php
                             echo esc_html($google_trans_auth);
                             ?>" class="cr_width_full"/>
                       </div>
                    </td>
                 </tr>
                                   </table>     
        </div>
        <div id="tab-3" class="tab-content">
        <table class="widefat">
        <tr><td colspan="2"><h3><?php echo esc_html__("Stability.AI API Settings:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
        <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Set what model to use when generating images. Default is stable-diffusion-512-v2-0.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Image Model:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <select id="stable_model" name="aiomatic_Main_Settings[stable_model]"  class="cr_width_full">
                              <option value="stable-diffusion-512-v2-0"<?php
                                 if ($stable_model == "stable-diffusion-512-v2-0") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("stable-diffusion-512-v2-0", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="stable-diffusion-v1"<?php
                                 if ($stable_model == "stable-diffusion-v1") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("stable-diffusion-v1", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="stable-diffusion-v1-5"<?php
                                 if ($stable_model == "stable-diffusion-v1-5") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("stable-diffusion-v1-5", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="stable-diffusion-768-v2-0"<?php
                                 if ($stable_model == "stable-diffusion-768-v2-0") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("stable-diffusion-768-v2-0", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="stable-diffusion-512-v2-1"<?php
                                 if ($stable_model == "stable-diffusion-512-v2-1") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("stable-diffusion-512-v2-1", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="stable-diffusion-768-v2-1"<?php
                                 if ($stable_model == "stable-diffusion-768-v2-1") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("stable-diffusion-768-v2-1", 'aiomatic-automatic-ai-content-writer');?></option>
                                 <option value="stable-inpainting-v1-0"<?php
                                    if ($stable_model == "stable-inpainting-v1-0") {
                                        echo " selected";
                                    }
                                    ?>><?php echo esc_html__("stable-inpainting-v1-0", 'aiomatic-automatic-ai-content-writer');?></option>
                                    <option value="stable-inpainting-512-v2-0"<?php
                                       if ($stable_model == "stable-inpainting-512-v2-0") {
                                           echo " selected";
                                       }
                                       ?>><?php echo esc_html__("stable-inpainting-512-v2-0", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
                        </div>
                     </td>
                  </tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("Number of diffusion steps to run. Default is 50.", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("Sampling Steps:", 'aiomatic-automatic-ai-content-writer');?></b>
                  </div>
               </td>
               <td>
                  <div>
                     <input type="number" min="10" step="1" max="250" name="aiomatic_Main_Settings[steps]" value="<?php echo esc_html($steps);?>" placeholder="<?php echo esc_html__("10-250", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">
                  </div>
               </td>
            </tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("How strictly the diffusion process adheres to the prompt text (higher values keep your image closer to your prompt). Default value is 7.", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("CFG Scale:", 'aiomatic-automatic-ai-content-writer');?></b>
                  </div>
               </td>
               <td>
                  <div>
                     <input type="number" min="0" step="1" max="35" name="aiomatic_Main_Settings[cfg_scale]" value="<?php echo esc_html($cfg_scale);?>" placeholder="<?php echo esc_html__("0-35", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">
                  </div>
               </td>
            </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Set what preset to use when generating images. Default is NONE.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Clip Guidance Preset:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <select id="clip_guidance_preset" name="aiomatic_Main_Settings[clip_guidance_preset]"  class="cr_width_full">
                              <option value="NONE"<?php
                                 if ($clip_guidance_preset == "NONE") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("NONE", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="FAST_BLUE"<?php
                                 if ($clip_guidance_preset == "FAST_BLUE") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("FAST_BLUE", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="FAST_GREEN"<?php
                                 if ($clip_guidance_preset == "FAST_GREEN") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("FAST_GREEN", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="SIMPLE"<?php
                                 if ($clip_guidance_preset == "SIMPLE") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("SIMPLE", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="SLOW"<?php
                                 if ($clip_guidance_preset == "SLOW") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("SLOW", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="SLOWER"<?php
                                 if ($clip_guidance_preset == "SLOWER") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("SLOWER", 'aiomatic-automatic-ai-content-writer');?></option>
                                 <option value="SLOWEST"<?php
                                    if ($clip_guidance_preset == "SLOWEST") {
                                        echo " selected";
                                    }
                                    ?>><?php echo esc_html__("SLOWEST", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Which sampler to use for the diffusion process. If this value is omitted we'll automatically select an appropriate sampler for you.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Sampler:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <select id="sampler" name="aiomatic_Main_Settings[sampler]"  class="cr_width_full">
                              <option value="auto"<?php
                                 if ($sampler == "auto") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Auto", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="DDIM"<?php
                                 if ($sampler == "DDIM") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("DDIM", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="DDPM"<?php
                                 if ($sampler == "DDPM") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("DDPM", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="K_DPMPP_2M"<?php
                                 if ($sampler == "K_DPMPP_2M") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("K_DPMPP_2M", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="K_DPMPP_2S_ANCESTRAL"<?php
                                 if ($sampler == "K_DPMPP_2S_ANCESTRAL") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("K_DPMPP_2S_ANCESTRAL", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="K_DPM_2"<?php
                                 if ($sampler == "K_DPM_2") {
                                    echo " selected";
                              }
                              ?>><?php echo esc_html__("K_DPM_2", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="K_DPM_2_ANCESTRAL"<?php
                              if ($sampler == "K_DPM_2_ANCESTRAL") {
                                    echo " selected";
                              }
                              ?>><?php echo esc_html__("K_DPM_2_ANCESTRAL", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="K_EULER"<?php
                              if ($sampler == "K_EULER") {
                                    echo " selected";
                              }
                              ?>><?php echo esc_html__("K_EULER", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="K_EULER_ANCESTRAL"<?php
                              if ($sampler == "K_EULER_ANCESTRAL") {
                                    echo " selected";
                              }
                              ?>><?php echo esc_html__("K_EULER_ANCESTRAL", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="K_HEUN"<?php
                              if ($sampler == "K_HEUN") {
                                    echo " selected";
                              }
                              ?>><?php echo esc_html__("K_HEUN", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="K_LMS"<?php
                              if ($sampler == "K_LMS") {
                                    echo " selected";
                              }
                              ?>><?php echo esc_html__("K_LMS", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
                        </div>
                     </td>
                  </tr></table>     
        </div>
        <div id="tab-4" class="tab-content">
        <table class="widefat">
            <tr><td colspan="2"><h3><?php echo esc_html__("AI Image Generator Settings:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("Do you want to copy AI generated images locally to your server?", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("Copy AI Generated Images Locally to Your Server:", 'aiomatic-automatic-ai-content-writer');?></b>
               </td>
               <td>
               <input type="checkbox" id="copy_locally" name="aiomatic_Main_Settings[copy_locally]" onclick="mainChanged()"<?php
                  if ($copy_locally == 'on')
                        echo ' checked ';
                  ?>>
               </td>
            </tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("Resize the AI generated image to the width specified in this text field (in pixels). If you want to disable this feature, leave this field blank. This feature will work only if you copy AI generated images locally to your server.", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("AI Generated Image Resize Width:", 'aiomatic-automatic-ai-content-writer');?></b>
                  </div>
               </td>
               <td>
                  <div>
                     <input type="number" min="1" step="1" name="aiomatic_Main_Settings[ai_resize_width]" value="<?php echo esc_html($ai_resize_width);?>" placeholder="<?php echo esc_html__("Please insert the desired width for AI generated images", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">
                  </div>
               </td>
            </tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("Resize the AI generated image to the height specified in this text field (in pixels). If you want to disable this feature, leave this field blank. This feature will work only if you copy AI generated images locally to your server.", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("AI Generated Image Resize Height:", 'aiomatic-automatic-ai-content-writer');?></b>
                  </div>
               </td>
               <td>
                  <div>
                     <input type="number" min="1" step="1" name="aiomatic_Main_Settings[ai_resize_height]" value="<?php echo esc_html($ai_resize_height);?>" placeholder="<?php echo esc_html__("Please insert the desired height for AI generated images", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">
                  </div>
               </td>
            </tr></table>     
        </div>
        <div id="tab-5" class="tab-content">
        <table class="widefat">
            <tr><td colspan="2"><h3><?php echo esc_html__("Statistics Settings:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Do you want to enable usage tracking for statistics and usage limits?", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Enable Usage Tracking For Statistics And Usage Limits:", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <input type="checkbox" id="enable_tracking" name="aiomatic_Main_Settings[enable_tracking]" <?php
                        if ($enable_tracking == 'on')
                            echo ' checked ';
                        ?>>
                     </td>
                  </tr></table>     
        </div>
        <div id="tab-6" class="tab-content">
        <table class="widefat">
            <tr><td colspan="2"><h3><?php echo esc_html__("Fine-Tuning Settings:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("If you are using fine tuned models, it is recommended you add the prompt suffix you used in your model training data, so the plugin can automatically add it to the prompts. The default is: \" ->\" (without the double quotes). Don't use new lines for suffixes (\\n) as currently they are not supported).", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("Suffix For Fine-Tuning Prompts:", 'aiomatic-automatic-ai-content-writer');?></b>
                  </div>
               </td>
               <td>
                  <div>
                     <input type="text" placeholder=" ->" name="aiomatic_Main_Settings[prompt_suffix]" value="<?php echo esc_html($prompt_suffix);?>" class="cr_width_full"/>
                  </div>
               </td>
            </tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("If you are using fine tuned models, it is recommended you add the completion suffix you used in your model training data, so the plugin can automatically add it to the completions. The default is: \" ###\". Don't use new lines for suffixes (\\n) as currently they are not supported).", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("Suffix For Fine-Tuning Completions:", 'aiomatic-automatic-ai-content-writer');?></b>
                  </div>
               </td>
               <td>
                  <div>
                     <input type="text" placeholder=" ###" name="aiomatic_Main_Settings[completion_suffix]" value="<?php echo esc_html($completion_suffix);?>" class="cr_width_full"/>
                  </div>
               </td>
            </tr></table>     
        </div>
        <div id="tab-13" class="tab-content">
<?php
if($pinecone_app_id != '')
{
?>
<h2><?php echo esc_html__("More details about Embeddings, check ", 'aiomatic-automatic-ai-content-writer');?><a href="<?php echo admin_url('admin.php?page=aiomatic_embeddings_panel');?>"><?php echo esc_html__("the 'AI Embeddings' settings page", 'aiomatic-automatic-ai-content-writer');?>.</a></h2>
        <table class="widefat">
            <tr><td colspan="2"><h3><?php echo esc_html__("AI Embeddings Settings:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("After creating your Pinecone API, create a new index. Make sure to set your dimension to 1536 and also make sure to set your metric to cosine. Enter the generated index ID here.", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("Pinecone Index:", 'aiomatic-automatic-ai-content-writer');?></b>
                  </div>
               </td>
               <td>
                  <div>
                     <input type="text" placeholder="mytestingindex-28cc276.svc.us-east1-gcp.pinecone.io" name="aiomatic_Main_Settings[pinecone_index]" value="<?php echo esc_html($pinecone_index);?>" class="cr_width_full"/>
                  </div>
               </td>
            </tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("The number of results to return for each query.", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("Number Of Results To Query:", 'aiomatic-automatic-ai-content-writer');?></b>
                  </div>
               </td>
               <td>
                  <div>
                     <input type="number" min="1" max="10000" step="1" placeholder="1" name="aiomatic_Main_Settings[pinecone_topk]" value="<?php echo esc_html($pinecone_topk);?>" class="cr_width_full"/>
                  </div>
               </td>
            </tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("Select the model you want to use for embeddings.", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("Embeddings Model:", 'aiomatic-automatic-ai-content-writer');?></b>
                  </div>
               </td>
               <td>
                  <div>
                     <select id="embeddings_model" name="aiomatic_Main_Settings[embeddings_model]" class="cr_width_full">
                        <option value="text-embedding-ada-002"<?php
                                 if ($embeddings_model == "text-embedding-ada-002") {
                                     echo " selected";
                                 }
                                 ?>>text-embedding-ada-002</option>
                     </select>  
                  </div>
               </td>
            </tr>
            <tr>
               <td>
                  <div>
                     <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                        <div class="bws_hidden_help_text cr_min_260px">
                           <?php
                              echo esc_html__("Enable embeddings for chat.", 'aiomatic-automatic-ai-content-writer');
                              ?>
                        </div>
                     </div>
                     <b><?php echo esc_html__("Enable Embeddings For:", 'aiomatic-automatic-ai-content-writer');?></b>
                  </div>
               </td>
               <td>
                  <div>
                     <input type="checkbox" id="embeddings_single" name="aiomatic_Main_Settings[embeddings_single]"<?php
                        if ($embeddings_single == 'on')
                            echo ' checked ';
                        ?>>
                        <label for="embeddings_single"><?php echo esc_html__("Single AI Post Creator", 'aiomatic-automatic-ai-content-writer');?></label><br/>

                     <input type="checkbox" id="embeddings_bulk" name="aiomatic_Main_Settings[embeddings_bulk]"<?php
                        if ($embeddings_bulk == 'on')
                            echo ' checked ';
                        ?>>
                        <label for="embeddings_bulk"><?php echo esc_html__("Bulk AI Post Creator", 'aiomatic-automatic-ai-content-writer');?></label><br/>

                     <input type="checkbox" id="embeddings_edit" name="aiomatic_Main_Settings[embeddings_edit]"<?php
                        if ($embeddings_edit == 'on')
                           echo ' checked ';
                        ?>>
                        <label for="embeddings_edit"><?php echo esc_html__("Content Editing", 'aiomatic-automatic-ai-content-writer');?></label><br/>

                     <input type="checkbox" id="embeddings_chat_short" name="aiomatic_Main_Settings[embeddings_chat_short]"<?php
                        if ($embeddings_chat_short == 'on')
                            echo ' checked ';
                        ?>>
                        <label for="embeddings_chat_short"><?php echo esc_html__("Chat Shortcodes", 'aiomatic-automatic-ai-content-writer');?></label><br/>
                        
                     <input type="checkbox" id="embeddings_article_short" name="aiomatic_Main_Settings[embeddings_article_short]"<?php
                        if ($embeddings_article_short == 'on')
                            echo ' checked ';
                        ?>>
                        <label for="embeddings_article_short"><?php echo esc_html__("Text Completion Shortcodes", 'aiomatic-automatic-ai-content-writer');?></label><br/>
                     
                     <input type="checkbox" id="embeddings_edit_short" name="aiomatic_Main_Settings[embeddings_edit_short]"<?php
                        if ($embeddings_edit_short == 'on')
                            echo ' checked ';
                        ?>>
                        <label for="embeddings_edit_short"><?php echo esc_html__("Text Editing Shortcode", 'aiomatic-automatic-ai-content-writer');?></label><br/>

                     <input type="checkbox" id="embeddings_related" name="aiomatic_Main_Settings[embeddings_related]"<?php
                        if ($embeddings_related == 'on')
                            echo ' checked ';
                        ?>>
                        <label for="embeddings_related"><?php echo esc_html__("Related Questions Creation", 'aiomatic-automatic-ai-content-writer');?></label><br/>
                  </div>
               </td>
            </tr>
         </table>
<?php
}
else
{
   echo '<h2>' . esc_html__("You need to enter a Pinecone.io API key in the 'API Keys' tab and save settings, to use this feature.", 'aiomatic-automatic-ai-content-writer') . '</h2>';
}
?>
        </div>
        <div id="tab-7" class="tab-content">
        <table class="widefat">
            <tr><td colspan="2"><h3><?php echo esc_html__("Plugin General Settings:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Add a time period between the plugin will run importing at a schedule. To disable this feature, leave this field blank. This works based on your current server timezone and time. Your current server time is: ", 'aiomatic-automatic-ai-content-writer') . date("h:i A");
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Automatically Run Rules Only Between These Hour Periods Each Day:", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <input type="time" id="run_after" name="aiomatic_Main_Settings[run_after]" value="<?php echo esc_html($run_after);?>" placeholder="<?php echo esc_html__("Run Rules Only After This Hour", 'aiomatic-automatic-ai-content-writer');?>"> - 
                     <input type="time" id="run_before" name="aiomatic_Main_Settings[run_before]" value="<?php echo esc_html($run_before);?>" placeholder="<?php echo esc_html__("Run Rules Only Before This Hour", 'aiomatic-automatic-ai-content-writer');?>">
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Do you want to enable logging for rules?", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Enable Logging for Rules:", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <input type="checkbox" id="enable_logging" name="aiomatic_Main_Settings[enable_logging]" onclick="mainChanged()"<?php
                        if ($enable_logging == 'on')
                            echo ' checked ';
                        ?>>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="hideLog">
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Do you want to enable detailed logging for rules? Note that this will dramatically increase the size of the log this plugin generates.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Enable Detailed Logging for Rules:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div class="hideLog">
                           <input type="checkbox" id="enable_detailed_logging" name="aiomatic_Main_Settings[enable_detailed_logging]"<?php
                              if ($enable_detailed_logging == 'on')
                                  echo ' checked ';
                              ?>>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="hideLog">
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Choose if you want to automatically clear logs after a period of time.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Automatically Clear Logs After:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div class="hideLog">
                           <select id="auto_clear_logs" name="aiomatic_Main_Settings[auto_clear_logs]" class="cr_width_full">
                              <option value="No"<?php
                                 if ($auto_clear_logs == "No") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Disabled", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="monthly"<?php
                                 if ($auto_clear_logs == "monthly") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Once a month", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="weekly"<?php
                                 if ($auto_clear_logs == "weekly") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Once a week", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="daily"<?php
                                 if ($auto_clear_logs == "daily") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Once a day", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="twicedaily"<?php
                                 if ($auto_clear_logs == "twicedaily") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Twice a day", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="hourly"<?php
                                 if ($auto_clear_logs == "hourly") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Once an hour", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Set the maximum number of times the plugin will retry API calls in case they fail. This is useful, as in some cases OpenAI API is failing and a retry will work. To disable this feature, leave this field blank.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("How Many Times To Retry API Calls In Case Of API Failure:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="number" id="max_retry" step="1" min="0" placeholder="<?php echo esc_html__("API retry max count", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[max_retry]" value="<?php
                              echo esc_html($max_retry);
                              ?>" class="cr_width_full"/>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Set the maximum number of times the plugin will retry chat API calls in case the AI writer considers the chat as ended. Warning, this can consume more tokens, as it will retry API calls multiple times. To disable this feature, leave this field blank.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Chat End of Conversation Retry Count:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="number" id="max_chat_retry" step="1" min="0" placeholder="<?php echo esc_html__("Chat end API retry max count", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[max_chat_retry]" value="<?php
                              echo esc_html($max_chat_retry);
                              ?>" class="cr_width_full"/>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div>
                              <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                 <div class="bws_hidden_help_text cr_min_260px">
                                    <?php
                                       echo esc_html__("Choose if you want to skip checking for duplicate post titles when publishing new posts. If you check this, duplicate post titles will be posted! So use it only when it is necesarry.", 'aiomatic-automatic-ai-content-writer');
                                       ?>
                                 </div>
                              </div>
                              <b><?php echo esc_html__("Do Not Check For Duplicate Titles:", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <input type="checkbox" id="do_not_check_duplicates" name="aiomatic_Main_Settings[do_not_check_duplicates]"<?php
                        if ($do_not_check_duplicates == 'on')
                            echo ' checked ';
                        ?>>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div>
                              <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                 <div class="bws_hidden_help_text cr_min_260px">
                                    <?php
                                       echo esc_html__("If you want to create long content (over 10000 words) in a single post and you are getting undesired results, you can check this checkbox for a fix.", 'aiomatic-automatic-ai-content-writer');
                                       ?>
                                 </div>
                              </div>
                              <b><?php echo esc_html__("Use Alternate Continue Tokens (Experimental):", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <input type="checkbox" id="alternate_continue" name="aiomatic_Main_Settings[alternate_continue]"<?php
                        if ($alternate_continue == 'on')
                            echo ' checked ';
                        ?>>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("If you want to use a proxy to crawl webpages, input it's address here. Required format: IP Address/URL:port. You can input a comma separated list of proxies.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Web Proxy Address List:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="text" id="proxy_url" placeholder="<?php echo esc_html__("Input web proxy url", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[proxy_url]" value="<?php echo esc_html($proxy_url);?>" class="cr_width_full"/>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("If you want to use a proxy to crawl webpages, and it requires authentification, input it's authentification details here. Required format: username:password. You can input a comma separated list of users/passwords. If a proxy does not have a user/password, please leave it blank in the list. Example: user1:pass1,user2:pass2,,user4:pass4.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Web Proxy Authentication:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="text" id="proxy_auth" placeholder="<?php echo esc_html__("Input web proxy auth", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[proxy_auth]" value="<?php echo esc_html($proxy_auth);?>" class="cr_width_full"/>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Set the timeout (in seconds) for every rule running. I recommend that you leave this field at it's default value (3600).", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Timeout for Rule Running (seconds):", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="number" id="rule_timeout" step="1" min="0" placeholder="<?php echo esc_html__("Input rule timeout in seconds", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[rule_timeout]" value="<?php
                              echo esc_html($rule_timeout);
                              ?>" class="cr_width_full"/>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Set the timeout (in milliseconds) between each subsequent API call. This will allow API call throttling, so the API call quota limit is not reached for your account.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Delay Between Multiple API Requests:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="text" id="request_delay" placeholder="<?php echo esc_html__("Input request delay", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[request_delay]" value="<?php echo esc_html($request_delay);?>" class="cr_width_full"/>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Choose if you want to receive a summary of the rule running in an email.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Send Rule Running Summary in Email:", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <input type="checkbox" id="send_email" name="aiomatic_Main_Settings[send_email]" onchange="mainChanged()"<?php
                        if ($send_email == 'on')
                            echo ' checked ';
                        ?>>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="hideMail">
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Input the email adress where you want to send the report. You can input more email addresses, separated by commas.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Email Address:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div class="hideMail">
                           <input type="text" id="email_address" placeholder="<?php echo esc_html__("Input a valid email adress", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[email_address]" value="<?php
                              echo esc_html($email_address);
                              ?>" class="cr_width_full">
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Set the maximum width of the player in pixels. Default value is 580.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Player Max Width (Pixels):", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="number" id="player_width" step="1" min="0" placeholder="<?php echo esc_html__("580", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[player_width]" value="<?php
                              echo esc_html($player_width);
                              ?>" class="cr_width_full"/>  
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Set the maximum height of the player in pixels. Default value is 380.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Player Max Height (Pixels):", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="number" id="player_height" step="1" min="0" placeholder="<?php echo esc_html__("380", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Main_Settings[player_height]" value="<?php
                              echo esc_html($player_height);
                              ?>" class="cr_width_full"/>  
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Resize the image that was assigned to be the featured image to the width specified in this text field (in pixels). If you want to disable this feature, leave this field blank.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Featured Image Resize Width:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="number" min="1" step="1" name="aiomatic_Main_Settings[resize_width]" value="<?php echo esc_html($resize_width);?>" placeholder="<?php echo esc_html__("Please insert the desired width for featured images", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Resize the image that was assigned to be the featured image to the height specified in this text field (in pixels). If you want to disable this feature, leave this field blank.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Featured Image Resize Height:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="number" min="1" step="1" name="aiomatic_Main_Settings[resize_height]" value="<?php echo esc_html($resize_height);?>" placeholder="<?php echo esc_html__("Please insert the desired height for featured images", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Do you want to enable swear word filtering for created content?", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Enable Swear Word Filtering:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="checkbox" id="swear_filter" name="aiomatic_Main_Settings[swear_filter]"<?php
                              if ($swear_filter == 'on')
                                  echo ' checked ';
                              ?>>
                        </div>
                     </td>
                  </tr></table>     
        </div>
        <div id="tab-12" class="tab-content">
        <table class="widefat">
        <tr><td colspan="2"><h3><?php echo esc_html__("Spin & Translate:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Do you want to automatically translate generated content using Google Translate?", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Automatically Translate Content To:", 'aiomatic-automatic-ai-content-writer');?></b><br/><b><?php echo esc_html__("Info:", 'aiomatic-automatic-ai-content-writer');?></b> <?php echo esc_html__("for translation, the plugin also supports WPML.", 'aiomatic-automatic-ai-content-writer');?> <b><a href="https://wpml.org/?aid=238195&affiliate_key=ix3LsFyq0xKz" target="_blank"><?php echo esc_html__("Get WPML now!", 'aiomatic-automatic-ai-content-writer');?></a></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <select id="translate" name="aiomatic_Main_Settings[translate]"  class="cr_width_full">
                           <?php
                              $i = 0;
                              foreach ($language_names as $lang) {
                                  echo '<option value="' . esc_html($language_codes[$i]) . '"';
                                  if ($translate == $language_codes[$i]) {
                                      echo ' selected';
                                  }
                                  echo '>' . esc_html($language_names[$i]) . '</option>';
                                  $i++;
                              }
                              ?>
                           </select>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Select the source language of the translation.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Translation Source Language:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <select id="translate_source" name="aiomatic_Main_Settings[translate_source]"  class="cr_width_full">
                           <?php
                              $i = 0;
                              foreach ($language_names as $lang) {
                                  echo '<option value="' . esc_html($language_codes[$i]) . '"';
                                  if ($translate_source == $language_codes[$i]) {
                                      echo ' selected';
                                  }
                                  echo '>' . esc_html($language_names[$i]) . '</option>';
                                  $i++;
                              }
                              ?>
                           </select>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div id="bestspin">
                           <p><?php echo esc_html__("Don't have an 'The Best Spinner' account yet? Click here to get one:", 'aiomatic-automatic-ai-content-writer');?> <b><a href="https://paykstrt.com/10313/38910" target="_blank"><?php echo esc_html__("get a new account now!", 'aiomatic-automatic-ai-content-writer');?></a></b></p>
                        </div>
                        <div id="wordai">
                           <p><?php echo esc_html__("Don't have an 'WordAI' account yet? Click here to get one:", 'aiomatic-automatic-ai-content-writer');?> <b><a href="https://wordai.com/?ref=h17f4" target="_blank"><?php echo esc_html__("get a new account now!", 'aiomatic-automatic-ai-content-writer');?></a></b></p>
                        </div>
                        <div id="spinrewriter">
                           <p><?php echo esc_html__("Don't have an 'SpinRewriter' account yet? Click here to get one:", 'aiomatic-automatic-ai-content-writer');?> <b><a href="https://www.spinrewriter.com/?ref=24b18" target="_blank"><?php echo esc_html__("get a new account now!", 'aiomatic-automatic-ai-content-writer');?></a></b></p>
                        </div>
                        <div id="spinnerchief">
                           <p><?php echo esc_html__("Don't have an 'SpinnerChief' account yet? Click here to get one:", 'aiomatic-automatic-ai-content-writer');?> <b><a href="http://www.whitehatbox.com/Agents/SSS?code=iscpuQScOZMi3vGFhPVBnAP5FyC6mPaOEshvgU4BbyoH8ftVRbM3uQ==" target="_blank"><?php echo esc_html__("get a new account now!", 'aiomatic-automatic-ai-content-writer');?></a></b></p>
                        </div>
                        <div id="contentprofessor">
                           <p><?php echo esc_html__("Don't have an 'ContentProfessor' account yet? Click here to get one:", 'aiomatic-automatic-ai-content-writer');?> <b><a href="http://www.contentprofessor.com/go.php?offer=kisded&pid=2" target="_blank"><?php echo esc_html__("get a new account now!", 'aiomatic-automatic-ai-content-writer');?></a></b></p>
                        </div>
                        <div id="chimprewriter">
                           <p><?php echo esc_html__("Don't have an 'ChimpRewriter' account yet? Click here to get one:", 'aiomatic-automatic-ai-content-writer');?> <b><a href="https://coderevolution--chimprewriter.thrivecart.com/chimp-rewriter-monthly/" target="_blank"><?php echo esc_html__("get a new account now!", 'aiomatic-automatic-ai-content-writer');?></a></b></p>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Do you want to randomize text by changing words of a text with synonyms using one of the listed methods? Note that this is an experimental feature and can in some instances drastically increase the rule running time!", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Spin Text Using Word Synonyms (for automatically generated posts only):", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <select id="spin_text" name="aiomatic_Main_Settings[spin_text]" onchange="mainChanged()" class="cr_width_full">
                     <option value="disabled"
                        <?php
                           if ($spin_text == 'disabled') {
                               echo ' selected';
                           }
                           ?>
                        ><?php echo esc_html__("Disabled", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="best"
                        <?php
                           if ($spin_text == 'best') {
                               echo ' selected';
                           }
                           ?>
                        >The Best Spinner - <?php echo esc_html__("High Quality - Paid", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="wordai"
                        <?php
                           if($spin_text == 'wordai')
                                   {
                                       echo ' selected';
                                   }
                           ?>
                        >Wordai - <?php echo esc_html__("High Quality - Paid", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="spinrewriter"
                        <?php
                           if($spin_text == 'spinrewriter')
                                   {
                                       echo ' selected';
                                   }
                           ?>
                        >SpinRewriter - <?php echo esc_html__("High Quality - Paid", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="spinnerchief"
                        <?php
                           if($spin_text == 'spinnerchief')
                                   {
                                       echo ' selected';
                                   }
                           ?>
                        >SpinnerChief - <?php echo esc_html__("High Quality - Paid", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="chimprewriter"
                        <?php
                           if($spin_text == 'chimprewriter')
                                   {
                                       echo ' selected';
                                   }
                           ?>
                        >ChimpRewriter - <?php echo esc_html__("High Quality - Paid", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="contentprofessor"
                        <?php
                           if($spin_text == 'contentprofessor')
                                   {
                                       echo ' selected';
                                   }
                           ?>
                        >ContentProfessor - <?php echo esc_html__("High Quality - Paid", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="builtin"
                        <?php
                           if ($spin_text == 'builtin') {
                               echo ' selected';
                           }
                           ?>
                        ><?php echo esc_html__("Built-in - Medium Quality - Free", 'aiomatic-automatic-ai-content-writer');?></option>
                     </select>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Do you want to not spin content (only title)?", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Do Not Spin Content, Only Title:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="checkbox" id="no_title" name="aiomatic_Main_Settings[no_title]"<?php
                              if ($no_title == 'on')
                                  echo ' checked ';
                              ?> class="cr_width_full">
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Select a list of comma separated words that you do not wish to spin (only for built-in spinners).", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Excluded Word List (For Built-In Spinner Only):", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="text" name="aiomatic_Main_Settings[exclude_words]" value="<?php
                              echo esc_html($exclude_words);
                              ?>" placeholder="<?php echo esc_html__("word1, word2, word3", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">
                        </div>
                     </td>
                  </tr>
         <tr class="hideChief">
            <td>
               <div>
                  <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                     <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                           echo esc_html__("Select the language of the content that will be processed.", 'aiomatic-automatic-ai-content-writer');
                           ?>
                     </div>
                  </div>
                  <b><?php echo esc_html__("SpinnerChief Spinning Language:", 'aiomatic-automatic-ai-content-writer');?></b>
               </div>
            </td>
            <td>
               <div>
                    <select class="cr_width_80" name="aiomatic_Main_Settings[spin_lang]" class="cr_width_full" >
                     <option value='English'<?php
                        if ($spin_lang == 'English')
                            echo ' selected';
                        ?>><?php echo esc_html__("English", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Arabic'<?php
                        if ($spin_lang == 'Arabic')
                            echo ' selected';
                        ?>><?php echo esc_html__("Arabic", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Belarusian'<?php
                        if ($spin_lang == 'Belarusian')
                            echo ' selected';
                        ?>><?php echo esc_html__("Belarusian", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Bulgarian'<?php
                        if ($spin_lang == 'Bulgarian')
                            echo ' selected';
                        ?>><?php echo esc_html__("Bulgarian", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Croatian'<?php
                        if ($spin_lang == 'Croatian')
                            echo ' selected';
                        ?>><?php echo esc_html__("Croatian", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Danish'<?php
                        if ($spin_lang == 'Danish')
                            echo ' selected';
                        ?>><?php echo esc_html__("Danish", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Dutch'<?php
                        if ($spin_lang == 'Dutch')
                            echo ' selected';
                        ?>><?php echo esc_html__("Dutch", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Filipino'<?php
                        if ($spin_lang == 'Filipino')
                            echo ' selected';
                        ?>><?php echo esc_html__("Filipino", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Finnish'<?php
                        if ($spin_lang == 'Finnish')
                            echo ' selected';
                        ?>><?php echo esc_html__("Finnish", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='French'<?php
                        if ($spin_lang == 'French')
                            echo ' selected';
                        ?>><?php echo esc_html__("French", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='German'<?php
                        if ($spin_lang == 'German')
                            echo ' selected';
                        ?>><?php echo esc_html__("German", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Greek'<?php
                        if ($spin_lang == 'Greek')
                            echo ' selected';
                        ?>><?php echo esc_html__("Greek", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Hebrew'<?php
                        if ($spin_lang == 'Hebrew')
                            echo ' selected';
                        ?>><?php echo esc_html__("Hebrew", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Indonesian'<?php
                        if ($spin_lang == 'Indonesian')
                            echo ' selected';
                        ?>><?php echo esc_html__("Indonesian", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Italian'<?php
                        if ($spin_lang == 'Italian')
                            echo ' selected';
                        ?>><?php echo esc_html__("Italian", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Lithuanian'<?php
                        if ($spin_lang == 'Lithuanian')
                            echo ' selected';
                        ?>><?php echo esc_html__("Lithuanian", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Norwegian'<?php
                        if ($spin_lang == 'Norwegian')
                            echo ' selected';
                        ?>><?php echo esc_html__("Norwegian", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Polish'<?php
                        if ($spin_lang == 'Polish')
                            echo ' selected';
                        ?>><?php echo esc_html__("Polish", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Portuguese'<?php
                        if ($spin_lang == 'Portuguese')
                            echo ' selected';
                        ?>><?php echo esc_html__("Portuguese", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Romanian'<?php
                        if ($spin_lang == 'Romanian')
                            echo ' selected';
                        ?>><?php echo esc_html__("Romanian", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Slovak'<?php
                        if ($spin_lang == 'Slovak')
                            echo ' selected';
                        ?>><?php echo esc_html__("Slovak", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Slovenian'<?php
                        if ($spin_lang == 'Slovenian')
                            echo ' selected';
                        ?>><?php echo esc_html__("Slovenian", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Spanish'<?php
                        if ($spin_lang == 'Spanish')
                            echo ' selected';
                        ?>><?php echo esc_html__("Spanish", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Swedish'<?php
                        if ($spin_lang == 'Swedish')
                            echo ' selected';
                        ?>><?php echo esc_html__("Swedish", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Turkish'<?php
                        if ($spin_lang == 'Turkish')
                            echo ' selected';
                        ?>><?php echo esc_html__("Turkish", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value='Vietnamese'<?php
                        if ($spin_lang == 'Vietnamese')
                            echo ' selected';
                        ?>><?php echo esc_html__("Vietnamese", 'aiomatic-automatic-ai-content-writer');?></option>
                  </select> 
                     
                     
               </div>
            </td>
         </tr>
                  <tr>
                     <td>
                        <div class="hideBest">
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Insert your user name on premium spinner service.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Premium Spinner Service User Name/Email:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div class="hideBest">
                           <input type="text" name="aiomatic_Main_Settings[best_user]" value="<?php
                              echo esc_html($best_user);
                              ?>" placeholder="<?php echo esc_html__("Please insert your premium text spinner service user name", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="hideBest">
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Insert your password for the selected premium spinner service.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Premium Spinner Service Password/API Key:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div class="hideBest">
                           <input type="password" autocomplete="off" name="aiomatic_Main_Settings[best_password]" value="<?php
                              echo esc_html($best_password);
                              ?>" placeholder="<?php echo esc_html__("Please insert your premium text spinner service password", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">
                        </div>
                     </td>
                  </tr>
                  </table>     
        </div>
        <div id="tab-8" class="tab-content">
        <table class="widefat">
                  <tr>
                     <td colspan="2">
                        <h3><?php echo esc_html__("Royalty Free Image Options:", 'aiomatic-automatic-ai-content-writer');?></h3>
                        </td></tr>
                  <tr>
                     <td colspan="2">
                        <hr class="cr_dotted"/>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Choose if you want to improve royalty free image importing, using the below services. These will extract keywords from the original text and provide better image quality results. If you select TextRazor, you also need to enter a TextRazor API key below. If you select OpenAI, you also need to enter a prompt for OpenAI keyword extraction, below.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Improve Royalty Free Featured Image Precision Using This Service:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <select id="improve_keywords" name="aiomatic_Main_Settings[improve_keywords]" class="cr_width_full" >
                              <option value="disabled"<?php
                                 if ($improve_keywords == "disabled") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("Disabled", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="textrazor"<?php
                                 if ($improve_keywords == "textrazor") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("TextRazor", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value="openai"<?php
                                 if ($improve_keywords == "openai") {
                                     echo " selected";
                                 }
                                 ?>><?php echo esc_html__("OpenAI/AiomaticAPI", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo sprintf( wp_kses( __( "Insert your TextRazor API Key. Learn how to get one <a href='%s' target='_blank'>here</a>. This is used when extracting relevant keywords from longer texts. Adding an API key here can greatly improve royalty free image accuracy.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://www.textrazor.com/console' ) );
                                    ?>
                              </div>
                           </div>
                           <b><a href="https://www.textrazor.com/console" target="_blank"><?php echo esc_html__("TextRazor API Key List (Optional) (Used for Relevant Keyword Extraction From Text):", 'aiomatic-automatic-ai-content-writer');?></a></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="password" autocomplete="off" id="textrazor_key" name="aiomatic_Main_Settings[textrazor_key]" value="<?php
                              echo esc_html($textrazor_key);
                              ?>" class="cr_width_full" placeholder="<?php echo esc_html__("Please insert your TextRazor API Key", 'aiomatic-automatic-ai-content-writer');?>">
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__( "Set a prompt for generating a keyword for importing royalty free images for the created posts. You can use something like: Extract a comma separated list of relevant keywords from the text: '%%post_title%%'. You can also instruct the AI writer to return a comma separated list of keywords. You can use the following shortcodes here: %%post_title%%, %%random_sentence%%, %%random_sentence2%%, %%blog_title%%. You can also add a link to a TXT file, containing keywords (one per line), or to an RSS feed. If you use RSS feeds, you can also use the following additional shortcodes: %%post_content%%, %%post_content_plain_text%%, %%post_excerpt%%, %%post_cats%%, %%author_name%%, %%post_link%%. The length of this command should not be greater than the max token count set in the settings for the seed command - Update: nested shortcodes also supported (shortcodes generated by rules from other plugins). You can also add here a link to a .txt file, where you can add multiple prompts (one per line) and the plugin will select a random one at each run.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Prompt For OpenAI Keyword Generator For Royalty Free Image Importing:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                        <textarea rows="2" cols="70" class="cr_width_full" name="aiomatic_Main_Settings[keyword_prompts]" placeholder="<?php echo esc_html__("Extract a comma separated list of relevant keywords from the text: '%%post_title%%'.", 'aiomatic-automatic-ai-content-writer');?>"><?php
                        echo esc_textarea($keyword_prompts);
                        ?></textarea>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Select the model you want to use for keyword extraction, for royalty free image importing.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Model For Keyword Extraction For Royalty Free Images:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <select id="keyword_model" name="aiomatic_Main_Settings[keyword_model]" class="cr_width_full">
<?php
foreach($all_models as $modelx)
{
   echo '<option value="' . $modelx .'"';
   if ($keyword_model == $modelx) 
   {
       echo " selected";
   }
   echo '>' . esc_html($modelx) . '</option>';
}
?>
                           </select>  
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td colspan="2">
                        <hr class="cr_dotted"/>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo sprintf( wp_kses( __( "Insert your Pexels App ID. Learn how to get an API key <a href='%s' target='_blank'>here</a>. If you enter an API Key and an API Secret, you will enable search for images using the Pexels API.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( "https://www.pexels.com/api/" ));
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Pexels App ID:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="text" name="aiomatic_Main_Settings[pexels_api]" value="<?php
                              echo esc_html($pexels_api);
                              ?>" placeholder="<?php echo esc_html__("Please insert your Pexels API key", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td colspan="2">
                        <hr class="cr_dotted"/>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo sprintf( wp_kses( __( "Insert your Flickr App ID. Learn how to get an API key <a href='%s' target='_blank'>here</a>. If you enter an API Key and an API Secret, you will enable search for images using the Flickr API.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( "https://www.flickr.com/services/apps/create/apply" ));
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Flickr App ID: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="text" name="aiomatic_Main_Settings[flickr_api]" placeholder="<?php echo esc_html__("Please insert your Flickr APP ID", 'aiomatic-automatic-ai-content-writer');?>" value="<?php if(isset($flickr_api)){echo esc_html($flickr_api);}?>" class="cr_width_full" />
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("The license id for photos to be searched.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Photo License: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <select name="aiomatic_Main_Settings[flickr_license]" class="cr_width_full">
                           <option value="-1" 
                              <?php
                                 if($flickr_license == '-1')
                                 {
                                     echo ' selected';
                                 }
                                 ?>
                              ><?php echo esc_html__("Do Not Search By Photo Licenses", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="0"
                              <?php
                                 if($flickr_license == '0')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("All Rights Reserved", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="1"
                              <?php
                                 if($flickr_license == '1')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Attribution-NonCommercial-ShareAlike License", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="2"
                              <?php
                                 if($flickr_license == '2')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Attribution-NonCommercial License", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="3"
                              <?php
                                 if($flickr_license == '3')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Attribution-NonCommercial-NoDerivs License", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="4"
                              <?php
                                 if($flickr_license == '4')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Attribution License", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="5"
                              <?php
                                 if($flickr_license == '5')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Attribution-ShareAlike License", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="6"
                              <?php
                                 if($flickr_license == '6')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Attribution-NoDerivs License", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="7"
                              <?php
                                 if($flickr_license == '7')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("No known copyright restrictions", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="8"
                              <?php
                                 if($flickr_license == '8')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("United States Government Work", 'aiomatic-automatic-ai-content-writer');?></option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("The order in which to sort returned photos. Deafults to date-posted-desc (unless you are doing a radial geo query, in which case the default sorting is by ascending distance from the point specified).", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Search Results Order: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <select name="aiomatic_Main_Settings[flickr_order]" class="cr_width_full">
                           <option value="date-posted-desc"
                              <?php
                                 if($flickr_order == 'date-posted-desc')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Date Posted Descendant", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="date-posted-asc"
                              <?php
                                 if($flickr_order == 'date-posted-asc')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Date Posted Ascendent", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="date-taken-asc"
                              <?php
                                 if($flickr_order == 'date-taken-asc')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Date Taken Ascendent", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="date-taken-desc"
                              <?php
                                 if($flickr_order == 'date-taken-desc')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Date Taken Descendant", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="interestingness-desc"
                              <?php
                                 if($flickr_order == 'interestingness-desc')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Interestingness Descendant", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="interestingness-asc"
                              <?php
                                 if($flickr_order == 'interestingness-asc')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Interestingness Ascendant", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="relevance"
                              <?php
                                 if($flickr_order == 'relevance')
                                 {
                                     echo ' selected';
                                 }
                                 ?>><?php echo esc_html__("Relevance", 'aiomatic-automatic-ai-content-writer');?></option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <td colspan="2">
                        <hr class="cr_dotted"/>
                     </td>
                  </tr>
                  </td></tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo sprintf( wp_kses( __( "Insert your Pixabay App ID. Learn how to get one <a href='%s' target='_blank'>here</a>. If you enter an API Key here, you will enable search for images using the Pixabay API.", 'aiomatic-automatic-ai-content-writer'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( "https://pixabay.com/api/docs/" ) );
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Pixabay App ID:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <input type="text" class="cr_width_full" name="aiomatic_Main_Settings[pixabay_api]" value="<?php
                              echo esc_html($pixabay_api);
                              ?>" placeholder="<?php echo esc_html__("Please insert your Pixabay API key", 'aiomatic-automatic-ai-content-writer');?>">
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Filter results by image type.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Image Types To Search:", 'aiomatic-automatic-ai-content-writer');?></b>
                        </div>
                     </td>
                     <td>
                        <div>
                           <select class="cr_width_full" name="aiomatic_Main_Settings[imgtype]" >
                              <option value='all'<?php
                                 if ($imgtype == 'all')
                                     echo ' selected';
                                 ?>><?php echo esc_html__("All", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value='photo'<?php
                                 if ($imgtype == 'photo')
                                     echo ' selected';
                                 ?>><?php echo esc_html__("Photo", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value='illustration'<?php
                                 if ($imgtype == 'illustration')
                                     echo ' selected';
                                 ?>><?php echo esc_html__("Illustration", 'aiomatic-automatic-ai-content-writer');?></option>
                              <option value='vector'<?php
                                 if ($imgtype == 'vector')
                                     echo ' selected';
                                 ?>><?php echo esc_html__("Vector", 'aiomatic-automatic-ai-content-writer');?></option>
                           </select>
                        </div>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Order results by a predefined rule.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Results Order: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <select name="aiomatic_Main_Settings[img_order]" class="cr_width_full">
                           <option value="popular"<?php
                              if ($img_order == "popular") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Popular", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="latest"<?php
                              if ($img_order == "latest") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Latest", 'aiomatic-automatic-ai-content-writer');?></option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Filter results by image category.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Image Category: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <select name="aiomatic_Main_Settings[img_cat]" class="cr_width_full">
                           <option value="all"<?php
                              if ($img_cat == "all") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("All", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="fashion"<?php
                              if ($img_cat == "fashion") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Fashion", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="nature"<?php
                              if ($img_cat == "nature") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Nature", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="backgrounds"<?php
                              if ($img_cat == "backgrounds") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Backgrounds", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="science"<?php
                              if ($img_cat == "science") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Science", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="education"<?php
                              if ($img_cat == "education") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Education", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="people"<?php
                              if ($img_cat == "people") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("People", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="feelings"<?php
                              if ($img_cat == "feelings") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Feelings", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="religion"<?php
                              if ($img_cat == "religion") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Religion", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="health"<?php
                              if ($img_cat == "health") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Health", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="places"<?php
                              if ($img_cat == "places") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Places", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="animals"<?php
                              if ($img_cat == "animals") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Animals", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="industry"<?php
                              if ($img_cat == "industry") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Industry", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="food"<?php
                              if ($img_cat == "food") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Food", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="computer"<?php
                              if ($img_cat == "computer") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Computer", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="sports"<?php
                              if ($img_cat == "sports") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Sports", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="transportation"<?php
                              if ($img_cat == "transportation") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Transportation", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="travel"<?php
                              if ($img_cat == "travel") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Travel", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="buildings"<?php
                              if ($img_cat == "buildings") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Buildings", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="business"<?php
                              if ($img_cat == "business") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Business", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="music"<?php
                              if ($img_cat == "music") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Music", 'aiomatic-automatic-ai-content-writer');?></option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Minimum image width.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Image Min Width: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="number" min="1" step="1" name="aiomatic_Main_Settings[img_width]" value="<?php echo esc_html($img_width);?>" placeholder="<?php echo esc_html__("Please insert image min width", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">     
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Maximum image width.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Image Max Width: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="number" min="1" step="1" name="aiomatic_Main_Settings[img_mwidth]" value="<?php echo esc_html($img_mwidth);?>" placeholder="<?php echo esc_html__("Please insert image max width", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">     
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("A flag indicating that only images suitable for all ages should be returned.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Safe Search: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="checkbox" name="aiomatic_Main_Settings[img_ss]"<?php
                           if ($img_ss == 'on') {
                               echo ' checked="checked"';
                           }
                           ?> >
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Select images that have received an Editor's Choice award.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Editor\'s Choice: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="checkbox" name="aiomatic_Main_Settings[img_editor]"<?php
                           if ($img_editor == 'on') {
                               echo ' checked="checked"';
                           }
                           ?> >
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Specify default language for regional content.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Filter Language: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <select name="aiomatic_Main_Settings[img_language]" class="cr_width_full">
                           <option value="any"<?php
                              if ($img_language == "any") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Any", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="en"<?php
                              if ($img_language == "en") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("English", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="cs"<?php
                              if ($img_language == "cs") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Czech", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="da"<?php
                              if ($img_language == "da") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Danish", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="de"<?php
                              if ($img_language == "de") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("German", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="es"<?php
                              if ($img_language == "es") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Spanish", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="fr"<?php
                              if ($img_language == "fr") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("French", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="id"<?php
                              if ($img_language == "id") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Indonesian", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="it"<?php
                              if ($img_language == "it") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Italian", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="hu"<?php
                              if ($img_language == "hu") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Hungarian", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="nl"<?php
                              if ($img_language == "nl") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Dutch", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="no"<?php
                              if ($img_language == "no") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Norvegian", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="pl"<?php
                              if ($img_language == "pl") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Polish", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="pt"<?php
                              if ($img_language == "pt") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Portuguese", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="ro"<?php
                              if ($img_language == "ro") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Romanian", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="sk"<?php
                              if ($img_language == "sk") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Slovak", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="fi"<?php
                              if ($img_language == "fi") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Finish", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="sv"<?php
                              if ($img_language == "sv") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Swedish", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="tr"<?php
                              if ($img_language == "tr") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Turkish", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="vi"<?php
                              if ($img_language == "vi") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Vietnamese", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="th"<?php
                              if ($img_language == "th") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Thai", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="bg"<?php
                              if ($img_language == "bg") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Bulgarian", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="ru"<?php
                              if ($img_language == "ru") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Russian", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="el"<?php
                              if ($img_language == "el") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Greek", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="ja"<?php
                              if ($img_language == "ja") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Japanese", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="ko"<?php
                              if ($img_language == "ko") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Korean", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="zh"<?php
                              if ($img_language == "zh") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Chinese", 'aiomatic-automatic-ai-content-writer');?></option>
                        </select>
                     </td>
                  </tr>
                 <tr>
                    <td colspan="2">
                       <hr class="cr_dotted"/>
                    </td>
                 </tr>
                 <tr>
                    <td>
                       <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                          <div class="bws_hidden_help_text cr_min_260px">
                             <?php
                                echo esc_html__("Select if you want to enable usage of the Google Images Search with the Creative Commons filter enabled, for getting images.", 'aiomatic-automatic-ai-content-writer');
                                ?>
                          </div>
                       </div>
                       <b><?php esc_html_e('Enable Google Images Search Usage: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                    </td>
                    <td>
                       <input type="checkbox" name="aiomatic_Main_Settings[google_images]"<?php
                          if ($google_images == 'on') {
                              echo ' checked="checked"';
                          }
                          ?> >
                    </td>
                 </tr>
                 <tr>
                    <td colspan="2">
                       <hr class="cr_dotted"/>
                    </td>
                 </tr>
                 <tr>
                    <td>
                       <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                          <div class="bws_hidden_help_text cr_min_260px">
                             <?php
                                echo esc_html__("Select if you want to enable usage of the Unsplash API for getting images.", 'aiomatic-automatic-ai-content-writer');
                                ?>
                          </div>
                       </div>
                       <b><?php esc_html_e('Enable Unsplash API Usage: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                    </td>
                    <td>
                       <input type="checkbox" name="aiomatic_Main_Settings[unsplash_api]"<?php
                          if ($unsplash_api == 'on') {
                              echo ' checked="checked"';
                          }
                          ?> >
                    </td>
                 </tr>
                  <tr>
                     <td colspan="2">
                        <hr class="cr_dotted"/>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Select if you want to enable direct scraping of Pixabay website. This will generate different results from the API.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Enable Pixabay Direct Website Scraping: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="checkbox" name="aiomatic_Main_Settings[pixabay_scrape]"<?php
                           if ($pixabay_scrape == 'on') {
                               echo ' checked="checked"';
                           }
                           ?> >
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Filter results by image type.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Image Types To Search: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <select name="aiomatic_Main_Settings[scrapeimgtype]" class="cr_width_full">
                           <option value="all"<?php
                              if ($scrapeimgtype == "all") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("All", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="photo"<?php
                              if ($scrapeimgtype == "photo") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Photo", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="illustration"<?php
                              if ($scrapeimgtype == "illustration") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Illustration", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="vector"<?php
                              if ($scrapeimgtype == "vector") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Vector", 'aiomatic-automatic-ai-content-writer');?></option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Filter results by image orientation.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Image Orientation: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <select name="aiomatic_Main_Settings[scrapeimg_orientation]" class="cr_width_full">
                           <option value="all"<?php
                              if ($scrapeimg_orientation == "all") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("All", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="horizontal"<?php
                              if ($scrapeimg_orientation == "horizontal") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Horizontal", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="vertical"<?php
                              if ($scrapeimg_orientation == "vertical") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Vertical", 'aiomatic-automatic-ai-content-writer');?></option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Order results by a predefined rule.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Results Order: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <select name="aiomatic_Main_Settings[scrapeimg_order]" class="cr_width_full">
                           <option value="any"<?php
                              if ($scrapeimg_order == "any") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Any", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="popular"<?php
                              if ($scrapeimg_order == "popular") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Popular", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="latest"<?php
                              if ($scrapeimg_order == "latest") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Latest", 'aiomatic-automatic-ai-content-writer');?></option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Filter results by image category.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Image Category: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <select name="aiomatic_Main_Settings[scrapeimg_cat]" class="cr_width_full">
                           <option value="all"<?php
                              if ($scrapeimg_cat == "all") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("All", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="fashion"<?php
                              if ($scrapeimg_cat == "fashion") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Fashion", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="nature"<?php
                              if ($scrapeimg_cat == "nature") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Nature", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="backgrounds"<?php
                              if ($scrapeimg_cat == "backgrounds") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Backgrounds", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="science"<?php
                              if ($scrapeimg_cat == "science") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Science", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="education"<?php
                              if ($scrapeimg_cat == "education") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Education", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="people"<?php
                              if ($scrapeimg_cat == "people") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("People", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="feelings"<?php
                              if ($scrapeimg_cat == "feelings") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Feelings", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="religion"<?php
                              if ($scrapeimg_cat == "religion") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Religion", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="health"<?php
                              if ($scrapeimg_cat == "health") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Health", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="places"<?php
                              if ($scrapeimg_cat == "places") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Places", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="animals"<?php
                              if ($scrapeimg_cat == "animals") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Animals", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="industry"<?php
                              if ($scrapeimg_cat == "industry") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Industry", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="food"<?php
                              if ($scrapeimg_cat == "food") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Food", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="computer"<?php
                              if ($scrapeimg_cat == "computer") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Computer", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="sports"<?php
                              if ($scrapeimg_cat == "sports") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Sports", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="transportation"<?php
                              if ($scrapeimg_cat == "transportation") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Transportation", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="travel"<?php
                              if ($scrapeimg_cat == "travel") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Travel", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="buildings"<?php
                              if ($scrapeimg_cat == "buildings") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Buildings", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="business"<?php
                              if ($scrapeimg_cat == "business") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Business", 'aiomatic-automatic-ai-content-writer');?></option>
                           <option value="music"<?php
                              if ($scrapeimg_cat == "music") {
                                  echo " selected";
                              }
                              ?>><?php echo esc_html__("Music", 'aiomatic-automatic-ai-content-writer');?></option>
                        </select>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Minimum image width.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Image Min Width: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="number" min="1" step="1" name="aiomatic_Main_Settings[scrapeimg_width]" value="<?php echo esc_html($scrapeimg_width);?>" placeholder="<?php echo esc_html__("Please insert image min width", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">     
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Maximum image height.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Image Min Height: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="number" min="1" step="1" name="aiomatic_Main_Settings[scrapeimg_height]" value="<?php echo esc_html($scrapeimg_height);?>" placeholder="<?php echo esc_html__("Please insert image min height", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">     
                     </td>
                  </tr>
                  <tr>
                     <td colspan="2">
                        <hr class="cr_dotted"/>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Please set a the image attribution shortcode value. You can use this value, using the %%image_attribution%% shortcode, in 'Prepend Content With' and 'Append Content With' settings fields. You can use the following shortcodes, in this settings field: %%image_source_name%%, %%image_source_website%%, %%image_source_url%%. These will be updated automatically for the respective image source, from where the imported image is from. This will replace the %%royalty_free_image_attribution%% shortcode, in 'Generated Post Content' settings field.", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Royalty Free Image Attribution Text (%%royalty_free_image_attribution%%): ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="text" name="aiomatic_Main_Settings[attr_text]" value="<?php echo esc_html(stripslashes($attr_text));?>" placeholder="<?php echo esc_html__("Please insert image attribution text pattern", 'aiomatic-automatic-ai-content-writer');?>" class="cr_width_full">     
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Do you want to enable broad search for royalty free images?", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Enable broad image search: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="checkbox" name="aiomatic_Main_Settings[bimage]" <?php
                           if ($bimage == 'on') {
                               echo 'checked="checked"';
                           }
                           ?> />
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                           <div class="bws_hidden_help_text cr_min_260px">
                              <?php
                                 echo esc_html__("Do you want to not skip importing the aritcle if no royalty free image found for the post?", 'aiomatic-automatic-ai-content-writer');
                                 ?>
                           </div>
                        </div>
                        <b><?php esc_html_e('Skip Importing of Article If No Free Image Found: ', 'aiomatic-automatic-ai-content-writer'); ?></b>
                     </td>
                     <td>
                        <input type="checkbox" name="aiomatic_Main_Settings[no_royalty_skip]" <?php
                           if ($no_royalty_skip == 'on') {
                               echo 'checked="checked"';
                           }
                           ?> />
                     </td>
                  </tr>
                  </td></tr></table>     
        </div>
        <div id="tab-9" class="tab-content">
        <table class="widefat">
                  <tr>
                     <td>
                        <h3><?php echo esc_html__("Random Sentence Generator:", 'aiomatic-automatic-ai-content-writer');?></h3>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Insert some sentences from which you want to get one at random. You can also use variables defined below. %something ==> is a variable. Each sentence must be separated by a new line.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("First List of Possible Sentences (%%random_sentence%%):", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <textarea rows="8" class="cr_width_full" cols="70" name="aiomatic_Main_Settings[sentence_list]" placeholder="<?php echo esc_html__("Please insert the first list of sentences", 'aiomatic-automatic-ai-content-writer');?>"><?php
                        echo esc_textarea($sentence_list);
                        ?></textarea>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Insert some sentences from which you want to get one at random. You can also use variables defined below. %something ==> is a variable. Each sentence must be separated by a new line.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Second List of Possible Sentences (%%random_sentence2%%):", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <textarea rows="8" cols="70" class="cr_width_full" name="aiomatic_Main_Settings[sentence_list2]" placeholder="<?php echo esc_html__("Please insert the second list of sentences", 'aiomatic-automatic-ai-content-writer');?>"><?php
                        echo esc_textarea($sentence_list2);
                        ?></textarea>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Insert some variables you wish to be exchanged for different instances of one sentence. Please format this list as follows:<br/>
                                    Variablename => Variables (seperated by semicolon)<br/>Example:<br/>adjective => clever;interesting;smart;huge;astonishing;unbelievable;nice;adorable;beautiful;elegant;fancy;glamorous;magnificent;helpful;awesome<br/>", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("List of Possible Variables:", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <textarea rows="8" cols="70" class="cr_width_full" name="aiomatic_Main_Settings[variable_list]" placeholder="<?php echo esc_html__("Please insert the list of variables", 'aiomatic-automatic-ai-content-writer');?>"><?php
                        echo esc_textarea($variable_list);
                        ?></textarea>
                     </td>
                  </tr></table>     
        </div>
        <div id="tab-10" class="tab-content">
        <table class="widefat">
                  <tr>
                     <td>
                        <h3><?php echo esc_html__("Custom HTML Code/ Ad Code:", 'aiomatic-automatic-ai-content-writer');?></h3>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Insert a custom HTML code that will replace the %%custom_html%% variable. This can be anything, even an Ad code.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Custom HTML Code #1:", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <textarea rows="3" cols="70" class="cr_width_full" name="aiomatic_Main_Settings[custom_html]" placeholder="<?php echo esc_html__("Custom HTML #1", 'aiomatic-automatic-ai-content-writer');?>"><?php
                        echo esc_textarea($custom_html);
                        ?></textarea>
                     </td>
                  </tr>
                  <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Insert a custom HTML code that will replace the %%custom_html2%% variable. This can be anything, even an Ad code.", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Custom HTML Code #2:", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <textarea rows="3" cols="70" class="cr_width_full" name="aiomatic_Main_Settings[custom_html2]" placeholder="<?php echo esc_html__("Custom HTML #2", 'aiomatic-automatic-ai-content-writer');?>"><?php
                        echo esc_textarea($custom_html2);
                        ?></textarea>
                     </td>
                  </tr>
               </table>    
        </div>
        <div id="tab-11" class="tab-content">
               <h3><?php echo esc_html__("Affiliate Keyword Replacer Tool Settings:", 'aiomatic-automatic-ai-content-writer');?></h3>
               <div class="table-responsive">
                  <table class="responsive table cr_main_table">
                     <thead>
                        <tr>
                           <th>
                              <?php echo esc_html__("ID", 'aiomatic-automatic-ai-content-writer');?>
                              <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                 <div class="bws_hidden_help_text cr_min_260px">
                                    <?php
                                       echo esc_html__("This is the ID of the rule.", 'aiomatic-automatic-ai-content-writer');
                                       ?>
                                 </div>
                              </div>
                           </th>
                           <th class="cr_max_width_40">
                              <?php echo esc_html__("Del", 'aiomatic-automatic-ai-content-writer');?>
                              <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                 <div class="bws_hidden_help_text cr_min_260px">
                                    <?php
                                       echo esc_html__("Do you want to delete this rule?", 'aiomatic-automatic-ai-content-writer');
                                       ?>
                                 </div>
                              </div>
                           </th>
                           <th>
                              <?php echo esc_html__("Search Keyword", 'aiomatic-automatic-ai-content-writer');?>
                              <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                 <div class="bws_hidden_help_text cr_min_260px">
                                    <?php
                                       echo esc_html__("This keyword will be replaced with a link you define.", 'aiomatic-automatic-ai-content-writer');
                                       ?>
                                 </div>
                              </div>
                           </th>
                           <th>
                              <?php echo esc_html__("Replacement Keyword", 'aiomatic-automatic-ai-content-writer');?>
                              <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                 <div class="bws_hidden_help_text cr_min_260px">
                                    <?php
                                       echo esc_html__("This keyword will replace the search keyword you define. Leave this field blank if you only want to add an URL to the specified keyword.", 'aiomatic-automatic-ai-content-writer');
                                       ?>
                                 </div>
                              </div>
                           </th>
                           <th>
                              <?php echo esc_html__("Link to Add", 'aiomatic-automatic-ai-content-writer');?>
                              <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                                 <div class="bws_hidden_help_text cr_min_260px">
                                    <?php
                                       echo esc_html__("Define the link you want to appear the defined keyword. Leave this field blank if you only want to replace the specified keyword without linking from it.", 'aiomatic-automatic-ai-content-writer');
                                       ?>
                                 </div>
                              </div>
                           </th>
                        </tr>
                        <tr>
                           <td>
                              <hr/>
                           </td>
                           <td>
                              <hr/>
                           </td>
                           <td>
                              <hr/>
                           </td>
                           <td>
                              <hr/>
                           </td>
                           <td>
                              <hr/>
                           </td>
                        </tr>
                     </thead>
                     <tbody>
                        <?php
                           echo aiomatic_expand_keyword_rules();
                           ?>
                        <tr>
                           <td>
                              <hr/>
                           </td>
                           <td>
                              <hr/>
                           </td>
                           <td>
                              <hr/>
                           </td>
                           <td>
                              <hr/>
                           </td>
                           <td>
                              <hr/>
                           </td>
                        </tr>
                        <tr>
                           <td class="cr_short_td">-</td>
                           <td class="cr_shrt_td2"><span class="cr_gray20">X</span></td>
                           <td class="cr_rule_line"><input type="text" name="aiomatic_keyword_list[keyword][]"  placeholder="<?php echo esc_html__("Please insert the keyword to be replaced", 'aiomatic-automatic-ai-content-writer');?>" value="" class="cr_width_100" /></td>
                           <td class="cr_rule_line"><input type="text" name="aiomatic_keyword_list[replace][]"  placeholder="<?php echo esc_html__("Please insert the keyword to replace the search keyword", 'aiomatic-automatic-ai-content-writer');?>" value="" class="cr_width_100" /></td>
                           <td class="cr_rule_line"><input type="url" validator="url" name="aiomatic_keyword_list[link][]" placeholder="<?php echo esc_html__("Please insert the link to be added to the keyword", 'aiomatic-automatic-ai-content-writer');?>" value="" class="cr_width_100" />
                        </tr>
                     </tbody>
                  </table>
               </div>
            </div>
   <hr/>
   
   <div><p class="submit"><input type="submit" name="btnSubmit" id="btnSubmit" class="button button-primary" onclick="unsaved = false;" value="<?php echo esc_html__("Save Settings", 'aiomatic-automatic-ai-content-writer');?>"/></p></div>
   </form>
</div>
<?php
   }
   if (isset($_POST['aiomatic_keyword_list'])) {
       add_action('admin_init', 'aiomatic_save_keyword_rules');
   }
   function aiomatic_save_keyword_rules($data2)
   {
       $data2 = $_POST['aiomatic_keyword_list'];
       $rules = array();
       if (isset($data2['keyword'][0])) {
           for ($i = 0; $i < sizeof($data2['keyword']); ++$i) {
               if (isset($data2['keyword'][$i]) && $data2['keyword'][$i] != '') {
                   $index         = trim(sanitize_text_field($data2['keyword'][$i]));
                   $rules[$index] = array(
                       trim(sanitize_text_field($data2['link'][$i])),
                       trim(sanitize_text_field($data2['replace'][$i]))
                   );
               }
           }
       }
       update_option('aiomatic_keyword_list', $rules);
   }
   function aiomatic_expand_keyword_rules()
   {
       $rules  = get_option('aiomatic_keyword_list');
    if(!is_array($rules))
    {
       $rules = array();
    }
       $output = '';
       $cont   = 0;
       if (!empty($rules)) {
           foreach ($rules as $request => $value) {
               $output .= '<tr>
                           <td class="cr_short_td">' . esc_html($cont) . '</td>
                           <td class="cr_shrt_td2"><span class="wpaiomatic-delete">X</span></td>
                           <td class="cr_rule_line"><input type="text" placeholder="' . esc_html__('Input the keyword to be replaced. This field is required', 'aiomatic-automatic-ai-content-writer') . '" name="aiomatic_keyword_list[keyword][]" value="' . esc_html($request) . '" required class="cr_width_100"></td>
                           <td class="cr_rule_line"><input type="text" placeholder="' . esc_html__('Input the replacement word', 'aiomatic-automatic-ai-content-writer') . '" name="aiomatic_keyword_list[replace][]" value="' . esc_html($value[1]) . '" class="cr_width_100"></td>
                           <td class="cr_rule_line"><input type="url" validator="url" placeholder="' . esc_html__('Input the URL to be added', 'aiomatic-automatic-ai-content-writer') . '" name="aiomatic_keyword_list[link][]" value="' . esc_html($value[0]) . '" class="cr_width_100"></td>
   					</tr>';
               $cont++;
           }
       }
       return $output;
   }
   ?>
<?php
if (checkloggedin()) {
    $ses_userdata = get_user_data($_SESSION['user']['username']);
    $currency = !empty($ses_userdata['currency'])?$ses_userdata['currency']:get_option('currency_code');
    $currency_data = get_currency_by_code($currency);

    $menu = ORM::for_table($config['db']['pre'] . 'menu')
        ->where(array(
            'id' => $_GET['id'],
            'user_id' => $_SESSION['user']['id']
        ))
        ->find_one();

    if(!empty($menu['id'])) {
        $menuId = $menu['id'];
        $menuName = $menu['name'];
        $menuPrice = $currency_data['html_entity'] . $menu['price'];
        $menuImage = !empty($menu['image']) ? $menu['image'] : 'default.png';

        $user_lang = !empty($_COOKIE['Quick_user_lang_code'])
            ? $_COOKIE['Quick_user_lang_code']
            : $config['lang_code'];

        /* Variant Options */
        $variant_options_data = ORM::for_table($config['db']['pre'] . 'menu_variant_options')
            ->where(array(
                'menu_id' => $menuId
            ))
            ->order_by_asc('position')
            ->find_many();

        $variant_options = $variant_options2 = array();
        foreach ($variant_options_data as $info) {
            $variant_options[$info['id']]['id'] = $info['id'];
            $json = json_decode($info['translation'], true);

            $variant_options[$info['id']]['title'] = !empty($json[$user_lang]['title'])
                                                    ? $json[$user_lang]['title']
                                                    : $info['title'];
            $variant_options[$info['id']]['options_array'] = !empty($json[$user_lang]['options'])
                                                    ? $json[$user_lang]['options']
                                                    : json_decode($info['options'], true);

            $variant_options[$info['id']]['options'] = implode(', ', $variant_options[$info['id']]['options_array']);

            $variant_options[$info['id']]['active'] = $info['active'];

            /* Create extra loop for variant form (add only if the option is active) */
            if($info['active']) {
                $variant_options2[$info['id']]['id'] = $info['id'];
                $variant_options2[$info['id']]['title'] = !empty($json[$user_lang]['title'])
                    ? $json[$user_lang]['title']
                    : $info['title'];
                $variant_options2[$info['id']]['options'] = '';
                $options = !empty($json[$user_lang]['options'])
                    ? $json[$user_lang]['options']
                    : json_decode($info['options'], true);

                $loop_options = array();
                foreach ($options as $key => $option) {
                    $loop_options[$key]['key'] = $key;
                    $loop_options[$key]['title'] = $option;
                }
                $variant_options2[$info['id']]['OPTIONS_LOOP'] = $loop_options;
            }
        }

        /* Variants */
        $variants_data = ORM::for_table($config['db']['pre'] . 'menu_variants')
            ->where(array(
                'menu_id' => $menuId
            ))
            ->order_by_asc('position')
            ->find_many();

        $variants = array();
        $variant_options3 = array();
        foreach ($variants_data as $info) {
            $variants[$info['id']]['id'] = $info['id'];
            $variants[$info['id']]['price'] = $info['price'];
            $variants[$info['id']]['active'] = $info['active'];

            $variants[$info['id']]['options'] = json_decode($info['options'], true);

            /* Create title for the variant from options */
            $titles = array();
            foreach ($variants[$info['id']]['options'] as $key => $option)
            {
                if(isset($variant_options[$key]['options_array'][$option])) {
                    $titles[] = $variant_options[$key]['options_array'][$option];
                }
            }
            $variants[$info['id']]['title'] = implode(', ', $titles);

            /* Insert variant options for nested loop */
            $variants[$info['id']]['VARIANT_LOOP'] = $variant_options2;
            foreach ($variants[$info['id']]['VARIANT_LOOP'] as $upper_key => $loop_variants)
            {

                foreach ($loop_variants['OPTIONS_LOOP'] as $key => $option)
                {
                    /* Insert 'selected' keyword for selected options */
                    if($key == $variants[$info['id']]['options'][$upper_key])
                    {
                        $variants[$info['id']]['VARIANT_LOOP'][$upper_key]['OPTIONS_LOOP'][$key]['selected'] = 'selected';
                    }
                    else
                    {
                        $variants[$info['id']]['VARIANT_LOOP'][$upper_key]['OPTIONS_LOOP'][$key]['selected'] = '';
                    }
                }
            }
        }

        /* Extras */
        $extras_data = ORM::for_table($config['db']['pre'] . 'menu_extras')
            ->where(array(
                'menu_id' => $menuId
            ))
            ->order_by_asc('position')
            ->find_many();

        $extra = array();
        foreach ($extras_data as $info) {
            $extra[$info['id']]['id'] = $info['id'];

            $user_lang = !empty($_COOKIE['Quick_user_lang_code'])
                        ? $_COOKIE['Quick_user_lang_code']
                        : $config['lang_code'];
            $json = json_decode($info['translation'], true);

            $extra[$info['id']]['title'] = !empty($json[$user_lang]['title'])
                                        ? $json[$user_lang]['title']
                                        : $info['title'];

            $extra[$info['id']]['price'] = $info['price'];
            $extra[$info['id']]['active'] = $info['active'];
        }

        $menu_lang = get_user_option($_SESSION['user']['id'],'restaurant_menu_languages','');
        $menu_lang = explode(',', $menu_lang);

        /* Languages */
        $language = array();
        if(!empty($menu_lang) && count($menu_lang) > 1) {
            $menu_languages = ORM::for_table($config['db']['pre'] . 'languages')
                ->where('active', 1)
                ->order_by_asc('name')
                ->where_in('code', $menu_lang)
                ->find_many();

            foreach ($menu_languages as $info) {
                $language[$info['id']]['code'] = $info['code'];
                $language[$info['id']]['name'] = $info['name'];
                $language[$info['id']]['file_name'] = $info['file_name'];
            }
        }

        $page = new HtmlTemplate ('templates/' . $config['tpl_name'] . '/menu-edit.tpl');
        $page->SetParameter('OVERALL_HEADER', create_header($lang['MANAGE_MENU']));
        $page->SetParameter('MENU_ID', $menuId);
        $page->SetParameter('MENU_NAME', $menuName);
        $page->SetParameter('MENU_PRICE', $menuPrice);
        $page->SetParameter('MENU_IMAGE', $menuImage);
        $page->SetParameter('SHOW_LANGS', count($language));
        $page->SetParameter('OPTIONS_COUNT', count($variant_options2));
        $page->SetLoop ('LANGS', $language);
        $page->SetLoop('VARIANT_OPTIONS', $variant_options);
        $page->SetLoop('VARIANT_OPTIONS2', $variant_options2);
        $page->SetLoop('VARIANTS', $variants);
        $page->SetLoop('EXTRAS', $extra);
        $page->SetParameter('OVERALL_FOOTER', create_footer());
        $page->CreatePageEcho();

    }else{
        // 404 page
        error($lang['PAGE_NOT_FOUND'], __LINE__, __FILE__, 1);
    }
} else {
    headerRedirect($link['LOGIN']);
}
<?php
if (checkloggedin()) {
    $ses_userdata = get_user_data($_SESSION['user']['username']);
    $currency = !empty($ses_userdata['currency']) ? $ses_userdata['currency'] : get_option('currency_code');

    if(!isset($_GET['page']))
        $page = 1;
    else
        $page = $_GET['page'];

    $limit = 25;
    $offset = ($page - 1) * $limit;
    $total_orders = 0;

    $user_lang = !empty($_COOKIE['Quick_user_lang_code'])? $_COOKIE['Quick_user_lang_code'] : $config['lang_code'];

    $orders_data = array();
    $restaurant = ORM::for_table($config['db']['pre'] . 'restaurant')
        ->where('user_id', $_SESSION['user']['id'])
        ->find_one();

    if (isset($restaurant['user_id'])) {
        /* get orders */
        $orders_query = ORM::for_table($config['db']['pre'] . 'orders')
            ->where(array(
                'restaurant_id' => $restaurant['id']
            ))
            ->where_not_equal('status','unpaid')
            ->order_by_desc('id');

        $total_orders = $orders_query->count();

        $orders = $orders_query
            ->limit($limit)
            ->offset($offset)
            ->find_many();

        foreach ($orders as $order) {
            $orders_data[$order['id']]['id'] = $order['id'];
            $orders_data[$order['id']]['type'] = $order['type'];
            $orders_data[$order['id']]['customer_name'] = escape($order['customer_name']);
            $orders_data[$order['id']]['table_number'] = escape($order['table_number']);
            $orders_data[$order['id']]['phone_number'] = $order['phone_number'];
            $orders_data[$order['id']]['address'] = escape($order['address']);
            $orders_data[$order['id']]['is_paid'] = $order['is_paid'];
            $orders_data[$order['id']]['status'] = $order['status'];
            $orders_data[$order['id']]['message'] = escape($order['message']);
            $orders_data[$order['id']]['created_at'] = date('d M Y h:i A',strtotime($order['created_at']));

            /* get order items */
            $order_items = ORM::for_table($config['db']['pre'] . 'order_items')
                ->table_alias('oi')
                ->select_many('oi.*', 'm.name', 'm.translation', 'm.price')
                ->where(array(
                    'order_id' => $order['id']
                ))
                ->join($config['db']['pre'] . 'menu', array('oi.item_id', '=', 'm.id'), 'm')
                ->order_by_desc('id')
                ->find_many();

            $orders_data[$order['id']]['items_tpl'] = $print_tpl = '';
            $price = 0;
            foreach ($order_items as $order_item) {
                $json = json_decode($order_item['translation'],true);
                $order_item['name'] = !empty($json[$user_lang]['title'])?$json[$user_lang]['title']:$order_item['name'];

                /* Menu Variants */
                $variant_title = array();
                if(is_numeric($order_item['variation'])){
                    $menu_variant = ORM::for_table($config['db']['pre'] . 'menu_variants')
                        ->where('id', $order_item['variation'])
                        ->where('menu_id', $order_item['item_id'])
                        ->find_one();

                    if(!empty($menu_variant['options'])) {
                        $order_item['price'] = $menu_variant['price'];

                        $menu_variant['options'] = json_decode($menu_variant['options'], true);

                        foreach ($menu_variant['options'] as $option_id => $option_key) {
                            $menu_variant_option = ORM::for_table($config['db']['pre'] . 'menu_variant_options')
                                ->where('id', $option_id)
                                ->find_one();

                            $json = json_decode($menu_variant_option['translation'], true);

                            $menu_variant_option['options'] = !empty($json[$user_lang]['options'])
                                ? $json[$user_lang]['options']
                                : json_decode($menu_variant_option['options'], true);

                            $variant_title[] = $menu_variant_option['options'][$option_key];
                        }
                    }
                }
                $variant_title = !empty($variant_title) ? ' <small>('.implode(', ', $variant_title).')</small>' : '';

                $tpl = '<div class="order-table-item">';
                $tpl .= '<strong><i class="icon-material-outline-restaurant"></i> '.$order_item['name'].$variant_title.'</strong>';

                if($order_item['quantity'] > 1){
                    $tpl .= ' &times; '.$order_item['quantity'];
                }
                $price += $order_item['price'] * $order_item['quantity'];

                $print_tpl_extra = $print_tpl_menu = '';

                $print_tpl_menu .= '<tr><td>'.$order_item['name'].$variant_title.' &times; '.$order_item['quantity'].'</td><td>'.price_format($order_item['price'] * $order_item['quantity'],$currency).'</td></tr>';

                /* get order extras */
                $order_item_extras = ORM::for_table($config['db']['pre'] . 'order_item_extras')
                    ->table_alias('oie')
                    ->select_many('oie.*', 'me.title', 'me.translation', 'me.price')
                    ->where(array(
                        'order_item_id' => $order_item['id']
                    ))
                    ->join($config['db']['pre'] . 'menu_extras', array('oie.extra_id', '=', 'me.id'), 'me')
                    ->order_by_desc('id')
                    ->find_many();

                if($order_item_extras->count()) {

                    $tpl .= '<div class="padding-left-10">';
                    foreach ($order_item_extras as $order_item_extra) {
                        $json = json_decode($order_item_extra['translation'],true);
                        $order_item_extra['title'] = !empty($json[$user_lang]['title'])?$json[$user_lang]['title']:$order_item_extra['title'];

                        $price += $order_item_extra['price'] * $order_item['quantity'];
                        $tpl .= '<div><i class="icon-feather-plus"></i> ' . $order_item_extra['title'].'</div>';
                        $print_tpl_extra .= '<tr class="order-menu-extra"><td><span class="margin-left-5">'.$order_item_extra['title'].'</span></td><td>'.price_format($order_item_extra['price'] * $order_item['quantity'],$currency).'</td></tr>';
                    }
                    $tpl .= '</div>';
                }
                $tpl .= '</div>';
                $orders_data[$order['id']]['items_tpl'] .= $tpl;
                $print_tpl .= $print_tpl_menu . $print_tpl_extra;
            }

            $delivery_charge = 0;

            if($orders_data[$order['id']]['type'] == 'on-table')
                $type = $orders_data[$order['id']]['table_number'];
            elseif($orders_data[$order['id']]['type'] == 'takeaway')
                $type = $lang['TAKEAWAY'];
            elseif($orders_data[$order['id']]['type'] == 'delivery'){
                $type = $lang['DELIVERY'];
                $delivery_charge = get_restaurant_option($restaurant['id'],'restaurant_delivery_charge',0);
            }

            $orders_data[$order['id']]['price'] = price_format($price + $delivery_charge,$currency);

            if($delivery_charge){
                $print_tpl .= '<tr><td>'.$lang['DELIVERY_CHARGE'].'</td><td>'.price_format($delivery_charge,$currency).'</td></tr>';
            }

            $order_print_tpl = "<table>
                            <tr>
                                <td>{$lang['TIME']}</td>
                                <td>{$orders_data[$order['id']]['created_at']}</td>
                            </tr>
                            <tr>
                                <td>{$lang['NAME']}</td>
                                <td>{$orders_data[$order['id']]['customer_name']}</td>
                            </tr>
                            <tr>
                                <td>{$lang['TABLE_NO_ORDER_TYPE']}</td>
                                <td>$type</td>
                            </tr>".
                (!empty($orders_data[$order['id']]['phone_number'])?"<tr>
                                <td>{$lang['PHONE']}</td>
                                <td>{$orders_data[$order['id']]['phone_number']}</td>
                            </tr>":'')
                .
                (!empty($orders_data[$order['id']]['address'])?"<tr>
                                <td>{$lang['ADDRESS']}</td>
                                <td>{$orders_data[$order['id']]['address']}</td>
                            </tr>":'').
                (!empty($orders_data[$order['id']]['message'])?"<tr>
                                <td>{$lang['MESSAGE']}</td>
                                <td>{$orders_data[$order['id']]['message']}</td>
                            </tr>":'').
                (!empty($orders_data[$order['id']]['is_paid'])?"<tr>
                                <td>{$lang['PAYMENT']}</td>
                                <td>{$lang['PAID']}</td>
                            </tr>":'')."
                        </table>
                        <div class='order-print-divider'></div>
                        <table class='order-print-menu'>
                            <thead>
                            <tr>
                                <th>{$lang['MENU']}</th>
                                <th>{$lang['PRICE']}</th>
                            </tr>
                            </thead>
                            <tbody id='order-print-menu'>$print_tpl</tbody>
                            <tfoot>
                            <tr>
                                <th>{$lang['TOTAL']}</th>
                                <td>{$orders_data[$order['id']]['price']}</td>
                            </tr>
                            </tfoot>
                        </table>";

            $orders_data[$order['id']]['order_print_tpl'] = $order_print_tpl;

            $orders = ORM::for_table($config['db']['pre'] . 'orders')->find_one($order['id']);
            $orders->seen = 1;
            $orders->save();
        }
    }

    // delete unpaid old orders
    $orders_query = ORM::for_table($config['db']['pre'] . 'orders')
        ->where(array(
            'restaurant_id' => $restaurant['id'],
            'status' => 'unpaid'
        ))
        ->where_raw('created_at <= DATE_SUB(NOW(), INTERVAL 30 MINUTE)');

    foreach ($orders_query->find_many() as $o) {
        ORM::for_table($config['db']['pre'] . 'order_items')
            ->where(array(
                'order_id' => $o['id']
            ))
            ->delete_many();
    }

    $orders_query->delete_many();

    $paging = pagenav($total_orders, $page, $limit, $link['ORDER']);

    $menu_lang = get_user_option($_SESSION['user']['id'],'restaurant_menu_languages','');
    $menu_lang = explode(',', $menu_lang);

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

    $page = new HtmlTemplate ('templates/' . $config['tpl_name'] . '/orders.tpl');
    $page->SetParameter('OVERALL_HEADER', create_header($lang['ORDERS']));
    $page->SetParameter('ORDERS_FOUND', (int)(count($orders_data) > 0));
    $page->SetParameter('RESTAURANT_NAME', $restaurant['name']);
    $page->SetParameter('RESTAURANT_ADDRESS', $restaurant['address']);
    $page->SetLoop('ORDERS', $orders_data);
    $page->SetLoop('PAGES', $paging);
    $page->SetParameter('SHOW_PAGING', (int)($total_orders > $limit));
    $page->SetParameter('SHOW_LANGS', count($language));
    $page->SetLoop ('LANGS', $language);
    $page->SetParameter('OVERALL_FOOTER', create_footer());
    $page->CreatePageEcho();
} else {
    headerRedirect($link['LOGIN']);
}
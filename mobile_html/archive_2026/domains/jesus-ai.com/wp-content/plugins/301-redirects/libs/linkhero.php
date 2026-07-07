<?php
/*
 * 301 Redirects Pro
 * Utility & Helper functions
 * (c) WebFactory Ltd, 2019 - 2021, www.webfactoryltd.com
 */

class LinkHero
{
    /**
     * Initialize plugin
     * @param string $plugin_file
     * @param string $plugin_dir
     */
    public $linkhero;
    public $linkhero_api = 'http://api-beta.linkhero.com';
        
    function init()
    {
        global $wpdb, $wf_301_licensing;
        $license = $wf_301_licensing->get_license();
        if(is_array($license) && is_array($license['meta']) && array_key_exists('disable_link_scanner', $license['meta']) && $license['meta']['disable_link_scanner'] == true){
            return;
        }

        add_action('wp_ajax_linkhero_run_tool', array($this, 'ajax_tool'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        add_action('linkhero_cron', array($this, 'do_cron'));

        add_filter('cron_schedules', function ($schedules) {
            $options = WF301_setup::get_options();
            if ($options['scheduled_scans'] == 'off') {
                return $schedules;
            }
            switch ($options['scheduled_scans']) {
                case '2days':
                    $schedules['linkhero_cron_schedule'] = array(
                        'interval' => 60 * 60 * 24 * 2,
                        'display'  => esc_html__('Every 2 days'),
                    );
                    break;
                case '3days':
                    $schedules['linkhero_cron_schedule'] = array(
                        'interval' => 60 * 60 * 24 * 3,
                        'display'  => esc_html__('Every 3 days'),
                    );
                    break;
                case '1week':
                    $schedules['linkhero_cron_schedule'] = array(
                        'interval' => 60 * 60 * 24 * 7,
                        'display'  => esc_html__('Every week'),
                    );
                    break;
                case '2week':
                    $schedules['linkhero_cron_schedule'] = array(
                        'interval' => 60 * 60 * 24 * 14,
                        'display'  => esc_html__('Every 2 weeks'),
                    );
                    break;
            }

            return $schedules;
        });

        if (!wp_next_scheduled('linkhero_cron')) {
            wp_schedule_event(time(), 'linkhero_cron_schedule', 'linkhero_cron');
        }



        $this->linkhero = get_option('wf301-linkhero', array('checker' => array(), 'enabled' => false));
        $wpdb->wf301_linkhero = $wpdb->prefix . 'wf301_linkhero';
    }

    function do_cron()
    {
        global $wpdb;
        $this->linkhero = array('checker' => array(), 'enabled' => true);
        $wpdb->query('TRUNCATE TABLE ' . $wpdb->wf301_linkhero);
        delete_option('wf301-linkhero-subscribed');
        update_option('wf301-linkhero', $this->linkhero);
        $res = $this->lh_request_scan();
        
        if (is_wp_error($res)) {
            $error = $res->get_error_message();
            if (strpos($error, 'cURL error') !== false) {
                //error_log('LinkHero is briefly unavailable for maintenance. We applogize for any inconvenience. Please try again later.');
            }
            //error_log($res->get_error_message());
        } else {
            //error_log('Scan started by cron');
        }
    }

    static function is_localhost()
    {
        $whitelist = array('127.0.0.1', '::1');
        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            return true;
        }
        return false;
    }

    function admin_enqueue_scripts()
    {
        if (!WF301_admin::is_plugin_page()) {
            return;
        }

        $linkhero = get_option('wf301-linkhero', array('checker' => array(), 'enabled' => false));
        if (!isset($linkhero['enabled'])) {
            $linkhero['enabled'] = false;
        }

        $linkhero_js = array(
            'nonce_ajax' => wp_create_nonce('linkhero_run_tool'),
            'loader' => admin_url('/images/spinner.gif'),
            'link_checking_enabled' => false
        );

        if (!LinkHero::is_localhost()) {
            $linkhero_js['link_checking_enabled'] = $linkhero['enabled'];
        }

        wp_enqueue_style(
            '301-linkhero',
            plugins_url('/css/301-linkhero.css', WF301_PLUGIN_FILE),
            array(),
            WF301::get_plugin_version()
        );

        wp_enqueue_script(
            '301-linkhero',
            plugins_url('/js/301-linkhero.js', WF301_PLUGIN_FILE),
            array('jquery'),
            WF301::get_plugin_version(),
            true
        );

        wp_localize_script('301-linkhero', 'linkhero', $linkhero_js);
    }

    function frontend_enqueue_scripts()
    {
        wp_register_script(
            'linkhero-highlighter',
            plugins_url('/js/301-linkhero-highlighter.js', WF301_PLUGIN_FILE),
            array('jquery'),
            WF301::get_plugin_version(),
            true
        );

        if (isset($_GET['linkhero-link-highlight'])) {
            wp_enqueue_script('linkhero-highlighter');
        }
    }

    function get_sitemap_url()
    {
        $sitemap_url = home_url('/sitemap.xml');
        $sitemap_test_response = wp_remote_head($sitemap_url, array('sslverify' => false));
        if(is_wp_error($sitemap_test_response)){
            return false;
        }
        
        if($sitemap_test_response['response']['code'] == 200){
            return $sitemap_url;
        } else if($sitemap_test_response['response']['code'] >= 300 && $sitemap_test_response['response']['code'] < 400){
            return $sitemap_test_response['headers']['location'];
        } 

        return false;
    }

    function lh_request_scan()
    {
        global $wpdb, $wf_301_licensing;
        $license = $wf_301_licensing->get_license();

        $sitemap_url = $this->get_sitemap_url();

        if ($sitemap_url !== false) {
            $request = array(
                'sitemap' => $sitemap_url
            );

            $request = array();
            $request['license_key'] = $license['license_key'];
            $request['site_url'] = get_home_url();
            $request['version'] = WF301::get_plugin_version();
            $request['wp_version'] = get_bloginfo('version');
            $request['sitemap'] = $sitemap_url;

            $res = wp_remote_post(
                $this->linkhero_api . '/linkhero_wp301/add/check_urls',
                array(
                    'sslverify' => false,
                    'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
                    'data_format' => 'body',
                    'body' => json_encode($request),
                    'timeout' => 15
                )
            );

            if (!is_wp_error($res)) {
                $res = wp_remote_retrieve_body($res);
                $res = json_decode($res);

                //TODO: Returns as object
                if (!empty($res->success) && $res->success == true && isset($res->result->job_id)) {
                    $this->linkhero['checker']['lastscan'] = time();
                    $this->linkhero['checker']['status'] = 'pending';
                    $this->linkhero['checker']['limit'] = 0;
                    $this->linkhero['checker']['total_pages'] = 0;
                    $this->linkhero['checker']['job_id'] = $res->result->job_id;

                    update_option('wf301-linkhero', $this->linkhero);
                    return true;
                } else {
                    //TODO: Handle error
                    return new WP_Error(1, 'An error occured: ' . $res->result);
                }
            } else {
                //TODO: Handle error
                error_log($res->get_error_message());
                return $res;
            }
        } else {
            return new WP_Error(1, 'Could not locate your website sitemap at ' . home_url('/sitemap.xml') . ' or ' . home_url('/wp-sitemap.xml') . '');
        }
    }

    function get_url_data($url) {
        $cookie_file = tmpfile();
        $parts = parse_url($url);
        $host = $parts['host'];
        $ch = curl_init();
        $header = array('GET /1575051 HTTP/1.1',
            "Host: {$host}",
            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language:en-US,en;q=0.8',
            'Cache-Control:max-age=0',
            'Connection:keep-alive',
            'Host:adfoc.us',
            'User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.116 Safari/537.36',
        );
    
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $result = curl_exec($ch);
        curl_close($ch);
        fclose($cookie_file);
        return $result;
    }

    function lh_get_results()
    {
        global $wpdb, $wf_301_licensing;
        $license = $wf_301_licensing->get_license();

        $request = array();
        $request['license_key'] = $license['license_key'];
        $request['site_url'] = get_home_url();
        $request['version'] = WF301::get_plugin_version();
        $request['wp_version'] = get_bloginfo('version');
        $request['job_id'] = $this->linkhero['checker']['job_id'];

        $upload_dir = wp_upload_dir();
        $results_zip = $upload_dir['basedir'] . '/wp301_lh_results.zip';
        $results_file = $upload_dir['basedir'] . '/wp301_lh_results.json';

        file_put_contents($results_zip, $this->get_url_data($this->linkhero_api . '/linkhero_wp301/get/check_urls?' . http_build_query($request)));
        if (!(file_exists($results_zip) && filesize($results_zip) > 0)) {
            return new WP_Error(1, 'LinkHero is briefly unavailable for maintenance. We applogize for any inconvenience. Please try again in a few minutes.');
        }

        if (true !== WP_Filesystem()) {
            return new WP_Error(1, 'Could not access WP_Filesystem');
        }

        if (is_wp_error($unzip_result = unzip_file($results_zip, $upload_dir['basedir']))) {
            return $unzip_result;
        }

        $res = file_get_contents($results_file);
        unlink($results_zip);
        unlink($results_file);

        $this->linkhero['checker']['total_pages'] = 0;
        $this->linkhero['checker']['total_links'] = 0;
        $this->linkhero['checker']['total_finished'] = 0;
        $this->linkhero['checker']['total_error'] = 0;
        $this->linkhero['checker']['total_pending'] = 0;

        if (!is_wp_error($res)) {
            $res = json_decode($res, true);
            if (!empty($res['success']) && $res['success'] == true) {
                $this->linkhero['checker']['status'] = array_key_exists('status', $res) && ($res['status'] == 'finished' ||  $res['status'] == 'sitemap_error')? $res['status'] : 'pending';
                $this->linkhero['checker']['limit'] = array_key_exists('limit', $res) && (int)$res['limit'] > 0 ? (int)$res['limit'] : 0;
                $this->linkhero['checker']['total_pages'] = array_key_exists('total_pages', $res) && (int)$res['total_pages'] > 0 ? (int)$res['total_pages'] : 0;
                $this->linkhero['checker']['total_links'] = array_key_exists('total_links', $res) && (int)$res['total_links'] > 0 ? (int)$res['total_links'] : 0;
                $this->linkhero['checker']['total_finished'] = array_key_exists('total_finished', $res) && (int)$res['total_finished'] > 0 ? (int)$res['total_finished'] : 0;
                $this->linkhero['checker']['total_pending'] = array_key_exists('total_pending', $res) && (int)$res['total_pending'] > 0 ? (int)$res['total_pending'] : 0;
                $this->linkhero['checker']['total_error'] = array_key_exists('total_error', $res) && (int)$res['total_error'] > 0 ? (int)$res['total_error'] : 0;

                $pending_pages = false;
                $page_db_ids = array();
                $get_page_db_ids = $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT `id`, `href` FROM ' . $wpdb->wf301_linkhero . ' WHERE job_id = %s AND parent = 0',
                        array($this->linkhero['checker']['job_id'])
                    )
                );

                $total_errors = 0;

                foreach ($get_page_db_ids as $pid => $page_db_data) {
                    $page_db_ids[$page_db_data->href] = $page_db_data->id;
                }

                foreach ($res['result'] as $page_url => $page) {
                    if (!is_array($page) || $page['status'] != 'finished' || !array_key_exists('hrefs', $page) || !is_array($page['hrefs'])) {
                        $pending_pages = true;
                        $page['status'] == 'error';
                    }

                    if (isset($page['error']) && strlen($page['error']) > 0) {
                        $page['status'] == 'error';
                    }

                    $page_links = 0;
                    $page_errors = 0;
                    $db_status = isset($page['status']) ? $page['status'] : 'error';
                    $db_title = isset($page['title']) ? $page['title'] : '';
                    $db_load = isset($page['load']) ? $page['load'] : '';
                    $db_redirects = isset($page['redirects']) ? serialize($page['redirects']) : '';
                    $db_webrisk = isset($page['webrisk']) ? $page['webrisk'] : '';
                    $db_alexa = isset($page['alexa']) ? serialize($page['alexa']) : '';
                    $db_malware = isset($page['malware']) ? $page['malware'] : '';
                    $db_adult = isset($page['adult']) ? serialize($page['adult']) : '';

                    $add_page = $wpdb->query(
                        $wpdb->prepare(
                            'INSERT IGNORE INTO ' . $wpdb->wf301_linkhero . '(`job_id`,`type`,`parent`,`status`,`title`,`load`,`rel`,`target`,`href`,`href_text`,`redirects`,`status_code`,`webrisk`,`alexa`,`malware`,`adult`)
                            VALUES(%s,%s,%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s) 
                            ON DUPLICATE KEY UPDATE `status`=%s,`title`=%s,`load`=%s,`rel`=%s,`target`=%s,`href`=%s,`href_text`=%s,`redirects`=%s,`status_code`=%s,`webrisk`=%s,`alexa`=%s,`malware`=%s,`adult`=%s',
                            array(
                                $this->linkhero['checker']['job_id'], 'page', 0, $db_status, $db_title, $db_load, '', '', $page_url, '', $db_redirects, '', $db_webrisk, $db_alexa, $db_malware, $db_adult,
                                $db_status, $db_title, $db_load, '', '', $page_url, '', $db_redirects, '', $db_webrisk, $db_alexa, $db_malware, $db_adult
                            )
                        )
                    );

                    if ($add_page) {
                        $page_db_ids[$page_url] = $wpdb->insert_id;
                    }

                    if (!($page_db_ids[$page_url] > 0)) {
                        continue;
                    }

                    if (array_key_exists('hrefs', $page)) foreach ($page['hrefs'] as $href_url => $href) {
                        $page_links++;
                        if ($href['scrape_status'] == 'error' || (array_key_exists('status', $href) && ($href['status'] == 404 || $href['status'] > 499))) {
                            $page_errors++;
                            $total_errors++;
                        }
                        if (!array_key_exists('scrape_status', $href) ||  ($href['scrape_status'] != 'finished' && $href['scrape_status'] != 'error')) {
                            $res['result'][$page_url]['status'] = 'pending';
                            $pending_pages = true;
                        }



                        $db_scrape_status = isset($href['scrape_status']) ? $href['scrape_status'] : 'pending';
                        $db_title = isset($href['title']) ? $href['title'] : '';
                        $db_load = isset($href['load']) ? $href['load'] : '';
                        $db_redirects = isset($href['redirects']) ? serialize($href['redirects']) : '';
                        $db_text = isset($href['text']) ? $href['text'] : '';

                        $db_rel = isset($href['rel']) ? $href['rel'] : '';
                        $db_target = isset($href['target']) ? $href['target'] : '';

                        $db_status = isset($href['status']) ? $href['status'] : 0;

                        $db_webrisk = isset($href['webrisk']) ? $href['webrisk'] : '';
                        $db_alexa = isset($href['alexa']) ? serialize($href['alexa']) : '';
                        $db_malware = isset($href['malware']) ? $href['malware'] : '';
                        $db_adult = isset($href['adult']) ? serialize($href['adult']) : '';

                        $add_href = $wpdb->query(
                            $wpdb->prepare(
                                'INSERT IGNORE INTO ' . $wpdb->wf301_linkhero . '(`job_id`,`type`,`parent`,`status`,`title`,`load`,`rel`,`target`,`href`,`href_text`,`redirects`,`status_code`,`webrisk`,`alexa`,`malware`,`adult`)
                                VALUES(%s,%s,%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s) 
                                ON DUPLICATE KEY UPDATE `status`=%s,`title`=%s,`load`=%s,`rel`=%s,`target`=%s,`href`=%s,`href_text`=%s,`redirects`=%s,`status_code`=%s,`webrisk`=%s,`alexa`=%s,`malware`=%s,`adult`=%s',
                                array(
                                    $this->linkhero['checker']['job_id'], 'link', $page_db_ids[$page_url], $db_scrape_status, $db_title, $db_load, $db_rel, $db_target, $href_url, $db_text, $db_redirects, $db_status, $db_webrisk, $db_alexa, $db_malware, $db_adult,
                                    $db_scrape_status, $db_title, $db_load, $db_rel, $db_target, $href_url, $db_text, $db_redirects, $db_status, $db_webrisk, $db_alexa, $db_malware, $db_adult
                                )
                            )
                        );
                    }

                    $wpdb->query(
                        $wpdb->prepare(
                            'UPDATE ' . $wpdb->wf301_linkhero . ' SET links = %d, errors = %d WHERE id=%d',
                            array($page_links, $page_errors, $page_db_ids[$page_url])
                        )
                    );
                }
                $this->linkhero['checker']['total_error'] = $total_errors;
                update_option('wf301-linkhero', $this->linkhero);
            } else {
                //TODO: Handle error
                wp_send_json_error('An error occured: ' . $res['result']);
                return new WP_Error(1, 'An error occured: ' . serialize($res));
            }
        } else {
            //TODO: Handle error
            error_log('Error getting results');
            return $res;
        }
    }

    function check_url_redirect($url)
    {
        $request = array(
            'url' => $url
        );


        $res = wp_remote_post(
            $this->linkhero_api . '/linkhero_wp301/check',
            array(
                'sslverify' => false,
                'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
                'data_format' => 'body',
                'body' => json_encode($request),
                'timeout' => 15
            )
        );

        if (!is_wp_error($res)) {
            $res = wp_remote_retrieve_body($res);
            return json_decode($res, true);
        } else {
            return false;
        }
    }

    function ajax_tool()
    {
        global $wpdb;
        switch ($_REQUEST['tool']) {
            case 'check_links':
                $this->linkhero['enabled'] = true;

                if (isset($_REQUEST['force']) && $_REQUEST['force'] == 'true') {
                    $this->linkhero = array('checker' => array(), 'enabled' => false);
                    $wpdb->query('TRUNCATE TABLE ' . $wpdb->wf301_linkhero);
                    delete_option('wf301-linkhero-subscribed');
                    update_option('wf301-linkhero', $this->linkhero);
                    wp_send_json_success();
                }

                if (empty($this->linkhero['checker']) || $this->linkhero['checker'] == false) {
                    $res = $this->lh_request_scan();
                    if (is_wp_error($res)) {
                        $error = $res->get_error_message();
                        if (strpos($error, 'cURL error') !== false) {
                            wp_send_json_error('LinkHero is briefly unavailable for maintenance. We applogize for any inconvenience. Please try again later.');
                        }
                        wp_send_json_error($res->get_error_message());
                    } else {
                        wp_send_json_success(array('status' => 'pending'));
                    }
                } else {
                    //Refresh
                    if (!isset($this->linkhero['checker']['job_id']) || empty($this->linkhero['checker']['job_id'])) {
                        wp_send_json_error('No job ID exists');
                    }

                    if ($this->linkhero['checker']['status'] == 'pending' && !isset($_REQUEST['display'])) {
                        $get_results = $this->lh_get_results();
                        if (is_wp_error($get_results)) {
                            wp_send_json_error($get_results->get_error_message());
                        }
                    }


                    $results = array();
                    $current_page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
                    $per_page = isset($_REQUEST['per_page']) ? (int)$_REQUEST['per_page'] : 10;
                    $page_order = 'title';
                    $page_order_dir = 'ASC';
                    $page_order = isset($_REQUEST['order']) && ($_REQUEST['order'] == 'errors' || $_REQUEST['order'] == 'links' || $_REQUEST['order'] == 'title') ? $_REQUEST['order'] : 'errors';
                    if ($page_order == 'links' || $page_order == 'errors') {
                        $page_order_dir = 'DESC';
                    }
                    // if finished prepare results table
                    if ($this->linkhero['checker']['status'] == 'finished') {


                        $get_pages = $wpdb->get_results(
                            $wpdb->prepare(
                                'SELECT * FROM ' . $wpdb->wf301_linkhero . ' WHERE job_id=%s AND parent=0 ORDER BY `' . $page_order . '` ' .  $page_order_dir . ' LIMIT ' . (($current_page - 1) * $per_page) . ',' . $per_page,
                                array($this->linkhero['checker']['job_id'])
                            )
                        );

                        $all_results = array();
                        foreach ($get_pages as $result) {
                            $all_results[$result->id] = $result;
                            $all_results[$result->id]->links = array();
                        }

                        if (empty($all_results) || !is_array($all_results) || count($all_results) == 0) {
                            wp_send_json_error('An error occured creating results tables.');
                        }

                        $get_links = $wpdb->get_results(
                            $wpdb->prepare(
                                'SELECT * FROM ' . $wpdb->wf301_linkhero . ' WHERE job_id=%s AND parent IN(' . implode(',', array_keys($all_results)) . ')',
                                array($this->linkhero['checker']['job_id'])
                            )
                        );

                        foreach ($get_links as $result) {
                            $all_results[$result->parent]->links[$result->href] = (array)$result;
                        }

                        foreach ($all_results as $page_id => $page) {
                            $page = (array)$page;
                            $page_id = 'p' . strval($page_id);
                            $results[$page_id] = $page;

                            $results[$page_id]['links_total'] = 0;
                            $results[$page_id]['links_finished'] = 0;
                            $results[$page_id]['links_error'] = 0;

                            if (array_key_exists('links', $page) && is_array($page['links'])) {
                                $results[$page_id]['links_total'] = count($page['links']);
                                $results[$page_id]['links_finished'] = 0;
                                $results[$page_id]['links_error'] = 0;
                                foreach ($page['links'] as $link_url => $url_result) {
                                    if (array_key_exists('status', $url_result) && ($url_result['status'] == 'error' || $url_result['status_code'] == 404 || $url_result['status_code'] > 499)) {
                                        $results[$page_id]['links_error']++;
                                    } else if (array_key_exists('status', $url_result) && $url_result['status'] == 'finished') {
                                        $results[$page_id]['links_finished']++;
                                    }
                                }
                            }
                        }
                    } // finished - prepare results
                    wp_send_json_success(
                        array(
                            'status' => $this->linkhero['checker']['status'],
                            'pages' => $results,
                            'limit' => $this->linkhero['checker']['limit'] > 0 ? $this->linkhero['checker']['limit'] : 0,
                            'total_pages' => $this->linkhero['checker']['total_pages'] > 0 ? $this->linkhero['checker']['total_pages'] : 0,
                            'total_links' => $this->linkhero['checker']['total_links'],
                            'total_finished' => $this->linkhero['checker']['total_finished'],
                            'total_error' => $this->linkhero['checker']['total_error'],
                            'total_pending' => $this->linkhero['checker']['total_pending'],
                            'current_page' => $current_page
                        )
                    );
                }
                break;
            case 'link_details':
                $page = str_replace('p', '', $_REQUEST['page']);
                $results = array();
                $page_url = $wpdb->get_var(
                    $wpdb->prepare(
                        'SELECT href FROM ' . $wpdb->wf301_linkhero . ' WHERE job_id=%s AND `id`=%d',
                        array($this->linkhero['checker']['job_id'], $page)
                    )
                );

                $get_all_results = $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT * FROM ' . $wpdb->wf301_linkhero . ' WHERE job_id=%s AND `parent`=%d',
                        array($this->linkhero['checker']['job_id'], $page)
                    )
                );

                $results = array();

                foreach ($get_all_results as $details) {
                    $details = (array)$details;
                    $redirects = '';
                    $details['redirects'] = unserialize($details['redirects']);
                    if (array_key_exists('redirects', $details) && is_array($details['redirects']) && count($details['redirects']) > 0) {
                        foreach ($details['redirects'] as $redirect) {
                            $redirects .= '<span class="lh-redirect"><span>' . $redirect['status'] . '</span>' . $redirect['url'] . '</span>';
                        }
                    } else {
                        $redirects = '<i>not redirected</i>';
                    }

                    if (array_key_exists('target', $details) && strlen($details['target']) > 3) {
                        $target = $details['target'];
                    } else {
                        $target = '_self';
                    }

                    $domain_info = '<div class="linkhero-domain-info">';


                    $google_web_risk = '<i class="fas fa-check"></i> not listed';
                    $lh_malware = '<i class="fas fa-check"></i> not listed';
                    $lh_adult = '<i class="fas fa-check"></i> not listed';
                    $awis_adult_content = '<i class="fas fa-check"></i>';
                    $awis_inbound = '<i class="fas fa-question"></i>';
                    $awis_rank = '<i class="fas fa-question"></i>';
                    $awis_language = '<i class="fas fa-question"></i>';

                    //Google Web Risk
                    if (isset($details['webrisk']) && !empty($details['webrisk'])) {
                        $google_web_risk = '<i class="fas fa-times"></i>' . $details['webrisk'];
                    }

                    //LinkHero Malware
                    if (isset($details['malware']) && !empty($details['malware'])) {
                        $lh_malware = '<i class="fas fa-times"></i> Yes';
                    }

                    //LinkHero Adult
                    if (isset($details['adult']) && !empty($details['adult'])) {
                        $lh_adult = '<i class="fas fa-times"></i> Yes';
                    }

                    //AWIS
                    $domain_info .= '<br />';
                    $domain_info .= '<div class="domain-info-provider">Alexa Web Info:</div>';

                    if (!empty($details['alexa'])) {
                        $alexa_info = unserialize($details['alexa']);
                        if (array_key_exists("adult", $alexa_info) && !empty($alexa_info['adult'])) {
                            $awis_adult_content = '<i class="fas fa-times"></i> yes';
                        } else {
                            $awis_adult_content = '<i class="fas fa-check"></i> no';
                        }

                        if (array_key_exists("linksin", $alexa_info) && !empty($alexa_info['linksin'])) {
                            $awis_inbound = number_format($alexa_info['linksin']);
                        }

                        if (array_key_exists("rank", $alexa_info) && !empty($alexa_info['rank'])) {
                            $awis_rank = number_format($alexa_info['rank']);
                        }

                        if (array_key_exists("language", $alexa_info) && !empty($alexa_info['language'])) {
                            $awis_language = $this->language_code_to_name($alexa_info['language']['Locale']);
                        }
                    }

                    $rel = '';
                    if (isset($details['rel']) && strlen($details['rel']) > 0) {
                        $rel_arr = explode(' ', $details['rel']);
                        foreach ($rel_arr as $rel_tag) {
                            $rel .= '<span class="wf301-tag">' . $rel_tag . '</span>';
                        }
                    }

                    if (empty($rel)) {
                        $rel = '<i>none</i>';
                    }

                    $domain_info .= '</div>';

                    $domain_ok = true;
                    $domain_error = '';
                    if ($details['status'] == 'error' || $details['status_code'] == 404 || $details['status_code'] > 499) {
                        $domain_ok = false;
                        if ($details['status'] == 'error') {
                            if (array_key_exists('error', $details) && !empty($details['error'])) {
                                $domain_error = $details['error'];
                            } else {
                                $domain_error = 'Not found';
                            }
                        } else {
                            $domain_error = 'Status code: ' . $details['status_code'];
                        }
                    }

                    $results[] = [
                        (!$domain_ok ? '<span title="' . $domain_error . '" class="linkhero_bad dashicons dashicons-editor-unlink"></span><span style="display:none">0</span>' : '<span class="linkhero_good dashicons dashicons-admin-links"></span><span style="display:none">1</span>'),
                        '<div class="dt-lh-title">' .
                            $details['title'] . '<br />' .
                            '<a href="' . $details['href'] . '" target="_blank">' . $details['href'] . '</a>' .
                            '<a target="_blank" class="linkhero-link-locator" href="' . $page_url . '?linkhero-link-highlight=' . urlencode($details['href']) . '" title="Locate link on page"><span class="dashicons dashicons-pressthis"></span></a><br />' .
                            '</div>',
                        isset($details['title']) ? $details['title'] : '',
                        $target . '<br />' . $rel,
                        $redirects,
                        $domain_ok ? $google_web_risk : '',
                        $domain_ok ? $awis_adult_content : '',
                        $domain_ok ? $awis_inbound : '',
                        $domain_ok ? $awis_rank : '',
                        $domain_ok ? $awis_language : '',
                        $domain_ok ? $lh_malware : '',
                        $domain_ok ? $lh_adult : ''
                    ];
                }

                echo json_encode(['data' => $results]);
                break;
            case 'verify_link':
                $result = $this->check_url_redirect($_REQUEST['url']);
                wp_send_json_success($result);
                break;
            case 'subscribed':
                update_option('wf301-linkhero-subscribed', true);
                break;
            default:
                wp_send_json_error('Unknown action');
                break;
        }
        die();
    }

    function language_code_to_name($lang)
    {
        $languageCodes = array(
            "aa" => "Afar",
            "ab" => "Abkhazian",
            "ae" => "Avestan",
            "af" => "Afrikaans",
            "ak" => "Akan",
            "am" => "Amharic",
            "an" => "Aragonese",
            "ar" => "Arabic",
            "as" => "Assamese",
            "av" => "Avaric",
            "ay" => "Aymara",
            "az" => "Azerbaijani",
            "ba" => "Bashkir",
            "be" => "Belarusian",
            "bg" => "Bulgarian",
            "bh" => "Bihari",
            "bi" => "Bislama",
            "bm" => "Bambara",
            "bn" => "Bengali",
            "bo" => "Tibetan",
            "br" => "Breton",
            "bs" => "Bosnian",
            "ca" => "Catalan",
            "ce" => "Chechen",
            "ch" => "Chamorro",
            "co" => "Corsican",
            "cr" => "Cree",
            "cs" => "Czech",
            "cu" => "Church Slavic",
            "cv" => "Chuvash",
            "cy" => "Welsh",
            "da" => "Danish",
            "de" => "German",
            "dv" => "Divehi",
            "dz" => "Dzongkha",
            "ee" => "Ewe",
            "el" => "Greek",
            "en" => "English",
            "eo" => "Esperanto",
            "es" => "Spanish",
            "et" => "Estonian",
            "eu" => "Basque",
            "fa" => "Persian",
            "ff" => "Fulah",
            "fi" => "Finnish",
            "fj" => "Fijian",
            "fo" => "Faroese",
            "fr" => "French",
            "fy" => "Western Frisian",
            "ga" => "Irish",
            "gd" => "Scottish Gaelic",
            "gl" => "Galician",
            "gn" => "Guarani",
            "gu" => "Gujarati",
            "gv" => "Manx",
            "ha" => "Hausa",
            "he" => "Hebrew",
            "hi" => "Hindi",
            "ho" => "Hiri Motu",
            "hr" => "Croatian",
            "ht" => "Haitian",
            "hu" => "Hungarian",
            "hy" => "Armenian",
            "hz" => "Herero",
            "ia" => "Interlingua (International Auxiliary Language Association)",
            "id" => "Indonesian",
            "ie" => "Interlingue",
            "ig" => "Igbo",
            "ii" => "Sichuan Yi",
            "ik" => "Inupiaq",
            "io" => "Ido",
            "is" => "Icelandic",
            "it" => "Italian",
            "iu" => "Inuktitut",
            "ja" => "Japanese",
            "jv" => "Javanese",
            "ka" => "Georgian",
            "kg" => "Kongo",
            "ki" => "Kikuyu",
            "kj" => "Kwanyama",
            "kk" => "Kazakh",
            "kl" => "Kalaallisut",
            "km" => "Khmer",
            "kn" => "Kannada",
            "ko" => "Korean",
            "kr" => "Kanuri",
            "ks" => "Kashmiri",
            "ku" => "Kurdish",
            "kv" => "Komi",
            "kw" => "Cornish",
            "ky" => "Kirghiz",
            "la" => "Latin",
            "lb" => "Luxembourgish",
            "lg" => "Ganda",
            "li" => "Limburgish",
            "ln" => "Lingala",
            "lo" => "Lao",
            "lt" => "Lithuanian",
            "lu" => "Luba-Katanga",
            "lv" => "Latvian",
            "mg" => "Malagasy",
            "mh" => "Marshallese",
            "mi" => "Maori",
            "mk" => "Macedonian",
            "ml" => "Malayalam",
            "mn" => "Mongolian",
            "mr" => "Marathi",
            "ms" => "Malay",
            "mt" => "Maltese",
            "my" => "Burmese",
            "na" => "Nauru",
            "nb" => "Norwegian Bokmal",
            "nd" => "North Ndebele",
            "ne" => "Nepali",
            "ng" => "Ndonga",
            "nl" => "Dutch",
            "nn" => "Norwegian Nynorsk",
            "no" => "Norwegian",
            "nr" => "South Ndebele",
            "nv" => "Navajo",
            "ny" => "Chichewa",
            "oc" => "Occitan",
            "oj" => "Ojibwa",
            "om" => "Oromo",
            "or" => "Oriya",
            "os" => "Ossetian",
            "pa" => "Panjabi",
            "pi" => "Pali",
            "pl" => "Polish",
            "ps" => "Pashto",
            "pt" => "Portuguese",
            "qu" => "Quechua",
            "rm" => "Raeto-Romance",
            "rn" => "Kirundi",
            "ro" => "Romanian",
            "ru" => "Russian",
            "rw" => "Kinyarwanda",
            "sa" => "Sanskrit",
            "sc" => "Sardinian",
            "sd" => "Sindhi",
            "se" => "Northern Sami",
            "sg" => "Sango",
            "si" => "Sinhala",
            "sk" => "Slovak",
            "sl" => "Slovenian",
            "sm" => "Samoan",
            "sn" => "Shona",
            "so" => "Somali",
            "sq" => "Albanian",
            "sr" => "Serbian",
            "ss" => "Swati",
            "st" => "Southern Sotho",
            "su" => "Sundanese",
            "sv" => "Swedish",
            "sw" => "Swahili",
            "ta" => "Tamil",
            "te" => "Telugu",
            "tg" => "Tajik",
            "th" => "Thai",
            "ti" => "Tigrinya",
            "tk" => "Turkmen",
            "tl" => "Tagalog",
            "tn" => "Tswana",
            "to" => "Tonga",
            "tr" => "Turkish",
            "ts" => "Tsonga",
            "tt" => "Tatar",
            "tw" => "Twi",
            "ty" => "Tahitian",
            "ug" => "Uighur",
            "uk" => "Ukrainian",
            "ur" => "Urdu",
            "uz" => "Uzbek",
            "ve" => "Venda",
            "vi" => "Vietnamese",
            "vo" => "Volapuk",
            "wa" => "Walloon",
            "wo" => "Wolof",
            "xh" => "Xhosa",
            "yi" => "Yiddish",
            "yo" => "Yoruba",
            "za" => "Zhuang",
            "zh" => "Chinese",
            "zu" => "Zulu"
        );
        return $languageCodes[$lang];
    }
}

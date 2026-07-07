<?php
defined('ABSPATH') or die();
class Aiomatic_Statistics {
    private $wpdb = null;
    private $db_check = false;
    private $table_logs = null;
    private $table_logmeta = null;
    private $apiRef = null;
  
    public function __construct() 
    {
      $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
      $aiomatic_Limit_Settings = get_option('aiomatic_Limit_Settings', false);
      global $wpdb;
      $this->wpdb = $wpdb;
      $this->table_logs = $wpdb->prefix . 'aiomatic_logs';
      $this->table_logmeta = $wpdb->prefix . 'aiomatic_logmeta';
      add_shortcode( 'aiomatic-user-remaining-credits', array( $this, 'shortcode_current' ) );
      if (isset($aiomatic_Main_Settings['aiomatic_enabled']) && $aiomatic_Main_Settings['aiomatic_enabled'] === 'on') 
      {
        if (isset($aiomatic_Main_Settings['enable_tracking']) && trim($aiomatic_Main_Settings['enable_tracking']) == 'on') 
        {
          add_filter( "aiomatic_stats_query", array( $this, 'query' ), 10, 1 );
          add_filter( 'aiomatic_ai_reply', function ( $reply, $query ) {
            global $aiomatic_stats;
            $aiomatic_stats->addCasually( $query, $reply, []);
            return $reply;
          }, 10, 4 );
      
          if ( session_status() == PHP_SESSION_NONE ) {
              @session_start( ['read_and_close' => true] );
          }
          if (isset($aiomatic_Limit_Settings['enable_limits']) && trim($aiomatic_Limit_Settings['enable_limits']) == 'on') 
          {
              add_filter( 'aiomatic_ai_allowed', array( $this, 'check_limits' ), 1, 2 );
          }
        }
      }
    }
  
    function check_limits( $allowed, $aiomatic_Limit_Settings ) {
      global $aiomatic_stats;
      if ( empty( $aiomatic_stats ) ){
        return $allowed;
      }
    
      if (!isset($aiomatic_Limit_Settings['enable_limits']) || trim($aiomatic_Limit_Settings['enable_limits']) != 'on')
      {
        return $allowed;
      }
  
      $userId = $this->getUserId();
      $target = $userId ? 'users' : 'guests';
  
      if ( $target === 'users' ) {
        if (isset($aiomatic_Limit_Settings['ignored_users']) && $aiomatic_Limit_Settings['ignored_users'] != '') 
        {
            $ignoredUsers = $aiomatic_Limit_Settings['ignored_users'];
        }
        else
        {
            $ignoredUsers = 'admin';
        }
        $isAdministrator = current_user_can( 'manage_options' );
        if ( $isAdministrator && $ignoredUsers == 'admin' ) {
          return $allowed;
        }
        $isEditor = current_user_can( 'edit_posts' );
        if ( $isEditor && $ignoredUsers == 'editor' ) {
          return $allowed;
        }
      }
      if (isset($aiomatic_Limit_Settings['ignored_users']) && $aiomatic_Limit_Settings['ignored_users'] != '') 
      {
          $ignoredUsers = $aiomatic_Limit_Settings['ignored_users'];
      }
      else
      {
          $ignoredUsers = 'admin';
      }
      if (isset($aiomatic_Limit_Settings['limit_message_logged']) && $aiomatic_Limit_Settings['limit_message_logged'] != '')
      {
        $limit_message_logged = $aiomatic_Limit_Settings['limit_message_logged'];
      }
      else
      {
        $limit_message_logged = esc_html__('You have reached the usage limit.', 'aiomatic-automatic-ai-content-writer');
      }
      if (isset($aiomatic_Limit_Settings['limit_message_not_logged']) && $aiomatic_Limit_Settings['limit_message_not_logged'] != '')
      {
        $limit_message_not_logged = $aiomatic_Limit_Settings['limit_message_not_logged'];
      }
      else
      {
        $limit_message_not_logged = esc_html__('You have reached the usage limit.', 'aiomatic-automatic-ai-content-writer');
      }
      if ( $target === 'users' ) 
      {
        if (isset($aiomatic_Limit_Settings['user_credits']) && $aiomatic_Limit_Settings['user_credits'] == '0')
        {
            return $limit_message_logged;
        }
        elseif (!isset($aiomatic_Limit_Settings['user_credits']) || $aiomatic_Limit_Settings['user_credits'] == '')
        {
            return $allowed;
        }
        if (isset($aiomatic_Limit_Settings['user_time_frame']) && $aiomatic_Limit_Settings['user_time_frame'] != '')
        {
            $timeFrame = $aiomatic_Limit_Settings['user_time_frame'];
        }
        else
        {
            $timeFrame = 'day';
        }
        if (isset($aiomatic_Limit_Settings['is_absolute_user']) && $aiomatic_Limit_Settings['is_absolute_user'] == 'on')
        {
            $isAbsolute = true;
        }
        else
        {
            $isAbsolute = false;
        }
      }
      else
      {
        if (isset($aiomatic_Limit_Settings['guest_credits']) && $aiomatic_Limit_Settings['guest_credits'] == '0')
        {
            return $limit_message_not_logged;
        }
        elseif (!isset($aiomatic_Limit_Settings['guest_credits']) || $aiomatic_Limit_Settings['guest_credits'] == '')
        {
            return $allowed;
        }
        if (isset($aiomatic_Limit_Settings['guest_time_frame']) && $aiomatic_Limit_Settings['guest_time_frame'] != '')
        {
            $timeFrame = $aiomatic_Limit_Settings['guest_time_frame'];
        }
        else
        {
            $timeFrame = 'day';
        }
        if (isset($aiomatic_Limit_Settings['is_absolute_guest']) && $aiomatic_Limit_Settings['is_absolute_guest'] == 'on')
        {
            $isAbsolute = true;
        }
        else
        {
            $isAbsolute = false;
        }
      }
      $stats = $this->query( $timeFrame, $isAbsolute );
      if ( $stats['overLimit'] ) {
        if ( $target === 'users' ) 
        {
          return $limit_message_logged;
        }
        else
        {
          return $limit_message_not_logged;
        }
      }
      return $allowed;
    }
    function calculatePrice( $model, $units, $option = null )
    {
      // Price as of February 2023: https://openai.com/api/pricing/
      $openai_pricing = array(
        // Base models:
        [ "model" => "davinci", "price" => 0.02, "type" => "token", "unit" => 1 / 1000 ],
        [ "model" => "curie", "price" => 0.002, "type" => "token", "unit" => 1 / 1000 ],
        [ "model" => "babbage", "price" => 0.0005, "type" => "token", "unit" => 1 / 1000 ],
        [ "model" => "ada", "price" => 0.0004, "type" => "token", "unit" => 1 / 1000 ],
        // Image models:
        [ "model" => "dall-e", "type" => "image", "unit" => 1, "options" => [
            [ "option" => "1024x1024", "price" => 0.02 ],
            [ "option" => "512x512", "price" => 0.018 ],
            [ "option" => "256x256", "price" => 0.016 ]
          ],
        ],
        [ "model" => "stable-diffusion", "type" => "image", "unit" => 1, "options" => [
            [ "option" => "1024x1024", "price" => 0.02 ],
            [ "option" => "512x512", "price" => 0.018 ]
          ],
        ],
        // Fine-tuned models:
        [ "model" => "fn-davinci", "price" => 0.12, "type" => "token", "unit" => 1 / 1000 ],
        [ "model" => "fn-curie", "price" => 0.012, "type" => "token", "unit" => 1 / 1000 ],
        [ "model" => "fn-babbage", "price" => 0.0024, "type" => "token", "unit" => 1 / 1000 ],
        [ "model" => "fn-ada", "price" => 0.0016, "type" => "token", "unit" => 1 / 1000 ],
      );

      foreach ( $openai_pricing as $price ) {
        if ( $price['model'] == $model ) {
          if ( $price['type'] == 'image' ) {
            if ( !$option ) {
              aiomatic_log_to_file( "Image models require an option." );
              return null;
            }
            else {
              foreach ( $price['options'] as $imageType ) {
                if ( $imageType['option'] == $option ) {
                  return $imageType['price'] * $units;
                }
              }
            }
          }
          else {
            return $price['price'] * $price['unit'] * $units;
          }
        }
      }
      aiomatic_log_to_file( "Invalid model (" . $model . ")." );
      return null;
    }
    function getPrice( $query, $answer )
    {
      $model = $query->model;
      $modelBase = null;
      $option = '';
      $units = 0;
      if ($query->mode == 'text') {
        if ( preg_match('/^([a-zA-Z]{0,32}):/', $model, $matches ) ) {
          $modelBase = "fn-" . $matches[1];
        }
        else if ( preg_match('/^(?:text|code)-(\w+)-\d+/', $model, $matches ) ) {
          $modelBase = $matches[1];
        }
        if ( empty( $modelBase ) ) {
          aiomatic_log_to_file("Cannot find the base model for $model.");
          return null;
        }
        $units = count(aiomatic_encode($answer));
      }
      else if ( $query->mode == 'image' ) {
        $modelBase = 'dall-e';
        $units = 1;
        if(isset($query->image_size))
        {
          $option = $query->image_size;
        }
      }
      else if ( $query->mode == 'stable' ) {
        $modelBase = 'stable-diffusion';
        $units = 1;
        if(isset($query->image_size))
        {
          $option = $query->image_size;
        }
      }
      else if ( $query->mode == 'edit' ) {
        if ( preg_match('/^([a-zA-Z]{0,32}):/', $model, $matches ) ) {
          $modelBase = "fn-" . $matches[1];
        }
        else if ( preg_match('/^(?:text|code)-(\w+)-edit-\d+/', $model, $matches ) ) {
          $modelBase = $matches[1];
        }
        if ( empty( $modelBase ) ) {
          aiomatic_log_to_file("Cannot find the base model for $model.");
          return null;
        }
        $units = count(aiomatic_encode($answer));
      }
      else
      {
        aiomatic_log_to_file('Unknown query: ' . print_r($query, true));
      }
      return $this->calculatePrice( $modelBase, $units, $option );
    }
    function addCasually( $query, $answer, $overrides ) {
      $type = null;
      $units = 0;
      if ( $query->mode == 'text' || $query->mode == 'edit' ) {
        $type = 'tokens';
        $units = count(aiomatic_encode($answer)) + count(aiomatic_encode($query->prompt));
      }
      else if ( $query->mode == 'image' ) {
        $type = 'images';
        $units = 1;
      }
      else if ( $query->mode == 'stable' ) {
        $type = 'images';
        $units = 1;
      }
      $stats = [ 
        'env' => $query->env,
        'session' => $query->session,
        'mode' => $query->mode,
        'model' => $query->model,
        'apiRef' => $query->apiKey,
        'units' => $units,
        'type' => $type,
      ];
      $stats = array_merge( $stats, $overrides );
      if ( empty( $stats['price'] ) ) 
      {
        $stats['price'] = $this->getPrice( $query, $answer );
      }
      return $this->add( $stats );
    }
    function getUserId( $data = null ) {
      if ( isset( $data ) && isset( $data['userId'] ) ) {
        return (int)$data['userId'];
      }
      if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        if ( $current_user->ID > 0 ) {
          return $current_user->ID;
        }
      }
      return null;
    }
  
    function buildTagsForDb( $tags ) {
      if ( is_array( $tags ) ) {
        $tags = implode( '|', $tags );
      }
      if ( !empty( $tags ) ) {
        $tags .= '|';
      }
      else {
        $tags = null;
      }
      return $tags;
    }
  
    function getUserIpAddress( $data = null ) {
      if ( isset( $data ) && isset( $data['ip'] ) ) {
        $data['ip'] = (string)$data['ip'];
      }
      else {
        if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
          $data['ip'] = $_SERVER['REMOTE_ADDR'];
        }
        else if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
          $data['ip'] = $_SERVER['HTTP_CLIENT_IP'];
        }
        else if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
          $data['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
      }
      return $data['ip'];
    }
  
    function query( $timeFrame = null, $isAbsolute = null, $userId = null, $ipAddress = null, $apiRef = null ) {
  
      if ( $apiRef === null ) {
        $apiRef = $this->apiRef;
      }
      $target = 'guests';
      if ( $userId === null && $ipAddress === null ) {
        $userId = $this->getUserId();
        if ( $userId ) {
          $target = 'users';
        }
        else {
          $ipAddress =  $this->getUserIpAddress();
          if ( $ipAddress === null ) {
            aiomatic_log_to_file( "There should be an userId or an ipAddress." );
            return null;
          }
        }
      }
      $aiomatic_Limit_Settings = get_option('aiomatic_Limit_Settings', false);
      if($target == 'guests')
      {
        if (isset($aiomatic_Limit_Settings['guest_credits']) && $aiomatic_Limit_Settings['guest_credits'] != '')
        {
            $hasLimits = true;
        }
        else
        {
            $hasLimits = false;
        }
        if ( $timeFrame === null ) {
          if (isset($aiomatic_Limit_Settings['guest_time_frame']) && $aiomatic_Limit_Settings['guest_time_frame'] != '')
          {
              $timeFrame = $aiomatic_Limit_Settings['guest_time_frame'];
          }
          else
          {
              $timeFrame = 'day';
          }
        }
        if ( $isAbsolute === null ) {
          if (isset($aiomatic_Limit_Settings['is_absolute_guest']) && $aiomatic_Limit_Settings['is_absolute_guest'] == 'on')
          {
              $isAbsolute = true;
          }
          else
          {
              $isAbsolute = false;
          }
        }
        if (isset($aiomatic_Limit_Settings['guest_credits']) && $aiomatic_Limit_Settings['guest_credits'] != '')
        {
          $credits = $aiomatic_Limit_Settings['guest_credits'];
        }
        else
        {
          $credits = '';
        }
        if (isset($aiomatic_Limit_Settings['guest_credit_type']) && $aiomatic_Limit_Settings['guest_credit_type'] != '')
        {
            $credit_type = $aiomatic_Limit_Settings['guest_credit_type'];
        }
        else
        {
            $credit_type = 'month';
        }
      }
      else
      {
        if (isset($aiomatic_Limit_Settings['user_credits']) && $aiomatic_Limit_Settings['user_credits'] != '')
        {
            $hasLimits = true;
        }
        else
        {
            $hasLimits = false;
        }
        if ( $timeFrame === null ) {
          if (isset($aiomatic_Limit_Settings['user_time_frame']) && $aiomatic_Limit_Settings['user_time_frame'] != '')
          {
              $timeFrame = $aiomatic_Limit_Settings['user_time_frame'];
          }
          else
          {
              $timeFrame = 'day';
          }
        }
        if ( $isAbsolute === null ) {
          if (isset($aiomatic_Limit_Settings['is_absolute_user']) && $aiomatic_Limit_Settings['is_absolute_user'] == 'on')
          {
              $isAbsolute = true;
          }
          else
          {
              $isAbsolute = false;
          }
        }
        if (isset($aiomatic_Limit_Settings['user_credits']) && $aiomatic_Limit_Settings['user_credits'] != '')
        {
          $credits = $aiomatic_Limit_Settings['user_credits'];
        }
        else
        {
          $credits = '';
        }
        if (isset($aiomatic_Limit_Settings['user_credit_type']) && $aiomatic_Limit_Settings['user_credit_type'] != '')
        {
            $credit_type = $aiomatic_Limit_Settings['user_credit_type'];
        }
        else
        {
            $credit_type = 'month';
        }
      }
      if ( $timeFrame !== 'day' && $timeFrame !== 'week' && $timeFrame !== 'month' && $timeFrame !== 'year' ) {
        aiomatic_log_to_file( "TimeFrame should be day, week, month, or year." );
        return null;
      }
  
      $this->check_db();
      $prefix = esc_sql( $this->wpdb->prefix );
      $sql = "SELECT COUNT(*) AS queries, SUM(units) AS units, SUM(price) AS price FROM {$prefix}aiomatic_logs WHERE ";
      
      if ( $target === 'users' ) {
        $sql .= "userId = " . esc_sql( $userId ) . "";
      }
      else {
        $sql .= "ip = '" . esc_sql( $ipAddress ) . "'";
      }
  
      if ( $apiRef ) {
        $sql .= " AND apiRef = '" . esc_sql( $apiRef ) . "'";
      }
      
      if ( $timeFrame === 'day' ) {
        if ( $isAbsolute ) {
          $sql .= " AND DAY(time) = DAY(CURRENT_DATE())";
        }
        else {
          $sql .= " AND time >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)";
        }
      }
      else if ( $timeFrame === 'week' ) {
        if ( $isAbsolute ) {
          $sql .= " AND WEEK(time) = WEEK(CURRENT_DATE())";
        }
        else {
          $sql .= " AND time >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 WEEK)";
        }
      }
      else if ( $timeFrame === 'month' ) {
        if ( $isAbsolute ) {
          $sql .= " AND MONTH(time) = MONTH(CURRENT_DATE())";
        }
        else {
          $sql .= " AND time >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)";
        }
      }
      else if ( $timeFrame === 'year' ) {
        if ( $isAbsolute ) {
          $sql .= " AND YEAR(time) = YEAR(CURRENT_DATE())";
        }
        else {
          $sql .= " AND time >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 YEAR)";
        }
      }
  
      $results = $this->wpdb->get_results( $sql );
      if ( count( $results ) === 0 ) {
        return null;
      }
      $result = $results[0];
      $stats = [];
      $stats['userId'] = $userId;
      $stats['ipAddress'] = $ipAddress;
      $stats['queries'] = intVal( $result->queries );
      $stats['units'] = intVal( $result->units );
      $stats['price'] = round( floatVal( $result->price ), 2 );
      
      $stats['queriesLimit'] = intVal( $hasLimits && $credit_type === "queries" ? $credits : 0 );
      $stats['unitsLimit'] = intVal( $hasLimits && $credit_type === "units" ? $credits : 0 );
      $stats['priceLimit'] = floatVal( $hasLimits && $credit_type === "price" ? $credits : 0 );
  
      $credits = apply_filters( 'aiomatic_stats_credits',  $credits, $userId );
      $stats['overLimit'] = false;
      if ( $hasLimits ) {
        if ( $credit_type === "queries" ) {
          $stats['overLimit'] = $stats['queries'] > $credits;
          $stats['usagePercentage'] = $stats['queriesLimit'] > 0 ? round( $stats['queries'] / $stats['queriesLimit'] * 100, 2 ) : 0;
        }
        else if ( $credit_type === "units" ) {
          $stats['overLimit'] = $stats['units'] > $credits;
          $stats['usagePercentage'] = $stats['unitsLimit'] > 0 ? round( $stats['units'] / $stats['unitsLimit'] * 100, 2 ) : 0;
        }
        else if ( $credit_type === "price" ) {
          $stats['overLimit'] = $stats['price'] > $credits;
          $stats['usagePercentage'] = $stats['priceLimit'] > 0 ? round( $stats['price'] / $stats['priceLimit'] * 100, 2 ) : 0;
        }
      }
      return $stats;
    }
  
    function shortcode_current( $atts ) {
      $display = isset( $atts['display'] ) ? $atts['display'] : 'usage';
      $stats = $this->query();
      if ( $display === "usage" ) {
        wp_register_style('aiomatic-stats-style', plugins_url('../styles/stats-chatgpt.css', __FILE__), false, '1.0.0');
        wp_enqueue_style('aiomatic-stats-style');
        $percent = isset( $stats['usagePercentage'] ) ? $stats['usagePercentage'] : 0;
        $cssPercent = $percent > 100 ? 100 : $percent;
        $output = '<div class="aiomatic-statistics aiomatic-statistics-usage">';
        $output .= '<div class="aiomatic-statistics-bar-container">';
        $output .= '<div class="aiomatic-statistics-bar" style="width: ' . $cssPercent . '%;"></div>';
        $output .= '</div>';
        $output .= '<div class="aiomatic-statistics-bar-text">' . $percent . '%</div>';
        $output .= '</div>';
        return $output;
      }
      else if ( $display === "debug" ) {
        if ( $stats === null ) {
          return "No stats available.";
        }
        $output = '<div class="aiomatic-statistics aiomatic-statistics-debug">';
        if ( !empty( $stats['userId'] ) ) {
          $output .= "User ID: {$stats['userId']}<br>";
        }
        if ( !empty( $stats['ipAddress'] ) ) {
          $output .= "IP Address: {$stats['ipAddress']}<br>";
        }
        $output .= "Queries: {$stats['queries']}" . 
          ( !empty( $stats['queriesLimit'] ) ? " / {$stats['queriesLimit']}" : "" ) . "<br>";
        $output .= "Tokens (Units): {$stats['units']}" . 
          ( !empty( $stats['unitsLimit'] ) ? " / {$stats['unitsLimit']}" : "" ) . "<br>";
        $output .= "Dollars (Price): {$stats['price']}" . 
          ( !empty( $stats['priceLimit'] ) ? " / {$stats['priceLimit']}" : "" ) . "<br>";
        if ( isset( $stats['usagePercentage'] ) ) {
          $output .= "Usage: {$stats['usagePercentage']}%" . "<br>";
          $output .= "Status: " . ( $stats['overLimit'] ? "OVER LIMIT" : "OK" );
        }
        $output .= '</div>';
        return $output;
      }
    }
  
    function validate_data( $data ) {
      // env: Could be "textwriter", "chatbot", "imagesbot", or anything else
      $data['time'] = date( 'Y-m-d H:i:s' );
      $data['userId'] = $this->getUserId( $data );
      $data['session'] = isset( $data['session'] ) ? (string)$data['session'] : null;
      $data['ip'] = $this->getUserIpAddress( $data );
      $data['model'] = isset( $data['model'] ) ? (string)$data['model'] : null;
      $data['mode'] = isset( $data['mode'] ) ? (string)$data['mode'] : null;
      $data['units'] = isset( $data['units'] ) ? intval( $data['units'] ) : 0;
      $data['type'] = isset( $data['type'] ) ? (string)$data['type'] : null;
      $data['price'] = isset( $data['price'] ) ? floatval( $data['price'] ) : 0;
      $data['env'] = isset( $data['env'] )? (string)$data['env'] : null;
      $data['apiRef'] = isset( $data['apiRef'] ) ? (string)$data['apiRef'] : null;
      $data['tags'] = $this->buildTagsForDb( isset( $data['tags'] ) ? $data['tags'] : null );
      return $data;
    }
  
    function add( $data ) {
      $this->check_db();
      $data = $this->validate_data( $data );
      $this->wpdb->insert( $this->table_logs, $data );
    }
  
    function check_db() {
      if ( $this->db_check ) {
        return true;
      }
      $this->db_check = !( strtolower( 
        $this->wpdb->get_var( "SHOW TABLES LIKE '$this->table_logs'" ) ) != strtolower( $this->table_logs )
      );
      if ( !$this->db_check ) {
        $this->create_db();
        $this->db_check = !( strtolower( 
          $this->wpdb->get_var( "SHOW TABLES LIKE '$this->table_logs'" ) ) != strtolower( $this->table_logs )
        );
      }

      $this->db_check = $this->db_check && $this->wpdb->get_var( "SHOW COLUMNS FROM $this->table_logs LIKE 'apiRef'" );
      if ( !$this->db_check ) {
        $this->wpdb->query( "ALTER TABLE $this->table_logs ADD COLUMN apiRef VARCHAR(128) NULL" );
        $this->wpdb->query( "UPDATE $this->table_logs SET apiRef = '$this->apiRef'" );
        $this->db_check = true;
      }
  
      return $this->db_check;
    }
  
    function create_db() {
      $charset_collate = $this->wpdb->get_charset_collate();
  
      $sqlLogs = "CREATE TABLE $this->table_logs (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        userId BIGINT(20) NULL,
        ip VARCHAR(64) NULL,
        session VARCHAR(64) NULL,
        model VARCHAR(64) NULL,
        mode VARCHAR(64) NULL,
        units INT(11) NOT NULL DEFAULT 0,
        type VARCHAR(64) NULL,
        price FLOAT NOT NULL DEFAULT 0,
        env VARCHAR(64) NULL,
        tags VARCHAR(128) NULL,
        apiRef VARCHAR(128) NULL,
        time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";
  
      $sqlLogMeta = "CREATE TABLE $this->table_logmeta (
        meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
        log_id BIGINT(20) NOT NULL,
        meta_key varchar(255) NULL,
        meta_value longtext NULL,
        PRIMARY KEY  (meta_id)
      ) $charset_collate;";
  
      require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sqlLogs );
      dbDelta( $sqlLogMeta );
    }
  
    function remove_db() {
      $sql = "DROP TABLE IF EXISTS $this->table_logs, $this->table_logmeta;";
      $this->wpdb->query( $sql );
    }
  }
  ?>
<?php
function aiomatic_array_unique($array, $keep_key_assoc = false){
   $duplicate_keys = array();
   $tmp = array();       

   foreach ($array as $key => $val){
       if (is_object($val))
           $val = (array)$val;

       if (!in_array($val, $tmp))
           $tmp[] = $val;
       else
           $duplicate_keys[] = $key;
   }

   foreach ($duplicate_keys as $key)
       unset($array[$key]);

   return $keep_key_assoc ? $array : array_values($array);
}
function aiomatic_getIncidents() 
{
   $url = 'https://status.openai.com/history.rss';
   $response = wp_remote_get( $url );
   if ( is_wp_error( $response ) ) {
     throw new Exception( $response->get_error_message() );
   }
   $response = wp_remote_retrieve_body( $response );
   $xml = simplexml_load_string( $response );
   $incidents = array();
   $oneWeekAgo = time() - 7 * 24 * 60 * 60;
   foreach ( $xml->channel->item as $item ) {
     $date = strtotime( $item->pubDate );
     if ( $date > $oneWeekAgo ) {
       $incidents[] = array(
         'title' => (string) $item->title,
         'description' => (string) $item->description,
         'date' => $date
       );
     }
   }
   return $incidents;
 }

function aiomatic_openai_status()
{
   $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
   $aiomatic_Limit_Settings = get_option('aiomatic_Limit_Settings', false);
   if (isset($aiomatic_Limit_Settings['user_credits'])) {
      $user_credits = $aiomatic_Limit_Settings['user_credits'];
  } else {
      $user_credits = '';
  }
  if (isset($aiomatic_Limit_Settings['user_credit_type'])) {
      $user_credit_type = $aiomatic_Limit_Settings['user_credit_type'];
  } else {
      $user_credit_type = '';
  }
  if (isset($aiomatic_Limit_Settings['user_time_frame'])) {
      $user_time_frame = $aiomatic_Limit_Settings['user_time_frame'];
  } else {
      $user_time_frame = '';
  }
  if (isset($aiomatic_Limit_Settings['guest_time_frame'])) {
      $guest_time_frame = $aiomatic_Limit_Settings['guest_time_frame'];
  } else {
      $guest_time_frame = '';
  }
  if (isset($aiomatic_Limit_Settings['is_absolute_user'])) {
      $is_absolute_user = $aiomatic_Limit_Settings['is_absolute_user'];
  } else {
      $is_absolute_user = '';
  }
  if (isset($aiomatic_Limit_Settings['is_absolute_guest'])) {
      $is_absolute_guest = $aiomatic_Limit_Settings['is_absolute_guest'];
  } else {
      $is_absolute_guest = '';
  }
  if (isset($aiomatic_Limit_Settings['guest_credit_type'])) {
      $guest_credit_type = $aiomatic_Limit_Settings['guest_credit_type'];
  } else {
      $guest_credit_type = '';
  }
  if (isset($aiomatic_Limit_Settings['guest_credits'])) {
      $guest_credits = $aiomatic_Limit_Settings['guest_credits'];
  } else {
      $guest_credits = '';
  }
  if (isset($aiomatic_Limit_Settings['limit_message_logged'])) {
      $limit_message_logged = $aiomatic_Limit_Settings['limit_message_logged'];
  } else {
      $limit_message_logged = '';
  }
  if (isset($aiomatic_Limit_Settings['limit_message_not_logged'])) {
      $limit_message_not_logged = $aiomatic_Limit_Settings['limit_message_not_logged'];
  } else {
      $limit_message_not_logged = '';
  }
  if (isset($aiomatic_Limit_Settings['ignored_users'])) {
      $ignored_users = $aiomatic_Limit_Settings['ignored_users'];
  } else {
      $ignored_users = '';
  }
  if (isset($aiomatic_Limit_Settings['enable_limits'])) {
      $enable_limits = $aiomatic_Limit_Settings['enable_limits'];
  } else {
      $enable_limits = '';
  }
?>
<div class="wp-header-end"></div>
<div class="wrap gs_popuptype_holder seo_pops">
<h2 class="cr_center"><?php echo esc_html__("Limits & Statistics", 'aiomatic-automatic-ai-content-writer');?></h2>
<div class="wrap">
        <nav class="nav-tab-wrapper">
            <a href="#tab-1" class="nav-tab nav-tab-active"><?php echo esc_html__("Usage Statistics", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-2" class="nav-tab"><?php echo esc_html__("Usage Limits", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-3" class="nav-tab"><?php echo esc_html__("OpenAI Status", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-4" class="nav-tab"><?php echo esc_html__("Persistent Chat Logs", 'aiomatic-automatic-ai-content-writer');?></a>
        </nav>
        <div id="tab-1" class="tab-content">
         <br/>
         <?php
         if (isset($aiomatic_Main_Settings['enable_tracking']) && $aiomatic_Main_Settings['enable_tracking'] === 'on') {
            echo esc_html__("Statistics coming soon...", 'aiomatic-automatic-ai-content-writer') . '<br/>';
         }
         else
         {
             echo esc_html__("You need to enable the 'Enable Usage Tracking For Statistics And Usage Limits' checkbox from the plugin's 'Main Settings' menu to enable this feature.", 'aiomatic-automatic-ai-content-writer');
         }
         ?>
        </div>
        <div id="tab-2" class="tab-content">
        <br/>
        <?php
         if (isset($aiomatic_Main_Settings['enable_tracking']) && $aiomatic_Main_Settings['enable_tracking'] === 'on') {
         ?>
        <form id="myForm" method="post" action="<?php if(is_multisite() && is_network_admin()){echo '../options.php';}else{echo 'options.php';}?>">
        <?php
        settings_fields('aiomatic_option_group3');
        do_settings_sections('aiomatic_option_group3');
        ?>
   <div class="cr_autocomplete">
      <input type="password" id="PreventChromeAutocomplete" 
         name="PreventChromeAutocomplete" autocomplete="address-level4" />
   </div>
        <table class="widefat">
        <tr>
                     <td>
                        <div>
                           <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                              <div class="bws_hidden_help_text cr_min_260px">
                                 <?php
                                    echo esc_html__("Do you want to enable usage limits?", 'aiomatic-automatic-ai-content-writer');
                                    ?>
                              </div>
                           </div>
                           <b><?php echo esc_html__("Enable Usage Limits:", 'aiomatic-automatic-ai-content-writer');?></b>
                     </td>
                     <td>
                     <input type="checkbox" id="enable_limits" name="aiomatic_Limit_Settings[enable_limits]" <?php
                        if ($enable_limits == 'on')
                            echo ' checked ';
                        ?>>
                     </div>
                     </td>
                  </tr>
        <tr><td colspan="2"><h3><?php echo esc_html__("Restrictions For Logged In Users:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
         <tr>
            <td>
               <div>
                  <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                     <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                           echo esc_html__("Select the maximum number of credits for logged in users. Also, you can select the type of credits: queries, tokens or price. To disable this feature, leave this field blank.", 'aiomatic-automatic-ai-content-writer');
                           ?>
                     </div>
                  </div>
                  <b><?php echo esc_html__("Max User Credits:", 'aiomatic-automatic-ai-content-writer');?></b>
               </div>
            </td>
            <td>
               <div>
               <input type="number" id="user_credits" step="0.01" min="0" placeholder="<?php echo esc_html__("Maximum Credits For Users", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Limit_Settings[user_credits]" value="<?php
                     echo esc_html($user_credits);
                     ?>"/>
                     <select id="user_credit_type" name="aiomatic_Limit_Settings[user_credit_type]" >
                     <option value="queries"<?php
                        if ($user_credit_type == "queries") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Queries", 'aiomatic-automatic-ai-content-writer');?></option>
                        <?php
                     $appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
                     $appids = array_filter($appids);
                     $token = $appids[array_rand($appids)];   
                     if(!aiomatic_is_aiomaticapi_key($token))
                     {
                     ?>
                     <option value="units"<?php
                        if ($user_credit_type == "units") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Tokens", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="price"<?php
                        if ($user_credit_type == "price") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Price", 'aiomatic-automatic-ai-content-writer');?></option>
                        <?php
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
                           echo esc_html__("Select the time frame for which to apply the above limitation.", 'aiomatic-automatic-ai-content-writer');
                           ?>
                     </div>
                  </div>
                  <b><?php echo esc_html__("Time Frame:", 'aiomatic-automatic-ai-content-writer');?></b>
               </div>
            </td>
            <td>
               <div>
               <select id="user_time_frame" name="aiomatic_Limit_Settings[user_time_frame]" >
                     <option value="day"<?php
                        if ($user_time_frame == "day") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Day", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="week"<?php
                        if ($user_time_frame == "week") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Week", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="month"<?php
                        if ($user_time_frame == "month") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Month", 'aiomatic-automatic-ai-content-writer');?></option>
                        <option value="year"<?php
                           if ($user_time_frame == "year") {
                                 echo " selected";
                           }
                           ?>><?php echo esc_html__("Year", 'aiomatic-automatic-ai-content-writer');?></option>
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
                           echo esc_html__("With absolute, a day represents today. Otherwise, it represent the past 24 hours from now. The same logic applies to the other time frames.", 'aiomatic-automatic-ai-content-writer');
                           ?>
                     </div>
                  </div>
                  <b><?php echo esc_html__("Absolute Timeframe:", 'aiomatic-automatic-ai-content-writer');?></b>
               </div>
            </td>
            <td>
               <div>
               <input type="checkbox" id="is_absolute_user" name="aiomatic_Limit_Settings[is_absolute_user]"<?php
                  if ($is_absolute_user == 'on')
                        echo ' checked ';
                  ?>>
               </div>
            </td>
         </tr>
         <tr>
            <td>
               <div>
                  <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                     <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                           echo esc_html__("Select the users who will have full access when interacting with the features of the plugin.", 'aiomatic-automatic-ai-content-writer');
                           ?>
                     </div>
                  </div>
                  <b><?php echo esc_html__("Full Access Users:", 'aiomatic-automatic-ai-content-writer');?></b>
               </div>
            </td>
            <td>
               <div>
                  <select id="ignored_users" name="aiomatic_Limit_Settings[ignored_users]" >
                     <option value="admin"<?php
                        if ($ignored_users == "admin") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Admins Only", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="editor"<?php
                        if ($ignored_users == "editor") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Editors & Admins", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="none"<?php
                        if ($ignored_users == "none") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("None", 'aiomatic-automatic-ai-content-writer');?></option>
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
                           echo esc_html__("Set the message to be displayed to logged in users when usage limit is reached.", 'aiomatic-automatic-ai-content-writer');
                           ?>
                     </div>
                  </div>
                  <b><?php echo esc_html__("Message When Limit Reached (Logged In):", 'aiomatic-automatic-ai-content-writer');?></b>
               </div>
            </td>
            <td>
               <div>
                  <textarea rows="1" cols="70" name="aiomatic_Limit_Settings[limit_message_logged]" placeholder="<?php echo esc_html__("Usage limit message", 'aiomatic-automatic-ai-content-writer');?>"><?php
               echo esc_textarea($limit_message_logged);
               ?></textarea>
               </div>
            </td>
         </tr>
         <tr><td colspan="2"><h3><?php echo esc_html__("Restrictions For Not Logged In Users:", 'aiomatic-automatic-ai-content-writer');?></h3></td></tr>
         <tr>
            <td>
               <div>
                  <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                     <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                           echo esc_html__("Select the maximum number of credits for guests who are not logged in. To disable this feature, leave this field blank.", 'aiomatic-automatic-ai-content-writer');
                           ?>
                     </div>
                  </div>
                  <b><?php echo esc_html__("Max Guest Credits:", 'aiomatic-automatic-ai-content-writer');?></b>
               </div>
            </td>
            <td>
               <div>
               <input type="number" id="guest_credits" step="0.01" min="0" placeholder="<?php echo esc_html__("Maximum Credits For Guests", 'aiomatic-automatic-ai-content-writer');?>" name="aiomatic_Limit_Settings[guest_credits]" value="<?php
                     echo esc_html($guest_credits);
                     ?>"/>
                     <select id="guest_credit_type" name="aiomatic_Limit_Settings[guest_credit_type]" >
                     <option value="queries"<?php
                        if ($guest_credit_type == "queries") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Queries", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="units"<?php
                        if ($guest_credit_type == "units") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Tokens", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="price"<?php
                        if ($guest_credit_type == "price") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Price", 'aiomatic-automatic-ai-content-writer');?></option>
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
                           echo esc_html__("Select the time frame for which to apply the above limitation.", 'aiomatic-automatic-ai-content-writer');
                           ?>
                     </div>
                  </div>
                  <b><?php echo esc_html__("Time Frame:", 'aiomatic-automatic-ai-content-writer');?></b>
               </div>
            </td>
            <td>
               <div>
               <select id="guest_time_frame" name="aiomatic_Limit_Settings[guest_time_frame]" >
                     <option value="day"<?php
                        if ($guest_time_frame == "day") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Day", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="week"<?php
                        if ($guest_time_frame == "week") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Week", 'aiomatic-automatic-ai-content-writer');?></option>
                     <option value="month"<?php
                        if ($guest_time_frame == "month") {
                              echo " selected";
                        }
                        ?>><?php echo esc_html__("Month", 'aiomatic-automatic-ai-content-writer');?></option>
                        <option value="year"<?php
                           if ($guest_time_frame == "year") {
                                 echo " selected";
                           }
                           ?>><?php echo esc_html__("Year", 'aiomatic-automatic-ai-content-writer');?></option>
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
                           echo esc_html__("With absolute, a day represents today. Otherwise, it represent the past 24 hours from now. The same logic applies to the other time frames.", 'aiomatic-automatic-ai-content-writer');
                           ?>
                     </div>
                  </div>
                  <b><?php echo esc_html__("Absolute Timeframe:", 'aiomatic-automatic-ai-content-writer');?></b>
               </div>
            </td>
            <td>
               <div>
               <input type="checkbox" id="is_absolute_guest" name="aiomatic_Limit_Settings[is_absolute_guest]"<?php
                  if ($is_absolute_guest == 'on')
                        echo ' checked ';
                  ?>>
               </div>
            </td>
         </tr>
         <tr>
            <td>
               <div>
                  <div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help cr_align_middle">
                     <div class="bws_hidden_help_text cr_min_260px">
                        <?php
                           echo esc_html__("Set the message to be displayed to not logged in users when usage limit is reached.", 'aiomatic-automatic-ai-content-writer');
                           ?>
                     </div>
                  </div>
                  <b><?php echo esc_html__("Message When Limit Reached (Not Logged In):", 'aiomatic-automatic-ai-content-writer');?></b>
               </div>
            </td>
            <td>
               <div>
                  <textarea rows="1" cols="70" name="aiomatic_Limit_Settings[limit_message_not_logged]" placeholder="<?php echo esc_html__("Usage limit message", 'aiomatic-automatic-ai-content-writer');?>"><?php
               echo esc_textarea($limit_message_not_logged);
               ?></textarea>
               </div>
            </td>
         </tr>
         </table>
         <div><p class="submit"><input type="submit" name="btnSubmit" id="btnSubmit" class="button button-primary" value="<?php echo esc_html__("Save Settings", 'aiomatic-automatic-ai-content-writer');?>"/></p></div>
         </form>
         <?php
            echo esc_html__("API usage for this user account: ", 'aiomatic-automatic-ai-content-writer') . do_shortcode('[aiomatic-user-remaining-credits]');
         }
         else
         {
             echo esc_html__("You need to enable the 'Enable Usage Tracking For Statistics And Usage Limits' checkbox from the plugin's 'Main Settings' menu to enable this feature.", 'aiomatic-automatic-ai-content-writer');
         }
            ?>
        </div>
        <div id="tab-3" class="tab-content">
        <br/>
        <p class="cr_center"><?php echo esc_html__("Only the incidents which occured less than a week ago are displayed here.", 'aiomatic-automatic-ai-content-writer');?></p><hr/>
<?php 
try {
   $incidents = get_transient( 'aiomatic_openai_incidents' );
   if ( $incidents === false ) {
      $incidents = aiomatic_getIncidents();
      set_transient( 'aiomatic_openai_incidents', $incidents, 60 * 10 );
   }
   $echo_me = '';
   foreach($incidents as $incident)
   {
      $echo_me .= '<div><h3><img draggable="false" role="img" class="emoji" alt="⚠️" src="https://s.w.org/images/core/emoji/14.0.0/svg/26a0.svg">';
      $echo_me .= ' ' . $incident['date'] . ': ' . $incident['title'] . '</h3><div class="description">' . $incident['description'] . '</div></div><hr class="cr-dashed"/>';
   }
   echo $echo_me;
   if($echo_me != '')
   {
       echo '<hr/>';
   }
}
catch ( Exception $e ) {
   echo 'Error while processing OpenAI status: ' . $e->getMessage();
}
?>
        </div>
        <div id="tab-4" class="tab-content">
         <br/>
<?php
      if ( isset( $_GET['action'] ) && isset( $_GET['user_id'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'user_meta_manager_' . $_GET['action'] . '_' . $_GET['user_id'] ) ) 
      {
         $user_id = intval( $_GET['user_id'] );
         $action = sanitize_key( $_GET['action'] );
         $conv_id = sanitize_key( $_GET['conv_id'] );
         if ( $action == 'delete_meta' && is_numeric($user_id) && $user_id > 0 ) 
         {
            delete_user_meta( $user_id, 'aiomatic_chat_history' . $conv_id );
         }
      }

      $paged = 1;
      if ( isset( $_GET['paged'] ) ) {
        $paged = intval( $_GET['paged'] );
      }
      $users_per_page = 20;
      if ( isset( $_GET['users_per_page'] ) ) {
        $users_per_page = intval( $_GET['users_per_page'] );
      }
      $users_query = new WP_User_Query(
        array(
         'meta_query' => array(
            array(
               'key'     => 'aiomatic_chat_history',
               'compare_key' => 'LIKE'
            )
          ),
          'number' => $users_per_page,
          'paged' => $paged,
        )
      );

      $total_users = $users_query->get_total();
      $total_pages = ceil( $total_users / $users_per_page );

      $users = $users_query->get_results();

      echo '<div class="wrap">';
      echo '<h1>' . esc_html__('User Conversation Manager', 'aiomatic-automatic-ai-content-writer') . '</h1>';
      echo '<table class="wp-list-table widefat fixed striped users">';
      echo '<thead>';
      echo '<tr>';
      echo '<th scope="col" id="username" class="manage-column column-username column-primary">Username</th>';
      echo '<th scope="col" id="username" class="manage-column column-username column-primary">Chat ID</th>';
      echo '<th scope="col" id="email" class="manage-column column-email">Email</th>';
      echo '<th scope="col" id="actions" class="manage-column column-actions">Actions</th>';
      echo '</tr>';
      echo '</thead>';
      echo '<tbody id="the-list">';
      if(count($users) == 0)
      {
         echo '</tbody></table><br/><br/>' . esc_html__('No persistent chat messages found. You can enable this feature if you use the following shortcode to add a persistent AI chat to your page: [aiomatic-chat-form persistent="on"]', 'aiomatic-automatic-ai-content-writer') . '</div>';
      }
      else
      {
         $current_page = (aiomatic_isSecure() ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
         $users = aiomatic_array_unique($users);
         foreach ( $users as $user ) {
               $user_id = $user->ID;
               $username = $user->user_login;
               $email = $user->user_email;
               $all_meta = get_user_meta($user_id, '', true);
               $my_meta = array_filter($all_meta, function($key){
                  return strpos($key, 'aiomatic_chat_history') === 0;
               }, ARRAY_FILTER_USE_KEY);
               foreach($my_meta as $key => $zmeta)
               {
                  echo '<tr>';
                  echo '<td class="username column-username has-row-actions column-primary" data-colname="Username">' . esc_html( $username ) . '</td>';
                  $pref = explode('aiomatic_chat_history', $key);
                  echo '<td class="chatid column-chatid" data-colname="ChatID">' . $pref[1] . '</td>';
                  echo '<td class="email column-email" data-colname="Email">' . esc_html( $email ) . '</td>';
                  echo '<td class="actions column-actions" data-colname="Actions">';
                  echo '<a href="' . add_query_arg( array( 'action' => 'view_meta', 'user_id' => $user_id, 'conv_id' => $pref[1], '_wpnonce' => wp_create_nonce( 'user_meta_manager_view_meta_' . $user_id ) ), $current_page ) . '">View</a> | ';
                  echo '<a href="' . add_query_arg( array( 'action' => 'delete_meta', 'user_id' => $user_id, 'conv_id' => $pref[1], '_wpnonce' => wp_create_nonce( 'user_meta_manager_delete_meta_' . $user_id ) ), $current_page ) . '">Delete</a>';
                  echo '</td>';
                  echo '</tr>';
               }
         }
         echo '</tbody>';
         echo '</table>';
         echo '<div class="tablenav bottom">';
         echo '<div class="tablenav-pages">';
         echo '<span class="displaying-num">' . $total_users . ' items</span>';
         echo '<span class="pagination-links">';
         if($paged > 1)
         {
            echo '<a href="' . add_query_arg( array( 'paged' => $paged - 1 ), $current_page ) . '">' . esc_html__('Prev', 'aiomatic-automatic-ai-content-writer') . '</a>&nbsp;';
         }
         for ( $i = 1; $i <= $total_pages; $i++ ) 
         {
               $class = ( $i == $paged ) ? ' current' : '';
               echo '<a class="' . $class . '" href="' . add_query_arg( array( 'paged' => $i ), $current_page ) . '">' . $i . '</a>&nbsp;';
         }
         if($paged < $total_pages)
         {
            echo '<a href="' . add_query_arg( array( 'paged' => $paged + 1 ), $current_page ) . '">' . esc_html__('Next', 'aiomatic-automatic-ai-content-writer') . '</a>&nbsp;';
         }
         echo '</span>';
         echo '</div>';
         echo '</div>';
         echo '</div>';
         if ( isset( $_GET['action'] ) && isset( $_GET['user_id'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'user_meta_manager_' . $_GET['action'] . '_' . $_GET['user_id'] ) ) 
         {
               $user_id = intval( $_GET['user_id'] );
               $action = sanitize_key( $_GET['action'] );
               $conv_id = sanitize_key( $_GET['conv_id'] );
               if ( $action == 'delete_meta' ) 
               {
                  echo '<div class="notice notice-success is-dismissible">';
                  echo '<p>' . esc_html__('User conversation data has been deleted.', 'aiomatic-automatic-ai-content-writer') . '</p>';
                  echo '</div>';
               } 
               elseif ( $action == 'view_meta' ) 
               {
                  $conv_meta = get_user_meta($user_id, 'aiomatic_chat_history' . $conv_id, true);
                  echo '<div class="wrap">';
                  echo '<h1>' . esc_html__('User Conversation Manager', 'aiomatic-automatic-ai-content-writer') . '</h1>';
                  echo '<div id="aiomatic_chat_history" class="ai-chat form-control">
                  <table class="form-table">';
                  echo '<tbody>';
                  echo '<tr>';
                  echo '<td>' . $conv_meta . '</td>';
                  echo '</tr>';
                  echo '</tbody>';
                  echo '</table></div>';
                  $zcurrent_page = preg_replace('#&action=view_meta#', '', $current_page);
                  $zcurrent_page = preg_replace('#&conv_id=([^&]*?)&#', '&', $zcurrent_page);
                  echo '<a href="' . $zcurrent_page . '" class="button">' . esc_html__('Back to List', 'aiomatic-automatic-ai-content-writer') . '</a>';
                  echo '</div>';
               }
         }
      }
      ?>
        </div>
    </div>




</div>
<?php
}
?>
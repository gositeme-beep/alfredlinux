<?php
defined('ABSPATH') or die();
use Orhanerday\OpenAi\OpenAi;
if(!class_exists('Aiomatic_Embeddings')) {
    class Aiomatic_Embeddings
    {
        private static  $instance = null ;
        public static function get_instance()
        {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            require_once (dirname(__FILE__) . "/openai/Url.php"); 
            require_once (dirname(__FILE__) . "/openai/OpenAi.php"); 
            add_action('wp_ajax_aiomatic_embeddings',[$this,'aiomatic_embeddings']);
        }

        public function aiomatic_save_embedding($content, $post_type = '', $title = '', $embaddings_id = false, $model = 'text-embedding-ada-002')
        {
            $aiomatic_result = array('status' => 'error', 'msg' => 'Something went wrong');
            $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
            if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
            {
                $aiomatic_result['msg'] = 'Missing API Setting';
                return $aiomatic_result;
            }
            else 
            {
                $appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
                $appids = array_filter($appids);
                $token = $appids[array_rand($appids)];
                $openai = new OpenAi($token);
            }
            if (!isset($aiomatic_Main_Settings['pinecone_app_id']) || trim($aiomatic_Main_Settings['pinecone_app_id']) == '') 
            {
                $aiomatic_result['msg'] = 'You must add a Pinecone API key in the plugin\'s \'Main Settings\' menu (API Keys tab), before you can use this feature!';
                return $aiomatic_result;
            }
            if (!isset($aiomatic_Main_Settings['pinecone_index']) || trim($aiomatic_Main_Settings['pinecone_index']) == '') 
            {
                $aiomatic_result['msg'] = 'You must add a Pinecone index in the plugin\'s \'Main Settings\' menu (Embeddings tab), before you can use this feature!';
                return $aiomatic_result;
            }
            $appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
            $appids = array_filter($appids);
            if(count($appids) > 1)
            {
                $aiomatic_result['msg'] = 'This feature is currently supported only if you enter a single OpenAI API key in the plugin\'s \'Main Settings\' menu.';
                return $aiomatic_result;
            }
            $token = $appids[array_rand($appids)];
            if(aiomatic_is_aiomaticapi_key($token))
            {
                $aiomatic_result['msg'] = 'This feature is currently supported only for OpenAI API keys.';
                return $aiomatic_result;
            }
            $content = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content);
            if($openai){
                $aiomatic_pinecone_api = trim($aiomatic_Main_Settings['pinecone_app_id']);
                $aiomatic_pinecone_environment = trim($aiomatic_Main_Settings['pinecone_index']);
                  $headers = array(
                      'Content-Type' => 'application/json',
                      'Api-Key' => $aiomatic_pinecone_api
                  );
                  $response = wp_remote_get('https://'.$aiomatic_pinecone_environment.'/databases',array(
                      'headers' => $headers
                  ));
                  if(is_wp_error($response)){
                      $aiomatic_result['msg'] = $response->get_error_message();
                      return $aiomatic_result;
                  }

                  $response_code = $response['response']['code'];
                  if($response_code !== 200){
                      $aiomatic_result['msg'] = $response['body'];
                      return $aiomatic_result;
                  }
                  $response = $openai->embeddings(array(
                      'input' => $content,
                      'model' => $model
                  ));
                  $response = json_decode($response, true);
                  if(isset($response['error']) && !empty($response['error'])) {
                      $aiomatic_result['msg'] = $response['error']['message'];
                  }
                  else{
                      $embedding = $response['data'][0]['embedding'];
                      if(empty($embedding)){
                          $aiomatic_result['msg'] = 'No data returned';
                      }
                      else{
                          $pinecone_url = 'https://' . $aiomatic_pinecone_environment . '/vectors/upsert';
                          if(!$embaddings_id) {
                              $embedding_title = empty($title) ? substr($content, 0, 50) : $title;
                              $embedding_data = array(
                                  'post_type' => 'aiomatic_embeddings',
                                  'post_title' => $embedding_title,
                                  'post_content' => $content,
                                  'post_status' => 'publish'
                              );
                              if (!empty($post_type)) {
                                  $embedding_data['post_type'] = $post_type;
                              }
                              $embaddings_id = wp_insert_post($embedding_data);
                          }
                          if(is_wp_error($embaddings_id)){
                              $aiomatic_result['msg'] = $embaddings_id->get_error_message();
                          }
                          else {
                              update_post_meta($embaddings_id, 'aiomatic_start',time());
                              $usage_tokens = $response['usage']['total_tokens'];
                              add_post_meta($embaddings_id, 'aiomatic_embedding_token', $usage_tokens);
                              $vectors = array(
                                  array(
                                      'id' => (string)$embaddings_id,
                                      'values' => $embedding
                                  )
                              );
                              $response = wp_remote_post($pinecone_url, array(
                                  'headers' => $headers,
                                  'body' => json_encode(array('vectors' => $vectors))
                              ));
                              if(is_wp_error($response)){
                                  $aiomatic_result['msg'] = $response->get_error_message();
                                  wp_delete_post($embaddings_id);
                              }
                              else{
                                  $body = json_decode($response['body'],true);
                                  if($body){
                                      if(isset($body['code']) && isset($body['message'])){
                                          $aiomatic_result['msg'] = strip_tags($body['message']);
                                          wp_delete_post($embaddings_id);
                                      }
                                      else{
                                          $aiomatic_result['status'] = 'success';
                                          $aiomatic_result['id'] = $embaddings_id;
                                          update_post_meta($embaddings_id, 'aiomatic_completed', time());
                                      }
                                  }
                                  else{
                                      $aiomatic_result['msg'] = 'No data returned';
                                      wp_delete_post($embaddings_id);
                                  }
                              }
                          }
                      }
                  }
            }
            else{
                $aiomatic_result['msg'] = 'Missing OpenAI API Settings';
            }
            return $aiomatic_result;
        }

        public function aiomatic_delete_embedding($embaddings_id)
        {
            $aiomatic_result = array('status' => 'error', 'msg' => 'Something went wrong');
            $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
            if (!isset($aiomatic_Main_Settings['pinecone_app_id']) || trim($aiomatic_Main_Settings['pinecone_app_id']) == '') 
            {
                $aiomatic_result['msg'] = 'You must add a Pinecone API key in the plugin\'s \'Main Settings\' menu (API Keys tab), before you can use this feature!';
            }
            elseif (!isset($aiomatic_Main_Settings['pinecone_index']) || trim($aiomatic_Main_Settings['pinecone_index']) == '') 
            {
                $aiomatic_result['msg'] = 'You must add a Pinecone index in the plugin\'s \'Main Settings\' menu (Embeddings tab), before you can use this feature!';
            }
            else
            {
                $aiomatic_pinecone_api = trim($aiomatic_Main_Settings['pinecone_app_id']);
                $aiomatic_pinecone_environment = trim($aiomatic_Main_Settings['pinecone_index']);    
                $pinecone_url = 'https://' . $aiomatic_pinecone_environment . '/vectors/delete';
                $headers = array(
                    'Content-Type' => 'application/json',
                    'Api-Key' => $aiomatic_pinecone_api
                );
                $response = wp_remote_post($pinecone_url, array(
                    'headers' => $headers,
                    'body' => json_encode(array('ids' => array($embaddings_id)))
                ));
                if(is_wp_error($response)){
                    $aiomatic_result['msg'] = $response->get_error_message();
                    wp_delete_post($embaddings_id);
                }
                elseif(wp_remote_retrieve_response_code( $response ) != 200)
                {
                    $aiomatic_result['msg'] = 'Invalid response from API: ' . wp_remote_retrieve_response_code( $response );
                    wp_delete_post($embaddings_id);
                }
                else
                {
                    $aiomatic_result['status'] = 'success';
                    $aiomatic_result['id'] = $embaddings_id;
                    wp_delete_post($embaddings_id);
                }
            }
            return $aiomatic_result;
        }

        public function aiomatic_embeddings()
        {
            $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
            if (isset($aiomatic_Main_Settings['embeddings_model']) && $aiomatic_Main_Settings['embeddings_model'] != '') 
            {
                $model = $aiomatic_Main_Settings['embeddings_model'];
            }
            else
            {
                $model = 'text-embedding-ada-002';
            }
            $aiomatic_result = array('status' => 'error', 'msg' => 'Something went wrong');
            if(isset($_POST['content']) && !empty($_POST['content']))
            {
                $content = wp_kses_post(strip_tags($_POST['content']));
                if(!empty($content)){
                    $aiomatic_result = $this->aiomatic_save_embedding($content, '', '', false, $model);
                }
                else 
                {
                    $aiomatic_result['msg'] = 'Please insert your content first!';
                }
            }
            wp_send_json($aiomatic_result);
        }
    }
    Aiomatic_Embeddings::get_instance();
}

<?php
function aiomatic_embeddings_panel()
{
   $aiomatic_Main_Settings = get_option('aiomatic_Main_Settings', false);
   if (!isset($aiomatic_Main_Settings['app_id']) || trim($aiomatic_Main_Settings['app_id']) == '') 
   {
?>
<h1><?php echo esc_html__("You must add an OpenAI API Key into the plugin's 'Main Settings' menu before you can use this feature!", 'aiomatic-automatic-ai-content-writer');?></h1>
<?php
return;
   }
   if (!isset($aiomatic_Main_Settings['pinecone_app_id']) || trim($aiomatic_Main_Settings['pinecone_app_id']) == '') 
   {
?>
<h1><?php echo esc_html__("You must add a Pinecone API key in the plugin's 'Main Settings' menu (API Keys tab), before you can use this feature!", 'aiomatic-automatic-ai-content-writer');?></h1>
<?php
return;
   }
   if (!isset($aiomatic_Main_Settings['pinecone_index']) || trim($aiomatic_Main_Settings['pinecone_index']) == '') 
   {
?>
<h1><?php echo esc_html__("You must add a Pinecone index in the plugin's 'Main Settings' menu (Embeddings tab), before you can use this feature!", 'aiomatic-automatic-ai-content-writer');?></h1>
<?php
return;
   }
   $appids = preg_split('/\r\n|\r|\n/', trim($aiomatic_Main_Settings['app_id']));
   $appids = array_filter($appids);
   if(count($appids) > 1)
   {
?>
<h1><?php echo esc_html__("This feature is currently supported only if you enter a single OpenAI API key in the plugin's 'Main Settings' menu.", 'aiomatic-automatic-ai-content-writer');?></h1>
<?php
      return;
   }
   $token = $appids[array_rand($appids)];
   if(aiomatic_is_aiomaticapi_key($token))
   {
?>
<h1><?php echo esc_html__("This feature is currently supported only for OpenAI API keys.", 'aiomatic-automatic-ai-content-writer');?></h1>
<?php
      return;
   }
?>
<div class="wp-header-end"></div>
<div class="wrap gs_popuptype_holder seo_pops">
<h2 class="cr_center"><?php echo esc_html__("Aiomatic Embeddings", 'aiomatic-automatic-ai-content-writer');?></h2>

</div>
<div class="wrap">
        <h1><?php echo esc_html__("Embeddings", 'aiomatic-automatic-ai-content-writer');?></h1>
        <nav class="nav-tab-wrapper">
            <a href="#tab-0" class="nav-tab nav-tab-active"><?php echo esc_html__("Step 0: Usage & Tutorial", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-1" class="nav-tab"><?php echo esc_html__("Step 1: Add A New Embedding", 'aiomatic-automatic-ai-content-writer');?></a>
            <a href="#tab-2" class="nav-tab"><?php echo esc_html__("Step 2: List Added Embeddings", 'aiomatic-automatic-ai-content-writer');?></a>
        </nav>
        <div id="tab-0" class="tab-content">
         <br/>
         <h3>What are embeddings in GPT-3?</h3>
         <p>Embeddings are a way to send to the AI content writer a set o pre-trained data, to give it more context about the question or prompt which was submitted to it, for which a response is awaited. These embeddings can help the model better understand language and the requirements sent in the prompt.</p>
         <p>When creating embeds, it's important to keep in mind to always create a high quality data set, as this will help the AI writer to get a more correct context.</p>
         <p>Lets say you would like to give your AI the ability to answer specific questions about your website content, company, product or anything else, but you don't want to go through the process of training your own AI model. In this case, the Embeddings feature is what you will need. Simply specify your statements in the Embeddings section of the plugin and they will be also sent to the AI content writer, when needed.</p>
         <p>If you are looking for more complex way to customize the AI content writer and to be able to "teach" the AI a large set of information (by creating your own fine-tuned model), I suggest you check the <a href="<?php echo admin_url('admin.php?page=aiomatic_openai_training');?>">AI Model Training</a> feature of the plugin.</p>
         <h3>More about Embeddings</h3>
         <p><b>The main steps of creating embeddings are:</b></p>
         <ol><li><b>Step 0: Read this tutorial carefully and watch the tutorial video:</b> be sure to not skip this step! Also, be sure to be clear with <a href="https://openai.com/api/pricing/" target="_blank">OpenAI's pricing</a> for usage of embeddings.</li>
         <li><b>Step 1: Create your data for embeddings:</b> create as many high quality questions and answers as possible, add them on a single line, give detailed context. In this case (contrary to AI model training) you don't need to create very large amounts of data, it is enough to enter just the information which you would like the AI model to learn about.
        </li>
        <li><b>Step 2: List Added Embeddings:</b> Check and verify added embeddings and manage them to be sure they are correct.</li>
     </ol>
         <h3>Tutorial video</h3>
         <p class="cr_center"><div class="embedtool"><iframe src="https://www.youtube.com/embed/hkk0d7W0kIs" frameborder="0" allowfullscreen></iframe></div></p>

        </div>
        <div id="tab-1" class="tab-content">
         <br/>
         <form action="" method="post" id="aiomatic_embeddings_form">
    <input type="hidden" name="action" value="aiomatic_embeddings">
    <div class="aiomatic-embeddings-success" style="padding: 10px;background: #fff;border-left: 2px solid #11ad6b;display: none"><?php echo esc_html__("Embedding saved successfully", 'aiomatic-automatic-ai-content-writer');?></div>
    <div class="aiomatic-mb-10">
        <p><strong><?php echo esc_html__("Add a new embedding:", 'aiomatic-automatic-ai-content-writer');?></strong></p>
        <textarea name="content" class="aiomatic-embeddings-content coderevolution_gutenberg_input" id="aiomatic-embeddings-content" rows="15"></textarea>
    </div>
    <button class="button button-primary"><?php echo esc_html__("Save", 'aiomatic-automatic-ai-content-writer');?></button>
</form>
        </div>
        <div id="tab-2" class="tab-content">
        <br/>
        <button href="#" id="aiomatic_sync_embeddings" class="page-title-action aiomatic_sync_files">Sync Embeddings</button>
        <?php
        $aiomatic_embedding_page = isset($_GET['wpage']) && !empty($_GET['wpage']) ? sanitize_text_field($_GET['wpage']) : 1;
$aiomatic_embeddings = new WP_Query(array(
    'post_type' => 'aiomatic_embeddings',
    'posts_per_page' => 40,
    'paged' => $aiomatic_embedding_page,
    'order' => 'DESC',
    'orderby' => 'date'
));
?>
<table class="wp-list-table widefat fixed striped table-view-list posts">
    <thead>
    <tr>
        <th scope="col">Content</th>
        <th scope="col">Tokens</th>
        <th scope="col">Estimated</th>
        <th scope="col">Date</th>
        <th scope="col">Manage</th>
    </tr>
    </thead>
    <tbody>
    <?php
    if($aiomatic_embeddings->have_posts()){
        foreach ($aiomatic_embeddings->posts as $aiomatic_embedding){
            $token = get_post_meta($aiomatic_embedding->ID,'aiomatic_embedding_token',true);
            ?>
            <tr>
                <td><a href="<?php echo get_edit_post_link($aiomatic_embedding->ID);?>" class="aiomatic-embedding-content"><?php echo esc_html($aiomatic_embedding->post_title)?></a></td>
                <td><?php echo esc_html($token)?></td>
                <td><?php echo !empty($token) ? ((int)esc_html($token)*0.0004).'$': '--'?></td>
                <td><?php echo esc_html($aiomatic_embedding->post_date)?></td>
                <td>
                <button class="button button-small" id="aiomatic_manage_embedding_<?php echo $aiomatic_embedding->ID;?>" onclick="location.href='<?php echo get_edit_post_link($aiomatic_embedding->ID);?>';" href="<?php echo get_edit_post_link($aiomatic_embedding->ID);?>"><?php echo esc_html__("Manage", 'aiomatic-automatic-ai-content-writer');?></button>
                <button class="button button-small aiomatic_delete_embedding" id="aiomatic_delete_embedding_<?php echo $aiomatic_embedding->ID;?>" delete-id="<?php echo $aiomatic_embedding->ID;?>"><?php echo esc_html__("Delete", 'aiomatic-automatic-ai-content-writer');?></button>
                </td>
            </tr>
            <?php
        }
    }
    ?>
    </tbody>
</table>
<div class="aiomatic-paginate">
    <?php
    echo paginate_links( array(
        'base'         => admin_url('admin.php?page=aiomatic_embeddings_panel&wpage=%#%'),
        'total'        => $aiomatic_embeddings->max_num_pages,
        'current'      => $aiomatic_embedding_page,
        'format'       => '?wpage=%#%',
        'show_all'     => false,
        'prev_next'    => false,
        'add_args'     => false,
    ));
    ?>
</div>
        </div>
    </div>
<?php
}
?>
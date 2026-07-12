"use strict";
jQuery(document).ready(function ($)
{
    function aiomaticLoading(btn)
    {
        btn.attr('disabled','disabled');
        if(!btn.find('spinner').length){
            btn.append('<span class="spinner"></span>');
        }
        btn.find('.spinner').css('visibility','unset');
    }
    function aiomaticDisable(btn)
    {
        btn.prop('disabled', true);
    }
    function aiomaticEnable(btn)
    {
        btn.removeAttr('disabled');
    }
    function aiomaticRmLoading(btn)
    {
        btn.removeAttr('disabled');
        btn.find('.spinner').remove();
    }
    $('#aiomatic_sync_embeddings').click(function (){
        var btn = $(this);
        aiomaticLoading(btn);
        location.reload();
    });
    $('#aiomatic_embeddings_form').on('submit', function (e)
    {
        var form = $('#aiomatic_embeddings_form');
        var btn = form.find('button');
        var content = $('.aiomatic-embeddings-content').val();
        if(content === ''){
            alert('Please insert an embedding value!');
        }
        else{
            var data = form.serialize();
            $.ajax({
                url: aiomatic_object.ajax_url,
                data: data,
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function (){
                    aiomaticLoading(btn);
                },
                success: function (res){
                    aiomaticRmLoading(btn);
                    if(res.status === 'success'){
                        $('.aiomatic-embeddings-success').show();
                        $('.aiomatic-embeddings-content').val('');
                        setTimeout(function (){
                            $('.aiomatic-embeddings-success').hide();
                        },2000)
                    }
                    else{
                        alert(res.msg);
                    }
                },
                error: function (r, s, error){
                    aiomaticRmLoading(btn);
                    alert('Error in processing embedding saving: ' + error);
                }
            });
        }
        return false;
    });
    $(".aiomatic_delete_embedding").click(function(e) {
        if(confirm('Are you sure you want to delete this embedding?'))
        {
            var embeddingid = $(this).attr("delete-id");
            if(embeddingid == '')
            {
                alert('Incorrect delete id submitted');
            }
            else
            {
                e.preventDefault();
                var data = {
                    action: 'aiomatic_delete_embedding',
                    embeddingid: embeddingid,
                    nonce: aiomatic_object.nonce,
                };
                jQuery.ajax({
                    url: aiomatic_object.ajax_url,
                    data: data,
                    dataType: 'JSON',
                    type: 'POST',
                    beforeSend: function (){
                        aiomaticDisable($('#aiomatic_delete_embedding_' + embeddingid));
                    },
                    success: function (res){
                        if(res.status === 'success'){
                            location.reload();
                        }
                        else{
                            alert(res.msg);
                            location.reload();
                        }
                    },
                    error: function (r, s, error){
                        alert('Error in processing embedding saving: ' + error);
                        location.reload();
                    }
                });
            }
        }
    });
});
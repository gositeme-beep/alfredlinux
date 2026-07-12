"use strict";
jQuery(document).ready(function ($){
    function aiomatic_ltrim(str) {
        if(!str) return str;
        return str.replace(/^\s+/g, '');
    }
    function aiomatic_stripslashes (str) 
    {
        return (str + '').replace(/\\(.?)/g, function (s, n1) {
          switch (n1) {
          case '\\':
            return '\\';
          case '0':
            return '\u0000';
          case '':
            return '';
          default:
            return n1;
          }
        });
    }
    function aiomatic_toBinary(string) {
        const codeUnits = Uint16Array.from(
          { length: string.length },
          (element, index) => string.charCodeAt(index)
        );
        const charCodes = new Uint8Array(codeUnits.buffer);
      
        let result = "";
        charCodes.forEach((char) => {
          result += String.fromCharCode(char);
        });
        return result;
    }
    function aiomatic_trump(str, pattern) {
        var trumped = "";
        if (str && str.length) {
          trumped = str;
          if (pattern && pattern.length) {
            var idx = str.indexOf(pattern);
      
            if (idx != -1) {
              trumped = str.substring(0, idx);
            }
          }
        }
        return (trumped);
      }
    $('.aiomatic_modal_close').click(function (){
        $('.aiomatic_modal_close').closest('.aiomatic_modal').hide();
        $('.aiomatic_modal_close').closest('.aiomatic_modal').removeClass('aiomatic-small-modal');
        $('.aiomatic-overlay').hide();
    });
    function aiomaticLoading(btn){
        btn.attr('disabled','disabled');
        if(!btn.find('spinner').length){
            btn.append('<span class="spinner"></span>');
        }
        btn.find('.spinner').css('visibility','unset');
    }
    function aiomaticRmLoading(btn){
        btn.removeAttr('disabled');
        btn.find('.spinner').remove();
    }
    var aiomatic_max_file_size = aiomatic_object.maxfilesize;
    var aiomatic_max_size_in_mb = aiomatic_object.maxfilesize / (1024 ** 2);
    var aiomatic_file_button = $('#aiomatic_file_button');
    var aiomatic_file_upload = $('#aiomatic_file_upload');
    var aiomatic_file_purpose = $('#aiomatic_file_purpose');
    var aiomatic_file_name = $('#aiomatic_file_name');
    var aiomatic_file_model = $('#aiomatic_file_model');
    var aiomatic_progress = $('.aiomatic_progress');
    var aiomatic_error_message = $('.aiomatic-error-msg');
    var aiomatic_create_fine_tune = $('.aiomatic_create_fine_tune');
    var aiomatic_retrieve_content = $('.aiomatic_retrieve_content');
    var aiomatic_delete_file = $('.aiomatic_delete_file');
    var aiomatic_ajax_url = aiomatic_object.ajax_url;
    var aiomatic_upload_success = $('.aiomatic_upload_success');
    aiomatic_file_button.click(function (){
        if(aiomatic_file_upload[0].files.length === 0){
            alert('Please select a file!');
        }
        else{
            var aiomatic_file = aiomatic_file_upload[0].files[0];
            var aiomatic_file_extension = aiomatic_file.name.substr( (aiomatic_file.name.lastIndexOf('.') +1) );
            if(aiomatic_file_extension !== 'jsonl'){
                aiomatic_file_upload.val('');
                alert('This feature only accepts JSONL file type!');
            }
            else if(aiomatic_file.size > aiomatic_max_file_size){
                aiomatic_file_upload.val('');
                alert('Dataset allowed maximum size (MB): '+ aiomatic_max_size_in_mb)
            }
            else{
                var formData = new FormData();
                formData.append('action', 'aiomatic_finetune_upload');
                formData.append('file', aiomatic_file);
                formData.append('purpose', aiomatic_file_purpose.val());
                formData.append('model', aiomatic_file_model.val());
                formData.append('name', aiomatic_file_name.val());
                $.ajax({
                    url: aiomatic_ajax_url,
                    type: 'POST',
                    dataType: 'JSON',
                    data: formData,
                    beforeSend: function (){
                        aiomatic_progress.find('span').css('width','0');
                        aiomatic_progress.show();
                        aiomaticLoading(aiomatic_file_button);
                        aiomatic_error_message.hide();
                        aiomatic_upload_success.hide();
                    },
                    xhr: function() {
                        var xhr = $.ajaxSettings.xhr();
                        xhr.upload.addEventListener("progress", function(evt) {
                            if (evt.lengthComputable) {
                                var percentComplete = evt.loaded / evt.total;
                                aiomatic_progress.find('span').css('width',(Math.round(percentComplete * 100))+'%');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(res) {
                        if(res.status === 'success'){
                            aiomaticRmLoading(aiomatic_file_button);
                            aiomatic_progress.hide();
                            aiomatic_file_upload.val('');
                            aiomatic_upload_success.show();
                        }
                        else{
                            aiomaticRmLoading(aiomatic_file_button);
                            aiomatic_progress.find('small').html('Error');
                            aiomatic_progress.addClass('aiomatic_error');
                            aiomatic_error_message.html(res.msg);
                            aiomatic_error_message.show();
                        }
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    error: function (r, s, error){
                        aiomatic_file_upload.val('');
                        aiomaticRmLoading(aiomatic_file_button);
                        aiomatic_progress.addClass('aiomatic_error');
                        aiomatic_progress.find('small').html('Error');
                        alert('Error in processing finetune uploading: ' + error);
                        aiomatic_error_message.show();
                    }
                });
            }
        }
    });
    function aiomaticSortData(){
        $('.aiomatic_data').each(function (idx, item){
            $(item).find('.aiomatic_data_prompt').attr('name','data['+idx+'][prompt]');
            $(item).find('.aiomatic_data_completion').attr('name','data['+idx+'][completion]');
        })
    }
    function aiomaticLoading(btn){
        btn.attr('disabled','disabled');
        if(!btn.find('spinner').length){
            btn.append('<span class="spinner"></span>');
        }
        btn.find('.spinner').css('visibility','unset');
    }
    function aiomaticRmLoading(btn){
        btn.removeAttr('disabled');
        btn.find('.spinner').remove();
    }
    var aiomatic_item = '<div class="aiomatic_data_item aiomatic_data"><div><textarea rows="1" name="data[0][prompt]" class="regular-text aiomatic_data_prompt aiomatic_height" placeholder="Prompt"></textarea> </div><div><textarea rows="1" name="data[0][completion]" class="regular-text aiomatic_data_completion aiomatic_height" placeholder="Completion"></textarea><span class="button button-link-delete">×</span></div></div>';
    var aiomatic_data_restore = window.localStorage.getItem('aiomatic_data_list');
    if(aiomatic_data_restore !== null && aiomatic_data_restore !== "")
    {
        var appendData = '';
        var oldobj = '';
        try{
            oldobj = JSON.parse(aiomatic_data_restore);
            oldobj.forEach(function (element){if(element.prompt !== null && element.completion !== null){appendData += '<div class="aiomatic_data_item aiomatic_data"><div><textarea rows="1" name="data[0][prompt]" class="regular-text aiomatic_data_prompt aiomatic_height" placeholder="Prompt">' + element.prompt + '</textarea> </div><div><textarea rows="1" name="data[0][completion]" class="regular-text aiomatic_data_completion aiomatic_height" placeholder="Completion">' + element.completion + '</textarea><span class="button button-link-delete">×</span></div></div>';}});
            appendData += aiomatic_item;
            $('.aiomatic_data_list').html(appendData);
        }
        catch(e)
        {
            alert(e);
        }
    }
    var progressBar = $('.aiomatic-convert-bar');
    var aiomatic_add_data = $('.aiomatic_add_data');
    var aiomatic_clear_data = $('.aiomatic_clear_data');
    var aiomatic_download_data = $('.aiomatic_download_data');
    var aiomatic_load_data = $('.aiomatic_load_data');
    var form = $('#aiomatic_form_data');
    aiomatic_add_data.click(function (){
        $('.aiomatic_data_list').append(aiomatic_item);
        aiomaticSortData();
        var total = 0;
        var lists = [];
        $('.aiomatic_data').each(function (idx, item){
            var item_prompt = $(item).find('.aiomatic_data_prompt').val();
            var item_completion = $(item).find('.aiomatic_data_completion').val();
            if(item_prompt !== '' && item_completion !== ''){
                total += 1;
                lists.push({prompt: item_prompt ,completion: item_completion});
            }
        });
        if(total > 0){
            try
            {
                var jsonstr = JSON.stringify(lists);
                window.localStorage.setItem('aiomatic_data_list', jsonstr);
            }
            catch(e)
            {
                alert(e);
            }
        }
    });
    aiomatic_clear_data.click(function (){
        $('.aiomatic_data_list').html('<div class="aiomatic_data_item aiomatic_data"><div><textarea rows="1" name="data[0][prompt]" class="regular-text aiomatic_data_prompt aiomatic_height" placeholder="Prompt"></textarea></div><div><textarea rows="1" name="data[0][completion]" class="regular-text aiomatic_data_completion aiomatic_height" placeholder="Completion"></textarea><span class="button button-link-delete">×</span></div></div>');
        window.localStorage.removeItem('aiomatic_data_list');
    });
    aiomatic_download_data.click(function (){
        var total = 0;
        var lists = '';
        $('.aiomatic_data').each(function (idx, item){
            var item_prompt = $(item).find('.aiomatic_data_prompt').val();
            var item_completion = $(item).find('.aiomatic_data_completion').val();
            if(item_prompt !== '' && item_completion !== ''){
                total += 1;
                var json_arr = {};
                json_arr['prompt'] = item_prompt + aiomatic_stripslashes(aiomatic_object.prompt_suffix);
                json_arr['completion'] = ' ' + item_completion + aiomatic_stripslashes(aiomatic_object.completion_suffix);
                try
                {
                    var myJsonString = JSON.stringify(json_arr);
                    lists += myJsonString + '\n';
                }
                catch(e)
                {
                    alert(e);
                }
            }
        });
        lists = lists.trim();
        if(total > 0){
            try
            {
                var blists = aiomatic_toBinary(lists);
                var encodedString = btoa(blists);
                var hiddenElement = document.createElement('a');
                hiddenElement.href = 'data:text/attachment;base64,' + encodedString;
                hiddenElement.target = '_blank';
                hiddenElement.download = 'data.jsonl';
                hiddenElement.click();
            }
            catch(e)
            {
                alert(e);
            }
        }
        else
        {
            alert('No data to download!');
        }
    });
    aiomatic_load_data.click(function (event){
        event.preventDefault();
        var aiomatic_file_load = $('#aiomatic_file_load');
        if(aiomatic_file_load[0].files.length === 0){
            alert('Please select a file first!');
        }
        else
        {
            var aiomatic_file = aiomatic_file_load[0].files[0];
            var aiomatic_file_extension = aiomatic_file.name.substr( (aiomatic_file.name.lastIndexOf('.') +1) );
            if(aiomatic_file_extension !== 'jsonl')
            {
                aiomatic_file_load.val('');
                alert('This feature only accepts JSON file type!');
            }
            else if(aiomatic_file.size > aiomatic_max_file_size)
            {
                aiomatic_file_load.val('');
                alert('Dataset allowed maximum size (MB): '+ aiomatic_max_size_in_mb)
            }
            else
            {
                var reader = new FileReader();
                reader.readAsText(aiomatic_file, "UTF-8");
                var thehtml = '';
                reader.onload = function (evt) {
                    var explodefile = evt.target.result.split(/\r?\n/);
                    explodefile.forEach(function (element){if(element.trim() !== ''){var oldobj = '';try{oldobj = JSON.parse(element.trim());}catch(e) {alert(e);}if(oldobj.prompt !== null && oldobj.completion !== null){thehtml += '<div class="aiomatic_data_item aiomatic_data"><div><textarea rows="1" name="data[0][prompt]" class="regular-text aiomatic_data_prompt aiomatic_height" placeholder="Prompt">' + aiomatic_trump(oldobj.prompt, aiomatic_stripslashes(aiomatic_object.prompt_suffix)) + '</textarea> </div><div><textarea rows="1" name="data[0][completion]" class="regular-text aiomatic_data_completion aiomatic_height" placeholder="Completion">' + aiomatic_ltrim(aiomatic_trump(oldobj.completion, aiomatic_stripslashes(aiomatic_object.completion_suffix))) + '</textarea><span class="button button-link-delete">×</span></div></div>';}}});
                    if(thehtml !== '')
                    {
                        thehtml += '<div class="aiomatic_data_item aiomatic_data"><div><textarea rows="1" name="data[0][prompt]" class="regular-text aiomatic_data_prompt aiomatic_height" placeholder="Prompt"></textarea> </div><div><textarea rows="1" name="data[0][completion]" class="regular-text aiomatic_data_completion aiomatic_height" placeholder="Completion"></textarea><span class="button button-link-delete">×</span></div></div>';
                        $('.aiomatic_data_list').html(thehtml);
                        var total = 0;
                        var lists = [];
                        $('.aiomatic_data').each(function (idx, item){
                            var item_prompt = $(item).find('.aiomatic_data_prompt').val();
                            var item_completion = $(item).find('.aiomatic_data_completion').val();
                            if(item_prompt !== '' && item_completion !== ''){
                                total += 1;
                                lists.push({prompt: item_prompt ,completion: item_completion});
                            }
                        });
                        if(total > 0){
                            try
                            {
                                var jsonstr = JSON.stringify(lists);
                                window.localStorage.setItem('aiomatic_data_list', jsonstr);
                                alert("Data loaded successfully!");
                            }
                            catch(e)
                            {
                                alert(e);
                            }
                        }
                    }
                    else
                    {
                        alert("Invalid file submitted: " + aiomatic_file.name);
                    }
                }
                reader.onerror = function (evt) {
                    alert("Error reading file: " + aiomatic_file.name + ' - ' + reader.error);
                }
            }
        }
    });
    $(document).on('click','.aiomatic_data span', function (e){
        $(e.currentTarget).parent().parent().remove();
        var total = 0;
        var lists = [];
        $('.aiomatic_data').each(function (idx, item){
            var item_prompt = $(item).find('.aiomatic_data_prompt').val();
            var item_completion = $(item).find('.aiomatic_data_completion').val();
            if(item_prompt !== '' && item_completion !== ''){
                total += 1;
                lists.push({prompt: item_prompt ,completion: item_completion});
            }
        });
        if(total > 0){
            try
            {
                var jsonstr = JSON.stringify(lists);
                window.localStorage.setItem('aiomatic_data_list', jsonstr);
            }
            catch(e)
            {
                alert(e);
            }
        }
        else
        {
            window.localStorage.removeItem('aiomatic_data_list');
        }
        aiomaticSortData();
    });

    function aiomaticFileUpload(data, btn){
        var aiomatic_upload_convert_index = parseInt($('#aiomatic_upload_convert_index').val());
        $.ajax({
            url: aiomatic_ajax_url,
            data: data,
            type: 'POST',
            dataType: 'JSON',
            success: function (res){
                if(res.status === 'success'){
                    if(res.next === 'DONE'){
                        $('.aiomatic_data_list').html(aiomatic_item);
                        $('.aiomatic-upload-message').html('The upload was successfully completed!');
                        progressBar.find('small').html('100%');
                        progressBar.find('span').css('width','100%');
                        aiomaticRmLoading(btn);
                        setTimeout(function (){
                            $('#aiomatic_upload_convert_line').val('0');
                            $('#aiomatic_upload_convert_index').val('1');
                            progressBar.hide();
                            progressBar.removeClass('aiomatic_error')
                            progressBar.find('span').css('width',0);
                            progressBar.find('small').html('0%');
                        },2000);

                    }
                    else{
                        $('#aiomatic_upload_convert_line').val(res.next);
                        $('#aiomatic_upload_convert_index').val(aiomatic_upload_convert_index+1);
                        var data = $('#aiomatic_upload_convert').serialize();
                        aiomaticFileUpload(data,btn);
                    }
                }
                else{
                    progressBar.addClass('aiomatic_error');
                    aiomaticRmLoading(btn);
                    alert(res.msg);
                }
            },
            error: function (r, s, error){
                progressBar.addClass('aiomatic_error');
                aiomaticRmLoading(btn);
                alert('Error in processing upload: ' + error);
            }
        })
    }

    function aiomaticProcessData(lists, start, file, btn){
        var purpose = $('select[name=purpose]').val();
        var model = $('select[name=model]').val();
        var name = $('input[name=custom]').val();
        var data = {
            action: 'aiomatic_data_insert',
            prompt: aiomatic_stripslashes(lists[start].prompt),
            completion: aiomatic_stripslashes(lists[start].completion),
            file: file,
        };
        $.ajax({
            url: aiomatic_ajax_url,
            data: data,
            dataType: 'JSON',
            type: 'POST',
            success: function (res){
                if(res.status === 'success'){
                    var percent = Math.ceil((start+1)*90/lists.length);
                    progressBar.find('small').html(percent+'%');
                    progressBar.find('span').css('width',percent+'%');
                    if((start + 1) === lists.length){
                        $('#aiomatic_upload_convert input[name=model]').val(model);
                        $('#aiomatic_upload_convert input[name=purpose]').val(purpose);
                        $('#aiomatic_upload_convert input[name=custom]').val(name);
                        $('#aiomatic_upload_convert input[name=file]').val(res.file);
                        var data = $('#aiomatic_upload_convert').serialize();
                        aiomaticFileUpload(data, btn);
                    }
                    else{
                        file = res.file;
                        aiomaticProcessData(lists, (start+1), file, btn);
                    }
                }
                else{
                    progressBar.addClass('aiomatic_error');
                    aiomaticRmLoading(btn);
                    alert(res.msg);
                }
            },
            error: function (r, s, error){
                progressBar.addClass('aiomatic_error');
                aiomaticRmLoading(btn);
                alert('Error in processing data: ' + error);
            }
        });
    }
    form.on('submit', function (){
        var total = 0;
        var lists = [];
        var btn = form.find('.aiomatic_submit');
        $('.aiomatic_data').each(function (idx, item){
            var item_prompt = $(item).find('.aiomatic_data_prompt').val();
            var item_completion = $(item).find('.aiomatic_data_completion').val();
            if(item_prompt !== '' && item_completion !== ''){
                total += 1;
                lists.push({prompt: item_prompt, completion: item_completion })
            }
        });
        if(total > 0){
            $('#aiomatic_upload_convert_line').val('0');
            $('#aiomatic_upload_convert_index').val('1');
            $('.aiomatic-upload-message').empty();
            progressBar.show();
            progressBar.removeClass('aiomatic_error')
            progressBar.find('span').css('width',0);
            progressBar.find('small').html('0%');
            aiomaticLoading(btn);
            aiomaticProcessData(lists, 0, '', btn);
        }
        else{
            alert('Please insert least one row');
        }
        return false;
    });
    $('.aiomatic_modal_close').click(function (){
        $('.aiomatic_modal_close').closest('.aiomatic_modal').hide();
        $('.aiomatic_modal_close').closest('.aiomatic_modal').removeClass('aiomatic-small-modal');
        $('.aiomatic-overlay').hide();
    });
    var form = $('#aiomatic_data_converter');
    var btn = $('.aiomatic_converter_button');
    var progressBar = $('.aiomatic-convert-bar');
    var aiomatic_convert_upload = $('.aiomatic_convert_upload');
    var aiomatic_delete_upload = $('.aiomatic_delete_upload');
    function aiomaticLoading(btn){
        btn.attr('disabled','disabled');
        if(!btn.find('spinner').length){
            btn.append('<span class="spinner"></span>');
        }
        btn.find('.spinner').css('visibility','unset');
    }
    function aiomaticRmLoading(btn){
        btn.removeAttr('disabled');
        btn.find('.spinner').remove();
    }
    function aiomaticConverter(data){
        $.ajax({
            url: aiomatic_ajax_url,
            data: data,
            type: 'POST',
            dataType: 'JSON',
            success: function (res){
                if(res.status === 'success'){
                    if(res.next_page === 'DONE'){
                        aiomaticRmLoading(btn);
                        progressBar.find('small').html('100%');
                        progressBar.find('span').css('width','100%');
                        setTimeout(function (){
                            window.location.reload();
                        },1000);
                    }
                    else{
                        var percent = Math.ceil(data.page*100/data.total);
                        progressBar.find('small').html(percent+'%');
                        progressBar.find('span').css('width',percent+'%');
                        data.page = res.next_page;
                        data.file = res.file;
                        data.id = res.id;
                        aiomaticConverter(data);
                    }
                }
                else{
                    progressBar.addClass('aiomatic_error');
                    aiomaticRmLoading(btn);
                    alert(res.msg);
                }
            },
            error: function (request, status, error){
                progressBar.addClass('aiomatic_error');
                aiomaticRmLoading(btn);
                alert('Error in processing: ' + error);
            }
        });
    }
    form.on('submit', function (){
        if(!$('.aiomatic_converter_data:checked').length){
            alert('Please select least one data to convert');
        }
        else{
            var data = form.serialize();
            $.ajax({
                url: aiomatic_ajax_url,
                data: data,
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function (){
                    progressBar.show();
                    progressBar.removeClass('aiomatic_error')
                    progressBar.find('span').css('width',0);
                    progressBar.find('small').html('0%');
                    aiomaticLoading(btn);
                },
                success: function (res){
                    if(res.status === 'success'){
                        if(res.count > 0){
                            aiomaticConverter({action: 'aiomatic_data_converter', types: res.types, total: res.count, page: 1, per_page: 100, content_excerpt: res.content_excerpt});
                        }
                        else{
                            progressBar.addClass('aiomatic_error');
                            aiomaticRmLoading(btn);
                            alert('Nothing to convert');
                        }
                    }
                    else{
                        progressBar.addClass('aiomatic_error');
                        aiomaticRmLoading(btn);
                        alert(res.msg);
                    }
                },
                error: function (request, status, error) {
                    progressBar.addClass('aiomatic_error');
                    aiomaticRmLoading(btn);
                    alert('Error in processing: ' + error);
                }
            });
        }
        return false;
    });
    aiomatic_convert_upload.click(function (){
        var btn = $(this);
        var file = btn.attr('data-file');
        var lines = btn.attr('data-lines');
        $('.aiomatic-overlay').show();
        $('.aiomatic_modal').show();
        $('.aiomatic_modal_title').html('File Setting');
        $('.aiomatic_modal').addClass('aiomatic-small-modal');
        $('.aiomatic_modal_content').empty();
        var html = '<form id="aiomatic_upload_convert" action="" method="post"><input type="hidden" name="action" value="aiomatic_upload_convert"><input type="hidden" id="aiomatic_upload_convert_index" name="index" value="1"><input id="aiomatic_upload_convert_line" type="hidden" name="line" value="0"><input id="aiomatic_upload_convert_lines" type="hidden" value="'+lines+'"><input type="hidden" name="file" value="'+file+'"><p><label>Purpose</label>&nbsp;<select class="coderevolution_gutenberg_select" name="purpose"><option value="fine-tune">Fine-Tune</option></select></p>';
        html += '<p><label>Model Base</label>&nbsp;<select class="coderevolution_gutenberg_select" name="model"><option value="ada">ada</option><option value="babbage">babbage</option><option value="curie">curie</option><option value="davinci" selected>davinci</option></select></p>';
        html += '<p><label>Custom Name</label>&nbsp;<input class="coderevolution_gutenberg_select" type="text" name="custom"></p>';
        html += '<div class="aiomatic-convert-progress aiomatic-upload-bar"><span></span><small>0%</small></div>';
        html += '<div class="aiomatic-upload-message"></div><p><button class="button button-primary coderevolution_gutenberg_select">Upload</button></p>'
        $('.aiomatic_modal_content').append(html);
    });
    aiomatic_delete_upload.click(function (){
        var btn = $(this);
        var file = btn.attr('data-file');
        $.ajax({
            url: aiomatic_ajax_url,
            data: {
                action: 'aiomatic_file_delete',
                file: file
            },
            type: 'POST',
            beforeSend: function (){
                progressBar.show();
                progressBar.removeClass('aiomatic_error')
                progressBar.find('span').css('width',0);
                progressBar.find('small').html('0%');
                aiomaticLoading(btn);
            },
            success: function (res){
                if(res.status === 'success'){
                    window.location.reload();
                }
                else{
                    progressBar.addClass('aiomatic_error');
                    aiomaticRmLoading(btn);
                    alert(res.msg);
                }
            },
            error: function (request, status, error) {
                progressBar.addClass('aiomatic_error');
                aiomaticRmLoading(btn);
                alert('Error in deleting: ' + error);
            }
        });
    });
    function aiomaticFileUpload(data, btn){
        var aiomatic_upload_convert_index = parseInt($('#aiomatic_upload_convert_index').val());
        var total_lines = parseInt($('#aiomatic_upload_convert_lines').val());
        if(total_lines === 0)
        {
            total_lines = 1;
        }
        var  aiomatic_upload_bar = $('.aiomatic-convert-bar');
        $.ajax({
            url: aiomatic_ajax_url,
            data: data,
            type: 'POST',
            dataType: 'JSON',
            success: function (res){
                if(res.status === 'success'){
                    if(res.next === 'DONE'){
                        $('.aiomatic-upload-message').html('Upload was successful!');
                        res.next = total_lines;
                        var percent = Math.ceil(res.next*100/total_lines);
                        aiomatic_upload_bar.find('small').html(percent+'%');
                        aiomatic_upload_bar.find('span').css('width',percent+'%');
                        aiomaticRmLoading(btn);
                    }
                    else{
                        var percent = Math.ceil(res.next*100/total_lines);
                        aiomatic_upload_bar.find('small').html(percent+'%');
                        aiomatic_upload_bar.find('span').css('width',percent+'%');
                        $('#aiomatic_upload_convert_line').val(res.next);
                        $('#aiomatic_upload_convert_index').val(aiomatic_upload_convert_index+1);
                        var data = $('#aiomatic_upload_convert').serialize();
                        aiomaticFileUpload(data,btn);
                    }
                }
                else{
                    aiomatic_upload_bar.addClass('aiomatic_error');
                    aiomaticRmLoading(btn);
                    alert(res.msg);
                }
            },
            error: function (r, s, error){
                aiomatic_upload_bar.addClass('aiomatic_error');
                aiomaticRmLoading(btn);
                alert('Error in processing file upload: ' + error);
            }
        });
    }
    $(document).on('submit','#aiomatic_upload_convert', function (e){
        $('#aiomatic_upload_convert_index').val(1);
        $('#aiomatic_upload_convert_line').val(0);
        $('.aiomatic-upload-message').empty();
        var form = $(e.currentTarget);
        var data = form.serialize();
        var btn = form.find('button');
        aiomaticLoading(btn);
        var  aiomatic_upload_bar = $('.aiomatic-upload-bar');
        aiomatic_upload_bar.show();
        aiomatic_upload_bar.removeClass('aiomatic_error')
        aiomatic_upload_bar.find('span').css('width',0);
        aiomatic_upload_bar.find('small').html('0%');
        aiomaticFileUpload(data,btn);
        return false;
    })
    $('.aiomatic_modal_close').click(function (){
        $('.aiomatic_modal_close').closest('.aiomatic_modal').hide();
        $('.aiomatic_modal_close').closest('.aiomatic_modal').removeClass('aiomatic-small-modal');
        $('.aiomatic-overlay').hide();
    })
    function aiomaticLoading(btn){
        btn.attr('disabled','disabled');
        if(!btn.find('spinner').length){
            btn.append('<span class="spinner"></span>');
        }
        btn.find('.spinner').css('visibility','unset');
    }
    function aiomaticRmLoading(btn){
        btn.removeAttr('disabled');
        btn.find('.spinner').remove();
    }
    var aiomaticAjaxRunning = false;
    $('.aiomatic_sync_files').click(function (){
        var btn = $(this);
        if(!aiomaticAjaxRunning) {
            $.ajax({
                url: aiomatic_ajax_url,
                data: {action: 'aiomatic_fetch_finetune_files'},
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function () {
                    aiomaticAjaxRunning = true;
                    aiomaticLoading(btn);
                },
                success: function (res) {
                    aiomaticAjaxRunning = false;
                    aiomaticRmLoading(btn);
                    if (res.status === 'success') {
                        window.location.reload();
                    } else {
                        alert(res.msg);
                    }
                },
                error: function (r, s, error) {
                    aiomaticAjaxRunning = false;
                    aiomaticRmLoading(btn);
                    alert('Error in processing sunc: ' + error);
                }
            });
        }
    });
    aiomatic_delete_file.click(function (){
        if(!aiomaticAjaxRunning) {
            var conf = confirm('Are you sure?');
            if (conf) {
                var btn = $(this);
                var id = btn.attr('data-id');
                $.ajax({
                    url: aiomatic_ajax_url,
                    data: {action: 'aiomatic_delete_finetune_file', id: id},
                    dataType: 'JSON',
                    type: 'POST',
                    beforeSend: function () {
                        aiomaticAjaxRunning = true;
                        aiomaticLoading(btn);
                    },
                    success: function (res) {
                        aiomaticAjaxRunning = false;
                        aiomaticRmLoading(btn);
                        if (res.status === 'success') {
                            window.location.reload();
                        } else {
                            alert(res.msg);
                        }
                    },
                    error: function (r, s, error) {
                        aiomaticAjaxRunning = false;
                        aiomaticRmLoading(btn);
                        alert('Error in processing finetune removal: ' + error);
                    }
                });
            }
            else{
                aiomaticAjaxRunning = false;
            }
        }
    });
    $(document).on('click','#aiomatic_create_finetune_btn', function (e){
        if(!aiomaticAjaxRunning) {
            var btn = $(e.currentTarget);
            var id = $('#aiomatic_create_finetune_id').val();
            var model = $('#aiomatic_create_finetune_model').val();
            $.ajax({
                url: aiomatic_ajax_url,
                data: {action: 'aiomatic_create_finetune', id: id, model: model},
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function () {
                    aiomaticAjaxRunning = true;
                    aiomaticLoading(btn);
                },
                success: function (res) {
                    aiomaticRmLoading(btn);
                    aiomaticAjaxRunning = false;
                    if (res.status === 'success') {
                        window.location.reload();
                    } else {
                        alert(res.msg);
                    }
                },
                error: function (r, s, error) {
                    aiomaticAjaxRunning = false;
                    aiomaticRmLoading(btn);
                    alert('Error in processing new finetune: ' + error);
                }
            });
        }
    });
    aiomatic_create_fine_tune.click(function (){
        if(!aiomaticAjaxRunning) {
            var btn = $(this);
            var id = btn.attr('data-id');
            $.ajax({
                url: aiomatic_ajax_url,
                data: {action: 'aiomatic_create_finetune_modal'},
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function () {
                    aiomaticAjaxRunning = true;
                    aiomaticLoading(btn);
                },
                success: function (res) {
                    aiomaticAjaxRunning = false;
                    aiomaticRmLoading(btn);
                    if (res.status === 'success') {
                        $('.aiomatic_modal_content').empty();
                        $('.aiomatic-overlay').show();
                        $('.aiomatic_modal').show();
                        $('.aiomatic_modal_title').html('Choose Model');
                        $('.aiomatic_modal').addClass('aiomatic-small-modal');
                        var html = '<input type="hidden" id="aiomatic_create_finetune_id" value="' + id + '"><p><label>Select Model</label>';
                        html += '<select class="coderevolution_gutenberg_select" id="aiomatic_create_finetune_model">';
                        html += '<option value="">New Model</option>';
                        $.each(res.data, function (idx, item) {
                            html += '<option value="' + item + '">' + item + '</option>';
                        })
                        html += '</select>';
                        html += '</p>';
                        html += '<p><button class="button button-primary coderevolution_gutenberg_select" id="aiomatic_create_finetune_btn">Create</button></p>'
                        $('.aiomatic_modal_content').append(html)
                    } else {
                        alert(res.msg);
                    }
                },
                error: function (r, s, error) {
                    aiomaticAjaxRunning = false;
                    aiomaticRmLoading(btn);
                    alert('Error in processing new finetune modal: ' + error);
                }
            });
        }
    });
    aiomatic_retrieve_content.click(function (){
        if(!aiomaticAjaxRunning) {
            var btn = $(this);
            var id = btn.attr('data-id');
            $.ajax({
                url: aiomatic_ajax_url,
                data: {action: 'aiomatic_get_finetune_file', id: id},
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function () {
                    aiomaticAjaxRunning = true;
                    aiomaticLoading(btn);
                },
                success: function (res) {
                    aiomaticAjaxRunning = false;
                    aiomaticRmLoading(btn);
                    if (res.status === 'success') {
                        $('.aiomatic_modal_title').html('File Content');
                        $('.aiomatic_modal_content').html('<pre>' + res.data + '</pre>');
                        $('.aiomatic-overlay').show();
                        $('.aiomatic_modal').show();
                    } else {
                        alert(res.msg);
                    }
                },
                error: function (r, s, error) {
                    aiomaticAjaxRunning = false;
                    aiomaticRmLoading(btn);
                    alert('Error in processing finetune file: ' + error);
                }
            });
        }
    });
    var aiomaticAjaxRunning = false;
    $('.aiomatic_modal_close').click(function (){
        $('.aiomatic_modal_close').closest('.aiomatic_modal').hide();
        $('.aiomatic-overlay').hide();
    })
    function aiomaticLoading(btn){
        btn.attr('disabled','disabled');
        if(btn.find('.spinner').length === 0){
            btn.append('<span class="aiomatic-spinner spinner"></span>');
        }
        btn.find('.spinner').css('visibility','unset');
    }
    function aiomaticRmLoading(btn){
        btn.removeAttr('disabled');
        btn.find('.spinner').remove();
    }
    var aiomatic_get_other = $('.aiomatic_get_other');
    var aiomatic_get_finetune = $('.aiomatic_get_finetune');
    var aiomatic_cancel_finetune = $('.aiomatic_cancel_finetune');
    var aiomatic_delete_finetune = $('.aiomatic_delete_finetune');
    aiomatic_cancel_finetune.click(function (){
        var conf = confirm('Are you sure?');
        if(conf) {
            var btn = $(this);
            var id = btn.attr('data-id');
            if (!aiomaticAjaxRunning) {
                aiomaticAjaxRunning = true;
                $.ajax({
                    url: aiomatic_ajax_url,
                    data: {action: 'aiomatic_cancel_finetune', id: id},
                    dataType: 'JSON',
                    type: 'POST',
                    beforeSend: function () {
                        aiomaticLoading(btn);
                    },
                    success: function (res) {
                        aiomaticRmLoading(btn);
                        aiomaticAjaxRunning = false;
                        if (res.status === 'success') {
                            window.location.reload();
                        } else {
                            alert(res.msg);
                        }
                    },
                    error: function (r, s, error) {
                        aiomaticRmLoading(btn);
                        aiomaticAjaxRunning = false;
                        alert('Error in processing finetune cancelling: ' + error);
                    }
                });
            }
        }
    });
    aiomatic_delete_finetune.click(function (){
        var conf = confirm('Are you sure?');
        if(conf) {
            var btn = $(this);
            var id = btn.attr('data-id');
            if (!aiomaticAjaxRunning) {
                aiomaticAjaxRunning = true;
                $.ajax({
                    url: aiomatic_ajax_url,
                    data: {action: 'aiomatic_delete_finetune', id: id},
                    dataType: 'JSON',
                    type: 'POST',
                    beforeSend: function () {
                        aiomaticLoading(btn);
                    },
                    success: function (res) {
                        aiomaticRmLoading(btn);
                        aiomaticAjaxRunning = false;
                        if (res.status === 'success') {
                            window.location.reload();
                        } else {
                            alert(res.msg);
                        }
                    },
                    error: function (r, s, error) {
                        aiomaticRmLoading(btn);
                        aiomaticAjaxRunning = false;
                        alert('Error in processing finetune deletion: ' + error);
                    }
                });
            }
        }
    });
    aiomatic_get_other.click(function (){
        var btn = $(this);
        var id = btn.attr('data-id');
        var type = btn.attr('data-type');
        var aiomaticTitle = btn.text().trim();
        if(!aiomaticAjaxRunning){
            aiomaticAjaxRunning = true;
            $.ajax({
                url: aiomatic_ajax_url,
                data: {action: 'aiomatic_other_finetune', id: id, type: type},
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function (){
                    aiomaticLoading(btn);
                },
                success: function (res){
                    aiomaticRmLoading(btn);
                    aiomaticAjaxRunning = false;
                    if(res.status === 'success'){
                        $('.aiomatic_modal_title').html(aiomaticTitle);
                        $('.aiomatic_modal_content').html(res.html);
                        $('.aiomatic-overlay').show();
                        $('.aiomatic_modal').show();
                    }
                    else{
                        alert(res.msg);
                    }
                },
                error: function (r, s, error){
                    aiomaticRmLoading(btn);
                    aiomaticAjaxRunning = false;
                    alert('Error in processing finetune switching: ' + error);
                }
            });
        }
    });
    $('.aiomatic_sync_finetunes').click(function (){
        var btn = $(this);
        $.ajax({
            url: aiomatic_ajax_url,
            data: {action: 'aiomatic_fetch_finetunes'},
            dataType: 'JSON',
            type: 'POST',
            beforeSend: function (){
                aiomaticLoading(btn);
            },
            success: function (res){
                aiomaticRmLoading(btn);
                if(res.status === 'success'){
                    window.location.reload();
                }
                else{
                    alert(res.msg);
                }
            },
            error: function (r, s, error){
                aiomaticRmLoading(btn);
                alert('Error in processing finetune fetching: ' + error);
            }
        });
    })
});
"use strict";
function aiomatic_call_func()
{
    const model_holder = document.getElementById("model_holder");
    if(model_holder !== null)
    {
        const btn = document.getElementById("aiomatic_toggle_model");
        if(btn !== null)
        {
            if (btn.value === "Show") {
                model_holder.style.display = "block";
                btn.value = "Hide";
            } else {
                model_holder.style.display = "none";
                btn.value = "Show";
            }
        }
        else
        {
            console.log('aiomatic_toggle_model not found');
        }
    }
    else
    {
        console.log('model_holder not found');
    }
}
function aiomatic_prompt_func()
{
    const prompt_holder = document.getElementById("prompt_holder");
    if(model_holder !== null)
    {
        const btn = document.getElementById("aiomatic_toggle_prompt");
        if(btn !== null)
        {
            if (btn.value === "Show") {
                prompt_holder.style.display = "block";
                btn.value = "Hide";
            } else {
                prompt_holder.style.display = "none";
                btn.value = "Show";
            }
        }
        else
        {
            console.log('aiomatic_toggle_prompt not found');
        }
    }
    else
    {
        console.log('prompt_holder not found');
    }
}
function aiomatic_all_empty() {
    var aiomatic_topics = document.getElementById("aiomatic_topics");
    var generate_all = document.getElementById("generate_all");
    var generate_title = document.getElementById("generate_title");
    if(generate_title !== null && generate_all !== null && aiomatic_topics !== null)
    {
        if(aiomatic_topics.value === "") { 
            generate_all.disabled = true; 
            generate_title.disabled = true; 
        } else { 
            generate_all.disabled = false;
            generate_title.disabled = false;
        }
    }
    else
    {
        console.log('generate_all/aiomatic_topics/generate_title not found');
    }
}
function aiomatic_title_empty() {
    var title = document.getElementById("title");
    var generate_sections = document.getElementById("generate_sections");
    var generate_paragraphs = document.getElementById("generate_paragraphs");
    var generate_excerpt = document.getElementById("generate_excerpt");
    var post_publish = document.getElementById("post_publish");
    if(title !== null && generate_sections !== null && generate_paragraphs !== null && generate_excerpt !== null && post_publish !== null && post_content !== null)
    {
        if(title.value === "") { 
            generate_sections.disabled = true; 
            generate_paragraphs.disabled = true; 
            generate_excerpt.disabled = true; 
            post_publish.disabled = true; 
        } else { 
            generate_sections.disabled = false;
            generate_paragraphs.disabled = false;
            generate_excerpt.disabled = false;
            if(window.parent.tinymce.get('post_content').getContent() === "") { 
                post_publish.disabled = true;
            }
            else
            {
                post_publish.disabled = false;
            }
        }
    }
    else
    {
        console.log('title/generate_sections/generate_paragraphs/generate_excerpt/post_publish/post_content not found');
    }
}
function aiomatic_content_empty() {
    var title = document.getElementById("title");
    var post_publish = document.getElementById("post_publish");
    if(title !== null && post_publish !== null && post_content !== null)
    {
        if(title.value === "") { 
            post_publish.disabled = true; 
        } else { 
            if(window.parent.tinymce.get('post_content').getContent() === "") { 
                post_publish.disabled = true;
            }
            else
            {
                post_publish.disabled = false;
            }
        }
    }
    else
    {
        console.log('title/post_publish/post_content not found');
    }
}
function aiomatic_displayTimer(element){
    var start = 1;
    var minutes = 0;
    var extraSeconds = 0;
    var setTimer = setInterval(function () {
        start++;
        minutes = Math.floor(start / 60);
        extraSeconds = start % 60;
        minutes = minutes < 10 ? "0" + minutes : minutes;
        extraSeconds = extraSeconds< 10 ? "0" + extraSeconds : extraSeconds;
        element.val(minutes + ':' + extraSeconds);
    }, 1000);
    return setTimer;
}

function aiomatic_generate_ai_text($, isthis, promptid, thisid, thisres, istiny, noenable, ajaxchain)
{
    var origvar = $(isthis).attr('value');
    var myInterval = aiomatic_displayTimer($(isthis));
    $("#generate_sections").prop( "disabled", true );
    $("#generate_all").prop( "disabled", true );
    $("#generate_title").prop( "disabled", true );
    $("#generate_paragraphs").prop( "disabled", true );
    $("#generate_excerpt").prop( "disabled", true );
    $("#post_publish").prop( "disabled", true );

    var prompt_prompt = $("#" + promptid).val();
    var title = $("#title").val();
    var sections_count = $( "#section_count option:selected" ).text();
    var paragraph_count = $( "#paragraph_count option:selected" ).text();
    var model = $( "#model option:selected" ).text();
    var max_tokens = $("#max_tokens").val();
    var language = $("#language").val();
    var topics = $("#aiomatic_topics").val();
    var sections = $("#post_sections").val();
    var writing_style = $("#writing_style").val();
    var writing_tone = $("#writing_tone").val();
    var temperature = $("#temperature").val();
    $.ajax({
        type: 'POST',
        url: aiomatic_ajax_object.ajax_url,
        data: {
            action: 'aiomatic_write_text',
            prompt: prompt_prompt,
            title: title,
            model: model,
            max_tokens: max_tokens,
            language: language,
            temperature: temperature,
            writing_style: writing_style,
            writing_tone: writing_tone,
            sections_count: sections_count,
            paragraph_count: paragraph_count,
            topics: topics,
            sections: sections,
            nonce: aiomatic_ajax_object.nonce
        },
        success: function(response) {
            if (response.success) {
                if(istiny === true)
                {
                    window.parent.tinymce.get(thisres).setContent(response.data.content);
                }
                else
                {
                    $("#" + thisres).val(response.data.content);
                }
                if(ajaxchain == true)
                {
                    if(promptid == 'prompt_title')
                    {
                        aiomatic_generate_ai_text($, $('#generate_sections'), 'prompt_sections', 'generate_sections', 'post_sections', false, true, true);
                    }
                    else if(promptid == 'prompt_sections')
                    {
                        aiomatic_generate_ai_text($, $('#generate_paragraphs'), 'prompt_content', 'generate_paragraphs', 'post_content', true, true, true);
                    }
                    else if(promptid == 'prompt_content')
                    {
                        aiomatic_generate_ai_text($, $('#generate_excerpt'), 'prompt_excerpt', 'generate_excerpt', 'post_excerpt', false, false, false);
                    }
                }
            } else {
                alert('Error: ' + response.data.message);
            }
            clearInterval(myInterval);
            $("#" + thisid).attr('value', origvar);
            if(noenable !== true)
            {
                $("#generate_sections").prop( "disabled", false );
                $("#generate_all").prop( "disabled", false );
                $("#generate_title").prop( "disabled", false );
                $("#generate_paragraphs").prop( "disabled", false );
                $("#generate_excerpt").prop( "disabled", false );
                aiomatic_title_empty();
            }
        },
        error: function(error) {
            $("#" + thisid).attr('value', origvar);
            $("#generate_sections").prop( "disabled", false );
            $("#generate_all").prop( "disabled", false );
            $("#generate_title").prop( "disabled", false );
            $("#generate_paragraphs").prop( "disabled", false );
            $("#generate_excerpt").prop( "disabled", false );
            aiomatic_title_empty();
        }
    });
}
jQuery(document).ready(function($) { 
    (function ($) {
        $('#aiomatic-dialog').dialog({
          title: 'Post Pulished Successfully',
          dialogClass: 'wp-dialog',
          autoOpen: false,
          draggable: false,
          width: 'auto',
          modal: true,
          resizable: false,
          closeOnEscape: true,
          position: {
            my: "center",
            at: "center",
            of: window
          },
          open: function () {
            $(document).on("click",".ui-widget-overlay", function(){
              $('#aiomatic-dialog').dialog('close');
            });
            $(document).on("click","#aiomatic-close-button", function(){
                $('#aiomatic-dialog').dialog('close');
            });
          },
          create: function () {
            $('.ui-dialog-titlebar-close').addClass('ui-button');
          },
        });
      })(jQuery);

      
    $(document.body).on("click","#aiomatic-success-button", function (e) {
        if (this.getAttribute("adminurl") !== null) {
            if (this.getAttribute("postid") !== null && this.getAttribute("postid") !== '') {
                window.location.href = this.getAttribute("adminurl") + this.getAttribute("postid") + '&action=edit';
            }
            else
            {
                console.log('Incorrect post ID provided!');
            }
        }
        else
        {
            console.log('Incorrect admin URL provided!');
        }
    });
    $('#generate_title').click(function()
    {
        aiomatic_generate_ai_text($, this, 'prompt_title', 'generate_title', 'title', false, false, false);
    });
    $('#generate_sections').click(function()
    {
        aiomatic_generate_ai_text($, this, 'prompt_sections', 'generate_sections', 'post_sections', false, false, false);
    });
    $('#generate_paragraphs').click(function()
    {
        aiomatic_generate_ai_text($, this, 'prompt_content', 'generate_paragraphs', 'post_content', true, false, false);
    });
    $('#generate_excerpt').click(function()
    {
        aiomatic_generate_ai_text($, this, 'prompt_excerpt', 'generate_excerpt', 'post_excerpt', false, false, false);
    });
    $('#generate_all').click(function()
    {
        $(this).attr('value', 'Working...');
        aiomatic_generate_ai_text($, $('#generate_title'), 'prompt_title', 'generate_title', 'title', false, true, true);
        $(this).attr('value', 'Generate All');
    });

    var ed = window.tinyMCE.activeEditor;
    ed.onKeyUp.add(function()
    {
        aiomatic_content_empty();
    })

    $('#aiomatic-single-post').submit(function(event) {
      event.preventDefault();
      var post_publish = document.getElementById("post_publish");
      var title = document.getElementById("title");
      var generate_sections = document.getElementById("generate_sections");
      var generate_paragraphs = document.getElementById("generate_paragraphs");
      var generate_excerpt = document.getElementById("generate_excerpt");
      var form = $(this);
      var title = form.find('#title').val();
      var content = window.parent.tinymce.get('post_content').getContent();
      var excerpt = form.find('#post_excerpt').val();
      var submit_status = form.find('#submit_status').val();
      var post_sticky = form.find('#post_sticky').val();
      var post_date = form.find('#post_date').val();
      var post_author = form.find('#post_author').val();
      var aiomatic_image_id = form.find('#aiomatic_image_id').val();
      var post_category = document.getElementById('post_category').selectedOptions;
      post_category = Array.from(post_category).map(({ value }) => value);
      post_category = JSON.stringify(post_category);
      var post_tags = form.find('#post_tags').val();
      var nonce = form.find('#create_post_nonce').val();
      $("#title").val("");
      window.parent.tinymce.get('post_content').setContent("");
      $("#post_excerpt").val("");
      $("#post_sections").val("");
      if(post_publish !== null && generate_sections !== null && generate_excerpt !== null && generate_paragraphs !== null)
      {
        post_publish.disabled = true; 
        generate_sections.disabled = true;
        generate_excerpt.disabled = true;
        generate_paragraphs.disabled = true; 
      }
      $.ajax({
        type: 'POST',
        url: aiomatic_ajax_object.ajax_url,
        data: {
          action: 'create_post',
          title: title,
          content: content,
          excerpt: excerpt,
          submit_status: submit_status,
          post_sticky: post_sticky,
          post_author: post_author,
          post_date: post_date,
          post_category: post_category,
          post_tags: post_tags,
          aiomatic_image_id: aiomatic_image_id,
          nonce: nonce
        },
        success: function(response) {
          if (response.success) {
            document.getElementById("aiomatic-success-button").setAttribute("postid", response.data.post_id);
            $('#aiomatic-dialog').dialog('open');
          } else {
            alert('Error: ' + response.data.message);
          }
        },
        error: function(error) 
        {
            alert('Error in post publishing: ' + error.responseText);
        }
      });
    });
    aiomatic_all_empty();
    aiomatic_title_empty();
  });
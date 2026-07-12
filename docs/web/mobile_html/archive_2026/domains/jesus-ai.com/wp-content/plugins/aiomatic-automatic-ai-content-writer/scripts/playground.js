"use strict";
jQuery(document).ready(function(){
    (function($) {
        var syncdone1 = sessionStorage.getItem("syncdone1");
        var syncdone2 = sessionStorage.getItem("syncdone2");
        var tabs = $('.nav-tab-wrapper a');
        var activeTab = localStorage.getItem('active-tab') || 'tab-1';
        $('.tab-content').hide();
        $('#' + activeTab).show();
        if( $('#' + activeTab).length ) 
        {
            $('#' + activeTab).show();
        }
        else
        {
            activeTab = 'tab-1';
            $('#' + activeTab).show();
        }
        tabs.removeClass('nav-tab-active');
        $('.nav-tab[href="#' + activeTab + '"]').addClass('nav-tab-active');
        tabs.on('click', function(e) {
            e.preventDefault();
            var tab = $(this).attr('href').substr(1);
            localStorage.setItem('active-tab', tab);
            $('.tab-content').hide();
            $('#' + tab).show();
            tabs.removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            if($("#aiomatic_sync_files").is(":visible"))
            {
                if(syncdone1 === false || syncdone1 === null)
                {
                    sessionStorage.setItem("syncdone1", true);
                    $('.aiomatic_sync_files').click();
                }
            }
            if($("#aiomatic_sync_finetunes").is(":visible"))
            {
                if(syncdone2 === false || syncdone2 === null)
                {
                    sessionStorage.setItem("syncdone2", true);
                    $('.aiomatic_sync_finetunes').click();
                }
            }
        });
    })(jQuery);
});
"use strict";
var initial = '';
function mainChanged()
{
    if(jQuery("#ai_rewriter").val() == 'enabled')
    {            
        jQuery(".hideMain").show();
    }
    else
    {
        jQuery(".hideMain").hide();
    }
}
function mainChanged2()
{
    if(jQuery("#ai_featured_image").val() == 'enabled')
    {            
        jQuery(".hideMain2").show();
    }
    else
    {
        jQuery(".hideMain2").hide();
    }
}
function mainChanged3()
{
    if(jQuery("#append_spintax").val() == 'append' || jQuery("#append_spintax").val() == 'preppend')
    {            
        jQuery(".hideMain3").show();
    }
    else
    {
        jQuery(".hideMain3").hide();
    }
}
function loadMe()
{
    mainChanged();
    toggleCats();
    mainChanged2();
    mainChanged3();
}
window.onload = loadMe;
var unsaved = false;
jQuery(document).ready(function () {
    jQuery(":input").change(function(){
        if (this.id != 'PreventChromeAutocomplete')
            unsaved = true;
    });
    function unloadPage(){ 
        if(unsaved){
            return "You have unsaved changes on this page. Do you want to leave this page and discard your changes or stay on this page?";
        }
    }
    window.onbeforeunload = unloadPage;
});
function toggleCats()
{
    if(jQuery('#hideCats').is(":visible"))
    {            
        jQuery(".hideCats").hide();
    }
    else
    {
        jQuery(".hideCats").show();
    }
}
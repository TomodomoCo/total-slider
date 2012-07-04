/*  Copyright (C) 2011-2012 Peter Upfold.

    This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
// see interface.dev.js for readable source code
var isEditing=false;var isEditingUntitledSlide=false;var editingSlideSortButton=false;var originalTitle=false;var originalBackground=false;var dontStartEdit=false;var newShouldShuffle=false;var deleteCaller=false;var linkToSave='';function isUrl(s){var regexp=/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;return regexp.test(s);}
window.onbeforeunload=function(){if(isEditing)
return _total_slider_L10n.leavePageWouldLoseChanges;}
jQuery(document).ready(function(){jQuery.fn.sortSlides=function(){if(isEditingUntitledSlide!=false){jQuery('#message-area').html('<p>'+_total_slider_L10n.sortWillSaveSoon+'</p>');jQuery('#message-area').fadeIn('slow');newShouldShuffle=true;window.setTimeout(function(){jQuery('#message-area').fadeOut('slow');},7500);}
else{var newSortOrder=jQuery('#slidesort').sortable('serialize');jQuery.ajax({type:'POST',url:VPM_HPS_PLUGIN_URL+'action=newSlideOrder&group='+VPM_HPS_GROUP,data:newSortOrder,success:function(result){if(result.success)
{}},error:function(jqXHR,textStatus,errorThrown)
{var response=jQuery.parseJSON(jqXHR.responseText);var errorToShow='';if(response!=null&&response.error!=null)
{errorToShow=response.error;}
alert(_total_slider_L10n.unableToResortSlides+'\n\n'+errorToShow);}});}}
jQuery('#slidesort').sortable({update:function(event,ui){jQuery().sortSlides();}});jQuery('#slidesort').disableSelection();jQuery('#new-slide-button').click(function(event){event.preventDefault();if(isEditing)
{if(confirm(_total_slider_L10n.switchEditWouldLoseChanges))
{isEditing=false;if(isEditingUntitledSlide){jQuery('#'+isEditingUntitledSlide).remove();}}}
if(!isEditing){jQuery('.slidesort-add-hint').hide();jQuery('#edit-controls').fadeTo(400,1);jQuery('#edit-controls-choose-hint').fadeTo(400,0).hide();jQuery('#slidesort li').removeClass('slidesort-selected');var newIdNo=jQuery('#slidesort').children().length+1;jQuery('#slidesort').append('<li id="slidesort_untitled'+newIdNo+'" style="background: url();" class="slidesort-selected"><div class="slidesort_slidebox" style="background:url();"><div id="slidesort_untitled'+newIdNo+'_text" class="slidesort_text">'+_total_slider_L10n.newSlideTemplateUntitled+'</div><a id="slidesort_'+newIdNo+'_move_button" class="slidesort-icon slide-move-button" href="#">'+_total_slider_L10n.newSlideTemplateMove+'</a><span id="slidesort_'+newIdNo+'_delete" class="slide-delete"><a id="slidesort_untitled'+newIdNo+'_delete_button" class="slidesort-icon slide-delete-button" href="#">'+_total_slider_L10n.newSlideTemplateDelete+'</a></span></div></li>');jQuery('#slidesort_untitled'+newIdNo+'_delete_button').click(function(event){jQuery().deleteSlide(event,this);});jQuery('#slidesort_item'+newIdNo).addClass('slidesort-selected');jQuery('#slidesort').css('width',parseInt(jQuery('#slidesort').css('width'))+180+'px');isEditing=true;isEditingUntitledSlide=jQuery('#slidesort_untitled'+newIdNo).attr('id');editingSlideSortButton=jQuery('#slidesort_untitled'+newIdNo).attr('id');jQuery('#slidesort-container').animate({scrollLeft:parseInt(jQuery('#slidesort').css('width'))-180},1500);jQuery().clearForm();}});jQuery.fn.clearForm=function(){jQuery('#edit-slide-title').val('');jQuery('#edit-slide-description').val('');jQuery('#edit-slide-image-url').val('');jQuery('#edit-slide-link').val('');jQuery('#slide-preview-title').html(_total_slider_L10n.newSlideTemplateUntitled);jQuery('#slide-preview-description').html(_total_slider_L10n.newSlideTemplateNoText);jQuery('#slide-link-internal-id').val('');jQuery('#slide-link-internal-display').html(_total_slider_L10n.slideEditNoPostSelected);jQuery('#slide-link-is-internal').prop('checked',false);jQuery('#slide-link-is-external').prop('checked',false);jQuery('#slide-link-internal-settings').hide();jQuery('#slide-link-external-settings').hide();jQuery('#slide-preview').offset({left:jQuery('#preview-area').offset().left,top:jQuery('#preview-area').offset().top});jQuery('#edit-controls-saving').fadeTo(0,0).hide();jQuery('#edit-controls-save,#edit-controls-cancel').removeAttr('disabled');jQuery('#edit-controls-save').val(_total_slider_L10n.saveButtonValue);linkToSave='';jQuery('#preview-area').css('background','');window.setTimeout(function(){},550);}
jQuery('.edit-controls-inputs').keyup(function(e){isEditing=true;});jQuery.fn.clickSlideObject=function(object){if(dontStartEdit)
return;if(isEditing)
{if(confirm(_total_slider_L10n.switchEditWouldLoseChanges))
{isEditing=false;if(isEditingUntitledSlide){jQuery('#'+isEditingUntitledSlide).remove();}}}
if(!isEditing)
{jQuery('#slidesort li').removeClass('slidesort-selected');jQuery(object).addClass('slidesort-selected');originalTitle=jQuery('#'+jQuery(object).attr('id')+'_text').text();jQuery.ajax({type:'POST',url:VPM_HPS_PLUGIN_URL+'action=getSlide&group='+VPM_HPS_GROUP,data:{'id':jQuery(object).attr('id').substr(jQuery(object).attr('id').indexOf('slidesort_')+10,jQuery(object).attr('id').length)},success:function(result){if(result.error)
{alert(result.error);}
else{jQuery().clearForm();jQuery('#edit-slide-title').val(result.title);jQuery('#edit-slide-description').val(result.description);jQuery('#edit-slide-image-url').val(result.background);if(!isNaN(result.link)&&result.link!=0)
{jQuery('#slide-link-external-settings').hide();jQuery('#slide-link-internal-settings').show('slow');jQuery('#slide-link-is-internal').prop('checked',true);jQuery('#slide-link-internal-id').val(parseInt(result.link));jQuery('#slide-link-internal-display').text(result.link_post_title);}
else if(result.link.length>0){jQuery('#slide-link-internal-settings').hide();jQuery('#slide-link-external-settings').show('slow');jQuery('#slide-link-is-external').prop('checked',true);jQuery('#edit-slide-link').val(result.link);}
else{jQuery('#slide-link-is-internal').prop('checked',false);jQuery('#slide-link-is-external').prop('checked',false);jQuery('#slide-link-internal-settings').hide();jQuery('#slide-link-external-settings').hide();}
jQuery('#slide-preview-title').text(result.title);jQuery('#slide-preview-description').text(result.description);jQuery('#preview-area').css('background','url('+result.background+')');originalBackground=result.background;if(!VPM_SHOULD_DISABLE_XY)
{var containerPos=jQuery('#preview-area').offset();var newLeft=containerPos.left+result.title_pos_x;var newTop=containerPos.top+result.title_pos_y
jQuery('#slide-preview').offset({left:newLeft,top:newTop});}
jQuery('#edit-controls').fadeTo(400,1);jQuery('#edit-controls-choose-hint').fadeTo(400,0).hide();editingSlideSortButton=jQuery(object).attr('id');}},error:function(jqXHR,textStatus,errorThrown){var response=jQuery.parseJSON(jqXHR.responseText);var errorToShow='';if(response!=null&&response.error!=null)
{errorToShow=response.error;}
alert(_total_slider_L10n.unableToGetSlide+'\n\n'+errorToShow);}});}};jQuery('#slidesort li').click(function(){jQuery().clickSlideObject(this);});jQuery.fn.showSavedMessage=function(){jQuery('#message-area').html('<p>'+_total_slider_L10n.slideSaved+'</p>');jQuery('#message-area').fadeIn('slow');window.setTimeout(function(){jQuery('#message-area').fadeOut('slow');jQuery('#message-area').html();},5500);};jQuery('#edit-slide-title').keyup(function(e){jQuery('#slide-preview-title').text(jQuery(this).val());if(jQuery(this).val()=="")
{jQuery('#'+editingSlideSortButton+'_text').text(_total_slider_L10n.newSlideTemplateUntitled);}
else{jQuery('#'+editingSlideSortButton+'_text').text(jQuery(this).val());}});jQuery('#edit-slide-description').keyup(function(e){jQuery('#slide-preview-description').text(jQuery(this).val());});if(!VPM_SHOULD_DISABLE_XY)
{jQuery('#slide-preview').draggable({containment:'#preview-area'});}
else{jQuery('#slide-preview').css('cursor','default');}
jQuery('#edit-slide-image-upload').click(function(){var myTop=jQuery(this).offset();tb_show(_total_slider_L10n.uploadSlideBgImage,'media-upload.php?total-slider-uploader=bgimage&type=image&post_id=0&TB_iframe=true&height=400&width=600');return false;});window.send_to_editor=function(html){imgurl=jQuery('img',html).attr('src');var imgTitle=jQuery('img',html).attr('title');var classes=jQuery('img',html).attr('class').split(' ');var attachmentID=parseInt(classes[classes.length-1].substring(9,classes[classes.length-1].length));jQuery('#edit-slide-image-url').val(imgurl);jQuery('#preview-area').css('background','url('+imgurl+')');jQuery('#'+editingSlideSortButton).children('.slidesort_slidebox').css('background','url('+imgurl+')');tb_remove();}
jQuery('#edit-controls-save').click(function(){var validationErrors=Array();if(jQuery('#edit-slide-title').val().length<1)
{validationErrors[validationErrors.length]=_total_slider_L10n.validationNoSlideTitle;}
if(jQuery('#edit-slide-description').val().length<1)
{validationErrors[validationErrors.length]=_total_slider_L10n.validationNoSlideDescription;}
if(jQuery('#edit-slide-image-url').val().length>1&&!isUrl(jQuery('#edit-slide-image-url').val()))
{validationErrors[validationErrors.length]=_total_slider_L10n.validationInvalidBackgroundURL;}
if(jQuery('#slide-link-is-external').prop('checked')==true)
{if(jQuery('#edit-slide-link').val().length>1&&!isUrl(jQuery('#edit-slide-link').val()))
{validationErrors[validationErrors.length]=_total_slider_L10n.validationInvalidLinkURL;}
linkToSave=jQuery('#edit-slide-link').val();}
if(jQuery('#slide-link-is-internal').prop('checked')==true)
{if(jQuery('#slide-link-internal-id').val().length>1&&isNaN(jQuery('#slide-link-internal-id').val()))
{validationErrors[validationErrors.length]=_total_slider_L10n.validationInvalidLinkID;}
linkToSave=jQuery('#slide-link-internal-id').val();}
if(validationErrors.length>0)
{var errorString=_total_slider_L10n.validationErrorIntroduction;for(var i=0;i<validationErrors.length;i++){errorString+=validationErrors[i]+'\n';}
alert(errorString);return false;}
jQuery('#edit-controls-saving').show().fadeTo(400,1);jQuery('#edit-controls-save,#edit-controls-cancel').prop('disabled','disabled');var calcBoxOffsetLeft=jQuery('#slide-preview').offset().left-jQuery('#preview-area').offset().left;var calcBoxOffsetTop=jQuery('#slide-preview').offset().top-jQuery('#preview-area').offset().top;if(isEditingUntitledSlide){jQuery.ajax({type:'POST',url:VPM_HPS_PLUGIN_URL+'action=createNewSlide&group='+VPM_HPS_GROUP,data:{'title':jQuery('#edit-slide-title').val(),'description':jQuery('#edit-slide-description').val(),'background':jQuery('#edit-slide-image-url').val(),'link':linkToSave,'title_pos_x':calcBoxOffsetLeft,'title_pos_y':calcBoxOffsetTop},success:function(result){if(result.error){alert(result.error);}
else{jQuery('#'+editingSlideSortButton).removeClass('slidesort-selected');jQuery('#'+editingSlideSortButton).click(function(){jQuery().clickSlideObject(this);});jQuery('#'+editingSlideSortButton).attr('id','slidesort_'+result.new_id);jQuery('#'+editingSlideSortButton+'_text').attr('id','slidesort_'+result.new_id+'_text');jQuery('#slidesort_untitled_delete').attr('id','slidesort_'+result.new_id+'_delete');jQuery('#slidesort_untitled_delete_button').attr('id','slidesort_'+result.new_id+'_delete_button');jQuery('#edit-controls').fadeTo(400,0);jQuery('#edit-controls-choose-hint').show().fadeTo(400,1);window.setTimeout(function(){jQuery().clearForm();},750);isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;if(newShouldShuffle)
{newShouldShuffle=false;window.setTimeout(function(){jQuery().sortSlides();},1200);}
newShouldShuffle=false;jQuery().showSavedMessage();}},error:function(jqXHR,textStatus,errorThrown){var response=jQuery.parseJSON(jqXHR.responseText);var errorToShow='';if(response!=null&&response.error!=null)
{errorToShow=response.error;}
alert(_total_slider_L10n.unableToSaveSlide+'\n\n'+response.error);jQuery('#edit-controls-save,#edit-controls-cancel').removeAttr('disabled');jQuery('#edit-controls-save').val(_total_slider_L10n.saveButtonValue);jQuery('#edit-controls-saving').fadeTo(0,0).hide();}});}
else{jQuery.ajax({type:'POST',url:VPM_HPS_PLUGIN_URL+'action=updateSlide&group='+VPM_HPS_GROUP,data:{'id':jQuery('#'+editingSlideSortButton).attr('id').substr(jQuery('#'+editingSlideSortButton).attr('id').indexOf('slidesort_')+10,jQuery('#'+editingSlideSortButton).attr('id').length),'title':jQuery('#edit-slide-title').val(),'description':jQuery('#edit-slide-description').val(),'background':jQuery('#edit-slide-image-url').val(),'link':linkToSave,'title_pos_x':calcBoxOffsetLeft,'title_pos_y':calcBoxOffsetTop},success:function(result){if(result.error){alert(result.error);}
else{jQuery('#'+editingSlideSortButton).removeClass('slidesort-selected');jQuery('#edit-controls').fadeTo(400,0);jQuery('#edit-controls-choose-hint').show().fadeTo(400,1);window.setTimeout(function(){jQuery().clearForm();},750);isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;jQuery().showSavedMessage();}},error:function(jqXHR,textStatus,errorThrown){var response=jQuery.parseJSON(jqXHR.responseText);var errorToShow='';if(response!=null&&response.error!=null)
{errorToShow=response.error;}
alert(_total_slider_L10n.unableToSaveSlide+'\n\n'+errorToShow);jQuery('#edit-controls-save,#edit-controls-cancel').removeAttr('disabled');jQuery('#edit-controls-save').val(_total_slider_L10n.saveButtonValue);jQuery('#edit-controls-saving').fadeTo(0,0).hide();}});}});jQuery('#edit-controls-cancel').click(function(){if(isEditingUntitledSlide)
{if(confirm(_total_slider_L10n.wouldLoseUnsavedChanges))
{jQuery('#'+editingSlideSortButton).remove();jQuery('#edit-controls').fadeTo(400,0);jQuery('#edit-controls-choose-hint').show().fadeTo(400,1);jQuery().clearForm();isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;if(jQuery('#slidesort > li').size()<1)
{jQuery('.slidesort-add-hint').show('slow');}}}
else if(isEditing){if(confirm(_total_slider_L10n.wouldLoseUnsavedChanges))
{jQuery('#edit-controls').fadeTo(400,0);jQuery('#edit-controls-choose-hint').show().fadeTo(400,1);jQuery().clearForm();jQuery('#'+editingSlideSortButton+'_text').text(originalTitle);jQuery('#'+editingSlideSortButton).children('.slidesort_slidebox').css('background','url('+originalBackground+')');jQuery('#'+editingSlideSortButton).removeClass('slidesort-selected');isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;}}
else{if(editingSlideSortButton)
{jQuery('#'+editingSlideSortButton).removeClass('slidesort-selected');}
jQuery('#'+editingSlideSortButton).children('.slidesort_slidebox').css('background','url('+originalBackground+')');jQuery('#edit-controls').fadeTo(400,0);jQuery('#edit-controls-choose-hint').show().fadeTo(400,1);jQuery().clearForm();isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;}});jQuery.fn.deleteSlide=function(event,caller){event.preventDefault();var slideID=jQuery(caller).parent().parent().parent().attr('id');if(!slideID)
alert(_total_slider_L10n.unableToDeleteSlideNoID);slideID=slideID.replace('slidesort_','');if(!slideID)
alert(_total_slider_L10n.unableToDeleteSlideNoID);if(slideID.match(/^untitled/))
{if(confirm(_total_slider_L10n.wouldLoseUnsavedChanges))
{jQuery(caller).parent().parent().fadeTo(350,0);window.setTimeout(function(){jQuery(caller).parent().parent().parent().remove();},380);jQuery('#edit-controls').fadeTo(400,0);jQuery('#edit-controls-choose-hint').show().fadeTo(400,1);jQuery().clearForm();isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;if(jQuery('#slidesort > li').size()<2)
{jQuery('.slidesort-add-hint').show('slow');jQuery('.slidesort-drag-hint').css('visibility','hidden');}}
return;}
if(confirm(_total_slider_L10n.confirmDeleteOperation))
{jQuery(caller).html('deleting&hellip;');deleteCaller=caller;jQuery.ajax({type:'POST',url:VPM_HPS_PLUGIN_URL+'action=deleteSlide&group='+VPM_HPS_GROUP,data:{'id':slideID},success:function(result){jQuery(deleteCaller).parent().parent().fadeTo(350,0);window.setTimeout(function(){jQuery(deleteCaller).parent().parent().parent().remove();},380);jQuery('#edit-controls').fadeTo(400,0);jQuery('#edit-controls-choose-hint').show().fadeTo(400,1);jQuery().clearForm();if(jQuery('#slidesort > li').size()<2)
{jQuery('.slidesort-add-hint').show('slow');jQuery('.slidesort-drag-hint').css('visibility','hidden');}
window.setTimeout(function(){deleteCaller=false;},520);},error:function(jqXHR,textStatus,errorThrown)
{jQuery(deleteCaller).html('Delete');var response=jQuery.parseJSON(jqXHR.responseText);var errorToShow='';if(response!=null&&response.error!=null)
{errorToShow=response.error;}
alert(_total_slider_L10n.unableToDeleteSlide+'\n\n'+errorToShow);deleteCaller=false;}});}
dontStartEdit=true;window.setTimeout(function(){dontStartEdit=false;},500);}
jQuery('.slide-delete-button').click(function(event){jQuery().deleteSlide(event,this);});jQuery('.total-slider-help-point').click(function(event)
{event.preventDefault();jQuery('#contextual-help-link').click();jQuery('#tab-link-total-slider-publishing').children('a:first-child').click();jQuery('body').animate({scrollTop:0},1000);});jQuery('#slide-link-finder').click(function(){jQuery('#slide-link-is-internal').click();if(VPM_SHOULD_WORKAROUND_16655){jQuery('#slide-link-is-internal').prop('checked',false);}
findPosts.open();isEditing=true;});jQuery('#slide-link-is-internal').click(function(){jQuery('#slide-link-external-settings').hide();jQuery('#slide-link-internal-settings').show('fast');isEditing=true;});jQuery('#slide-link-is-external').click(function(){jQuery('#slide-link-internal-settings').hide();jQuery('#slide-link-external-settings').show('fast');isEditing=true;});jQuery('#find-posts-submit').click(function(){jQuery('.found-radio input:checked').each(function(){jQuery('#slide-link-internal-display').text(jQuery('label[for="'+jQuery(this).attr('id')+'"]').text());jQuery('#slide-link-internal-id').val(jQuery(this).val());});findPosts.close();if(VPM_SHOULD_WORKAROUND_16655){jQuery('#slide-link-is-internal').prop('checked',true);}});if(VPM_SHOULD_WORKAROUND_16655){jQuery('#find-posts-close').click(function(){jQuery('#slide-link-is-internal').prop('checked',true);});jQuery('#find-posts-input').keyup(function(e){if(e.which==27){jQuery('#slide-link-is-internal').prop('checked',true);}});}});
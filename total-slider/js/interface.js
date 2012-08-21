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
var isEditing=false;var isEditingUntitledSlide=false;var editingSlideSortButton=false;var originalTitle=false;var originalBackground=false;var originalBackgroundID=false;var dontStartEdit=false;var newShouldShuffle=false;var deleteCaller=false;var linkToSave='';var tplEJS=false;var slidePreviewData={title:_total_slider_L10n.newSlideTemplateUntitled,description:_total_slider_L10n.newSlideTemplateNoText,identifier:'preview',background_url:'',background_attachment_id:0,link:'javascript:;',x:'0',y:'0'};var slidePreviewUntitledData;function isUrl(s){var regexp=/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;return regexp.test(s);}
window.onbeforeunload=function(){if(isEditing)
return _total_slider_L10n.leavePageWouldLoseChanges;}
jQuery(document).ready(function($){slidePreviewUntitledData=$.extend(true,{},slidePreviewData);$('#slide-ejs').each(function(){tplEJS=new EJS({element:'slide-ejs'});tplEJS.update('preview-slide',slidePreviewData);});$.fn.sortSlides=function(){if(isEditingUntitledSlide!=false){$('#message-area').html('<p>'+_total_slider_L10n.sortWillSaveSoon+'</p>');$('#message-area').fadeIn('slow');newShouldShuffle=true;window.setTimeout(function(){$('#message-area').fadeOut('slow');},7500);}
else{var newSortOrder=$('#slidesort').sortable('serialize');$.ajax({type:'POST',url:VPM_HPS_PLUGIN_URL+'action=newSlideOrder&group='+VPM_HPS_GROUP,data:newSortOrder,success:function(result){if(result.success)
{}},error:function(jqXHR,textStatus,errorThrown)
{var response=$.parseJSON(jqXHR.responseText);var errorToShow='';if(response!=null&&response.error!=null)
{errorToShow=response.error;}
alert(_total_slider_L10n.unableToResortSlides+'\n\n'+errorToShow);}});}}
$('#slidesort').sortable({update:function(event,ui){$().sortSlides();}});$('#slidesort').disableSelection();$('#new-slide-button').click(function(event){event.preventDefault();if(isEditing)
{if(confirm(_total_slider_L10n.switchEditWouldLoseChanges))
{isEditing=false;if(isEditingUntitledSlide){$('#'+isEditingUntitledSlide).remove();}}}
if(!isEditing){$('.slidesort-add-hint').hide();$('#edit-controls').fadeTo(400,1);$('#edit-controls-choose-hint').fadeTo(400,0).hide();$('#slidesort li').removeClass('slidesort-selected');var newIdNo=$('#slidesort').children().length+1;$('#slidesort').append('<li id="slidesort_untitled'+newIdNo+'" style="background: url();" class="slidesort-selected"><div class="slidesort_slidebox" style="background:url();"><div id="slidesort_untitled'+newIdNo+'_text" class="slidesort_text">'+_total_slider_L10n.newSlideTemplateUntitled+'</div><a id="slidesort_'+newIdNo+'_move_button" class="slidesort-icon slide-move-button" href="#">'+_total_slider_L10n.newSlideTemplateMove+'</a><span id="slidesort_'+newIdNo+'_delete" class="slide-delete"><a id="slidesort_untitled'+newIdNo+'_delete_button" class="slidesort-icon slide-delete-button" href="#">'+_total_slider_L10n.newSlideTemplateDelete+'</a></span></div></li>');$('#slidesort_untitled'+newIdNo+'_delete_button').click(function(event){$().deleteSlide(event,this);});$('#slidesort_item'+newIdNo).addClass('slidesort-selected');$('#slidesort').css('width',parseInt($('#slidesort').css('width'))+180+'px');isEditing=true;isEditingUntitledSlide=$('#slidesort_untitled'+newIdNo).attr('id');editingSlideSortButton=$('#slidesort_untitled'+newIdNo).attr('id');$('#slidesort-container').animate({scrollLeft:parseInt($('#slidesort').css('width'))-180},1500);$().clearForm();}});$.fn.clearForm=function(){$('#edit-slide-title').val('');$('#edit-slide-description').val('');$('#edit-slide-image-url').val('');$('#edit-slide-link').val('');$('#slide-link-internal-id').val('');$('#slide-link-internal-display').html(_total_slider_L10n.slideEditNoPostSelected);$('#slide-link-is-internal').prop('checked',false);$('#slide-link-is-external').prop('checked',false);$('#slide-link-internal-settings').hide();$('#slide-link-external-settings').hide();slidePreviewData=$.extend(true,{},slidePreviewUntitledData);$().updateSlidePreview();$('#edit-controls-saving').fadeTo(0,0).hide();$('#edit-controls-save,#edit-controls-cancel').removeAttr('disabled');$('#edit-controls-save').val(_total_slider_L10n.saveButtonValue);linkToSave='';}
$('.edit-controls-inputs').keyup(function(e){isEditing=true;});$.fn.clickSlideObject=function(object){if(dontStartEdit)
return;if(isEditing)
{if(confirm(_total_slider_L10n.switchEditWouldLoseChanges))
{isEditing=false;if(isEditingUntitledSlide){$('#'+isEditingUntitledSlide).remove();}}}
if(!isEditing)
{$('#slidesort li').removeClass('slidesort-selected');$(object).addClass('slidesort-selected');originalTitle=$('#'+$(object).attr('id')+'_text').text();$.ajax({type:'POST',url:VPM_HPS_PLUGIN_URL+'action=getSlide&group='+VPM_HPS_GROUP,data:{'id':$(object).attr('id').substr($(object).attr('id').indexOf('slidesort_')+10,$(object).attr('id').length)},success:function(result){if(result.error)
{alert(result.error);}
else{$().clearForm();$('#edit-slide-title').val(result.title);$('#edit-slide-description').val(result.description);if(parseInt(result.background)==result.background)
{$('#edit-slide-image-url').val(result.background_url);}
else{$('#edit-slide-image-url').val(result.background);}
if(!isNaN(result.link)&&result.link!=0)
{$('#slide-link-external-settings').hide();$('#slide-link-internal-settings').show('slow');$('#slide-link-is-internal').prop('checked',true);$('#slide-link-internal-id').val(parseInt(result.link));$('#slide-link-internal-display').text(result.link_post_title);}
else if(result.link.length>0){$('#slide-link-internal-settings').hide();$('#slide-link-external-settings').show('slow');$('#slide-link-is-external').prop('checked',true);$('#edit-slide-link').val(result.link);}
else{$('#slide-link-is-internal').prop('checked',false);$('#slide-link-is-external').prop('checked',false);$('#slide-link-internal-settings').hide();$('#slide-link-external-settings').hide();}
slidePreviewData.title=result.title;slidePreviewData.description=result.description;if(parseInt(result.background)==result.background)
{slidePreviewData.background_url=result.background_url;originalBackground=result.background_url;originalBackgroundID=result.background;slidePreviewData.background_attachment_id=result.background;}
else{slidePreviewData.background_url=result.background;originalBackground=result.background;slidePreviewData.background_attachment_id=0;originalBackgroundID=0;}
if(!VPM_SHOULD_DISABLE_XY)
{slidePreviewData.x=result.title_pos_x;slidePreviewData.y=result.title_pos_y;}
else{slidePreviewData.x=slidePreviewUntitledData.x;slidePreviewData.y=slidePreviewUntitledData.y;}
$('#edit-controls').fadeTo(400,1);$('#edit-controls-choose-hint').fadeTo(400,0).hide();editingSlideSortButton=$(object).attr('id');$().updateSlidePreview();}},error:function(jqXHR,textStatus,errorThrown){var response=$.parseJSON(jqXHR.responseText);var errorToShow='';if(response!=null&&response.error!=null)
{errorToShow=response.error;}
alert(_total_slider_L10n.unableToGetSlide+'\n\n'+errorToShow);}});}};$('#slidesort li').click(function(){$().clickSlideObject(this);});$.fn.showSavedMessage=function(){$('#message-area').html('<p>'+_total_slider_L10n.slideSaved+'</p>');$('#message-area').fadeIn('slow');window.setTimeout(function(){$('#message-area').fadeOut('slow');$('#message-area').html();},5500);};$('#edit-slide-title').keyup(function(e){if($(this).val().length<1)
{slidePreviewData.title=slidePreviewUntitledData.title;$('#'+editingSlideSortButton+'_text').text(_total_slider_L10n.newSlideTemplateUntitled);$().updateSlidePreview();}
else{slidePreviewData.title=$(this).val();$('#'+editingSlideSortButton+'_text').text($(this).val());$().updateSlidePreview();}});$('#edit-slide-description').keyup(function(e){if($(this).val().length<1)
{slidePreviewData.description=slidePreviewUntitledData.description;$().updateSlidePreview();}
else{slidePreviewData.description=$(this).val();$().updateSlidePreview();}});$.fn.updateSlidePreview=function(){$('#preview-var-title').text(slidePreviewData.title);slidePreviewData.title=$('#preview-var-title').text().replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#039;').replace(/</g,'&lt;').replace(/>/g,'&gt;');$('#preview-var-description').text(slidePreviewData.description);slidePreviewData.description=$('#preview-var-description').text().replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#039;').replace(/</g,'&lt;').replace(/>/g,'&gt;');slidePreviewData.x=parseInt(slidePreviewData.x).toString();slidePreviewData.y=parseInt(slidePreviewData.y).toString();slidePreviewData.link=slidePreviewUntitledData.link;if(!isUrl(slidePreviewData.background_url))
{slidePreviewData.background_url='';}
if(typeof tplEJS=='undefined'||tplEJS==false)
{tplEJS=new EJS({element:'slide-ejs'});}
tplEJS.update('preview-slide',slidePreviewData);$().makeDraggable();}
$.fn.makeDraggable=function(){if(!VPM_SHOULD_DISABLE_XY)
{$('.total-slider-template-draggable').draggable({containment:'.total-slider-template-draggable-parent',stop:function(event,ui){var calcBoxOffsetLeft=$('.total-slider-template-draggable').offset().left-$('.total-slider-template-draggable-parent').offset().left;var calcBoxOffsetTop=$('.total-slider-template-draggable').offset().top-$('.total-slider-template-draggable-parent').offset().top;slidePreviewData.x=parseInt(calcBoxOffsetLeft);slidePreviewData.y=parseInt(calcBoxOffsetTop);isEditing=true;},});}
else{$('.total-slider-template-draggable').css('cursor','default');}}
$('#edit-slide-image-upload').click(function(){var myTop=$(this).offset();tb_show(_total_slider_L10n.uploadSlideBgImage,'media-upload.php?total-slider-uploader=bgimage&type=image&post_id=0&TB_iframe=true&height=400&width=600');return false;});window.send_to_editor=function(html){imgurl=$('img',html).attr('src');var imgTitle=$('img',html).attr('title');var classes=$('img',html).attr('class').split(' ');var attachmentID=parseInt(classes[classes.length-1].substring(9,classes[classes.length-1].length));$('#edit-slide-image-url').val(imgurl);slidePreviewData.background_url=imgurl;slidePreviewData.background_attachment_id=attachmentID;$('#'+editingSlideSortButton).children('.slidesort_slidebox').css('background','url('+imgurl+')');tb_remove();$().updateSlidePreview();}
$('#edit-controls-save').click(function(){var validationErrors=Array();if($('#edit-slide-title').val().length<1)
{validationErrors[validationErrors.length]=_total_slider_L10n.validationNoSlideTitle;}
if($('#edit-slide-description').val().length<1)
{validationErrors[validationErrors.length]=_total_slider_L10n.validationNoSlideDescription;}
if($('#edit-slide-image-url').val().length>1&&!isUrl($('#edit-slide-image-url').val()))
{validationErrors[validationErrors.length]=_total_slider_L10n.validationInvalidBackgroundURL;}
if($('#slide-link-is-external').prop('checked')==true)
{if($('#edit-slide-link').val().length>1&&!isUrl($('#edit-slide-link').val()))
{validationErrors[validationErrors.length]=_total_slider_L10n.validationInvalidLinkURL;}
linkToSave=$('#edit-slide-link').val();}
if($('#slide-link-is-internal').prop('checked')==true)
{if($('#slide-link-internal-id').val().length>1&&isNaN($('#slide-link-internal-id').val()))
{validationErrors[validationErrors.length]=_total_slider_L10n.validationInvalidLinkID;}
linkToSave=$('#slide-link-internal-id').val();}
if(validationErrors.length>0)
{var errorString=_total_slider_L10n.validationErrorIntroduction;for(var i=0;i<validationErrors.length;i++){errorString+=validationErrors[i]+'\n';}
alert(errorString);return false;}
$('#edit-controls-saving').show().fadeTo(400,1);$('#edit-controls-save,#edit-controls-cancel').prop('disabled','disabled');var calcBoxOffsetLeft=$('.total-slider-template-draggable').offset().left-$('.total-slider-template-draggable-parent').offset().left;var calcBoxOffsetTop=$('.total-slider-template-draggable').offset().top-$('.total-slider-template-draggable-parent').offset().top;if(isEditingUntitledSlide){$.ajax({type:'POST',url:VPM_HPS_PLUGIN_URL+'action=createNewSlide&group='+VPM_HPS_GROUP,data:{'title':$('#edit-slide-title').val(),'description':$('#edit-slide-description').val(),'background':slidePreviewData.background_attachment_id,'link':linkToSave,'title_pos_x':calcBoxOffsetLeft,'title_pos_y':calcBoxOffsetTop},success:function(result){if(result.error){alert(result.error);}
else{$('#'+editingSlideSortButton).removeClass('slidesort-selected');$('#'+editingSlideSortButton).click(function(){$().clickSlideObject(this);});$('#'+editingSlideSortButton).attr('id','slidesort_'+result.new_id);$('#'+editingSlideSortButton+'_text').attr('id','slidesort_'+result.new_id+'_text');$('#slidesort_untitled_delete').attr('id','slidesort_'+result.new_id+'_delete');$('#slidesort_untitled_delete_button').attr('id','slidesort_'+result.new_id+'_delete_button');$('#edit-controls').fadeTo(400,0);$('#edit-controls-choose-hint').show().fadeTo(400,1);window.setTimeout(function(){$().clearForm();},750);isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;if(newShouldShuffle)
{newShouldShuffle=false;window.setTimeout(function(){$().sortSlides();},1200);}
newShouldShuffle=false;$().showSavedMessage();}},error:function(jqXHR,textStatus,errorThrown){var response=$.parseJSON(jqXHR.responseText);var errorToShow='';if(response!=null&&response.error!=null)
{errorToShow=response.error;}
alert(_total_slider_L10n.unableToSaveSlide+'\n\n'+response.error);$('#edit-controls-save,#edit-controls-cancel').removeAttr('disabled');$('#edit-controls-save').val(_total_slider_L10n.saveButtonValue);$('#edit-controls-saving').fadeTo(0,0).hide();}});}
else{if(slidePreviewData.background_attachment_id)
{backgroundToSave=slidePreviewData.background_attachment_id;}
else{backgroundToSave=$('#edit-slide-image-url').val();}
$.ajax({type:'POST',url:VPM_HPS_PLUGIN_URL+'action=updateSlide&group='+VPM_HPS_GROUP,data:{'id':$('#'+editingSlideSortButton).attr('id').substr($('#'+editingSlideSortButton).attr('id').indexOf('slidesort_')+10,$('#'+editingSlideSortButton).attr('id').length),'title':$('#edit-slide-title').val(),'description':$('#edit-slide-description').val(),'background':backgroundToSave,'link':linkToSave,'title_pos_x':calcBoxOffsetLeft,'title_pos_y':calcBoxOffsetTop},success:function(result){if(result.error){alert(result.error);}
else{$('#'+editingSlideSortButton).removeClass('slidesort-selected');$('#edit-controls').fadeTo(400,0);$('#edit-controls-choose-hint').show().fadeTo(400,1);window.setTimeout(function(){$().clearForm();},750);isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;$().showSavedMessage();}},error:function(jqXHR,textStatus,errorThrown){var response=$.parseJSON(jqXHR.responseText);var errorToShow='';if(response!=null&&response.error!=null)
{errorToShow=response.error;}
alert(_total_slider_L10n.unableToSaveSlide+'\n\n'+errorToShow);$('#edit-controls-save,#edit-controls-cancel').removeAttr('disabled');$('#edit-controls-save').val(_total_slider_L10n.saveButtonValue);$('#edit-controls-saving').fadeTo(0,0).hide();}});}});$('#edit-controls-cancel').click(function(){if(isEditingUntitledSlide)
{if(confirm(_total_slider_L10n.wouldLoseUnsavedChanges))
{$('#'+editingSlideSortButton).remove();$('#edit-controls').fadeTo(400,0);$('#edit-controls-choose-hint').show().fadeTo(400,1);$().clearForm();isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;if($('#slidesort > li').size()<1)
{$('.slidesort-add-hint').show('slow');}}}
else if(isEditing){if(confirm(_total_slider_L10n.wouldLoseUnsavedChanges))
{$('#edit-controls').fadeTo(400,0);$('#edit-controls-choose-hint').show().fadeTo(400,1);$().clearForm();$('#'+editingSlideSortButton+'_text').text(originalTitle);$('#'+editingSlideSortButton).children('.slidesort_slidebox').css('background','url('+originalBackground+')');$('#'+editingSlideSortButton).removeClass('slidesort-selected');isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;}}
else{if(editingSlideSortButton)
{$('#'+editingSlideSortButton).removeClass('slidesort-selected');}
$('#'+editingSlideSortButton).children('.slidesort_slidebox').css('background','url('+originalBackground+')');$('#edit-controls').fadeTo(400,0);$('#edit-controls-choose-hint').show().fadeTo(400,1);$().clearForm();isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;}});$.fn.deleteSlide=function(event,caller){event.preventDefault();var slideID=$(caller).parent().parent().parent().attr('id');if(!slideID)
alert(_total_slider_L10n.unableToDeleteSlideNoID);slideID=slideID.replace('slidesort_','');if(!slideID)
alert(_total_slider_L10n.unableToDeleteSlideNoID);if(slideID.match(/^untitled/))
{if(confirm(_total_slider_L10n.wouldLoseUnsavedChanges))
{$(caller).parent().parent().fadeTo(350,0);window.setTimeout(function(){$(caller).parent().parent().parent().remove();},380);$('#edit-controls').fadeTo(400,0);$('#edit-controls-choose-hint').show().fadeTo(400,1);$().clearForm();isEditing=false;isEditingUntitledSlide=false;editingSlideSortButton=false;if($('#slidesort > li').size()<2)
{$('.slidesort-add-hint').show('slow');$('.slidesort-drag-hint').css('visibility','hidden');}}
return;}
if(confirm(_total_slider_L10n.confirmDeleteOperation))
{$(caller).html('deleting&hellip;');deleteCaller=caller;$.ajax({type:'POST',url:VPM_HPS_PLUGIN_URL+'action=deleteSlide&group='+VPM_HPS_GROUP,data:{'id':slideID},success:function(result){$(deleteCaller).parent().parent().fadeTo(350,0);window.setTimeout(function(){$(deleteCaller).parent().parent().parent().remove();},380);$('#edit-controls').fadeTo(400,0);$('#edit-controls-choose-hint').show().fadeTo(400,1);$().clearForm();if($('#slidesort > li').size()<2)
{$('.slidesort-add-hint').show('slow');$('.slidesort-drag-hint').css('visibility','hidden');}
window.setTimeout(function(){deleteCaller=false;},520);},error:function(jqXHR,textStatus,errorThrown)
{$(deleteCaller).html('Delete');var response=$.parseJSON(jqXHR.responseText);var errorToShow='';if(response!=null&&response.error!=null)
{errorToShow=response.error;}
alert(_total_slider_L10n.unableToDeleteSlide+'\n\n'+errorToShow);deleteCaller=false;}});}
dontStartEdit=true;window.setTimeout(function(){dontStartEdit=false;},500);}
$('.slide-delete-button').click(function(event){$().deleteSlide(event,this);});$('.total-slider-help-point').click(function(event)
{event.preventDefault();$('#contextual-help-link').click();$('#tab-link-total-slider-publishing').children('a:first-child').click();$('body').animate({scrollTop:0},1000);});$('#template-switch-form').submit(function(e){if(isEditing)
{alert(_total_slider_L10n.mustFinishEditingFirst);e.preventDefault();return false;}
if(!confirm(_total_slider_L10n.templateChangeWouldLoseData))
{e.preventDefault();return false;}});$('#slide-link-finder').click(function(){$('#slide-link-is-internal').click();if(VPM_SHOULD_WORKAROUND_16655){$('#slide-link-is-internal').prop('checked',false);}
findPosts.open();isEditing=true;});$('#slide-link-is-internal').click(function(){$('#slide-link-external-settings').hide();$('#slide-link-internal-settings').show('fast');isEditing=true;});$('#slide-link-is-external').click(function(){$('#slide-link-internal-settings').hide();$('#slide-link-external-settings').show('fast');isEditing=true;});$('#find-posts-submit').click(function(){$('.found-radio input:checked').each(function(){$('#slide-link-internal-display').text($('label[for="'+$(this).attr('id')+'"]').text());$('#slide-link-internal-id').val($(this).val());});findPosts.close();if(VPM_SHOULD_WORKAROUND_16655){$('#slide-link-is-internal').prop('checked',true);}});if(VPM_SHOULD_WORKAROUND_16655){$('#find-posts-close').click(function(){$('#slide-link-is-internal').prop('checked',true);});$('#find-posts-input').keyup(function(e){if(e.which==27){$('#slide-link-is-internal').prop('checked',true);}});}
$().makeDraggable();});
/*  Copyright (C) 2011-2013 Peter Upfold.

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

var isEditing = false;
var isEditingUntitledSlide = false;
var editingSlideSortButton = false;
var originalTitle = false;
var originalBackground = false;
var originalBackgroundID = false;
var dontStartEdit = false;
var newShouldShuffle = false;
var deleteCaller = false;
var linkToSave = '';
var tplEJS = false;

var slidePreviewData = {

		title: _total_slider_L10n.newSlideTemplateUntitled,
		description: _total_slider_L10n.newSlideTemplateNoText,
		identifier: 'preview',
		background_url: '',
		background_attachment_id: 0,
		link: 'javascript:;',
		x: '0',
		y: '0'
	
};

var slidePreviewUntitledData;

if ( 'elvin' == _total_slider_uploader ) {
	var upl_frame = false;
}

/* language is now done by total_slider.php:jsL10n() */

/* !Miscellaneous functions */
function isUrl(s) {
	var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
	return regexp.test(s);
}

window.onbeforeunload = function() {
	if (isEditing)
	    return _total_slider_L10n.leavePageWouldLoseChanges;
}

jQuery(document).ready(function($) {

	/* !Preserve slidePreviewUntitledData */
	slidePreviewUntitledData = $.extend(true, {}, slidePreviewData);
	
	/* !Instantiate and initially render preview area */
	$('#slide-ejs').each(function() {
		tplEJS = new EJS({element: 'slide-ejs'});
		tplEJS.update('preview-slide', slidePreviewData );
	});

	$.fn.sortSlides = function () {
	
		/* 
			The user has performed a sort and has dropped an object, 
			or we have just saved a new untitled slide which may have
			been resorted, so we should update the shuffle.
		
		*/
			
		if (isEditingUntitledSlide != false) {
			$('#message-area').html('<p>' + _total_slider_L10n.sortWillSaveSoon + '</p>');
			$('#message-area').fadeIn('slow');
			
			newShouldShuffle = true;
			
			window.setTimeout(function() { $('#message-area').fadeOut('slow'); }, 7500);
			
		}
		else {
			
			var newSortOrder = $('#slidesort').sortable('serialize');
			
			$.ajax({
			
				type: 'POST',
				url: VPM_HPS_PLUGIN_URL + 'action=newSlideOrder&group=' + VPM_HPS_GROUP,
				data: newSortOrder,
				
				success: function(result) {
					if (result.success)
					{
						/*
							// show saved notification
						$('#message-area').css('background-color', '#8cff84');
						$('#message-area').html('Slide order saved.');
						$('#message-area').show('slow');
						window.setTimeout(function() {
							$('#message-area').hide('slow');
						}, 2500);
						*/
					}
				},
				error: function(jqXHR, textStatus, errorThrown)
				{
					var response = $.parseJSON(jqXHR.responseText);
					var errorToShow = '';
					
					if (response != null && response.error != null)
					{
						errorToShow = response.error;
					}
				
					alert(_total_slider_L10n.unableToResortSlides + '\n\n' + errorToShow);												
				}				
			});
			
		}

	
	}

	$('#slidesort').sortable({
	
		update: function(event, ui) {
		
				$().sortSlides();	
		}
	
	});
	
	
	$('#slidesort').disableSelection();
	
	/* !Click the 'add new' button */
	$('#new-slide-button').click(function(event) {
	
		event.preventDefault();
	
		if (isEditing)
		{
			if (confirm(_total_slider_L10n.switchEditWouldLoseChanges))
			{
				isEditing = false;
				
				if (isEditingUntitledSlide) {
					$('#' + isEditingUntitledSlide).remove();				
				}
				
			}
		}
	
		if (!isEditing) {
		

			$('.slidesort-add-hint').hide();

			$('#edit-controls').fadeTo(400, 1);
			$('#edit-controls-choose-hint').fadeTo(400,0).hide();
			// make all deselected
			$('#slidesort li').removeClass('slidesort-selected');
			
			var newIdNo = $('#slidesort').children().length+1;
			
			// create a new button
			$('#slidesort').append('<li id="slidesort_untitled'  + newIdNo + '" style="background: url();" class="slidesort-selected"><div class="slidesort_slidebox" style="background:url();"><div id="slidesort_untitled'  + newIdNo + '_text" class="slidesort_text">' + _total_slider_L10n.newSlideTemplateUntitled + '</div><a id="slidesort_'  + newIdNo + '_move_button" class="slidesort-icon slide-move-button" href="#">' + _total_slider_L10n.newSlideTemplateMove + '</a><span id="slidesort_'  + newIdNo + '_delete" class="slide-delete"><a id="slidesort_untitled'  + newIdNo + '_delete_button" class="slidesort-icon slide-delete-button" href="#">' + _total_slider_L10n.newSlideTemplateDelete + '</a></span></div></li>');			
			
			// hook up new pseudo-delete button
			$('#slidesort_untitled' + newIdNo + '_delete_button').click(function (event) {
				$().deleteSlide(event, this);
			});
			
			$('#slidesort_item' + newIdNo).addClass('slidesort-selected');
			
			// ensure width of slide sorting area is large enough
			$('#slidesort').css('width', parseInt($('#slidesort').css('width')) + 180 + 'px');
			
			isEditing = true;
			isEditingUntitledSlide = $('#slidesort_untitled' + newIdNo).attr('id');
			editingSlideSortButton = $('#slidesort_untitled' + newIdNo).attr('id');
			
			// scroll to the end of the slidesort view
			$('#slidesort-container').animate({ scrollLeft: parseInt($('#slidesort').css('width')) - 180 }, 1500);
			
			$().clearForm();
			
		}
	
	});
	
	$.fn.clearForm = function () {
	/*
		Clear the form, ready for a new untitled slide
		(or later populating).
	*/
				
		// clear the form
		$('#edit-slide-title').val('');
		$('#edit-slide-description').val('');
		$('#edit-slide-image-url').val('');
		$('#edit-slide-link').val('');
		$('#slide-link-internal-id').val('');
		$('#slide-link-internal-display').html(_total_slider_L10n.slideEditNoPostSelected);
		$('#slide-link-is-internal').prop('checked', false);
		$('#slide-link-is-external').prop('checked', false);
		$('#slide-link-internal-settings').hide();
		$('#slide-link-external-settings').hide();	
		
		// reset preview area
		slidePreviewData = $.extend(true, {}, slidePreviewUntitledData);
		$().updateSlidePreview();
		
		$('#edit-controls-saving').fadeTo(0,0).hide();
		
		$('#edit-controls-save,#edit-controls-cancel').removeAttr('disabled');
		$('#edit-controls-save').val(_total_slider_L10n.saveButtonValue);
		
		linkToSave = '';
	
	}
	
	/* any form editing performed, set inEditing to true */
	$('.edit-controls-inputs').keyup(function(e) {
		isEditing = true;
	});
	
	/* click on a slide in the resortable list to select it for editing */

	/* this must be an fn. child function because it must be ready to bind both to
		#slidesort li, and to manually bind it to new slidesort objects
		as they are created. They don't get the event binding automatically.
	*/
	
	$.fn.clickSlideObject = function(object) {

		if (dontStartEdit)
			return;
	
		if (isEditing)
		{
			if (confirm(_total_slider_L10n.switchEditWouldLoseChanges))
			{
				isEditing = false;
				
				if (isEditingUntitledSlide) {
					$('#' + isEditingUntitledSlide).remove();				
				}
				
			}	
		
		}
		
		if (!isEditing)
		{
			
			// make all deselected
			$('#slidesort li').removeClass('slidesort-selected');
					
			// now make me selected
			$(object).addClass('slidesort-selected');
			
			// save the original title in case of cancel
			originalTitle = $('#' + $(object).attr('id') + '_text').text();
			
			// get the data
			$.ajax({
				type: 'POST',
				url: VPM_HPS_PLUGIN_URL + 'action=getSlide&group=' + VPM_HPS_GROUP,
				data: {
					'id': $(object).attr('id').substr( $(object).attr('id').indexOf('slidesort_')+10, $(object).attr('id').length )
				},
				
				success: function(result) {
	
					if (result.error)
					{
						alert(result.error);
					}
					else {
					
						// let's get the form cookin'
						
						$().clearForm(); //for good measure
						
						// fill the fields
						$('#edit-slide-title').val(result.title);
						$('#edit-slide-description').val(result.description);

						if (parseInt(result.background) == result.background)
						{
							$('#edit-slide-image-url').val(result.background_url);
						}
						else {
							$('#edit-slide-image-url').val(result.background);
						}
						
						// if link is numeric, then load in the post
						if (!isNaN(result.link) && result.link != 0)
						{
							$('#slide-link-external-settings').hide();
							$('#slide-link-internal-settings').show('slow');
							$('#slide-link-is-internal').prop('checked', true);
							$('#slide-link-internal-id').val(parseInt(result.link));
							$('#slide-link-internal-display').text(result.link_post_title);
							
						}
						else if (result.link.length > 0) {
							$('#slide-link-internal-settings').hide();
							$('#slide-link-external-settings').show('slow');
							$('#slide-link-is-external').prop('checked', true);
							$('#edit-slide-link').val(result.link);
						}
						else {
							$('#slide-link-is-internal').prop('checked', false);
							$('#slide-link-is-external').prop('checked', false);
							$('#slide-link-internal-settings').hide();
							$('#slide-link-external-settings').hide();
						}
						
						slidePreviewData.title = result.title;
						slidePreviewData.description = result.description;
						
						// put the background image on
						if (parseInt(result.background) == result.background)
						{
							// it is an attachment ID-based background
							slidePreviewData.background_url = result.background_url;
							originalBackground = result.background_url;
							originalBackgroundID = result.background;
							slidePreviewData.background_attachment_id = result.background;
							
						}
						else {
							// it is a URL-based background
							slidePreviewData.background_url = result.background;
							originalBackground = result.background;
							slidePreviewData.background_attachment_id = 0;
							originalBackgroundID = 0;
						}
						
						// restore the pos x and pos y of the slide preview box
						if (!VPM_SHOULD_DISABLE_XY)
						{
							slidePreviewData.x = result.title_pos_x;
							slidePreviewData.y = result.title_pos_y;
						}
						else {
							slidePreviewData.x = slidePreviewUntitledData.x;
							slidePreviewData.y = slidePreviewUntitledData.y;
						}
						
						// ok, do the grand unveiling
						$('#edit-controls').fadeTo(400, 1);
						$('#edit-controls-choose-hint').fadeTo(400,0).hide();
						
						editingSlideSortButton = $(object).attr('id');
						
						$().updateSlidePreview();
						
					}				
				},
				error: function(jqXHR, textStatus, errorThrown) {
				
					var response = $.parseJSON(jqXHR.responseText);
					var errorToShow = '';
					
					if (response != null && response.error != null)
					{
						errorToShow = response.error;
					}
				
					alert(_total_slider_L10n.unableToGetSlide + '\n\n' + errorToShow);				
				}
			
			});
		} // end else
		
	
	};
	// glue for previous -- for objects that are there at pageload-time
	$('#slidesort li').click(function () {
		$().clickSlideObject(this);
	});
	
	/* show saved message */
	$.fn.showSavedMessage = function() {
	
		$('#message-area').html('<p>' + _total_slider_L10n.slideSaved + '</p>');
		$('#message-area').fadeIn('slow');
		window.setTimeout(function() {
			$('#message-area').fadeOut('slow');
			$('#message-area').html();
		
		}, 5500);
	
	};
	
	/* !Update slide title as typed */
	$('#edit-slide-title').keyup(function(e) {
	
		if ($(this).val().length < 1)
		{
			slidePreviewData.title = slidePreviewUntitledData.title;
			if ($('#edit-slide-description').val().length < 1)
			{
				slidePreviewData.description = slidePreviewUntitledData.description;
			}
			else {
				slidePreviewData.description = $('#edit-slide-description').val();
			}
			$('#' + editingSlideSortButton + '_text').text(_total_slider_L10n.newSlideTemplateUntitled);
			$().updateSlidePreview();
		}
		else {
			slidePreviewData.title = $(this).val();
			$('#' + editingSlideSortButton + '_text').text($(this).val());
			if ($('#edit-slide-description').val().length < 1)
			{
				slidePreviewData.description = slidePreviewUntitledData.description;
			}
			else {
				slidePreviewData.description = $('#edit-slide-description').val();
			}
			$().updateSlidePreview();
		}		

	});
	
	/* !Update slide description as typed */
	$('#edit-slide-description').keyup(function(e) {

		if ($(this).val().length < 1)
		{
			slidePreviewData.description = slidePreviewUntitledData.description;
			if ($('#edit-slide-title').val().length < 1)
			{
				slidePreviewData.title = slidePreviewUntitledData.title;
			} else {
				slidePreviewData.title = $('#edit-slide-title').val();
			}
			$().updateSlidePreview();
		}
		else {
			slidePreviewData.description = $(this).val();
			if ($('#edit-slide-title').val().length < 1)
			{
				slidePreviewData.title = slidePreviewUntitledData.title;
			} else {
				slidePreviewData.title = $('#edit-slide-title').val();
			}
			$().updateSlidePreview();
		}
	});
	
	/* !Update the preview rendering */
	$.fn.updateSlidePreview = function() {
		// expects slidePreviewData to be a JSON object with the relevant tokens and
		// their values
	
		// Validation
		
		/*
		We do a little dance here, pushing the values for description and title
		into the preview-var-* div's text(), then pulling the text() back out.
		This is to help neuter any HTML or JS that might otherwise be potent, so we
		don't unintentionally inject executable data into the EJS rendering.
		*/
		
		// we are doing a PHP htmlspecialchars() imitation as well
		
		$('#preview-var-title').text(slidePreviewData.title);
		slidePreviewData.title = $('#preview-var-title').text().replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		
		$('#preview-var-description').text(slidePreviewData.description);
		slidePreviewData.description = $('#preview-var-description').text().replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		
		// for the X, Y and URL, there are known good formats against which we validate
		slidePreviewData.x = parseInt(slidePreviewData.x).toString();
		slidePreviewData.y = parseInt(slidePreviewData.y).toString();
		
		slidePreviewData.link = slidePreviewUntitledData.link; // slide link always completely neutered
		
		if (!isUrl(slidePreviewData.background_url))
		{
			slidePreviewData.background_url = '';
		}

	
		if (typeof tplEJS == 'undefined' || tplEJS == false)
		{
			tplEJS = new EJS({element: 'slide-ejs'});
		}
		
		tplEJS.update('preview-slide', slidePreviewData );
		
		$().makeDraggable();
				
	}
	
	$.fn.makeDraggable = function() {
	/* Make the preview slide in the edit area draggable */
		if (!VPM_SHOULD_DISABLE_XY)
		{
			$('.total-slider-template-draggable').draggable({
				containment: '.total-slider-template-draggable-parent',
				stop: function(event, ui) {
					// recalculate offsets and update our slidePreviewData for refreshing state properly
					var calcBoxOffsetLeft = $('.total-slider-template-draggable').offset().left - $('.total-slider-template-draggable-parent').offset().left;
					var calcBoxOffsetTop  = $('.total-slider-template-draggable').offset().top - $('.total-slider-template-draggable-parent').offset().top;
					
					slidePreviewData.x = parseInt(calcBoxOffsetLeft);
					slidePreviewData.y = parseInt(calcBoxOffsetTop);
					
					isEditing = true;
					//console.log("x = " + slidePreviewData.x + ", y = " + slidePreviewData.y);
					
				},
			});
		}
		else {
		/* or disable the drag cursor if X/Y positioned is disabled in template */
			$('.total-slider-template-draggable').css('cursor', 'default');
		}
	}
	
	/* !Trigger the upload thickbox for the background image */
	
	$('#edit-slide-image-upload').click(function (e) {	

		e.preventDefault();
		var myTop = $(this).offset();

		if ( 'legacy' == _total_slider_uploader ) {

			tb_show(_total_slider_L10n.uploadSlideBgImage, 'media-upload.php?total-slider-uploader=bgimage&total-slider-slide-group-template=' + encodeURIComponent(VPM_SLIDE_GROUP_TEMPLATE) + '&total-slider-slide-group-template-location=' + encodeURIComponent(VPM_SLIDE_GROUP_TEMPLATE_LOCATION) + '&type=image&post_id=0&TB_iframe=true&height=400&width=600');
		
			return false;
		}
		else if ( 'elvin' == _total_slider_uploader ) {
			
			if ( upl_frame ) {
				upl_frame.open();
				return false;
			}

			upl_frame = wp.media.frames.file_frame = wp.media({
				title: _total_slider_L10n.uploadSlideBgImage,
				button: {
					text: _total_slider_L10n.uploadSlideBgButtonText,
				},
				multiple: false,
			});

			upl_frame.on( 'select', function() { 
				attachment = upl_frame.state().get('selection').first().toJSON();
				$('#edit-slide-image-url').val( attachment.url );
				slidePreviewData.background_url = attachment.url;
				slidePreviewData.background_attachment_id = attachment.id;
				$('#' + editingSlideSortButton).children('.slidesort_slidebox').css('background', 'url(' + attachment.url + ')');

				slidePreviewData.title = $('#edit-slide-title').val();
				slidePreviewData.description = $('#edit-slide-description').val();
				$().updateSlidePreview();

			});

			upl_frame.open();

		}
	
	});
	
	/* !Uploader has returned from uploading */
	window.send_to_editor = function(html) {
	
		imgurl = $('img',html).attr('src');
		var imgTitle = $('img',html).attr('title');
		
		// attempt to extract the image attachment ID
		var classes = $('img', html).attr('class').split(' ');
		var attachmentID = parseInt( classes[classes.length - 1].substring(9, classes[classes.length - 1].length) );
		
		$('#edit-slide-image-url').val(imgurl);
		//$('#edit-slide-image-title').text(imgTitle);
		
		// update the preview to show this background
		slidePreviewData.background_url = imgurl;
		slidePreviewData.background_attachment_id = attachmentID;
		
		$('#' + editingSlideSortButton).children('.slidesort_slidebox').css('background', 'url(' + imgurl + ')');
		
		tb_remove();
		
		slidePreviewData.title = $('#edit-slide-title').val();
		slidePreviewData.description = $('#edit-slide-description').val();
		$().updateSlidePreview();
		
	}
	
	/* !Save button -- create a new, or update an existing slide */
	$('#edit-controls-save').click(function() {
	
		// validate data
		
		var validationErrors = Array();
		
		if ($('#edit-slide-title').val().length < 1)
		{ // blank title
			validationErrors[validationErrors.length] = _total_slider_L10n.validationNoSlideTitle;
		}
		if ($('#edit-slide-description').val().length < 1)
		{ // blank description
			validationErrors[validationErrors.length] = _total_slider_L10n.validationNoSlideDescription;
		}
		if ($('#edit-slide-image-url').val().length > 1 && !isUrl($('#edit-slide-image-url').val()))
		{	// if we have a background URL set, but it is not a proper URL
			validationErrors[validationErrors.length] = _total_slider_L10n.validationInvalidBackgroundURL;
		}
		
		
		if ($('#slide-link-is-external').prop('checked') == true)
		{
		
			if ($('#edit-slide-link').val().length > 1 && !isUrl($('#edit-slide-link').val()))
			{	// if we have an external link URL set, but it is not a proper URL
				validationErrors[validationErrors.length] = _total_slider_L10n.validationInvalidLinkURL;
			}		
			
			linkToSave = $('#edit-slide-link').val();
		}
		
		// check for valid number in the internal link post ID column
		if ($('#slide-link-is-internal').prop('checked') == true)
		{
			if ($('#slide-link-internal-id').val().length > 1 && isNaN($('#slide-link-internal-id').val()))
			{
				validationErrors[validationErrors.length] = _total_slider_L10n.validationInvalidLinkID;
			}
			
			linkToSave = $('#slide-link-internal-id').val();
			
		}
		
		// X/Y bounds??
		
		if (validationErrors.length > 0)
		{
			var errorString = _total_slider_L10n.validationErrorIntroduction;
			
			for (var i = 0; i < validationErrors.length; i++) {
				errorString += validationErrors[i] + '\n';
			}
			
			alert(errorString);
			return false;		
			
		}
		
		$('#edit-controls-saving').show().fadeTo(400,1);
		
		$('#edit-controls-save,#edit-controls-cancel').prop('disabled', 'disabled');
		// $('#edit-controls-save').val('Saving');
		
		if ($('.total-slider-template-draggable').length < 1 || $('.total-slider-template-draggable-parent').length < 1)
		{
			var calcBoxOffsetLeft = 0;
			var calcBoxOffsetTop = 0;
		}
		else {
			var calcBoxOffsetLeft = $('.total-slider-template-draggable').offset().left - $('.total-slider-template-draggable-parent').offset().left;
			var calcBoxOffsetTop  = $('.total-slider-template-draggable').offset().top - $('.total-slider-template-draggable-parent').offset().top;
		}
			
		if (isEditingUntitledSlide) {
		
			// create new slide
			$.ajax({
				type: 'POST',
				url: VPM_HPS_PLUGIN_URL + 'action=createNewSlide&group=' + VPM_HPS_GROUP,
				data: {
					'title': $('#edit-slide-title').val(),
					'description': $('#edit-slide-description').val(),
					'background': slidePreviewData.background_attachment_id,
					'link': linkToSave,
					'title_pos_x': calcBoxOffsetLeft,
					'title_pos_y': calcBoxOffsetTop						
				},
				
				success: function(result) {
				
					if (result.error) {
						alert(result.error);
					}
					else {
				
					$('#' + editingSlideSortButton).removeClass('slidesort-selected');
					$('#' + editingSlideSortButton).click(function() { $().clickSlideObject(this); } );
					$('#' + editingSlideSortButton).attr('id', 'slidesort_' + result.new_id);
					 
					// update other IDs too
					$('#' + editingSlideSortButton + '_text').attr('id', 'slidesort_' + result.new_id + '_text');
					$('#slidesort_untitled_delete').attr('id', 'slidesort_' + result.new_id + '_delete');
					$('#slidesort_untitled_delete_button').attr('id', 'slidesort_' + result.new_id + '_delete_button');					
					
					$('#edit-controls').fadeTo(400, 0);
					$('#edit-controls-choose-hint').show().fadeTo(400,1);
					window.setTimeout(function() { $().clearForm(); }, 750);

						
					
					isEditing = false;
					isEditingUntitledSlide = false;
					editingSlideSortButton = false;
					
					// trigger a shuffle update with the new order, if changed, of this new item
					if (newShouldShuffle)
					{
						newShouldShuffle = false;
						window.setTimeout(function() { $().sortSlides(); }, 1200);
					}
					
					newShouldShuffle = false;
					
					$().showSavedMessage();
					
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
				
					var response = $.parseJSON(jqXHR.responseText);
					var errorToShow = '';
					
					if (response != null && response.error != null)
					{
						errorToShow = response.error;
					}
					
					alert(_total_slider_L10n.unableToSaveSlide + '\n\n' + response.error);
					
					$('#edit-controls-save,#edit-controls-cancel').removeAttr('disabled');
					$('#edit-controls-save').val(_total_slider_L10n.saveButtonValue);
					$('#edit-controls-saving').fadeTo(0,0).hide();		
							
				}
				
			});
		
		}
		
		else {
			
			// update existing slide
			
			if (slidePreviewData.background_attachment_id)
			{
				backgroundToSave = slidePreviewData.background_attachment_id;				
			}
			else {
				backgroundToSave = 	$('#edit-slide-image-url').val();			
			}
			
			$.ajax({
				type: 'POST',
				url: VPM_HPS_PLUGIN_URL + 'action=updateSlide&group=' + VPM_HPS_GROUP,
				data: {
					'id': $('#' + editingSlideSortButton).attr('id').substr( $('#' + editingSlideSortButton).attr('id').indexOf('slidesort_')+10, $('#' + editingSlideSortButton).attr('id').length ),
					'title': $('#edit-slide-title').val(),
					'description': $('#edit-slide-description').val(),
					'background': backgroundToSave,
					'link': linkToSave,
					'title_pos_x': calcBoxOffsetLeft,
					'title_pos_y': calcBoxOffsetTop									
				},
				
				success: function(result) {
				
					if (result.error) {
						alert(result.error);
					}
					else {
						$('#' + editingSlideSortButton).removeClass('slidesort-selected');
						$('#edit-controls').fadeTo(400, 0);
						$('#edit-controls-choose-hint').show().fadeTo(400,1);
						window.setTimeout(function() { $().clearForm(); }, 750);
						
						isEditing = false;
						isEditingUntitledSlide = false;
						editingSlideSortButton = false;
						
						$().showSavedMessage();
						
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
				
					var response = $.parseJSON(jqXHR.responseText);
					var errorToShow = '';
					
					if (response != null && response.error != null)
					{
						errorToShow = response.error;
					}
									
					alert(_total_slider_L10n.unableToSaveSlide + '\n\n' + errorToShow);

					$('#edit-controls-save,#edit-controls-cancel').removeAttr('disabled');
					$('#edit-controls-save').val(_total_slider_L10n.saveButtonValue);
					$('#edit-controls-saving').fadeTo(0,0).hide();					
									
				}
				
			});
		
		}
	
	});
	
	/* Cancel with esc key */
	/*$(document).keyup(function(event) {
	  if (event.keyCode == 27) { $('#edit-controls-cancel').click(); }
	});*/
	
	
	/* !Cancel button -- cancel edits, or cancel new item */
	$('#edit-controls-cancel').click(function() {
	
		if (isEditingUntitledSlide)
		{
			// editing untitled, new slide
		
			if (confirm(_total_slider_L10n.wouldLoseUnsavedChanges))
			{
				$('#' + editingSlideSortButton).remove();
				$('#edit-controls').fadeTo(400, 0);
				$('#edit-controls-choose-hint').show().fadeTo(400,1);
				$().clearForm();
				
				isEditing = false;
				isEditingUntitledSlide = false;
				editingSlideSortButton = false;
				
				
				if ($('#slidesort > li').size() < 1)
				{
					// bring up the help text if zero left after that delete
					$('.slidesort-add-hint').show('slow');
				}
			
			}			
		
		}
		else if (isEditing) {
			// editing existing slide
			
			if (confirm(_total_slider_L10n.wouldLoseUnsavedChanges))
			{
			
				$('#edit-controls').fadeTo(400, 0);
				$('#edit-controls-choose-hint').show().fadeTo(400,1);
				$().clearForm();				
				
				$('#' + editingSlideSortButton + '_text').text(originalTitle); // restore pre-edit title
				$('#' + editingSlideSortButton).children('.slidesort_slidebox').css('background', 'url(' + originalBackground + ')'); // restore pre-edit background
				$('#' + editingSlideSortButton).removeClass('slidesort-selected'); // unselect
				
				isEditing = false;
				isEditingUntitledSlide = false;
				editingSlideSortButton = false;
			
			}
			
		
		}
		else { // not editing at all
		
			if (editingSlideSortButton)
			{
				$('#' + editingSlideSortButton).removeClass('slidesort-selected'); // unselect the button if it was selected
			}
			
			$('#' + editingSlideSortButton).children('.slidesort_slidebox').css('background', 'url(' + originalBackground + ')'); // restore pre-edit background
		
			$('#edit-controls').fadeTo(400, 0);
			$('#edit-controls-choose-hint').show().fadeTo(400,1);
			$().clearForm();
			
			isEditing = false;
			isEditingUntitledSlide = false;
			editingSlideSortButton = false;
			
		}
	
	});

	/* !Delete slide generic function */
	$.fn.deleteSlide = function(event, caller) {
	
		event.preventDefault();
		
		// what is our ID?
		var slideID = $(caller).parent().parent().parent().attr('id');
		
		if (!slideID)
			alert(_total_slider_L10n.unableToDeleteSlideNoID);
					
		slideID = slideID.replace('slidesort_', '');
		
		if (!slideID)
			alert(_total_slider_L10n.unableToDeleteSlideNoID);
		
		// if untitled delete was asked, simply cancel
		if (slideID.match(/^untitled/))
		{
			if (confirm(_total_slider_L10n.wouldLoseUnsavedChanges))
			{
		
				$(caller).parent().parent().fadeTo(350, 0);	
				window.setTimeout(function() {$(caller).parent().parent().parent().remove();}, 380);
				$('#edit-controls').fadeTo(400, 0);
				$('#edit-controls-choose-hint').show().fadeTo(400,1);
				$().clearForm();
				
				isEditing = false;
				isEditingUntitledSlide = false;
				editingSlideSortButton = false;
				
				if ($('#slidesort > li').size() < 2) // will be 1, once remove happens
				{
					// bring up the help text if zero left after that delete
					$('.slidesort-add-hint').show('slow');
					$('.slidesort-drag-hint').css('visibility', 'hidden');					
				}
				
				// trim width of slide sorting area
				//$('#slidesort').css('width', parseInt($('#slidesort').css('width')) - 180 + 'px');
			
			}
			return;			
		}
		
		if (confirm(_total_slider_L10n.confirmDeleteOperation))
		{
		
			$(caller).html('deleting&hellip;');
		
			deleteCaller = caller;
		
			// actually do the delete
			$.ajax({
			
				type: 'POST',
				url: VPM_HPS_PLUGIN_URL + 'action=deleteSlide&group=' + VPM_HPS_GROUP,
				data: { 'id' : slideID },
				
				success: function(result) {
				
					$(deleteCaller).parent().parent().fadeTo(350, 0);
					window.setTimeout(function() {$(deleteCaller).parent().parent().parent().remove();}, 380);
					
					$('#edit-controls').fadeTo(400, 0);
					$('#edit-controls-choose-hint').show().fadeTo(400,1);
					$().clearForm();
					
					if ($('#slidesort > li').size() < 2) // will be 1 after delete 
					{
						// bring up the help text if zero left after that delete
						$('.slidesort-add-hint').show('slow');
						$('.slidesort-drag-hint').css('visibility', 'hidden');
					}				
					
					// trim width of slide sorting area
					//$('#slidesort').css('width', parseInt($('#slidesort').css('width')) - 180 + 'px');
					
					window.setTimeout(function() {
						deleteCaller = false;
					}, 520);

				
				},
				
				error: function(jqXHR, textStatus, errorThrown)
				{
				
					$(deleteCaller).html('Delete');
				
					var response = $.parseJSON(jqXHR.responseText);
					var errorToShow = '';
					
					if (response != null && response.error != null)
					{
						errorToShow = response.error;
					}			
					alert(_total_slider_L10n.unableToDeleteSlide + '\n\n' + errorToShow);					
					
					deleteCaller = false;				
				}			
			});
		
		}
				
		dontStartEdit = true;
		window.setTimeout(function() { dontStartEdit = false; }, 500);
	
	}
	
	/* !Delete slide glue for pre-built buttons */
	$('.slide-delete-button').click(function(event) {
	
		$().deleteSlide(event, this);
	
	});
	
	/* !Help buttons */
	$('.total-slider-help-point').click(function(event)
	{
		event.preventDefault();
		$('#contextual-help-link').click();
		$('#tab-link-total-slider-publishing').children('a:first-child').click();
		$('body').animate({ scrollTop: 0 }, 1000);
	});
	
	/* !Changing slide template */
	$('#template-switch-button').click(function(e) {
		e.preventDefault();
		$('#template-switch-form').submit();		
	});
	
	$('#template-slug-selector').change(function(e) {
		$('#template-slug').val($('select#template-slug-selector option:selected').val());
	});
			
	$('#template-switch-form').submit(function(e) {
		if (isEditing)
		{
			alert(_total_slider_L10n.mustFinishEditingFirst);
			e.preventDefault();
			return false;
		}
		if (!confirm(_total_slider_L10n.templateChangeWouldLoseData))
		{
			e.preventDefault();
			return false;
		}		
	});
	
	/* !Find post/page button for links */
	$('#slide-link-finder').click(function() {
	
		$('#slide-link-is-internal').click();

		if (VPM_SHOULD_WORKAROUND_16655) {
			// workaround for https://core.trac.wordpress.org/ticket/16655
			$('#slide-link-is-internal').prop('checked', false);
		}
		
		findPosts.open();
		isEditing = true;
	});
	
	/* !'Internal post or page' chosen for slide link */
	$('#slide-link-is-internal').click(function() {
		$('#slide-link-external-settings').hide();
		$('#slide-link-internal-settings').show('fast');	
		isEditing = true;		
	});
	
	/* !'External link' chosen for slide link */
	$('#slide-link-is-external').click(function() {
		$('#slide-link-internal-settings').hide();
		$('#slide-link-external-settings').show('fast');
		isEditing = true;			
	});
	
	/* !Shim the find post/page button to get it for the link */
	$('#find-posts-submit').click(function() {
		$('.found-radio input:checked').each(function() {
			$('#slide-link-internal-display').text($('label[for="' + $(this).attr('id') + '"]').text());
			$('#slide-link-internal-id').val($(this).val());
		});
		findPosts.close();
		
		if (VPM_SHOULD_WORKAROUND_16655) {
			// workaround for https://core.trac.wordpress.org/ticket/16655
			$('#slide-link-is-internal').prop('checked', true);
		}
		
	});
	
	if (VPM_SHOULD_WORKAROUND_16655) {
	
		$('#find-posts-close').click(function() {
			// workaround for https://core.trac.wordpress.org/ticket/16655
				$('#slide-link-is-internal').prop('checked', true);
		});
		
		// workaround for https://core.trac.wordpress.org/ticket/16655
		$('#find-posts-input').keyup(function(e){
			if (e.which == 27) { $('#slide-link-is-internal').prop('checked', true); } // close on Escape
		});
	
	}
	
	$().makeDraggable();

});

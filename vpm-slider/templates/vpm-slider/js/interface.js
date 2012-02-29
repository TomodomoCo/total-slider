/*  Copyright (C) 2011-2012 Peter Upfold.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

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
var dontStartEdit = false;
var newShouldShuffle = false;
var deleteCaller = false;

/* language */
var switchEditWouldLoseChanges = 'You are still editing the current slide. Switching to a different slide will lose your changes.\n\nDo you still want to switch slides?';
var leavePageWouldLoseChanges = 'You are still editing the current slide. Leaving this page will lose your changes.';
	/* note:
		This message may or may not be shown. This is browser-dependent. All we can do in some cases is throw a generic
		"don't leave, you haven't saved yet" confirm box, which is better than nothing.
	*/
var wouldLoseUnsavedChanges = 'You will lose any unsaved changes.\n\nAre you sure you want to lose these changes?';
var confirmDeleteOperation = 'Are you sure you want to delete this slide?\n\nThis action cannot be undone.';
var validationErrorIntroduction = "Please correct the following errors with the form.\n\n";
var validationNoSlideTitle = 'You must enter a slide title.';
var validationNoSlideDescription = 'You must enter a slide description.';
var validationInvalidBackgroundURL = 'The supplied background image URL is not a valid URL.';
var validationInvalidLinkURL = 'The supplied slide link is not a valid URL.';

/* miscellaneous functions */
function isUrl(s) {
	var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
	return regexp.test(s);
}

window.onbeforeunload = function() {
	if (isEditing)
	    return leavePageWouldLoseChanges;
}

jQuery(document).ready(function() {

	jQuery.fn.sortSlides = function () {
	
		/* 
			The user has performed a sort and has dropped an object, 
			or we have just saved a new untitled slide which may have
			been resorted, so we should update the shuffle.
		
		*/
			
		if (isEditingUntitledSlide != false) {
			jQuery('#message-area').css('background-color', '#f6ff96');
			jQuery('#message-area').html('The new order will be saved when you save the new slide.');
			jQuery('#message-area').show('slow');
			
			newShouldShuffle = true;
			
			window.setTimeout(function() { jQuery('#message-area').hide('slow'); }, 7500);
			
		}
		else {
			
			var newSortOrder = jQuery('#slidesort').sortable('serialize');
			
			jQuery.ajax({
			
				type: 'POST',
				url: VPM_HPS_PLUGIN_URL + 'action=newSlideOrder&group=' + VPM_HPS_GROUP,
				data: newSortOrder,
				
				success: function(result) {
					if (result.success)
					{
						/*
							// show saved notification
						jQuery('#message-area').css('background-color', '#8cff84');
						jQuery('#message-area').html('Slide order saved.');
						jQuery('#message-area').show('slow');
						window.setTimeout(function() {
							jQuery('#message-area').hide('slow');
						}, 2500);
						*/
					}
				},
				error: function(jqXHR, textStatus, errorThrown)
				{
					var response = jQuery.parseJSON(jqXHR.responseText);
					var errorToShow = '';
					
					if (response != null && response.error != null)
					{
						errorToShow = response.error;
					}
				
					alert('Sorry, unable to resort the slides.\n\n' + errorToShow);												
				}				
			});
			
		}

	
	}

	jQuery('#slidesort').sortable({
	
		update: function(event, ui) {
		
				jQuery().sortSlides();	
		}
	
	});
	
	
	jQuery('#slidesort').disableSelection();
	
	/* click the 'add new' button */
	jQuery('#new-slide-button').click(function(event) {
	
		event.preventDefault();
	
		if (isEditing)
		{
			if (confirm(switchEditWouldLoseChanges))
			{
				isEditing = false;
				
				if (isEditingUntitledSlide) {
					jQuery('#' + isEditingUntitledSlide).remove();				
				}
				
			}
		}
	
		if (!isEditing) {
		
			jQuery('.slidesort-add-hint').hide();
			jQuery('#edit-area').show('slow');
		
			// make all deselected
			jQuery('#slidesort li').removeClass('slidesort-selected');
			
			var newIdNo = jQuery('#slidesort').children().length+1;
			
			// create a new button
			jQuery('#slidesort').append('<li id="slidesort_untitled' + newIdNo + '" class="slidesort-selected"><span id="slidesort_untitled'  + newIdNo + '_text">untitled</span><span id="slidesort_untitled_delete" class="slide-delete">[<a id="slidesort_untitled_delete_button" class="slide-delete-button" href="#">delete</a>]</span></li>');
			
			// hook up new pseudo-delete button
			jQuery('#slidesort_untitled_delete_button').click(function (event) {
				jQuery().deleteSlide(event, this);
			});
			
			jQuery('#slidesort_item' + newIdNo).addClass('slidesort-selected');
			
			isEditing = true;
			isEditingUntitledSlide = jQuery('#slidesort_untitled' + newIdNo).attr('id');
			editingSlideSortButton = jQuery('#slidesort_untitled' + newIdNo).attr('id');
			
			jQuery().clearForm();
		}
	
	});
	
	jQuery.fn.clearForm = function () {
	/*
		Clear the form, ready for a new untitled slide
		(or later populating).
	*/
				
		// clear the form
		jQuery('#edit-slide-title').val('');
		jQuery('#edit-slide-description').val('');
		jQuery('#edit-slide-image-url').html('');
		jQuery('#edit-slide-link').val('');
		jQuery('#slide-preview-title').html('untitled');
		jQuery('#slide-preview-description').html('');
		
		jQuery('#slide-preview').offset({ left: jQuery('#preview-area').offset().left, top: jQuery('#preview-area').offset().top } ); 
		// reset offset on box
		
		jQuery('#preview-area').css('background', '');
		
		window.setTimeout(function() { }, 550);
			
	
	}
	
	/* any form editing performed, set inEditing to true */
	jQuery('.edit-controls-inputs input').keyup(function(e) {
		isEditing = true;
	});
	
	/* click on a slide in the resortable list to select it for editing */

	/* this must be an fn. child funciton because it must be ready to bind both to
		#slidesort li, and to manually bind it to new slidesort objects
		as they are created. They don't get the event binding automatically.
	*/
	
	jQuery.fn.clickSlideObject = function(object) {
	
		if (dontStartEdit)
			return;
	
		if (isEditing)
		{
			if (confirm(switchEditWouldLoseChanges))
			{
				isEditing = false;
				
				if (isEditingUntitledSlide) {
					jQuery('#' + isEditingUntitledSlide).remove();				
				}
				
			}	
		
		}
		
		if (!isEditing)
		{
		
			jQuery('#loading-area').css('visibility', 'visible');	
			
			// make all deselected
			jQuery('#slidesort li').removeClass('slidesort-selected');
					
			// now make me selected
			jQuery(object).addClass('slidesort-selected');
			
			// save the original title in case of cancel
			originalTitle = jQuery('#' + jQuery(object).attr('id') + '_text').html();
			
			// get the data
			jQuery.ajax({
				type: 'POST',
				url: VPM_HPS_PLUGIN_URL + 'action=getSlide&group=' + VPM_HPS_GROUP,
				data: {
					'id': jQuery(object).attr('id').substr( jQuery(object).attr('id').indexOf('slidesort_')+10, jQuery(object).attr('id').length )
				},
				
				success: function(result) {
				
					jQuery('#loading-area').css('visibility', 'hidden');
	
					if (result.error)
					{
						alert(result.error);
					}
					else {
					
						// let's get the form cookin'
						
						jQuery().clearForm(); //for good measure
						
						// fill the fields
						jQuery('#edit-slide-title').val(result.title);
						jQuery('#edit-slide-description').val(result.description);
						jQuery('#edit-slide-image-url').html(result.background);
						jQuery('#edit-slide-link').val(result.link);
						jQuery('#slide-preview-title').html(result.title);
						jQuery('#slide-preview-description').html(result.description);
						
						// put the background image on
						jQuery('#preview-area').css('background', 'url(' + result.background + ')');
						
						// restore the pos x and pos y of the slide preview box
						var containerPos = jQuery('#preview-area').offset();
						
						var newLeft = containerPos.left + result.title_pos_x;
						var newTop = containerPos.top + result.title_pos_y
						
						jQuery('#slide-preview').offset({ left: newLeft, top: newTop });
						
						// ok, do the grand unveiling
						jQuery('#edit-area').show('slow');
						
						editingSlideSortButton = jQuery(object).attr('id');
						
					}				
				},
				error: function(jqXHR, textStatus, errorThrown) {
				
					var response = jQuery.parseJSON(jqXHR.responseText);
					var errorToShow = '';
					
					if (response != null && response.error != null)
					{
						errorToShow = response.error;
					}
				
					alert('Sorry, unable to get that slide.\n\n' + errorToShow);				
				}
			
			});
		} // end else
		
	
	};
	// glue for previous -- for objects that are there at pageload-time
	jQuery('#slidesort li').click(function () {
		jQuery().clickSlideObject(this);
	});
	
	/* show saved message */
	jQuery.fn.showSavedMessage = function() {
	
		jQuery('#message-area').css('background-color', '#8cff84');
		jQuery('#message-area').html('Slide saved.');
		jQuery('#message-area').show('slow');
		window.setTimeout(function() {
			jQuery('#message-area').hide('slow');
			jQuery('#message-area').html();
		
		}, 5500);
	
	};
	
	/* update slide title as typed */
	jQuery('#edit-slide-title').keyup(function(e) {
		jQuery('#slide-preview-title').html(jQuery(this).val());
		if (jQuery(this).val() == "")
		{
			jQuery('#' + editingSlideSortButton + '_text').html('untitled');
		}
		else {
			jQuery('#' + editingSlideSortButton + '_text').html(jQuery(this).val());
		}
	});
	
	/* update slide description as typed */
	jQuery('#edit-slide-description').keyup(function(e) {
		jQuery('#slide-preview-description').html(jQuery(this).val());
	});
	
	/* Make the preview slide in the edit area draggable */
	jQuery('#slide-preview').draggable({ containment: '#preview-area' } );
	
	/* Test button for XY data */
	/*jQuery('#show-xy-test').click(function() {
	
		var calcBoxOffsetLeft = jQuery('#slide-preview').offset().left - jQuery('#preview-area').offset().left;
		var calcBoxOffsetTop  = jQuery('#slide-preview').offset().top - jQuery('#preview-area').offset().top;
	
		var xydata = 'slidepreview left is ' + jQuery('#slide-preview').offset().left + "\n" + 'slidepreview top is ' + jQuery('#slide-preview').offset().top + "\n" + 'previewarea left is ' + jQuery('#preview-area').offset().left + "\n" + 'previewareatop is ' + jQuery('#preview-area').offset().top + "\ncalculated left offset is " + calcBoxOffsetLeft + "\ncalculated top offset is " + calcBoxOffsetTop;
		
		alert(xydata);
	
	});*/
	
	/* Trigger the upload thickbox for the background image */
	
	jQuery('#edit-slide-image-upload').click(function () {		
		var myTop = jQuery(this).offset();

		tb_show('Upload slide background image', 'media-upload.php?type=image&TB_iframe=true&height=400&width=600');
		
		return false;
	
	});
	
	/* Uploader has returned from uploading */
	window.send_to_editor = function(html) {
	
		imgurl = jQuery('img',html).attr('src');
		jQuery('#edit-slide-image-url').html(imgurl);
		
		// update the preview to show this background
		jQuery('#preview-area').css('background', 'url(' + imgurl + ')');
		
		tb_remove();
		
	}
	
	/* Save button -- create a new, or update an existing slide */
	jQuery('#edit-controls-save').click(function() {
	
		// validate data
		
		var validationErrors = Array();
		
		if (jQuery('#edit-slide-title').val().length < 1)
		{ // blank title
			validationErrors[validationErrors.length] = validationNoSlideTitle;
		}
		if (jQuery('#edit-slide-description').val().length < 1)
		{ // blank description
			validationErrors[validationErrors.length] = validationNoSlideDescription;
		}
		if (jQuery('#edit-slide-image-url').val().length > 1 && !isUrl(jQuery('#edit-slide-image-url').val()))
		{	// if we have a background URL set, but it is not a proper URL
			validationErrors[validationErrors.length] = validationInvalidBackgroundURL;
		}
		if (jQuery('#edit-slide-link').val().length > 1 && !isUrl(jQuery('#edit-slide-link').val()))
		{	// if we have a background URL set, but it is not a proper URL
			validationErrors[validationErrors.length] = validationInvalidLinkURL;
		}
		
		// X/Y bounds??
		
		if (validationErrors.length > 0)
		{
			var errorString = validationErrorIntroduction;
			
			for (var i = 0; i < validationErrors.length; i++) {
				errorString += validationErrors[i] + '\n';
			}
			
			alert(errorString);
			return false;		
			
		}
	
	
		// go ahead and get ready to save
		jQuery('#loading-area').css('visibility', 'visible');	
	
		var calcBoxOffsetLeft = jQuery('#slide-preview').offset().left - jQuery('#preview-area').offset().left;
		var calcBoxOffsetTop  = jQuery('#slide-preview').offset().top - jQuery('#preview-area').offset().top;
	
		if (isEditingUntitledSlide) {
		
			// create new slide
			jQuery.ajax({
				type: 'POST',
				url: VPM_HPS_PLUGIN_URL + 'action=createNewSlide&group=' + VPM_HPS_GROUP,
				data: {
					'title': jQuery('#edit-slide-title').val(),
					'description': jQuery('#edit-slide-description').val(),
					'background': jQuery('#edit-slide-image-url').html(),
					'link': jQuery('#edit-slide-link').val(),
					'title_pos_x': calcBoxOffsetLeft,
					'title_pos_y': calcBoxOffsetTop						
				},
				
				success: function(result) {
				
					jQuery('#loading-area').css('visibility', 'hidden');
				
					if (result.error) {
						alert(result.error);
					}
					else {
				
					jQuery('#' + editingSlideSortButton).removeClass('slidesort-selected');
					jQuery('#' + editingSlideSortButton).click(function() { jQuery().clickSlideObject(this); } );
					jQuery('#' + editingSlideSortButton).attr('id', 'slidesort_' + result.new_id);
					 
					// update other IDs too
					jQuery('#' + editingSlideSortButton + '_text').attr('id', 'slidesort_' + result.new_id + '_text');
					jQuery('#slidesort_untitled_delete').attr('id', 'slidesort_' + result.new_id + '_delete');
					jQuery('#slidesort_untitled_delete_button').attr('id', 'slidesort_' + result.new_id + '_delete_button');					
					
					jQuery('#edit-area').hide('slow');
					jQuery().clearForm();

						
					
					isEditing = false;
					isEditingUntitledSlide = false;
					editingSlideSortButton = false;
					newShouldShuffle = false;//TODO fixme should be under??
					
					// trigger a shuffle update with the new order, if changed, of this new item
					if (newShouldShuffle)
					{
						newShouldShuffle = false;
						window.setTimeout(function() { jQuery().sortSlides(); }, 450);
					}
					
					newShouldShuffle = false;
					
					jQuery().showSavedMessage();
					
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
				
					var response = jQuery.parseJSON(jqXHR.responseText);
					var errorToShow = '';
					
					if (response != null && response.error != null)
					{
						errorToShow = response.error;
					}
				
					jQuery('#loading-area').css('visibility', 'hidden');
					alert('Sorry, unable to save the new slide.\n\n' + response.error);				
				}
				
			});
		
		}
		
		else {
			
			// update existing slide
			jQuery.ajax({
				type: 'POST',
				url: VPM_HPS_PLUGIN_URL + 'action=updateSlide&group=' + VPM_HPS_GROUP,
				data: {
					'id': jQuery('#' + editingSlideSortButton).attr('id').substr( jQuery('#' + editingSlideSortButton).attr('id').indexOf('slidesort_')+10, jQuery('#' + editingSlideSortButton).attr('id').length ),
					'title': jQuery('#edit-slide-title').val(),
					'description': jQuery('#edit-slide-description').val(),
					'background': jQuery('#edit-slide-image-url').html(),
					'link': jQuery('#edit-slide-link').val(),
					'title_pos_x': calcBoxOffsetLeft,
					'title_pos_y': calcBoxOffsetTop									
				},
				
				success: function(result) {
				
					jQuery('#loading-area').css('visibility', 'hidden');
				
					if (result.error) {
						alert(result.error);
					}
					else {
						jQuery('#' + editingSlideSortButton).removeClass('slidesort-selected');
						jQuery('#edit-area').hide('slow');
						jQuery().clearForm();	
						
						isEditing = false;
						isEditingUntitledSlide = false;
						editingSlideSortButton = false;
						
						jQuery().showSavedMessage();
						
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
				
					var response = jQuery.parseJSON(jqXHR.responseText);
					var errorToShow = '';
					
					if (response != null && response.error != null)
					{
						errorToShow = response.error;
					}
				
					jQuery('#loading-area').css('visibility', 'hidden');				
					alert('Sorry, unable to save the slide.\n\n' + errorToShow);				
				}
				
			});
		
		}
	
	});
	
	
	/* Cancel button -- cancel edits, or cancel new item */
	jQuery('#edit-controls-cancel').click(function() {
	
		if (isEditingUntitledSlide)
		{
			// editing untitled, new slide
		
			if (confirm(wouldLoseUnsavedChanges))
			{
				jQuery('#' + editingSlideSortButton).remove();
				jQuery('#edit-area').hide('slow');
				jQuery().clearForm();
				
				isEditing = false;
				isEditingUntitledSlide = false;
				editingSlideSortButton = false;
				
				if (jQuery('#slidesort > li').size() < 1)
				{
					// bring up the help text if zero left after that delete
					jQuery('.slidesort-add-hint').show('slow');
				}
			
			}			
		
		}
		else if (isEditing) {
			// editing existing slide
			
			if (confirm(wouldLoseUnsavedChanges))
			{
			
				jQuery('#edit-area').hide('slow');
				jQuery().clearForm();				
				
				jQuery('#' + editingSlideSortButton + '_text').html(originalTitle); // restore pre-edit title
				jQuery('#' + editingSlideSortButton).removeClass('slidesort-selected'); // unselect
				
				isEditing = false;
				isEditingUntitledSlide = false;
				editingSlideSortButton = false;
			
			}
			
		
		}
		else { // not editing at all
		
			if (editingSlideSortButton)
			{
				jQuery('#' + editingSlideSortButton).removeClass('slidesort-selected'); // unselect the button if it was selected
			}
		
			jQuery('#edit-area').hide('slow');
			jQuery().clearForm();
			
			isEditing = false;
			isEditingUntitledSlide = false;
			editingSlideSortButton = false;
			
		}
	
	});

	/* delete slide generic function */
	jQuery.fn.deleteSlide = function(event, caller) {
	
		event.preventDefault();
		
		// what is our ID?
		var slideID = jQuery(caller).parent().parent().attr('id');
		slideID = slideID.replace('slidesort_', '');
		
		if (!slideID)
			alert('Unable to delete -- could not get the slide ID for the current slide.');
			
		// if untitled delete was asked, simply cancel
		if (slideID.match(/^untitled/))
		{
			if (confirm(wouldLoseUnsavedChanges))
			{
		
				jQuery(caller).parent().parent().hide(350);	
				window.setTimeout(function() {jQuery(caller).parent().parent().remove();}, 380);
				jQuery('#edit-area').hide('slow');
				jQuery().clearForm();
				
				isEditing = false;
				isEditingUntitledSlide = false;
				editingSlideSortButton = false;
				
				if (jQuery('#slidesort > li').size() < 2) // will be 1, once remove happens
				{
					// bring up the help text if zero left after that delete
					jQuery('.slidesort-add-hint').show('slow');
				}
			
			}
			return;			
		}
		
		if (confirm(confirmDeleteOperation))
		{
		
			jQuery(caller).html('deleting&hellip;');
		
			deleteCaller = caller;
		
			// actually do the delete
			jQuery.ajax({
			
				type: 'POST',
				url: VPM_HPS_PLUGIN_URL + 'action=deleteSlide&group=' + VPM_HPS_GROUP,
				data: { 'id' : slideID },
				
				success: function(result) {
				
					jQuery(deleteCaller).parent().parent().hide(350);
					window.setTimeout(function() {jQuery(deleteCaller).parent().parent().remove();}, 380);
					
					jQuery('#edit-area').hide('slow');
					jQuery().clearForm();
					
					if (jQuery('#slidesort > li').size() < 2) // will be 1 after delete 
					{
						// bring up the help text if zero left after that delete
						jQuery('.slidesort-add-hint').show('slow');
					}				
					
					window.setTimeout(function() {
						deleteCaller = false;
					}, 520);
				
				},
				
				error: function(jqXHR, textStatus, errorThrown)
				{
				
					jQuery(deleteCaller).html('Delete');
				
					var response = jQuery.parseJSON(jqXHR.responseText);
					var errorToShow = '';
					
					if (response != null && response.error != null)
					{
						errorToShow = response.error;
					}			
					alert('Sorry, unable to delete the slide.\n\n' + errorToShow);					
					
					deleteCaller = false;				
				}			
			});
		
		}
				
		dontStartEdit = true;
		window.setTimeout(function() { dontStartEdit = false; }, 500);
	
	}
	
	/* delete slide glue for pre-built buttons */
	jQuery('.slide-delete-button').click(function(event) {
	
		jQuery().deleteSlide(event, this);
	
	});
	

});
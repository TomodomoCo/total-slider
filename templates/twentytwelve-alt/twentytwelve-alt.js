// Initialize Cycle2
jQuery('.total-slider').cycle({
	slides: '.total-slider-slide',

	// Edit the slide delay here. This is in milliseconds. The current delay is 10 seconds.
	timeout: 10000
});

// Set up the paging/nav element
jQuery('.total-slider-slide .total-slider-link-wrapper').each( function(i) {
	jQuery(this).appendTo('.total-slider-nav').replaceWith('<li class="total-slider-link-wrapper">' + jQuery(this).html() + '</li>');
});

// Set up the nav click action
jQuery('.total-slider-nav-link').click( function(event) {
	// Keep links from working as links by default
	event.preventDefault();

	// Grab the current slide iterator from the HTML5 data-slide-iteration attribute
	slide_iteration = jQuery(this).data('slideIteration');

	// Navigate to the selected slide
	jQuery('.total-slider').cycle('goto', slide_iteration);

	// Set the "total-slider-current" class on the active link
	jQuery('.total-slider-nav-link').removeClass('total-slider-current');
	jQuery(this).addClass('total-slider-current');
});

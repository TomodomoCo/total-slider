<?php
/*
 * Admin Help Pointer JavaScript
 *
 */

/* ----------------------------------------------*/

/*  Copyright (C) 2011-2015 Peter Upfold.

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

if ( ! defined('TOTAL_SLIDER_REQUIRED_CAPABILITY' ) ) { 
        header('HTTP/1.1 403 Forbidden');
        die('<h1>Forbidden</h1>');
}

if ( ! function_exists( '__' ) ) 
{
        header( 'HTTP/1.1 403 Forbidden' );
        die( '<h1>Forbidden</h1>' );
}

// if dismissed, set empty pointer content, which hides it
$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

if ( in_array( 'total-slider-help-pointer', $dismissed ) ) {
	$pointer_content = '';
}
else {
	$pointer_content = '<h3>' . esc_attr ( __('Need help?', 'total-slider') ) . '</h3>';
	$pointer_content .= '<p>' . esc_attr ( __('The help menu will walk you through creating new groups, adding slides, and getting them to display in your theme. It’s a great place to start!', 'total-slider') ) . '</p>';

}

?>
<script type="text/javascript">
/*<![CDATA[ */
jQuery(document).ready( function($) {
	$('#contextual-help-link-wrap').pointer({
		content: '<?php echo $pointer_content; ?>',
		position: {
			edge:  'top',
			align: 'right'
		},
		pointerClass: 'slider-help-pointer',
		close: function() {
			$.post( ajaxurl, {
					pointer: 'total-slider-help-pointer',
					_ajax_nonce: $('#_ajax_nonce').val(),
					action: 'dismiss-wp-pointer'
			});
		}
	}).pointer('open');

	$(window).resize(function() {
		if ( $('.slider-help-pointer').is(":visible") ) $('#contextual-help-link-wrap').pointer('reposition');
	});

	$('#contextual-help-link-wrap').click( function () {
		setTimeout( function () {
			$('#contextual-help-link-wrap').pointer('close');
		}, 0);
	});
});
//]]>
</script>
<style type="text/css">
.slider-help-pointer .wp-pointer-arrow {
	right:10px;
	left:auto;
}
</style><?php


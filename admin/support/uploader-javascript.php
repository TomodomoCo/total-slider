<?php
/*
 * Print uploader JavaScript
 *
 * Print the JavaScript to inject into the Media Uploader
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

if ( array_key_exists( 'total-slider-uploader', $_GET ) && 'bgimage' == $_GET['total-slider-uploader'] ) {

	if (
		! array_key_exists( 'total-slider-slide-group-template', $_GET ) ||
		empty( $_GET['total-slider-slide-group-template'] ) ||
		! array_key_exists( 'total-slider-slide-group-template-location', $_GET ) ||
		empty( $_GET['total-slider-slide-group-template-location'] )
	) {
		$crop = array( 
			'crop_width' => TOTAL_SLIDER_DEFAULT_CROP_WIDTH,
			'crop_height' => TOTAL_SLIDER_DEFAULT_CROP_HEIGHT
		);
	}
	else {
		try {
			$t = new Total_Slider_Template( $_GET['total-slider-slide-group-template'], $_GET['total-slider-slide-group-template-location'] );
			
			$crop = $t->determine_options();					
		}
		catch ( Exception $e )
		{
			$crop = array(
				'crop_width' => TOTAL_SLIDER_DEFAULT_CROP_WIDTH,
				'crop_height' => TOTAL_SLIDER_DEFAULT_CROP_HEIGHT
			);
		}			
	}

?>
<!-- a little shimming to prettify the uploader/media library options for Total Slider purposes -->
<style type="text/css">
#media-items .post_title,#media-items .image_alt,#media-items .post_excerpt,#media-items .post_content, #media-items .url, #media-items .align { display:none !important; }
</style>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
	jQuery('.imgedit-response').append('<p style="text-align:center;font-size:12px;color:#909090;"><?php printf( __( 'Choose ‘Edit Image’ and crop to %d×%d for best results.', 'total-slider' ), $crop['crop_width'], $crop['crop_height'] ); ?></p>');

	jQuery('.savesend .button').each(function() {
		jQuery(this).attr('value', '<?php _e( 'Use as background image', 'total-slider' );?>');
	});

	if (typeof uploader == 'object') {
		uploader.bind('FileUploaded', function() {
			window.setTimeout(function() {

				jQuery('.imgedit-response').append('<p style="text-align:center;font-size:12px;color:#909090;"><?php printf( __( 'Choose ‘Edit Image’ and crop to %d×%d for best results.', 'total-slider' ), $crop['crop_width'], $crop['crop_height'] ); ?></p>');
				// rename the main action button
				jQuery('.savesend .button').each(function() {
					jQuery(this).attr('value', '<?php _e( 'Use as background image', 'total-slider' );?>');
				});
			}, 680);
		});
	}
});
//]]>
</script>
<!-- we're done shimming -->
<?php

}

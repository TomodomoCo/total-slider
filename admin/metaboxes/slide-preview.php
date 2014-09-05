<?php
/*
Slide Preview metabox

Print to output the contents of the slide preview metabox. 

/* ----------------------------------------------*/

/*  Copyright (C) 2011-2014 Peter Upfold.

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

?>
<div id="edit-area">

	<?php /*
	
		These elements are placeholders. We will briefly put the title and description in these elements,
		so we can pull the values back out from $.text() on the objects.
		
		Doing so helps to neuter executable stuff and HTML that we don't want to inject into the EJS template
		and possibly break things or be a local security issue.
	
	*/ ?>
	<div id="preview-var-title" class="preview-var-placeholder"></div>
	<div id="preview-var-description" class="preview-var-placeholder"></div>
	
	<!--
		The div 'preview-slide' is where EJS will render to.
		
		The EJS template to begin with must itself live in a separate text/ejs script block.
	-->
	<div id="preview-slide">
	<script id="slide-ejs" type="text/ejs">
	<?php
	
	if ( ! $TS_The_Template || ! is_a( $TS_The_Template, 'Total_Slider_Template' ) ) {	
		// determine the current template
		if ( ! Total_Slider::determineTemplate() ) {
			?><div class="template-render-error"><?php
			_e( 'Unable to load the preview.', 'total_slider' );
			?></div><?php
		}
	}
	
	if ( is_a( $TS_The_Template, 'Total_Slider_Template' ) ) {
		try {
			echo $TS_The_Template->render();
		}
		catch ( Exception $e )
		{
			?><div class="template-render-error"><?php
			_e( 'Unable to load the preview.', 'total_slider' );
			?><br />
			<em><?php echo esc_html( $e->getMessage() ); ?></em>
			</div><?php					
		}
	}
	
	?>
	</script>
	</div>
</div>

<div style="clear:both;"></div><?php


<?php
/*
Slide Sorter metabox
 
Print to output the contents of the slide sorter/slide listing metabox.

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

?><!--sortable slides-->
<?php $current_slides = Total_Slider::get_current_slides($TS_The_Slug); ?>
<div id="slidesort-container">
<ul id="slidesort" style="width:<?php echo intval( count( $current_slides ) * 180 ); ?>px;">
<?php

if ( is_array( $current_slides ) && count( $current_slides ) > 0 ) {

	foreach( $current_slides as $slide ) {

		$my_id = Total_Slider::id_filter( $slide['id'] );
		
		if ( is_numeric($slide['background'] ) )
		{
			$background_url = wp_get_attachment_url( (int) $slide['background'] );
		}
		else {
			$background_url = $slide['background'];
		}

		?>

		<li id="slidesort_<?php echo $my_id;?>">

			<div class="slidesort_slidebox" style="background: url(<?php echo esc_url( $background_url );?>)">
				<div id="slidesort_<?php echo $my_id;?>_text" class="slidesort_text"><?php echo stripslashes( esc_html( $slide['title'] ) );?></div>

				<a id="slidesort_<?php echo $my_id;?>_move_button" class="slidesort-icon slide-move-button" href="#"><?php _e( 'Move', 'total_slider' );?></a>
				<span id="slidesort_<?php echo $my_id;?>_delete" class="slide-delete"><a id="slidesort_<?php echo $my_id;?>_delete_button" class="slidesort-icon slide-delete-button" href="#"><?php _e( 'Delete', 'total_slider' );?></a></span>
			</div>

		</li>

		<?php

	}

}

?>
</ul>

<div class="slidesort-add-hint"<?php if ( is_array( $current_slides ) && count( $current_slides ) > 0) echo ' style="display:none"'; ?>>
<?php _e('Click &lsquo;Add New&rsquo; to create a Slide.', 'total_slider');?></div>

</div>

<?php



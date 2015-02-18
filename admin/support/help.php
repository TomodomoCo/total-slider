<?php
/*
 * Print help
 *
 * Add the slides admin help to the current screen. 
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

$screen = get_current_screen();


$slide_groups_help[0] = __( 'Each slide group contains a number of slides that will appear, one after another, when you publish your slides on your site.', 'total-slider' );
$slide_groups_help[1] = sprintf( __( 'You can make up to %d slide groups, which you can use to have different slideshows on different parts of your site.', 'total-slider' ), intval(TOTAL_SLIDER_MAX_SLIDE_GROUPS) );

$screen->add_help_tab( array (
	'id'			=>			'total-slider-groups',
	'title'			=>			__( 'Slide Groups', 'total-slider' ),
	'content'		=>			'<p>' . $slide_groups_help[0] . '</p><p>' . $slide_groups_help[1] . '</p>'

) );

$editing_help[0] = __( 'Once you have clicked ‘Edit’ on the desired slide group, you’ll see all of its slides.', 'total-slider' );
$editing_help[1] = __( 'Click on any slide to make changes. You can also drag and drop the title and description to place them anywhere over the background image.', 'total-slider' );
$editing_help[2] = __( 'Simply drag and drop to re-order the slides within the group. The new order is saved immediately.', 'total-slider' );

$screen->add_help_tab( array (
	'id'			=>			'total-slider-editing',
	'title'			=>			__( 'Editing', 'total-slider' ),
	'content'		=>			'<p>' . $editing_help[0] . '</p><p>' . $editing_help[1] . '</p><p>' . $editing_help[2] . '</p>'

) );

$publishing_help[0] = __( 'Once you are happy with your new slide group, you need to publish it for it to show up on your site.', 'total-slider' );
$publishing_help[1] = __( 'To do this, your theme needs to support Widgets, and have a widget area in the theme where you’d like the slides to show up.', 'total-slider' );
$publishing_help[2] = __( 'Go to <a href="widgets.php">Appearance » Widgets</a> and drag a <strong>Total Slider</strong> widget to the desired sidebar. In the widget’s settings, choose the slide group you would like to show and click <em>Save</em>.', 'total-slider' );


$screen->add_help_tab( array(

	'id'			=>			'total-slider-publishing',
	'title'			=>			__( 'Publishing', 'total-slider' ),
	'content'		=>			'<p>' . $publishing_help[0] . '</p><p>' . $publishing_help[1] . '</p><p>' . $publishing_help[2] . '</p>'

) );

$hints_tips[0] = __( 'For the best visual results, crop your background images to the size used by your slide template.', 'total-slider' );
$hints_tips[1] = __( 'Experiment with dragging and dropping the title and description over different parts of the background to achieve a different visual effect.', 'total-slider' );
$hints_tips[2] = __( 'Keep your site fresh — create multiple slide groups ahead of time, then simply edit the <strong>Total Slider</strong> widget to switch over to display another slide group every now and then.', 'total-slider' );
$hints_tips[3] = __( 'Completely customise the look of your slides — create a <em>total-slider-templates</em> subfolder in your theme. You can use our <em>templates</em> folder in the plugin as a starting point.', 'total-slider' );

$screen->add_help_tab( array(

	'id'			=>			'total-slider-hints',
	'title'			=>			__( 'Hints &amp; Tips', 'total-slider' ),
	'content'		=>			'<ul><li>' . $hints_tips[0] . '</li><li>' . $hints_tips[1] . '</li><li>' . $hints_tips[2] . '</li><li>' . $hints_tips[3] . '</li></ul>'

) );

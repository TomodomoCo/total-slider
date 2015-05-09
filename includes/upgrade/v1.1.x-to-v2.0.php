<?php
/*
 * Total Slider Data Format Upgrade Tool -- v1.1.x - v1.2
 * 
 * This file is invoked by Total Slider when the user has upgraded the plugin,
 * to ensure that the data format has been upgraded to the latest version.
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

require_once ( ABSPATH . 'wp-admin/includes/user.php' );

// get the slide group list

$legacy_slide_groups = get_option( 'total_slider_slide_groups' );

if ( is_array( $legacy_slide_groups ) && count( $legacy_slide_groups ) > 0 ) {

	$legacy_slide_group_names = array();
	// to detect and handle Slide Group name clashes, which are now a problem with the 2.0 data format

	foreach( $legacy_slide_groups as $key => $legacy_group ) {

		if ( is_a( $legacy_group, 'Total_Slide_Group' ) ) {


			// convert this group to the new format
			$new_slug = Total_Slider::sanitize_slide_group_slug( $legacy_group->slug );
			$legacy_slides = get_option( 'total_slider_slides_' . $new_slug );

			$new_group = new Total_Slide_Group( $new_slug, $legacy_group->name );
			$new_group->template = $legacy_group->template;
			$new_group->templateLocation = $legacy_group->templateLocation;

			// if this legacy group name collides with an existing legacy group name, we must rename and re-slug it
			// or WP will combine posts attached to the two terms, merging the Slide Groups!
			if ( in_array( $legacy_group->name, $legacy_slide_group_names ) ) {
				$new_group->name = $legacy_group->name . ' (duplicate name)';
				$new_group->slug = Total_Slider::sanitize_slide_group_slug( substr( $legacy_group->slug, 0, 12 ) . sanitize_title_with_dashes( uniqid( '', true ) ) );
			}

			$new_group->save();

			$new_slide_ids = array();

			if ( is_array( $legacy_slides ) && count( $legacy_slides ) > 0 ) {
				foreach( $legacy_slides as $legacy_slide ) {
					$title = $legacy_slide['title'];
					$description = $legacy_slide['description'];
					$background = $legacy_slide['background'];
					$link = $legacy_slide['link'];
					$title_pos_x = $legacy_slide['title_pos_x'];
					$title_pos_y = $legacy_slide['title_pos_y'];

					$new_slide_ids[] = $new_group->new_slide( $title, $description, $background, $link, $title_pos_x, $title_pos_y, 'publish' );	
				}
			}

			// make sure we don't use this name again
			$legacy_slide_group_names[] = $legacy_group->name;

			// fix ordering by doing a reshuffle with the new array of slide ids returned
			$new_group->reshuffle( $new_slide_ids );
		}
	}
}

// handle role assignments and add the new capabilities to roles that previous had TOTAL_SLIDER_REQUIRED_CAPABILITY
$all_roles = get_editable_roles();
$roles_to_set = array( 'administrator' );

if ( is_array( $all_roles ) && count( $all_roles ) > 0 ) {
	foreach( $all_roles as $role_name => $role_info ) {
		if ( in_array( TOTAL_SLIDER_REQUIRED_CAPABILITY, array_keys( $role_info['capabilities'] ) ) ) {
			$roles_to_set[] = $role_name;
		}
	}
}

$ts_class->set_capability_for_roles( $roles_to_set, 'preserve_existing', 'upgrading' );



// completed -- so update our data format version
update_option( 'total_slider_dataformat_version', '2.0' );

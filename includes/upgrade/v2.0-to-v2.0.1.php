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

// determine if there are any slides not attached properly to slide groups, and re-attach by inferring
// ownership from the legacy data
$ts_ug_201_wpdb = &$GLOBALS['wpdb'];

$orphaned_slides_query = "SELECT ID, post_title, post_name, post_content, post_status FROM {$ts_ug_201_wpdb->prefix}posts
	LEFT JOIN {$ts_ug_201_wpdb->prefix}term_relationships
		ON {$ts_ug_201_wpdb->prefix}posts.ID = {$ts_ug_201_wpdb->prefix}term_relationships.object_id
	WHERE post_type = 'total_slider_slide'
	AND term_taxonomy_id IS NULL
";

$orphaned_slides = $ts_ug_201_wpdb->get_results( $orphaned_slides_query, OBJECT );

if ( is_array( $orphaned_slides ) && count( $orphaned_slides ) > 0 ) {

	// pull in legacy slides, so we can title + description match slides to determine their correct slide group ownership
	// for the new data format
	$legacy_slide_groups = get_option( 'total_slider_slide_groups' );
	$legacy_slides = array();

	if ( is_array( $legacy_slide_groups ) && count( $legacy_slide_groups ) > 0 ) {
		foreach( $legacy_slide_groups as $lsg ) {
			if ( is_a( $lsg, 'Total_Slide_Group' ) ) {
				$lsg->slug = Total_Slider::sanitize_slide_group_slug( $lsg->slug );
				$legacy_slides[ $lsg->slug ] = get_option( 'total_slider_slides_' . $lsg->slug );
			}
		}


		// now that we have pulled legacy slide groups and their slides, we can match orphaned slides to these

		foreach( $orphaned_slides as $orphan ) {

			$found_parent = false;

			// loop through all legacy slide groups
			foreach( $legacy_slides as $slug => $lsg ) { // this still gives us groups -- slides are one level down
				if ( $found_parent) { break; }
				if ( is_array( $lsg ) && count( $lsg ) > 0 ) {
					foreach( $lsg as $ls ) {
						if ( $found_parent ) { break; }
						if (
							$ls['title'] == $orphan->post_title &&
							$ls['description'] == $orphan->post_content
						) {
							$found_parent = true;
							// fix this match with this legacy slide group's slug
							if ( term_exists( $slug, 'total_slider_slide_group' ) ) {
								wp_set_object_terms( $orphan->ID, array( $slug ), 'total_slider_slide_group' );
							}							
						}
					}
				}
			}

		}

	}
}

// completed -- so update our data format version
update_option( 'total_slider_dataformat_version', '2.0.1' );

<?php
/*
 * This file contains the Total Slider Ajax Interface Tools Class, which provides a set of methods used by
 * ajax_interface.php.
 *
/*
Total Slider Ajax Interface Tools
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

/**
 * Class: Provides various tools and methods used by the Ajax Interface.
 *
 */

class Total_Slider_Ajax_Interface_Tools {

	/**
	 * Dump a JSON object detailing a 'failed at the server' error, with a HTTP 500 response code.
	 *
	 * If WP_DEBUG is defined and enabled, dump the supplied WP_Error object to the JSON output.
	 *
	 * @var WP_Error The WP_Error object.
	 * @return void
	 */
	public static function maybe_dump_wp_error( $error_obj ) {

		header( 'HTTP/1.0 500 Internal Server Error' );
		header( 'Content-Type: application/json' );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ob_start();

			var_dump( $error_obj );
			$dump = ob_get_contents();

			ob_end_clean();

			echo json_encode(
				array(
					'error' => __( 'The operation failed at the server.\n\nYou may inspect the WP_Error object returned using the web browser Developer Tools.', 'total-slider' ),
					'WP_Error' => $dump
				)
			);
		}
		else {
			echo json_encode(
				array(
					'error' => __( 'The operation failed at the server.\n\nFor detailed information, please enable WP_DEBUG.', 'total-slider' )
				)
			);
		}
	}

};

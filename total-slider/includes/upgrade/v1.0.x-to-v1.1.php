<?php
/*
Total Slider Data Format Upgrade Tool -- v1.0.x - v1.1
	
This file is invoked by Total Slider when the user has upgraded the plugin,
to ensure that the data format has been upgraded to the latest version.

/* ----------------------------------------------*/

/*  Copyright (C) 2011-2012 Peter Upfold.

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

if (!defined('TOTAL_SLIDER_REQUIRED_CAPABILITY'))
{
	header('HTTP/1.1 403 Forbidden');
	die('<h1>Forbidden</h1>');
}

if (!function_exists('__'))
{
	header('HTTP/1.1 403 Forbidden');
	die('<h1>Forbidden</h1>');
}

// add the data format option
if (!get_option('total_slider_dataformat_version'))
{
	add_option('total_slider_dataformat_version', TOTAL_SLIDER_DATAFORMAT_VERSION);
}

// set default general options, if not set (unlikely)
if (!get_option('total_slider_general_options'))
{
	add_option('total_slider_general_options', array(
		'should_enqueue_template'	=> 	'1',
		'should_show_tinymce_button' => '1'
	));
}
else {
	
	$general_options = get_option('total_slider_general_options');
	
	// set should_show_tinymce_button to default on, if it is not set at all
		
	if ( is_array($general_options) && count($general_options) > 0 ) {
		if ( !array_key_exists('should_show_tinymce_button', $general_options) || $general_options['should_show_tinymce_button'] == '' )
		{
			$general_options['should_show_tinymce_button'] = '1';
			
			// save our options back
			update_option('total_slider_general_options', $general_options);
		}
	}
		
}
		

?>
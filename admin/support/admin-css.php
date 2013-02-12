<?php
/*
Print admin CSS

Print the admin CSS to show our admin menu icons.

/* ----------------------------------------------*/

/*  Copyright (C) 2011-2013 Peter Upfold.

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

<!-- Total Slider inline admin style -->
<style type="text/css" id="total-slider-menu-css">
#toplevel_page_total-slider .wp-menu-image img { visibility: hidden; }
#toplevel_page_total-slider .wp-menu-image { background: url( <?php echo plugin_dir_url( dirname( __FILE__ ) ).'../img/slider-icon-switch.png'; ?> ) 0 90% no-repeat; }
#toplevel_page_total-slider.current .wp-menu-image, #toplevel_page_total-slider.wp-has-current-submenu .wp-menu-image, #toplevel_page_total-slider:hover .wp-menu-image { background-position: top left; }
</style>

<?php



<?php
/*
 * Print admin JS reference
 * 
 * When WordPress is displaying the WP-Admin page headers, add a reference to the
 * ajax_interface.php access URL, so we can pass it to TinyMCE popup iframes.
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

//TODO: migrate to WP_Ajax_Response
	
?>
<script type="text/javascript">
//<![CDATA[
window._total_slider_ajax = '<?php echo get_admin_url(); ?>admin.php?page=total-slider&total-slider-ajax=true';
window._total_slider_jq = '<?php echo includes_url(); ?>js/jquery/jquery.js';
window._total_slider_tmp = '<?php echo includes_url(); ?>js/tinymce/tiny_mce_popup.js';
var _total_slider_mce_l10n = '<?php if ( strpos( get_locale(), 'en' ) !== 0 ) { echo '_' . esc_attr( get_locale() ); } ?>';
var _total_slider_mce_l10n_insert = '<?php _e( 'Insert Slider', 'total-slider' );?>';
var _total_slider_uploader = '<?php echo ( version_compare( get_bloginfo( 'version' ), '3.5', '>=' ) ) ? 'elvin' : 'legacy'; ?>';
var _total_slider_allowed_post_statuses = [ '<?php echo implode( "','", Total_Slider::$allowed_post_statuses ) ; ?>' ];
var _total_slider_locale = '<?php echo esc_attr( get_locale() ); ?>';
//]]>
</script>
<?php	

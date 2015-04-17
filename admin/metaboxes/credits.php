<?php
/*
 * Credits metabox
 * 
 * Print to output the contents of the credits/notes metabox.
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

?>

<div id="credits">
	<p><?php _e( 'Development: <a href="http://peter.upfold.org.uk/">Peter Upfold</a>', 'total-slider' );?></p>

	<p><?php _e( 'Additional UI: <a href="https://www.vanpattenmedia.com/">Chris Van Patten</a>', 'total-slider' );?></p>

	<p><?php _e( 'Icons: <a href="http://www.field2.com/">Ben Dunkle</a>', 'total-slider' );?></p>
	
	<?php if ( strpos( strtolower( get_locale() ), 'en' ) !== 0 ): ?>
		<p>
		<?php
		/* translators: please replace this with a credit to yourself! "Translation: Your Name". This English message will appear when no translation is available, but the user is using WordPress in another language.. */
		?>
		<?php printf( __( '<a href="%s">Help us translate this plugin into your language!</a>', 'total-slider'), 'http://www.totalslider.com/' );?>
		</p>
	<?php endif; ?>
</div>

<div id="contrib-note">
	<p><?php printf( __( 'If you find this plugin useful, or are using it commercially, please consider <a href="%s">donating</a> to support development.', 'total-slider'), 'http://www.totalslider.com/support/' ); ?></p>

	<p><?php printf ( __( 'You can also <a href="%s">report bugs</a>, <a href="%s">suggest features</a>, or <a href="%s">send pull requests</a>.', 'total-slider'), 'https://github.com/vanpattenmedia/total-slider', 'https://github.com/vanpattenmedia/total-slider', 'https://github.com/vanpattenmedia/total-slider' ); ?></p>

	<p><?php _e( 'Thanks!', 'total-slider' ); ?></p>
</div>

<div id="copyright">
	<p><?php _e( '© 2011-2015 Peter Upfold. Proud to be <a href="https://www.gnu.org/licenses/gpl-2.0.html">GPLv2 (or later) licensed</a>.', 'total-slider' );?></p>
	<p id="vpm-credit"><?php _e( 'Built by <a href="http://www.vanpattenmedia.com/">Van Patten Media</a>', 'total-slider' );?></p>
</div>
<?php


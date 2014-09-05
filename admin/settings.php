<?php
/*
Settings page       
 
Print the settings page to output, and also handle any of the Settings forms if they
have come back to us.

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

if ( ! current_user_can( TOTAL_SLIDER_REQUIRED_CAPABILITY ) )
{
	echo '<h1>';
	_e( 'You do not have permission to manage Slider settings.', 'total_slider' );
	echo '</h1>';
	die();
}

$success = null;
$message = '';

$other_options = get_option( 'total_slider_general_options' );

if (
	'post' == strtolower( $_SERVER['REQUEST_METHOD'] ) &&
	array_key_exists( 'total-slider-settings-submitted', $_POST )
) {
	// handle the submitted form

	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'total-slider-settings' ) ) {
		die( __( 'Unable to confirm the form’s security.', 'total_slider' ) );
	}

	if ( current_user_can( 'manage_options' ) )	{

		$roles_to_add = array();

		// find any checked roles to add our capability to
		foreach( $_POST as $pk => $po ) {
			if ( preg_match( '/^required_capability_/', $pk ) ) {
			
				$role_name_chopped = substr( $pk, strlen( 'required_capability_' ) );

				// do not allow administrator to be modified
				if ( 'administrator' != $role_name_chopped && $po == '1' )
				{
					$roles_to_add[] = $role_name_chopped;
				}
			}
		}

		Total_Slider::set_capability_for_roles($roles_to_add);
		$success = true;
		$message = __( 'Settings saved.', 'total_slider' );

	}

	// should we update the should_enqueue_template option with '1'?
	if (
		array_key_exists( 'should_enqueue_template', $_POST ) &&
		'1' == $_POST['should_enqueue_template']
	) {
		
		// if it would be changed by this save, then set the message to 'Settings saved'.
		if (
			array_key_exists('should_enqueue_template', $other_options )
			&& $other_options['should_enqueue_template'] != '1'
		) {
			$success = true;
			$message = __('Settings saved.', 'total_slider');
		}

		$other_options['should_enqueue_template'] = '1';
		update_option( 'total_slider_general_options', $other_options );
	}
	else {
		// disable the option
		if (
			array_key_exists( 'should_enqueue_template', $other_options) &&
			'0' != $other_options['should_enqueue_template']
		) {
			$success = true;
			$message = __('Settings saved.', 'total_slider');
		}

		$other_options['should_enqueue_template'] = '0';
		update_option( 'total_slider_general_options', $other_options );

	}

	// should we update the should_show_tinymce_button option with '1'?			
	if (
		array_key_exists('should_show_tinymce_button', $_POST) &&
		'1' == $_POST['should_show_tinymce_button']
	) {
	
		if (
			array_key_exists('should_show_tinymce_button', $other_options) &&
			'1' != $other_options['should_show_tinymce_button']
		) {
			$success = true;
			$message = __( 'Settings saved.', 'total_slider' );
		}
		
		$other_options['should_show_tinymce_button'] = '1';
		update_option('total_slider_general_options', $other_options);
	}
	else {
		// disable the option
		if (
			array_key_exists('should_show_tinymce_button', $other_options) &&
			'0' != $other_options['should_show_tinymce_button']
		) {
			$success = true;
			$message = __( 'Settings saved.', 'total_slider' );
		}

		$other_options['should_show_tinymce_button'] = '0';
		update_option('total_slider_general_options', $other_options);				
	}


}

?><div class="wrap">
<div id="icon-total-slides" class="icon32" style="background:transparent url(<?php echo plugin_dir_url( __FILE__ ); ?>../img/total-slider-icon-32.png?ver=20120229) no-repeat;"><br /></div><h2><?php _e( 'Settings', 'total_slider' );?></h2>


<?php if ( $success ): ?>
	<div class="updated settings-error">
		<p><strong><?php echo esc_html( $message ); ?></strong></p>
	</div>
<?php endif; ?>


<form method="post" action="admin.php?page=total-slider-settings">
	<input type="hidden" name="total-slider-settings-submitted" value="true" />
	<?php wp_nonce_field ( 'total-slider-settings' ); ?>

	<!-- Only display 'Required Role Level' to manage_options capable users -->
	<?php if ( current_user_can( 'manage_options' ) ):?>

	<table class="form-table edit-controls-form-table">
		<tbody>
			<tr class="form-field">
				<th scope="row">
					<label for="required_capabilities"><?php _e( 'Required role level', 'total_slider' );?></label>
				</th>
				<td><fieldset>
					<?php
					$all_roles = get_editable_roles();
					?>

					<?php
							if ( is_array( $all_roles ) && count( $all_roles ) > 0 ):
								foreach( $all_roles as $r_name => $r ): ?>
						<label for="required_capability_<?php echo esc_attr( $r_name ); ?>">
								<input type="checkbox" name="required_capability_<?php echo esc_attr( $r_name ); ?>"
								id="required_capability_<?php echo esc_attr( $r_name ); ?>" value="1" style="width:20px;"
									<?php
									/* if this role has the total_slider_manage_slides capability, mark it as selected */

									if ( array_key_exists( TOTAL_SLIDER_REQUIRED_CAPABILITY, $r['capabilities'] ) ): ?>
									checked="checked"
									<?php endif;?>

									<?php // lock administrator checkbox on
									if ( 'administrator' == $r_name ):
									 ?>
									disabled="disabled"
									 <?php endif; ?>

								 /> <?php echo esc_html( $r['name'] );?>
							</label><br/>
					<?php endforeach; endif; ?>
					<span class="description"><?php _e( 'Users belonging to checked roles will be able to create, edit and delete slides. Only users that can manage widgets are able to activate, deactivate or move the Total Slider widget, which makes the slides show up on your site.', 'total_slider' );?></span>
				</fieldset></td>
			</tr>
			
			<tr class="form-field">
				<th scope="row">
					<label for="should_show_tinymce_button"><?php _e( 'Editor', 'total_slider' );?></label>			
				</th>
				<td><fieldset>
					<label for="should_show_tinymce_button">
						<input type="checkbox" name="should_show_tinymce_button" id="should_show_tinymce_button" value="1" style="width:20px;"
						<?php echo ( array_key_exists( 'should_show_tinymce_button', $other_options) && intval( $other_options['should_show_tinymce_button'] ) ) ? ' checked="checked"' : ''; ?>
						/> <?php _e( 'Show the Total Slider button in the editor toolbar', 'total_slider' ); ?>
					</label>
				</fieldset></td>
			</tr>

		</tbody>
	</table>

	<h3><?php _e( 'Advanced Settings', 'total_slider' );?></h3>

	<table class="form-table edit-controls-form-table">
		<tbody>
			<tr class="form-field">
				<th scope="row">
					<label for="should_enqueue_template"><?php _e( 'Load JS & CSS', 'total_slider' );?></label>
				</th>
				<td><fieldset>
					<label for="should_enqueue_template">
						<input type="checkbox" name="should_enqueue_template" id="should_enqueue_template" value="1" style="width:20px;"
						<?php echo ( intval( $other_options['should_enqueue_template'] ) ) ? ' checked="checked"' : ''; ?>
						/> <?php _e( 'Automatically load slide template CSS and JavaScript into my theme', 'total_slider' );?>
					</label><br/>
					<span class="description"><?php _e( 'Uncheck for manual control over how slide template CSS and JavaScript are included in your theme.', 'total_slider' );?></span>
				</fieldset></td>
			</tr>
		</tbody>
	</table>

	<?php endif; ?>

<p class="submit">
	<input class="button-primary" type="submit" value="<?php _e( 'Save Changes', 'total_slider' );?>" id="submitbutton" />
</p>

</form>
</div><?php

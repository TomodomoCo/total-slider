<?php
/**
 * Slide Groups page
 *
 * Print the page for adding, deleting Slide Groups and for pushing people over
 * to the 'actual' slides editing interface for that Slide Group.
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

// permissions check
if ( ! current_user_can( TOTAL_SLIDER_REQUIRED_CAPABILITY ) ) {
	?><h1>This page is not accessible to your user.</h1><?php
	return;
}

// add the credits/notes metabox
add_meta_box( 'credits-notes', __( 'Credits', 'total-slider' ), array( $TS_Total_Slider, 'print_credits_metabox' ), '_total_slider_slide_groups', 'side', 'core' );

// if we are to remove a slide group, do that and redirect to home
if ( array_key_exists( 'action', $_GET ) && 'remove' == $_GET['action'] && array_key_exists( 'group', $_GET ) ) {
	
	if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'remove-slide-group' ) ) {
		
		// remove the slide group
		$new_group = new Total_Slide_Group( $_GET['group'] );
		$new_group->delete();


		// redirect back to the admin total slider root page
		$TS_Total_Slider->ugly_js_redirect( 'root' );
		die();

	}
}

// if we are to batch remove slide groups, do so and redirect to home

if (
	/* all the expected $_POST keys exist */
	array_key_exists( 'slidegroup', $_POST ) &&
	(
		array_key_exists('action', $_POST) ||
		array_key_exists('action2', $_POST)
	) &&
	/* wpnonce is set and the actions are 'remove' */
	array_key_exists( '_wpnonce', $_POST ) &&
	(
		$_POST['action'] == 'remove' ||
		$_POST['action2'] == 'remove'
	) &&
	/* there are some slide groups to remove! */
	is_array( $_POST['slidegroup'] ) &&
	count( $_POST['slidegroup'] > 0 )
) {
	
	if ( wp_verify_nonce($_POST['_bulk_wpnonce'], 'remove-bulk-slide-group' ) ) {
		// remove selected slide groups
		
		foreach( $_POST['slidegroup'] as $slide_group ) {
			// remove the slide group
			$new_group = new Total_Slide_Group( $slide_group );
			$new_group->delete();

		}

		// redirect back to the admin total slider root page
		$TS_Total_Slider->ugly_js_redirect( 'root' );
		die();
	}
}

// if the URL otherwise has 'group' in the GET parameters, it's time to pass control
// to print_slides_page() for editing purposes
if ( array_key_exists( 'group', $_GET ) ) {
	$TS_Total_Slider->print_slides_page();
	return;
}

// if we are to create a new slide group, do that and redirect to edit
if (
	array_key_exists( 'action', $_GET ) &&
	'new_slide_group' == $_GET['action'] &&
	array_key_exists( '_wpnonce', $_REQUEST )
) {
	if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'new-slide-group' ) ) {

		if ( ! empty( $_POST['group-name'] ) && ! empty( $_POST['template-slug'] ) ) {
			// add the new slide group
			$new_slug = $TS_Total_Slider->sanitize_slide_group_slug( sanitize_title_with_dashes( $_POST['group-name'] ) );

			// slide group already with this name?
			$existing = new Total_Slide_Group( $new_slug );
			$collision = false;

			if ( $existing->load() ) {
				$collision = true;
			}

			// v2.0: we can no longer have slide groups that have identical names, even if slugs don't clash
			$existing_terms = get_terms( 'total_slider_slide_group', array( 'hide_empty' => false ) );
			if ( ! $collision && is_array( $existing_terms ) && count( $existing_terms ) > 0 ) {
				foreach( $existing_terms as $term ) {
					if ( $term->name == $_POST['group-name'] ) {
						$collision = true;
						break;
					}
				}
			}


			// if collision, throw an error:
			if ( $collision ) {
				$create_error = __( 'Unable to create this slide group, as there is already a group with this name.', 'total-slider' );
			}
			else {

				$new_group = new Total_Slide_Group( $new_slug, $_POST['group-name'] );

				// set the new template
				$desired_tpl_slug = Total_Slider_Template::sanitize_slug( $_POST['template-slug'] );
				$tpl_location = false;
				$tpl_slug = false;

				// determine which template location this template is from
				$t = new Total_Slider_Template_Iterator();

				foreach( Total_Slider::$allowed_template_locations as $l )
				{

					if ($tpl_location || $tpl_slug)	{
						break;
					}

					$choices = $t->discover_templates( $l, false );	

					// find the right template and set our provision template slug and location to it
					if ( is_array( $choices ) && count( $choices ) > 0 )
					{
						foreach( $choices as $c )
						{
							if ( $desired_tpl_slug == $c['slug'] ) {
								$tpl_location = $l;
								$tpl_slug = $desired_tpl_slug;
								break;
							}		
						}
					}

				}

				if ( $tpl_location && $tpl_slug ) {
					$new_group->templateLocation = $tpl_location;
					$new_group->template = $tpl_slug;
				}
				else {
					$new_group->templateLocation = 'builtin';
					$new_group->template = 'default';
				}

				$new_group->save();

				// redirect to the new edit page for this slide group
				$TS_Total_Slider->ugly_js_redirect( 'edit-slide-group', $new_slug );
				die();
			}
		}
	}
}

?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function() {
	jQuery('#new-slide-group-button').click(function(e) {
		e.preventDefault();
		jQuery('#new-slide-group').show('slow');
	});
	jQuery('#new-slide-group-cancel').click(function(e) {
		e.preventDefault();
		jQuery('#new-slide-group').hide('slow');
	});
});
var VPM_SHOULD_DISABLE_XY = false;
//]]>
</script>
<div class="wrap">

<div id="icon-total-slides" class="icon32"><br /></div><h2><?php _e( 'Slide Groups', 'total-slider' );?> <a href="#" id="new-slide-group-button" class="add-new-h2"><?php _e( 'Add New', 'total-slider' );?></a></h2>

<noscript>
<h3><?php _e( 'Sorry, this interface requires JavaScript to function.', 'total-slider' ); ?></h3>
<p><?php _e( 'You will need to enable JavaScript for this page before many of the controls below will work.', 'total-slider' );?></p>
</noscript>

<?php if ( isset( $create_error ) ): ?>
<div id="message" class="error"><?php echo esc_html( $create_error ); ?></div>
<?php endif; ?>

<div id="new-slide-group">
	<form name="new-slide-group-form" id="new-slide-group-form" method="post" action="admin.php?page=total-slider&action=new_slide_group">
		<h3 id="new-slide-group-header"><?php _e( 'Add a Slide Group', 'total-slider' ); ?></h3>
		<?php wp_nonce_field( 'new-slide-group' );?>
		<table class="form-table" style="max-width:690px">

			<tr class="form-field form-required">
				<th scope="row"><label for="group-name"><?php _e( 'Group Name', 'total-slider' ); ?></label></th>
				<td><input name="group-name" type="text" id="group-name" value="" /></td>
			</tr>
			<tr class="form-field form-required">
				<th scope="row"><label for="template-slug"><?php _e( 'Template', 'total-slider' ); ?></label></th>
				<td>
					<?php $t = new Total_Slider_Template_Iterator(); ?>
					<select name="template-slug" id="template-slug">
						
						<?php $builtin = $t->discover_templates( 'builtin' ); ?>
						<?php if ( is_array( $builtin ) && count( $builtin ) > 0 ): ?>
						<optgroup label="<?php _e( 'Built-in', 'total-slider' );?>">
							<?php foreach( $builtin as $tpl ): ?>
								<option value="<?php echo esc_attr( $tpl['slug'] ); ?>"><?php echo esc_html( $tpl['name'] ); ?></option>
							<?php endforeach; ?>
						</optgroup>
						<?php endif; ?>
						
						<?php $theme = $t->discover_templates( 'theme' ); ?>
						<?php if ( is_array( $theme ) && count( $theme ) > 0 ): ?>
						<optgroup label="<?php _e( 'Theme', 'total-slider' ); ?>">
							<?php foreach($theme as $tpl): ?>
								<option value="<?php echo esc_attr( $tpl['slug' ]); ?>"><?php echo esc_html( $tpl['name'] ); ?></option>
							<?php endforeach; ?>
						</optgroup>
						<?php endif; ?>
						
						<?php $legacy = $t->discover_templates( 'legacy', false ); ?>
						<?php if ( is_array( $legacy ) && count( $legacy ) > 0 ): ?>
						<optgroup label="<?php _e( 'v1.0 Templates', 'total-slider' ); ?>">
							<?php foreach($legacy as $tpl): ?>
								<option value="<?php echo esc_attr( $tpl['slug'] ); ?>"><?php echo esc_html( $tpl['name'] ); ?></option>
							<?php endforeach; ?>
						</optgroup>								
						<?php endif; ?>
				
						<?php //$downloaded = $t->discover_templates( 'downloaded' ); ?>
						<?php $downloaded = false; ?>
						<?php if ( is_array( $downloaded ) && count( $downloaded ) > 0 ): ?>
						<!--<optgroup label="<?php _e( 'Downloaded', 'total-slider' ); ?>">
							<?php foreach( $downloaded as $tpl ): ?>
								<option value="<?php echo esc_attr( $tpl['slug'] ); ?>"><?php echo esc_html( $tpl['name'] ); ?></option>
							<?php endforeach; ?>																
						</optgroup>	-->
						<?php endif; ?>
														
					</select>
				</td>
			</tr>
		</table>
		<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Add Slide Group', 'total-slider' ); ?>"  />
		<input type="button" id="new-slide-group-cancel" class="button-secondary" value="<?php _e( 'Cancel', 'total-slider' ); ?>" /></p></form>
	</form>
</div>

<div id="poststuff">
	<div class="metabox-holder columns-2">

		<div class="inner-sidebar" id="postbox-container-1">
			<?php do_meta_boxes( '_total_slider_slide_groups', 'side', null ); ?>
		</div>

		<div id="post-body" class="columns-2"><div id="post-body-content">
		<form id="slide-groups-bulk-actions" method="post" action="admin.php?page=total-slider" onsubmit="if ( jQuery('.slide-group-checkbox:checked').length > 0 && jQuery('option[value=remove]:selected').length > 0) { return confirm('<?php _e( 'Are you sure you want to delete these slide groups?\n\nThis action cannot be undone.', 'total-slider' );?>'); }">
			<input type="hidden" name="_bulk_wpnonce" value="<?php echo wp_create_nonce( 'remove-bulk-slide-group' ); ?>" />
			<?php require_once( dirname( __FILE__ ) . '/../includes/class.slide-groups-table.php' );
			$table = new Slide_Groups_Table();
			$table->prepare_items();
			$table->display();
			?>
		</form>
		</div></div>

	</div>
</div>

</div><!--wrap-->
<?php

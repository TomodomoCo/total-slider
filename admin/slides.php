<?php
/*
 * Slides page
 * Print the actual slides page for adding, editing and removing the slides.
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
if ( ! current_user_can( TOTAL_SLIDER_REQUIRED_CAPABILITY ) )
{
	?><h1><?php _e( 'This page is not accessible to your user.', 'total-slider' ); ?></h1><?php
	return;
}

$TS_Total_Slider->slug = $TS_Total_Slider->sanitize_slide_group_slug( $_GET['group'] );

if ( empty( $TS_Total_Slider->slug ) ) {
	echo '<div class="wrap"><h1>';
	_e( 'No Slide Group selected.', 'total-slider' );
	echo '</h1></div>';
	return;
}

// get the name data for this slide group based on its slug
$slide_group = new Total_Slide_Group( $TS_Total_Slider->slug );

if ( ! $slide_group->load() ) {
	echo '<div class="wrap"><h1>';
	_e( 'Could not load the selected Slide Group. Does it exist?', 'total-slider' );
	echo '</h1></div>';
	return;
}

// determine and load template
if ( ! $TS_Total_Slider->template || ! is_a( $TS_Total_Slider->template, 'Total_Slider_Template' ) )
{
	$TS_Total_Slider->determine_template();
}


if (
	'post' == strtolower( $_SERVER['REQUEST_METHOD'] ) &&
	array_key_exists( 'action', $_GET ) &&
	'changeTemplate' == $_GET['action']
) {
	
	// change the template and redirect
	if ( ! wp_verify_nonce( $_POST['total-slider-change-template-nonce'], 'total-slider-change-template' ) ) {
		die( __( 'Unable to confirm the form’s security.', 'total-slider' ) );
	}
	
	// update the new template
	$desired_tpl_slug = Total_Slider_Template::sanitize_slug( $_POST['template-slug'] );
	$tpl_location = false;
	$tpl_slug = false;

	// determine which template location this template is from
	$t = new Total_Slider_Template_Iterator();
	
	foreach( Total_Slider::$allowed_template_locations as $l ) {
	
		if ( $tpl_location || $tpl_slug ) {
			break;
		}
	
		$choices = $t->discover_templates($l, false);						

		// find the right template and set our provision template slug and location to it
		if ( is_array( $choices ) && count( $choices ) > 0 ) {
			foreach( $choices as $c ) {
				if ( $desired_tpl_slug == $c['slug'] ) {
					$tpl_location = $l;
					$tpl_slug = $desired_tpl_slug;
					break;
				}								
			}
		}
								
	}
	
	if (
		$tpl_location == $slide_group->templateLocation &&
		$tpl_slug == $slide_group->template
	) {
		// avoid being destructive if it's unnecessary
		// there is no change, so just go back
		$this->ugly_js_redirect( 'edit-slide-group', $slide_group->slug );
		die();								
	}
	
	if ( $tpl_location && $tpl_slug ) {
		$slide_group->templateLocation = $tpl_location;
		$slide_group->template = $tpl_slug;
	}
	else {
		$slide_group->templateLocation = 'builtin';
		$slide_group->template = 'default';
	}

	// remove X/Y positioning data, or else we may have an off-screen title box on the new template
	$slide_group->remove_xy_data();
	$slide_group->save();
	
	$TS_Total_Slider->ugly_js_redirect( 'edit-slide-group', $slide_group->slug );
	die();
	
}
// add the metaboxes


add_meta_box( 'slide-sorter-mb', __( 'Slides', 'total-slider' ), array( $TS_Total_Slider, 'print_slide_sorter_metabox' ), '_total_slider_slide', 'normal', 'core' );
add_meta_box( 'slide-preview-mb', __( 'Preview', 'total-slider' ), array( $TS_Total_Slider, 'print_slide_preview_metabox' ), '_total_slider_slide', 'normal', 'core' );
add_meta_box( 'slide-editor-mb', __( 'Edit', 'total-slider' ), array( $TS_Total_Slider, 'print_slide_editor_metabox' ), '_total_slider_slide_bottom', 'normal', 'core' );
add_meta_box( 'slide-template-mb', __( 'Template', 'total-slider' ), array( $TS_Total_Slider, 'print_slide_template_metabox' ), '_total_slider_slide_bottom', 'side', 'core' ); 
add_meta_box( 'credits-notes-mb', __( 'Credits', 'total-slider' ), array( $TS_Total_Slider, 'print_credits_metabox' ), '_total_slider_slide_bottom', 'side', 'core' );



if ( function_exists( 'find_posts_div' ) ) {
	// bring in the post/page finder interface for links
	find_posts_div();
}

?>
<!-- Proxy template change form -->
<form name="template-switch-form" id="template-switch-form" method="POST" action="admin.php?page=total-slider&amp;group=<?php echo $TS_Total_Slider->slug; ?>&amp;action=changeTemplate">
<?php wp_nonce_field( 'total-slider-change-template', 'total-slider-change-template-nonce' ); ?>
<input type="hidden" id="template-slug" name="template-slug" value="<?php echo esc_attr( $slide_group->template ); ?>" />
</form>


<script type="text/javascript">
//<![CDATA[
var VPM_WP_ROOT = '<?php echo admin_url(); ?>';
var VPM_HPS_PLUGIN_URL = '<?php echo admin_url(); ?>admin.php?page=total-slider&total-slider-ajax=true&';
var VPM_HPS_GROUP = '<?php echo esc_attr($TS_Total_Slider->slug); ?>';
document.title = '‘<?php echo esc_attr($slide_group->name); ?>’ Slides ' + document.title.substring(13, document.title.length);//TODO i18n

var VPM_SLIDE_GROUP_TEMPLATE = '<?php echo esc_attr( $slide_group->template );?>';
var VPM_SLIDE_GROUP_TEMPLATE_LOCATION = '<?php echo esc_attr( $slide_group->templateLocation );?>';

<?php if ( $TS_Total_Slider->template && is_a( $TS_Total_Slider->template, 'Total_Slider_Template' ) ) {
	$template_options = $TS_Total_Slider->template->determine_options(); 	
} ?>

var VPM_SHOULD_DISABLE_XY = <?php echo ( $template_options['disable_xy'] ) ? 'true' : 'false'; ?>;
//]]>
</script>

<div class="wrap">

<div id="icon-total-slides" class="icon32"><br /></div>
<h2><?php printf( __( '‘%s’ Slides', 'total-slider' ), esc_html( $slide_group->name ) );?>
<a href="#" id="new-slide-button" class="add-new-h2"><?php _e( 'Add New', 'total-slider' );?></a></h2>

<noscript>
<h3><?php _e( 'Sorry, this interface requires JavaScript to function.', 'total-slider' );?></h3>
<p><?php _e( 'You will need to enable JavaScript for this page before any of the controls below will work.', 'total-slider' );?></p>
</noscript>

<?php if ( $TS_Total_Slider->tpl_error ): ?>
	<div id="template-error" class="updated settings-error below-h2">
	<h3><?php _e( 'There is a problem with this slide group&rsquo;s template.', 'total-slider' ); ?></h3>
	<p><?php echo esc_html( $TS_Total_Slider->tpl_error->getMessage() ); ?> <em><?php printf( __( '(error code %d)', 'total-slider' ), intval( $TS_Total_Slider->tpl_error->getCode() ) ); ?></em></p>
	<p><?php _e( 'Please either resolve this problem, or choose a different template for this slide group.', 'total-slider' ); ?></p>
	</div>
<?php endif; ?>

<form name="vpm-the-slides">

	<div id="message-area" class="updated settings-error below-h2"></div>
	<div id="poststuff">
		<div class="metabox-holder">
			<?php do_meta_boxes( '_total_slider_slide', 'normal', null );?>
		</div>

		<div class="metabox-holder columns-2">
			<div class="inner-sidebar" id="postbox-container-1">
				<?php do_meta_boxes( '_total_slider_slide_bottom', 'side', null );?>

			</div>
			<div id="post-body" class="columns-2">
				<div id="post-body-content">
					<?php do_meta_boxes( '_total_slider_slide_bottom', 'normal', null );?>

				</div>
			</div>
		</div>
	</div>
</form>
</div><?php

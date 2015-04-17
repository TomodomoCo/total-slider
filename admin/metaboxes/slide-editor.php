<?php
/*
 * Slide Editor metabox
 *
 * Print to output the contents of the slide editor metabox.
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

	<div id="edit-controls-choose-hint">
		<p><?php _e( 'Click a slide to edit it, or click ‘Add New’.', 'total-slider' );?></p>					
	</div>

	<div id="edit-controls">
	<form id="edit-form">
		<table class="form-table edit-controls-form-table">
			<tbody>
				<tr class="form-field" id="slide-title-container">
					<th scope="row">
						<label for="edit-slide-title"><?php _e( 'Title', 'total-slider' );?></label>
					</th>
					<td>
						<input type="text" name="slide-title" id="edit-slide-title" value="" maxlength="2048" class="edit-controls-inputs" />
					</td>
				</tr>
				<tr class="form-field" id="slide-description-container">
					<th scope="row">
						<label for="edit-slide-description"><?php _e( 'Description', 'total-slider' );?></label>
					</th>
					<td>
						<textarea name="slide-description" id="edit-slide-description" class="widefat edit-controls-inputs" rows="4"></textarea>
					</td>
				</tr>

				<tr class="form-field" id="slide-image-container">
					<th scope="row">
						<label for="edit-slide-image-upload"><?php _e( 'Background', 'total-slider' );?></label>
					</th>
					<td>
						<input id="edit-slide-image-url" type="hidden" name="slide-image" value="" />
						<input id="edit-slide-image-upload" type="button" class="button" value="<?php _e( 'Upload or choose image', 'total-slider' );?>" />
					</td>
				</tr>

				<tr class="form-field" id="slide-link-container">
					<th scope="row">
						<?php _e( 'Slide Link', 'total-slider' );?>
					</th>
					<td>
						<label for="slide-link-is-internal">
							<input type="radio" style="width:auto;" name="slide-link-is-internal" id="slide-link-is-internal" value="true" />
						<?php _e( 'A page or post on this site', 'total-slider' );?>
						</label>
						<br>
						<label for="slide-link-is-external">
							<input type="radio" style="width:auto;" name="slide-link-is-internal" id="slide-link-is-external" value="false" />
						<?php _e( 'An external link', 'total-slider' );?>
						</label>
					</td>
				</tr>

				<tr class="form-field" id="slide-link-internal-settings">
					<th scope="row">

					</th>
					<td>
						<span id="slide-link-internal-display"><?php _e( 'No post selected', 'total-slider' );?></span>
						<input id="slide-link-internal-id" name="slide-link-internal" value="" type="hidden" />
						<input id="slide-link-finder" type="button" class="button" value="<?php _e( 'Find post', 'total-slider' );?>" style="width:70px;" />
					</td>
				</tr>

				<tr class="form-field" id="slide-link-external-settings">
					<th scope="row">
					</th>
					<td>
						<input type="text" name="slide-link" id="edit-slide-link" value="" maxlength="255" class="edit-controls-inputs" placeholder="http://www.example.com/" />
					</td>
				</tr>

				<tr class="form-field" id="slide-publish-status-container">
					<th scope="row">
						<?php _e( 'Status', 'total-slider' ); ?>
					</th>
					<td>
						<select id="edit-slide-publish-status" name="slide_post_status">
							<option value="draft"><?php _e( 'Draft', 'total-slider' ); ?></option>
							<option value="publish"><?php _e( 'Published', 'total-slider' ); ?></option>
						</select>
					</td>
				</tr>

			</tbody>
		</table>
		<p class="submit">
			<input type="button" id="edit-controls-save-draft" class="button-secondary" value="<?php _e( 'Save Draft', 'total-slider' ); ?>" />
			<input type="button" id="edit-controls-cancel" class="button-secondary" value="<?php _e( 'Cancel', 'total-slider' );?>" />
			<input type="button" id="edit-controls-publish" class="button-primary" value="<?php _e( 'Publish', 'total-slider' );?>" />
			<p class="delete-container">
				<a id="edit-controls-delete-slide" href="#"><?php _e( 'Delete Slide', 'total-slider' ); ?></a>
			</p>

		</p>
			<div id="edit-controls-saving">
			<img id="edit-controls-spinner" src="images/loading.gif" width="16" height="16" alt="<?php _e( 'Loading', 'total-slider' );?>" />
			<span><?php _e( 'Saving…', 'total-slider' );?></span>
		</div>
	</form>

</div>
<?php

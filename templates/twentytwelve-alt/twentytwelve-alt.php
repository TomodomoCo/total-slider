<?php
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

/*
Template Options

Crop-Suggested-Width: 960
Crop-Suggested-Height: 325
Disable-XY-Positioning-In-Admin: Yes
*/
?>

<?php if ( !$s->is_runtime() ) : ?>
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,400' rel='stylesheet' type='text/css'>
<style type="text/css">
.total-slider {
	font-family: "Open Sans", Helvetica, Arial, sans-serif;
	max-width: 960px;
}
</style>
<?php endif; ?>

<?php if ( $s->slides_count() > 0 ) : ?>
	<div class="total-slider-container">
		<ul class="total-slider <?php if ( !$s->is_runtime() ) : ?>total-slider-admin<?php else : ?>total-slider-live<?php endif; ?>">
		<?php while ( $s->has_slides() ) : ?>
			<li id="total-slider-slide-<?php $s->the_identifier(); ?>" class="total-slider-slide">
				<a href="<?php $s->the_link(); ?>" class="total-slider-link" style="background-image: url(<?php $s->the_background_url(); ?>);"></a>
				<div class="total-slider-link-wrapper">
					<a class="total-slider-nav-link" data-slide-iteration="<?php echo $s->iteration(); ?>" href="#">
						<h1 class="total-slider-title"><?php $s->the_title(); ?></h1>
						<p class="total-slider-description"><?php $s->the_description(); ?></p>
					</a>
				</div>
			</li>
		<?php endwhile; ?>
		</ul>
		<ul class="total-slider-nav"></ul>
	</div>
<?php endif; ?>

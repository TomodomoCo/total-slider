<?php
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

/*
Template Options

Crop-Suggested-Width: 960
Crop-Suggested-Height: 325
Disable-XY-Positioning-In-Admin: No
*/
?>

<?php if ( !$s->is_runtime() ) : ?>
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,400' rel='stylesheet' type='text/css'>
<style type="text/css">
.ts-twentytwelve {
	font-family: "Open Sans", Helvetica, Arial, sans-serif;
	max-width: 960px;
}
</style>
<?php endif; ?>

<?php if ( $s->slides_count() > 0 ) : ?>
	<ul class="ts-twentytwelve">
	<?php while ( $s->has_slides() ) : ?>
		<li id="ts-twentytwelve-slide-<?php $s->the_identifier(); ?>" class="ts-twentytwelve-slide <?php $s->draggable_parent(); ?>" style="background-image: url(<?php $s->the_background_url(); ?>);">
			<a href="<?php $s->the_link(); ?>" class="ts-twentytwelve-link <?php $s->make_draggable(); ?>" style="left: <?php $s->the_x(); ?>px; top: <?php $s->the_y(); ?>px">
				<div class="ts-twentytwelve-overlay">
					<h1 class="ts-twentytwelve-title"><?php $s->the_title(); ?></h1>
					<p class="ts-twentytwelve-description"><?php $s->the_description(); ?></p>
				</div>
			</a>
		</li>
	<?php endwhile; ?>
	</ul>
<?php endif; ?>

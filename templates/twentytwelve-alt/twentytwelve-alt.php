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
.ts-twentytwelve-alt {
	font-family: "Open Sans", Helvetica, Arial, sans-serif;
	max-width: 960px;
}
</style>
<?php endif; ?>

<?php if ( $s->slides_count() > 0 ) : ?>
	<div class="ts-twentytwelve-alt-container">
		<ul class="ts-twentytwelve-alt <?php if ( !$s->is_runtime() ) : ?>ts-twentytwelve-alt-admin<?php else : ?>ts-twentytwelve-alt-live<?php endif; ?>">
		<?php while ( $s->has_slides() ) : ?>
			<li id="ts-twentytwelve-alt-slide-<?php $s->the_identifier(); ?>" class="ts-twentytwelve-alt-slide">
				<a href="<?php $s->the_link(); ?>" class="ts-twentytwelve-alt-link" style="background-image: url(<?php $s->the_background_url(); ?>);" title="<?php $s->the_title(); ?>"></a>
				<div class="ts-twentytwelve-alt-link-wrapper">
					<a class="ts-twentytwelve-alt-nav-link" data-slide-iteration="<?php echo $s->iteration(); ?>" href="#">
						<h1 class="ts-twentytwelve-alt-title"><?php $s->the_title(); ?></h1>
						<p class="ts-twentytwelve-alt-description"><?php $s->the_description(); ?></p>
					</a>
				</div>
			</li>
		<?php endwhile; ?>
		</ul>
		<ul class="ts-twentytwelve-alt-nav"></ul>
	</div>
<?php endif; ?>

<?php
/********************************************************************************

	VPM Slider Default Theme
	
	The default theme for showing the slides. Used if there is no vpm-slider.php
	file found in the active theme's directory.

*********************************************************************************/

/*  Copyright (C) 2011-2012 Peter Upfold.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>

<ul>
<?php

while ($s->has_slides())
{

	?><li id="<?php $s->the_identifier();?>">
		<strong>Title:</strong> <?php $s->the_title(); ?><br />
		<strong>Description:</strong> <?php $s->the_description(); ?><br />
		<strong>Background:</strong> <?php $s->the_background_url(); ?><br />
		<strong>Link:</strong> <?php $s->the_link(); ?><br />
		<strong>X:</strong> <?php $s->the_x(); ?><br />
		<strong>Y:</strong> <?php $s->the_y(); ?><br />
	</li>
	<?php

}

?></ul>
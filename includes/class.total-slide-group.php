<?php
/*
 * This file contains the Total Slider Slide Group Class, which provides a set of methods for manipulating
 * the slide group and its slides at the database level.
 *
/*
Total Slider Slide Group Class
	
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

/*
 * NEW DATA FORMAT PLAN:
 *
 * Each slide is a custom post type wp_post. The custom post type is for Slides, not Slide Groups.
 *
 * Slide Groups are implemented as a taxonomy, allowing a Slide Group's slide to be retrieved with
 * a search on the taxonomy.
 *
 * Slide fields other than title/description are implemented as custom fields.
 *
 *
 */

if ( ! defined('TOTAL_SLIDER_IN_FUNCTIONS' ) ) {
	header( 'HTTP/1.1 403 Forbidden' );
	die( '<h1>Forbidden</h1>' );
}

/*
 * Class: This class provides a set of methods for manipulating the slide group and its slides at the database level. It is used by ajax_interface.php, primarily.
 *
 * Defines a slide group object for the purposes of storing a list of available groups in the wp_option 'total_slider_slide_groups'.
 * This object specifies the slug and friendly group name. We then use the slug to work out which wp_option to query later -- total_slider_slides_[slug].
 * This class provides a set of methods for manipulating the slide group and its slides at the database level. It is used by ajax_interface.php, primarily.
 *
 */	
class Total_Slide_Group { 

	/**
	 * The URL-friendly slug of this Slide Group.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * The original slug of this Slide Group, if it becomes sanitized for display.
	 *
	 * @var string
	 */
	public $originalSlug;

	/**
	 * The friendly name of this Slide Group.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The location of the template used to render this Slide Group. One of 'builtin','theme','downloaded','legacy'.
	 *
	 * @var string
	 */
	public $templateLocation;

	/**
	 * The slug of the template used to render this Slide Group. Along with templateLocation, this allows the template to be identified
	 * and located.
	 *
	 * @var string
	 */
	public $template;

	/**
	 * The term ID of this slide group's taxonomy term.
	 *
	 * @var integer
	 */
	private $term_id;
	
	/**
	 * Set the slug and name for this Slide Group.
	 *
	 * @param string $slug Slug for this Slide Group.
	 * @param string $name Name of this Slide Group.
	 * @return void
	 */
	public function __construct( $slug, $name = null ) {
	
		$this->slug = $this->sanitize_slug($slug);
		$this->originalSlug = $this->slug;
		
		if ($name)
		{
			$this->name = $name;
		}
	}
	
	/**
	 * Sanitize a Slide Group slug, so that we can successfully access a wp_option row with that slug.
	 *
	 * @param string $slug The slug to sanitize.
	 * @return string
	 */
	public function sanitize_slug( $slug ) {
		return substr( preg_replace( '/[^a-zA-Z0-9_\-]/', '', $slug ), 0, ( 63 - strlen( 'total_slider_slides_' ) ) );
	}	
	
	/**
	 * Fetch this Slide Group's properties, including slug, name, templateLocation, template, from the database.
	 *
	 * This uses the slug provided in the constructor to load in this Slide Group's other properties.
	 *
	 * @return boolean
	 *
	 */
	public function load() {
	
	
		$term = get_term_by( 'slug', $this->originalSlug, 'total_slider_slide_group' );


		$this->name = $term->name;
		$this->slug = Total_Slider::sanitize_slide_group_slug( $term->slug );
		$this->term_id = intval( $term->term_id );

		// get template from wp_option
	
			/*
				The slide group template and template location are stored in a wp_option
				similarly named to that which was formerly (1.x) used for the actual slide data.

				This is a string, delimited by a pipe (|) character, with the templateLocation
				('builtin','theme','downloaded','legacy') before the pipe, and the template
				slug after the pipe.
			*/
			 
		
		$group_options = get_option( 'total_slider_grptpl_' . $this->slug);

		if ( false === $group_options ) {
			// set default template information if none found
			$this->set_default_template();
		}
		else {
			$group_options_expl = explode( '|', $group_options );

			if ( ! is_array( $group_options_expl ) || count( $group_options_expl ) < 1 ) {
				$this->set_default_template();
			}
			else {
				foreach( $group_options_expl as $opt => $key ) {
					if ( $key == 1 ) { // templateLocation
						if ( ! in_array( $opt, Total_Slider::$allowed_template_locations ) ) {
							$this->set_default_template();
							break;
						}

						$this->templateLocation = $opt;

					}
					if ( $key == 2 ) { // template slug
						$this->template = Total_Slider_Template::sanitize_slug( $opt );
					}
				}
			}

		}

	}
	
	/**
	 * Save this Slide Group, as currently represented in this object, to the database.
	 *
	 * @return void
	 *
	 */
	public function save() {
		//TODO	
		if ( ! get_option('total_slider_slide_groups' ) ) {
			// create option
			add_option( 'total_slider_slide_groups', array(), '', 'yes' );
		}
		
		// get the current slide groups
		$current_groups = get_option( 'total_slider_slide_groups' );
		
		$the_index = false;
		
		// loop through to find one with this original slug
		foreach( $current_groups as $key => $group ) {
			if ( $group->slug == $this->originalSlug ) {
				$the_index = $key;
				break;
			}
		}
		
		if ( false === $the_index ) {
			// add this as a new slide group at the end
			$current_groups[] = $this;
		}
		else {
			// replace the group at $theIndex with the new information
			$current_groups[$the_index] = $this;
		}
		
		// save the groups list
		update_option( 'total_slider_slide_groups', $current_groups );
	
	}
	
	/**
	 * Delete this Slide Group from the database and all its Slides.
	 *
	 * @return void
	 */
	public function delete() {

		if ( ! isset( $this->term_id ) ) {
			$this->load();
		}

		// delete all slides first
		$to_delete = array();

		$args = array(
			'post_type' => 'total_slider_slide',
			'tax_query' => array(
				array(
					'taxonomy' => 'total_slider_slide_group',
					'field'    => 'slug',
					'terms'    => $this->slug,
				),
			),
		);
		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while( $query->have_posts() ) {
				$query->the_post();

				$to_delete[] = get_the_ID();
			}
		}

		wp_reset_postdata();

		if ( count( $to_delete ) > 0 ) {
			foreach( $to_delete as $id ) {
				wp_delete_post( $id, true );
			}
		}

		// now delete the slide group
		delete_option( 'total_slider_grptpl_' . $this->slug);
		wp_delete_term( $this->term_id, 'total_slider_slide_group' );

	}
	
	/**
	 * Given a pre-validated set of data, create a new slide.
	 *
	 * Also returns the new slide ID for re-sorting purposes.
	 *
	 * @param string $title
	 * @param string $description
	 * @param mixed $background This can be specified as a URL, or an attachment ID.
	 * @param mixed $link This can be specified as a URL, or a post ID.
	 * @param integer $title_pos_x The X-offset where the description box should be displayed.
	 * @param integer $title_pos_y The Y-offset where the description box should be displayed.
	 *
	 * @return integer
	 */
	public function new_slide( $title, $description, $background, $link, $title_pos_x, $title_pos_y ) {

		if ( ! isset( $this->term_id ) ) {
			$this->load();
		}

		$new_post_data = array(
			'post_content'        => $description,
			'post_title'          => $title,
			'post_name'           => sanitize_title( $title ),
			'post_status'         => 'publish',
			'post_type'           => 'total_slider_slide',
			'comment_status'      => 'closed',
			'tax_input'           => array(
			                         	'total_slider_slide_group' => $this->term_id
			                         )
		);

		$result = wp_insert_post( $new_post_data, true );

		return $result;
	
		$current_slides = get_option('total_slider_slides_' . $this->slug);
		
		if (false === $current_slides) {
			
			$this->save();
			
			$current_slides = get_option( 'total_slider_slides_' . $this->slug );
			if (false === $current_slides)
			{
				return false; //can't do it
			}
		}
		
		$new_id = str_replace('.', '', uniqid('', true));
		
		$new_slide = array(
		
			'id' => $new_id,
			'title' => $title,
			'description' => $description,
			'background' => $background,
			'link' => $link,
			'title_pos_x' => $title_pos_x,
			'title_pos_y' => $title_pos_y		
		
		);	
		
		$current_slides[] = $new_slide;
		
		if ( $this->save_slides( $current_slides) ) {
			return $new_id;
		}
		else {
			return false;
		}
		
		
	}
	
	/**
	 * Fetch the given slide from this Slide Group.
	 *
	 * @param string $slide_id
	 * @return array
	 */
	public function get_slide( $slide_id ) {
	
		$current_slides = get_option( 'total_slider_slides_' . $this->slug );
		
		if (
			false === $current_slides ||
			! is_array( $current_slides ) ||
			count( $current_slides ) < 0
		) {
			return false;
		}
		
		else {
		
			foreach( $current_slides as $slide ) {
			
				if ( $slide['id'] == $slide_id ) {
				
					if ( (int) $slide['link'] == $slide['link'] ) {
						// if slide link is a number, and therefore a post ID of some sort
						$slp = (int) $slide['link'];
						$link_post = get_post($slp);
						if ($link_post)
						{
							$slide['link_post_title'] = $link_post->post_title;
						}
					}
					
					if ( (int) $slide['background'] == $slide['background'] && $slide['background'] > 0 ) {
						// if slide background is a number, it must be an attachment ID
						// so get its URL
						$slide['background_url'] = wp_get_attachment_url((int)$slide['background']);
						
						if ( $slide['background_url'] == false )
						{
							/* 
								If it failed to look up, simply fail to provide the URL.
								We must not provide (string)'false' as the URL or things will break.
								
								'false' isn't a valid URL, but will be loaded into the frontend, and stays unless replaced by the user
								during the edit process. This will bite the user when they then try and save, as they will be told
								the background URL is not valid.
							*/
							unset( $slide['background_url'] );
						}
					}
				
					return $slide;
				
				}			
			
			}
			
			// if we didn't find it
			
			return false;
		}
	
	}
	
	/**
	 * Update the slide with the specified ID with the supplied pre-validated data.
	 *
	 * @param string $slide_id
	 * @param string $title
	 * @param string $description
	 * @param mixed $background This can be specified as a URL, or an attachment ID.
	 * @param mixed $link This can be specified as a URL, or a post ID.
	 * @param integer $title_pos_x The X-offset where the description box should be displayed.
	 * @param integer $title_pos_y The Y-offset where the description box should be displayed.
	 * @return boolean
	 */
	public function update_slide( $slide_id, $title, $description, $background, $link, $title_pos_x, $title_pos_y ) {
	
		$current_slides = get_option( 'total_slider_slides_' . $this->slug );
		$original_slides = $current_slides;
		
		if (
			false === $current_slides ||
			!is_array( $current_slides ) ||
			count( $current_slides ) < 0
		) {
			return false;
		}
		
		else {
		
			$found = false;
		
			foreach( $current_slides as $i => $slide ) {
			
				if ( $slide['id'] == $slide_id ) {
				
					// we found the record we were looking for. update it
					$current_slides[$i]['title'] = $title;
					$current_slides[$i]['description'] = $description;
					$current_slides[$i]['background'] = $background;
					$current_slides[$i]['link'] = $link;
					$current_slides[$i]['title_pos_x'] = $title_pos_x;
					$current_slides[$i]['title_pos_y'] = $title_pos_y;
				
					$found = true;
				
				}	
			
			}
			
			if ( ! $found ) {
				return false;
			}
		}
		
		if ( $current_slides === $original_slides )
		{
			return true; // no change, don't bother update_option as it returns false and errors us out
		}
		
		// $current_slides now holds the slides we want to save
		return $this->save_slides( $current_slides );
	
	}
	
	/**
	 * Assess whether or not the given string is in a valid URL format.
	 *
	 * Imported from Drupal 7 common.inc:valid_url. This function is Drupal code and is Copyright 2001 - 2010 by the original authors.
	 * This function, like the rest of this software, is GPL2-licensed.
	 *
	 * @param string $url
	 * @return boolean
	 */
	public function validate_url($url) {

		if ( $url === '#' )
		{
			// allow a '#' character only
			return true;
		}
		else {
	
			return (bool) preg_match( "
	      /^                                                      # Start at the beginning of the text
	      (?:ftp|https?|feed):\/\/                                # Look for ftp, http, https or feed schemes
	      (?:                                                     # Userinfo (optional) which is typically
	        (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*      # a username or a username and password
	        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@          # combination
	      )?
	      (?:
	        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+                        # A domain name or a IPv4 address
	        |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
	      )
	      (?::[0-9]+)?                                            # Server port number (optional)
	      (?:[\/|\?]
	        (?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})   # The path and query (optional)
	      *)?
	    $/xi", $url );
    
    	}
	
	}
	
	/**
	 * Remove this slide from the Slide Group.
	 *
	 * @param string $slide_id
	 * @return boolean
	 */
	public function delete_slide( $slide_id ) {

		$current_slides = get_option( 'total_slider_slides_' . $this->slug );
		
		if ( false === $current_slides ) {
			$this->save();
			
			$current_slides = get_option( 'total_slider_slides_' . $this->slug );
			if ( false === $current_slides ) {
				return false; //can't do it
			}
		}	
		
		if ( is_array( $current_slides) && count($current_slides) > 0 ) {

			$found_it = false;		
			
			foreach( $current_slides as $index => $slide ) {
			
				if ($slide['id'] == $slide_id)
				{
					unset($current_slides[$index]);
					$found_it = true;
					break;
				}
			
			}
			
			if ( ! $found_it )
				return false;
			else
			{
				return $this->save_slides($current_slides);
			}
		
		}
		
		else {
			return false;
		}			
	
	}
	
	/**
	 * Given the new slide order (array of sorted slide IDs as values), sort the slides in this order in the database.
	 *
	 * @param array $new_slide_order
	 * @return boolean
	 */
	public function reshuffle($new_slide_order)
	{

		$current_slides = get_option( 'total_slider_slides_' . $this->slug );
		
		if ( false === $current_slides ) {
			
			$this->save();
			
			$current_slides = get_option( 'total_slider_slides_' . $this->slug );
			if ( false === $current_slides ) {
				return false; //can't do it
			}
		}	
		
		
		if ( is_array( $current_slides ) && count( $current_slides ) > 0 ) {
		
			$new_slides = array();	
			
			$new_slide_not_found_in_current = false;	
			
			foreach( $new_slide_order as $new_index => $new_slide_id ) {			
				$found_this_slide = false;
			
				foreach( $current_slides as $index => $slide ) {
					if ( $slide['id'] == $new_slide_id ) {
						$new_slides[] = $slide;
						$found_this_slide = true;
						continue;
					}
				}
				
				if (!$found_this_slide)
				{
					$new_slide_not_found_in_current = true;
				}
				
			}
			
			if (
				count($current_slides ) != count( $new_slides ) ||
				$new_slide_not_found_in_current
			) {
				// there is a disparity -- so a slide or more will be lost
				return 'disparity';
			}
			
			if ( $new_slides === $current_slides ) {
				return true;
			}
			
			return $this->save_slides($new_slides);
		
		}
		else
		{
			return false;
		}
	
	}


	/**
	 * Set default template, if no template information was found for this Slide Group.
	 *
	 * @return boolean
	 */
	private function set_default_template() {

		return update_option( 'total_slider_grptpl_' . $this->slug, 'builtin|default' );

	}

	/**
	 * If WP_DEBUG is defined and enabled, dump the supplied WP_Error object to the JSON output.
	 *
	 * @var WP_Error The WP_Error object.
	 * @return void
	 */
	private function maybe_dump_wp_error( $error_obj ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			ob_start();

			var_dump( $result );
			$dump = ob_get_contents();

			ob_end_clean();
			echo json_encode(
				array(
					'WP_Error' => $dump
				);
			);
		}
	}

	/**
	 * Save the given valid slide array to the database.
	 *
	 * @param array $slides_to_write
	 * @return boolean
	 */
	private function save_slides($slides_to_write) {
	
		return update_option( 'total_slider_slides_' . $this->slug, $slides_to_write );
	
	}
	
	/**
	 * Remove all X/Y positional information from this Slide Group's slides.
	 *
	 * This is used when changing templates, to avoid the title/description box
	 * from being off-screen on the new template. The user is warned in the UI
	 * before they take this action, as it is destructive to X/Y data.
	 *
	 * @return boolean
	 *
	 */
	public function remove_xy_data() {
	
		$current_slides = get_option( 'total_slider_slides_' . $this->slug );
		
		if ( false === $current_slides ) {
			
			$this->save();
			
			$current_slides = get_option( 'total_slider_slides_' . $this->slug );
			
			if ( false === $current_slides ) {
				return false; //can't do it
			}
		}
		
		if ( is_array( $current_slides ) && count( $current_slides ) > 0 ) {
			foreach( $current_slides as $i => $slide ) {
				$current_slides[$i]['title_pos_x'] = 0;
				$current_slides[$i]['title_pos_y'] = 0;	
			}
			
			$this->save_slides($current_slides);
			return true;
			
		}
		else {
			return true;
		}				
		
	}
	
	/**
	 * Render an HTML mini-preview of the slide images, for use in the widget selector. TODO UNFINISHED
	 *
	 * This allows an at-a-glance verification that the selected slide group is the desired slide group.
	 *
	 * @return void
	 *
	 */
	public function mini_preview() {
		
		/*
			* Extract background images from slides.
			* (Get thumbnail versions?)
			* Get suggested crop width and height, scale down proportionally to calculate thumbnail size of template
			* Render thumbnail images against those dimensions
			* JS to spin through them with some kind of animation?
			
			How do we disclaim that this isn't truly WYSIWYG? Is that a problem?
		*/
		
		if ( empty($this->template) || empty($this->templateLocation) ) {
			if ( ! $this->load() )
				return false;
		}
		
		// load template information
		try {
			$t = new Total_Slider_Template( $this->template, $this->templateLocation );
		}
		catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				printf( __( 'Unable to render the slide mini-preview: %s (error code %d)', 'total_slider' ), $e->getMessage(), $e->getCode() );
			}
			return false;
		}		
		
		$template_options = $t->determine_options();
		
		?><p><strong><?php _e( 'Template:', 'total_slider' );?></strong> <?php echo esc_html( $t->name() ); ?></p><?php
		
		
		$current_slides = get_option( 'total_slider_slides_' . $this->slug );
		
		if (false === $current_slides || !is_array( $current_slides ) || count( $current_slides ) < 0)
		{
			?><p><?php _e( 'There are no slides to show.', 'total_slider' );?></p><?php
			return true;
		}
		
		?><div class="total-slider-mini-preview">
		<ul><?php
		
		foreach( $current_slides as $idx => $slide ) {
		
			if ( is_numeric($slide['background'] ) && intval( $slide['background'] ) == $slide['background'] ) {
				// background references an attachment ID
				$image = wp_get_attachment_image_src( intval( $slide['background'] ), 'thumbnail' );
				$image = $image[0];
			}
			else {
				$image = $slide['background'];				
			}
			?><li><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $slide['title'] ); ?>" title="<?php echo esc_attr( $slide['title'] ); ?>" width="100" height="32" /></li><?php
			
		}
		
		?>
		</ul>
		</div><?php
		
	}
	

};

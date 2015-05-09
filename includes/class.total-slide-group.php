<?php
/*
 * This file contains the Total Slider Slide Group Class, which provides a set of methods for manipulating
 * the slide group and its slides at the database level.
 *
/*
Total Slider Slide Group Class
	
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


		if ( ! $term ) {
			return false;
		}

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
				foreach( $group_options_expl as $key => $opt ) {
					if ( $key == 0 ) { // templateLocation
						if ( ! in_array( $opt, Total_Slider::$allowed_template_locations ) ) {
							$this->set_default_template();
							break;
						}

						$this->templateLocation = $opt;

					}
					if ( $key == 1 ) { // template slug
						$this->template = Total_Slider_Template::sanitize_slug( $opt );
					}
				}
			}

		}

		return true;


	}

	/**
	 * Get the current slides of this Slide Group.
	 *
	 * @return array
	 */
	public function get_slides() {

		if ( ! $this->name || ! $this->term_id ) {
			$this->load();
		}

		$args = array(
			'post_type'          => 'total_slider_slide',
			'tax_query'          => array( array( 
							'taxonomy' => 'total_slider_slide_group',
							'field'    => 'term_id',
							'terms'    => array( $this->term_id ),
						) ),
			'orderby'            => 'meta_value_num',
			'order'              => 'ASC',
			'meta_key'           => 'total_slider_meta_sequence',
		);

		$raw = new WP_Query( $args );

		$slides = array();
		$n = 0;

		while ( $raw->have_posts() ) { 

				$raw->the_post();

				$slides[$n]['id'] = get_the_ID();
				$slides[$n]['title'] = get_the_title();
				$slides[$n]['description'] = get_the_content();

				$bg = get_post_meta( get_the_ID(), '_thumbnail_id', true );
				if ( ! is_numeric( $bg ) || $bg < 1 ) {
					// legacy -- direct URL
					$slides[$n]['background_url'] = get_post_meta( get_the_ID(), 'total_slider_meta_legacy_bgurl', true );
				}
				else {
					// attachment ID, so let's parse it
					$slides[$n]['background_url'] = wp_get_attachment_url( $bg ); 
				}

				$slides[$n]['background'] = $slides[$n]['background_url'];

				$link = get_post_meta( get_the_ID(), 'total_slider_meta_link', true );

				if ( ! is_numeric( $link ) || $link < 1 ) {
					// external URL
					$slides[$n]['link'] = $link;
				}
				else {
					// post or page ID
					$slides[$n]['link'] = get_permalink( $link );
					$slp = (int) $slides[$n]['link'];

					$link_post = get_post($slp);
					if ($link_post)
					{
						$slides[$n]['link_post_title'] = $link_post->post_title;
					}
				}

				$slides[$n]['title_pos_x'] = get_post_meta( get_the_ID(), 'total_slider_meta_title_pos_x', true );
				$slides[$n]['title_pos_y'] = get_post_meta( get_the_ID(), 'total_slider_meta_title_pos_y', true );
				$slides[$n]['post_status'] = get_post_status( get_the_ID() );
 
				++$n;
		}


		wp_reset_postdata();

		return $slides;

	}


	/**
	 * Save this Slide Group, as currently represented in this object, to the database.
	 *
	 * @return void
	 *
	 */
	public function save() {

		$existing = term_exists( $this->name, 'total_slider_slide_group' );

		if ( $existing ) {
			$result = wp_update_term( $existing['term_id'], 'total_slider_slide_group', array(
		       		'name'     => $this->name,
				'slug'     => $this->slug,
			) );
		}
		else {
			wp_insert_term( $this->name, 'total_slider_slide_group', array(
				'name'     => $this->name,
				'slug'     => $this->slug,
				'taxonomy' => 'total_slider_slide_group'
			) );
		}

		// update or set template information
		if ( empty( $this->template ) || ! in_array( $this->templateLocation, Total_Slider::$allowed_template_locations ) ) {
			$this->set_default_template();
		}
		else {
			update_option( 'total_slider_grptpl_' . $this->slug, $this->templateLocation . '|' . Total_Slider_Template::sanitize_slug( $this->template ) );
		}
	
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
					'terms'    => array( $this->slug ),
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
	 * Also returns the new slide ID for re-sorting purposes. Always puts this new slide at the end of the sequence.
	 *
	 * @param string $title
	 * @param string $description
	 * @param mixed $background This can be specified as a URL, or an attachment ID.
	 * @param mixed $link This can be specified as a URL, or a post ID.
	 * @param integer $title_pos_x The X-offset where the description box should be displayed.
	 * @param integer $title_pos_y The Y-offset where the description box should be displayed.
	 * @param string $status Whether this slide should be 'publish' or 'draft'
	 *
	 * @return integer|WP_Error
	 */
	public function new_slide( $title, $description, $background, $link, $title_pos_x, $title_pos_y, $status ) {

		if ( ! isset( $this->term_id ) ) {
			$this->load();
		}

		if ( !in_array( $status, Total_Slider::$allowed_post_statuses ) ) {
			throw new UnexpectedValueException( sprintf( __( 'The slide cannot be created with the \'%s\' status, as this is not supported by %s.', 'total-slider' ), esc_html( $status ), 'Total Slider' ) );
			return false;
		}

		$new_post_data = array(
			'post_content'        => $description,
			'post_title'          => $title,
			'post_name'           => sanitize_title( $title ),
			'post_status'         => $status,
			'post_type'           => 'total_slider_slide',
			'comment_status'      => 'closed',
		);

		$result = wp_insert_post( $new_post_data, true );

		if ( is_int( $result ) ) {
			// set slide group taxonomy
			wp_set_object_terms( $result, array( $this->slug ), 'total_slider_slide_group' );

			// set meta
			update_post_meta( $result, 'total_slider_meta_link', $link );
			update_post_meta( $result, 'total_slider_meta_title_pos_x', $title_pos_x );
			update_post_meta( $result, 'total_slider_meta_title_pos_y', $title_pos_y );

			if ( is_numeric( $background ) ) {
				update_post_meta( $result, '_thumbnail_id', $background );
			}
			else {
				update_post_meta( $result, 'total_slider_meta_legacy_bgurl', $background );
			}

			// we need to know the highest sort order number for this group and add +1 to this meta
			// we will query all other slides in this group, sorted properly, and grab the last one's sequence
			$highest_sequence_posts = new WP_Query( array(
				'post_type'      => 'total_slider_slide',
				'tax_query'      => array( array( 
							'taxonomy' => 'total_slider_slide_group',
							'field'    => 'slug',
							'terms'    => array( $this->slug ),
						) ),
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'meta_key'       => 'total_slider_meta_sequence',
				'posts_per_page' => 1,

			) );

			if ( $highest_sequence_posts->have_posts() ) {
				$highest_sequence_posts->the_post();
				$sequence = get_post_meta( get_the_ID(), 'total_slider_meta_sequence', true );

				if ( empty( $sequence ) ) {
					$sequence = 0;
				}
				else {
					$sequence = intval( $sequence ) + 1;
				}				

			}
			else {
				$sequence = 0;
			}

			wp_reset_postdata(); 

			update_post_meta( $result, 'total_slider_meta_sequence', $sequence );

		}

		return $result;		
		
	}
	
	/**
	 * Fetch the given slide from this Slide Group.
	 *
	 * @param integer $slide_id
	 * @return array
	 */
	public function get_slide( $slide_id ) {
		
		$post = get_post( $slide_id );
	
		if ( ! $post ) {
			return false;
		}

		$slide = array();

		$slide['id'] = $slide_id;
		$slide['title'] = $post->post_title;
		$slide['slug'] = $post->post_name;
		$slide['description'] = $post->post_content;

		// link, background, other meta

		$slide['background'] = get_post_meta( $slide_id, '_thumbnail_id', true );
	
		if ( empty( $slide['background'] ) ) {
			// uses legacy URL string
			$slide['background'] = get_post_meta( $slide_id, 'total_slider_meta_legacy_bgurl', true );
			$slide['background_url'] = $slide['background'];
		}
		else {
			// extract background image URL for frontend
			$slide['background_url'] = wp_get_attachment_url( $slide['background'] );
		}

		$slide['title_pos_x'] = get_post_meta( $slide_id, 'total_slider_meta_title_pos_x', true );
		$slide['title_pos_y'] = get_post_meta( $slide_id, 'total_slider_meta_title_pos_y', true );

		$slide['link'] = get_post_meta( $slide_id, 'total_slider_meta_link', true );

		if ( is_numeric( $slide['link'] ) ) {
			$slide['link_url'] = get_permalink( $slide['link'] );
			
			$slp = (int) $slide['link'];

			$link_post = get_post($slp);
			if ($link_post)
			{
				$slide['link_post_title'] = $link_post->post_title;
			}
		}
		else {
			$slide['link_url'] = $slide['link'];
		}

		$slide['sequence'] = get_post_meta( $slide_id, 'total_slider_meta_sequence', true );
		$slide['post_status'] = get_post_status( $slide_id );
		
		return $slide;
	
	}
	
	/**
	 * Update the slide with the specified ID with the supplied pre-validated data.
	 *
	 * @param integer $slide_id
	 * @param string $title
	 * @param string $description
	 * @param mixed $background This can be specified as a URL, or an attachment ID.
	 * @param mixed $link This can be specified as a URL, or a post ID.
	 * @param integer $title_pos_x The X-offset where the description box should be displayed.
	 * @param integer $title_pos_y The Y-offset where the description box should be displayed.
	 * @param string $post_status The post status -- 'draft' or 'publish', of this slide.
	 * @return boolean|WP_Error
	 */
	public function update_slide( $slide_id, $title, $description, $background, $link, $title_pos_x, $title_pos_y, $post_status ) {

		// only allow total_slider_slide CPT objects to be updated
		$check = get_post( $slide_id );

		if ( ! $check ) {
			return false;
		}

		if ( $check->post_type != 'total_slider_slide' ) {
			return false;
		}

		$updated_post_args = array(
			'ID'           => $slide_id,
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => $post_status
		);

		$result = wp_update_post( $updated_post_args, true );

		if ( is_int( $result ) && $result > 0 ) {
			// also update post meta
			update_post_meta( $result, 'total_slider_meta_link', $link );
			update_post_meta( $result, 'total_slider_meta_title_pos_x', $title_pos_x );
			update_post_meta( $result, 'total_slider_meta_title_pos_y', $title_pos_y );

			if ( is_numeric( $background ) ) {
				update_post_meta( $result, '_thumbnail_id', $background );
			}
			else {
				update_post_meta( $result, 'total_slider_meta_legacy_bgurl', $background );
			}

		}

		return $result;

		
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
	 * @param integer $slide_id
	 * @return boolean
	 */
	public function delete_slide( $slide_id ) {

		// only allow total_slider_slide CPT objects to be deleted
		$check = get_post( $slide_id );

		if ( ! $check ) {
			return false;
		}

		if ( $check->post_type != 'total_slider_slide' ) {
			return false;
		}
		return wp_delete_post( $slide_id, true );
	
	}
	
	/**
	 * Given the new slide order (array of sorted slide IDs as values), sort the slides in this order in the database.
	 *
	 * @param array $new_slide_order
	 * @return boolean
	 */
	public function reshuffle($new_slide_order)
	{

		if ( ! is_array( $new_slide_order ) || count( $new_slide_order ) < 1 ) {
			return false;
		}
		foreach( $new_slide_order as $sequence => $slide ) {
			update_post_meta( intval( $slide ), 'total_slider_meta_sequence', $sequence );
		}

		return true;
	
	}


	/**
	 * Set default template, if no template information was found for this Slide Group.
	 *
	 * @return boolean
	 */
	private function set_default_template() {

		$this->template = 'default';
		$this->templateLocation = 'builtin';

		return update_option( 'total_slider_grptpl_' . $this->slug, 'builtin|default' );

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
					'terms'    => array( $this->slug ),
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
				update_post_meta( $id, 'total_slider_meta_title_pos_x', 0 );
				update_post_meta( $id, 'total_slider_meta_title_pos_y', 0 );
			}
		}

		return true;
		
	}
	
	/**
	 * Render an HTML mini-preview of the slide images, for use in the widget selector. TODO UNFINISHED
	 *
	 * This allows an at-a-glance verification that the selected slide group is the desired slide group.
	 *
	 * @return void
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
				printf( __( 'Unable to render the slide mini-preview: %s (error code %d)', 'total-slider' ), $e->getMessage(), $e->getCode() );
			}
			return false;
		}		
		
		$template_options = $t->determine_options();
		
		?><p><strong><?php _e( 'Template:', 'total-slider' );?></strong> <?php echo esc_html( $t->name() ); ?></p><?php
		
		
		$current_slides = get_option( 'total_slider_slides_' . $this->slug );
		
		if (false === $current_slides || !is_array( $current_slides ) || count( $current_slides ) < 0)
		{
			?><p><?php _e( 'There are no slides to show.', 'total-slider' );?></p><?php
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

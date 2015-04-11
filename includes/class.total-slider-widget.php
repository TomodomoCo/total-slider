<?php
/*
 * This file contains the Total_Slider_Widget class, used for regular runtime rendering of Slide Groups.
 */

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



/**
 * Class: Responsible for allowing the user to place the slider in a 'sidebar' in their theme. Invokes the Template for rendering.
 *
 * This widget class also defines a minimalist API for the Slider Template to display the correct data for this Slide Group.
 *
 * @see WP_Widget
 */
class Total_Slider_Widget extends WP_Widget {

	/*
		These hold the data for the current slide we are working with.

		The template file accesses these indirectly, through the the_… and get_the_… functions.
	*/
	
	/**
	 * Stores all of the slides in this Group.
	 *
	 * @var array
	 */
	private $slides; // stores all of the slides in this group


	/**
	 * has_slides requires access to the Widget instance data.
	 *
	 * @var array
	 * @see WP_Widget
	 */
	private $instance; // has_slides needs access to the instance data

	/**
	 * The slide title.
	 *
	 * @var string
	 */
	protected $slide_title;

	/**
	 * The slide description.
	 *
	 * @var string
	 */
	protected $slide_description;

	/**
	 * The slide background image URL.
	 *
	 * @var string
	 */
	protected $slide_background_url;

	/**
	 * The slide's link URL.
	 *
	 * @var string
	 */
	protected $slide_link;

	/**
	 * The slide description box's X offset in pixels.
	 *
	 * @var integer
	 */
	protected $slide_x;
	
	/**
	 * The slide description box's Y offset in pixels.
	 *
	 * @var integer
	 */
	protected $slide_y;

	/**
	 * The slide identifier string.
	 *
	 * @var string
	 */
	protected $slide_identifier;

	/**
	 * The iteration count for the Slide Group rendering process.
	 *
	 * @var integer
	 */
	protected $slider_iteration = 0;

	/**
	 * A reference to this widget's Slide Group object, used to get slides.
	 *
	 * @var Total_Slide_Group|boolean
	 */
	protected $slide_group = false;

	/**
	 * Calls the WP_Widget constructor.
	 *
	 */
	public function __construct() {
		parent::__construct( false, 'Total Slider' );
	}

	/**
	 * Render the widget output. Invoke the Slide Group Template file to perform the bulk of the work.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		$this->instance = $instance;

		// clear out all the data
		$this->slide_title = null;
		$this->slide_description = null;
		$this->slide_background_url = null;
		$this->slide_link = null;
		$this->slide_x = null;
		$this->slide_y = null;
		$this->slide_identifier = null;
		$this->slides = null;
		$this->slider_iteration = 0;

		// determine the correct template to use
		$this->slide_group = new Total_Slide_Group( Total_Slider::sanitize_slide_group_slug( $this->instance['groupSlug'] ) );
		if ( ! $this->slide_group->load() ) {
			_e( '<strong>Total Slider:</strong> Could not find the selected slide group to show. Does it still exist?', 'total-slider' );
			return;
		}
		
		try {
			$tpl = new Total_Slider_Template( $this->slide_group->template, $this->slide_group->templateLocation );	
		}
		catch ( Exception $e ) {
			_e( '<strong>Total Slider:</strong> Unable to load the template for this slide group.', 'total-slider' );
			if ( is_user_logged_in() && current_user_can( 'publish_posts') ) {
				echo ' <em>' . esc_html( $e->getMessage() ) . '</em>';
			}
			return;
		}

		$general_options = get_option('total_slider_general_options');
		
		// only enqueue template if relevant option is set (fixes #29)
		if (
			is_array($general_options) &&
			array_key_exists('should_enqueue_template', $general_options) &&
			$general_options['should_enqueue_template'] == '1'
		) {
			// enqueue CSS and JS
			wp_register_style(
				'total-slider-' . esc_attr( $this->slide_group->template ),			/* handle */
				$tpl->css_uri(),								/* src */
				array(),									/* deps */
				date( "YmdHis", @filemtime($tpl->css_path() ) ),				/* ver */
				'all'										/* media */	
			);
			
			wp_enqueue_style( 'total-slider-' . esc_attr($this->slide_group->template) );
	
			
			// load .min.js if available, if SCRIPT_DEBUG is not true in wp-config.php
			$is_min = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? false : true;
			
			if ( $is_min ) {
				$js_uri = $tpl->js_min_uri();
				$js_path = $tpl->js_min_path();				
			}
			else {
				$js_uri = $tpl->js_uri();
				$js_path = $tpl->js_path();				
			}
			
			wp_register_script(	
					'total-slider-' . esc_attr($this->slide_group->template), 		/* handle */
					$js_uri,								/* src */
					array(
						'jquery'
					),									/* deps */
					date( 'YmdHis', @filemtime( $js_path) ),				/* ver */
					true									/* in_footer */		
			);
			
			wp_enqueue_script( 'total-slider-' . esc_attr($this->slide_group->template) );
		}
		
		$s = &$this; // $s is used by the theme to call our functions to actually display the data
		
		// include the template
		include ( $tpl->php_path() );
		
		unset( $s );

	}

	/**
	 * Print out the Widget's editing form, which allows the user to pick a Slide Group.
	 *
	 * @param array $instance
	 */
	public function form( $instance ) {
	?><p><?php _e( 'Choose a slide group for this widget to show:', 'total-slider' ); ?></p>

	<select id="<?php echo $this->get_field_id( 'groupSlug' ); ?>" name="<?php echo $this->get_field_name( 'groupSlug' ); ?>">
		<option value="**INVALID**" disabled="disabled" selected="selected"><?php _e( 'Select a group', 'total-slider' ); ?></option>
		<?php

			// find all the slide groups and offer them for the widget
			$args = array(
				'hide_empty'   => false,
			);
	
			$groups = get_terms( 'total_slider_slide_group', $args );

			$slide_groups = array();
			$n = 0;

			if ( is_array( $groups ) && count( $groups ) > 0 ) {
				foreach( $groups as $g ) {
					$slide_groups[$n] = new Total_Slide_Group( $g->slug );
					$slide_groups[$n]->load();
					++$n;
				}
			}

			$slide_templates = array();

			if ( count( $slide_groups ) > 0 ) {
				foreach( $slide_groups as $group ) {
					?><option value="<?php echo esc_attr($group->slug);?>"
						<?php if ( array_key_exists('groupSlug', $instance ) ):
							echo ( $group->slug == $instance['groupSlug'] ) ? ' selected="selected"' : '';
						endif; ?>
					><?php echo esc_html( $group->name );?></option><?php
					
					// get the template for this slide group					
					$slide_templates[ esc_attr( $group->slug ) ] = $group->template;
					
				}

			}

		?>
	</select>
	<?php

	}

	/**
	 * Update the Widget settings with the new selected Slide Group.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 */
	public function update( $new_instance, $old_instance ) {
		if ( '**INVALID**' != $new_instance['groupSlug'] ) {

			return array( 
				'groupSlug' => Total_Slider::sanitize_slide_group_slug( $new_instance['groupSlug'] ) 
			);
		}
		else {
			return $old_instance;
		}

	}

	/**
	 * Return the number of slides in this Slide Group.
	 *
	 * Can also be used to test if there are any slides to show at all.
	 *
	 * @return integer
	 *
	 */
	public function slides_count()
	{
		if ( ! is_array($this->slides ) )
		{
			$this->slides = $this->slide_group->get_slides();
		}

		return count( $this->slides );

	}
	
	/**
	 * Allows the template to be aware of whether it is running at runtime (viewing), or at editing time. Returns true here.
	 *
	 * @return boolean
	 *
	 */
	public function is_runtime()
	{

		return true;
		
	}


	/**
	 * An iterator for the purposes of Slider Template files. Loads the next slide in, preparing other methods to spit out this Slide's information.
	 *
	 * @return boolean
	 *
	 */
	public function has_slides()
	{

		if ( ! $this->instance )
		{
			throw new Exception( "The widget's instance data, containing information about which slide group to show, could not be loaded." );
			return false;
		}

		if ( ! is_array( $this->slides ) || count( $this->slides ) < 1 ) {
			$this->slides = $this->slide_group->get_slides();
		}

		// on which slide should we work? does it exist?
		if ( count( $this->slides ) < $this->slider_iteration + 1 )
		{
			return false; // we are at the end of the slides
		}

		// otherwise, load in the data
		if (!empty( $this->slides[$this->slider_iteration]['title'] ) ) {
			$this->slide_title = $this->slides[$this->slider_iteration]['title'];
		}
		if (!empty( $this->slides[$this->slider_iteration]['description'] ) ) {
			$this->slide_description = $this->slides[$this->slider_iteration]['description'];
		}

		if (!empty( $this->slides[$this->slider_iteration]['id'] ) ) {
			$this->slide_identifier = $this->slides[$this->slider_iteration]['id'];
		}

		// the background may be blank!
		if (!empty ( $this->slides[$this->slider_iteration]['background'] ) ) {
			$this->slide_background_url = $this->slides[$this->slider_iteration]['background'];
		}
		else {
			$this->slide_background_url = '';
		}

		// the link may be blank!
		if (!empty( $this->slides[$this->slider_iteration]['link'] ) ) {
			$this->slide_link = $this->slides[$this->slider_iteration]['link'];
		}
		else {
			$this->slide_link = '';
		}

		// get X and Y coords
		if (!empty( $this->slides[$this->slider_iteration]['title_pos_x'] ) || 0 === $this->slides[$this->slider_iteration]['title_pos_x']) {
			$this->slide_x = $this->slides[$this->slider_iteration]['title_pos_x'];
		}
		if (!empty( $this->slides[$this->slider_iteration]['title_pos_y'] ) || 0 === $this->slides[$this->slider_iteration]['title_pos_y'] ) {
			$this->slide_y = $this->slides[$this->slider_iteration]['title_pos_y'];
		}

		// the data is ready, bump the iterator and return true
		$this->slider_iteration++;
		return true;

	}

	/**
	 * Print the sanitized slide title.
	 *
	 * @return void 
	 *
	 */
	public function the_title() {

		echo $this->get_the_title();

	}

	/**
	 * Return the sanitized slide title.
	 *
	 * @return string
	 *
	 */
	public function get_the_title() {

		return esc_html( apply_filters( 'total-slider_slide_title', $this->slide_title ) );

	}

	/**
	 * Print the sanitized slide description.
	 *
	 * @return void
	 *
	 */
	public function the_description() {

		echo $this->get_the_description();

	}

	/**
	 * Return the sanitized slide description.
	 *
	 * @return string
	 *
	 */
	public function get_the_description() {

		return esc_html( apply_filters ( 'total-slider_slide_description', $this->slide_description ) );

	}

	/**
	 * Print the sanitized background image URL.
	 *
	 * @return void
	 *
	 */
	public function the_background_url() {

		echo $this->get_the_background_url();

	}

	/**
	 * Return the sanitized background image URL.
	 *
	 * @return string
	 *
	 */
	public function get_the_background_url() {

		if ( is_numeric( $this->slide_background_url ) )
		{
			$bg_attach = (int) $this->slide_background_url;
			$bg_attach = wp_get_attachment_url( $bg_attach );

			return esc_url ( apply_filters('total-slider_slide_background_url', $bg_attach ) );

		}
		else {
			return esc_url( apply_filters ('total-slider_slide_background_url', $this->slide_background_url) );
		}

	}

	/**
	 * Print the sanitized slide link URL.
	 *
	 * @return void
	 *
	 */
	public function the_link() {

		echo $this->get_the_link();

	}

	/**
	 * Return the sanitized slide link URL.
	 *
	 * @return string
	 *
	 */
	public function get_the_link() {

		if ( is_numeric( $this->slide_link ) ) {
			$link_post = (int) $this->slide_link;
			return esc_url( apply_filters( 'total-slider_slide_link', get_permalink( $link_post ) ) );
		}
		else {
			return esc_url ( apply_filters('total-slider_slide_link', $this->slide_link) );
		}

	}

	/**
	 * Print the sanitized X coordinate.
	 *
	 * @return void
	 */
	public function the_x() {

		echo $this->get_the_x();

	}

	/**
	 * Return the sanitized X coordinate.
	 *
	 * @return integer
	 *
	 */
	public function get_the_x() {

		return intval ( apply_filters( 'total-slider_slide_x', $this->slide_x ), 10 /* decimal */ );

	}

	/**
	 * Print the sanitized Y coordinate.
	 *
	 * @return void
	 *
	 */
	public function the_y() {

		echo $this->get_the_y();

	}

	/**
	 * Return the sanitized Y coordinate.
	 *
	 * @return integer
	 *
	 */
	public function get_the_y() {

		return intval ( apply_filters( 'total-slider_slide_y', $this->slide_y ), 10 /* decimal */ );

	}

	/**
	 * Print the sanitized slide identifier.
	 *
	 * @return void
	 *
	 */
	public function the_identifier() {

		echo $this->get_the_identifier();

	}

	/**
	 * Return the sanitized slide identifier.
	 *
	 * @return string 
	 *
	 */
	public function get_the_identifier() {

		return esc_attr( apply_filters( 'total-slider_slide_identifier', $this->slide_identifier ) );

	}

	/**
	 * Return the number of slides we have been through so far.
	 *
	 * @return integer
	 *
	 */
	public function iteration() {
		return intval ( $this->slider_iteration - 1 );
		// has_slides() always bumps the iteration ready for the next run, but we
		// are still running, for the theme's purposes, on the previous iteration.
		// Hence, returning the iteration - 1.

	}
	
		
	/**
	 * At editing time, this makes the title/description box draggable. It performs no action here.
	 *
	 * @return void
	 *
	 */
	public function make_draggable() {

		// here in runtime, we do nothing
		
		return;
		
	}
	
	/**
	 * At editing time, this defines the parent element for the draggable box. It performs no action here.
	 *
	 * @return void
	 *
	 */
	public function draggable_parent() {
		return;
		
	}	


};

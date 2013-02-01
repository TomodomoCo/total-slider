<?php

/*  Copyright (C) 2011-2013 Peter Upfold.

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



class Total_Slider_Widget extends WP_Widget {
/*
	The Total Slider Widget is responsible for allowing the user to place the slider in any
	‘sidebar’ defined in their theme and for invoking the Slider template file for displaying
	the slides.

	This widget class also defines a minimalist API for the Slider template files to use to display
	the slides.
*/

	/*
		These hold the data for the current slide we are working with.

		The template file accesses these indirectly, through the the_… and get_the_… functions.
	*/
	private $slides; // stores all of the slides in this group
	private $instance; // has_slides needs access to the instance data
	protected $slide_title;
	protected $slide_description;
	protected $slide_background_url;
	protected $slide_link;
	protected $slide_x;
	protected $slide_y;
	protected $slide_identifier;
	protected $slider_iteration = 0;


	public function __construct() {
	/*
		Constructor, merely calls the WP_Widget constructor.
	*/
		parent::__construct( false, 'Total Slider' );
	}

	public function widget( $args, $instance ) {
	/*
		The widget function is responsible for rendering the widget's output. In the case
		of Total Slider Widget, this will invoke the Slider template file to output the slides
		to the desired widget area.
	*/


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
		$group = new Total_Slide_Group( Total_Slider::sanitize_slide_group_slug( $this->instance['groupSlug'] ) );
		if ( ! $group->load() ) {
			_e( '<strong>Total Slider:</strong> Could not find the selected slide group to show. Does it still exist?', 'total_slider' );
			return;
		}
		
		try {
			$tpl = new Total_Slider_Template( $group->template, $group->templateLocation );	
		}
		catch ( Exception $e ) {
			_e( '<strong>Total Slider:</strong> Unable to load the template for this slide group.', 'total_slider' );
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
				'total-slider-' . esc_attr( $group->template ),					/* handle */
				$tpl->css_uri(),												/* src */
				array(),														/* deps */
				date( "YmdHis", @filemtime($tpl->css_path() ) ),				/* ver */
				'all'															/* media */	
			);
			
			wp_enqueue_style( 'total-slider-' . esc_attr($group->template) );
	
			
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
					'total-slider-' . esc_attr($group->template), 				/* handle */
					$js_uri,													/* src */
					array(
						'jquery'
					),															/* deps */
					date( 'YmdHis', @filemtime( $js_path) ),					/* ver */
					true														/* in_footer */		
			);
			
			wp_enqueue_script( 'total-slider-' . esc_attr($group->template) );
		}
		
		$s = &$this; // $s is used by the theme to call our functions to actually display the data
		
		// include the template
		include ( $tpl->php_path() );
		
		unset( $s );

	}

	public function form( $instance ) {
	/*
		The form function defines the settings form for the widget.

		In our case, we will allow the user to pick which Slide Group this widget is responsible
		for displaying.
	*/

	?><p><?php _e( 'Choose a slide group for this widget to show:', 'total_slider' ); ?></p>

	<select id="<?php echo $this->get_field_id( 'groupSlug' ); ?>" name="<?php echo $this->get_field_name( 'groupSlug' ); ?>">
		<option value="**INVALID**" disabled="disabled" selected="selected"><?php _e( 'Select a group', 'total_slider' ); ?></option>
		<?php

			// find all the slide groups and offer them for the widget

			$slide_groups = get_option( 'total_slider_slide_groups' );
			$slide_templates = array();

			if ( is_array( $slide_groups ) && count( $slide_groups ) > 0 ) {
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

	public function update( $new_instance, $old_instance ) {
	/*
		Update the widget's settings with the new selected slide group from the form()
	*/

		if ( '**INVALID**' != $new_instance['groupSlug'] ) {

			return array( 
				'groupSlug' => Total_Slider::sanitize_slide_group_slug( $new_instance['groupSlug'] ) 
			);
		}
		else {
			return $old_instance;
		}

	}

	public function slides_count()
	{
	/*
		Return the number of slides in this slide group.

		Can also be used by templates to test if there are any slides to show at all,
		and, for example, not output the starting <ul>.
	*/

		if ( ! is_array($this->slides ) )
		{
			$this->slides = get_option( 'total_slider_slides_' . Total_Slider::sanitize_slide_group_slug( $this->instance['groupSlug'] ) );
			$this->slides = array_values( $this->slides );
		}

		return count( $this->slides );

	}
	
	public function is_runtime()
	{
	/*
		Allows the template to be aware of whether it is running at runtime (viewing as part of the
		actual site): 'true', or at edit-time (the user is editing slides in the admin interface, and
		the template is executing as a preview): 'false'.
	*/
	
		return true;
		
	}


	public function has_slides()
	{
	/*
		Behaves as an iterator for the purposes of slider template files. It loads
		in the next slide, readying the other functions below for returning
		the data from this particular slide to the theme.


	*/

		if ( ! $this->instance )
		{
			throw new Exception( "The widget's instance data, containing information about which slide group to show, could not be loaded." );
			return false;
		}

		if ( ! is_array( $this->slides ) || count( $this->slides ) < 1 ) {
			$this->slides = get_option( 'total_slider_slides_' . Total_Slider::sanitize_slide_group_slug( $this->instance['groupSlug'] ) );
			$this->slides = array_values( $this->slides );
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

	public function the_title() {
	/*
		Print the slide title to output, having sanitised it.
	*/

		echo $this->get_the_title();

	}

	public function get_the_title() {
	/*
		Return the slide title, having sanitised it.
	*/

		return esc_html( apply_filters( 'total-slider_slide_title', $this->slide_title ) );

	}

	public function the_description() {
	/*
		Print the slide description to output, having sanitised it.
	*/

		echo $this->get_the_description();

	}

	public function get_the_description() {
	/*
		Return the slide description, having sanitised it.
	*/

		return esc_html( apply_filters ( 'total-slider_slide_description', $this->slide_description ) );

	}

	public function the_background_url() {
	/*
		Print the background URL to output, having sanitised it.
	*/

		echo $this->get_the_background_url();

	}

	public function get_the_background_url() {
	/*
		Return the background URL, having sanitisied it.
	*/

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

	public function the_link() {
	/*
		Print the slide link URL to output, having sanitised it.
	*/

		echo $this->get_the_link();

	}

	public function get_the_link() {
	/*
		Return the slide link URL, having sanitised it.
	*/

		if ( is_numeric( $this->slide_link ) ) {
			$link_post = (int) $this->slide_link;
			return esc_url( apply_filters( 'total-slider_slide_link', get_permalink( $link_post ) ) );
		}
		else {
			return esc_url ( apply_filters('total-slider_slide_link', $this->slide_link) );
		}

	}

	public function the_x() {
	/*
		Print the X coordinate to the output, having sanitised it.
	*/

		echo $this->get_the_x();

	}

	public function get_the_x() {
	/*
		Return the X coordinate, having sanitised it.
	*/

		return intval ( apply_filters( 'total-slider_slide_x', $this->slide_x ), 10 /* decimal */ );

	}

	public function the_y() {
	/*
		Print the Y coordinate to the output, having sanitised it.
	*/

		echo $this->get_the_y();

	}

	public function get_the_y() {
	/*
		Return the Y coordinate, having sanitised it.
	*/

		return intval ( apply_filters( 'total-slider_slide_y', $this->slide_y ), 10 /* decimal */ );

	}

	public function the_identifier() {
	/*
		Print the slide identifier to output, having sanitised it.
	*/

		echo $this->get_the_identifier();

	}

	public function get_the_identifier() {
	/*
		Return the slide identifier to output, having sanitised it.
	*/

		return esc_attr( apply_filters( 'total-slider_slide_identifier', $this->slide_identifier ) );

	}

	public function iteration() {
	/*
		Return the iteration number. How many slides have we been through?
	*/

		return intval ( $this->slider_iteration - 1 );
		// has_slides() always bumps the iteration ready for the next run, but we
		// are still running, for the theme's purposes, on the previous iteration.
		// Hence, returning the iteration - 1.

	}
	
		
	public function make_draggable() {
	/*
		Outputs a class that in edit-time mode makes the object draggable (for X/Y positioning
		of the title/description overlay).
	
		Should be called when inside a DOM object's 'class' attribute.
	*/
	
		// here in runtime, we do nothing
		
		return;
		
	}
	
	public function draggable_parent() {
	/*
		Outputs a class that in edit-time mode makes the object the draggable's parent. This
		will be used to calculate the X/Y offset for the title/description box.
		
		This element will also be used as the containment for the draggable title/description box,
		i.e. the box will not be able to be dragged outside of the object marked with this class.
		
		Should be called when inside a DOM object's 'class' attribute.
		
		Does nothing at runtime.
	*/
		
		return;
		
	}	


};

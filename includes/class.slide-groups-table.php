<?php
/*
 * Slide Groups WP_List_Table subclass
 * 
 * To facilitate the display of Slide Groups in a WP_List_Table on the main
 * Slide Groups screen.
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

/**
 * Class: Extending WP_List_Table, this class provides the functions to display a WordPress admin-style table on the main Slide Groups page.
 *
 * @see WP_List_Table
 */
class Slide_Groups_Table extends WP_List_Table {

	/**
	 * Construct the LocationsTable, passing in singular and plural	labels and calling the parent constructor.
	 *
	 * @param array $args Arguments to create the WP_List_Table
	 * @return array
	 */
	public function __construct( $args = array() ) {
	/*
		*/
		return parent::__construct(
		
			array (
				'singular' 	=> __( 'slide group', 'total-slider' ),
				'plural'	=> __( 'slide groups', 'total-slider' ),
				'ajax'		=> false			
			)
		);
	}
	
	/**
	 * Define this table's columns and their tables.
	 *
	 */
	public function get_columns() {
	
		return array(
		
	        'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text	
			'name'		=>	__('Group Name', 'total-slider'),
			'template'  =>	__('Template', 'total-slider'),
			'slides_count'		=>	__('Slides', 'total-slider')
		
		);
	
	}
		
	/**
	 * Return the list of columns, and associated entries within the result record, by which the user can sort the table.
	 *
	 * @return array
	 *
	 */
	public function get_sortable_columns() {
	
		return array();
	
	}
	
	/**
	 * Display handler for the group name (the first column).
	 *
	 * We need to display the name, as well as Edit/Delete inline links for this Slide Group.
	 *
	 * @param Total_Slide_Group $item A slide group object.
	 * @return void
	 */
	public function column_name($item)
	{
	/*
		Display handler for the group name, first column.
		
		We need to display the name, as well as Edit/Delete inline links for this SG.
	*/
	
		echo esc_html( stripslashes( $item->name ) );
		?><br/><div class="row-actions">
			<span class="edit"><a href="admin.php?page=total-slider&amp;group=<?php echo esc_attr( $item->slug ); ?>"><?php _e( 'Edit', 'total-slider' ); ?></a></span> |
			<span class="trash"><a class="submitdelete" href="admin.php?page=total-slider&amp;action=remove&amp;group=<?php echo esc_attr( $item->slug ); ?>&amp;_wpnonce=<?php echo wp_create_nonce( 'remove-slide-group' );?>"
				onclick="return confirm('<?php _e( 'Are you sure you want to delete this slide group?\n\nThis action cannot be undone.', 'total-slider' ); ?>');"
			><?php _e( 'Remove', 'total-slider' ); ?></a></span>
		</div><?php
	}
	
	/**
	 * Gather a count of this slide group for the 'count' column.
	 *
	 * @param Total_Slide_Group $item A slide group object.
	 * @return integer
	 */
	public function column_slides_count( $item ) {

		$group_object = new Total_Slide_Group( $item->slug );
		$group_object->load();

		return count( $group_object->get_slides() );		
	
	}
	
	/**
	 * Return the template name used for the slide group.
	 *
	 * @param Total_Slide_Group $item A slide group object.
	 * @return string
	 */
	public function column_template( $item ) {
	
		if (
			property_exists( $item, 'template' ) &&
			! empty($item->template) &&
			property_exists( $item, 'templateLocation' ) &&
			!empty($item->templateLocation)
		) {
		
			// load template's friendly name
			try {
				$t = new Total_Slider_Template( $item->template, $item->templateLocation );
			}
			catch ( Exception $e )
			{
				return esc_html( $item->template );
			}
			
			if ( $t->name() )
			{
				return esc_html( $t->name() );	
			}
			else {
				return esc_html( $item->template );
			}
			
		}
		else {
			return 'Default';			
		}
	}
	
	/**
	 * The default display handler for any column that does not have its own column_name() handler.
	 *
	 * Return the item for display.
	 *
	 * @param object $item
	 * @param string $col_name
	 *
	 * @return string
	 */
	public function column_default( $item, $col_name ) {

		return esc_html( stripslashes( $item->$col_name ) );
	}
	
	/**
	 * The display handler for the checkbox column.
	 *
	 * @param Total_Slide_Group $item The Slide Group object.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input class="slide-group-checkbox" type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item->slug
		);
	}
	
	/**
	 * Define the bulk actions that can be performed against the table data.
	 *
	 * @return array
	 *
	 */
	public function get_bulk_actions() {
		
		$actions = array(
			'remove'			=> __('Remove', 'total-slider')
		);
		
		
		return $actions;
	}
    
	
	/**
	 * Return a count for the total number of Slide Groups for pagination purposes.
	 *
	 * @return integer
	 *
	 */
	public function get_total_items()
	{
	
		return count( get_terms( 'total_slider_slide_group' ) );
	
	}
	
	/**
	 * Get the Slide Groups, so we can display them in the table.
	 *
	 * @return array
	 *
	 */
	public function get_groups()
	{

		$args = array(
			'hide_empty'   => false,
		);

		$groups = get_terms( 'total_slider_slide_group', $args );

		// load template information
		if ( is_array( $groups ) && count( $groups ) > 0 ) {
			foreach( $groups as $group ) {
				$group_object = new Total_Slide_Group( $group->slug );
				$group_object->load();
				$group->template = $group_object->template;
				$group->templateLocation = $group_object->templateLocation;
			}
		}

		return $groups;
	
	}
		
	/**
	 * Prepare data for the table -- getting data and running accessory functions.
	 *
	 * @return void
	 *
	 */
	public function prepare_items()
	{
	
		$per_page = TOTAL_SLIDER_MAX_SLIDE_GROUPS;
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable_columns = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable_columns);

		// pagination
		$current_page = $this->get_pagenum();
		
		$total_items = $this->get_total_items();
		$data = $this->get_groups();
		
		// get the data

		$this->items = $data;
		
		$this->set_pagination_args(array(
		
			'total_items'	=>	$total_items,
			'per_page'	=>	$per_page,
			'total_pages'	=>	ceil( $total_items / $per_page )
		
		));
			
	}
	
	/**
	 * Return the text to display if there are no Slide Groups.
	 *
	 * @return string
	 *
	 */
	public function no_items()
	{
	
		echo __( 'Click &lsquo;Add New&rsquo; to create a new group of slides.', 'total-slider' );
	
	}
	

}

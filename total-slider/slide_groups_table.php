<?php
/********************************************************************************

	Slide Groups WP_List_Table subclass
	
	To facilitate the display of Slide Groups in a WP_List_Table on the main
	Slide Groups screen.

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

class Slide_Groups_Table extends WP_List_Table {

	public function __construct($args = array())
	{
	/*
		Construct the LocationsTable, passing in singular and plural
		labels and calling the parent constructor.
	*/
		return parent::__construct(
		
			array (
				'singular' 	=> __('slide group', 'total_slider'),
				'plural'	=> __('slide groups', 'total_slider'),
				'ajax'		=> false			
			)
		);
	}
	
	public function get_columns() {
	/*
		Define this table's columns and their titles.
	*/
	
		return array(
		
	        'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text	
			'name'		=>	__('Group Name', 'total_slider'),
			'slides_count'		=>	__('Slides', 'total_slider')
		
		);
	
	}
		
	public function get_sortable_columns() {
	/*
		Return the list of columns, and associated entries
		within the result record, that by which the user
		can sort the table.
	*/
	
		return array();
	
	}
	
	public function column_name($item)
	{
	/*
		Display handler for the group name, first column.
		
		We need to display the name, as well as Edit/Delete inline links for this SG.
	*/
	
		echo esc_html(stripslashes($item->name));
		?><br/><div class="row-actions">
			<span class="edit"><a href="admin.php?page=total-slider&amp;group=<?php echo esc_attr($item->slug);?>"><?php _e('Edit', 'total_slider');?></a></span> |
			<span class="remove"><a class="submitdelete" href="admin.php?page=total-slider&amp;action=remove&amp;group=<?php echo esc_attr($item->slug);?>&amp;_wpnonce=<?php echo wp_create_nonce('remove-slide-group');?>"
				onclick="return confirm('<?php _e('Are you sure you want to delete this slide group?\n\nThis action cannot be undone.', 'total_slider');?>');"
			><?php _e('Remove', 'total_slider');?></a></span>
		</div><?php
	}
	
	public function column_slides_count($item)
	{
	/* 
		Gather a count of this slide group for the column.
	*/
		
		return count(get_option('total_slider_slides_' . esc_attr($item->slug) ));		
	
	}
	
	public function column_default($item, $colName)
	{
	/*
		The default display handler for any column that does
		not have its own column_name() handler in the class.
		
		We should return the item for display.
	*/
	
		return esc_html(stripslashes($item->$colName));
	}
	
	public function column_cb ($item) 
	{
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],
			/*$2%s*/ $item->name
		);
	}
	
	/*public function get_bulk_actions() {
	/*
		Define the bulk actions that can be performed
		against the table data.
	*//*
		
		$actions = array(
			'remove'			=> 'Remove'
		);
		
		
		return $actions;
	}*/
	// not implemented yet
    
	
	public function get_total_items()
	{
	/*
		Quickly get a count for the total number of items
		so that we can do pagination properly.
	*/
	
		return count(get_option('total_slider_slide_groups'));
	
	}
	
	public function get_groups()
	{
	/*
		Get the slide groups from the options table, so
		we can display them in the table.
	*/
	
		$groups = get_option('total_slider_slide_groups');
		return $groups;
	
	}
		
	public function prepare_items()
	{
	/*
		Prepare data for display -- getting the data and returning
		the data for the table.
	*/
	
		$perPage = TOTAL_SLIDER_MAX_SLIDE_GROUPS;
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortableColumns = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortableColumns);

		// pagination
		$currentPage = $this->get_pagenum();
		
		$totalItems = $this->get_total_items();
		$data = $this->get_groups();
		
		// get the data

		$this->items = $data;
		
		$this->set_pagination_args(array(
		
			'total_items'	=>	$totalItems,
			'per_page'		=>	$perPage,
			'total_pages'	=>	ceil($totalItems/$perPage)
		
		));
			
	}
	

}

?>
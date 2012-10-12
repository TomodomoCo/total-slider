<?php
/*
Total Slider Ajax Interface
	
This file is invoked by Total Slider when the user is manipulating
slides in the edit interface. It is responsible for receiving commands
from the edit interface, executing them (invoking the Total_Slide_Group class)
and returning JSON to the interface on success, or failure.

/* ----------------------------------------------*/

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

ini_set('magic_quotes_gpc', 'Off');

if (strtolower($_SERVER['REQUEST_METHOD']) != 'post') {
	header('HTTP/1.1 403 Forbidden');
	die('<h1>Forbidden</h1>');
}

if (!defined('TOTAL_SLIDER_REQUIRED_CAPABILITY'))
{
	header('HTTP/1.1 403 Forbidden');
	die('<h1>Forbidden</h1>');
}

if (!function_exists('__'))
{
	header('HTTP/1.1 403 Forbidden');
	die('<h1>Forbidden</h1>');
}

require_once(dirname(__FILE__).'/slide_group.php');

// get list of slide groups if asked
if (!array_key_exists('group', $_GET) && $_GET['action'] == 'getSlideGroups')
{

	if (!current_user_can(TOTAL_SLIDER_REQUIRED_CAPABILITY))
	{
		header('HTTP/1.0 403 Forbidden');
		header('Content-Type: application/json');
		echo json_encode(array('error' => __('Your user does not have the required permission level. Are you sure you are still logged in to the WordPress dashboard?', 'total_slider')));
		die();
	}
	
	$groups = get_option('total_slider_slide_groups');
	
	$results = array();
	$i = 0;
	
	if (is_array($groups) && count($groups) > 0)
	{
		foreach($groups as $group)
		{
			$results[$i]['slug'] = $group->slug;
			$results[$i]['name'] = $group->name;
			$i++;
		}		
	}
	
	header('Content-Type: application/json');
	echo json_encode($results);
	die();

}

// get the group that we are supposed to be acting on
if (array_key_exists('group', $_GET)) {
	$slug = $_GET['group'];
}
else {
	$slug = '';
}

if (empty($slug))
{
	header('HTTP/1.0 400 Bad Request');
	header('Content-Type: application/json');
	echo json_encode(array('error' => __('You did not supply the slide group to which this action should be applied.', 'total_slider')));
	die();
}

try {
	$g = new Total_Slide_Group($slug);
	if (!$g->load()) {
		header('HTTP/1.0 400 Bad Request');
		header('Content-Type: application/json');
		echo json_encode(array('error' => __('Could not load the selected Slide Group. Does it exist?', 'total_slider')));
		die();
	}
}
catch (Exception $e)
{
	header('HTTP/1.0 400 Bad Request');
	header('Content-Type: application/json');
	echo json_encode(array('error' => $e->getMessage()));
	die();
}


switch ($_GET['action'])
{

	case 'createNewSlide':
	
		if (!current_user_can(TOTAL_SLIDER_REQUIRED_CAPABILITY))
		{
			header('HTTP/1.0 403 Forbidden');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('Your user does not have the required permission level. Are you sure you are still logged in to the WordPress dashboard?', 'total_slider')));
			die();
		}
		
		// error out if the POST format isn't right
		if ( !array_key_exists('title_pos_x', $_POST) ||  !array_key_exists('title_pos_y', $_POST)
			|| !array_key_exists('background', $_POST) || !array_key_exists('title', $_POST)
			|| !array_key_exists('description', $_POST) || !array_key_exists('link', $_POST)
		)
		{
			header('HTTP/1.0 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('You have not supplied all of the required data.', 'total_slider')));
			die();				
		}
		
		// validate data that we have received
		
		$_POST['title_pos_x'] = (int) $_POST['title_pos_x'];
		$_POST['title_pos_y'] = (int) $_POST['title_pos_y'];
		
		// do we have all the data we asked for? is it all valid?
		
		/* 
			note that we check empty AND is not numeric	for title_pos_x and title_pos_y,
			since empty() returns true for decimal 0, which *is* a valid value for that. 
			This logic will only fail on title_pos_x or title_pos_y if it is considered
			'empty' but isn't a number (i.e. decimal 0 will be accepted, blank string will not).
			
			A blank string should never get here, though, because of the casting
			to int above. A blank string will be cast to decimal 0, which is OK.
		*/
		if (empty($_POST['title']) || empty($_POST['description']) || 
			(
				empty($_POST['title_pos_x']) && !is_numeric($_POST['title_pos_x'])
			)
			|| 
			(
				empty($_POST['title_pos_y']) && !is_numeric($_POST['title_pos_y'])
			)
		)
		{
			header('HTTP/1.0 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('You have not supplied all of the required data.', 'total_slider')));
			die();			
		}
		
		if (!empty($_POST['background']))
		{
			if (is_numeric($_POST['background']))
			{
				if ((int)$_POST['background'] != $_POST['background'])
				{
					header('HTTP/1.0 400 Bad Request');
					header('Content-Type: application/json');
					echo json_encode(array('error' => __('Invalid attachment ID for the specified background.', 'total_slider')));
					die();	
				}
			}
			else if (!$g->validateURL($_POST['background']))
			{
				header('HTTP/1.0 400 Bad Request');
				header('Content-Type: application/json');
				echo json_encode(array('error' => __('Invalid URL format for the specified background URL.', 'total_slider')));
				die();			
			}	
		}
		
		if (!empty($_POST['link']))
		{
			if (is_numeric($_POST['link']))
			{
				$_POST['link'] = (int)$_POST['link'];
				
				if ($_POST['link'] < 1)
				{
					header('HTTP/1.0 400 Bad Request');
					header('Content-Type: application/json');
					echo json_encode(array('error' => __('The post ID for the specified slide link is not valid.', 'total_slider')));
					die();					
				}
			}
			else {
				if (!$g->validateURL($_POST['link']))
				{
					header('HTTP/1.0 400 Bad Request');
					header('Content-Type: application/json');
					echo json_encode(array('error' => __('Invalid URL format for the specified link URL.', 'total_slider')));
					die();	
				}
			}
		}
		
		$_POST['title'] = stripslashes($_POST['title']);
		$_POST['description'] = stripslashes($_POST['description']);
		
		// do the work
		$result = $g->newSlide($_POST['title'], $_POST['description'], $_POST['background'], $_POST['link'], $_POST['title_pos_x'], $_POST['title_pos_y']);
		
		if ($result) {
			header('Content-Type: application/json');
			echo json_encode(array('new_id' => $result));
			die();
		}
		else {
			header('HTTP/1.0 500 Internal Server Error');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('The create slide operation failed at the server.', 'total_slider')));
			die();
		}
	
	break;
	
	case 'getSlide':
	
		if (!current_user_can(TOTAL_SLIDER_REQUIRED_CAPABILITY))
		{
			header('HTTP/1.0 403 Forbidden');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('Your user does not have the required permission level. Are you sure you are still logged in to the WordPress dashboard?', 'total_slider')));
			die();
		}
		
		// error out if the POST format isn't right
		if ( !array_key_exists('id', $_POST)	)
		{
			header('HTTP/1.0 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' =>  __('You have not supplied the ID to look up.', 'total_slider')));
			die();				
		}
		
		// we need the slide ID
		$_POST['id'] = preg_replace('[^0-9a-zA-Z_]', '', $_POST['id']);
		
		if (empty($_POST['id']))
		{
			header('HTTP/1.0 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('You have not supplied the ID to look up.', 'total_slider')));
			die();				
		}
		
		$result = $g->getSlide($_POST['id']);
		
		if (is_array($result) && count($result) > 0) {
		
			$result['title'] = stripslashes($result['title']);
			$result['description'] = stripslashes($result['description']);
		
			header('Content-Type: application/json');
			echo json_encode($result);
			die();	
		}
		else {
			header('HTTP/1.1 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('Specified slide ID could not be found. It may have been already deleted.', 'total_slider')));
			die();	
		}
		
	
	break;
	
	case 'updateSlide':
	
		if (!current_user_can(TOTAL_SLIDER_REQUIRED_CAPABILITY))
		{
			header('HTTP/1.0 403 Forbidden');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('Your user does not have the required permission level. Are you sure you are still logged in to the WordPress dashboard?', 'total_slider')));
			die();
		}
		
		// error out if the POST format isn't right
		if ( !array_key_exists('title_pos_x', $_POST) ||  !array_key_exists('title_pos_y', $_POST)
			|| !array_key_exists('background', $_POST) || !array_key_exists('title', $_POST)
			|| !array_key_exists('description', $_POST) || !array_key_exists('link', $_POST)
			|| !array_key_exists('id', $_POST)
		)
		{
			header('HTTP/1.0 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('You have not supplied all of the required data.', 'total_slider')));
			die();				
		}
		
		// we need the slide ID
		$_POST['id'] = preg_replace('[^0-9a-zA-Z_]', '', $_POST['id']);
		
		if (empty($_POST['id']))
		{
			header('HTTP/1.0 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('You have not supplied the ID to look up.', 'total_slider')));
			die();				
		}
		
		// do we have all the data we asked for? is it all valid?
		
				
		$_POST['title_pos_x'] = (int) $_POST['title_pos_x'];
		$_POST['title_pos_y'] = (int) $_POST['title_pos_y'];
		
		/* 
			note that we check empty AND is not numeric	for title_pos_x and title_pos_y,
			since empty() returns true for decimal 0, which *is* a valid value for that. 
			This logic will only fail on title_pos_x or title_pos_y if it is considered
			'empty' but isn't a number (i.e. decimal 0 will be accepted, blank string will not).
			
			A blank string should never get here, though, because of the casting
			to int above. A blank string will be cast to decimal 0, which is OK.
		*/
		if (empty($_POST['title']) || empty($_POST['description']) || 
			(
				empty($_POST['title_pos_x']) && !is_numeric($_POST['title_pos_x'])
			)
			|| 
			(
				empty($_POST['title_pos_y']) && !is_numeric($_POST['title_pos_y'])
			)
		)
		{
			header('HTTP/1.0 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('You have not supplied all of the required data.', 'total_slider')));
			die();			
		}
		
		if (!empty($_POST['background']))
		{
			if (is_numeric($_POST['background']))
			{
				if ((int)$_POST['background'] != $_POST['background'])
				{
					header('HTTP/1.0 400 Bad Request');
					header('Content-Type: application/json');
					echo json_encode(array('error' => __('Invalid attachment ID for the specified background.', 'total_slider')));
					die();	
				}
			}
			else if (!$g->validateURL($_POST['background']))
			{
				header('HTTP/1.0 400 Bad Request');
				header('Content-Type: application/json');
				echo json_encode(array('error' => __('Invalid URL format for the specified background URL.', 'total_slider')));
				die();			
			}	
		}
		
		if (!empty($_POST['link']))
		{
			if (is_numeric($_POST['link']))
			{
				$_POST['link'] = (int)$_POST['link'];
				
				if ($_POST['link'] < 1)
				{
					header('HTTP/1.0 400 Bad Request');
					header('Content-Type: application/json');
					echo json_encode(array('error' => __('The post ID for the specified slide link is not valid.', 'total_slider')));
					die();					
				}
			}
			else {
				if (!$g->validateURL($_POST['link']))
				{
					header('HTTP/1.0 400 Bad Request');
					header('Content-Type: application/json');
					echo json_encode(array('error' => __('Invalid URL format for the specified link URL.', 'total_slider')));
					die();	
				}
			}
		}
		
		$_POST['title'] = stripslashes($_POST['title']);
		$_POST['description'] = stripslashes($_POST['description']);

		$result = $g->updateSlide($_POST['id'], $_POST['title'], $_POST['description'], $_POST['background'], $_POST['link'], $_POST['title_pos_x'], $_POST['title_pos_y']);
		
		if ($result)
		{
			header('Content-Type: application/json');
			echo json_encode(array('success' => 'true'));
			die();
		}
		else {
			header('HTTP/1.0 500 Internal Server Error');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('The update slide operation failed at the server.', 'total_slider')));
			die();
		}
		
	
	break;
	
	case 'newSlideOrder':
	
	
		if (!current_user_can(TOTAL_SLIDER_REQUIRED_CAPABILITY))
		{
			header('HTTP/1.0 403 Forbidden');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('Your user does not have the required permission level. Are you sure you are still logged in to the WordPress dashboard?', 'total_slider')));
			die();
		}
		
		// error out if the POST format isn't right
		if ( !array_key_exists('slidesort', $_POST) )
		{
			header('HTTP/1.0 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('The new slide order was not specified, or there were no items within it.', 'total_slider')));
			die();
		}
		
		if (empty($_POST['slidesort']) || !is_array($_POST['slidesort']) || count($_POST['slidesort']) < 1) {
			header('HTTP/1.1 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('The new slide order was not specified, or there were no items within it.', 'total_slider')));
			die();			
		}
		
		foreach($_POST['slidesort'] as $key => $item) {
			$_POST['slidesort'][$key] = preg_replace('[^0-9a-zA-Z_]', '', $item);
		}
		
		$result = $g->reshuffle($_POST['slidesort']);
		
		if ($result === 'disparity')
		{
			header('HTTP/1.1 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('The new slide order is missing one or more current slides. Cannot save the new order, or slides would be lost. Please reload the page to ensure all current slides are in the sorting area and try sorting again.', 'total_slider')));
			die();				
		}
		else if ($result === true)
		{
			header('Content-Type: application/json');
			echo json_encode(array('success' => 'true'));
			die();
		}
		else {
			header('HTTP/1.0 500 Internal Server Error');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('The sort slide operation failed at the server.', 'total_slider')));
			die();			
		}
	
	break;
	
	case 'deleteSlide':
	
		if (!current_user_can(TOTAL_SLIDER_REQUIRED_CAPABILITY))
		{
			header('HTTP/1.0 403 Forbidden');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('Your user does not have the required permission level. Are you sure you are still logged in to the WordPress dashboard?', 'total_slider')));
			die();
		}
		
		// error out if the POST format isn't right
		if ( !array_key_exists('id', $_POST) )
		{
			header('HTTP/1.0 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('You have not supplied the ID to delete.', 'total_slider')));
			die();
		}
		
		// we need the slide ID
		$_POST['id'] = preg_replace('[^0-9a-zA-Z_]', '', $_POST['id']);
		
		if (empty($_POST['id']))
		{
			header('HTTP/1.0 400 Bad Request');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('You have not supplied the ID to delete.', 'total_slider')));
			die();				
		}
		
		$result = $g->deleteSlide($_POST['id']);
		
		if ($result)
		{
			header('Content-Type: application/json');
			echo json_encode(array('success' => 'true'));			
			die();
		}
		else {
			header('HTTP/1.1 500 Internal Server Error');
			header('Content-Type: application/json');
			echo json_encode(array('error' => __('The delete slide operation failed at the server. Perhaps it has already been deleted by someone else.', 'total_slider')));		
			die();
		}						
	
	break;
	
	default:

		header('HTTP/1.1 403 Forbidden');
		die('<h1>Forbidden</h1>');
		
	break;


}

header('Content-Type: application/json');

?>
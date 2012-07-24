<?php
/*

	Template Manager
	
	Handles the determination of canonical template URIs and paths for inclusion and
	enqueue purposes, as well as rendering the templates for edit-time JavaScript purposes.

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

if (!defined('TOTAL_SLIDER_IN_FUNCTIONS'))
{
	header('HTTP/1.1 403 Forbidden');
	die('<h1>Forbidden</h1>');
}

class Total_Slider_Template {
	
	private $slug;
	private $location; // one of 'builtin','theme','downloaded'
	
	private $mdName;
	private $mdURI;
	private $mdDescription;
	private $mdVersion;
	private $mdAuthor;	
	
	private $phpPath;
	private $jsPath;
	private $cssPath;
	
	private $phpURI;
	private $jsURI;
	private $cssURI;
	
	private static $allowedTemplateLocations = array(
		'builtin',
		'theme',
		'downloaded'
	);
	
	public function __construct($slug, $location)
	{
	/*
		Prepare this Template -- pass in the slug of its directory, as
		well as the location ('builtin','theme','downloaded').
		
		We will check for existence, prepare to be asked about this template's
		canonical URIs and paths, and, if required, be ready to render this
		template to JavaScript.
	*/
	
		$this->slug = $this->sanitizeSlug($slug);
		
		if (in_array($location, $this->allowedTemplateLocations))
		{
			$this->location = $location;
		}
		else {
			throw new Exception(); //TODO how do we handle this, I wonder?			
		}		
		
	}
	
	private function sanitizeSlug($slug)
	{
	/*
		Sanitize a template slug before we assign it to our instance internally, or print
		it anywhere.
		
		A template slug must be fewer than 128 characters in length, unique, and consist only
		of the following characters:
		
			a-z, A-Z, 0-9, _, -
			
		The slug is used as the directory name for the template, as well as the basename for its
		standard PHP, CSS and JS files.
		
	*/
	
		return substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', $slug), 0, 128 );
		
	}
	
	
};


?>
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

// expected directories where we'll find templates, in the plugin (builtin) and elsewhere
define('TOTAL_SLIDER_TEMPLATES_BUILTIN_DIR', 'templates');
define('TOTAL_SLIDER_TEMPLATES_DIR', 'total-slider-templates');

/* Exceptions:

	1xx -- invalid input or arguments

		101 -- the template location is not one of the allowed template locations.
		102 -- Unable to determine the WP_CONTENT_DIR to load this template.
		
	2xx -- unable to load the template
		201 -- The template's %s file was not found, but we expected to find it at '%s'.
	
*/

class Total_Slider_Template {
	
	private $slug;
	private $location; // one of 'builtin','theme','downloaded'
	
	private $mdName;
	private $mdURI;
	private $mdDescription;
	private $mdVersion;
	private $mdAuthor;
	
	private $pathPrefix = null;
	private $uriPrefix = null;
	
	private $phpPath = null;
	private $jsPath = null;
	private $jsDevPath = null;
	private $cssPath = null;
	
	private $phpURI = null;
	private $jsURI = null;
	private $jsDevURI = null;
	private $cssURI = null;
	
	private $allowedTemplateLocations = array(
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
		canonical URIs and paths, and, if required, be ready to load metadata
		from the PHP file, and render the template for JavaScript edit-side purposes.
	*/
	
		// get some key things ready
		$this->slug = $this->sanitizeSlug($slug);
		
		if (in_array($location, $this->allowedTemplateLocations))
		{
			$this->location = $location;
		}
		else
		{
			throw new UnexpectedValueException(__('The supplied template location is not one of the allowed template locations', 'total_slider'), 101);
			return;
		}
		
		// we will load canonicalised paths and urls, and check for existence, but be lazy about metadata
		$this->canonicalize();
		
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
	
	private function canonicalize()
	{
	/*
		Construct canonical paths and URLs for this template, by using the template slug
		and the location to work out where the template files are.
		
		We must check that these canonical paths correspond to files that exist, so we are ready
		for enqueuing and such.
	*/
	
		switch ($this->location)
		{
			
			case 'builtin':
				$pathPrefix = plugin_dir_path( dirname(__FILE__) ) . '/' . TOTAL_SLIDER_TEMPLATES_BUILTIN_DIR . '/';
				$uriPrefix = plugin_dir_url( dirname(__FILE__) ) . '/'. TOTAL_SLIDER_TEMPLATES_BUILTIN_DIR . '/';
				
				$phpExists = @file_exists($pathPrefix . $this->slug . '/' . $this->slug . '.php' );
				$cssExists = @file_exists($pathPrefix . $this->slug . '/' . 'style.css');
				$jsExists = @file_exists($pathPrefix . $this->slug . '/' . $this->slug . '.js' );
				$jsDevExists = @file_exists($pathPrefix . $this->slug . '/' . $this->slug . '.dev.js' ); 
				
				$missingFile = '';
				
				if (!$phpExists)
				{
					$missingFile = 'PHP';
					$expectedLocation = $pathPrefix . $this->slug . '/' . $this->slug . '.php';			
				}
				else if (!$jsExists)
				{
					$missingFile = 'JS';
					$expectedLocation = $pathPrefix . $this->slug . '/' . $this->slug . '.js';								
				}
				else if (!$cssExists)
				{
					$missingFile = 'CSS';
					$expectedLocation = $pathPrefix . $this->slug . '/style.css';										
				}
				
				else
				{
					$this->phpPath = $pathPrefix . $this->slug . '/' . $this->slug . '.php';
					$this->phpURI = $uriPrefix . $this->slug . '/' . $this->slug . '.php';
					
					$this->cssPath = $pathPrefix . $this->slug . '/style.css';
					$this->cssURI = $uriPrefix . $this->slug . '/style.css';

					$this->jsPath = $pathPrefix . $this->slug . '/' . $this->slug . '.js';
					$this->jsURI = $uriPrefix . $this->slug . '/' . $this->slug . '.js';
					
					if ($jsDevExists)
					{
						$this->jsDevPath = $pathPrefix . $this->slug . '/' . $this->slug . '.dev.js';
						$this->jsDevURI = $uriPrefix . $this->slug . '/' . $this->slug . '.dev.js';
					}
					
					$this->pathPrefix = $pathPrefix;
					$this->uriPrefix = $uriPrefix;
					
					return true;
					
				}
				
				// if a file was missing, then bubble up a relevant exception
				if (!empty($missingFile))
				{
					throw new RuntimeException(
						sprintf(__("The template's %s file was not found, but we expected to find it at '%s'.", 'total_slider'), $missingFile, $expectedLocation)
					, 201);
					return false;
				}
				
				
			break;
			
			case 'theme':
				// check in child theme 'get_stylesheet_*', if not, look in parent theme 'get_template_directory()'
				$prefix['child']['path'] = get_stylesheet_diretory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				$prefix['child']['uri'] = get_stylesheet_directory_uri() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				$prefix['parent']['path'] = get_template_directory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR .'/';
				$prefix['parent']['uri'] = get_template_directory_uri() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
				/* 
				
				Loop through each the child prefix and the parent prefix.
				
				If everything is in order in the child theme directory, we will use that as
				canonical.
				
				If not, and everything is in order in the parent theme directory, we will use that
				as canonical.
				
				Failing that, we can't load the template.
				
				*/
				foreach ($prefix as $p)
				{
				
					// load in either the child or parent JS
					if (!$this->jsPath || !$this->jsURI)
					{
						//TODO decide on whether it's [slug].js, or script.js, or something else
						// #19 -- decide on js expected filename
						if ( @file_exists($p['path'] . $this->slug . '/' . $this->slug . '.js' ))
						{
							$this->jsPath = $p['path'] . $this->slug . '/' . $this->slug . '.js';
							$this->jsURI = $p['uri'] . $this->slug . '/' . $this->slug . '.js'; 
						}
						else {
							$this->jsPath = null;
							$this->jsURI = null;
						}
						
						if ( @file_exists($p['path'] . $this->slug . '/' . $this->slug . '.dev.js'))
						{
							$this->jsDevPath = $p['path'] . $this->slug . '/' . $this->slug . '.dev.js';
							$this->jsDevURI = $p['uri'] . $this->slug . '/' . $this->slug . '.dev.js';						
						}
						else {
							$this->jsDevPath = null;
							$this->jsDevURI = null;
						}
					}			
				
					if (!$this->cssPath || !$this->cssURI || !$this->phpPath || !$this->phpURI) {
					
						// check for the PHP file and the CSS file
						if ( @file_exists($p['path'] . $this->slug . '/' . $this->slug . '.php' ) &&
							 @file_exists($p['path'] . $this->slug . '/' . 'style.css' )
						)
						{
							$this->phpPath = $p['path'] . $this->slug . '/' . $this->slug . '.php';
							$this->cssPath = $p['path'] . $this->slug . '/style.css';
							
							$this->phpURI = $p['uri'] . $this->slug . '/'. $this->slug . '.php';
							$this->cssURI = $p['uri'] . $this->slug . '/style.css';
							
							$this->pathPrefix = $p['path'];
							$this->uriPrefix = $p['uri'];
												
						}
						else {
							$this->phpPath = null;
							$this->cssPath = null;
							
							$this->phpURI = null;
							$this->cssURI = null;
						}
					}
										
				}
				
				$missingFile = '';
				
				// if any paths are null, we can't load the template
				if (!$this->phpPath || !$this->phpURI)
				{
					$missingFile = 'PHP';
					$expectedLocation = $prefix['child']['path'] . $this->slug . '/' . $this->slug . '.php\' or \''; // allow the error message to include 'or' parent hint
					$expectedLocation .= $prefix['parent']['path'] . $this->slug . '/' . $this->slug . '.php';
				}
				else if (!$this->jsPath || !$this->jsURI)
				{
					$missingFile = 'JS';
					$expectedLocation = $prefix['child']['path'] . $this->slug . '/' . $this->slug . '.js\' or \'';
					$expectedLocation .= $prefix['parent']['path'] . $this->slug . '/' . $this->slug . '.js';
				}
				else if (!$this->cssPath || !$this->cssURI)
				{
					$missingFile = 'CSS';
					$expectedLocation = $prefix['child']['path'] . $this->slug . '/style.css\' or \'';
					$expectedLocation .= $prefix['parent']['path'] . $this->slug . '/style.css';					
				}
				
				// if a file was missing, then bubble up a relevant exception
				if (!empty($missingFile))
				{
					throw new RuntimeException(
						sprintf(__("The template's %s file was not found, but we expected to find it at '%s'.", 'total_slider'), $missingFile, $expectedLocation)
					, 201);
					return false;
				}
				else {
					return true;
				}
								
			break;
			
			case 'downloaded':
				//NOTE: in the conspicious absence of a `content_path()` function, we must use the WP_CONTENT_DIR constant
				
				if (!defined('WP_CONTENT_DIR'))
				{
					throw new UnexpectedValueException(__('Unable to determine the WP_CONTENT_DIR, so cannot load this template.', 'total_slider'), 102);
					return false;					
				}
				
				$pathPrefix = WP_CONTENT_DIR . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				$uriPrefix = content_url() . '/'. TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
				$phpExists = @file_exists($pathPrefix . $this->slug . '/' . $this->slug . '.php' );
				$cssExists = @file_exists($pathPrefix . $this->slug . '/style.css');
				$jsExists = @file_exists($pathPrefix . $this->slug . '/' . $this->slug . '.js' );
				$jsDevExists = @file_exists($pathPrefix . $this->slug . '/' . $this->slug . '.dev.js' );
								
				$missingFile = '';
				
				if (!$phpExists)
				{
					$missingFile = 'PHP';
					$expectedLocation = $pathPrefix . $this->slug . '/' . $this->slug . '.php';			
				}
				else if (!$jsExists)
				{
					$missingFile = 'JS';
					$expectedLocation = $pathPrefix . $this->slug . '/' . $this->slug . '.js';								
				}
				else if (!$cssExists)
				{
					$missingFile = 'CSS';
					$expectedLocation = $pathPrefix . $this->slug . '/style.css';										
				}
				
				else
				{
					$this->phpPath = $pathPrefix . $this->slug . '/' . $this->slug . '.php';
					$this->phpURI = $uriPrefix . $this->slug . '/' . $this->slug . '.php';
					
					$this->cssPath = $pathPrefix . $this->slug . '/style.css';
					$this->cssURI = $uriPrefix . $this->slug . '/style.css';

					$this->jsPath = $pathPrefix . $this->slug . '/' . $this->slug . '.js';
					$this->jsURI = $uriPrefix . $this->slug . '/' . $this->slug . '.js';
					
					if ($jsDevExists)
					{
						$this->jsDevPath = $pathPrefix . $this->slug . '/' . $this->slug . '.dev.js';
						$this->jsDevURI = $uriPrefix . $this->slug . '/' . $this->slug . '.dev.js';
					}					
					
					$this->pathPrefix = $pathPrefix;
					$this->uriPrefix = $uriPrefix;					
					
					return true;
					
				}
				
				// if a file was missing, then bubble up a relevant exception
				if (!empty($missingFile))
				{
					throw new RuntimeException(
						sprintf(__("The template's %s file was not found, but we expected to find it at '%s'.", 'total_slider'), $missingFile, $expectedLocation)
					, 201);
					return false;
				}			
			break;
			
			default:
				throw new UnexpectedValueException(__('The supplied template location is not one of the allowed template locations', 'total_slider'), 101);
				return false;				
			break;
			
			
		}
		
	}
	
	public function render()
	{
	/*
		Render this template, using the pseudo-widget class, so that it will be executed,
		calls to the widget public methods will product the EJS placeholder text instead of
		rendering actual slide information.
		
		The result will be buffered and ready for use by the client-side code.
	*/
	
		if (!$this->phpPath)
		{
			$this->canonicalize();
		}
		
		// prepare a widget templater for the template's use
		$s = new Total_Slider_Widget_Templater();
		
		// a modicum of "time between check and use" protection
		if (!@file_exists($this->phpPath))
		{
			throw new RuntimeException(
				sprintf(__("The template's %s file was not found, but we expected to find it at '%s'.", 'total_slider'), 'PHP', $this->phpPath)
			, 201);
			return false;
		}
		
		ob_start();
		require($this->phpPath);

		$renderedTemplate = ob_get_clean();
		
		unset($s);
		
		return $renderedTemplate;	
		
	}
	
	/***********	// !Canonical path and URI accessor methods		***********/
	
	public function pathPrefix()
	{
	/*
		Return the canonical path for this template.
	*/	
	
		if (!$this->pathPrefix)
		{
			$this->canonicalize();
		}
		
		return $this->pathPrefix;
		
	}
	
	public function uriPrefix()
	{
	/*
		Return the canonical URI for this template.
	*/
	
		if (!$this->uriPrefix)
		{
			$this->canonicalize();
		}
		
		return $this->uriPrefix;
		
	}
	
	public function phpPath()
	{
	/*
		Return the canonical path to this template's PHP file.
	*/
		
		if (!$this->phpPath)
		{
			$this->canonicalize();
		}
		
		return $this->phpPath;
		
	}
	
	public function jsPath()
	{
	/*
		Return the canonical path to this template's JavaScript file.
	*/
	
		if (!$this->jsPath)
		{
			$this->canonicalize();
		}
		
		return $this->jsPath;
	
	}
	
	public function jsDevPath()
	{
	/*
		Return the canonical path to this template's development (non-minified) JavaScript file.
	*/	
	
		if (!$this->jsDevPath)
		{
			return $this->jsPath;
		}
		
		return $this->jsDevPath;
				
	}
	
	public function cssPath()
	{
	/*
		Return the canonical path to this template's CSS file.
	*/	
		if (!$this->cssPath)
		{
			$this->canonicalize();
		}
		
		return $this->cssPath;		
	}
	
	public function phpURI()
	{
	/*
		Return the canonical URI for this template's PHP file.
	*/
		if (!$this->phpURI)
		{
			$this->canonicalize();
		}
		
		return $this->phpURI;	
		
	}
	
	public function jsURI()
	{
	/*
		Return the canonical URI for this template's JavaScript file.
	*/
		if (!$this->jsURI)
		{
			$this->canonicalize();
		}
		
		return $this->jsURI;
		
	}
	
	public function jsDevURI()
	{
	/*
		Return the canonical URI for this template's development (non-minified) JavaScript file.
	*/
		if (!$this->jsDevURI)
		{
			return $this->jsURI;
		}
		
		return $this->jsDevURI;
		
	}
	
	public function cssURI()
	{
	/*
		Return the canonical URI for this template's PHP file.
	*/
		if (!$this->cssURI)
		{
			$this->canonicalize();
		}
		
		return $this->cssURI;	
		
	}
	
	/***********	// !Metadata accessor methods		***********/
	
};


class Total_Slider_Widget_Templater
{
/*
	A 'dummy' class that behaves like Total_Slider_Widget, and that is used to render the template's
	$s calls to CanJS EJS-friendly tokens, so that the editing interface JS can alter the template's
	placeholder data in real-time.
*/

	private $counter = 0;

	//NOTE: the FE format for these tokens is not finalised and is placeholder only

	public function slides_count()
	{
	/*
		Return the number of slides in this slide group.

		Can also be used by templates to test if there are any slides to show at all,
		and, for example, not output the starting <ul>.
	*/

		return 1;

	}
	
	public function is_runtime()
	{
	/*
		Allows the template to be aware of whether it is running at runtime (viewing as part of the
		actual site): 'true', or at edit-time (the user is editing slides in the admin interface, and
		the template is executing as a preview): 'false'.
	*/
	
		return false;
		
	}


	public function has_slides()
	{
	/*
		For our purposes, we want the slide previewer to load the template for one slide only.
	*/
	
		++$this->counter;
		
		if ($this->counter > 1)
			return false;
		else
			return true;


	}

	public function the_title()
	{
	/*
		Print the slide title token to output.
	*/

		echo $this->get_the_title();

	}

	public function get_the_title()
	{
	/*
		Return the slide title token.
	*/

		return '[%= title %]';

	}

	public function the_description()
	{
	/*
		Print the slide description token.
	*/

		echo $this->get_the_description();

	}

	public function get_the_description()
	{
	/*
		Return the slide description token.
	*/

		return '[%= description %]';

	}

	public function the_background_url()
	{
	/*
		Print the background URL token.
	*/

		echo $this->get_the_background_url();

	}

	public function get_the_background_url()
	{
	/*
		Return the background URL token.
	*/

		return '[%= background_url %]'; // use other quote style -- likely in a url() CSS block

	}

	public function the_link()
	{
	/*
		Print the slide link token.
	*/

		echo $this->get_the_link();

	}

	public function get_the_link()
	{
	/*
		Return the slide link token.
	*/

		return '[%= link %]';

	}

	public function the_x()
	{
	/*
		Print the X coordinate token.
	*/

		echo $this->get_the_x();

	}

	public function get_the_x()
	{
	/*
		Return the X coordinate token.
	*/

		return '[%= x %]';

	}

	public function the_y()
	{
	/*
		Print the Y coordinate token.
	*/

		echo $this->get_the_y();

	}

	public function get_the_y()
	{
	/*
		Return the Y coordinate token.
	*/

		return '[%= y %]';

	}

	public function the_identifier()
	{
	/*
		Print the slide identifier token.
	*/

		echo $this->get_the_identifier();

	}

	public function get_the_identifier()
	{
	/*
		Return the slide identifier token.
	*/

		return '[%= identifier %]';

	}

	public function iteration()
	{
	/*
		Return the iteration number. How many slides have we been through?
	*/

		return intval ( $this->counter - 1 );
		// has_slides() always bumps the iteration ready for the next run, but we
		// are still running, for the theme's purposes, on the previous iteration.
		// Hence, returning the iteration - 1.

	}
	
	public function make_draggable()
	{
	/*
		Outputs a class that in edit-time mode makes the object draggable (for X/Y positioning
		of the title/description overlay).
	
		Should be called when inside a DOM object's 'class' attribute.
		
		Does nothing at runtime.
	*/
		
		echo 'total-slider-template-draggable';
		
	}	
	
	public function draggable_parent()
	{
	/*
		Outputs a class that in edit-time mode makes the object the draggable's parent. This
		will be used to calculate the X/Y offset for the title/description box.
		
		This element will also be used as the containment for the draggable title/description box,
		i.e. the box will not be able to be dragged outside of the object marked with this class.
		
		Should be called when inside a DOM object's 'class' attribute.
		
		Does nothing at runtime.
	*/
		
		echo 'total-slider-template-draggable-parent';
		
	}


}

?>
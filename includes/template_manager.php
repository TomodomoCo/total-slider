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

if ( ! defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );


/* Exceptions:

	1xx -- invalid input or arguments

		101 -- the template location is not one of the allowed template locations.
		102 -- Unable to determine the WP_CONTENT_DIR to load this template.
		103 -- The allowed template locations are not available. This file must not be loaded without slide_group.php
		
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
	private $mdAuthorURI;
	private $mdLicense;
	private $mdLicenseURI;
	private $mdTags;
	
	private $options;
	
	private $templateFile = null;
	private $templatePHPFile = null;
	
	private $pathPrefix = null;
	private $uriPrefix = null;
	
	private $phpPath = null;
	private $jsPath = null;
	private $jsMinPath = null;
	private $cssPath = null;
	
	private $phpURI = null;
	private $jsURI = null;
	private $jsMinURI = null;
	private $cssURI = null;
	
	public function __construct($slug, $location)
	{
	/*
		Prepare this Template -- pass in the slug of its directory, as
		well as the location ('builtin','theme','downloaded').
		
		We will check for existence, prepare to be asked about this template's
		canonical URIs and paths, and, if required, be ready to load metadata
		from the PHP file, and render the template for JavaScript edit-side purposes.
	*/
	
		global $allowedTemplateLocations;
		
		if (!is_array($allowedTemplateLocations))
		{
			throw new UnexpectedValueException(__('The allowed template locations are not available. This file must not be loaded without slide_group.php', 'total_slider'), 103);
			return;
		}
	
		// get some key things ready
		$this->slug = $this->sanitizeSlug($slug);
		
		if (in_array($location, $allowedTemplateLocations))
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
	
	public static function sanitizeSlug($slug)
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
				$jsMinExists = @file_exists($pathPrefix . $this->slug . '/' . $this->slug . '.min.js' ); 
				
				$missingFile = '';
				
				if (!$phpExists)
				{
					$missingFile = 'PHP';
					$expectedLocation = $pathPrefix . $this->slug . '/' . $this->slug . '.php';			
				}
				else if (!$jsExists && !$jsMinExists)
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

					if ($jsExists)
					{
						$this->jsPath = $pathPrefix . $this->slug . '/' . $this->slug . '.js';
						$this->jsURI = $uriPrefix . $this->slug . '/' . $this->slug . '.js';
					}
					
					if ($jsMinExists)
					{
						$this->jsMinPath = $pathPrefix . $this->slug . '/' . $this->slug . '.min.js';
						$this->jsMinURI = $uriPrefix . $this->slug . '/' . $this->slug . '.min.js';
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
				$prefix['child']['path'] = get_stylesheet_directory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
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
						if ( @file_exists($p['path'] . $this->slug . '/' . $this->slug . '.js' ))
						{
							$this->jsPath = $p['path'] . $this->slug . '/' . $this->slug . '.js';
							$this->jsURI = $p['uri'] . $this->slug . '/' . $this->slug . '.js'; 
						}
						else {
							$this->jsPath = null;
							$this->jsURI = null;
						}
						
						if ( @file_exists($p['path'] . $this->slug . '/' . $this->slug . '.min.js'))
						{
							$this->jsMinPath = $p['path'] . $this->slug . '/' . $this->slug . '.min.js';
							$this->jsMinURI = $p['uri'] . $this->slug . '/' . $this->slug . '.min.js';						
						}
						else {
							$this->jsMinPath = null;
							$this->jsMinURI = null;
						}
					}			
				
					if (!$this->cssPath || !$this->cssURI || !$this->phpPath || !$this->phpURI) {
					
						// check for the PHP file and the CSS file
						if ( @file_exists($p['path'] . $this->slug . '/' . $this->slug . '.php' ) )
						{
							$this->phpPath = $p['path'] . $this->slug . '/' . $this->slug . '.php';
							$this->phpURI = $p['uri'] . $this->slug . '/'. $this->slug . '.php';
						}
						else {
							$this->phpPath = null;
							$this->phpURI = null;				
						}
						
						if ( @file_exists($p['path'] . $this->slug . '/' . 'style.css' ) )
						{
							$this->cssPath = $p['path'] . $this->slug . '/style.css';
							$this->cssURI = $p['uri'] . $this->slug . '/style.css';
						}
						else {
							$this->cssPath = null;
							$this->cssURI = null;						
						}
					}
										
				}
				
				$missingFile = '';
				
				// if any paths are null, we can't load the template
				if (!$this->phpPath || !$this->phpURI)
				{
					$missingFile = 'PHP';
					$expectedLocation = $prefix['child']['path'] . $this->slug . '/' . $this->slug . '.php';
					if ($prefix['child']['path'] != $prefix['parent']['path']) {
						$expectedLocation .=  '\' or \''; // allow the error message to include 'or' parent hint
						$expectedLocation .= $prefix['parent']['path'] . $this->slug . '/' . $this->slug . '.php';
					}
				}
				else if ( (!$this->jsPath || !$this->jsURI) && (!$this->jsMinPath || !$this->jsMinURI) )
				{
					$missingFile = 'JS';
					$expectedLocation = $prefix['child']['path'] . $this->slug . '/' . $this->slug . '.js';
					if ($prefix['child']['path'] != $prefix['parent']['path']) {
						$expectedLocation .=  '\' or \''; // allow the error message to include 'or' parent hint
						$expectedLocation .= $prefix['parent']['path'] . $this->slug . '/' . $this->slug . '.js';
					}
				}
				else if (!$this->cssPath || !$this->cssURI)
				{
					$missingFile = 'CSS';
					$expectedLocation = $prefix['child']['path'] . $this->slug . '/style.css';
					if ($prefix['child']['path'] != $prefix['parent']['path']) {
						$expectedLocation .=  '\' or \''; // allow the error message to include 'or' parent hint
						$expectedLocation .= $prefix['parent']['path'] . $this->slug . '/' . $this->slug . '.css';
					}
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
				$jsMinExists = @file_exists($pathPrefix . $this->slug . '/' . $this->slug . '.min.js' );
								
				$missingFile = '';
				
				if (!$phpExists)
				{
					$missingFile = 'PHP';
					$expectedLocation = $pathPrefix . $this->slug . '/' . $this->slug . '.php';			
				}
				else if (!$jsExists && !$jsMinExists)
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
					
					if ($jsExists)
					{
						$this->jsPath = $pathPrefix . $this->slug . '/' . $this->slug . '.js';
						$this->jsURI = $uriPrefix . $this->slug . '/' . $this->slug . '.js';
					}
					
					if ($jsMinExists)
					{
						$this->jsMinPath = $pathPrefix . $this->slug . '/' . $this->slug . '.min.js';
						$this->jsMinURI = $uriPrefix . $this->slug . '/' . $this->slug . '.min.js';
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
			
			case 'legacy':
			
				// in the theme, but simply 'loose' in the total-slider-templates folder, rather than in its own subfolder
			
				$pathPrefix = get_stylesheet_directory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				$uriPrefix = get_stylesheet_directory_uri() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
				$phpExists = @file_exists($pathPrefix .  'total-slider-template.php' );
				$cssExists = @file_exists($pathPrefix . 'total-slider-template.css');
				$jsExists = @file_exists($pathPrefix . 'total-slider-template.js' );
				$jsMinExists = @file_exists($pathPrefix . 'total-slider-template.min.js' );
								
				$missingFile = '';
				
				if (!$phpExists)
				{
					$missingFile = 'PHP';
					$expectedLocation = $pathPrefix .  'total-slider-template.php';
				}
				else if (!$jsExists && !$jsMinExists)
				{
					$missingFile = 'JS';
					$expectedLocation = $pathPrefix . 'total-slider-template.js';	
				}
				else if (!$cssExists)
				{
					$missingFile = 'CSS';
					$expectedLocation = $pathPrefix . 'total-slider-template.css';
				}
				
				else
				{
					$this->phpPath = $pathPrefix . 'total-slider-template.php';
					$this->phpURI = $uriPrefix . 'total-slider-template.php';
					
					$this->cssPath = $pathPrefix . 'total-slider-template.css';
					$this->cssURI = $uriPrefix . 'total-slider-template.css';

					$this->jsPath = $pathPrefix . 'total-slider-template.js';
					$this->jsURI = $uriPrefix . 'total-slider-template.js';
					
					if ($jsMinExists)
					{
						$this->jsMinPath = $pathPrefix . 'total-slider-template.min.js';
						$this->jsMinURI = $uriPrefix . 'total-slider-template.min.js';
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
	
		if (!$this->jsPath && !$this->jsMinPath)
		{
			$this->canonicalize();
		}
		
		if (!$this->jsPath && $this->jsMinPath)
		{
			return $this->jsMinPath;
		}
		else
		{
			return $this->jsPath;
		}
	}
	
	public function jsMinPath()
	{
	/*
		Return the canonical path to this template's minified JavaScript file.
	*/	
	
		if (!$this->jsMinPath && !$this->jsPath)
		{
			$this->canonicalize();
		}

		if (!$this->jsMinPath && $this->jsPath)
		{
			return $this->jsPath;
		}
		else {
			return $this->jsMinPath;
		}		
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
		if (!$this->jsURI && !$this->jsMinURI)
		{
			$this->canonicalize();
		}
		
		if (!$this->jsURI && $this->jsMinURI)
		{
			return $this->jsMinURI;
		}
		else
		{	
			return $this->jsURI;
		}
	}
	
	public function jsMinURI()
	{
	/*
		Return the canonical URI for this template's minified JavaScript file.
	*/
		if (!$this->jsMinURI && !$this->jsURI)
		{
			$this->canonicalize();
		}
		
		if (!$this->jsMinURI && $this->jsURI)
		{
			return $this->jsURI;
		}
		else
		{
			return $this->jsMinURI;
		}
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
	
	public function name()
	{
	/*
		Return the friendly name for this template.
	*/	
	
		if ($this->mdName)
		{
			return $this->mdName; // caching
		}
	
		if (!$this->templateFile)
		{
			if (!$this->cssPath())
			{
				return $this->slug;
			}
			$this->templateFile = @file_get_contents($this->cssPath());	
		}
		
		// extract the template name
		$matches = array();
		preg_match('/^\s*Template\sName:\s*(.*)/im', $this->templateFile, $matches);

		if ($matches && count($matches) > 1)
		{
			$this->mdName = $matches[1];
			return $this->mdName;
		}
		else {
			if ($this->location == 'legacy')
			{
				return __('v1.0 Custom Template', 'total_slider');
			}
			else {
				return $this->slug;
			}
		}
		
	}
	
	public function uri()
	{
	/*
		Return the Template URI metadata for this template.
	*/	
		if ($this->mdURI)
		{
			return $this->mdURI; // caching
		}
	
		if (!$this->templateFile)
		{
			if (!$this->cssPath())
			{
				return false;
			}
			$this->templateFile = @file_get_contents($this->cssPath());	
		}
		
		// extract the template name
		$matches = array();
		preg_match('/^\s*Template\sURI:\s*(.*)/im', $this->templateFile, $matches);

		if ($matches && count($matches) > 1)
		{
			$this->mdURI = $matches[1];
			return $this->mdURI;
		}
		else {
			return false;			
		}		
		
	}

	public function description()
	{
	/*
		Return the Template URI metadata for this template.
	*/	
		if ($this->mdDescription)
		{
			return $this->mdDescription; // caching
		}
	
		if (!$this->templateFile)
		{
			if (!$this->cssPath())
			{
				return false;
			}
			$this->templateFile = @file_get_contents($this->cssPath());	
		}
		
		// extract the template name
		$matches = array();
		preg_match('/^\s*Description:\s*(.*)/im', $this->templateFile, $matches);

		if ($matches && count($matches) > 1)
		{
			$this->mdDescription = $matches[1];
			return $this->mdDescription;
		}
		else {
			return false;			
		}		
		
	}
	
	public function version()
	{
	/*
		Return the version number for this template.
	*/	
		if ($this->mdVersion)
		{
			return $this->mdVersion; // caching
		}
	
		if (!$this->templateFile)
		{
			if (!$this->cssPath())
			{
				return false;
			}
			$this->templateFile = @file_get_contents($this->cssPath());	
		}
		
		// extract the template name
		$matches = array();
		preg_match('/^\s*Version:\s*(.*)/im', $this->templateFile, $matches);

		if ($matches && count($matches) > 1)
		{
			$this->mdVersion = $matches[1];
			return $this->mdVersion;
		}
		else {
			return false;			
		}		
		
	}
	
	public function author()
	{
	/*
		Return the author name for this template.
	*/	
		if ($this->mdAuthor)
		{
			return $this->mdAuthor; // caching
		}
	
		if (!$this->templateFile)
		{
			if (!$this->cssPath())
			{
				return false;
			}
			$this->templateFile = @file_get_contents($this->cssPath());	
		}
		
		// extract the template name
		$matches = array();
		preg_match('/^\s*Author:\s*(.*)/im', $this->templateFile, $matches);

		if ($matches && count($matches) > 1)
		{
			$this->mdAuthor = $matches[1];
			return $this->mdAuthor;
		}
		else {
			return false;			
		}		
		
	}
	
	public function authorURI()
	{
	/*
		Return the author URI for this template.
	*/	
		if ($this->mdAuthorURI)
		{
			return $this->mdAuthorURI; // caching
		}
	
		if (!$this->templateFile)
		{
			if (!$this->cssPath())
			{
				return false;
			}
			$this->templateFile = @file_get_contents($this->cssPath());	
		}
		
		// extract the template name
		$matches = array();
		preg_match('/^\s*Author\s*URI:\s*(.*)/im', $this->templateFile, $matches);

		if ($matches && count($matches) > 1)
		{
			$this->mdAuthorURI = $matches[1];
			return $this->mdAuthorURI;
		}
		else {
			return false;			
		}		
		
	}
	
	public function license()
	{
	/*
		Return the license metadata for this template.
	*/	
		if ($this->mdLicense)
		{
			return $this->mdLicense; // caching
		}
	
		if (!$this->templateFile)
		{
			if (!$this->cssPath())
			{
				return false;
			}
			$this->templateFile = @file_get_contents($this->cssPath());	
		}
		
		// extract the template name
		$matches = array();
		preg_match('/^\s*License:\s*(.*)/im', $this->templateFile, $matches);

		if ($matches && count($matches) > 1)
		{
			$this->mdLicense = $matches[1];
			return $this->mdLicense;
		}
		else {
			return false;			
		}
		
	}
	
	public function licenseURI()
	{
	/*
		Return the license URI for this template.
	*/	
		if ($this->mdLicenseURI)
		{
			return $this->mdLicenseURI; // caching
		}
	
		if (!$this->templateFile)
		{
			if (!$this->cssPath())
			{
				return false;
			}
			$this->templateFile = @file_get_contents($this->cssPath());	
		}
		
		// extract the template name
		$matches = array();
		preg_match('/^\s*License\s*URI:\s*(.*)/im', $this->templateFile, $matches);

		if ($matches && count($matches) > 1)
		{
			$this->mdLicenseURI = $matches[1];
			return $this->mdLicenseURI;
		}
		else {
			return false;			
		}
		
	}
	
	public function tags()
	{
	/*
		Return the license URI for this template.
	*/	
		if ($this->mdTags)
		{
			return $this->mdTags; // caching
		}
	
		if (!$this->templateFile)
		{
			if (!$this->cssPath())
			{
				return false;
			}
			$this->templateFile = @file_get_contents($this->cssPath());	
		}
		
		// extract the template name
		$matches = array();
		preg_match('/^\s*Tags:\s*(.*)/im', $this->templateFile, $matches);

		if ($matches && count($matches) > 1)
		{
			$this->mdTags = $matches[1];
			$this->mdTags = explode(',', $this->mdTags);
			
			return $this->mdTags;
		}
		else {
			return false;			
		}
		
	}
	
	public function determineOptions()
	{
	/*
		Determine the desired crop height and crop width for the background image, as well as other options, including
		disabling X/Y positioning in admin.

		Requires that custom template PHP include something like the following:
			/*
			Template Options

			Crop-Suggested-Width: 600
			Crop-Suggested-Height: 300
			Disable-XY-Positioning-In-Admin: No
			*/
		/*

		These are parsed as configuration directives for the admin-side.

	*/

		if (isset($this->options) && is_array($this->options) && count($this->options) > 0)
		{
			// cache results
			return $this->options;
		}
		
		if (!$this->templatePHPFile)
		{
			if (!$this->phpPath())
			{
				return false;
			}
			$this->templatePHPFile = @file_get_contents($this->phpPath());	
		}

		if ($this->templatePHPFile !== false)
		{
			// look for Crop-Suggested-Width: xx directive
			$matches = array();
			preg_match('/^\s*Crop\-Suggested\-Width:\s*([0-9]+)/im', $this->templatePHPFile, $matches);
			if (count($matches) == 2)
			{
				if (intval($matches[1]) == $matches[1])
				{
					$cropWidth = intval( $matches[1] );
				}
				else {
					$cropWidth = TOTAL_SLIDER_DEFAULT_CROP_WIDTH;
				}
			}
			else {
				$cropWidth = TOTAL_SLIDER_DEFAULT_CROP_WIDTH;
			}

			// look for Crop-Suggested-Height: xx directive
			$matches = array();
			preg_match('/^\s*Crop\-Suggested\-Height:\s*([0-9]+)/im', $this->templatePHPFile, $matches);
			if (count($matches) == 2)
			{
				if (intval($matches[1]) == $matches[1])
				{
					$cropHeight = intval( $matches[1] );
				}
				else {
					$cropHeight = TOTAL_SLIDER_DEFAULT_CROP_HEIGHT;
				}
			}
			else {
				$cropHeight = TOTAL_SLIDER_DEFAULT_CROP_HEIGHT;
			}

			// look for Disable-XY-Positioning-In-Admin directive
			$matches = array();
			preg_match('/^\s*Disable\-XY\-Positioning\-In\-Admin:\s*(Yes|No|On|Off|1|0|True|False)/im', $this->templatePHPFile, $matches);
			$affirmativeResponses = array('yes', 'on', '1', 'true');
			//$negativeResponses = array('no', 'off', '0', 'false');

			if (count($matches) == 2)
			{
				if (in_array(strtolower($matches[1]), $affirmativeResponses))
				{
					$disableXY = true;
				}
				else {
					$disableXY = false;
				}
			}
			else {
				$disableXY = false;
			}

		}
		else {
			$cropWidth = TOTAL_SLIDER_DEFAULT_CROP_WIDTH;
			$cropHeight = TOTAL_SLIDER_DEFAULT_CROP_HEIGHT;
			$disableXY = false;
		}

		// cache results in global $templateOptions
		$this->options = array('crop_width' => $cropWidth, 'crop_height' => $cropHeight, 'disable_xy' => $disableXY);
		return $this->options;
				
	}
	
};


class Total_Slider_Widget_Templater
{
/*
	A 'dummy' class that behaves like Total_Slider_Widget, and that is used to render the template's
	$s calls to EJS-friendly tokens, so that the editing interface JS can alter the template's
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


};

class Total_Slider_Template_Iterator {
/*
	This class hunts through any of the builtin, theme (stylesheet_ and template_)
	and downloaded (wp-content) directories to find templates.
	
	It will do crude checking for file existence and grab the template name, but leaves more detailed
	inspection to the Total_Slider_Template class (e.g. more metadata parsing).
*/

	public function discoverTemplates($location, $shouldParseName = true) {
	/*
		Discovers the template files that are available in the given location (one of 'builtin',
		'theme', 'downloaded', 'legacy'.
		
		Returns an array of the template slugs and names, which can be used for further inspection by
		instantiating the Total_Slider_Template class with the slug and location.
	*/
		global $allowedTemplateLocations;
		
		if (!is_array($allowedTemplateLocations))
		{
			throw new UnexpectedValueException(__('The allowed template locations are not available. This file must not be loaded without slide_group.php', 'total_slider'), 103);
			return false;
		}
		
		// check the location given is valid	
		if (!in_array($location, $allowedTemplateLocations))
		{
			throw new UnexpectedValueException(__('The supplied template location is not one of the allowed template locations', 'total_slider'), 101);
			return false;
		}
		
		// what path(s) should we walk?
		$paths = array();
		
		$cssName = 'style.css';
		
		switch ($location) {
			
			case 'builtin':
				$paths[] = plugin_dir_path( dirname(__FILE__) ) . '/' . TOTAL_SLIDER_TEMPLATES_BUILTIN_DIR . '/';
			break;
			
			case 'theme':
				$paths[] = get_stylesheet_directory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
				if (get_stylesheet_directory() != get_template_directory())
					$paths[] = get_template_directory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR .'/';				
			break;
			
			case 'downloaded':
				if (!defined('WP_CONTENT_DIR'))
				{
					throw new UnexpectedValueException(__('Unable to determine the WP_CONTENT_DIR, so cannot find relevant templates.', 'total_slider'), 102);
					return false;					
				}
				
				// in the absence of content_dir() existing, we must use the WP_CONTENT_DIR constant. Sorry!
				$paths[] = WP_CONTENT_DIR . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
			break;
			
			case 'legacy':
				$path = get_stylesheet_directory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
				if (!@file_exists($path) || !@is_dir($path) ) {
					return false;
				}
				
				$files = @scandir($path);
				
				if (!$files)
				{
					return false;
				}
				
				foreach($files as $f)
				{
					$templates = array();
					$i = 0;
					
					if ($f == 'total-slider-template.php' )
					{
						$templates[$i]['slug'] = Total_Slider_Template::sanitizeSlug(basename($f));
						$templates[$i]['name'] = __('v1.0 Custom Template', 'total_slider');
						return $templates;
					}
				}
				
				return false;
				
			break;
			
			default:
				return false;
			break;
						
		}
		
		$templates = array();
		$i = 0;
		
		// walk the walk
		foreach($paths as $key => $path)
		{
			if (!@file_exists($path) || !@is_dir($path) ) {
				continue;
			}
			
			$files = @scandir($path);
			
			if (!$files)
			{
				continue;
			}
			
			foreach($files as $f)
			{
			
				if ($f == '.' || $f == '..')
					continue;
			
				if (@is_dir($path . '/' . $f))
				{			
					if (@file_exists($path . '/' . $f . '/' . $cssName ))
					{
					
						if ($shouldParseName) {
					
							$tplContent = @file_get_contents( $path . '/' . $f . '/' . $cssName );
						
							// extract the template name
							$matches = array();
							preg_match('/^\s*Template\sName:\s*(.*)/im', $tplContent, $matches);
							
							unset($tplContent);
							
							$templates[$i]['slug'] = Total_Slider_Template::sanitizeSlug(basename($f));
							
							if ($matches && count($matches) > 1)
							{
								$templates[$i]['name'] = $matches[1];
							}
							
							++$i;
							
						}
						else {
						
							$templates[$i]['slug'] = Total_Slider_Template::sanitizeSlug(basename($f));
							++$i;
														
						}
						
					}
				}
			}
			
		}
		
		return $templates;		
		
	}
	
	
};

?>
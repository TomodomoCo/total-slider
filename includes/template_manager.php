<?php
/*

	Template Manager
	
	Handles the determination of canonical template URIs and paths for inclusion and
	enqueue purposes, as well as rendering the templates for edit-time JavaScript purposes.

/* ----------------------------------------------*/

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

if ( ! defined( 'TOTAL_SLIDER_IN_FUNCTIONS' ) ) {
	header( 'HTTP/1.1 403 Forbidden' );
	die( '<h1>Forbidden</h1>' );
}

// expected directories where we'll find templates, in the plugin (builtin) and elsewhere
define( 'TOTAL_SLIDER_TEMPLATES_BUILTIN_DIR', 'templates' );
define( 'TOTAL_SLIDER_TEMPLATES_DIR', 'total-slider-templates' );

if ( ! defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );


/* Exceptions:

	1xx -- invalid input or arguments

		101 -- the template location is not one of the allowed template locations.
		102 -- Unable to determine the WP_CONTENT_DIR to load this template.
		103 -- The allowed template locations are not available. This file must not be loaded without class.total-slide-group.php
		
	2xx -- unable to load the template
		201 -- The template's %s file was not found, but we expected to find it at '%s'.
	
*/

class Total_Slider_Template {
	
	private $slug;
	private $location; // one of 'builtin','theme','downloaded'
	
	private $md_name;
	private $md_uri;
	private $md_description;
	private $md_version;
	private $md_author;
	private $md_author_uri;
	private $md_license;
	private $md_license_uri;
	private $md_tags;
	
	private $options;
	
	private $template_file = null;
	private $template_php_file = null;
	
	private $path_prefix = null;
	private $uri_prefix = null;
	
	private $php_path = null;
	private $js_path = null;
	private $js_min_path = null;
	private $css_path = null;
	
	private $php_uri = null;
	private $js_uri = null;
	private $js_min_uri = null;
	private $css_uri = null;
	
	public function __construct( $slug, $location ) {
	/*
		Prepare this Template -- pass in the slug of its directory, as
		well as the location ('builtin','theme','downloaded').
		
		We will check for existence, prepare to be asked about this template's
		canonical URIs and paths, and, if required, be ready to load metadata
		from the PHP file, and render the template for JavaScript edit-side purposes.
	*/
	
		global $allowed_template_locations;
		
		if ( ! is_array($allowed_template_locations ) )	{
			throw new UnexpectedValueException( __( 'The allowed template locations are not available. This file must not be loaded without class.total-slide-group.php', 'total_slider' ), 103 );
			return;
		}
	
		// get some key things ready
		$this->slug = $this->sanitize_slug( $slug );
		
		if ( in_array( $location, $allowed_template_locations ) ) {
			$this->location = $location;
		}
		else {
			throw new UnexpectedValueException( __( 'The supplied template location is not one of the allowed template locations', 'total_slider' ), 101 );
			return;
		}
		
		// we will load canonicalised paths and urls, and check for existence, but be lazy about metadata
		$this->canonicalize();
		
	}
	
	public static function sanitize_slug($slug) {
	/*
		Sanitize a template slug before we assign it to our instance internally, or print
		it anywhere.
		
		A template slug must be fewer than 128 characters in length, unique, and consist only
		of the following characters:
		
			a-z, A-Z, 0-9, _, -
			
		The slug is used as the directory name for the template, as well as the basename for its
		standard PHP, CSS and JS files.
		
	*/
	
		return substr( preg_replace( '/[^a-zA-Z0-9_\-]/', '', $slug ), 0, 128 );
		
	}
	
	private function canonicalize() {
	/*
		Construct canonical paths and URLs for this template, by using the template slug
		and the location to work out where the template files are.
		
		We must check that these canonical paths correspond to files that exist, so we are ready
		for enqueuing and such.
	*/
	
		switch ( $this->location ) {
			
			case 'builtin':
				$path_prefix = plugin_dir_path( dirname( __FILE__ ) ) . '/' . TOTAL_SLIDER_TEMPLATES_BUILTIN_DIR . '/';
				$uri_prefix = plugin_dir_url( dirname( __FILE__ ) ) . '/'. TOTAL_SLIDER_TEMPLATES_BUILTIN_DIR . '/';
				
				$php_exists = @file_exists( $path_prefix . $this->slug . '/' . $this->slug . '.php' );
				$css_exists = @file_exists($path_prefix . $this->slug . '/' . 'style.css' );
				$js_exists = @file_exists( $path_prefix . $this->slug . '/' . $this->slug . '.js' );
				$js_min_exists = @file_exists( $path_prefix . $this->slug . '/' . $this->slug . '.min.js' ); 
				
				$missing_file = '';
				
				if ( ! $php_exists ) {
					$missing_file = 'PHP';
					$expected_location = $path_prefix . $this->slug . '/' . $this->slug . '.php';			
				}
				else if ( ! $js_exists && ! $js_min_exists ) {
					$missing_file = 'JS';
					$expected_location = $path_prefix . $this->slug . '/' . $this->slug . '.js';								
				}
				else if ( ! $css_exists ) {
					$missing_file = 'CSS';
					$expected_location = $path_prefix . $this->slug . '/style.css';										
				}
				
				else
				{
					$this->php_path = $path_prefix . $this->slug . '/' . $this->slug . '.php';
					$this->php_uri = $uri_prefix . $this->slug . '/' . $this->slug . '.php';
					
					$this->css_path = $path_prefix . $this->slug . '/style.css';
					$this->css_uri = $uri_prefix . $this->slug . '/style.css';

					if ( $js_exists ) {
						$this->js_path = $path_prefix . $this->slug . '/' . $this->slug . '.js';
						$this->js_uri = $uri_prefix . $this->slug . '/' . $this->slug . '.js';
					}
					
					if ( $js_min_exists ) {
						$this->js_min_path = $path_prefix . $this->slug . '/' . $this->slug . '.min.js';
						$this->js_min_uri = $uri_prefix . $this->slug . '/' . $this->slug . '.min.js';
					}
					
					$this->path_prefix = $path_prefix;
					$this->uri_prefix = $uri_prefix;
					
					return true;
					
				}
				
				// if a file was missing, then bubble up a relevant exception
				if ( ! empty( $missing_file ) )	{
					throw new RuntimeException(
						sprintf( __( "The template's %s file was not found, but we expected to find it at '%s'.", 'total_slider' ), $missing_file, $expected_location )
					, 201 );
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
				foreach ( $prefix as $p ) {
				
					// load in either the child or parent JS
					if ( ! $this->js_path || ! $this->js_uri ) {
						if ( @file_exists( $p['path'] . $this->slug . '/' . $this->slug . '.js'  ) ) {
							$this->js_path = $p['path'] . $this->slug . '/' . $this->slug . '.js';
							$this->js_uri = $p['uri'] . $this->slug . '/' . $this->slug . '.js'; 
						}
						else {
							$this->js_path = null;
							$this->js_uri = null;
						}
						
						if ( @file_exists($p['path'] . $this->slug . '/' . $this->slug . '.min.js' ) ) {
							$this->js_min_path = $p['path'] . $this->slug . '/' . $this->slug . '.min.js';
							$this->js_min_uri = $p['uri'] . $this->slug . '/' . $this->slug . '.min.js';						
						}
						else {
							$this->js_min_path = null;
							$this->js_min_uri = null;
						}
					}			
				
					if ( ! $this->css_path || ! $this->css_uri || ! $this->php_path || ! $this->php_uri ) {
					
						// check for the PHP file and the CSS file
						if ( @file_exists($p['path'] . $this->slug . '/' . $this->slug . '.php' ) ) {
							$this->php_path = $p['path'] . $this->slug . '/' . $this->slug . '.php';
							$this->php_uri = $p['uri'] . $this->slug . '/'. $this->slug . '.php';
						}
						else {
							$this->php_path = null;
							$this->php_uri = null;				
						}
						
						if ( @file_exists($p['path'] . $this->slug . '/' . 'style.css' ) ) {
							$this->css_path = $p['path'] . $this->slug . '/style.css';
							$this->css_uri = $p['uri'] . $this->slug . '/style.css';
						}
						else {
							$this->css_path = null;
							$this->css_uri = null;						
						}
					}
										
				}
				
				$missing_file = '';
				
				// if any paths are null, we can't load the template
				if ( ! $this->php_path || !$this->php_uri )	{
					$missing_file = 'PHP';
					$expected_location = $prefix['child']['path'] . $this->slug . '/' . $this->slug . '.php';
					
					if ( $prefix['child']['path'] != $prefix['parent']['path'] ) {
						$expected_location .=  '\' or \''; // allow the error message to include 'or' parent hint
						$expected_location .= $prefix['parent']['path'] . $this->slug . '/' . $this->slug . '.php';
					}
				}
				else if ( (!$this->js_path || !$this->js_uri) && (!$this->js_min_path || !$this->js_min_uri) ) {
					$missing_file = 'JS';
					$expected_location = $prefix['child']['path'] . $this->slug . '/' . $this->slug . '.js';
					if ( $prefix['child']['path'] != $prefix['parent']['path'] ) {
						$expected_location .=  '\' or \''; // allow the error message to include 'or' parent hint
						$expected_location .= $prefix['parent']['path'] . $this->slug . '/' . $this->slug . '.js';
					}
				}
				else if ( ! $this->css_path || !$this->css_uri ) {
					$missing_file = 'CSS';
					$expected_location = $prefix['child']['path'] . $this->slug . '/style.css';
					if ( $prefix['child']['path'] != $prefix['parent']['path'] ) {
						$expected_location .=  '\' or \''; // allow the error message to include 'or' parent hint
						$expected_location .= $prefix['parent']['path'] . $this->slug . '/' . $this->slug . '.css';
					}
				}
				
				// if a file was missing, then bubble up a relevant exception
				if ( ! empty( $missing_file ) ) {
					throw new RuntimeException(
						sprintf( __( "The template's %s file was not found, but we expected to find it at '%s'.", 'total_slider'), $missing_file, $expected_location)
					, 201 );
					return false;
				}
				else {
					return true;
				}
								
			break;
			
			case 'downloaded':
				//NOTE: in the conspicious absence of a `content_path()` function, we must use the WP_CONTENT_DIR constant
				
				if ( ! defined( 'WP_CONTENT_DIR' ) ) {
					throw new UnexpectedValueException(__('Unable to determine the WP_CONTENT_DIR, so cannot load this template.', 'total_slider'), 102);
					return false;					
				}
				
				$path_prefix = WP_CONTENT_DIR . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				$uri_prefix = content_url() . '/'. TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
				$php_exists = @file_exists( $path_prefix . $this->slug . '/' . $this->slug . '.php' );
				$css_exists = @file_exists( $path_prefix . $this->slug . '/style.css');
				$js_exists = @file_exists( $path_prefix . $this->slug . '/' . $this->slug . '.js' );
				$js_min_exists = @file_exists( $path_prefix . $this->slug . '/' . $this->slug . '.min.js' );
								
				$missing_file = '';
				
				if ( ! $php_exists ) {
					$missing_file = 'PHP';
					$expected_location = $path_prefix . $this->slug . '/' . $this->slug . '.php';			
				}
				else if ( ! $js_exists && ! $js_min_exists ) {
					$missing_file = 'JS';
					$expected_location = $path_prefix . $this->slug . '/' . $this->slug . '.js';								
				}
				else if ( ! $css_exists ) {
					$missing_file = 'CSS';
					$expected_location = $path_prefix . $this->slug . '/style.css';										
				}
				
				else {
					$this->php_path = $path_prefix . $this->slug . '/' . $this->slug . '.php';
					$this->php_uri = $uri_prefix . $this->slug . '/' . $this->slug . '.php';
					
					$this->css_path = $path_prefix . $this->slug . '/style.css';
					$this->css_uri = $uri_prefix . $this->slug . '/style.css';
					
					if ( $js_exists ) {
						$this->js_path = $path_prefix . $this->slug . '/' . $this->slug . '.js';
						$this->js_uri = $uri_prefix . $this->slug . '/' . $this->slug . '.js';
					}
					
					if ( $js_min_exists ) {
						$this->js_min_path = $path_prefix . $this->slug . '/' . $this->slug . '.min.js';
						$this->js_min_uri = $uri_prefix . $this->slug . '/' . $this->slug . '.min.js';
					}					
					
					$this->path_prefix = $path_prefix;
					$this->uri_prefix = $uri_prefix;					
					
					return true;
					
				}
				
				// if a file was missing, then bubble up a relevant exception
				if ( ! empty( $missing_file ) )	{
					throw new RuntimeException(
						sprintf( __( "The template's %s file was not found, but we expected to find it at '%s'.", 'total_slider' ), $missing_file, $expected_location )
					, 201 );
					return false;
				}			
			break;
			
			case 'legacy':
			
				// in the theme, but simply 'loose' in the total-slider-templates folder, rather than in its own subfolder
			
				$path_prefix = get_stylesheet_directory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				$uri_prefix = get_stylesheet_directory_uri() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
				$php_exists = @file_exists( $path_prefix .  'total-slider-template.php' );
				$css_exists = @file_exists( $path_prefix . 'total-slider-template.css');
				$js_exists = @file_exists( $path_prefix . 'total-slider-template.js' );
				$js_min_exists = @file_exists( $path_prefix . 'total-slider-template.min.js' );
								
				$missing_file = '';
				
				if ( ! $php_exists ) {
					$missing_file = 'PHP';
					$expected_location = $path_prefix .  'total-slider-template.php';
				}
				else if ( ! $js_exists && ! $js_min_exists ) {
					$missing_file = 'JS';
					$expected_location = $path_prefix . 'total-slider-template.js';	
				}
				else if ( ! $css_exists ) {
					$missing_file = 'CSS';
					$expected_location = $path_prefix . 'total-slider-template.css';
				}
				
				else {
					$this->php_path = $path_prefix . 'total-slider-template.php';
					$this->php_uri = $uri_prefix . 'total-slider-template.php';
					
					$this->css_path = $path_prefix . 'total-slider-template.css';
					$this->css_uri = $uri_prefix . 'total-slider-template.css';

					$this->js_path = $path_prefix . 'total-slider-template.js';
					$this->js_uri = $uri_prefix . 'total-slider-template.js';
					
					if ( $js_min_exists ) {
						$this->js_min_path = $path_prefix . 'total-slider-template.min.js';
						$this->js_min_uri = $uri_prefix . 'total-slider-template.min.js';
					}					
					
					$this->path_prefix = $path_prefix;
					$this->uri_prefix = $uri_prefix;
					
					return true;
					
				}
				
				// if a file was missing, then bubble up a relevant exception
				if ( ! empty( $missing_file ) )	{
					throw new RuntimeException(
						sprintf( __( "The template's %s file was not found, but we expected to find it at '%s'.", 'total_slider'), $missing_file, $expected_location)
					, 201 );
					return false;
				}					
			
			break;
			
			default:
				throw new UnexpectedValueException( __( 'The supplied template location is not one of the allowed template locations', 'total_slider' ), 101 );
				return false;				
			break;
			
			
		}
		
	}
	
	public function render() {
	/*
		Render this template, using the pseudo-widget class, so that it will be executed,
		calls to the widget public methods will product the EJS placeholder text instead of
		rendering actual slide information.
		
		The result will be buffered and ready for use by the client-side code.
	*/
	
		if ( ! $this->php_path )
		{
			$this->canonicalize();
		}
		
		// prepare a widget templater for the template's use
		$s = new Total_Slider_Widget_Templater();
		
		// a modicum of "time between check and use" protection
		if ( ! @ file_exists($this->php_path ) )
		{
			throw new RuntimeException(
				sprintf( __( "The template's %s file was not found, but we expected to find it at '%s'.", 'total_slider' ), 'PHP', $this->php_path )
			, 201 );
			return false;
		}
		
		ob_start();
		require($this->php_path);

		$rendered_template = ob_get_clean();
		
		unset($s);
		
		return $rendered_template;	
		
	}
	
	/***********	// !Canonical path and URI accessor methods		***********/
	
	public function path_prefix() {
	/*
		Return the canonical path for this template.
	*/	
	
		if ( ! $this->path_prefix ) {
			$this->canonicalize();
		}
		
		return $this->path_prefix;
		
	}
	
	public function uri_prefix() {
	/*
		Return the canonical URI for this template.
	*/
	
		if ( ! $this->uri_prefix ) {
			$this->canonicalize();
		}
		
		return $this->uri_prefix;
		
	}
	
	public function php_path() {
	/*
		Return the canonical path to this template's PHP file.
	*/
		
		if ( ! $this->php_path ) {
			$this->canonicalize();
		}
		
		return $this->php_path;
		
	}
	
	public function js_path() {
	/*
		Return the canonical path to this template's JavaScript file.
	*/
	
		if ( ! $this->js_path && ! $this->js_min_path ) {
			$this->canonicalize();
		}
		
		if ( ! $this->js_path && $this->js_min_path ) {
			return $this->js_min_path;
		}
		else {
			return $this->js_path;
		}
	}
	
	public function js_min_path() {
	/*
		Return the canonical path to this template's minified JavaScript file.
	*/	
	
		if ( ! $this->js_min_path && ! $this->js_path ) {
			$this->canonicalize();
		}

		if ( ! $this->js_min_path && $this->js_path ) {
			return $this->js_path;
		}
		else {
			return $this->js_min_path;
		}		
	}
	
	public function css_path() {
	/*
		Return the canonical path to this template's CSS file.
	*/	
		if ( ! $this->css_path ) {
			$this->canonicalize();
		}
		
		return $this->css_path;		
	}
	
	public function php_uri() {
	/*
		Return the canonical URI for this template's PHP file.
	*/
		if ( ! $this->php_uri ) {
			$this->canonicalize();
		}
		
		return $this->php_uri;	
		
	}
	
	public function js_uri() {
	/*
		Return the canonical URI for this template's JavaScript file.
	*/
		if ( ! $this->js_uri && ! $this->js_min_uri ) {
			$this->canonicalize();
		}
		
		if ( ! $this->js_uri && $this->js_min_uri )	{
			return $this->js_min_uri;
		}
		else {	
			return $this->js_uri;
		}
	}
	
	public function js_min_uri() {
	/*
		Return the canonical URI for this template's minified JavaScript file.
	*/
		if ( ! $this->js_min_uri && ! $this->js_uri ) {
			$this->canonicalize();
		}
		
		if ( ! $this->js_min_uri && $this->js_uri ) {
			return $this->js_uri;
		}
		else {
			return $this->js_min_uri;
		}
	}
	
	public function css_uri() {
	/*
		Return the canonical URI for this template's PHP file.
	*/
		if ( ! $this->css_uri ) {
			$this->canonicalize();
		}
		
		return $this->css_uri;	
		
	}
	
	/***********	// !Metadata accessor methods		***********/
	
	public function name() {
	/*
		Return the friendly name for this template.
	*/	
	
		if ( $this->md_name ) {
			return $this->md_name; // caching
		}
	
		if ( ! $this->template_file ) {
			if ( ! $this->css_path() ) {
				return $this->slug;
			}
			$this->template_file = @file_get_contents( $this->css_path() );
		}
		
		// extract the template name
		$matches = array();
		preg_match( '/^\s*Template\sName:\s*(.*)/im', $this->template_file, $matches );

		if ( $matches && count( $matches ) > 1 ) {
			$this->md_name = $matches[1];
			return $this->md_name;
		}
		else {
			if ( $this->location == 'legacy' ) {
				return __( 'v1.0 Custom Template', 'total_slider' );
			}
			else {
				return $this->slug;
			}
		}
		
	}
	
	public function uri() {
	/*
		Return the Template URI metadata for this template.
	*/	
		if ( $this->md_uri ) {
			return $this->md_uri; // caching
		}
	
		if ( ! $this->template_file ) {
			if ( ! $this->css_path() ) {
				return false;
			}
			$this->template_file = @file_get_contents( $this->css_path() );
		}
		
		// extract the template name
		$matches = array();
		preg_match( '/^\s*Template\sURI:\s*(.*)/im', $this->template_file, $matches );

		if ( $matches && count( $matches ) > 1 ) {
			$this->md_uri = $matches[1];
			return $this->md_uri;
		}
		else {
			return false;			
		}		
		
	}

	public function description() {
	/*
		Return the Template URI metadata for this template.
	*/	
		if ( $this->md_description ) {
			return $this->md_description; // caching
		}
	
		if ( ! $this->template_file ) {
			if ( ! $this->css_path() ) {
				return false;
			}
			$this->template_file = @file_get_contents( $this->css_path() );
		}
		
		// extract the template name
		$matches = array();
		preg_match( '/^\s*Description:\s*(.*)/im', $this->template_file, $matches );

		if ( $matches && count( $matches ) > 1 ) {
			$this->md_description = $matches[1];
			return $this->md_description;
		}
		else {
			return false;			
		}		
		
	}
	
	public function version() {
	/*
		Return the version number for this template.
	*/	
		if ( $this->md_version ) {
			return $this->md_version; // caching
		}
	
		if ( ! $this->template_file ) {
			if ( ! $this->css_path() ) {
				return false;
			}
			$this->template_file = @file_get_contents( $this->css_path() );
		}
		
		// extract the template name
		$matches = array();
		preg_match( '/^\s*Version:\s*(.*)/im', $this->template_file, $matches );

		if ( $matches && count( $matches ) > 1 ) {
			$this->md_version = $matches[1];
			return $this->md_version;
		}
		else {
			return false;			
		}		
		
	}
	
	public function author() {
	/*
		Return the author name for this template.
	*/	
		if ( $this->md_author ) {
			return $this->md_author; // caching
		}
	
		if ( ! $this->template_file ) {
			if ( ! $this->css_path() ) {
				return false;
			}
			$this->template_file = @file_get_contents( $this->css_path() );
		}
		
		// extract the template name
		$matches = array();
		preg_match( '/^\s*Author:\s*(.*)/im', $this->template_file, $matches );

		if ( $matches && count( $matches ) > 1) {
			$this->md_author = $matches[1];
			return $this->md_author;
		}
		else {
			return false;			
		}		
		
	}
	
	public function author_uri() {
	/*
		Return the author URI for this template.
	*/	
		if ( $this->md_author_uri ) {
			return $this->md_author_uri; // caching
		}
	
		if ( ! $this->template_file ) {
			if ( ! $this->css_path() ) {
				return false;
			}
			$this->template_file = @file_get_contents( $this->css_path() );
		}
		
		// extract the template name
		$matches = array();
		preg_match( '/^\s*Author\s*URI:\s*(.*)/im', $this->template_file, $matches );

		if ( $matches && count( $matches ) > 1 ) {
			$this->md_author_uri = $matches[1];
			return $this->md_author_uri;
		}
		else {
			return false;			
		}		
		
	}
	
	public function license() {
	/*
		Return the license metadata for this template.
	*/	
		if ( $this->md_license ) {
			return $this->md_license; // caching
		}
	
		if ( ! $this->template_file ) {
			if ( ! $this->css_path() ) {
				return false;
			}
			$this->template_file = @file_get_contents( $this->css_path() );
		}
		
		// extract the template name
		$matches = array();
		preg_match( '/^\s*License:\s*(.*)/im', $this->template_file, $matches );

		if ( $matches && count( $matches ) > 1 ) {
			$this->md_license = $matches[1];
			return $this->md_license;
		}
		else {
			return false;			
		}
		
	}
	
	public function license_uri() {
	/*
		Return the license URI for this template.
	*/	
		if ( $this->md_license_uri ) {
			return $this->md_license_uri; // caching
		}
	
		if ( ! $this->template_file ) {
			if ( ! $this->css_path() ) {
				return false;
			}
			$this->template_file = @file_get_contents( $this->css_path() );
		}
		
		// extract the template name
		$matches = array();
		preg_match( '/^\s*License\s*URI:\s*(.*)/im', $this->template_file, $matches );

		if ( $matches && count( $matches ) > 1 ) {
			$this->md_license_uri = $matches[1];
			return $this->md_license_uri;
		}
		else {
			return false;			
		}
		
	}
	
	public function tags() {
	/*
		Return the license URI for this template.
	*/	
		if ( $this->md_tags ) {
			return $this->md_tags; // caching
		}
	
		if ( ! $this->template_file ) {
			if ( ! $this->css_path() ) {
				return false;
			}
			$this->template_file = @file_get_contents( $this->css_path() );
		}
		
		// extract the template name
		$matches = array();
		preg_match( '/^\s*Tags:\s*(.*)/im', $this->template_file, $matches );

		if ( $matches && count( $matches ) > 1 ) {
			$this->md_tags = $matches[1];
			$this->md_tags = explode(',', $this->md_tags);
			
			return $this->md_tags;
		}
		else {
			return false;			
		}
		
	}
	
	public function determine_options() {
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

		if ( isset( $this->options ) && is_array( $this->options ) && count( $this->options ) > 0 ) {
			// cache results
			return $this->options;
		}
		
		if ( ! $this->template_php_file ) {
			if ( ! $this->php_path() ) {
				return false;
			}
			$this->template_php_file = @file_get_contents( $this->php_path() );
		}

		if ( $this->template_php_file !== false ) {
			// look for Crop-Suggested-Width: xx directive
			$matches = array();
			preg_match( '/^\s*Crop\-Suggested\-Width:\s*([0-9]+)/im', $this->template_php_file, $matches );
			if ( 2 == count( $matches ) ) {
				if ( intval( $matches[1] ) == $matches[1] ) {
					$crop_width = intval( $matches[1] );
				}
				else {
					$crop_width = TOTAL_SLIDER_DEFAULT_CROP_WIDTH;
				}
			}
			else {
				$crop_width = TOTAL_SLIDER_DEFAULT_CROP_WIDTH;
			}

			// look for Crop-Suggested-Height: xx directive
			$matches = array();
			preg_match( '/^\s*Crop\-Suggested\-Height:\s*([0-9]+)/im', $this->template_php_file, $matches );
			if ( 2 == count( $matches ) ) {
				if ( intval( $matches[1] ) == $matches[1] ) {
					$crop_height = intval( $matches[1] );
				}
				else {
					$crop_height = TOTAL_SLIDER_DEFAULT_CROP_HEIGHT;
				}
			}
			else {
				$crop_height = TOTAL_SLIDER_DEFAULT_CROP_HEIGHT;
			}

			// look for Disable-XY-Positioning-In-Admin directive
			$matches = array();
			preg_match( '/^\s*Disable\-XY\-Positioning\-In\-Admin:\s*(Yes|No|On|Off|1|0|True|False)/im', $this->template_php_file, $matches );
			$affirmative_responses = array( 'yes', 'on', '1', 'true' );
			//$negative_responses = array( 'no', 'off', '0', 'false' );

			if ( 2 == count( $matches ) ) {
				if ( in_array( strtolower( $matches[1] ), $affirmative_responses ) ) {
					$disable_xy = true;
				}
				else {
					$disable_xy = false;
				}
			}
			else {
				$disable_xy = false;
			}

		}
		else {
			$crop_width = TOTAL_SLIDER_DEFAULT_CROP_WIDTH;
			$crop_height = TOTAL_SLIDER_DEFAULT_CROP_HEIGHT;
			$disable_xy = false;
		}

		// cache results in global $templateOptions
		$this->options = array( 'crop_width' => $crop_width, 'crop_height' => $crop_height, 'disable_xy' => $disable_xy );
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

	public function slides_count() {
	/*
		Return the number of slides in this slide group.

		Can also be used by templates to test if there are any slides to show at all,
		and, for example, not output the starting <ul>.
	*/

		return 1;

	}
	
	public function is_runtime() {
	/*
		Allows the template to be aware of whether it is running at runtime (viewing as part of the
		actual site): 'true', or at edit-time (the user is editing slides in the admin interface, and
		the template is executing as a preview): 'false'.
	*/
	
		return false;
		
	}


	public function has_slides() {
	/*
		For our purposes, we want the slide previewer to load the template for one slide only.
	*/
	
		++$this->counter;
		
		if ($this->counter > 1)
			return false;
		else
			return true;


	}

	public function the_title() {
	/*
		Print the slide title token to output.
	*/

		echo $this->get_the_title();

	}

	public function get_the_title() {
	/*
		Return the slide title token.
	*/

		return '[%= title %]';

	}

	public function the_description() {
	/*
		Print the slide description token.
	*/

		echo $this->get_the_description();

	}

	public function get_the_description() {
	/*
		Return the slide description token.
	*/

		return '[%= description %]';

	}

	public function the_background_url() {
	/*
		Print the background URL token.
	*/

		echo $this->get_the_background_url();

	}

	public function get_the_background_url() {
	/*
		Return the background URL token.
	*/

		return '[%= background_url %]'; // use other quote style -- likely in a url() CSS block

	}

	public function the_link() {
	/*
		Print the slide link token.
	*/

		echo $this->get_the_link();

	}

	public function get_the_link() {
	/*
		Return the slide link token.
	*/

		return '[%= link %]';

	}

	public function the_x() {
	/*
		Print the X coordinate token.
	*/

		echo $this->get_the_x();

	}

	public function get_the_x() {
	/*
		Return the X coordinate token.
	*/

		return '[%= x %]';

	}

	public function the_y() {
	/*
		Print the Y coordinate token.
	*/

		echo $this->get_the_y();

	}

	public function get_the_y() {
	/*
		Return the Y coordinate token.
	*/

		return '[%= y %]';

	}

	public function the_identifier() {
	/*
		Print the slide identifier token.
	*/

		echo $this->get_the_identifier();

	}

	public function get_the_identifier() {
	/*
		Return the slide identifier token.
	*/

		return '[%= identifier %]';

	}

	public function iteration() {
	/*
		Return the iteration number. How many slides have we been through?
	*/

		return intval ( $this->counter - 1 );
		// has_slides() always bumps the iteration ready for the next run, but we
		// are still running, for the theme's purposes, on the previous iteration.
		// Hence, returning the iteration - 1.

	}
	
	public function make_draggable() {
	/*
		Outputs a class that in edit-time mode makes the object draggable (for X/Y positioning
		of the title/description overlay).
	
		Should be called when inside a DOM object's 'class' attribute.
		
		Does nothing at runtime.
	*/
		
		echo 'total-slider-template-draggable';
		
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

	public function discover_templates($location, $should_parse_name = true) {
	/*
		Discovers the template files that are available in the given location (one of 'builtin',
		'theme', 'downloaded', 'legacy'.
		
		Returns an array of the template slugs and names, which can be used for further inspection by
		instantiating the Total_Slider_Template class with the slug and location.
	*/
		global $allowed_template_locations;
		
		if ( ! is_array( $allowed_template_locations ) ) {
			throw new UnexpectedValueException( __( 'The allowed template locations are not available. This file must not be loaded without class.total-slide-group.php', 'total_slider' ), 103 );
			return false;
		}
		
		// check the location given is valid	
		if ( ! in_array( $location, $allowed_template_locations ) ) {
			throw new UnexpectedValueException( __( 'The supplied template location is not one of the allowed template locations', 'total_slider' ), 101 );
			return false;
		}
		
		// what path(s) should we walk?
		$paths = array();
		
		$css_name = 'style.css';
		
		switch ( $location ) {
			
			case 'builtin':
				$paths[] = plugin_dir_path( dirname(__FILE__) ) . '/' . TOTAL_SLIDER_TEMPLATES_BUILTIN_DIR . '/';
			break;
			
			case 'theme':
				$paths[] = get_stylesheet_directory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
				if ( get_stylesheet_directory() != get_template_directory() )
					$paths[] = get_template_directory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR .'/';				
			break;
			
			case 'downloaded':
				if ( ! defined('WP_CONTENT_DIR') ) {
					throw new UnexpectedValueException(__( 'Unable to determine the WP_CONTENT_DIR, so cannot find relevant templates.', 'total_slider' ), 102 );
					return false;					
				}
				
				// in the absence of content_dir() existing, we must use the WP_CONTENT_DIR constant. Sorry!
				$paths[] = WP_CONTENT_DIR . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
			break;
			
			case 'legacy':
				$path = get_stylesheet_directory() . '/' . TOTAL_SLIDER_TEMPLATES_DIR . '/';
				
				if ( ! @file_exists( $path ) || ! @is_dir( $path) ) {
					return false;
				}
				
				$files = @scandir( $path );
				
				if ( ! $files )	{
					return false;
				}
				
				foreach( $files as $f ) {
					$templates = array();
					$i = 0;
					
					if ( 'total-slider-template.php' == $f ) {
						$templates[$i]['slug'] = Total_Slider_Template::sanitize_slug(basename($f));
						$templates[$i]['name'] = __( 'v1.0 Custom Template', 'total_slider' );
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
		foreach( $paths as $key => $path ) {
			if ( ! @file_exists( $path ) || ! @is_dir( $path ) ) {
				continue;
			}
			
			$files = @scandir( $path );
			
			if ( ! $files )	{
				continue;
			}
			
			foreach( $files as $f ) {
			
				if ( '.' == $f || '..' == $f )
					continue;
			
				if ( @is_dir( $path . '/' . $f ) ) {			
					if ( @file_exists( $path . '/' . $f . '/' . $css_name ) ) {
					
						if ( $should_parse_name ) {
					
							$tpl_content = @file_get_contents( $path . '/' . $f . '/' . $css_name );
						
							// extract the template name
							$matches = array();
							preg_match( '/^\s*Template\sName:\s*(.*)/im', $tpl_content, $matches );
							
							unset($tpl_content);
							
							$templates[$i]['slug'] = Total_Slider_Template::sanitize_slug( basename( $f ) );
							
							if ( $matches && count( $matches ) > 1 ) {
								$templates[$i]['name'] = $matches[1];
							}
							
							++$i;
							
						}
						else {
						
							$templates[$i]['slug'] = Total_Slider_Template::sanitize_slug( basename( $f ) );
							++$i;

						}
						
					}
				}
			}
			
		}
		
		return $templates;		
		
	}
	
	
};

<?php
/*
 * Template Manager
 *
 * Handles the determination of canonical template URIs and paths, to allow inclusion and enqueue
 * into the front- and backend. It also renders templates at editing time.
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

/**
 * Class: The class that represents a Total Slider template. Includes methods for canonicalizing paths, and rendering.
 *
 */
class Total_Slider_Template {
	
	/**
	 * This template's slug. Should correspond to its folder name.
	 *
	 * @var string
	 */
	private $slug;


	/**
	 * The template's location. One of 'builtin','theme','downloaded'
	 *
	 * @var string
	 */
	private $location; // one of 'builtin','theme','downloaded'
	
	/**
	 * This template's metadata name -- the friendly name of the template.
	 *
	 * @var string
	 */
	private $md_name;

	/**
	 * The metadata URI -- for example, the template author's home page.
	 *
	 * @var string
	 */
	private $md_uri;

	/**
	 * The metadata description -- information about the template's style and purpose.
	 *
	 * @var string
	 */
	private $md_description;


	/**
	 * The version number of the template.
	 *
	 * @var string
	 */
	private $md_version;

	/**
	 * The template's author.
	 *
	 * @var string
	 */
	private $md_author;
	
	/**
	 * The template author's personal page, for example.
	 *
	 * @var string
	 */
	private $md_author_uri;

	/**
	 * The license under which the template is made available.
	 *
	 * @var string
	 */

	private $md_license;
	
	/**
	 * The URI to the template's license document.
	 *
	 * @var string
	 */
	private $md_license_uri;

	/**
	 * Any metadata tags the template wishes to be associated with.
	 *
	 * @var string
	 */
	private $md_tags;
	
	/**
	 * The template's options -- an array of options, for example default crop width and crop height.
	 *
	 * @var array
	 */
	private $options;
	
	/**
	 * String containing the contents of this template's CSS file.
	 *
	 * @var string
	 */
	private $template_file = null;

	/**
	 * String containing the contents of this template's PHP file.
	 *
	 * @var string
	 */
	private $template_php_file = null;
	
	/**
	 * Determined from the template location and slug, the path to this template's files.
	 *
	 * @var string
	 */
	private $path_prefix = null;

	/**
	 * Determined from the template location and slug, the URI base path to this template's files.
	 *
	 * @var string
	 */
	private $uri_prefix = null;
	
	/**
	 * The full file path to this template's PHP file.
	 *
	 * @var string
	 */
	private $php_path = null;

	/**
	 * The full file path to this template's JavaScript file.
	 *
	 * @var string
	 */
	private $js_path = null;

	/**
	 * The full file path to this template's minified JavaScript file.
	 *
	 * @var string
	 */
	private $js_min_path = null;

	/**
	 * The full file path to this template's CSS file.
	 *
	 * @var string
	 */
	private $css_path = null;
	
	/**
	 * The fully qualified URI to this template's PHP file.
	 *
	 * @var string
	 */
	private $php_uri = null;

	/**
	 * The fully qualified URI to this template's JavaScript file.
	 *
	 * @var string
	 */
	private $js_uri = null;

	/**
	 * The fully qualified URI to this template's minified JavaScript file.
	 *
	 * @var string
	 */
	private $js_min_uri = null;

	/**
	 * The fully qualified URI to this template's CSS file.
	 *
	 * @var string
	 */
	private $css_uri = null;
	
	/**
	 * Prepare this Template object.
	 *
	 * Check for existence of prerequisite files, prepare to be asked about this template's canonical URIs and paths
	 * and, if required, be ready to lazy load metadata from the template files. Also prepares
	 * for rendering of the template's JavaScript, which is done at editing time.
	 *
	 * @param string $slug
	 * @param string $location One of 'builtin','theme','downloaded'
	 * @return void
	 */
	public function __construct( $slug, $location ) {

		if ( ! is_array(Total_Slider::$allowed_template_locations ) ) {
			throw new UnexpectedValueException( __( 'The allowed template locations are not available. This file must not be loaded without class.total-slide-group.php', 'total-slider' ), 103 );
			return;
		}
	
		// get some key things ready
		$this->slug = $this->sanitize_slug( $slug );
		
		if ( in_array( $location, Total_Slider::$allowed_template_locations ) ) {
			$this->location = $location;
		}
		else {
			throw new UnexpectedValueException( __( 'The supplied template location is not one of the allowed template locations', 'total-slider' ), 101 );
			return;
		}
		
		// we will load canonicalised paths and urls, and check for existence, but be lazy about metadata
		$this->canonicalize();
		
	}
	
	/**
	 * Sanitize a template slug before we assign it internally, or print it anywhere.
	 *
	 * A template slug must be fewer than 128 characters in length, unique, and consist only of
	 * a-z, A-Z, 0-9, _, -.
	 * The slug is used as the directory name for the template, as well as the base name for its
	 * standard PHP, CSS and JavaScript files.
	 *
	 * @param string $slug
	 */
	public static function sanitize_slug($slug) {

		return substr( preg_replace( '/[^a-zA-Z0-9_\-]/', '', $slug ), 0, 128 );
		
	}
	
	/**
	 * Construct canonical paths and URIs for this template's files, using the template slug and location.
	 *
	 * This will check that these canonicalized paths refer to paths that exist, so we are confident and
	 * ready to enqueue necessary files in the front- or backend.
	 * Will throw documented exceptions upon failure, which are caught and pretty-displayed in the admin UI if
	 * a template is broken because of invalid paths.
	 *
	 * @return boolean
	 *
	 */
	private function canonicalize() {

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
						sprintf( __( "The template's %s file was not found, but we expected to find it at '%s'.", 'total-slider' ), $missing_file, $expected_location )
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
						sprintf( __( "The template's %s file was not found, but we expected to find it at '%s'.", 'total-slider'), $missing_file, $expected_location)
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
					throw new UnexpectedValueException(__('Unable to determine the WP_CONTENT_DIR, so cannot load this template.', 'total-slider'), 102);
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
						sprintf( __( "The template's %s file was not found, but we expected to find it at '%s'.", 'total-slider' ), $missing_file, $expected_location )
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
						sprintf( __( "The template's %s file was not found, but we expected to find it at '%s'.", 'total-slider'), $missing_file, $expected_location)
					, 201 );
					return false;
				}					
			
			break;
			
			default:
				throw new UnexpectedValueException( __( 'The supplied template location is not one of the allowed template locations', 'total-slider' ), 101 );
				return false;				
			break;
			
			
		}
		
	}
	
	/**
	 * Render this template, using the pseudo-widget class in this file for displaying in the admin UI.
	 *
	 * Calls to the WP_Widget public methods actually produce EJS placeholder text, so that the JavaScript
	 * in the admin UI knows where to place user input at runtime.
	 *
	 * @return string
	 *
	 */
	public function render() {

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
				sprintf( __( "The template's %s file was not found, but we expected to find it at '%s'.", 'total-slider' ), 'PHP', $this->php_path )
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
	
	/**
	 * Return the canonical path for this template.
	 *
	 * return @string
	 *
	 */
	public function path_prefix() {
	
		if ( ! $this->path_prefix ) {
			$this->canonicalize();
		}
		
		return $this->path_prefix;
		
	}

	/**
	 * Return the canonical URI for this template.
	 *
	 * return @string
	 *
	 */
	
	public function uri_prefix() {

		if ( ! $this->uri_prefix ) {
			$this->canonicalize();
		}
		
		return $this->uri_prefix;
		
	}
	
	/**
	 * Return the canonical path to this template's PHP file.
	 *
	 * return @string
	 *
	 */
	public function php_path() {
	
		if ( ! $this->php_path ) {
			$this->canonicalize();
		}
		
		return $this->php_path;
		
	}
	
	public function js_path() {
	/**
	 * Return the canonical path to this template's JavaScript file.
	 *
	 * return @string
	 *
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
	/**
	 * Return the canonical path to this template's minifed JavaScript file.
	 *
	 * return @string
	 *
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
	/**
	 * Return the canonical path to this template's CSS file.
	 *
	 * return @string
	 *
	 */
		if ( ! $this->css_path ) {
			$this->canonicalize();
		}
		
		return $this->css_path;		
	}
	
	/**
	 * Return the canonical URI for this template's PHP file.
	 *
	 * return @string
	 *
	 */
	public function php_uri() {
		if ( ! $this->php_uri ) {
			$this->canonicalize();
		}
		
		return $this->php_uri;	
		
	}

	/**
	 * Return the canonical URI for this template's JavaScript file.
	 *
	 * return @string
	 *
	 */

	public function js_uri() {
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
	/**
	 * Return the canonical URI for this template's minified JavaScript file.
	 *
	 * return @string
	 *
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
	
	/**
	 * Return the canonical URI for this template's CSS file.
	 *
	 * return @string
	 *
	 */
	public function css_uri() {
		if ( ! $this->css_uri ) {
			$this->canonicalize();
		}
		
		return $this->css_uri;	
		
	}
	
	/***********	// !Metadata accessor methods		***********/
	
	/**
	 * Return the friendly name for this template.
	 *
	 * return @string
	 *
	 */
	public function name() {

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
				return __( 'v1.0 Custom Template', 'total-slider' );
			}
			else {
				return $this->slug;
			}
		}
		
	}
	
	/**
	 * Return the Template URI metadata for this template.
	 *
	 * @return string
	 *
	 */
	public function uri() {
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

	/**
	 * Return the Template description metadata for this template.
	 *
	 * @return string
	 *
	 */
	public function description() {
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

	/**
	 * Return the version number for this template.
	 *
	 * @return string
	 *
	 */
	public function version() {
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
	
	/**
	 * Return the author name for this Template.
	 *
	 * return @string
	 *
	 */
	public function author() {
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
	
	/**
	 * Return the template's metadata author URI.
	 *
	 * @return string
	 *
	 */
	public function author_uri() {
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
	
	/**
	 * Return the license for this Template.
	 *
	 * @return string
	 *
	 */
	public function license() {
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
	
	/**
	 * Return the license URI for this Template.
	 *
	 * @return string
	 *
	 */
	public function license_uri() {
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
	
	/**
	 * Return the metadata tags for this Template.
	 *
	 * @return array
	 *
	 */
	public function tags() {
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
	
	/**
	 * Determine the desired crop height and crop width for the background image, and other template options.
	 *
	 * Pulls these from an options statement in the Template's CSS file.
	 *
	 * return @array
	 *
	 */
	public function determine_options() {
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


/**
 * Class: A 'dummy' class that behaves like Total_Slider_Widget, but used for rendering the editing time interface of this template.
 *
 * The template's $s calls are rendered as EJS-friendly tokens, so that the editing interface JavaScript canm alter
 * the template's placeholder data in real-time.
 *
 */
class Total_Slider_Widget_Templater
{

	/**
	 * A 'dummy' counter used to force the number of slides to display to 1 in has_slides().
	 *
	 * @var integer
	 */
	private $counter = 0;

	//NOTE: the FE format for these tokens is not finalised and is placeholder only

	/**
	 * Return the number of slides. Hard-coded to 1 for edit-time purposes.
	 *
	 * @return integer
	 *
	 */
	public function slides_count() {
		return 1;
	}
	
	/**
	 * Allows the template to be aware of whether it is running at runtime, or editing time. Hard-coded to false (i.e. "editing time") here.
	 * 
	 * @return boolean
	 *
	 */
	public function is_runtime() {

		return false;
		
	}


	/**
	 * The template is made aware if any slides exist in the Slide Group. For editing time purposes, we will return true.
	 *
	 * @return boolean
	 *
	 */
	public function has_slides() {

		++$this->counter;
		
		if ($this->counter > 1)
			return false;
		else
			return true;


	}

	/**
	 * Print the slide title token.
	 *
	 * @return void
	 *
	 */
	public function the_title() {
		echo $this->get_the_title();
	}

	/**
	 * Return the slide title token.
	 *
	 * @return string
	 *
	 */
	public function get_the_title() {
		return '[%= title %]';

	}

	/**
	 * Print the slide description token.
	 *
	 * @return void
	 *
	 */
	public function the_description() {

		echo $this->get_the_description();

	}

	/**
	 * Return the slide description token.
	 *
	 * @return string
	 *
	 */
	public function get_the_description() {
		return '[%= description %]';
	}

	/**
	 * Print the background URL token.
	 * 
	 * @return void
	 *
	 */
	public function the_background_url() {
		echo $this->get_the_background_url();
	}

	/**
	 * Return the background URL token.
	 *
	 * @return string
	 *
	 */
	public function get_the_background_url() {

		return '[%= background_url %]'; // use other quote style -- likely in a url() CSS block

	}

	/**
	 * Print the slide link token.
	 *
	 * @return void
	 *
	 */
	public function the_link() {

		echo $this->get_the_link();

	}

	/**
	 * Return the slide link token.
	 *
	 * @return string
	 *
	 */
	public function get_the_link() {

		return '[%= link %]';

	}

	/**
	 * Print the X coordinate token.
	 *
	 * @return void
	 *
	 */
	public function the_x() {

		echo $this->get_the_x();

	}

	/**
	 * Return the X coordinate token.
	 *
	 * @return string
	 *
	 */
	public function get_the_x() {
		return '[%= x %]';

	}

	/**
	 * Print the Y coordinate token.
	 *
	 * @return void
	 *
	 */
	public function the_y() {

		echo $this->get_the_y();

	}

	/**
	 * Return the Y coordinate token.
	 *
	 * @return string
	 *
	 */
	public function get_the_y() {

		return '[%= y %]';

	}

	/**
	 * Print the slide identifier token.
	 *
	 * @return void
	 *
	 */
	public function the_identifier() {
		echo $this->get_the_identifier();

	}

	/**
	 * Return the slide identifier token.
	 *
	 * @return string
	 *
	 */
	public function get_the_identifier() {

		return '[%= identifier %]';

	}

	/**
	 * Return the iteration number. Always '1'.
	 *
	 * @return int
	 *
	 */
	public function iteration() {
		return intval ( $this->counter - 1 );
		// has_slides() always bumps the iteration ready for the next run, but we
		// are still running, for the theme's purposes, on the previous iteration.
		// Hence, returning the iteration - 1.

	}
	
	/**
	 * Print a CSS class that, at editing time, allows the object to be made draggable for X/Y positioning.
	 *
	 * Should be called when inside a DOM object's 'class' attribute. Does nothing at runtime.
	 *
	 * @return void
	 *
	 */
	public function make_draggable() {
	
		echo 'total-slider-template-draggable';
		
	}	
	
	/**
	 * Print a CSS class that, at editing time, makes the object the draggable's parent. This parent is used for X/Y offset calculation.
	 *
	 * This element will also be used as the containment for the draggable object, i.e. the box cannot be dragged
	 * outside of the element marked with this class.
	 * Should be called when inside a DOM object's 'class' attribute. Does nothing at runtime
	 *
	 * @return void
	 *
	 */
	public function draggable_parent() {
	
		echo 'total-slider-template-draggable-parent';
		
	}


};

/**
 * Class: Hunts through any of the builtin, theme (stylesheet_ and template_) and downloaded (wp-content) directories to find templates.
 *
 * It will do crude checking for file existence and grab the template name, but more detailed inspection and metadata extraction
 * is left to Total_Slider_Template.
 *
 */
class Total_Slider_Template_Iterator {

	/**
	 * Discover templates that are available in the specified location.
	 *
	 * Returns an array of the template slugs and names, which can be used for further inspection by
	 * instantiating a Total_Slider_Template class with the returned slug and supplied template location.
	 *
	 * @param string $location One of 'builtin','theme','downloaded','legacy'.
	 * @param boolean $should_parse_name Set to false to avoid the overhead of parsing template names.
	 * @return array
	 */
	public function discover_templates($location, $should_parse_name = true) {
		
		if ( ! is_array( Total_Slider::$allowed_template_locations ) ) {
			throw new UnexpectedValueException( __( 'The allowed template locations are not available. This file must not be loaded without class.total-slide-group.php', 'total-slider' ), 103 );
			return false;
		}
		
		// check the location given is valid	
		if ( ! in_array( $location, Total_Slider::$allowed_template_locations ) ) {
			throw new UnexpectedValueException( __( 'The supplied template location is not one of the allowed template locations', 'total-slider' ), 101 );
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
					throw new UnexpectedValueException(__( 'Unable to determine the WP_CONTENT_DIR, so cannot find relevant templates.', 'total-slider' ), 102 );
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
						$templates[$i]['name'] = __( 'v1.0 Custom Template', 'total-slider' );
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

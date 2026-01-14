<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library provides functions to load and instantiate controllers
 * and module controllers allowing use of modules and the HMVC design pattern.
 *
 * Install this file as application/third_party/MX/Modules.php
 *
 * @copyright	Copyright (c) 2015 Wiredesignz
 * @version 	5.5
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 **/
class Modules
{
	public static $registry;
	public static $routes;
	
	/**
	* Run a module controller method
	* Output from module is buffered and returned.
	**/
	public static function run($module) 
	{
		$method = 'index';
		
		if(($pos = strrpos($module, '/')) !== FALSE) 
		{
			$method = substr($module, $pos + 1);		
			$module = substr($module, 0, $pos);
		}

		if($class = self::load($module)) 
		{
			if (method_exists($class, $method))	
			{
				ob_start();
				$args = func_get_args();
				$output = call_user_func_array(array($class, $method), array_slice($args, 1));
				$buffer = ob_get_clean();
				return ($output !== NULL) ? $output : $buffer;
			}
		}
		
		log_message('error', "Module controller failed to run: {$module}/{$method}");
	}
	
	/** Load a module controller **/
	public static function load($module) 
	{
		(is_array($module)) ? list($module, $params) = each($module) : $params = NULL;	
		
		/* get the requested controller */
		$alias = strtolower(basename($module));
		$controller = ucfirst($alias);
		
		/* module already loaded */
		if (isset(self::$registry[$alias])) 
			return self::$registry[$alias];
		
		/* get the requested controller */
		list($class) = CI::$APP->router->locate(explode('/', $module));
		
		$dir = APPPATH.'modules/';
		
		if($location = self::find($controller.'.php', $module, 'controllers/')) 
		{
			/* module exists */
			if ( ! class_exists('CI_Controller', FALSE))
			{
				load_class('Controller', 'core');
			}
			
			/* load custom core controllers */
			if(file_exists(APPPATH.'core/MY_Controller.php'))
			{
				require_once APPPATH.'core/MY_Controller.php';
			}
			
			if($class !== $controller)
			{
				show_404("{$module}/{$controller}");
			}
			
			/* load the controller file */
			if ( ! class_exists($class, FALSE))
			{
				require_once $location;
			}
			
			/* create and store module controller instance */
			return self::$registry[$alias] = new $class($params);
		}
		
		/* module not found */
		return FALSE;
	}
	
	/** Find a module file **/
	public static function find($file, $module, $base) 
	{
		$segments = explode('/', $module);
		$file_ext = (pathinfo($file, PATHINFO_EXTENSION)) ? $file : $file.EXT;
		
		while($segments) 
		{
			$path = implode('/', $segments).'/';
			$file_location = $path.$base.$file_ext;
			
			if($location = self::locate($file_location)) 
			{
				return $location;
			}
			
			array_pop($segments);
		}
		
		return self::locate($file_location);
	}

	/** Locate a file **/
	private static function locate($file)
	{
		foreach (array(APPPATH, BASEPATH) as $path)
		{
			$module_file = $path.'modules/'.$file;
			
			if (file_exists($module_file)) return $module_file;
		}
	}
	
	/** Parse module routes **/
	public static function parse_routes($module, $uri) 
	{	
		/* load the route file */
		if ( ! isset(self::$routes[$module])) 
		{
			if (list($path) = self::find('routes.php', $module, 'config/') AND $path) 
				self::$routes[$module] = self::load_file('routes', $path, 'route');
		}

		if ( ! isset(self::$routes[$module])) return;
			
		/* parse module routes */
		foreach (self::$routes[$module] as $key => $val) 
		{						
			$key = str_replace(array(':any', ':num'), array('.+', '[0-9]+'), $key);
			
			if (preg_match('#^'.$key.'$#', $uri)) 
			{							
				if (strpos($val, '$') !== FALSE AND strpos($key, '(') !== FALSE) 
				{
					$val = preg_replace('#^'.$key.'$#', $val, $uri);
				}
				return explode('/', $module.'/'.$val);
			}
		}
	}
	
	/** Load a module config file **/
	public static function load_file($file, $path, $type = 'other', $result = TRUE)	
	{
		$file = str_replace(EXT, '', $file);		
		$location = $path.$file.EXT;
		
		if ($type === 'other') 
		{
			if (class_exists('CI_Config', FALSE)) 
			{
				$config =& CI::$APP->config;
			} 
			else 
			{
				$config = load_class('Config', 'core');
			}
			
			return $config->load($location, TRUE, TRUE);
		} 
		
		if ( ! file_exists($location)) 
			return $result;
		
		if ($type != 'other') 
			include $location;
		
		if ( ! isset($$type) OR ! is_array($$type))				
			show_error("{$location} does not contain a valid {$type} array");

		$result = $$type;
		return $result;
	}
}

/** Define the CI class for PHP5 **/
class CI
{
	public static $APP;
}

/* End of file Modules.php */
/* Location: ./application/third_party/MX/Modules.php */

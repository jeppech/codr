<?php

	if (!function_exists('load_config'))
	{
		function &load_config()
		{
			static $_config;

			if (isset($_config))
			{
				return $_config;
			}

			$file_path = APPPATH.'config/'.ENVIRONMENT.'/config.php';

			if (!file_exists($file_path))
			{
				exit('The config file was not found. Please correct this at: '.APPPATH.'config/'.ENVIRONMENT.'/');
			}

			require $file_path;

			if (!isset($config) || !is_array($config))
			{
				exit('The config file is not configured properly. Please correct this at: '.APPPATH.'config/'.ENVIRONMENT.'/');
			}

			return $_config =& $config;
		}
	}

/**
 * Load class (Register classes)
 *
 * @access	public
 * @param	string	Requested class name
 * @param	string	Directory where the class can be found
 * @param	string	Class name prefix
 * @return	object
 */

	if (!function_exists('load_class'))
	{
		function &load_class($class, $directory = 'library', $prefix = 'CODR_')
		{
			static $_classes = array();

			if (isset($_classes[$class]))
			{
				return $_classes[$class];
			}

			$name = FALSE;

			/**
			 * --------
			 * Search for the class, first in the application folder
			 * then in the system folder
			 * --------
			 */

			foreach (array(APPPATH,BASEPATH) as $path)
			{
				if (file_exists($path.$directory.'/'.$class.'.php'))
				{
					$name = (($prefix == '') || (!$prefix) ? $class : $prefix.$class);

					if (class_exists($name) === FALSE)
					{
						require $path.$directory.'/'.$class.'.php';
					}

					break;
				}
			}

			if ($name === FALSE)
			{
				exit('Unable to load the requested class: '.$class).'.php';
			}

			// Keep track of the loaded classes.
			is_loaded($class);

			$_classes[$class] = new $name();
			return $_classes[$class];
		}
	}

/**
 * Is loaded (Class register)
 * Keeps track of which classes that has been loaded
 *
 * @access public
 * @param string Class name (Optional)
 * @return array
 */
	if (!function_exists('is_loaded'))
	{
		function &is_loaded($class = '')
		{
			static $_is_loaded = array();

			if ($class != '')
			{
				$_is_loaded[strtolower($class)] = $class;
			}

			return $_is_loaded;
		}
	}


/**
 * Show error (Error view)
 * Outputs a giving error, and exits
 *
 * @access public
 * @param string Header
 * @param string Message
 * @param string Error template
 * @param int Error status code
 * @return string
 */

	if (!function_exists('show_error'))
	{
		function show_error($heading = 'PHP error', $message = 'An unknown error has occured')
		{
			$_error =& load_class('Error','core');
			echo $_error->show_error($heading,$message);
			exit;
		}
	}

/**
 * Show 404 (Missing page)
 * Outputs a default 404 page, commonly
 * used if a 404_override wasn't detected.
 */

	if (!function_exists('show_404'))
	{
		function show_404($page)
		{
			$_error =& load_class('Error','core');

			echo $_error->show_404($page);
			exit;
		}
	}

	if (!function_exists('log_message'))
	{
		function log_message($level,$message)
		{
			$_log =& load_class('Log','core');
			$_log->log_message($level,$message);
		}
	}

	if (!function_exists('toObject'))
	{
		function toObject($array)
		{
			if (is_array($array))
			{
				(object) $_obj;

				foreach ($array as $key  => $value)
				{
					if (!is_array($value))
					{
						$_obj->{$key} = $value;
					} else {
						$_obj->{$key} = toObject($value);
					}
				}
				return $_obj;
			}
		}
	}
/**
 * Print formatted ouput
 */
	if (!function_exists('dd'))
	{
		function dd($data)
		{
			echo "<pre>";
			print_r($data);
			echo "</pre>";
		}
	}

/**
 * no = Nice Output
 * Adds PHP_EOL to the end of string,
 * usually used  in view files
 * to produce nice source output.
 */
	if (!function_exists('no'))
	{
		function no($string)
		{
			return $string.PHP_EOL;
		}
	}
/**
 * Translation
 */
	if (!function_exists('t'))
	{
		function t($string, $args = array(), $decode = false, $editable = true)
		{
			$_locale = load_class("Locale","core");
			return $_locale->t($string, $args, $decode, $editable);
		}
	}

/**
 * Same as nl2br but REPLACES \r\n instead 
 */
	if (!function_exists('nl2brBB'))
	{
		function nl2brBB($string)
		{
			return str_replace(array("\r\n","\r","\n"), "[BR]", $string);
		}
	}
?>

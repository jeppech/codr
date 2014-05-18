<?php
	// @TODO Secure session?
	// Ferncer
	session_start();
/**
 * --------
 * Load global functions
 * --------
 */

	require BASEPATH."core/Global.php";

/**
 * --------
 * Load global constants.
 * --------
 */

	if (file_exists(APPPATH."config/constants.php"))
	{
		require_once APPPATH."config/constants.php";
	}
/**
 * --------
 * Instantiate Profiler class
 * --------
 */

	$profiler =& load_class("Profiler","core");
	$profiler->mark("execution_start");
/**
 * --------
 * Instantiate the Config class.
 * --------
 */

	$config =& load_class("Config","core");

/**
 * -------
 * Instantiate the Locale class
 * -------
 */

	//$locale =& load_class("Locale","core");

/**
 * --------
 * Instantiate the Header class
 * --------
 */

	$header =& load_class("Header","core");

/**
 * --------
 * Instantiate the Uri class.
 * --------
 */

	$uri =& load_class("Uri","core");

/**
 * --------
 * Instantiate the Router class.
 * --------
 */

	$router =& load_class("Router","core");

/**
 * --------
 * Instantiate the hooks class
 * --------
 */

	$hooks =& load_class("Hooks","core");

/**
 * --------
 * Load the core controller, and the app controller.
 * --------
 */
	require BASEPATH."core/Controller.php";

	function &get_instance() {
		return CODR_Controller::get_instance();
	}

	$class 			=& $router->fetch_class();
	$method 		=& $router->fetch_method();
	$method_args 	=& $router->fetch_method_args();

	if (!file_exists(APPPATH."controllers/".$class.EXT))
	{
		show_404("$class/$method");
	}

	include(APPPATH."controllers/".$class.EXT);
/**
 * --------
 * Instantiate the View class.
 * --------
 */

	$view =& load_class("View","core");

/**
 * -------
 * Check if the request tries to access protected core functions,
 * and if the requested class actually exists.
 * -------
 */

	if (
	!class_exists($class) ||
	(strncmp($method,"_",1) == 0) ||
	in_array(strtolower($method), array_map("strtolower",get_class_methods("CODR_Controller")))
	)
	{
		log_message("debug","Kernel.php: Requested class does not exist, or method requested is protected.");
		show_404("$class/$method");
	}

/**
 * -------
 * Instatiate the requested controller.
 * -------
 */

	$codr = new $class();

	if (!in_array(strtolower($method), array_map("strtolower",get_class_methods($class))))
	{
		show_404("$class/$method");
	}

	call_user_func_array([&$codr, $method], $method_args);

/**
 * -------
 * Set final mark.
 * -------
 */

	$profiler->mark("execution_end");

/**
 * -------
 * Send buffered output to browser.
 * -------
 */

	$view->_output();

/**
 * -------
 * Are there any post_system hooks?
 * -------
 */

	$hooks->_call_hook("post_system");
/**
 * -------
 * Close any open DB connections
 * -------
 */

	if (class_exists("CODR_DB") && isset($CODR->db))
	{
		$CODR->db->close();
	}

?>
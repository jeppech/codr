<?php
	/**
	 * If you don't want to use the default
	 * 404 page, the set 404_override to a controller.
	 * --------
	 * $routes["404_override"] = "$class/$method";
	 *
	 * eg.
	 * $routes["404_override"] = "error/404";
	 */

	/**
	 * Set the default controller,
	 * if none could be found in the URI.
	 *
	 * If no method is set in default_controller,
	 * it will by default, use index method.
	 */

	$routes["default_controller"] = "welcome";

	/**
	 * Set some custom routes, if the URI
	 * not should provide any class/method.
	 *
	 * eg.
	 * $routes["login"] = "authenticate/login";
	 */



	/**
	 * The following, "(.+)" (Without quotes),
	 * can be used to match anything.
	 */
?>
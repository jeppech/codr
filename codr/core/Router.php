<?php

	class CODR_Router {

		private $config;
		private $uri;

		public $routes;

		public $class;
		public $method;

		public $overrides;
		public $method_args;

		function __construct()
		{
			$this->uri =& load_class("Uri","core");

			$this->config =& load_class("Config","core");

			log_message("debug","Router class initialized");

			$this->_set_routing();
		}

		private function _set_routing()
		{
			// Include the custom routes, from the router.php config file.
			include APPPATH."config/router.php";

			$this->routes = ((isset($routes) && is_array($routes)) ? $routes : array());
			unset($routes);

			if ($this->uri->uri_string == "")
			{
				$this->_set_default_controller();
			}

			if (count($this->uri->segments) > 0)
			{
				return $this->_validate_request();
			}
		}

		private function _validate_request()
		{
			$segments =& $this->_get_route();

			if (isset($segments[1]))
			{
				$this->_set_class($segments[1]);
			}
			if (!isset($segments[2]))
			{
				/*
				 * If none method is requested, check the
				 * routes config, if other is specified.
				 */
				if (!isset($this->routes[$segments[1]])) {
					$this->_set_method("index");
				}
				else
				{
					$method = explode("/",$this->routes[$segments[1]]);
					$this->_set_method($method[1]);
				}
			}
			else
			{
				$this->_set_method($segments[2]);
			}

			$this->_set_method_args($segments);

			if (!file_exists(APPPATH."controllers/".$segments[1].EXT))
			{
				if (isset($this->routes["404_override"]) && !empty($this->routes["404_override"]))
				{
					$x = explode("/", $this->routes["404_override"]);

					$this->_set_class($x[0]);
					$this->_set_method(isset($x[1]) ? $x[1] : "page_missing");

					return $x;
				}
				else
				{
					show_404($segments[1]);
				}
			}
			return $segments;
		}

		private function &_get_route()
		{
			foreach ($this->routes as $route => $action)
			{
				/* Remove first/last forward slash, and
				 * escape reserved characters */

				$route = trim($route,"/");

				if (preg_match_all("#^".$route."$#", $this->uri->uri_string, $matches))
				{
					array_shift($matches);

					if (preg_match_all("/\$[0-9]+/", $action, $_act_group))
					{

						$_act_group = $_act_group[0];
						$i = 0;

						foreach ($_act_group as $param)
						{
							$action = str_replace($param, $matches[$i][0], $action);

							$i++;
						}
					}

					$_segments_temp = explode("/", trim($action, "/"));
					$_segments = array();

					foreach ($_segments_temp as $k => $v)
					{
						$_segments[$k+1] = $v;
					}
					return $_segments;
				}
			}
			return $this->uri->segments;
		}

		private function _set_default_controller()
		{
			if (!isset($this->routes["default_controller"]))
			{
				exit("Could not determine what to view. Please setup a default controller in the routes.php file");
			}

			$arr_route = explode("/", $this->routes["default_controller"]);

			$this->_set_class($arr_route[0]);

			$method = (isset($arr_route[1]) ? $arr_route[1] : "index");

			$this->_set_method($method);
			$this->_set_method_args($arr_route);
		}

		private function _set_class($class)
		{
			$this->class = $class;
		}

		private function _set_method($method)
		{
			$this->method = $method;
		}

		private function _set_method_args($args)
		{
			// Do not include the class and method.
			$this->method_args = array_slice($args,2);
		}

		public function &fetch_class()
		{
			return $this->class;
		}

		public function &fetch_method()
		{
			return $this->method;
		}

		public function &fetch_method_args()
		{
			return $this->method_args;
		}
	}
?>